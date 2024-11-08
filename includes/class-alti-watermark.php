<?php

class Alti_Watermark {

	protected $version;
	protected $loader;

	/**
	 * constructor
	 */
	public function __construct() {

		$this->version = '0.3';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();

	}

	/**
	 * load seperate files needed to trigger actions or filters, translation and admin class only since public class has to be autonomous.
	 */
	private function load_dependencies() {

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-alti-watermark-loader.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-alti-watermark-i18n.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-alti-watermark-admin.php';

		$this->loader = new Alti_Watermark_Loader();

	}

	/**
	 * set locale for translation ends.
	 */
	private function set_locale() {

		$plugin_i18n = new Alti_Watermark_i18n();
		$plugin_i18n->set_domain( 'alti-watermark' );

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * action and filter for admin side
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Alti_Watermark_Admin( 'alti-watermark', $this->get_version() );

		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_submenu_page' );
		$this->loader->add_filter( 'plugin_action_links_' . 'alti-watermark' . '/' . 'alti-watermark' . '.php', $plugin_admin, 'add_settings_link' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

	}

	/**
	 * run the whole logic of the plugin
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * get plugin name from constructor
	 * @return string plugin name
	 */
	public function get_plugin_name() {
		return 'alti-watermark';
	}

	/**
	 * get loader
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * get version of plugin from constructor
	 * @return string current version
	 */
	public function get_version() {
		return $this->version;
	}

}