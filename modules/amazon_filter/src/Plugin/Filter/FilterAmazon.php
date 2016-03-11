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

        $params = explode(' ', trim($match));
        if (empty($params)) {
          $params = explode(':', trim($match));
        }
        if (empty($params)) {
          // @TODO: error handling? Or just return the broken token in the
          // string and figure the user will see that it's broken.
          return new FilterProcessResult($text);
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

    // @TODO: Handle in-token overrides of max-age.
    $text = strtr($text, $replacements);
    $return = new FilterProcessResult($text);
    $return->setCacheMaxAge((int) $maxAge);

    return $return;
  }

}
