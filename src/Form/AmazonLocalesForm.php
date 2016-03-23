<?php

/**
 * @file
 * Contains \Drupal\amzon\AmazonLocalesForm.
 */

namespace Drupal\amazon\Form;

use Drupal\amazon\AmazonAPIStorage;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Test Amazon Product Advertisment API settings
 */
class AmazonLocalesForm extends ConfigFormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'amazon_settings_locales';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'amazon.settings.locales',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = array();
    $config = $this->config('amazon.settings.cache');
    // \Drupal::service('date.formatter')->formatInterval(REQUEST_TIME - $timestamp);
    // $period = drupal_map_assoc(array(3600, 7200, 14400, 21600, 43200, 86400), 'format_interval');

    kint($config);
    
    $form['message'] = array(
      '#markup' => $this->t('Manage the Amazon Product Advertisment System Storage Settings'),
    );



    // Submit  
    $form['add']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Submit'),
      '#button_type' => 'primary',
    );

    $form['#theme'] = 'system_config_form';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->config('amazon.settings.locales')
        ->set('amazon_refresh_schedule', $form_state->getValue('amazon_refresh_schedule'))
        ->set('amazon_core_data', $form_state->getValue('amazon_core_data'))
        ->save();
   
    parent::submitForm($form, $form_state);
  }

}
