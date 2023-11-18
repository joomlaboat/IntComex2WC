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
    error_reporting(E_ALL);
    ini_set('log_errors', 1);
    echo $this->makeGetRequest('https://intcomex-test.apigee.net/v1/getcatalog', '');//GetProducts?locale=en','');


    ?>

</div>
