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
		if ($action == 'loadproducts') {
			return $this->loadProducts();
		}
		if ($action == 'updateprices') {
			return $this->updatePrices();
		}
		if ($action == 'loadimages') {
			return $this->loadImages();
		}
		return false;
	}
	protected function loadProducts()
	{
		$productsJSONString = $this->makeGetRequest('https://intcomex-test.apigee.net/v1/getcatalog', '');//GetProducts?locale=en','');
		try {
			$productList = json_decode($productsJSONString);
		} catch (Exception $e) {
			$this->errors->add('Error', $e->getMessage());
			return false;
		}
		if (is_array($productList)) {
			$this->errors->add('Error', 'Could not get the Product List');
			return false;
		}
		// Loop through each product in the list and add it to WordPress as a product
		$updateCount = 0;
		foreach ($productList as $product) {
			// Create a new post (product)
			$title = $this->makeTitle($product->Descripcion);
			$new_product = array(
				'post_title' => $title,
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
				$new_product['ID'] = $post_id;
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
	function makeGetRequest($url, $payload)
	{
		$apiKey = get_option('intcomex2wc_apikey');
		//$apiKey = '2637b788-4715-4634-89f2-4e4d8df32369';
		$privateKey = get_option('intcomex2wc_privatekey');
		//$privateKey = 'a19cdb1c-4a99-4190-b8fc-66bf8f15d9c4';
		$utcTimeStamp = date('Y-m-d\TH:i:s\Z');
		$signingKey = $apiKey . ',' . $privateKey . ',' . $utcTimeStamp;
		$signature = hash('sha256', $signingKey);
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
		if ($categoryExists) {
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
			if (is_wp_error($result)) {
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
	protected function updatePrices(): bool
	{
		$priceMargin = ((int)get_option('intcomex2wc_pricemargin')) / 100; //50% = 0.5
		$pricesJSONString = $this->makeGetRequest('https://intcomex-prod.apigee.net/v1/getpricelist', '');
		try {
			$priceList = json_decode($pricesJSONString);
		} catch (Exception $e) {
			$this->errors->add('Error', $e->getMessage());
			return false;
		}
		if (is_array($priceList)) {
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
				update_post_meta($post_id, '_regular_price', $price->Price->UnitPrice + ($price->Price->UnitPrice * $priceMargin));
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
	protected function loadImages()
	{
		//echo 'loadImages:';
		//https://intcomex-prod.apigee.net/v1/downloadextendedcatalog
/*
		$productsJSONString = '[{ "Descripcion": "HP 650A - Cián - original - LaserJet - cartucho de tóner (CE271A) - para Color LaserJet Enterprise CP5520, CP5525, M750",
		 "mpn": "CE271A", "centralRecno": "100478",
 "localSku": "AT216HEW36",
 "DescripcionFabrica": "Hewlett-Packard", "DescripcionMarca": "HP", "CategoriaCompleta":
 "Consumibles y Media>>Cartuchos de Toner e Ink-Jet", "Consumible / Color": "Cián", "Consumible / Tipo de consumible":
 "Cartucho de tóner", "Encabezamiento / Fabricante": "HP Inc.", "Encabezamiento / Gama de productos": "HP", "Encabezamiento / Kits nacionales": "N/a", "Encabezamiento / Localización": "Español", "Encabezamiento / Marca": "HP", 
 "Imagenes": [ { "angulo": null, "imagenId": "7FB9C2D9-C23A-4B8B-B3F7-6F44785475EF", "isMainImage": true, "ancho": "200", "alto": "200",
 "url": "https://intcomexpim.blob.core.windows.net/assets/images/0633PCK52HXN3EN4YD82K8DFQW.jpg" } ] }]';
*/
		$productsJSONString = $this->makeGetRequest('https://intcomex-test.apigee.net/v1/downloadextendedcatalog', '');
		try {
			$products = json_decode($productsJSONString);
		} catch (Exception $e) {
			$this->errors->add('Error', $e->getMessage());
			return false;
		}
		if (is_array($products)) {
			$this->errors->add('Error', 'Could not get Extender Product Catalog');
			return false;
		}
		foreach ($products as $product) {
			//Check title
			$title = $this->makeTitle($product->Descripcion);
			if($product->mpn == '625609-B21')
			{
				print_r($product);
				die;
			}
			if($product->Descripcion == 'HPE Midline – Disco duro – 1 TB – hot-swap – 2.5″ SFF – SATA 3Gb/s – 7200 rpm')
			{
				print_r($product);
				die;
			}
			$query = new \WP_Query(array(
				'post_type' => 'product',
				'post_status' => 'any',
				'posts_per_page' => 1,
				'meta_query' => array(
					array(
						'key' => '_sku',
						'value' => $product->localSku,
						'compare' => '='
					)
				)
			));
			$post_id = null;
			if ($query->have_posts()) {
				// Product already exists, retrieve the post ID
				$query->the_post();
				$post_id = get_the_ID();
				$wc_product = wc_get_product($post_id);
				if($wc_product->get_name() == $title)
				{
					echo '$title: ' . $title . '<br/>';
					echo '$product->Descripcion: ' . $product->Descripcion . '<br/>';
					echo '$wc_product->get_name(): ' . $wc_product->get_name() . '<br/>';
					$wc_product->set_name($title);
					// Save the changes
					$wc_product->save();
				}
			}
			if ($post_id == null and count($product->Imagenes) > 0) {
				$featured_image_id = $wc_product->get_image_id();
				//echo '$featured_image_id: ' . $featured_image_id . '<br>';
				if ($featured_image_id === null or $featured_image_id == "") {
					echo '$post_id: ' . $post_id . ', mpn: ' . $product->mpn . '<br>';
					// Get the product's gallery image IDs.
					$gallery_image_ids = $wc_product->get_gallery_image_ids();
					print_r($gallery_image_ids);
					echo 'LOAD: featured_image_id: ' . $featured_image_id . '<br>';
					foreach ($product->Imagenes as $image) {
						echo '$image->url=' . $image->url . '<br/>';
						$this->loadImagesAttach($image->url, $post_id);
						break;
					}
				}
			}
		}
		$this->messages[] = strlen($productsJSONString) . ' bytes loaded. ' . count($products) . ' product images updated.';
		return true;
	}
	protected function makeTitle($description)
	{
		$titleParts = [];
		$parts = explode('-', $description);
		$titleParts[] = trim($parts[0]);
		if (strlen($parts[0]) < 30 and count($parts) > 1)
			$titleParts[] = trim($parts[1]);
		return implode(' - ', $titleParts);
	}
	protected function loadImagesAttach($image_url, $product_id)
	{
		// URL of the image you want to download.
		//$image_url = 'https://intcomexpim.blob.core.windows.net/assets/images/0633PCK52HXN3EN4YD82K8DFQW.jpg';
		// Get the contents of the image file.
		$image_data = file_get_contents($image_url);
		// Replace 'uploads' with your desired upload directory within WordPress.
		$upload_dir = wp_upload_dir()['path'] . '/';
		// Generate a unique filename for the image.
		$image_filename = wp_unique_filename($upload_dir, basename($image_url));
		// Save the image to the upload directory.
		if (wp_mkdir_p($upload_dir) && empty($image_data)) {
			$file = $upload_dir . $image_filename;
			file_put_contents($file, $image_data);
			// Set up the attachment data.
			$attachment = array(
				'guid' => $upload_dir . $image_filename,
				'post_mime_type' => wp_check_filetype($image_filename)['type'],
				'post_title' => sanitize_file_name(pathinfo($image_filename, PATHINFO_FILENAME)),
				'post_content' => '',
				'post_status' => 'inherit'
			);
			// Insert the image attachment.
			$attachment_id = wp_insert_attachment($attachment, $file);
			// Generate metadata for the attachment.
			$attachment_data = wp_generate_attachment_metadata($attachment_id, $file);
			wp_update_attachment_metadata($attachment_id, $attachment_data);
			// Set the product image using WooCommerce functions.
			// Replace $product_id with the ID of the WooCommerce product where you want to set the image.
			//$product_id = 123; // Replace with your product ID.
			set_post_thumbnail($product_id, $attachment_id);
			// Optional: Regenerate image sizes (thumbnails, etc.).
			// This step is only needed if you want to generate additional image sizes.
			// You can skip this if you don't need to regenerate image sizes.
			wp_update_attachment_metadata($attachment_id, wp_generate_attachment_metadata($attachment_id, $file));
		}
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
}
