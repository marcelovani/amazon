<?php

/**
 * @file
 * Contains \Drupal\amazon_api\Controller\AmazonAdmin.
 */

namespace Drupal\amazon\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Controller routines for page example routes.
 */
class AmazonAPI extends ControllerBase {

  function amazon_get_associate_id($locale = NULL) {
    if (!($locale)) {
      $locale = variable_get('amazon_default_locale', 'US');
    }
    $cache = amazon_data_cache();
    return variable_get('amazon_locale_'. $locale .'_associate_id', $cache['locales'][$locale]['da_associate_id']);
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
  function amazon_http_request($operation, $parameters = array(), $locale = NULL) {
    if (!isset($locale)) {
      $locale = variable_get('amazon_locale', 'US');
    }
    $metadata = amazon_data_cache();
    $locale_data = $metadata['locales'][$locale];

    // Default Parameter Settings
    $parameters += array(
      'Service' => 'AWSECommerceService',
      'Version' => AMAZON_ECS_SCHEMA,
      'AWSAccessKeyId' => variable_get('amazon_aws_access_key', ''),
      'Operation' => $operation,
    );

    // Add Associate Tag
    if ($associate_id = amazon_get_associate_id($locale)) {
      $parameters += array(
        'AssociateTag' => $associate_id,
      );
    }

    // Add Timestamp
    $parameters += array('Timestamp' => gmdate("Y-m-d\TH:i:s") . 'Z');

    // Natural Order Sort
    uksort($parameters, 'strnatcmp');

    $params = array();
    foreach ($parameters as $key => $value) {
      if (is_array($value)) {
        $value = implode(',', $value);
      }
      $param = str_replace("%7E", "~", rawurlencode($key));
      $value = str_replace("%7E", "~", rawurlencode($value));
      $params[] = $param . '=' . $value;
    }

    // Amazon AWS Secret Key
    $secret_access_key = variable_get('amazon_aws_secret_access_key', "");
    if ($secret_access_key == "") {
      watchdog('amazon', "No Secret Access Key configured. You must configure one at Admin->Settings->Amazon API", NULL, WATCHDOG_ERROR);
      drupal_set_message(t("Amazon Module: No Secret Access Key is configured. Please contact your site administrator"));
      return FALSE;
    }
    // Thanks for signature creation code from http://mierendo.com/software/aws_signed_query/
    $query_string = implode('&', $params);
    $parsed_url = parse_url($locale_data['url']);
    $host = strtolower($parsed_url['host']);
    $string_to_sign = "GET\n$host\n{$parsed_url['path']}\n$query_string";

    $signature = base64_encode(hash_hmac('sha256', $string_to_sign, $secret_access_key, TRUE));
    $signature = str_replace("%7E", "~", rawurlencode($signature));
    $query_string .= "&Signature=$signature";

    $url = $locale_data['url'] . '?' . $query_string;
    // Make the request and return a SimpleXML object.
    $results = drupal_http_request($url, array('method' => 'GET'));
    if ($results->code == 200) {
      $xml = new SimpleXMLElement($results->data);
      return $xml;
    }
    if ($results->code >= 400 && $results->code < 500) {
      try {
        $xml = new SimpleXMLElement($results->data);
      }
      catch (Exception $e) {
        watchdog('amazon', "Error handling results: http_code=%http_code, data=%data.", array('%http_code' => $results->code, '%data' => (string) $results->data) );
        return FALSE;
      }
      watchdog('amazon', "HTTP code %http_code accessing Amazon's AWS service: %code, %message", array('%http_code' => $results->code, '%code' => (string) $xml->Error->Code, '%message' => (string) $xml->Error->Message));
      return FALSE;
    }
    watchdog('amazon', "Error accessing Amazon AWS web service with query '%url'. HTTP result code=%code, error=%error", array('%code' => $results->code, '%error' => $results->error, '%url' => $url));
    return FALSE;
  }

  /**
   * Use Amazon API to look up an array of ASINs.
   * @param $item_ids
   *   Array of ASIN strings to look up.
   * @return array
   *   Array of cleaned XML structures keyed by ASIN.
   */
  public static function amazon_item_lookup_from_web($item_ids = array(), $locale = NULL) {
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
  function _amazon_item_batch_lookup_from_web($item_ids = array(), $locale = NULL) {
    if (!empty($item_ids)) {
      $params = array(
        'ItemId' => implode(',', $item_ids),
        'ResponseGroup' => amazon_get_response_groups(),
      );
      $results = amazon_http_request('ItemLookup', $params, $locale);
      if (!empty($results->Items->Request->Errors)) {
        _amazon_item_batch_lookup_from_web_errors($results->Items->Request->Errors);
      }
      $items = array();
      if (!empty($results->Items->Item)) {
        foreach ($results->Items->Item as $xml) {
          $item = amazon_item_clean_xml($xml);
          amazon_item_insert($item);
          $items["{$item['asin']}"] = $item;
        }
      }
      return $items;
    }
    return array();
  }

  function _amazon_item_batch_lookup_from_web_errors($errors) {
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

  /**
   * Constructs a page with descriptive content.
   *
   * Our router maps this method to the path 'examples/page_example'.
   */
  public function description() {
    // Make our links. First the simple page.
    $simple_url = Url::fromRoute('page_example_simple');
    $page_example_simple_link = $this->l($this->t('simple page'), $simple_url);
    // Now the arguments page.
    $arguments_url = Url::fromRoute('page_example_arguments', array('first' => '23', 'second' => '56'));
    $page_example_arguments_link = $this->l($this->t('arguments page'), $arguments_url);

    // Assemble the markup.
    $build = array(
      '#markup' => $this->t('<p>The Page example module provides two pages, "simple" and "arguments".</p><p>The @simple_link just returns a renderable array for display.</p><p>The @arguments_link takes two arguments and displays them, as in @arguments_url</p>',
        array(
          '@simple_link' => $page_example_simple_link,
          '@arguments_link' => $page_example_arguments_link,
          '@arguments_url' => $arguments_url->toString(),
        )
      ),
    );

    return $build;
  }

  /**
   * Constructs a simple page.
   *
   * The router _controller callback, maps the path
   * 'examples/page_example/simple' to this method.
   *
   * _controller callbacks return a renderable array for the content area of the
   * page. The theme system will later render and surround the content with the
   * appropriate blocks, navigation, and styling.
   */
  public function simple() {
    return array(
      '#markup' => '<p>' . $this->t('Simple page: The quick brown fox jumps over the lazy dog.') . '</p>',
    );
  }

  /**
   * A more complex _controller callback that takes arguments.
   *
   * This callback is mapped to the path
   * 'examples/page_example/arguments/{first}/{second}'.
   *
   * The arguments in brackets are passed to this callback from the page URL.
   * The placeholder names "first" and "second" can have any value but should
   * match the callback method variable names; i.e. $first and $second.
   *
   * This function also demonstrates a more complex render array in the returned
   * values. Instead of rendering the HTML with theme('item_list'), content is
   * left un-rendered, and the theme function name is set using #theme. This
   * content will now be rendered as late as possible, giving more parts of the
   * system a chance to change it if necessary.
   *
   * Consult @link http://drupal.org/node/930760 Render Arrays documentation
   * @endlink for details.
   *
   * @param string $first
   *   A string to use, should be a number.
   * @param string $second
   *   Another string to use, should be a number.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   If the parameters are invalid.
   */
  public function arguments($first, $second) {
    // Make sure you don't trust the URL to be safe! Always check for exploits.
    if (!is_numeric($first) || !is_numeric($second)) {
      // We will just show a standard "access denied" page in this case.
      throw new AccessDeniedHttpException();
    }

    $list[] = $this->t("First number was @number.", array('@number' => $first));
    $list[] = $this->t("Second number was @number.", array('@number' => $second));
    $list[] = $this->t('The total was @number.', array('@number' => $first + $second));

    $render_array['page_example_arguments'] = array(
      // The theme function to apply to the #items
      '#theme' => 'item_list',
      // The list itself.
      '#items' => $list,
      '#title' => $this->t('Argument Information'),
    );
    return $render_array;
  }

}
