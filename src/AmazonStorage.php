<?php

/**
 * @file
 * Contains \Drupal\amazon\AmazonStorage
 */

namespace Drupal\amazon;

/**
 * Class AmazonAPIStorage.
 */
class AmazonStorage {

  public function __construct() {  
    $this->schemaServices = \Drupal::service('amazon.schema');
  }

  /**
   * Save an entry in the database.
   *
   * The underlying DBTNG function is db_insert().
   *
   *
   * @param array $amazon_item
   *   An array containing all the fields of the database record.
   *
   * @return int
   *   The number of updated rows.
   *
   * @throws \Exception
   *   When the database insert fails.
   *
   * @see db_insert()
   */
  public function amazon_item_insert($amazon_item) {

  // Delete Existing Entry
  /*--------------------------------- */
  $this->amazon_item_delete($amazon_item['asin']);

  // Add Time Stamp
  /*--------------------------------- */
  $amazon_item['timestamp'] = REQUEST_TIME;

  // TRY || Insert returned Amazon Product Advertisment API request in Drupal Database
  try {
    $return_value = db_insert('amazon_item')
      ->fields(
        array(
          'asin' => $amazon_item['asin'],
          'title' => $amazon_item['title'],
          'detailpageurl' => $amazon_item['detailpageurl'],
          'salesrank' => $amazon_item['salesrank'],
          'brand' => $amazon_item['brand'],
          'publisher' => $amazon_item['publisher'],
          'manufacturer' => $amazon_item['manufacturer'],
          'mpn' => $amazon_item['mpn'],
          'studio' => $amazon_item['studio'],
          'binding' => $amazon_item['binding'],
          'releasedate' => $amazon_item['releasedate'],
          'listpriceamount' => $amazon_item['listpriceamount'],
          'listpricecurrencycode' => $amazon_item['listpricecurrencycode'],
          'lowestpriceamount' => $amazon_item['lowestpriceamount'],
          'lowestpricecurrencycode' => $amazon_item['lowestpricecurrencycode'],
          'lowestpriceformattedprice' => $amazon_item['lowestpriceformattedprice'],
          'amazonpriceamount' => $amazon_item['amazonpriceamount'],
          'amazonpricecurrencycode' => $amazon_item['amazonpricecurrencycode'],
          'amazonpriceformattedprice' => $amazon_item['amazonpriceformattedprice'],
          'productgroup' => $amazon_item['productgroup'],
          'producttypename' => $amazon_item['producttypename'],
          'customerreviews_iframe' => $amazon_item['customerreviews_iframe'],
          'invalid_asin' => $amazon_item['invalid_asin'],
          'timestamp' => $amazon_item['timestamp'],
        ))
      ->execute();
  }
  // CATCH || Inform administrator the database insert failed.
  catch (\Exception $e) {
    drupal_set_message(t('db_insert failed. Message = %message, query= %query', array(
        '%message' => $e->getMessage(),
        '%query' => $e->query_string,
      )
    ), 'error');
  }

  // Amazon Item Dispatch || Save Amazon Item Metadata
  /*--------------------------------- */
  $this->amazon_item_insert_dispatch($amazon_item);
  }

  /**
   * Save Amazon Product Advertisement API Metadata.
   *
   * @param array $amazon_item
   *   An array containing all of Amazon Product fields.
   *
   * @return 
   *   No return value
   *
   */
  public function amazon_item_insert_dispatch($amazon_item) {

    // IF || Imageset Exists
    if (isset($amazon_item['imagesets'])) {
      amazon_item_image_insert($amazon_item);
    }

    // IF || Imagesets Gallery Exists
    if (isset($amazon_item['imagesets_gallery'])) {
      // amazon_item_image_gallery_insert($amazon_item);
    }

    // IF || Editorial Reviews Exist
    if (isset($amazon_item['editorialreviews'])) {
      amazon_item_editorial_review_insert($amazon_item);
    }
  }

  /**
   * Save Amazon Product Advertisement API Image Metadata in "amazon_item_image" table.
   *
   * @param array $amazon_item
   *   An array containing all of Amazon Product fields.
   *
   * @return 
   *   No return value
   *
   * @see db_insert()
   */
  public function amazon_item_image_insert($amazon_item) {
    foreach ($item['imagesets'] as $size => $data) {
      $image = array('asin' => $item['asin'], 'size' => $size, 'height' => $data['height'], 'width' => $data['width'], 'url' => $data['url']);
      try {
        $return_value = db_insert('amazon_item_image')
        ->fields($image)
        ->execute();
      }
      catch (\Exception $e) {
        amazon_db_error_watchdog("Failed to insert item into amazon_item_image table", $e, $image);
      }
    }
  }

  /**
   * Save Amazon Product Advertisement API Image Metadata in "amazon_item_image_gallery" table.
   *
   * @param array $amazon_item
   *   An array containing all of Amazon Product fields.
   *
   * @return 
   *   No return value
   *
   * @see db_insert()
   */
  public function amazon_item_image_gallery_insert($amazon_item) {
    foreach ($item['imagesets_gallery'] as $image_order => $image_sizes) {
      foreach ($image_sizes as $image_key => $image_data) {
        $image = array('asin' => $item['asin'], 'size' => $image_order, 'height' => $image_data['height'], 'width' => $image_data['width'], 'url' => $image_data['url'], 'category' => $image_data['category'], 'image_order' => $image_key);
        try {
          $return_value = db_insert('amazon_item_image_gallery')
          ->fields($image)
          ->execute();
        }
        catch (\Exception $e) {
          amazon_db_error_watchdog("Failed to insert itemset Gallery into amazon_item_image table", $e, $image);
        }
      }
    }
  }

  /**
   * Save Amazon Product Advertisement API Image Metadata in "amazon_item_editorial_review" table.
   *
   * @param array $amazon_item
   *   An array containing all of Amazon Product fields.
   *
   * @return 
   *   No return value
   *
   * @see db_insert()
   */
  public function amazon_item_editorial_review_insert($amazon_item) {
    foreach ($item['editorialreviews'] as $data) {
      $review = array('asin' => $item['asin'], 'source' => $data['source'], 'content' => $data['content']);
      try {
        $return_value = db_insert('amazon_item_editorial_review')
        ->fields($review)
        ->execute();
      }
      catch (\Exception $e) {
        amazon_db_error_watchdog("Failed to insert item into amazon_item_editorial_review table", $e, $review);
      }
    }
  }

  /**
   * Delete an Amazon Product Advertisment API Product and Metadata from database.
   *
   * @param $asin
   *   ASIN to be deleted.
   *
   * @see db_delete()
   * Delete all vestiges of Amazon item.
   *
   * @return No return.
   */
  public function amazon_item_delete($asin) {

    // Core Amazon Product API Table
    db_delete('amazon_item')
    ->condition('asin', $asin)
    ->execute();

    // Participant (Author) Amazon Product API Table
    db_delete('amazon_item_participant')
    ->condition('asin', $asin)
    ->execute();

    // Image Amazon Product API Table
    db_delete('amazon_item_image')
    ->condition('asin', $asin)
    ->execute();

    // Image Gallery Amazon Product API Table
    db_delete('amazon_item_image_gallery')
    ->condition('asin', $asin)
    ->execute();

    // Editorial Review Amazon Product API Table
    // db_delete('amazon_item_editorial_review')
    // ->condition('asin', $asin)
    // ->execute();
  }

}
