<?php

/**
 * @file
 * Contains \Drupal\amazon\AmazonRequest
 */

namespace Drupal\amazon;

use Drupal\Core\Url;
use Drupal\Core\Controller\ControllerBase;
use Drupal\amazon\AmazonHelpers;
use Drupal\amazon\AmazonLocales;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Controller routines for Amazon Product Advertisment API Requests 
 */
class AmazonRequest extends ControllerBase {

  public function __construct(AmazonHelpers $helpers_services, AmazonLocales $locales_services) {  
    $this->helpersServices = \Drupal::service('amazon.amazon_helpers');
    $this->localesServices = \Drupal::service('amazon.amazon_locales');
    $this->storageServices = \Drupal::service('amazon.storage');
  }

  /**
   * Use Amazon API to look up an array of ASINs.
   * @param $item_ids
   *   Array of ASIN strings to look up.
   * @return array
   *   Array of cleaned XML structures keyed by ASIN.
   */
  public function amazon_item_lookup_from_web($item_ids = array(), $locale = NULL) {
    $amazon_limit = 10; // Amazon will accept no more than 10 items
    $asins = array();
    $results = array();
    $item_ids = array_filter($item_ids); // Remove any empty items.
    foreach ($item_ids as $asin) {
      if (!empty($asin)) {
        $asins[] = $asin;
        if (count($asins) >= $amazon_limit || count($asins) == count($item_ids)) {
          $results += _amazon_item_batch_lookup_from_web($asins, $locale);
          $asins = array();
        }
      }
    }
    return $results;
  }

  /**
   * Get 10 or less items from the AWS web service.
   * AWS allows ONLY 10 items,
   * See http://docs.amazonwebservices.com/AWSECommerceService/latest/DG/index.html?ItemLookup.html.
   * @param $item_ids
   *   Array of ASINs to be looked up.
   * @return
   *   Array of ASIN data structures keyed by ASIN.
   */
  public function amazon_item_batch_lookup_from_web($item_ids = array(), $locale = NULL) {
    if (!empty($item_ids)) {
      // Variables
      /* --------------------------------- */
      $items = array();

      // Parameters
      /* --------------------------------- */
      $params = array(
        'ItemId' => implode(',', $item_ids),
        'ResponseGroup' => 'Large',
      );

      // AWS Product API Request
      /* --------------------------------- */
      $results = $this->amazon_http_request('ItemLookup', $params, $locale);

      kint($results);

      // Error Checking
      /* --------------------------------- */
      if (!empty($results->Items->Request->Errors)) {

      }

      // Save AWS Product API Request
      /* --------------------------------- */
      // The Amazon API request DOES NOT return an indentical format for a "single-item" result and "multi-item" result
      // If the "ItemLookRequest" "ItemId" IS NOT an ARRAY we can conclude the returned XML only contains single-item.
      // Otherwise, the XML is treated as containing multiple item arrays within the parent "Item" array.
      /* --------------------------------- */
      if ( !is_array($results['Items']['Request']['ItemLookupRequest']['ItemId']) ) {
        $item = $this->helpersServices->amazon_item_clean_xml($results['Items']['Item']);
        $this->storageServices->amazon_item_insert($item);

        return $item;

      } else {
        foreach ($results['Items']['Item'] as $xml) {
          $item = $this->helpersServices->amazon_item_clean_xml($xml);
          $this->storageServices->amazon_item_insert($item);
          $items["{$item['asin']}"] = $item;
        }
        return $items;
      }
    }
  }


  /**
   * Create an issue an HTTP request to the Amazon API.
   *
   * Most of this is determined by the Amazon Product Advertising API.
   * @see http://docs.amazonwebservices.com/AWSECommerceService/latest/DG/index.html?CHAP_response_elements.html
   *
   *
   * @param $operation
   *   Must be 'AWSECommerceService'
   * @param $parameters
   *   An associative array with the parameters for the API call.
   * @param $locale
   *   The (optional) locale, a 2-character Amazon locale indicator. This has
   *   nothing to do with an actual locale - it's really shorthand for what
   *   Amazon site to use.
   */
  public function amazon_http_request($operation, $parameters = array(), $locale = NULL) {
    // Variables
    $parameters_encoded = array();

    // Set Operation 
    if (!isset($operation)) {
      $operation = 'ItemLookup';
    }

    $amazon_settings = $this->config('amazon.settings');

    // Services
    /*--------------------------------- */

    //Core
    $client_service = \Drupal::httpClient();

    // Module
    $helpers_service = \Drupal::service('amazon.amazon_helpers');
    $locales_service = \Drupal::service('amazon.amazon_locales');
    $encoder_service = \Drupal::service('serializer.encoder.xml');
    

    // Default Parameter Settings || Amazon Product API Essentials
    /*--------------------------------- */
    $parameters += array(
      'Service' => 'AWSECommerceService',
      'Version' => $amazon_settings->get('amazon_schema'),
      'AWSAccessKeyId' => $amazon_settings->get('amazon_aws_access_key'),
      'Operation' => $operation,
      'AssociateTag' => $amazon_settings->get('amazon_associate_id'),
      'Timestamp' => gmdate("Y-m-d\TH:i:s") . 'Z',
    );

    // Natural Order Sort
    uksort($parameters, 'strnatcmp');

    // Encode Parameters for HTTP Request
    foreach ($parameters as $key => $value) {
      if (is_array($value)) {
        $value = implode(',', $value);
      }
      $parameter = str_replace("%7E", "~", rawurlencode($key));
      $value = str_replace("%7E", "~", rawurlencode($value));
      $parameters_encoded[] = $parameter . '=' . $value;
    }

    // Locale || Reference AmazonLocals::amazon_locales_available()
    /*--------------------------------- */
    if (!isset($locale)) { $locale = $amazon_settings->get('amazon_locale'); }
    $amazon_global_locales = $this->localesServices->amazon_locales_available();
    $locale_data = $amazon_global_locales[$locale];

    // Amazon AWS Secret Key
    /*--------------------------------- */
    $secret_access_key = $amazon_settings->get('amazon_aws_secret_access_key');
    if ($secret_access_key == "") {
      // watchdog('amazon', "No Secret Access Key configured. You must configure one at Admin->Settings->Amazon API", NULL, WATCHDOG_ERROR);
      // drupal_set_message(t("Amazon Module: No Secret Access Key is configured. Please contact your site administrator"));
      return FALSE;
    }

    // Signed AWS HTTP Request || http://mierendo.com/software/aws_signed_query
    /*--------------------------------- */
    $query_string = implode('&', $parameters_encoded);
    $parsed_url = parse_url($locale_data['url']);
    $host = strtolower($parsed_url['host']);
    $string_to_sign = "GET\n$host\n{$parsed_url['path']}\n$query_string";

    // Encode Signature
    $signature = base64_encode(hash_hmac('sha256', $string_to_sign, $secret_access_key, TRUE));
    $signature = str_replace("%7E", "~", rawurlencode($signature));
    // $query_string .= "&Signature=$signature";

    $url = $locale_data['url'] . '?' . $query_string . "&Signature=$signature";
    try {
      $results = $client_service->request('GET', $url);
      $status_code = $results->getStatusCode();
      $xml = $results->getBody()->getContents();
    }
    catch(\Exception $e) {

    }
    catch(\RequestException $e) {

    }
    catch(\ClientException $e) {

    }
    catch(\ClientException $e) {

    }
    catch(\ServerException $e) {

    }
    if ($status_code == 200) {
      $xml_decoded = $encoder_service->decode($xml);
      return $xml_decoded;
    }
    if ($results->code >= 400 && $results->code < 500) {
      try {
        $xml_decoded = $encoder_service->decode($xml);
      }
      catch (Exception $e) {
        // watchdog('amazon', "Error handling results: http_code=%http_code, data=%data.", array('%http_code' => $results->code, '%data' => (string) $results->data) );
        return FALSE;
      }
      // watchdog('amazon', "HTTP code %http_code accessing Amazon's AWS service: %code, %message", array('%http_code' => $results->code, '%code' => (string) $xml->Error->Code, '%message' => (string) $xml->Error->Message));
      return FALSE;
    }
    // watchdog('amazon', "Error accessing Amazon AWS web service with query '%url'. HTTP result code=%code, error=%error", array('%code' => $results->code, '%error' => $results->error, '%url' => $url));
    return FALSE;
  }


  function amazon_item_batch_lookup_from_web_errors($errors) {
    foreach ($errors->Error as $error) {
      $code = (string) $error->Code;
      $message = (string) $error->Message;
      $matches = array();
      // Find and extract the failing ASIN, so we can mark it in the db.
      if (preg_match('/^([^ ]+) is not a valid value for ItemId/', $message, $matches)) {
        $error_asin = $matches[1];
        $update_fields = array('invalid_asin' => TRUE);
        try {
          $result = db_update('amazon_item')
          ->fields($update_fields)
          ->condition('asin', $error_asin)
          ->execute();
        }
        catch(Exception $e) {
          amazon_db_error_watchdog('Failed to update invalid_asin=TRUE on amazon_item.', $e);
        }
      }
      watchdog('amazon', 'Error retrieving Amazon item %code, message: %message.', array('%code' => $code, '%message' => $message), WATCHDOG_WARNING);
    }
  }

}
