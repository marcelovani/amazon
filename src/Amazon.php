<?php
/**
 * @file
 * Contains Drupal\amazon\Amazon
 */

namespace Drupal\amazon;

use Behat\Mink\Exception\Exception;
use Drupal\Core\Url;
use Drupal\amazon\AmazonRequest;

/**
 * Provides methods that interfaces with the Amazon Product Advertising API.
 *
 * @package Drupal\amazon
 */
class Amazon {

  /**
   * The server environment variables for (optionally) specifying the access
   * key and secret.
   */
  const AMAZON_ACCESS_KEY = 'AMAZON_ACCESS_KEY';
  const AMAZON_ACCESS_SECRET = 'AMAZON_ACCESS_SECRET';

  /**
   * @var string
   *   Cache for the Amazon access key.
   */
  protected $accessKey;

  /**
   * @var string
   *   Cache for the Amazon access secret.
   */
  protected $accessSecret;

  /**
   * @var string
   *   Cache for the Amazon Associates ID (aka tag).
   */
  protected $associatesId;


  /**
   * Provides an Amazon object for calling the Amazon API.
   *
   * @param string $associatesId
   *   The Amazon Associates ID (a.k.a. tag).
   * @param string $accessKey
   *   (optional) Access key to use for all API requests. If not specified, the
   *   access key is determined from other system variables.
   * @param string $accessSecret
   *   (optional) Access secret to use for all API requests. If not specified,
   *   the access key is determined from other system variables.
   */
  public function __construct($associatesId, $accessKey = '', $accessSecret = '') {
    $this->associatesId = $associatesId;
    if (empty($accessKey)) {
      $this->accessKey = self::getAccessKey();
    }
    else {
      $this->accessKey = $accessKey;
    }
    if (empty($accessSecret)) {
      $this->accessSecret = self::getAccessSecret();
    }
    else {
      $this->accessSecret = $accessKey;
    }
  }

  /**
   * Returns the secret key needed for API calls.
   */
  static public function getAccessSecret() {
    // Use credentials from environment variables, if available.
    $secret = getenv(self::AMAZON_ACCESS_SECRET);
    if ($secret) {
      return $secret;
    }

    // If not, use Drupal config variables. (Automatically handles overrides
    // in settings.php.)
    $secret = \Drupal::config('amazon.configuration')->get('access_secret');
    if ($secret) {
      return $secret;
    }

    return FALSE;
  }

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

  /**
   * Gets information about an item, or array of items, from Amazon.
   *
   * @param array|string $items
   *   A string containing a single ASIN or an array of ASINs to look up.
   *
   * @return array
   *   An array in the form of asin => response.
   *
   * @TODO: Generalize beyond just ASINs.
   */
  public function lookup($items) {
    if (empty($items)) {
      throw new \InvalidArgumentException('Calling lookup without anything to lookup!');
    }
    if (!is_array($items)) {
      $items = [$items];
    }
    if (empty($this->accessKey) || empty($this->associatesId) || empty($this->accessSecret)) {
      throw new \LogicException('Lookup called without valid access key, secret, or associates ID.');
    }

    $results = [];
    foreach(array_chunk($items, 10) as $asins) {
      $request = new AmazonRequest($this->accessSecret, $this->accessKey, $this->associatesId);
      $request->setOptions([
        'Service' => 'AWSECommerceService',
        'ItemId' => implode(',', $asins),
        'ResponseGroup' => 'Small',
        'Operation' => 'ItemLookup',
      ]);
      $results = array_merge($results, $request->execute()->getResults());
    }
    return $results;
  }

}
