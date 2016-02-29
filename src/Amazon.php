<?php
/**
 * @file
 * Contains Drupal\amazon_filter\Amazon
 */

namespace Drupal\amazon_filter;

/**
 * Class Amazon
 *
 * A collection of methods that interfaces with the Amazon Product API.
 *
 * @package Drupal\amazon_filter
 */
class Amazon {

  const AMAZON_ACCESS_KEY = 'AMAZON_ACCESS_KEY';

  /**
   * Returns the access key needed for API calls.
   */
  static public function getAccessKey() {
    // Use credentials from environment variables, if available.
    $key = getenv(self::AMAZON_ACCESS_KEY);
    if ($key) {
      return $key;
    }

    // If not, use Drupal config variables. (Automatically handles overrides
    // in settings.php.)
    $key = \Drupal::config('amazon.configuration')->get('access_key');
    if ($key) {
      return $key;
    }

    return FALSE;
  }

}
