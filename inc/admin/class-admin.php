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
use \WP_Error;
class Admin
{
	public array $messages;
	public \WP_Error $errors;
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
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version The version of this plugin.
	 * @param string $plugin_text_domain The text domain of this plugin
	 * @since    1.0.0
	 */
	public function __construct($plugin_name, $version, $plugin_text_domain)
	{
		$this->messages = [];
		$this->errors = new \WP_Error();
		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->plugin_text_domain = $plugin_text_domain;
		add_action('init', array($this, 'my_load_plugin_textdomain'));
		add_action('admin_init', array($this, 'intcomex2wc_register_settings'));
	}
	function intcomex2wc_apikey_callback()
	{
		$option_value = get_option('intcomex2wc_apikey');
		echo "<input type='text' name='intcomex2wc_apikey' value='$option_value' />";
	}
	function intcomex2wc_privatekey_callback()
	{
		$option_value = get_option('intcomex2wc_privatekey');
		echo "<input type='text' name='intcomex2wc_privatekey' value='$option_value' />";
	}
	function intcomex2wc_pricemargin_callback()
	{
		$option_value = get_option('intcomex2wc_pricemargin');
		echo "<input type='text' name='intcomex2wc_pricemargin' value='$option_value' />";
	}
	function intcomex2wc_register_settings()
	{
		add_settings_section('intcomex2wc_settings_section', 'IntComex 2 WC Settings Section', array($this, 'intcomex2wc_settings_section_callback'), 'intcomex2wc_settings_group');
		add_settings_field('intcomex2wc_apikey', 'IntComex API Key', array($this, 'intcomex2wc_apikey_callback'), 'intcomex2wc_settings_group', 'intcomex2wc_settings_section');
		add_settings_field('intcomex2wc_privatekey', 'IntComex Private Key', array($this, 'intcomex2wc_privatekey_callback'), 'intcomex2wc_settings_group', 'intcomex2wc_settings_section');
		add_settings_field('intcomex2wc_pricemargin', 'Price Margin %', array($this, 'intcomex2wc_pricemargin_callback'), 'intcomex2wc_settings_group', 'intcomex2wc_settings_section');
		register_setting('intcomex2wc_settings_group', 'intcomex2wc_apikey', array($this, 'sanitize_callback_function'));
		register_setting('intcomex2wc_settings_group', 'intcomex2wc_privatekey', array($this, 'sanitize_callback_function'));
		register_setting('intcomex2wc_settings_group', 'intcomex2wc_pricemargin', array($this, 'sanitize_callback_function'));
		if (isset($_POST['action']) && $_POST['action'] === 'update') {
			if(isset($_POST['option_page']) && $_POST['option_page'] === 'intcomex2wc_settings_group') {
				$option_value = sanitize_text_field($_POST['intcomex2wc_apikey']);
				update_option('intcomex2wc_apikey', $option_value);
				$option_value = sanitize_text_field($_POST['intcomex2wc_privatekey']);
				update_option('intcomex2wc_privatekey', $option_value);
				$option_value = sanitize_text_field($_POST['intcomex2wc_pricemargin']);
				update_option('intcomex2wc_pricemargin', $option_value);
				wp_redirect(admin_url('admin.php?page=intcomex2wc')); // Redirect after saving
				exit;
			}
		}
	}
	// Display and save plugin settings
	/*
	function myplugin_save_settings() {
		if (isset($_POST['submit'])) {
			$option_value = sanitize_callback_function($_POST['intcomex2wc_apikey']);
			update_option('yplugin_option_name', $option_value);
		}
	}
	*/
	function intcomex2wc_settings_section_callback()
	{
	}
	function intcomex2wc_settings_group(){
	}
	// Sanitize callback function
	function sanitize_callback_function($input) {
		// Sanitize and validate input as needed
		return $input;
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
		$icon = $this->getIntComex2WCIcon();
		// Dashboard
		$page_hook = add_menu_page(
			'IntComex to WooCommerce', // Page Title
			'IntComex2WC',             // Menu Title
			'manage_options',            // Capability
			'intcomex2wc',               // Menu Slug
			array($this, 'load_IntComex2WCAdminDashboard'), // Callback Function
			$icon                         // Icon URL
		);
		add_action('load-' . $page_hook, array($this, 'preload_admin_dashboard'));
	}
	protected function getIntComex2WCIcon()
	{
		$svg = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<svg
   xmlns:dc="http://purl.org/dc/elements/1.1/"
   xmlns:cc="http://creativecommons.org/ns#"
   xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
   xmlns:svg="http://www.w3.org/2000/svg"
   xmlns="http://www.w3.org/2000/svg"
   xmlns:sodipodi="http://sodipodi.sourceforge.net/DTD/sodipodi-0.dtd"
   xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"
   version="1.1"
   id="svg858"
   width="114"
   height="115"
   viewBox="0 0 114 115"
   sodipodi:docname="icon5.svg"
   inkscape:version="1.0 (4035a4fb49, 2020-05-01)">
  <metadata
     id="metadata8">
    <rdf:RDF>
      <cc:Work
         rdf:about="">
        <dc:format>image/svg+xml</dc:format>
        <dc:type
           rdf:resource="http://purl.org/dc/dcmitype/StillImage" />
        <dc:title></dc:title>
      </cc:Work>
    </rdf:RDF>
  </metadata>
  <sodipodi:namedview
     inkscape:current-layer="g866"
     inkscape:window-maximized="1"
     inkscape:window-y="-8"
     inkscape:window-x="-8"
     inkscape:cy="57.5"
     inkscape:cx="57"
     inkscape:zoom="7.3913043"
     showgrid="false"
     id="namedview6"
     inkscape:window-height="1017"
     inkscape:window-width="1920"
     inkscape:pageshadow="2"
     inkscape:pageopacity="0"
     guidetolerance="10"
     gridtolerance="10"
     objecttolerance="10"
     borderopacity="1"
     bordercolor="#666666"
     pagecolor="#ffffff" />
  <defs
     id="defs862" />
  <g
     inkscape:groupmode="layer"
     inkscape:label="Image"
     id="g866">
    <path
       d="M 55.929688 5.6132812 C 55.29385 5.6132812 50.616466 8.0988536 44.691406 11.414062 C 45.065291 11.408689 45.430412 11.383789 45.779297 11.318359 L 67.363281 11.318359 C 61.412286 8.0518982 56.687957 5.6132812 55.929688 5.6132812 z M 67.363281 11.318359 C 67.962389 11.647206 68.529375 11.956245 69.154297 12.302734 C 69.479476 12.338766 69.811353 12.461711 70.070312 12.738281 C 70.029535 12.745891 70.012084 12.744891 69.972656 12.751953 C 72.112157 13.943207 74.331754 15.201113 76.617188 16.507812 C 77.941717 14.10719 70.040975 15.139511 71.337891 11.318359 L 67.363281 11.318359 z M 76.617188 16.507812 C 76.363478 16.967645 75.775317 17.552148 74.714844 18.322266 C 70.553926 20.514586 71.264468 24.602387 71.337891 28.46875 C 70.767784 33.454597 72.862972 39.531247 70.070312 43.712891 C 64.051362 45.01464 57.10252 44.08101 50.691406 44.357422 L 37.634766 44.357422 L 37.634766 28.400391 L 37.634766 15.419922 C 25.731462 22.2676 12.313624 30.359417 11.417969 31.544922 C 9.8405338 33.632843 10.795592 80.281034 12.46875 82.867188 C 13.092914 83.831941 23.278516 89.996886 35.103516 96.566406 C 46.928517 103.13593 57.321439 108.5343 58.199219 108.5625 C 59.999199 108.6204 100.10735 84.207252 101.30273 82.326172 C 102.43044 80.551589 102.28262 33.815738 101.14258 31.685547 C 100.55743 30.59219 88.266852 23.168521 76.617188 16.507812 z M 37.634766 15.419922 C 38.029133 15.193052 38.424047 14.965926 38.814453 14.742188 C 38.809528 14.613777 38.818232 14.470086 38.808594 14.347656 C 39.490802 12.609777 41.564122 12.04432 43.083984 12.3125 C 43.647093 11.994974 44.150052 11.716963 44.691406 11.414062 C 42.212297 11.449695 39.307848 10.468836 37.634766 12.441406 L 37.634766 15.419922 z M 43.083984 12.3125 C 41.690789 13.098095 40.300934 13.890299 38.814453 14.742188 C 38.947232 18.204226 37.511758 23.616416 41.441406 19.988281 C 41.909583 21.119858 41.175755 23.900582 43.240234 22.001953 L 43.240234 22 C 46.548585 17.576487 37.572042 21.213403 42.634766 16.429688 C 45.958208 14.20063 44.931854 12.638557 43.083984 12.3125 z M 69.972656 12.751953 C 69.682556 12.590428 69.439756 12.461008 69.154297 12.302734 C 67.931744 12.167269 66.821927 13.316337 69.972656 12.751953 z M 55.976562 13.0625 C 54.81834 13.135378 53.667 13.926889 53.800781 15.869141 C 52.54309 19.123934 55.175514 16.586997 55.914062 16.669922 C 59.856764 14.813294 57.906933 12.941037 55.976562 13.0625 z M 69.589844 23.988281 C 69.517023 23.996944 69.434925 24.030953 69.341797 24.091797 C 66.389334 26.916419 63.877014 30.815147 60.630859 32.9375 C 55.385009 34.302303 61.560968 24.179172 55.041016 28.566406 C 51.741304 30.810555 55.083166 36.232673 58.966797 33.773438 C 61.68949 36.588558 49.005308 42.699022 54.177734 43.011719 L 68.341797 43.171875 C 68.789244 42.695921 69.244257 42.226703 69.664062 41.726562 C 70.606467 38.42685 69.770536 34.475318 70.017578 30.919922 C 69.611053 29.287343 70.682162 23.858336 69.589844 23.988281 z M 42.394531 31.763672 C 40.840166 31.677418 37.914864 34.269796 40.675781 35.886719 L 42.1875 35.599609 C 43.766309 32.830703 43.32715 31.815424 42.394531 31.763672 z M 39.259766 38.878906 C 38.629624 38.869189 38.403483 40.220039 38.722656 43.080078 C 39.823999 42.922864 41.26707 43.417087 42.220703 42.841797 C 40.92371 40.259037 39.889907 38.888624 39.259766 38.878906 z M 76.107422 43.541016 C 78.245032 43.674664 80.387948 45.489913 79.802734 47.792969 C 79.070474 50.576137 77.523961 53.081084 77.025391 55.941406 C 74.833935 64.470466 73.802726 73.288637 73.804688 82.089844 C 73.734517 83.836614 73.755257 86.068714 71.835938 86.865234 C 71.078981 87.242314 70.139996 87.280014 69.392578 86.855469 C 65.938875 84.971499 64.098035 81.286646 62.101562 78.072266 C 60.164004 74.479673 58.768249 70.628949 57.480469 66.765625 C 55.48568 69.527748 54.355855 72.790469 52.662109 75.730469 C 50.70632 79.5722 48.96372 83.803191 45.566406 86.613281 C 42.685048 88.097451 40.826125 84.509563 40.378906 82.164062 C 37.696743 73.866197 36.24515 65.237112 34.693359 56.673828 C 34.270554 53.942005 33.736927 51.200142 33.578125 48.445312 C 33.995039 45.442465 38.747911 44.129461 40.345703 46.869141 C 41.650313 49.512026 41.414621 52.568784 42.125 55.382812 C 43.064879 60.938201 44.076171 66.486937 45.445312 71.955078 C 47.585428 70.122171 48.010765 67.091988 49.65625 64.875 C 52.007918 60.502728 54.023435 55.898927 56.916016 51.853516 C 58.985441 49.928586 62.58753 52.069885 62.400391 54.734375 C 63.599225 59.80636 64.592669 64.973541 66.666016 69.783203 C 67.483297 65.930542 67.713914 61.97763 68.59375 58.132812 C 69.557201 53.411532 70.781058 48.579003 73.453125 44.513672 C 74.165049 43.767467 75.135781 43.480267 76.107422 43.541016 z "
       style="fill-opacity:1"
       id="path870" />
  </g>
</svg>
';
		return 'data:image/svg+xml;base64,' . base64_encode($svg);
	}
	public function load_IntComex2WCAdminDashboard()
	{
		include_once('views' . DIRECTORY_SEPARATOR . 'intcomex2wc-dashboard.php');
	}
	function preload_admin_dashboard()
	{
		$this->admin_dashboard = new Admin_Dashboard($this->plugin_text_domain);
		$this->admin_dashboard->handle_actions();
		$this->messages = $this->admin_dashboard->messages;
		$this->errors = $this->admin_dashboard->errors;
	}
}
