<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * Canada Helps Drupal specific stuff goes here
 */
class CRM_Utils_System_CanadaHelpsDrupal extends CRM_Utils_System_Drupal {

  /**
   * Generate an internal CiviCRM URL (copied from DRUPAL/includes/common.inc#url)
   *
   * @inheritDoc
   */
  public function url(
    $path = NULL,
    $query = NULL,
    $absolute = FALSE,
    $fragment = NULL,
    $frontend = FALSE,
    $forceBackend = FALSE,
    $htmlize = TRUE
  ) {
    $config = CRM_Core_Config::singleton();
    $script = 'index.php';

    $path = CRM_Utils_String::stripPathChars($path);

    if (isset($fragment)) {
      $fragment = '#' . $fragment;
    }

    $base = $absolute ? $config->userFrameworkBaseURL : $config->useFrameworkRelativeBase;

    $separator = '&';

    if (isset($path) && stristr($path, 'civicrm/') !== FALSE) {
      $path = str_replace('civicrm/', 'dms/', $path);
    }

    if (!$config->cleanURL) {
      if (isset($path)) {
        if (isset($query)) {
          return $base . $script . '?q=' . $path . $separator . $query . $fragment;
        }
        else {
          return $base . $script . '?q=' . $path . $fragment;
        }
      }
      else {
        if (isset($query)) {
          return $base . $script . '?' . $query . $fragment;
        }
        else {
          return $base . $fragment;
        }
      }
    }
    else {
      if (isset($path)) {
        if (isset($query)) {
          return $base . $path . '?' . $query . $fragment;
        }
        else {
          return $base . $path . $fragment;
        }
      }
      else {
        if (isset($query)) {
          return $base . $script . '?' . $query . $fragment;
        }
        else {
          return $base . $fragment;
        }
      }
    }
  }

}
