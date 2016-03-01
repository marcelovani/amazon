<?php
/**
 * @file
 * Contains \Drupal\amazon_filter\Plugin\Filter\FilterAmazon.
 */

namespace Drupal\amazon_filter\Plugin\Filter;

use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a filter to easily be links to Amazon using an Associate ID.
 *
 * @Filter(
 *   id = "filter_amazon",
 *   title = @Translation("Amazon Associates Filter"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 *   weight = -10
 * )
 */
class FilterAmazon  extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {

  }

  protected function getItemFromAmazon($items) {
    if (empty($items)) {
      return;
    }
    if (!is_array($items)) {
      $items = [$items];
    }

    $return = [];
    // Amazon will allow lookups in batches of 10 or less.
    return $return;
  }

}
