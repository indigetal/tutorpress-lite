<?php
/**
 * Plugin Name:       Indigetal Course Workflow Enhancements for Tutor LMS
 * Plugin URI:        https://wordpress.org/plugins/indigetal-course-workflow-enhancements-for-tutor-lms/
 * Description:       Improves Tutor LMS with shared TutorPress settings, Gutenberg-friendly redirects, lesson discussion tabs, and co-instructor editing—without the premium Course Builder.
 * Version:           1.0.4
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Requires Plugins:  tutor
 * Author:            Indigetal WebCraft
 * Author URI:        https://indigetal.com/tutorpress
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       indigetal-course-workflow-enhancements-for-tutor-lms
 * Domain Path:       /languages
 *
 * @package TutorPress_Lite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tutorpress_lite_plugin_data = get_file_data(
	__FILE__,
	array(
		'Version' => 'Version',
	)
);
define( 'TUTORPRESS_LITE_VERSION', $tutorpress_lite_plugin_data['Version'] );
define( 'TUTORPRESS_LITE_PATH', plugin_dir_path( __FILE__ ) );
define( 'TUTORPRESS_LITE_URL', plugin_dir_url( __FILE__ ) );
define( 'TUTORPRESS_LITE_FILE', __FILE__ );

/**
 * Full TutorPress product page (paid plugin; not on WordPress.org).
 */
if ( ! defined( 'TUTORPRESS_FULL_PRODUCT_URL' ) ) {
	define( 'TUTORPRESS_FULL_PRODUCT_URL', 'https://indigetal.com/tutorpress' );
}

/**
 * Plugin activation callback.
 */
function tutorpress_lite_activate() {
	flush_rewrite_rules();
	update_option( 'tutorpress_lite_version', TUTORPRESS_LITE_VERSION );

	require_once TUTORPRESS_LITE_PATH . 'includes/tutorlms/class-tutorpress-lite-capability-fixes.php';
	if ( function_exists( 'tutor' ) ) {
		TutorPress_Lite_Capability_Fixes::grant_missing_capabilities();
		update_option( 'tutorpress_lite_capability_version', TUTORPRESS_LITE_VERSION );
	}
}

/**
 * Plugin deactivation callback.
 */
function tutorpress_lite_deactivate() {
	flush_rewrite_rules();
}

register_activation_hook( __FILE__, 'tutorpress_lite_activate' );
register_deactivation_hook( __FILE__, 'tutorpress_lite_deactivate' );

require_once TUTORPRESS_LITE_PATH . 'includes/class-tutorpress-lite.php';

TutorPress_Lite_Main::instance(
	array(
		'main_file' => __FILE__,
		'version'   => TUTORPRESS_LITE_VERSION,
	)
);

require_once TUTORPRESS_LITE_PATH . 'includes/tutorlms/class-tutorpress-lite-capability-fixes.php';

/**
 * Capability migration: runs once per Lite version (activation + upgrades).
 *
 * WordPress does not fire register_activation_hook on plugin updates.
 */
add_action(
	'init',
	function () {
		$cap_version = get_option( 'tutorpress_lite_capability_version', '0' );
		if ( version_compare( $cap_version, TUTORPRESS_LITE_VERSION, '<' ) ) {
			if ( function_exists( 'tutor' ) && class_exists( 'TutorPress_Lite_Capability_Fixes' ) ) {
				TutorPress_Lite_Capability_Fixes::grant_missing_capabilities();
				update_option( 'tutorpress_lite_capability_version', TUTORPRESS_LITE_VERSION );
			}
		}
	},
	20
);

/**
 * Enable WordPress admin UI and REST support for Tutor assignment post type.
 */
add_action(
	'init',
	function () {
		if ( ! post_type_exists( 'tutor_assignments' ) ) {
			return;
		}

		$assignment_post_type = get_post_type_object( 'tutor_assignments' );
		if ( ! $assignment_post_type ) {
			return;
		}

		$assignment_post_type->show_ui             = true;
		$assignment_post_type->show_in_menu        = false;
		$assignment_post_type->public              = true;
		$assignment_post_type->publicly_queryable  = true;
		$assignment_post_type->map_meta_cap        = true;

		$assignment_post_type->cap->edit_published_posts  = 'edit_published_tutor_assignments';
		$assignment_post_type->cap->delete_published_posts = 'delete_published_tutor_assignments';
		$assignment_post_type->cap->delete_others_posts   = 'delete_others_tutor_assignments';
		$assignment_post_type->cap->delete_private_posts  = 'delete_private_tutor_assignments';
		$assignment_post_type->cap->edit_private_posts    = 'edit_private_tutor_assignments';

		$enable_gutenberg = (bool) tutor_utils()->get_option( 'enable_gutenberg_course_edit' );
		if ( $enable_gutenberg ) {
			$assignment_post_type->show_in_rest = true;
		}

		if ( ! $assignment_post_type->show_in_rest ) {
			$assignment_post_type->show_in_rest = true;
		}

		if ( ! post_type_supports( 'tutor_assignments', 'editor' ) ) {
			add_post_type_support( 'tutor_assignments', 'editor' );
		}

		if ( ! post_type_supports( 'tutor_assignments', 'custom-fields' ) ) {
			add_post_type_support( 'tutor_assignments', 'custom-fields' );
		}
	},
	20
);
