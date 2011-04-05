<?php

class views_handler_field_amazon_title extends views_handler_field {
  function option_definition() {
    $options = parent::option_definition();
    $options['link_format'] = array('default' => 'amazon');

    return $options;
  }

  /**
   * Override init function to provide generic option to link to node.
   */
  function init(&$view, &$data) {
    parent::init($view, $data);
    if (!empty($data['link_format']) && $data['link_format'] == 'amazon') {
      $this->additional_fields['detailpageurl'] = 'detailpageurl';
    }
    $this->additional_fields['asin'] = 'asin';
    $this->additional_fields['nid'] = array('table' => 'node', 'field' => 'nid');
  }

  /**
   * Provide link to node option
   */
  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $form['link_format'] = array(
      '#title' => t('Link behavior'),
      '#type' => 'radios',
      '#options' => array(
        'plain' => t('No link'),
        'amazon' => t("A link to the product's Amazon page"),
      ),
      '#default_value' => !empty($this->options['link_format']) ? $this->options['link_format'] : 'plain',
    );
    if ($this->view->base_table == 'node') {
      $form['link_format']['#options']['node'] = t('A link to the node the product is associated with');
    }
    if (module_exists('amazon_store')) {
      $form['link_format']['#options']['amazon_store'] = t("A link to the product's Amazon Store page (Amazon Store Module)");
    }

  }

  function render($values) {
    return $this->render_link(check_plain($values->{$this->field_alias}), $values);
  }

  function render_link($data, $values) {
    $title = $data;

    switch ($this->options['link_format']) {
      case 'plain':
        break;
      case 'node':
        $this->options['alter']['make_link'] = TRUE;
        $this->options['alter']['path'] = "node/$values->{$this->aliases['nid']}";
        $this->options['alter']['html'] = TRUE;
        break;
      case 'amazon':
        $this->options['alter']['make_link'] = TRUE;
        $this->options['alter']['path'] = check_url($values->{$this->aliases['detailpageurl']});
        $this->options['alter']['html'] = TRUE;
        break;
      case 'amazon_store':
        $this->options['alter']['make_link'] = TRUE;
        $this->options['alter']['html'] = TRUE;
        $asin = $values->{$this->aliases['asin']};
        $this->options['alter']['path'] = 'amazon_store/item/' . $asin;
        break;
    }

    return $data;
  }
}
