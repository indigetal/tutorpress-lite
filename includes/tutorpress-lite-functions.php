<?php
/**
 * TutorPress Lite global functions (shared helpers with full TutorPress).
 *
 * @package TutorPress_Lite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get a TutorPress setting value from the shared options array.
 *
 * No Freemius gate in Lite; full TutorPress may define this first when both are active.
 *
 * @param string $key     Setting key.
 * @param mixed  $default Default when the key is absent.
 * @return mixed
 */
if ( ! function_exists( 'tutorpress_get_setting' ) ) {
	function tutorpress_get_setting( $key, $default = null ) {
		$opts = get_option( 'tutorpress_settings', get_option( 'tutorpress_options', array() ) );
		if ( ! is_array( $opts ) ) {
			$opts = array();
		}

		return $opts[ $key ] ?? $default;
	}
}
