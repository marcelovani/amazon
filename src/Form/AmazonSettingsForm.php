<?php

/**
 * @file
 * Contains \Drupal\amzon\AmazonSettingsForm.
 */

namespace Drupal\amazon\Form;

use Drupal\amazon\AmazonStorage;
use Drupal\amazon\AmazonRequest;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Manage Amazon Product Advertisment API settings
 */
class AmazonSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'amazon_settings';
  }
    /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'amazon.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = array();
    $config = $this->config('amazon.settings');

    // Services
    $request_service = \Drupal::service('amazon.amazon_request');
    $helpers_service = \Drupal::service('amazon.amazon_helpers');

    // $request_service->amazon_cache_request();
    $locale_options = $helpers_service->amazon_cache_request();
    $aws_request = $request_service->amazon_item_batch_lookup_from_web($item_ids = array('B00NIYOOMA', 'B00LESY3AA'));
  
    $form['message'] = array(
      '#markup' => $this->t('Manage the Amazon Product Advertisment API Settings'),
    );

    // Amazon Web Services Key || Textfield
    $form['amazon_aws_access_key'] = array(
      '#type' => 'textfield',
      '#title' => t('Amazon AWS Access Key ID'),
      '#description' => t('You must sign up for an Amazon AWS account to use the Product Advertising Service.'),
      '#default_value' => $config->get('amazon_aws_access_key'),
      '#required' => TRUE,
    );

    // Amazon Web Servies Secret Key || Textfield
    $form['amazon_aws_secret_access_key'] = array(
      '#type' => 'textfield',
      '#title' => t('Amazon AWS Secret Access Key'),
      '#description' => t('You must sign up for an Amazon AWS account to use the Product Advertising Service.'),
      '#default_value' => $config->get('amazon_aws_secret_access_key'),
      '#required' => TRUE,
    );

    // Store Locale || Select
    $form['amazon_locale'] = array(
      '#type' => 'select',
      '#title' => t('Amazon locale'),
      '#default_value' => $config->get('amazon_locale'),
      '#options' => $locale_options,
      '#description' => t('Amazon.com uses separate product databases and Ecommerce features in different locales; pricing and availability information, as well as product categorization, differs from one locale to the next. Be sure to select the default locale your site will be running in.'),
    );

    // Associate ID || Textfield
    $form['amazon_associate_id'] = array(
      '#type' => 'textfield',
      '#title' => t('Associate ID'),
      '#description' => t('The Associate ID is used to track referral bonuses when shoppers purchase Amazon products via your site.'),
      '#default_value' => $config->get('amazon_associate_id'),
      '#required' => TRUE,
    );

    // Advanced Settings || Details
    $form['advanced_settings'] = array(
      '#type' => 'details',
      '#title' => t('Advanced Settings'),
      '#description' => t(''),
    );

    // Advanced Settings - Amazon Schema || Select
    $form['advanced_settings']['amazon_schema'] = array(
      '#type' => 'select',
      '#title' => t('Amazon Scehma'),
      '#default_value' => $config->get('amazon_schema'),
      '#options' => array(
        '2011-08-01' => t('2011-08-01'),
        '2013-08-01' => t('2013-08-01'),
      ),
      '#description' => t('Select the Schema version to request from the Amazon Product Advertisment API.'),
    );

    // Advanced Settings - Amazon Participant Types || Checkboxes
    $form['advanced_settings']['amazon_participant_types'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Amazon Scehma'),
      '#default_value' => $config->get('amazon_participant_types'),
      '#options' => array(
        'Author' => t('Author'),
        'Artist' => t('Artist'),
        'Actor' => t('Actor'),
        'Director' => t('Director'),
        'Creator' => t('Creator'),
      ),
      '#description' => t('Select "Participant" types to include in Amazon Product requests.'),
    );

    // Advanced Settings - Amazon Image Sizes || Checkboxes
    $form['advanced_settings']['amazon_image_sizes'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Amazon Scehma'),
      '#default_value' => $config->get('amazon_image_sizes'),
      '#options' => array(
        'SwatchImage' => t('SwatchImage'),
        'TinyImage' => t('TinyImage'),
        'ThumbnailImage' => t('ThumbnailImage'),
        'SmallImage' => t('SmallImage'),
        'MediumImage' => t('MediumImage'),
        'LargeImage' => t('LargeImage'),

      ),
      '#description' => t('Select "Participant" types to include in Amazon Product requests.'),
    );

    $form['add']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save Configuration'),
      '#button_type' => 'primary',
    );

    $form['#theme'] = 'system_config_form';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->config('amazon.settings')
        ->set('amazon_aws_access_key', $form_state->getValue('amazon_aws_access_key'))
        ->set('amazon_aws_secret_access_key', $form_state->getValue('amazon_aws_secret_access_key'))
        ->set('amazon_locale', $form_state->getValue('amazon_locale'))
        ->set('amazon_schema', $form_state->getValue('amazon_schema'))
        ->set('amazon_participant_types', $form_state->getValue('amazon_participant_types'))
        ->set('amazon_image_sizes', $form_state->getValue('amazon_image_sizes'))
        ->save();
   
    parent::submitForm($form, $form_state);
  }

}
