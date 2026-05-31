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
 */
class TutorPress_Lite_Main {

	/**
	 * User meta key for dismissed dual-plugin notice.
	 */
	const DISMISS_DUAL_PLUGIN_META_KEY = 'tutorpress_lite_dismiss_dual_plugin_notice';

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

		$main_file         = isset( $args['main_file'] ) ? $args['main_file'] : TUTORPRESS_LITE_FILE;
		$this->plugin_url  = plugin_dir_url( $main_file );
		$this->plugin_path = trailingslashit( dirname( $main_file ) );

		$this->init();
	}

	/**
	 * Initialize the plugin.
	 */
	private function init() {
		$this->check_dependencies();
		$this->register_dual_plugin_notice();
		$this->load_core_components();
	}

	/**
	 * Check plugin dependencies.
	 */
	private function check_dependencies() {
		require_once $this->plugin_path . 'includes/class-tutorpress-lite-dependency-checker.php';

		$errors = TutorPress_Lite_Dependency_Checker::check_immediate_requirements();
		if ( ! empty( $errors ) ) {
			TutorPress_Lite_Dependency_Checker::display_errors( $errors );
			return;
		}

		TutorPress_Lite_Dependency_Checker::schedule_deferred_checks();
	}

	/**
	 * Whether full TutorPress is loaded (defines TUTORPRESS_VERSION).
	 *
	 * @return bool
	 */
	private function is_full_tutorpress_active() {
		return defined( 'TUTORPRESS_VERSION' );
	}

	/**
	 * Register dual-plugin admin notice hooks.
	 *
	 * Hooks are always registered; full TutorPress is detected at render time
	 * because Lite may load before tutorpress.php defines TUTORPRESS_VERSION.
	 */
	private function register_dual_plugin_notice() {
		add_action( 'admin_notices', array( $this, 'render_dual_plugin_notice' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_dual_plugin_dismiss_script' ) );
		add_action( 'wp_ajax_tutorpress_lite_dismiss_dual_plugin_notice', array( $this, 'ajax_dismiss_dual_plugin_notice' ) );
	}

	/**
	 * Render dismissible notice when full TutorPress and Lite are both active.
	 */
	public function render_dual_plugin_notice() {
		if ( ! $this->is_full_tutorpress_active() ) {
			return;
		}

		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		if ( get_user_meta( get_current_user_id(), self::DISMISS_DUAL_PLUGIN_META_KEY, true ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen && ! in_array( $screen->id, array( 'dashboard', 'plugins', 'toplevel_page_tutorpress-settings' ), true ) ) {
			return;
		}

		$lite_plugin = 'tutorpress-lite/tutorpress-lite.php';
		$deactivate_url = wp_nonce_url(
			admin_url( 'plugins.php?action=deactivate&plugin=' . rawurlencode( $lite_plugin ) ),
			'deactivate-plugin_' . $lite_plugin
		);

		$deactivate_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( $deactivate_url ),
			esc_html__( 'TutorPress Lite', 'tutorpress-lite' )
		);

		$message = sprintf(
			/* translators: %s: deactivate link for TutorPress Lite */
			__(
				'TutorPress and TutorPress Lite are both active. Deactivate %s to avoid conflicts. The full version of TutorPress includes all Lite features but requires an active license.',
				'tutorpress-lite'
			),
			$deactivate_link
		);

		printf(
			'<div class="notice notice-warning is-dismissible tutorpress-lite-dual-plugin-notice"><p>%s</p></div>',
			wp_kses(
				$message,
				array(
					'a' => array(
						'href' => array(),
					),
				)
			)
		);
	}

	/**
	 * Enqueue script to persist dismissal of the dual-plugin notice.
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 */
	public function enqueue_dual_plugin_dismiss_script( $hook_suffix ) {
		unset( $hook_suffix );

		if ( ! $this->is_full_tutorpress_active() ) {
			return;
		}

		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		if ( get_user_meta( get_current_user_id(), self::DISMISS_DUAL_PLUGIN_META_KEY, true ) ) {
			return;
		}

		wp_enqueue_script( 'jquery' );

		$inline = sprintf(
			'jQuery(function($){$(document).on("click",".tutorpress-lite-dual-plugin-notice .notice-dismiss",function(){$.post(ajaxurl,{action:"tutorpress_lite_dismiss_dual_plugin_notice",nonce:%s});});});',
			wp_json_encode( wp_create_nonce( 'tutorpress_lite_dismiss_dual_plugin_notice' ) )
		);

		wp_add_inline_script( 'jquery', $inline );
	}

	/**
	 * AJAX handler: persist dual-plugin notice dismissal per user.
	 */
	public function ajax_dismiss_dual_plugin_notice() {
		check_ajax_referer( 'tutorpress_lite_dismiss_dual_plugin_notice', 'nonce' );

		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_send_json_error( null, 403 );
		}

		update_user_meta( get_current_user_id(), self::DISMISS_DUAL_PLUGIN_META_KEY, '1' );

		wp_send_json_success();
	}

	/**
	 * Load and initialize feature classes from this orchestrator only.
	 *
	 * Feature classes must not call ::init() at file bottom (Step 4+ register here).
	 */
	private function load_core_components() {
		require_once $this->plugin_path . 'includes/tutorpress-lite-functions.php';
		require_once $this->plugin_path . 'includes/class-tutorpress-lite-settings.php';
		TutorPress_Lite_Settings::init();

		// Step 7–13: tutorlms / assets classes via ::init() from here.
	}
}
