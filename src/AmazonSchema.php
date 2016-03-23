<?php

/**
 * @file
 * Contains \Drupal\amazon\AmazonSchema
 */

namespace Drupal\amazon;

class AmazonSchema {



  /**
   * Mirror Install Schema
   *
   */
  function amazon_item_schema() {
    $schema['amazon_item'] = array(
      'asin' => array('type' => 'varchar', 'length' => 64, 'not null' => TRUE),
      'title' => array('type' => 'varchar', 'length' => 255, 'not null' => TRUE),
      'detailpageurl' => array('type' => 'text'),
      'salesrank' => array('type' => 'int', 'unsigned' => TRUE),
      'brand' => array('type' => 'varchar', 'length' => 64),
      'publisher' => array('type' => 'varchar', 'length' => 64),
      'manufacturer' => array('type' => 'varchar', 'length' => 64),
      'mpn' => array('type' => 'varchar', 'length' => 255),
      'studio' => array('type' => 'varchar', 'length' => 64),
      'label' => array('type' => 'varchar', 'length' => 64),
      'binding' => array('type' => 'varchar', 'length' => 64),
      'releasedate' => array('type' => 'varchar', 'length' => 64),
      'listpriceamount' => array('type' => 'numeric', 'not null' => FALSE, 'precision' => '10', 'scale' => '0'),
      'listpricecurrencycode' => array('type' => 'varchar', 'length' => 32),
      'listpriceformattedprice' => array('type' => 'varchar', 'length' => 32),
      'lowestpriceamount' => array('type' => 'numeric', 'not null' => FALSE, 'precision' => '10', 'scale' => '0'),
      'lowestpricecurrencycode' => array('type' => 'varchar', 'length' => 32),
      'lowestpriceformattedprice' => array('type' => 'varchar', 'length' => 32),
      'amazonpriceamount' => array('type' => 'numeric', 'not null' => FALSE, 'precision' => '10', 'scale' => '0'),
      'amazonpricecurrencycode' => array('type' => 'varchar', 'length' => 32),
      'amazonpriceformattedprice' => array('type' => 'varchar', 'length' => 32),
      'productgroup' => array('type' => 'varchar', 'length' => 255),
      'producttypename' => array('type' => 'varchar', 'length' => 255),
      'customerreviews_iframe' => array('type' => 'varchar', 'length' => 255),
      'invalid_asin' => array('type' => 'int', 'default' => 0),
      'timestamp' => array('type' => 'int', 'unsigned' => TRUE, 'not null' => TRUE, 'default' => 0),
    );

    return $schema;
  }
}