<?php
/**
 * @file
 * Contains Drupal\amazon\AmazonRequest
 */

namespace Drupal\amazon;

use Drupal\Core\Url;

/**
 * Class AmazonRequest
 *
 * @package Drupal\amazon
 */
class AmazonRequest implements AmazonRequestInterface {

  /**
   * Stores the options used in this request.
   *
   * @var array
   */
  protected $options = [];

  /**
   * Stores the results of this request when executed.
   *
   * @var array
   */
  protected $results = [];

  /**
   * The access key secret for the AWS account authorized to use the Product
   * Advertising API.
   *
   * @var string
   */
  protected $accessSecret;

  /**
   * The access key ID for the AWS account authorized to use the Product
   * Advertising API.
   *
   * @var string
   */
  protected $accessKey;

  /**
   * The associates ID (or tag) for the Product Advertising API account.
   *
   * @var string
   */
  protected $associatesId;

  /**
   * The domain of the endpoint for making Product Advertising API requests.
   *
   * @TODO: generalize to include locale
   *
   * @see http://docs.aws.amazon.com/AWSECommerceService/latest/DG/AnatomyOfaRESTRequest.html
   *
   * @var string
   */
  protected $amazonRequestRoot = 'webservices.amazon.com';

  /**
   * The path to the endpoint for making Product Advertising API requests.
   *
   * @see http://docs.aws.amazon.com/AWSECommerceService/latest/DG/AnatomyOfaRESTRequest.html
   *
   * @var string
   */
  protected $amazonRequestPath = '/onca/xml';

  /**
   * AmazonRequest constructor.
   *
   * @param string $accessSecret
   *   The access key secret for the AWS account authorized to use the Product
   *   Advertising API.
   * @param string $accessKey
   *   (optional) The access key ID for the AWS account authorized to use the
   *   Product Advertising API. This can be passed into the request as an
   *   option.
   * @param string $associatesId
   *   (optional) The associates ID for the Product Advertising API account.
   *   This can be passed into the request as an option.
   */
  public function __construct($secretKey, $accessKey = '', $associatesId = '') {
    $this->accessSecret = $secretKey;
    if (!empty($accessKey)) {
      $this->accessKey = $accessKey;
    }
    if (!empty($associatesId)) {
      $this->associatesId = $associatesId;
    }
  }

  /**
   * Prepares the request for execution.
   *
   * @see http://docs.aws.amazon.com/AWSECommerceService/latest/DG/rest-signature.html
   */
  protected function prepare() {
    if (empty($this->options['AWSAccessKeyId'])) {
      if (empty($this->accessKey)) {
        throw new \InvalidArgumentException('Missing AWSAccessKeyId. Need to be passed as an option or set in the constructor.');
      }
      else {
        $this->setOption('AWSAccessKeyId', $this->accessKey);
      }
    }

    if (empty($this->options['AssociateTag'])) {
      if (empty($this->associatesId)) {
        throw new \InvalidArgumentException('Missing AssociateTag. Need to be passed as an option or set in the constructor.');
      }
      else {
        $this->setOption('AssociateTag', $this->associatesId);
      }
    }

    // Add a Timestamp.
    $this->options['Timestamp'] = gmdate("Y-m-d\TH:i:s\Z");

    // Sort options by key.
    ksort($this->options);

    // To build the Signature, we need a very specific string format. We also
    // have to handle urlencoding so that the hashed string matches the encoded
    // string received by Amazon.
    $encodedOptions = [];
    foreach ($this->options as $name => $value) {
      if (is_array($value)) {
        $value = implode(',', $value);
      }
      $encodedOptions[] = urlencode($name) . '=' . urlencode($value);
    }
    $string = join("\n", [
      'GET',
      $this->amazonRequestRoot,
      $this->amazonRequestPath,
      join('&', $encodedOptions),
    ]);

    $signature = base64_encode(hash_hmac('sha256', $string, $this->accessSecret, TRUE));
    return $signature;
  }

  /**
   * @inheritdoc
   */
  public function execute() {
    $endpoint = 'http://' . $this->amazonRequestRoot . $this->amazonRequestPath;
    $this->options['Signature'] = $this->prepare();
    $url = Url::fromUri($endpoint, ['query' => $this->options]);
    $data = \Drupal::httpClient()
      ->get($url->toString());

    if ($data->getStatusCode() == 200) {
      $xml = new \SimpleXMLElement($data->getBody());
      foreach ($xml->Items as $item) {
        $this->results[] = $item->Item;
      }
    }
    else {
      // @TODO: error handling...
    }

    return $this;
  }

  /**
   * @inheritdoc
   */
  public function setOption($name, $value) {
    if (empty($name)) {
      throw new \InvalidArgumentException('Invalid option name: ' . $name);
    }
    if ($name == 'Timestamp' || $name == 'Signature') {
      // Automatically calculated, so we ignore these.
      return $this;
    }

    $this->options[$name] = $value;
    return $this;
  }

  /**
   * @inheritdoc
   */
  public function setOptions(array $options) {
    foreach($options as $name => $value) {
      if (empty($name)) {
        throw new \InvalidArgumentException('Invalid option name: ' . $name);
      }
      $this->setOption($name, $value);
    }
    return $this;
  }

  /**
   * @inheritdoc
   */
  public function getResults() {
    return $this->results;
  }

}
