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


    <form method="post" name="dashboard" id="dashboard" novalidate="novalidate">
        <input id="actionInput" name="action" type="hidden" value="updateallprices"/>
		<?php wp_nonce_field('dashboard', '_wpnonce_dashboard'); ?>

        <hr style="margin-bottom: 30px; "/>

	    <?php if (isset($this->errors) && is_wp_error($this->errors) && $this->errors->has_errors()) : ?>
        <div class="error">
            <ul>
			    <?php
			    foreach ($this->errors->get_error_messages() as $err) {
				    echo "<li>$err</li>\n";
			    }
			    ?>
            </ul>
        </div>
        <?php
        endif; ?>

		<?php if (count($this->messages) > 0) {

			foreach ($this->messages as $msg) {
				echo '<div id="message" class="updated notice is-dismissible"><p>' . $msg . '</p></div>';
			}
		} ?>

        <div>
            <div style="display: inline-block">
				<?php submit_button('Load Products', 'primary', 'loadproducts', true, ['onclick' => 'document.getElementById("actionInput").value="loadproducts"']); ?>
            </div>

            <div style="display: inline-block">
				<?php submit_button('Update Prices', 'primary', 'updateprices', true, ['onclick' => 'document.getElementById("actionInput").value="updateprices"']); ?>
            </div>
        </div>

        <hr style="margin-bottom: 30px; "/>

		<?php
		//$productsJSONString = $this->getExampleText();
		//$productsJSONString = $this->makeGetRequest('https://intcomex-test.apigee.net/v1/getcatalog', '');//GetProducts?locale=en','');
		//$productsJSONString = $this->makeGetRequest('https://intcomex-prod.apigee.net/v1/getpricelist', '');//GetProducts?locale=en','');
		//print_r($productsJSONString);
		die;
		echo '<p>' . strlen($productsJSONString) . ' bytes loaded.</p>';

		try {
			$productList = json_decode($productsJSONString);
		} catch (Exception $e) {
			echo '<br/>Error:' . $e->getMessage() . '<br/>';
		}

		// Loop through each product in the list and add it to WordPress as a product
		echo '<p>' . count($productList) . ' products found.</p>';
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
				echo '<p>' . $product->Description . ' - added.</p>';
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
    </form>
</div>
