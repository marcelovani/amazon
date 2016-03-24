<?php
/**
 * @file
 * Contains \Drupal\amazon\AmazonRequestInterface.
 */

namespace Drupal\amazon;

/**
 * Provides tools to create requests and return information from Amazon.
 *
 * @package Drupal\amazon
 */
interface AmazonRequestInterface {

  /**
   * Prepares and executes an Amazon request.
   *
   * @return AmazonRequest
   */
  public function execute();

  /**
   * Sets a single option in the request. Note that Timestamp and Signature are
   * automatically calculated and will be ignored.
   *
   * @param string $name
   *   The name of the option to set.
   * @param string $value
   *   The value for that option.
   *
   * @return AmazonRequest
   */
  public function setOption($name, $value);

  /**
   * Sets multiple options in single call. Note that Timestamp and Signature are
   * automatically calculated and will be ignored.
   *
   * @param array $options
   *   Options in the form of (string) optionName => (string) optionValue.
   *
   * @return AmazonRequest
   */
  public function setOptions(array $options);

  /**
   * Returns the result of an Amazon request.
   *
   * @return array
   */
  public function getResults();
}
