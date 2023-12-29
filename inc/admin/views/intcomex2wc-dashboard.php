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


    <h1>Settings</h1>
    <form method="post" action="options.php">
	    <?php settings_fields('intcomex2wc_settings_group'); ?>
	    <?php do_settings_sections('intcomex2wc_settings_group'); ?>
	    <?php submit_button(); ?>
    </form>

    <hr style="margin-bottom: 30px; "/>

    <form method="post" name="dashboard" id="dashboard" novalidate="novalidate">
        <input id="actionInput" name="action" type="hidden" value="updateallprices"/>

        <?php wp_nonce_field('dashboard', '_wpnonce_dashboard'); ?>

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
            <!--
            <div style="display: inline-block">
				<?php submit_button('Load Products', 'primary', 'loadproducts', true, ['onclick' => 'document.getElementById("actionInput").value="loadproducts"']); ?>
            </div>
-->
            <div style="display: inline-block">
				<?php submit_button('Update Prices', 'primary', 'updateprices', true, ['onclick' => 'document.getElementById("actionInput").value="updateprices"']); ?>
            </div>

            <div style="display: inline-block">
		        <?php submit_button('Load Images', 'primary', 'loadimages', true, ['onclick' => 'document.getElementById("actionInput").value="loadimages"']); ?>
            </div>
        </div>
    </form>
</div>
