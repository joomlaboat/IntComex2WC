<?php

/**
 * The admin area of the plugin to load the User List Table
 */

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('IntComex to WooCommerce Import', $this->plugin_text_domain); ?></h1>


    <hr style="margin-bottom: 30px; "/>

	<?php
	//$productsJSONString = $this->getExampleText();
    $productsJSONString = $this->makeGetRequest('https://intcomex-test.apigee.net/v1/getcatalog', '');//GetProducts?locale=en','');
    echo '<p>'.strlen($productsJSONString).' bytes loaded.</p>';
	$productList = json_decode($productsJSONString);

	// Loop through each product in the list and add it to WordPress as a product
	echo '<p>'.count($productList).' products found.</p>';
	foreach ($productList as $product) {
		// Create a new post (product)
		$new_product = array(
			'post_title' => $product->Description,
			'post_content' => '',
			'post_status' => 'publish',
			'post_author' => 1,
			'post_type' => 'product'
		);

		// Check if the product already exists by title
		$query = new WP_Query(array(
			'post_type' => 'product',
			'post_status' => 'any',
			'posts_per_page' => 1,
			'title' => $new_product['post_title'],
		));

		if ($query->have_posts()) {
			// Product already exists

		} else {
			// Product doesn't exist, so insert the new product
            echo '<p>'.$product->Description.' - added.</p>';
			$product_id = wp_insert_post($new_product);

			// Update product meta data
			update_post_meta($product_id, '_sku', $product->Sku);
			update_post_meta($product_id, '_mpn', $product->Mpn);

			// Assign or update product category
			$categoryTermId = $this->addCategoryIfNeeded($product->Category);
			wp_set_post_terms($product_id, [$categoryTermId], 'product_cat');
		}
	}

/*
	?>
    <pre>Catalog:<?php

		print_r($productList);
		?></pre><?php */ ?>
</div>
