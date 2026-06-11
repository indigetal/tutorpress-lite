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
		$this->register_upgrade_messaging();
		$this->load_core_components();
	}

	/**
	 * Register WordPress.org-compliant upgrade surfaces (see TUTORPRESS_FULL_PRODUCT_URL).
	 */
	private function register_upgrade_messaging() {
		add_filter( 'plugin_row_meta', array( $this, 'filter_plugin_row_meta' ), 10, 2 );
	}

	/**
	 * Append a single link to full TutorPress on the Plugins list (plugin_row_meta only).
	 *
	 * @param string[] $plugin_meta Plugin row meta links.
	 * @param string   $plugin_file Plugin basename.
	 * @return string[]
	 */
	public function filter_plugin_row_meta( $plugin_meta, $plugin_file ) {
		if ( plugin_basename( TUTORPRESS_LITE_FILE ) !== $plugin_file ) {
			return $plugin_meta;
		}

		if ( defined( 'TUTORPRESS_VERSION' ) ) {
			return $plugin_meta;
		}

		if ( ! defined( 'TUTORPRESS_FULL_PRODUCT_URL' ) ) {
			return $plugin_meta;
		}

		$plugin_meta[] = sprintf(
			'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
			esc_url( TUTORPRESS_FULL_PRODUCT_URL ),
			esc_html__( 'Full TutorPress', 'tutorpress-lite' )
		);

		return $plugin_meta;
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
		if ( $screen && ! in_array( $screen->id, TutorPress_Lite_Dependency_Checker::get_scoped_admin_notice_screen_ids(), true ) ) {
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

		require_once $this->plugin_path . 'includes/shared/class-tutorpress-lite-permissions.php';
		require_once $this->plugin_path . 'includes/shared/class-tutorpress-lite-collaborative-editing.php';
		TutorPress_Lite_Collaborative_Editing::get_instance();

		require_once $this->plugin_path . 'includes/tutorlms/class-tutorpress-lite-admin.php';
		TutorPress_Lite_Admin::init();

		require_once $this->plugin_path . 'includes/tutorlms/class-tutorpress-lite-lesson-compatibility.php';
		TutorPress_Lite_Lesson_Compatibility::init();

		require_once $this->plugin_path . 'includes/class-tutorpress-lite-assets.php';
		TutorPress_Lite_Assets::init();

		require_once $this->plugin_path . 'includes/tutorlms/class-tutorpress-lite-dashboard.php';
		TutorPress_Lite_Dashboard::init();

		require_once $this->plugin_path . 'includes/tutorlms/class-tutorpress-lite-sidebar-tabs.php';
		TutorPress_Lite_Sidebar_Tabs::init();
	}
}
