<?php

namespace Drupal\amazon_field\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\amazon\Amazon;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'amazon_field_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "amazon_field_formatter",
 *   label = @Translation("Amazon field formatter"),
 *   field_types = {
 *     "amazon_asin_field"
 *   }
 * )
 */
class AmazonFieldFormatter extends FormatterBase {

  /**
   * Contians a list of display options for this formatter.
   *
   * @var array
   */
  protected $templateOptions = [];

  public function __construct($plugin_id, $plugin_definition, \Drupal\Core\Field\FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->templateOptions = [
      'amazon_inline' => $this->t('Item title'),
      'amazon_image_small' => $this->t('Small image'),
      'amazon_image_medium' => $this->t('Medium image'),
      'amazon_image_large' => $this->t('Large image'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $defaultMaxAge = \Drupal::config('amazon.settings')->get('default_max_age');
    if (is_null($defaultMaxAge)) {
      throw new \InvalidArgumentException('Missing Amazon settings: default max age.');
    }

    return array(
      'max_age' => $defaultMaxAge,
      'template' => 'amazon_image_large',
  ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $defaultMaxAge = \Drupal::config('amazon.settings')->get('default_max_age');

    $form['max_age'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Max age for cached results'),
      '#description' => $this->t('The number of seconds that the system should cache the results from Amazon\'s servers. Leave blank to use the default max age set on the <a href=":url">Amazon settings page</a>, currently set at @default_max_age seconds.', [
        ':url' => Url::fromRoute('amazon.settings_form'),
        '@default_max_age' => $defaultMaxAge
      ]),
      '#default_value' => ($this->getSetting('max_age') == $defaultMaxAge) ? '' : $this->getSetting('max_age'),
    ];
    $form['template'] = [
      '#type' => 'select',
      '#title' => $this->t('Display item as'),
      '#description' => $this->t('By default, all options will link to the item in the Amazon store tagged with your Associates ID.'),
      '#options' => $this->templateOptions,
      '#default_value' => $this->getSetting('template'),
    ];

    return $form + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Cache max age: @max_age', ['@max_age' => $this->getSetting('max_age')]);
    $summary[] = $this->t('Display as: @template', ['@template' => $this->templateOptions[$this->getSetting('template')]]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    dsm($items);
    foreach ($items as $delta => $item) {
      $elements[$delta] = ['#markup' => $this->viewValue($item)];
    }

    return $elements;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return string
   *   The textual output generated.
   */
  protected function viewValue(FieldItemInterface $item) {
    dsm($item);
    //$associatesId = \Drupal::config('amazon.settings')->get('associates_id');
    //$amazon = new Amazon($associatesId);
    //$results = $amazon->lookup($asin);
    //if (empty($results[0])) {
    //  continue;
    //}
    //
    //// Build a render array for this element. This allows us to easily
    //// override the layout by simply overriding the Twig template. It also
    //// lets us set custom caching for each filter link.
    //$build = [
    //  '#results' => $results,
    //  '#max_age' => $maxAge,
    //];
    //
    //// Use the correct Twig template based on the "type" specified.
    //switch (strtolower($type)) {
    //  case 'inline':
    //    $build['#theme'] = 'amazon_inline';
    //    break;
    //
    //  case 'small':
    //  case 'thumbnail':
    //    $build['#theme'] = 'amazon_image';
    //    $build['#size'] = 'small';
    //    break;
    //
    //  case 'medium':
    //    $build['#theme'] = 'amazon_image';
    //    $build['#size'] = 'medium';
    //    break;
    //
    //  case 'large':
    //  case 'full':
    //    $build['#theme'] = 'amazon_image';
    //    $build['#size'] = 'large';
    //    break;
    //
    //  default:
    //    continue;
    //}






    // The text value has no text format assigned to it, so the user input
    // should equal the output, including newlines.
    return nl2br(Html::escape($item->value));
  }

}
