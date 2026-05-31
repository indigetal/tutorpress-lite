<?php
/**
 * TutorPress Lite main orchestrator.
 *
 * @package TutorPress_Lite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main TutorPress Lite class.
 *
 * Feature loading is implemented in Step 3.
 */
class TutorPress_Lite_Main {

	/**
	 * Singleton instance.
	 *
	 * @var TutorPress_Lite_Main|null
	 */
	protected static $instance = null;

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	public $version;

	/**
	 * Plugin URL.
	 *
	 * @var string
	 */
	public $plugin_url;

	/**
	 * Plugin filesystem path (trailing slash).
	 *
	 * @var string
	 */
	public $plugin_path;

	/**
	 * Get or create the main instance.
	 *
	 * @param array<string, mixed> $args Constructor arguments.
	 * @return TutorPress_Lite_Main
	 */
	public static function instance( $args = array() ) {
		if ( null === self::$instance ) {
			self::$instance = new self( $args );
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @param array<string, mixed> $args Arguments (main_file, version).
	 */
	private function __construct( $args = array() ) {
		$this->version = isset( $args['version'] ) ? $args['version'] : TUTORPRESS_LITE_VERSION;

		$main_file       = isset( $args['main_file'] ) ? $args['main_file'] : TUTORPRESS_LITE_FILE;
		$this->plugin_url  = plugin_dir_url( $main_file );
		$this->plugin_path = trailingslashit( dirname( $main_file ) );
	}
}
