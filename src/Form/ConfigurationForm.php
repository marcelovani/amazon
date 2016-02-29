<?php

/**
 * @file
 * Contains Drupal\amazon_filter\Form\ConfigurationForm.
 */

namespace Drupal\amazon_filter\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ConfigurationForm.
 *
 * @package Drupal\amazon_filter\Form
 */
class ConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'amazon_filter.configuration',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('amazon_filter.configuration');
    $form = parent::buildForm($form, $form_state);

    $form['assoc_id'] = [
      '#type' => 'textfield',
      '#title' => t('Amazon Associates ID'),
      '#description' => t('You must register as an <a href=":url">Associate with Amazon</a> before using this module.', [':url' => 'http://docs.aws.amazon.com/AWSECommerceService/latest/DG/becomingAssociate.html']),
      '#default_value' => $config->get('assoc_id'),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('amazon_filter.configuration')
      ->set('assoc_id', $form_state->getValue('assoc_id'))
      ->save();
  }

}
