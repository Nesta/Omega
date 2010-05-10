<?php
//$Id$
// Report all PHP errors (see changelog)

/**
 * Implementation of hook_preprocess()
 * 
 * This function checks to see if a hook has a preprocess file associated with 
 * it, and if so, loads it.
 * 
 * This makes it easier to keep sorted the preprocess functions that can be present in the 
 * template.php file. You may still use hook_preprocess_page in template.php
 * or create a file preprocess-page.inc in the preprocess folder to include the appropriate
 * logic to your preprocess functionality
 * 
 * @param $vars
 * @param $hook
 * @return Array
 */
function omega_preprocess(&$vars, $hook) {
  // Collect all information for the active theme.
  $themes_active = array();
  global $theme_info;
  //krumo($theme_info);
  // If there is a base theme, collect the names of all themes that may have 
  // preprocess files to load.
  if(isset($theme_info->base_theme)) {
    global $base_theme_info;
    foreach($base_theme_info as $base){
      $themes_active[] = $base->name;
    }
  }

  // Add the active theme to the list of themes that may have preprocess files.
  $themes_active[] = $theme_info->name;

  // Check all active themes for preprocess files that will need to be loaded.
  foreach($themes_active as $name) {
    if(is_file(drupal_get_path('theme', $name) . '/preprocess/preprocess-' . str_replace('_', '-', $hook) . '.inc')) {
      include(drupal_get_path('theme', $name) . '/preprocess/preprocess-' . str_replace('_', '-', $hook) . '.inc');
    }
  }
}
/**
 * Preprocessor for page.tpl.php template file.
 * The default functionality can be found in preprocess/preprocess-page.inc
 */
function omega_preprocess_page(&$vars) {
  if (isset($vars['main_menu'])) {
    $vars['primary_nav'] = theme('links__system_main_menu', array(
      'links' => $vars['main_menu'],
      'attributes' => array(
        'class' => array('links', 'main-menu'),
      ),
      'heading' => array(
        'text' => t('Main menu'),
        'level' => 'h2',
        'class' => array('element-invisible'),
      )
    ));
  }
  else {
    $vars['primary_nav'] = FALSE;
    drupal_set_message('<strong>$vars[main_menu]</strong> not present... WTF...', 'error');
  }
  if (isset($vars['secondary_menu'])) {
    $vars['secondary_nav'] = theme('links__system_secondary_menu', array(
      'links' => $vars['secondary_menu'],
      'attributes' => array(
        'class' => array('links', 'secondary-menu'),
      ),
      'heading' => array(
        'text' => t('Secondary menu'),
        'level' => 'h2',
        'class' => array('element-invisible'),
      )
    ));
  }
  else {
    $vars['secondary_nav'] = FALSE;
    drupal_set_message('<strong>$vars[secondary_menu]</strong> not present... WTF...', 'error');
  }
  /*
  if(function_exists('krumo')) {
  	krumo($vars);
  }*/
} // end preprocess_page
function omega_preprocess_node(&$vars) {
  
} // end preprocess_node

function omega_process_page(&$vars) {
  
}
function omega_process_node(&$vars) {
  
}
/** 
 * NINESIXTY - Contextually adds 960 Grid System classes.
 *
 * The first parameter passed is the *default class*. All other parameters must
 * be set in pairs like so: "$variable, 3". The variable can be anything available
 * within a template file and the integer is the width set for the adjacent box
 * containing that variable.
 *
 *  class="<?php print ns('grid-16', $var_a, 6); ?>"
 *
 * If $var_a contains data, the next parameter (integer) will be subtracted from
 * the default class. See the README.txt file.
 */
function ns() {
  $args = func_get_args();
  $default = array_shift($args);
  // Get the type of class, i.e., 'grid', 'pull', 'push', etc.
  // Also get the default unit for the type to be procesed and returned.
  list($type, $return_unit) = explode('-', $default);

  // Process the conditions.
  $flip_states = array('var' => 'int', 'int' => 'var');
  $state = 'var';
  foreach ($args as $arg) {
    if ($state == 'var') {
      $var_state = !empty($arg);
    }
    elseif ($var_state) {
      $return_unit = $return_unit - $arg;
    }
    $state = $flip_states[$state];
  }

  $output = '';
  // Anything below a value of 1 is not needed.
  if ($return_unit > 0) {
    $output = $type . '-' . $return_unit;
  }
  return $output;
}
/**
 * NINESIXTY - This rearranges how the style sheets are included so the framework styles
 * are included first.
 *
 * Sub-themes can override the framework styles when it contains css files with
 * the same name as a framework style. This can be removed once Drupal supports
 * weighted styles.
 */
function omega_css_reorder($css) {
  global $theme_info, $base_theme_info;

  // Dig into the framework .info data.
  $framework = !empty($base_theme_info) ? $base_theme_info[0]->info : $theme_info->info;

  // Pull framework styles from the themes .info file and place them above all stylesheets.
  if (isset($framework['stylesheets'])) {
    foreach ($framework['stylesheets'] as $media => $styles_from_960) {
      // Setup framework group.
      if (isset($css[$media])) {
        $css[$media] = array_merge(array('framework' => array()), $css[$media]);
      }
      else {
        $css[$media]['framework'] = array();
      }
      foreach ($styles_from_960 as $style_from_960) {
        // Force framework styles to come first.
        if (strpos($style_from_960, 'framework') !== FALSE) {
          $framework_shift = $style_from_960;
          $remove_styles = array($style_from_960);
          // Handle styles that may be overridden from sub-themes.
          foreach ($css[$media]['theme'] as $style_from_var => $preprocess) {
            if ($style_from_960 != $style_from_var && basename($style_from_960) == basename($style_from_var)) {
              $framework_shift = $style_from_var;
              $remove_styles[] = $style_from_var;
              break;
            }
          }
          $css[$media]['framework'][$framework_shift] = TRUE;
          foreach ($remove_styles as $remove_style) {
            unset($css[$media]['theme'][$remove_style]);
          }
        }
      }
    }
  }
  return $css;
}

/**
 * The region_builder function will create the variables needed to create
 * a dynamic group of regions. This function is simply a quick pass-thru
 * that will create either inline or stacked regions. This function will
 * not do any advanced functionality, but simply assing the appropriate 
 * classes based on the settings for the theme.
 * 
 * For a more advanced set of regions, dynamic_region_builder() will be used.
 */
function static_region_builder($region_data, $container_width, $vars) {
  // let's cycle the region data, and determine what we have
  foreach ($region_data AS $region => $info) {
    // if we do have content for this region, let's create it.
    if ($info['data']) {
      $vars[$region .'_classes'] = ns('grid-'. $info['width']);
    }
    if (isset($info['spacing'])) {
      foreach ($info['spacing'] AS $attribute => $value) {
        if ($value) {
          $vars[$region .'_classes'] .= ' '. $attribute .'-'. $value;
        } 
      }
    }
  }
  return $vars;
}


function _omega_dynamic_zones($width, $conditions, $vars) {
  foreach($conditions AS $variable => $reaction) {
    if(($reaction['type'] && $vars['page'][$variable]) || (!$reaction['type'] && !$vars['page'][$variable])) {
      $width = $width - $reaction['value'];
    }
  }
  return $width;
}
function _omega_dynamic_widths($width, $conditions, $vars) {
  foreach($conditions AS $variable => $zone) {
    if(($vars['page'][$variable])) {
      $width = $width - $zone['width'];
    }
  }
  return $width;
}
/**
 * The dynamic_region_builder function will be used to pass important zones
 * like the content regions where the regions sent to the function MUST appear
 * inline, and advanced calculations need to be done in order to display the as such
 * 
 * Stacked regions are not possible using this function, and should be passed through
 * static_region_builder() instead.
 */
function dynamic_region_builder($region_data, $container_width, $vars) {
  // let's cycle the region data, and determine what we have
  foreach ($region_data AS $region => $info) {
    // if we do have content for this region, let's create it.
    if (isset($info['data'])) {
      if(isset($info['primary'])) {
      	$width = $container_width;
      	$vars[$region .'_classes'] = ns('grid-'. _omega_dynamic_widths($width, $info['related'], $vars));
      }
      else {
      	$width = $info['width'];
      	$vars[$region .'_classes'] = ns('grid-'. $info['width']);
      }
      //$width = $info['primary'] ? $container_width : $info['width'];
      //$vars[$region .'_classes'] = $info['primary'] ?  ns('grid-'. _omega_dynamic_widths($width, $info['related'], $vars)) : ns('grid-'. $info['width']);
      // we know we have stuff to put here, so we can check for push & pull options
      if($info['pull']) {
      	// looks like we do wanna pull, or this value would have been false, so let's boogie
      	$vars[$region .'_classes'] .= ' '. ns('pull-'. _omega_dynamic_zones($info['pull']['width'], $info['pull']['conditions'], $vars));
      	//krumo('Pulling '. $region .' '. $vars[$region .'_classes']);
      }
      if($info['push']) {
      	// looks like a push
      	$vars[$region .'_classes'] .= ' '. ns('push-'. _omega_dynamic_zones($info['push']['width'], $info['push']['conditions'], $vars));
      	//krumo('Pushing '. $region .' '. $vars[$region .'_classes']);
      	//krumo('Should be pushing '. $info['push']['width'] .' grids.');
      	//krumo($info['push']['conditions']);
      }
    }
    // currently ignored becuase we have not given prefix/suffix class options
    // to the primary content zones... this will become active again later
    if (isset($info['spacing'])) {
      foreach ($info['spacing'] AS $attribute => $value) {
        if ($value) {
          $vars[$region .'_classes'] .= ' '. $attribute .'-'. $value;
        } 
      }
    }
    // \unused prefix/suffix stuffs
  }
  return $vars;
}

/**
 * The rfilter function takes one argument, an array of values for the regions 
 * for a "group" of regions like preface or postscript 
 * @param $vars
 */
function rfilter($vars) {
	return count(array_filter($vars));
}

/**
 * OMEGA - A function to return the alpha and or omega classes based on context
 * This function is not currently being used.
 * @param $vars
 * @param $elements
 * @param $current
 * @param $alpha
 * @param $omega
 * @return classes
 */
function ao($vars, $elements, $current, $alpha = FALSE, $omega = FALSE){
  $classes = array();
  $regions = array();
  // let's get rid of empty elements first
  foreach($elements AS $k => $r) {
    if($vars[$r]) {
      $regions[$k] = $r;
    }
  }
  // now we do another fast loop since we emptied out any blank zones to determine what
  // regions are alpha & omega
  foreach($regions AS $k => $r) {
    $classes[$r] = '';
    if(!$alpha){
      $alpha = TRUE;
      $classes[$r] .= ' alpha';
    }
    if(!$omega && ($k == count($regions) - 1 || count($regions) == 1 )) {
      $omega = TRUE;
      $classes[$r] .= ' omega';
    }
  }
  return $classes[$current];
}

/**
 * Converts a string to a suitable html ID attribute.
 *
 * http://www.w3.org/TR/html4/struct/global.html#h-7.5.2 specifies what makes a
 * valid ID attribute in HTML. This function:
 *
 * - Ensure an ID starts with an alpha character by optionally adding an 'id'.
 * - Replaces any character except alphanumeric characters with dashes.
 * - Converts entire string to lowercase.
 *
 * @param $string
 *   The string
 * @return
 *   The converted string
 */
function omega_id_safe($string) {
  // Replace with dashes anything that isn't A-Z, numbers, dashes, or underscores.
  $string = strtolower(preg_replace('/[^a-zA-Z0-9-]+/', '-', $string));
  // If the first character is not a-z, add 'id' in front.
  if (!ctype_lower($string{0})) { // Don't use ctype_alpha since its locale aware.
    $string = 'id' . $string;
  }
  return $string;
}

/**
 * ZEN - Return a themed breadcrumb trail.
 *
 * @param $breadcrumb
 *   An array containing the breadcrumb links.
 * @return
 *   A string containing the breadcrumb output.
 */
/*
function omega_breadcrumb($breadcrumb) {
  // Determine if we are to display the breadcrumb.
  $show_breadcrumb = theme_get_setting('omega_breadcrumb');
  if ($show_breadcrumb == 'yes' || $show_breadcrumb == 'admin' && arg(0) == 'admin') {

    // Optionally get rid of the homepage link.
    $show_breadcrumb_home = theme_get_setting('omega_breadcrumb_home');
    if (!$show_breadcrumb_home) {
      array_shift($breadcrumb);
    }

    // Return the breadcrumb with separators.
    if (!empty($breadcrumb)) {
      $breadcrumb_separator = theme_get_setting('omega_breadcrumb_separator');
      $trailing_separator = $title = '';
      if (theme_get_setting('omega_breadcrumb_title')) {
        $trailing_separator = $breadcrumb_separator;
        $title = menu_get_active_title();
      }
      elseif (theme_get_setting('omega_breadcrumb_trailing')) {
        $trailing_separator = $breadcrumb_separator;
      }
      return '<div class="breadcrumb">' . implode($breadcrumb_separator, $breadcrumb) . "$trailing_separator$title</div>";
    }
  }
  // Otherwise, return an empty string.
  return '';
}
*/
/**
 * Create a string of attributes form a provided array.
 * 
 * @param $attributes
 * @return string
 */
function omega_render_attributes($attributes) {
  if ($attributes) {
    $items = array();
    foreach($attributes as $attribute => $data) {
      if(is_array($data)) {
        $data = implode(' ', $data);
      }
      $items[] = $attribute . '="' . $data . '"';
    }
    $output = ' ' . implode(' ', $items);
  }
  return $output;
}

/**
 * Implementation of hook_theme().
 *
 * @return
 */
function omega_theme(&$existing, $type, $theme, $path) {
  //include_once './' . drupal_get_path('theme', 'omega') . '/theme-functions.inc';
  // Since we are rebuilding the theme registry and the theme settings' default
  // values may have changed, make sure they are saved in the database properly.
  //omega_theme_get_default_settings($theme);
  return array(
    'id_safe' => array(
      'arguments' => array('string'),
    ),
    'render_attributes' => array(
      'arguments' => array('attributes'),
    ),
  );
}// */

