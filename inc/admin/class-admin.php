<?php

namespace IntComex2WC\Inc\Admin;

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @link       https://www.nuancedesignstudio.in
 * @since      1.0.0
 *
 * @author    Karan NA Gupta
 */
class Admin
{

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $plugin_name The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $version The current version of this plugin.
     */
    private $version;

    /**
     * The text domain of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $plugin_text_domain The text domain of this plugin.
     */
    private $plugin_text_domain;

    /**
     * WP_List_Table object
     *
     * @since    1.0.0
     * @access   private
     * @var      admin_table_list $admin_list_table
     */
    private $admin_table_list;
    private $admin_table_edit;
    private $admin_field_list;
    private $admin_field_edit;
    private $admin_record_list;
    private $admin_record_edit;
    private $admin_layout_list;
    private $admin_layout_edit;

    /**
     * Initialize the class and set its properties.
     *
     * @param string $plugin_name The name of this plugin.
     * @param string $version The version of this plugin.
     * @param string $plugin_text_domain The text domain of this plugin
     * @since    1.0.0
     */
    public function __construct($plugin_name, $version, $plugin_text_domain)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->plugin_text_domain = $plugin_text_domain;
        add_action('init', array($this, 'my_load_plugin_textdomain'));
    }

    function my_load_plugin_textdomain()
    {
        $domain = 'intcomex2wc';
        $mo_file = ABSPATH . 'wp-content/plugins/intcomex2wc/Languages/' . $domain . '-' . get_locale() . '.mo';

        load_textdomain($domain, $mo_file);
    }

    /**
     * Callback for the user sub-menu in define_admin_hooks() for class Init.
     *
     * @since    1.0.0
     */
    public function add_plugin_admin_menu()
    {
        // Get the custom tables icon
        $icon = $this->getCustomTablesIcon();

        // Dashboard
        add_menu_page(
            'IntComex to WooCommerce', // Page Title
            'IntComex2WC',             // Menu Title
            'manage_options',            // Capability
            'intcomex2wc',               // Menu Slug
            array($this, 'load_IntComex2WCAdminDashboard'), // Callback Function
            $icon                         // Icon URL
        );
    }

    protected function getCustomTablesIcon()
    {
        $svg = '<?xml version="1.0" encoding="UTF-8" standalone="no"?><svg xmlns:sodipodi="http://sodipodi.sourceforge.net/DTD/sodipodi-0.dtd"   xmlns="http://www.w3.org/2000/svg"
   xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"
   
   inkscape:version="1.0 (4035a4fb49, 2020-05-01)"
   sodipodi:docname="ct-gray.svg"
   viewBox="0 0 114 115"
   height="115"
   width="114"
   id="svg858">
  <defs
     id="defs862" />

  <g
     id="g866"
     inkscape:label="Image"
     inkscape:groupmode="layer">
    <path
       id="path870"
       d="M 35.103826,96.56662 C 23.278826,89.9971 13.093146,83.832692 12.468982,82.867939 10.795824,80.281786 9.8404542,33.632036 11.417889,31.544115 13.001683,29.447778 53.915377,5.6138435 55.930197,5.6138435 c 2.43754,0 44.123893,24.0385655 45.211953,26.0716115 1.14004,2.130191 1.28774,48.867091 0.16003,50.641674 -1.19538,1.88108 -41.302403,26.293271 -43.102383,26.235391 -0.87778,-0.0282 -11.27097,-5.42638 -23.095971,-11.9959 z M 51.406787,75.963401 c 3.54226,-0.585352 3.78219,-0.8413 3.5,-3.733727 -0.30086,-3.083731 -0.3435,-3.105334 -6.13946,-3.110564 -5.15179,-0.0046 -6.119637,-0.334426 -8.249999,-2.811031 -1.921122,-2.233358 -2.413502,-3.932138 -2.413502,-8.326923 0,-4.806975 0.389869,-5.911028 3.01397,-8.535129 2.704151,-2.704154 3.544471,-2.978928 8.174891,-2.673077 4.92357,0.325215 5.19554,0.212012 5.91354,-2.46142 0.64567,-2.404117 0.39843,-2.936974 -1.73999,-3.75 -6.40245,-2.434204 -16.319434,0.374476 -20.107948,5.694953 -5.6066,7.873744 -4.870656,19.869788 1.576962,25.704801 2.097016,1.897774 8.514176,4.539463 11.168576,4.597661 0.825,0.01809 3.21133,-0.249907 5.30296,-0.595544 z m 22.76236,-14.599558 -0.16146,-13.75 h 4.54807 c 4.531126,0 4.548066,-0.01304 4.548066,-3.5 v -3.5 h -12.499996 -12.5 v 3.5 c 0,3.481481 0.0238,3.5 4.5,3.5 h 4.5 v 14.060365 14.060366 l 3.61339,-0.310366 3.61339,-0.310365 z"
       style="fill-opacity:1" />
  </g>
</svg>';

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    public function load_IntComex2WCAdminDashboard()
    {
        include_once('views' . DIRECTORY_SEPARATOR . 'intcomex2wc-dashboard.php');
    }
}
