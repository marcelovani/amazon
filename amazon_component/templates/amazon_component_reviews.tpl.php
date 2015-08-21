<?php
/**
 * @file
 *   Template file for Amazon_store cart
 */
?>
<div class="review-card">
	<div class="card-zone zone-left">

		<div id ="review-information" class="review-information">
			<h3 class="review-title"><?php if(!empty($title)) { print $title; } ?></h4>
			<?php if(!empty($field_tagline)): ?>
				<h4 class="review-tagline"><?php { print $field_tagline[0]; } ?></h4>
			<?php endif ?>
			<?php if(!empty($body)): ?>
			<div class="review-body">
				<?php if(!empty($body)) { print $body[0]; } ?>
			</div>
			<?php endif ?>
		</div>

		<?php if(!empty($field_review_pros) && !empty($field_review_cons)): ?>
		<!-- Begin Pros & Cons -->
		<div class="pros-cons">

			<?php if(!empty($field_review_pros)): ?>
			<!-- Begin Pros -->
			<div class="pros">
				<h5 class="card-title">Pros</h5>
				<ul class="pros-list">
				<?php foreach($field_review_pros as $key => $value): ?>
					<li><?php print $value; ?></li>
				<?php endforeach; ?>
				</ul>
			</div>
			<!-- End Pros -->
			<?php endif ?>
			<?php if(!empty($field_review_cons)): ?>
			<!-- Begin Cons -->
			<div class="cons">
				<h5 class="card-title">Cons</h5>
				<ul class="cons-list">
				<?php foreach($field_review_cons as $key => $value): ?>
					<li><?php print $value; ?></li>
				<?php endforeach; ?>
				</ul>
			</div>
			<!-- End Cons -->
			<?php endif ?>

		</div>
		<!-- End Pros & Cons -->
		<?php endif ?>


	</div>

	<div class="card-zone zone-right">
		<div class="featured">
			<ul class="review-featured-images list-unstyled list-inline">
				<?php foreach($images as $key => $value): ?>
					<li><?php print $value; ?></li>
				<?php endforeach; ?>
		</div>

	</div>
