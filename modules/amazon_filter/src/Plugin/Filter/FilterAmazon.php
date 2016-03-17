<?php
/**
 * @file
 * Contains \Drupal\amazon_filter\Plugin\Filter\FilterAmazon.
 */

namespace Drupal\amazon_filter\Plugin\Filter;

use Drupal\amazon\Amazon;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a filter to easily be links to Amazon using an Associate ID.
 *
 * @Filter(
 *   id = "filter_amazon",
 *   title = @Translation("Amazon Associates filter"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 *   weight = -10
 * )
 */
class FilterAmazon  extends FilterBase {

  /**
   * The default max-age cache value as stored by the Amazon settings form.
   *
   * @var string
   */
  protected $defaultMaxAge;

  /**
   * @inheritdoc
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    if (!empty($configuration['default_max_age'])) {
      // Allows for easier unit testing.
      $this->defaultMaxAge = $configuration['default_max_age'];
    }
    else {
      $this->defaultMaxAge = \Drupal::config('amazon.settings')->get('default_max_age');
      if (is_null($this->defaultMaxAge)) {
        throw new \InvalidArgumentException('Missing amazon.settings config.');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $matches = [];
    $replacements = [];
    if (preg_match_all('`\[amazon(.*?)\]`', $text, $matches)) {
      foreach ($matches[1] as $index => $match) {
        $completeToken = $matches[0][$index];
        if (isset($replacements[$completeToken])) {
          continue;
        }

        // Preferred format.
        $params = explode(':', trim($match));
        if (count($params) == 1) {
          // Backwards-compatible format.
          $params = explode(' ', trim($match));
        }
        if (count($params) == 1) {
          continue;
        }

        $asin = $params[0];
        $type = $params[1];
        $maxAge = $this->defaultMaxAge;
        if (!empty($params[2])) {
          $maxAge = $params[2];
        }

        switch (strtolower($type)) {
          case 'inline':
            // @TODO: quick fix to get this working. Needs caching and injection!
            $associatesId = \Drupal::config('amazon.settings')->get('associates_id');
            $amazon = new Amazon($associatesId);
            $result = $amazon->lookup($asin);
            dsm($result);
            if (!empty($result[0])) {
              $result = $result[0];
              $replacements[$completeToken] = new FormattableMarkup('<a href=":url">@item</a>', [
                ':url' => $result->DetailPageURL,
                '@item' => $result->ItemAttributes->Title,
              ]);
            }
            break;
        }
      }
    }

    $return = '';
    if (!empty($replacements)) {
      // @TODO: Handle in-token overrides of max-age.
      $text = strtr($text, $replacements);
      $return = new FilterProcessResult($text);
      $return->setCacheMaxAge((int) $maxAge);
    }
    else {
      $return = new FilterProcessResult($text);
    }

    return $return;
  }

  public function tips($long = FALSE) {
    $output = $this->t('Link to Amazon products with [amazon:ASIN:display_type(:cache_max_age_in_seconds)]. Example: [amazon:1590597559:thumbnail:900] or [amazon:1590597559:author]. Details are <a href=":url" target="_blank">on the Amazon module handbook page</a>.', [':url' => 'http://drupal.org/node/595464#filters']);
    if (!$long) {
      return $output;
    }

    $output = '<p>' . $output . '</p>';
    $output .= '<p>' . $this->t('Currently supported options for display_typ:') . '</p>';
    $output .= '<ul><li>' . $this->t('inline: Creates a text link to Amazon using the product title') . '</li>';
    $output .= '<li>' . $this->t('thumbnail|small: Creates a link to Amazon using the small image size') . '</li>';
    $output .= '<li>' . $this->t('medium: Creates a link to Amazon using the medium image size') . '</li>';
    $output .= '<li>' . $this->t('full|large: Creates a link to Amazon using the large image size') . '</li>';
    //$output .= '<li>' . $this->t() . '</li>';
    //$output .= '<li>' . $this->t() . '</li>';
    //$output .= '<li>' . $this->t() . '</li>';
    //$output .= '<li>' . $this->t() . '</li>';
    $output .= '<li>' . $this->t() . '</li></ul>';
    return $output;
  }

}
