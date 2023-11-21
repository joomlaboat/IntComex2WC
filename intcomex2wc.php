<?php
/**
 * @link              https://ct4.us/
 * @since             1.0.0
 * @package           IntComex2WC
 *
 * @wordpress-plugin
 * Plugin Name:       IntComex2WC
 * Plugin URI:        https://ct4.us/
 * Description:       Import Products from IntComex to WooCommerce
 * Version:           1.0.0
 * Author:            Ivan Komlev
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       intcomex2wc
*/

namespace IntComex2WC;

if (!defined('WPINC')) {
    die;
}

define(__NAMESPACE__ . '\INTCOMEX2WC', __NAMESPACE__ . '\\');

define(INTCOMEX2WC . 'PLUGIN_NAME', 'intcomex2wc');

define(INTCOMEX2WC . 'PLUGIN_VERSION', '1.0.0');

define(INTCOMEX2WC . 'PLUGIN_NAME_DIR', plugin_dir_path(__FILE__));

define(INTCOMEX2WC . 'PLUGIN_NAME_URL', plugin_dir_url(__FILE__));

define(INTCOMEX2WC . 'PLUGIN_BASENAME', plugin_basename(__FILE__));

define(INTCOMEX2WC . 'PLUGIN_TEXT_DOMAIN', 'intcomex2wc');

require_once(PLUGIN_NAME_DIR . 'inc/libraries/autoloader.php');

/**
 * Register Activation and Deactivation Hooks
 * This action is documented in inc/core/class-activator.php
 */

//register_activation_hook(__FILE__, array(INTCOMEX2WC . 'Inc\Core\Activator', 'activate'));

/**
 * The code that runs during plugin deactivation.
 * This action is documented inc/core/class-deactivator.php
 */

//register_deactivation_hook(__FILE__, array(INTCOMEX2WC . 'Inc\Core\Deactivator', 'deactivate'));


/**
 * Plugin Singleton Container
 *
 * Maintains a single copy of the plugin app object
 *
 * @since    1.0.0
 */

class IntComex2WC
{

    static $init;

    /**
     * Loads the plugin
     *
     * @access    public
     */
    public static function init()
    {
        if (null == self::$init) {
            self::$init = new Inc\Core\Init();
            self::$init->run();
        }
        return self::$init;
    }

}

/*
 * Begins execution of the plugin
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * Also returns copy of the app object so 3rd party developers
 * can interact with the plugin's hooks contained within.
 *
 */
function intcomex2wc_init()
{
    return IntComex2WC::init();
}

$min_php = '5.6.0';

// Check the minimum required PHP version and run the plugin.
if (version_compare(PHP_VERSION, $min_php, '>=')) {
    intcomex2wc_init();
}