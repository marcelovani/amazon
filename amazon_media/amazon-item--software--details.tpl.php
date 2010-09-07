/**
 * @file
 * Theme the 'amazon-item' 'software' 'details' style.
 * Many, many available variables. See template_preprocess_amazon_item().
 * Everything that gets put into $variables there is available.
 */
<div class="<?php print $classes; ?>">
<?php if (!empty($smallimage)) { print $smallimage; } ?>
<div><strong><?php print l($title, $detailpageurl, array('html' => TRUE, 'attributes' => array('rel' => 'nofollow'))); ?></strong></div>
<div><strong><?php print t('Publisher'); ?>:</strong> <?php print $publisher; ?></div>
<div><strong><?php print t('Operating System'); ?>:</strong> <?php print $operatingsystem; ?></div>
<div><strong><?php print t('Price'); ?>:</strong> <?php print $listpriceformattedprice; ?></div>
</div>
