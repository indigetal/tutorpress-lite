<?php
/**
 * TutorPress Lite script and style enqueuing.
 *
 * @package TutorPress_Lite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Frontend and admin asset registration for Lite features.
 */
class TutorPress_Lite_Assets {

	/**
	 * Register asset hooks.
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_dashboard_assets' ) );
	}

	/**
	 * Enqueue frontend dashboard override script when redirects are enabled.
	 */
	public static function enqueue_dashboard_assets() {
		if ( ! tutorpress_get_setting( 'enable_dashboard_redirects', false ) ) {
			return;
		}

		$script_path = TUTORPRESS_LITE_PATH . 'assets/js/override-tutorlms.js';
		if ( ! file_exists( $script_path ) ) {
			return;
		}

		wp_enqueue_script(
			'tutorpress-lite-override-tutorlms',
			TUTORPRESS_LITE_URL . 'assets/js/override-tutorlms.js',
			array( 'jquery' ),
			(string) filemtime( $script_path ),
			true
		);

		wp_localize_script(
			'tutorpress-lite-override-tutorlms',
			'TutorPressData',
			array(
				'enableDashboardRedirects' => tutorpress_get_setting( 'enable_dashboard_redirects', false ),
				'enableExtraDashboardLinks' => tutorpress_get_setting( 'enable_extra_dashboard_links', false ),
				'adminUrl'                  => admin_url(),
			)
		);
	}
}
