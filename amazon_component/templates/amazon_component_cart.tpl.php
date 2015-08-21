<?php
/**
 * @file
 *   Template file for Amazon_store cart
 */
?>
<h4 class="cart-title"><?php if(!empty($username)) { print $username; } ?> Cart</h4>
<div class="cart-left box--vertical">
	<?php if (!empty($userImage)): ?><span class="user-image"><?php print $userImage ?></span><?php endif ?>
	<div class="vertical">
		<span class="item-amount"><?php if (isset($cart_total)) print $cart_total ?> Item(s)</span>
		<?php if (!empty($subtotal)): ?><span class="subtotal"><?php print $subtotal ?></span><?php endif ?>
	</div>
</div>
<div class="cart-right">
	<?php if (isset($cart->CartItems)): ?>
	<?php print theme('amazon_store_link_button', array('text' => t("Checkout"), 'url' => (string)$cart->PurchaseURL)); ?>
	<span class="review">Review Order</span>
	<?php endif ?>

</div>
<div class="cart-bottom">
	<!--- <?php $form = drupal_get_form('amazon_store_clear_cart'); print drupal_render($form); ?> -->
</div>
