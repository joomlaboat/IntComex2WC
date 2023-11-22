<?php

namespace IntComex2WC\Inc\Admin;

/**
 * Class for displaying registered WordPress Users
 * in a WordPress-like Admin Table with row actions to
 * perform user meta operations
 *
 *
 * @link       http://nuancedesignstudio.in
 * @since      1.0.0
 *
 * @author     Karan NA Gupta
 */
class Admin_Dashboard extends \IntComex2WC\inc\Libraries\WP_List_Table
{
	public string $plugin_text_domain;
	public array $messages;
	public \WP_Error $errors;

	public function __construct($plugin_text_domain)
	{
		$this->messages = [];
		$this->errors = new \WP_Error();
		$this->plugin_text_domain = $plugin_text_domain;
	}

	function handle_actions()
	{
		$action = $this->current_action();

		if ($action == 'updateprices') {
			return $this->updatePrices();
		}

		if ($action == 'loadproducts') {
			return $this->loadProducts();
		}

		return false;
	}

	protected function loadProducts()
	{
		$productsJSONString = '[{"Sku":"CS700GNC04","Mpn":"CX-1153-RB3","Description":"Gen Color Case Kit inAqua in Eng 110v","Type":"Physical","Manufacturer":{"ManufacturerId":"gen","Description":"Generic"},"Brand":{"ManufacturerId":"gen","BrandId":"gen","Description":"Generic"},"Category":{"CategoryId":"cco","Description":"Componentes Informáticos","Subcategories":[{"CategoryId":"cco.case","Description":"Cajas / Gabinetes","Subcategories":[]}]},"Components":null,"CompilationDate":"2023-11-13T18:40:12.6191757Z","PrePurchaseStartDate":null,"PrePurchaseEndDate":null,"PrePurchaseActive":false}]';//$this->makeGetRequest('https://intcomex-test.apigee.net/v1/getcatalog', '');//GetProducts?locale=en','');

		try {
			$productList = json_decode($productsJSONString);
		} catch (Exception $e) {
			$this->errors->add('Error', $e->getMessage());
			return false;
		}

		if(!is_array($productList))
		{
			$this->errors->add('Error', 'Could not get the Product List');
			return false;
		}

		// Loop through each product in the list and add it to WordPress as a product
		$updateCount = 0;
		foreach ($productList as $product) {
			// Create a new post (product)
			$new_product = array(
				'post_title' => $product->Mpn,
				'post_content' => $product->Description,
				'post_status' => 'publish',
				'post_author' => 1,
				'post_type' => 'product'
			);

			// Check if the product already exists by title
			$query = new \WP_Query(array(
				'post_type' => 'product',
				'post_status' => 'any',
				'posts_per_page' => 1,
				'title' => $new_product['post_title'],
			));

			if ($query->have_posts()) {
				// Product already exists

				$query->the_post();
				$post_id = get_the_ID();
				$new_product['ID']=$post_id;
				$product_id = wp_insert_post($new_product);

			} else {
				// Product doesn't exist, so insert the new product
				$product_id = wp_insert_post($new_product);

				// Update product meta data
				update_post_meta($product_id, '_sku', $product->Sku);

				// Assign or update product category
				$categoryTermId = $this->addCategoryIfNeeded($product->Category);
				wp_set_post_terms($product_id, [$categoryTermId], 'product_cat');

				$updateCount += 1;
			}

			$this->messages[] = strlen($productsJSONString) . ' bytes loaded. ' . $updateCount . ' product updated.';
		}
	}

	protected function updatePrices(): bool
	{
		$pricesJSONString = $this->makeGetRequest('https://intcomex-prod.apigee.net/v1/getpricelist', '');

		try {
			$priceList = json_decode($pricesJSONString);
		} catch (Exception $e) {
			$this->errors->add('Error', $e->getMessage());
			return false;
		}

		if(!is_array($priceList))
		{
			$this->errors->add('Error', 'Could not get the Price List');
			return false;
		}

		foreach ($priceList as $price) {
			// Check if the product already exists by SKU (_sku)
			$query = new \WP_Query(array(
				'post_type' => 'product',
				'post_status' => 'any',
				'posts_per_page' => 1,
				'meta_query' => array(
					array(
						'key' => '_sku',
						'value' => $price->Sku,
						'compare' => '='
					)
				)
			));

			if ($query->have_posts()) {
				// Product already exists, retrieve the post ID
				$query->the_post();
				$post_id = get_the_ID();

				// Update regular price
				update_post_meta($post_id, '_regular_price', $price->Price->UnitPrice + ($price->Price->UnitPrice * 0.1));

				// Update sale price
				update_post_meta($post_id, '_sale_price', $price->Price->UnitPrice);

				// Visible price
				update_post_meta($post_id, '_price', $price->Price->UnitPrice);

				//wp_reset_postdata($post_id); // Reset post data
				wc_delete_product_transients($post_id);
			} else {
				// Product doesn't exist
				$post_id = 0; // Set a default value or handle it as needed
			}
		}

		$this->messages[] = strlen($pricesJSONString) . ' bytes loaded. ' . count($priceList) . ' product prices updated.';

		return true;
	}

	protected function makePostRequest($url, $payload)
	{
		//$apiKey = '21B17E10-351D-40EC-9042-3AD080858584';
		//$utcTimeStamp='2015-02-26T15:06:18Z';
		//$privateKey = 'F46EF264-4FC5-4671-92D9-CE69B888F62F';

		$apiKey = '2637b788-4715-4634-89f2-4e4d8df32369';
		$privateKey = 'a19cdb1c-4a99-4190-b8fc-66bf8f15d9c4';
		$utcTimeStamp = date('Y-m-d\TH:i:s\Z');
		$signingKey = $apiKey . ',' . $privateKey . ',' . $utcTimeStamp;
		$signature = hash('sha256', $signingKey);

		//echo '$utcTimeStamp=' . $utcTimeStamp . ';<br>';
		//echo '$signature=' . $signature . ';<br>';

		$headers = array(
			'Content-type: application/json',
			'content-length: ' . strlen($payload),
			'Host: intcomex-test.apigee.net',
			'Authorization: Bearer apiKey=' . $apiKey . '&utcTimeStamp=' . $utcTimeStamp . '&signature=' . $signature
		);
		$ch = curl_init();

		//$timeout = 1500;
		curl_setopt($ch, CURLOPT_URL, $url);
		//curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		//curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_NOBODY, false);

		$serverResponse = curl_exec($ch);

		curl_close($ch);

		return $serverResponse;
	}

	function makeGetRequest($url, $payload)
	{
		$apiKey = '2637b788-4715-4634-89f2-4e4d8df32369';
		$privateKey = 'a19cdb1c-4a99-4190-b8fc-66bf8f15d9c4';
		$utcTimeStamp = date('Y-m-d\TH:i:s\Z');
		$signingKey = $apiKey . ',' . $privateKey . ',' . $utcTimeStamp;
		$signature = hash('sha256', $signingKey);

		//echo '$utcTimeStamp=' . $utcTimeStamp . ';<br>';
		//echo '$signingKey='.$signingKey.';<br>';
		//echo '$signature=' . $signature . ';<br>';

		$headers = array(
			'Content-type: application/json',
			'Host: intcomex-test.apigee.net'
		);
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url . '?apiKey=' . $apiKey . '&utcTimeStamp=' . $utcTimeStamp . '&signature=' . $signature);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_NOBODY, false);

		$serverResponse = curl_exec($ch);

		curl_close($ch);
		return $serverResponse;
	}

	protected function addCategoryIfNeeded($categoryInfo, $parentSlag = null)
	{
		$categoryId = $categoryInfo->CategoryId; // Category ID: 'cco';
		$categoryDescription = $categoryInfo->Description; // Category Description: 'Componentes Informáticos';

		// New category information
		$newCategory = [
			'name' => $categoryDescription, // Category name
			'slug' => $categoryId, // Category slug
			//'description' => $categoryDescription, // Category description (optional)
			'parent' => $parentSlag, // Parent category slug (if any)
		];

		// Check if the category already exists
		$categoryExists = term_exists($newCategory['slug'], 'product_cat');

		if (!$categoryExists) {

			// Insert the new category into WooCommerce product categories
			$result = wp_insert_term(
				$newCategory['name'], // Category name
				'product_cat', // Taxonomy name for product categories
				[
					//'description' => $newCategory['description'],
					'slug' => $newCategory['slug'],
					'parent' => $newCategory['parent'],
				]
			);
			if (!is_wp_error($result)) {
				// Category added successfully
				$categoryTermId = $result['term_id'];
				echo 'New category added successfully with ID: ' . $categoryTermId;
				//return $result['term_id'];
			} else {
				// Failed to add the category
				echo 'Failed to add the category. Error: ' . $result->get_error_message();
				die;
			}
		} else {
			$categoryTermId = $categoryExists['term_id'];
		}

		// Access subcategory details
		$subcategories = $categoryInfo->Subcategories;
		if (count($subcategories) > 1) {
			print_r($subcategories);
			die('More than one sub category');
		}

		foreach ($subcategories as $subcategory) {
			$subCategoryTermId = $this->addCategoryIfNeeded($subcategory, $categoryTermId);
			return $subCategoryTermId;
		}

		return $categoryTermId;
	}
}