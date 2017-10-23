<?php
/**
 * @file
 * amazon_test.field_instance.inc
 */

/**
 * Implements hook_field_default_field_instances().
 *
 * @param $bundle
 *   The bundle name.
 */
function amazon_test_field_default_field_instances($bundle = 'article') {
  $field_instances = array();

  $field_instances['node-' . $bundle . '-field_asin'] = array(
    'bundle' => $bundle,
    'default_value' => NULL,
    'deleted' => 0,
    'description' => '',
    'display' => array(
      'default' => array(
        'label' => 'above',
        'module' => 'asin',
        'settings' => array(),
        'type' => 'asin_default',
        'weight' => 1,
      ),
      'teaser' => array(
        'label' => 'above',
        'settings' => array(),
        'type' => 'hidden',
        'weight' => 0,
      ),
    ),
    'entity_type' => 'node',
    'field_name' => 'field_asin',
    'label' => 'ASIN',
    'required' => 0,
    'settings' => array(
      'user_register_form' => FALSE,
    ),
    'widget' => array(
      'active' => 0,
      'module' => 'asin',
      'settings' => array(),
      'type' => 'asin_text',
      'weight' => -2,
    ),
  );

  return $field_instances;
}
