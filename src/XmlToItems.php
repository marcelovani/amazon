<?php
/**
 * @file
 * Contains Drupal\amazon\XmlToItems
 */

namespace Drupal\amazon;

use ApaiIO\ResponseTransformer\ResponseTransformerInterface;

class XmlToItems implements ResponseTransformerInterface {

  public function transform($response) {
    $xml = simplexml_load_string($response);
    $xml->registerXPathNamespace("amazon", "http://webservices.amazon.com/AWSECommerceService/2011-08-01");
    $elements = $xml->xpath('//amazon:ItemSearchResponse/amazon:Items/amazon:Item');
    return $elements;
  }

}
