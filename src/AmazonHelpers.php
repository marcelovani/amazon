<?php

/**
 * @file
 * Contains \Drupal\amazon\AmazonHelpers
 */

namespace Drupal\amazon;

use Drupal\Core\Url;
use Drupal\Core\Controller\ControllerBase;
use Drupal\amazon\AmazonLocales;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Helper Functions for Amazon Product Advertisment API Requests 
 */
class AmazonHelpers {

  /**
   * The node storage.
   *
   * @var \Amazon\src\AmazonLocales
   */
  protected $localesServices;

  /**
   * Constructs a AmazonHelpers object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entityManager
   *   The entity manager.
   * @param \Drupal\book\BookManagerInterface $book_manager
   *   The book manager.
   */
  public function __construct(AmazonLocales $locales_services) {
    $this->localesServices = \Drupal::service('amazon.amazon_locales');
  } 

  public function amazon_cache_request($data_request = 'locales', $reset = FALSE) {
    $data_manipulate = array();
    $data_return = array();
    switch ($data_request) {
      // Transform the the "amazon_default_locals" into select list 
      case 'locales':
        $data_manipulate = $this->localesServices->amazon_locales_available();
        foreach ($data_manipulate as $locale_key => $locale_object) {
          $data_return[$locale_key] = $locale_key;
        }
        break;
    }
    return $data_return;
  }

  /**
   * Get Amazon API Product Advertisment Database Schemas .
   *
   * @param string $locale
   *   An array containing all the fields of the database record.
   *
   * @return string
   *   Assoicated ID for Locale
   */
  function amazon_get_associate_id($locale = NULL) {
    $amazon_settings = $this->config('amazon.settings');
    return $amazon_settings->get('amazon_associate_id');
  }

  /**
   * Get Amazon API Product Advertisment Database Schemas .
   *
   * @param string $database_scehma
   *   An array containing all the fields of the database record.
   *
   * @return array
   *   The "amazon_item" schema define in the amazon.install file
   */
  public static function amazon_item_schema_keys($database_schema = 'amazon_item') {
    $item_keys = NULL;

    if (empty($item_keys)) {
      // require_once('amazon.install');
      module_load_include($type, $module = "amazon", $name = "install");
      $schema = amazon_schema();
      $item_keys = $schema[$database_schema]['fields'];
    }

    // Return
    return $item_keys;
  }

  /**
   * Get Amazon API Product Advertisment Database Schemas .
   *
   * @param string $database_scehma
   *   An array containing all the fields of the database record.
   *
   * @return array
   *   The "amazon_item" schema define in the amazon.install file
   */
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

  /**
   * Take the Amazon XML item and turn it into our own private 'cleaned'
   * data structure.
   * @param $xml
   *   XML structure as returned from Amazon API call.
   * @return
   *   'Cleaned' XML structure for local use.
   */
  public function amazon_item_clean_xml($amazon_product) {
    $item = array();

    kint($amazon_product);

    // General Product Information
    /* --------------------------------- */
    $item['asin'] = $amazon_product['ASIN'];
    $item['title'] = $amazon_product['ItemAttributes']['Title'];
    if (!empty($amazon_product['ItemAttributes']['ISBN'])) {
      $item['isbn'] = $amazon_product['ItemAttributes']['ISBN'];
    }
    if (!empty($amazon_product['ItemAttributes']['EAN'])) {
      $item['ean'] = $amazon_product['ItemAttributes']['EAN'];
    }
    $item['salesrank'] = intval($amazon_product['SalesRank']);
    $item['detailpageurl'] = $amazon_product['DetailPageURL'];

    // Pricing & Offers
    /* --------------------------------- */
    if (!empty($amazon_product['ItemAttributes']['ListPrice'])) {
      $item['listpriceamount'] = intval($amazon_product['ItemAttributes']['ListPrice']['Amount']);
      $item['listpricecurrencycode'] = $amazon_product['ItemAttributes']['ListPrice']['CurrencyCode'];
      $item['listpriceformattedprice'] = $amazon_product['ItemAttributes']['ListPrice']['FormattedPrice'];
    }
    if (!empty($amazon_product['OfferSummary']['LowestNewPrice'])) {
      $item['lowestpriceamount'] = intval($amazon_product['OfferSummary']['LowestNewPrice']['Amount']);
      $item['lowestpricecurrencycode'] = (string) $amazon_product['OfferSummary']['LowestNewPrice']['CurrencyCode'];
      $item['lowestpriceformattedprice'] = (string) $amazon_product['OfferSummary']['LowestNewPrice']['FormattedPrice'];
    }

    // Editorial Review
    /* --------------------------------- */
    if (!empty($amazon_product['EditorialReviews'])) {
      foreach ($amazon_product['EditorialReviews'] as $editorialreview_key => $editorialreview_object) {
        $item['editorialreview'][str_replace(" ", "_", strtolower(($editorialreview_object["Source"])) )]['title'] = $editorialreview_object['Source'];
        $item['editorialreview'][str_replace(" ", "_", strtolower(($editorialreview_object["Source"])) )]['content'] = $editorialreview_object['Content'];
      }
    }

    // Imagesets
    /* --------------------------------- */
    // Certain Amazon Products DO NOT return all of the images included within the product display.
    // When "@Category" exists within the "ImageSet" array the product only returned a single set of images.
    /* --------------------------------- */
    if (!empty($amazon_product['ImageSets'])) {
      if ($amazon_product['ImageSets']['ImageSet']['@Category'] != 'primary') {
        $i = 0;
        foreach ($amazon_product['ImageSets']['ImageSet'] as $imageset_key => $imageset_object) {
          $i++;
          kint($imageset_object);
          foreach ( $imageset_object as $image_key => $image_object) {
            $item['imagesets_gallery'][$image_key . '_gallery'][$i] = array(
              'url' => $image_object['URL'],
              'category' => $imageset_object['@Category'],
              'height' => intval($image_object['Height']),
              'width' => intval($image_object['Width']),
            );
          }
        }
      } else {

      }
      
    }

    // TODO || Might be outdated, so figure out what the frick-frack we should do with it 
    // if (!empty($amazon_product->Offers->Offer[0]->OfferListing->Price)) {
    //   $item['amazonpriceamount'] = intval($amazon_product->Offers->Offer[0]->OfferListing->Price->Amount);
    //   $item['amazonpricecurrencycode'] = (string)$amazon_product->Offers->Offer[0]->OfferListing->Price->CurrencyCode;
    //   $item['amazonpriceformattedprice'] = (string)$amazon_product->Offers->Offer[0]->OfferListing->Price->FormattedPrice;
    // }

    $participant_types = preg_split('/,/', AMAZON_PARTICIPANT_TYPES);

    // Pull in the basics of the ItemAttributes collection.
    foreach ((array) ($xml->ItemAttributes) as $key => $value) {
      if (is_string($value) && !in_array($key, $participant_types)) {
        $key = strtolower($key);
        $item[$key] = $value;
      }
    }

    // Handle the Authors/Artists/Etc.
    foreach ($participant_types as $key) {
      if (isset($xml->ItemAttributes->$key)) {
        foreach ($xml->ItemAttributes->$key as $value) {
          $item[strtolower($key)][] = (string) $value;
          $item['participants'][] = (string) $value;
        }
      }
    }

    // Handle the product images. In theory, there could be a million different
    // product image types. We're only going to check for the most common ones
    // and ignore the rest for now.
    $supported_sizes = preg_split('/,/', AMAZON_IMAGE_SIZES);
    if (isset($xml->ImageSets->ImageSet)) {
      $ImageSets = (array) $xml->ImageSets;
       if (is_array($ImageSets['ImageSet'])) {
         if (isset($ImageSets['ImageSet'][0])) {
           $ImageSets = $ImageSets['ImageSet'];
           foreach ($ImageSets as $number => $set) {
             $set = (array) $set;
             if ($set['@attributes']['Category'] == 'primary') {
               $ImageSet = $set;
             }
           }
         }
       }
       else {
         $ImageSet = $ImageSets['ImageSet'];
       }
       foreach ($ImageSet as $key => $data) {
        if (in_array($key, $supported_sizes)) {
          $item['imagesets'][strtolower($key)] = array(
            'url' => (string) $data->URL,
            'height' => intval($data->Height),
            'width' => intval($data->Width),
          );
        }
      }
    }

    // Prepare XML Request ImageSets
    // $xml->ImageSets is an array of Images returned from amazon_http_request()
    if (isset($xml->ImageSets)) {
      // First, we loop through each ImageSet in the ImageSets array
      // Second, we loop through each image size ($supported_sizes) in the ImageSet
      // Finally, we store the imagesets in the $item array
      $i = 0;
      foreach ((array) $xml->ImageSets as $ImageSetKey => $ImageSets) {
        foreach ((array) $ImageSets as $ImageSetKey => $ImageSet) {
          $i++;
          $k = 0;
          $ImageSet = (array) $ImageSet;
          foreach ( $ImageSet as $ImageSize => $imageData) {
            $k++;
            if (in_array($ImageSize, $supported_sizes)) {
              $item['imagesets_gallery'][strtolower($ImageSize) . '_gallery'][$i] = array(
                'url' => (string) $imageData->URL,
                'category' => (string) $ImageSet['@attributes']['Category'],
                'height' => intval($imageData->Height),
                'width' => intval($imageData->Width),

              );
            }
          }
        }
      }
    }

    // Handle the editorial reviews.
    if (isset($xml->EditorialReviews)) {
      foreach ($xml->EditorialReviews->EditorialReview as $data) {
        $item['editorialreviews'][] = array(
          'source' => (string) $data->Source,
          'content' => (string) $data->Content,
        );
      }
    }

    // And the customer reviews.
    if (isset($xml->CustomerReviews)) {
      $item['customerreviews_iframe'] = (string)$xml->CustomerReviews->IFrameURL;
    }

    return $item;
  }

}
