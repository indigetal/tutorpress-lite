<?php
/**
 * Uninstall TutorPress Lite for Tutor LMS.
 *
 * Removes Lite-specific options only. Shared TutorPress settings are preserved
 * so sites can upgrade to full TutorPress without reconfiguring toggles.
 *
 * @package TutorPress_Lite
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Preserve tutorpress_settings (and legacy tutorpress_options) for upgrade path.
delete_option( 'tutorpress_lite_version' );
delete_option( 'tutorpress_lite_capability_version' );
