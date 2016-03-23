<?php

/**
 * @file
 * Contains \Drupal\amzon\AmazonTestForm.
 */

namespace Drupal\amazon\Form;

use Drupal\amazon\AmazonAPIStorage;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Test Amazon Product Advertisment API settings
 */
class AmazonTestForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'amazon_settings_test';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'amazon.settings.test',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = array();
    $config = $this->config('amazon.settings.test');

    $form['message'] = array(
      '#markup' => $this->t('Manage the Amazon Product Advertisment API Settings'),
    );

    $form['amazon_asin'] = array(
      '#type' => 'textfield',
      '#title' => t('Amazon Product ID'),
      '#description' => t('The ASIN (ISBN-10) or EAN (ISBN-13) of a product listed on Amazon.'),
      '#required' => TRUE,
    );

    $form['add']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Test AWS API Service'),
      '#button_type' => 'primary',
    );

    $form['#theme'] = 'system_config_form';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->config('amazon.settings.test')
        ->set('amazon_asin', $form_state->getValue('amazon_asin'))
        ->save();
   
    parent::submitForm($form, $form_state);
  }

}
