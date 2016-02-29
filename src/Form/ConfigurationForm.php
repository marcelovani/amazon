<?php

/**
 * @file
 * Contains Drupal\amazon_filter\Form\ConfigurationForm.
 */

namespace Drupal\amazon_filter\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\amazon_filter\Amazon;

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
      'amazon.configuration',
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
    $config = $this->config('amazon.configuration');
    $form = parent::buildForm($form, $form_state);

    $description = '';
    if (empty(Amazon::getAccessKey())) {
      $description = $this->t('You must sign up for an Amazon AWS account to use the Product Advertising Service. See the <a href=":url">AWS home page</a> for information and a registration form.', [':url' => 'https://aws-portal.amazon.com/gp/aws/developer/account/index.html?ie=UTF8&action=access-key']);
    }
    else {
      $description = $this->t('The access key is set by another method and does not need to be entered here.');
    }
    $form['access_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Amazon AWS Access Key ID'),
      '#description' => $description,
      '#default_value' => $config->get('access_key'),
      '#disabled' => !empty(Amazon::getAccessKey()),
    ];

    $form['assoc_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Amazon Associates ID'),
      '#description' => $this->t('You must register as an <a href=":url">Associate with Amazon</a> before using this module.', [':url' => 'http://docs.aws.amazon.com/AWSECommerceService/latest/DG/becomingAssociate.html']),
      '#default_value' => $config->get('assoc_id'),
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

    if (empty(Amazon::getAccessKey()) && $form_state->get('access_key') == '') {
      $form_state->setErrorByName('access_key', $this->t('If you do not specify an access key here, you must use one of the other methods of providing this information, such as a server environment variable or a $config setting in settings.php.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('amazon.configuration')
      ->set('access_key', $form_state->getValue('access_key'))
      ->set('assoc_id', $form_state->getValue('assoc_id'))
      ->set('default_max_age', $form_state->getValue('default_max_age'))
      ->save();
  }

}
