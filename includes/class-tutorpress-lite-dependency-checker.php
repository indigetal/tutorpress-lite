<?php
/**
 * TutorPress Lite dependency checker.
 *
 * @package TutorPress_Lite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Checks core system requirements before plugin initialization.
 */
class TutorPress_Lite_Dependency_Checker {

	/**
	 * Minimum PHP version required.
	 */
	const MINIMUM_PHP_VERSION = '7.4';

	/**
	 * Minimum WordPress version required.
	 */
	const MINIMUM_WP_VERSION = '6.0';

	/**
	 * Check immediate requirements (PHP/WordPress versions).
	 *
	 * @return string[] Error messages; empty when all requirements are met.
	 */
	public static function check_immediate_requirements() {
		$errors = array();

		if ( ! self::check_php_requirement() ) {
			$errors[] = sprintf(
				/* translators: 1: required PHP version, 2: current PHP version */
				__( 'TutorPress Lite requires PHP %1$s or higher, but you are running %2$s.', 'tutorpress-lite' ),
				self::MINIMUM_PHP_VERSION,
				phpversion()
			);
		}

		if ( ! self::check_wordpress_requirement() ) {
			$errors[] = sprintf(
				/* translators: 1: required WordPress version, 2: current WordPress version */
				__( 'TutorPress Lite requires WordPress %1$s or higher, but you are running %2$s.', 'tutorpress-lite' ),
				self::MINIMUM_WP_VERSION,
				get_bloginfo( 'version' )
			);
		}

		return $errors;
	}

	/**
	 * Schedule deferred dependency checks.
	 *
	 * Tutor LMS must be checked on plugins_loaded so all plugins are loaded.
	 */
	public static function schedule_deferred_checks() {
		add_action( 'plugins_loaded', array( __CLASS__, 'check_tutor_lms_deferred' ), 1 );
	}

	/**
	 * Deferred Tutor LMS check (runs on plugins_loaded).
	 */
	public static function check_tutor_lms_deferred() {
		if ( ! self::check_tutor_lms_requirement() ) {
			add_action(
				'admin_notices',
				function () {
					self::show_admin_notice(
						__( 'Tutor LMS is required for TutorPress Lite to function.', 'tutorpress-lite' )
					);
				}
			);
		}
	}

	/**
	 * Check PHP version requirement.
	 *
	 * @return bool
	 */
	public static function check_php_requirement() {
		return version_compare( PHP_VERSION, self::MINIMUM_PHP_VERSION, '>=' );
	}

	/**
	 * Check WordPress version requirement.
	 *
	 * @return bool
	 */
	public static function check_wordpress_requirement() {
		return version_compare( get_bloginfo( 'version' ), self::MINIMUM_WP_VERSION, '>=' );
	}

	/**
	 * Check if Tutor LMS is available.
	 *
	 * @return bool
	 */
	public static function check_tutor_lms_requirement() {
		return function_exists( 'tutor' );
	}

	/**
	 * Display error messages as admin notices.
	 *
	 * @param string[] $errors Error messages.
	 */
	public static function display_errors( $errors ) {
		if ( empty( $errors ) ) {
			return;
		}

		foreach ( $errors as $error ) {
			add_action(
				'admin_notices',
				function () use ( $error ) {
					self::show_admin_notice( $error );
				}
			);
		}
	}

	/**
	 * Show a single admin notice.
	 *
	 * @param string $message The message to display.
	 */
	private static function show_admin_notice( $message ) {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		$screen        = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		$valid_screens = array( 'dashboard', 'plugins', 'toplevel_page_tutorpress-settings' );

		if ( $screen && ! in_array( $screen->id, $valid_screens, true ) ) {
			return;
		}

		printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			esc_html( $message )
		);
	}
}
