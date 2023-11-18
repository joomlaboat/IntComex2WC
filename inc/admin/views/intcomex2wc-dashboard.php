<?php

/**
 * The admin area of the plugin to load the User List Table
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('IntComex to WooCommerce Import', $this->plugin_text_domain); ?></h1>


    <hr style="margin-bottom: 30px; "/>

    <?php


    // Enable error reporting and log errors to a file
    //error_reporting(E_ALL);
    //ini_set('log_errors', 1);
    //echo $this->makeGetRequest('https://intcomex-test.apigee.net/v1/getcatalog', '');//GetProducts?locale=en','');

    $productList = json_decode($this->getExampleText());

    // Loop through each product in the list and add it to WordPress as a product
    foreach ($productList as $product) {
	    // Create a new post (product)
	    $new_product = array(
		    'post_title' => $product->Description,
		    'post_content' => '',
		    'post_status' => 'publish',
		    'post_author' => 1,
		    'post_type' => 'product'
	    );

	    // Insert the post into the database
	    $product_id = wp_insert_post($new_product);

	    // Update product meta data
	    update_post_meta($product_id, '_sku', $product->Sku);
	    update_post_meta($product_id, '_mpn', $product->Mpn);
	    // Add more meta fields as needed...

	    // Assign product category
	    $category = get_term_by('slug', 'cco.case', 'product_cat');
	    wp_set_post_terms($product_id, [$category->term_id], 'product_cat');
        break;
    }




    ?>Catalog:
<pre><?php

//    print_r($list);
    ?></pre>
</div>
