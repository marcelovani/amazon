<?php
namespace Drupal\amazon\Controller;

use Drupal\Core\Controller\ControllerBase;

class AmazonProductTeaserController extends ControllerBase {
  /**
   * hello
   * @param  string $amazon_item
   * @return string
   */
  public function amazon_product_teaser($amazon_item) {
    return [
      '#theme' => 'amazon_product_teaser',
    ];
  }
}