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
      '#title' => $this->t('Amazon Associates ID'),
      '#description' => $this->t('You must register as an <a href=":url">Associate with Amazon</a> before using this module.', [':url' => 'http://docs.aws.amazon.com/AWSECommerceService/latest/DG/becomingAssociate.html']),
      '#default_value' => $config->get('assoc_id'),
      '#required' => TRUE,
    ];

    $max_age = $config->get('default_max_age');
    if ($max_age == '') {
      // Defaults to 15 minutes.
      $max_age = '900';
    }
    $form['default_max_age'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default max-age for retrieved information'),
      '#description' => $this->t('Number of seconds that the result from Amazon will be cached. This can be overridden by defining a different value in the text filter. Set to zero to disable caching by default.'),
      '#default_value' => $max_age,
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
      ->set('default_max_age', $form_state->getValue('default_max_age'))
      ->save();
  }

}
