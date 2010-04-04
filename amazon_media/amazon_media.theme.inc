<?php
// $Id$

/**
 * Implements theme_ with specialty dvd.
 *
 * @param $item
 *   fully populated amazon item.
 * @param $style
 *   'full', 'thumbnail', or whatever.
 */

function theme_amazon_item__dvd($item, $style = 'default') {
  drupal_set_message('Theming with theme_amazon_item__dvd');
  $output = '';
  $type = $item['type'];
  switch ($style) {
    case 'full':
      $output .= '<div class="' . _amazon_item_classes($type) . ' amazon-item-full">';
      $output .= theme('image', array('path' => $item['imagesets']['mediumimage']['url'], 'alt' => t('Cover image'), 'title' => check_markup($item['title']), 'attributes' => array(), 'getsize' => FALSE));
      $output .= '<h3>' . l(check_plain($item['title']), $item['detailpageurl'], array('attributes' => array('rel' => 'nofollow'))) . '</h3>';
      $output .= '</div>';
      break;
    case 'thumbnail':
      $output .= '<div class="' . _amazon_item_classes($type) . ' amazon-item-thumbnail">';
      $output .= theme('image', array('path' => $item['imagesets']['mediumimage']['url'], 'alt' => t('Cover image'), 'title' => check_markup($item['title']), 'attributes' => array(), 'getsize' => FALSE));
      $output .= '</div>';
      break;
    default:
      $output .= '<div class="' . _amazon_item_classes($type) . '">';
      $output .= theme('image', array('path' => $item['imagesets']['smallimage']['url'], 'alt' => t('Cover image'), 'title' => check_markup($item['title']), 'attributes' => array(), 'getsize' => FALSE));
      $date = preg_split('/-/', $item['theatricalreleasedate']);
      $output .= '<h3>' . l(check_markup($item['title']), $item['detailpageurl'], array('attributes' => array('rel' => 'nofollow'))) . ' (' . check_plain($date[0]) . ')</h3>';
      $output .= '<div><strong>' . t('Director') . ':</strong> ' . implode(', ', $item['director']) . '</div>';
      $output .= '<div><strong>' . t('Rating') . ':</strong> ' . $item['audiencerating'] . '</div>';
      $output .= '<div><strong>' . t('Running time') . ':</strong> ' . $item['runningtime'] . ' ' . t('minutes') . '</div>';
      $output .= '</div>';
      break;
  }
  return $output;
}

/**
 * Theme with inline item dvd.
 * @param $item
 * @return
 *   rendered output.
 */
function theme_amazon_inline_item_dvd($item) {
  $type = $item['type'];
  $date = preg_split('/-/', $item['theatricalreleasedate']);
  $output = '<span class="' . _amazon_item_classes($type) . ' amazon-item-inline">';
  $output .= l($item['title'] . ' (' . $date[0] . ')', $item['detailpageurl'], array('attributes' => array('rel' => 'nofollow')));
  $output .= '</span>';
  return $output;
}


function theme_amazon_inline_item_software($item) {
  $output = '<span class="' . _amazon_item_classes($item) . ' amazon-item-inline">';
  $output .= l($item['title'] . ' (' . $item['operatingsystem'] . ')', $item['detailpageurl'], array('attributes' => array('rel' => 'nofollow')));
  $output .= '</span>';
  return $output;
}


function theme_amazon_inline_item_video_games($item) {
  $output = '<span class="' . _amazon_item_classes($item) . ' amazon-item-inline">';
  $output .= l($item['title'] . ' (' . $item['hardwareplatform'] . ')', $item['detailpageurl'], array('attributes' => array('rel' => 'nofollow')));
  $output .= '</span>';
  return $output;
}
