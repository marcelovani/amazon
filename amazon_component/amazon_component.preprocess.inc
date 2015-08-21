<?php

function template_preprocess_amazon_component_cart(&$variables) {
/* ----------------------------------------------------------------- */ 
/* Request Cart Information
/* ----------------------------------------------------------------- */
  // IF :: Cart Exists
  if ($cart_creds = amazon_store_get_cart_creds()) {

    $cartRequest = amazon_store_http_request('CartGet', $cart_creds);

    $i = 0;
      if (!empty($cartRequest->Cart->CartItems)) {
        foreach ($cartRequest->Cart->CartItems->CartItem as $item) {
          $i++;
        }
      }
    
    // IF :: Check Amazon API Request Errors
    if (!empty($cartRequest->error) || $cartRequest->Cart->Request->IsValid != 'True') {
      amazon_store_report_error('Failed to get cart', $cartRequest->Cart->Request->Errors);
      drupal_set_message(t('Failed to get cart.'), 'error');
    }
  
    // IF :: Cart Request Is Valid 
    // Set template variables (cart, subtotal, etc.)
    if ($cartRequest->Cart->Request->IsValid == 'True') {
      $variables['cart'] = $cartRequest->Cart;
      $variables['subtotal'] = (string)$cartRequest->Cart->SubTotal->FormattedPrice;
      $variables['cart_total'] = $i;
    }

  /* User Name & Image
  /*--------------------------------- */
      if (!empty($variables['user']->uid)) {
        $user = user_load($variables['user']->uid);
        $variables['username'] = $variables['user']->name;
        $variables['userImage'] = theme('user_picture', array('account' => $user));
      } else {
        $variables['userImage'] = "<img class='no-avatar' src='sites/default/files/user/avatar-default.jpg'>";
      }
    }
}

function template_preprocess_amazon_component_reviews(&$variables) {
  // Fucntion Variables
  $db = array();
  $reviews = array();

  // Explode :: Convert current page alias into array
  $currentAlias = explode('/', drupal_get_path_alias());

  // IF :: the block is being rendered on Amazon Product Node type or Amazon Store Module path proceed.
  if ($currentAlias[0] == 'product' || $currentAlias[0] == variable_get('amazon_store_path')) {
    $reviews[$currentAlias[1]] = _amazon_component_fetch_reviews($currentAlias[1]);
    // Foreach :: Review 
    foreach ($reviews[$currentAlias[1]] as $key => $review) {
        // Content Variables
        /* ----------------------------------------------------------------- */
        $variables['title'] = isset($review->title) ? $review->title :'';
        $db['body'] = !empty($review->body) ? $review->body['und'] :'';
        $db['field_tagline'] = !empty($review->field_tagline) ? $review->field_tagline['und'] :'';
        $db['field_introduction'] = !empty($review->field_introduction) ? $review->field_introduction['und'] :'';
        $db['field_tl_dr'] = !empty($review->field_tl_dr) ? $review->field_tl_dr['und'] :'';
        $db['field_review_pros'] = !empty($review->field_review_pros) ? $review->field_review_pros['und'] :'';
        $db['field_review_cons'] = !empty($review->field_review_cons) ? $review->field_review_cons['und'] :'';
        
        // Foreach
        foreach( $db as $key_field => $value) {
          if (!empty($value)) {
            $field = field_get_items('node', $review, $key_field);
            foreach ($field as $key => $value) {
              $field_rendered = field_view_value('node', $review, $key_field, $field[$key]);
              // Variables :: Check to see if it's text, image, etc...
              $variables[$key_field][$key] = isset($field_rendered['#markup']) ? $field_rendered['#markup'] : $field_rendered;
              
            }
          }
        }

        // Image Variables
        /* ----------------------------------------------------------------- */
        $db_images = !empty($review->field_featured_image) ? $review->field_featured_image['und'] :'';
        foreach ($db_images as $key => $image) {
          $variables['images'][$key] = theme('image', array(
                              'path' => $image['uri'], 
                              'attributes' => array(
                                'height' => $image['height'], 
                                'width' => $image['width']), 
                               'getsize' => FALSE)
                            );
        }
    }
  }
}

function _amazon_component_fetch_reviews($asin) {
  // Query :: Select Amazon Product Nodes referencing Amazon Product ASIN number. 
  $query = 'SELECT * FROM {field_data_field_product_asin} WHERE field_product_asin_asin IN (:asins) AND bundle IN (:bundle)';
  $args = array(
    ':asins' => $asin,
    ':bundle' => 'review',
  );
  $options = array('fetch' => PDO::FETCH_ASSOC);

  // Result :: Amazon Product Community Review(s) Query Result
  $result = db_query($query,$args,$options);

  // Foreach ::  Retrieve Node via Entity ID
  // TODO :: Create a more optimized Entity Query.
  foreach ($result as $review) {
    $item[$review['bundle'] . "-" . $review['entity_id']] = node_load($review['entity_id']);
  }

  return $item;
}

function _amazon_component_process_reviews($asin) {


}

/**
 * The template_preprocess_amazon_gallery handles the "Amazon Gallery Block".
 * The Amazon Gallery Block is rended on the Amazon Product Node Type and Amazon Store Module paths
 * Galleries reference the Product ASIN # stored in the URL path
 * 
 * Node: Amazon Product
 * Pathauto: product/[node:field_product_asin]/[node:nid]
 *
 * Module: Amazon Store
 * Pathauto: variable_get('amazon_store_path')/[node:field_product_asin]/[node:nid]
 */

function template_preprocess_amazon_gallery(&$variables) {
  // Explode :: Convert current page alias into array
  $currentAlias = explode('/', drupal_get_path_alias());
  //$message = amazon_component_item_lookup($currentAlias[1]);

  // IF :: the block is being rendered on Amazon Product Node type or Amazon Store Module path proceed.
  // It's assumed the first URL seperator is "product" or the Amazon Store Path (default "store") 
  // and the second position is an Amazon Product ASIN.
  // Depending on future Open Source releases we may switch it to scan the URL array for an ASIN in any position.
  if ($currentAlias[0] == 'product' || $currentAlias[0] == variable_get('amazon_store_path')) {

    $result = amazon_component_gallery_lookup($currentAlias[1]);
    // Create gallery variables: $smallimagegallery, $mediumimagegallery, etc.
    foreach ($result as $item) {
      foreach ($item as $gallery) {
        foreach ($gallery as $image) {
          foreach ($image as $imageData) {
            $variables[$imageData['size']][$imageData['image_order']] = theme('image', 
            array(
              'path' => $imageData['url'], 
              'attributes' => array(
                'height' => $imageData['height'], 
                'width' => $imageData['width']), 
               'getsize' => FALSE)
            );
            unset($image['asin']);
          } // foreach
        } // foreach
      } // foreach
    } // foreach
  } // endif

  // Required $variables arrays;
  $variables['classes_array'] = array();
  $variables['attributes_array'] = array();
  $variables['title_attributes_array'] = array();
  $variables['content_attributes_array'] = array();

}

// function template_preprocess_amazon_gallery(&$variables, &$form_state) {


//   if ($node = menu_get_object()) {
//     // Get the nid
//     $nid = $node->nid;
//   }

//   if ($cart_creds = amazon_store_get_cart_creds()) {
//     $cartRequest = amazon_store_http_request('CartGet', $cart_creds);
//     // Check cartRequest
//     if (!empty($cartRequest->error) || $cartRequest->Cart->Request->IsValid != 'True') {
//       amazon_store_report_error('Failed to load Amazon Store gallery', $cartRequest->Cart->Request->Errors);
//       drupal_set_message(t('Failed to load Amazon Store gallery.'), 'error');
//     }
//   }
//   $asin = $cartRequest->Cart->CartItems->CartItem->ASIN;

//   $result = db_query('SELECT * FROM {amazon_item_image_gallery} WHERE asin = :asin',
//     array(':asin' => $asin),
//     array('fetch' => PDO::FETCH_ASSOC)
//   );

//   foreach ($result as $image) {
//     $variables[$image['size']][$image['image_order']] = theme('image', array(
//             'path' => $image['url'], 
//             'attributes' => array(
//               'height' => $image['height'], 
//               'width' => $image['width']), 
//              'getsize' => FALSE)
//           );
//     unset($image['asin']);
//     $item['imagesets'][$image['size']][$image['image_order']] = $image;
//   }

// }


// function template_preprocess_amazon_component_add_to_cart(&$variables) {


// }
