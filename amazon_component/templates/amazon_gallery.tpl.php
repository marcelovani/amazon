<?php
/**
 * @file
 *   Template file for Amazon_store cart
 */
?>
<ul class="product-gallery list-inline">
<?php if(isset($mediumimage)): ?>
  <?php foreach($mediumimage as $key=>$value): ?>
    <li class="gallery-item"><?php print $value; ?></li>
  <?php endforeach; ?>
<?php endif ?>
</ul>