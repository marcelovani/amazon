<?php
/**
 * @file
 * Contains Drupal\amazon\Amazon
 */

namespace Drupal\amazon;

use Behat\Mink\Exception\Exception;
use Drupal\Core\Url;

/**
 * Provides methods that interfaces with the Amazon Product Advertising API.
 *
 * @package Drupal\amazon
 */
class Amazon {

  /**
   * The server environment variables for (optionally) specifying the access
   * key.
   */
  const AMAZON_ACCESS_KEY = 'AMAZON_ACCESS_KEY';

  /**
   * @var string
   *   Cache for the Amazon access key.
   */
  protected $accessKey;

  /**
   * @var string
   *   Cache for the Amazon Associates ID (aka tag).
   */
  protected $associatesId;


  /**
   * Provides an Amazon object for calling the Amazon API.
   *
   * @param string $associatesId
   *   (optional) The Amazon Associates ID (a.k.a. tag).
   * @param string $accessKey
   *   (optional) Access key to use for all API requests. If not specified, the
   *   access key is determined from other system variables.
   */
  public function __construct($associatesId = '', $accessKey = '') {
    if (empty($accessKey)) {
      $this->accessKey = self::getAccessKey();
    }
    else {
      $this->accessKey = $accessKey;
    }
    $this->associatesId = $associatesId;
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
   * Gets information about an item from Amazon.
   *
   * @param array|string $items
   *   A string containing a single ASIN or an array of ASINs to look up.
   *
   * @return array
   *   An array in the form of asin => response.
   *
   * @see http://docs.aws.amazon.com/AWSECommerceService/latest/DG/ItemLookup.html
   *
   * @TODO: This should be generalized and moved to an Amazon object in the
   *        parent module.
   * @TODO: Generalize beyond just ASINs.
   * @TODO: Generalize locale (Amazon's locales, not Drupal's...)
   */


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
   * @TODO: Generalize locale (Amazon's locales, not Drupal's...)
   */
  public function lookup($items) {
    if (empty($items)) {
      throw new \InvalidArgumentException ('Calling lookup without anything to lookup!');
    }
    if (!is_array($items)) {
      $items = [$items];
    }

    foreach(array_chunk($items, 10) as $asins) {
      $params = [
        'Service' => 'AWSECommerceService',
        'AWSAccessKeyId' => $this->accessKey,
        'AssociateTag' => $this->associatesId,
        'ItemId' => implode(',', $asins),
        'ResponseGroup' => 'Small',
        'Operation' => 'ItemLookup',
      ];
      $uri = Url::fromUri('http://webservices.amazon.com/onca/xml', ['query' => $params]);

      try {
        $data = (string) \Drupal::httpClient()
          ->get($uri->toString());
      }
      catch(Exception $e) {
        dpr($e); exit;
      }

      dpr($data); exit;
    }
  }

}
