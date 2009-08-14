<?php
// $Id: theme-settings.php,v 1.1 2009/07/12 01:38:18 himerus Exp $

// Include the definition of zen_settings() and zen_theme_get_default_settings().
include_once './' . drupal_get_path('theme', 'omega') . '/theme-settings.php';
include_once './' . drupal_get_path('theme', 'omega') . '/theme-functions.inc';
/**
 * Implementation of THEMEHOOK_settings() function.
 *
 * @param $saved_settings
 *   An array of saved settings for this theme.
 * @return
 *   A form array.
 */
function polished_settings($saved_settings) {
  // Get the default values from the .info file.
  $defaults = omega_theme_get_default_settings('polished');
  // Merge the saved variables and their default values.
  $settings = array_merge($defaults, $saved_settings);
  $form = array();
  // Add the base theme's settings.
  $form += omega_settings($saved_settings, $defaults);
  // Return the form
  return $form;
}
