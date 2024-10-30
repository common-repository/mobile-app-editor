<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    WPRNE
 * @subpackage WPRNE/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    WPRNE
 * @subpackage WPRNE/includes
 * @author     Your Name <email@example.com>
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
	exit;
}


class WPRNE
{

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      WPRNE_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $WPRNE    The string used to uniquely identify this plugin.
	 */
	protected $WPRNE;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct()
	{
		if (defined('WPRNE_VERSION')) {
			$this->version = WPRNE_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->WPRNE = 'wprne';

		$this->load_dependencies();
		$this->set_locale();
		$this->rest_api_init();
		$this->woocommerce_support();
		$this->define_admin_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - WPRNE_Loader. Orchestrates the hooks of the plugin.
	 * - WPRNE_i18n. Defines internationalization functionality.
	 * - WPRNE_Admin. Defines all hooks for the admin area.
	 * - WPRNE_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies()
	{

		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wprne-loader.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wprne-i18n.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wprne-rest-api.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wprne-woocommerce.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wprne-page-templater.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-wprne-admin.php';

		$this->loader = new WPRNE_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the WPRNE_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale()
	{

		$plugin_i18n = new WPRNE_i18n();

		$this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
	}

	/**
	 * Rest api init.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function rest_api_init()
	{

		$rest_api = new WPRNE_Rest_Api();

		$this->loader->add_action('rest_api_init', $rest_api, 'rest_api_init');
	}

	/**
	 * Woocommerce support.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function woocommerce_support()
	{

		$woo = new WPRNE_Woocommerce();

		$this->loader->add_action('wp_loaded', $woo, 'woocommerce_maybe_add_multiple_products_to_cart');
		$this->loader->add_action('woocommerce_thankyou', $woo, 'wprne_process_order_received');
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks()
	{

		$plugin_admin = new WPRNE_Admin($this->get_WPRNE(), $this->get_version());

		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');

		$this->loader->add_action('admin_menu', $plugin_admin, 'admin_menu');
		$this->loader->add_action('publish_post', $plugin_admin, 'publish_post', 10, 2);
	}


	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run()
	{
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_WPRNE()
	{
		return $this->WPRNE;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    WPRNE_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader()
	{
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version()
	{
		return $this->version;
	}
}
