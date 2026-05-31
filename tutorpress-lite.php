<?php
/**
 * Plugin Name:       TutorPress Lite for Tutor LMS
 * Plugin URI:        https://wordpress.org/plugins/tutorpress-lite/
 * Description:       Improves Tutor LMS with shared TutorPress settings, Gutenberg-friendly redirects, lesson discussion tabs, and co-instructor editing—without the premium Course Builder.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Indigetal WebCraft
 * Author URI:        https://indigetal.com/tutorpress
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       tutorpress-lite
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
 * Plugin activation callback.
 *
 * Capability grants are added in Step 7.
 */
function tutorpress_lite_activate() {
	flush_rewrite_rules();
	update_option( 'tutorpress_lite_version', TUTORPRESS_LITE_VERSION );
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
