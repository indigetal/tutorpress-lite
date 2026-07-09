<?php
/**
 * TutorPress Lite frontend dashboard overrides.
 *
 * @package TutorPress_Lite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Instructor dashboard navigation and frontend course editing redirects.
 */
class TutorPress_Lite_Dashboard {

	/**
	 * Register dashboard filters when settings are enabled.
	 */
	public static function init() {
		if ( tutorpress_get_setting( 'enable_extra_dashboard_links', false ) ) {
			add_filter( 'tutor_dashboard/instructor_nav_items', array( __CLASS__, 'add_extra_dashboard_links' ) );
		}

		if ( tutorpress_get_setting( 'enable_dashboard_redirects', false ) ) {
			add_filter( 'tutor_dashboard_url', array( __CLASS__, 'override_dashboard_edit_buttons' ), 10, 2 );
			add_filter( 'tutor_dashboard_course_list_edit_link', array( __CLASS__, 'override_course_edit_links' ), 10, 2 );
		}
	}

	/**
	 * Add Media Library and H5P links to the instructor dashboard nav.
	 *
	 * @param array<int, array<string, string>> $nav_items Existing nav items.
	 * @return array<int, array<string, string>>
	 */
	public static function add_extra_dashboard_links( $nav_items ) {
		$is_tutor_lms_4_or_later = defined( 'TUTOR_VERSION' ) && version_compare( TUTOR_VERSION, '4.0.0', '>=' );
		$media_library_icons     = $is_tutor_lms_4_or_later
			? array( 'icon' => 'store-image', 'active_icon' => 'store-image' )
			: array( 'icon' => 'tutor-icon-images' );
		$interactive_content_icons = $is_tutor_lms_4_or_later
			? array( 'icon' => 'quiz-h5p', 'active_icon' => 'quiz-h5p' )
			: array( 'icon' => 'tutor-icon-puzzle' );

		$extra_links = array(
			array_merge(
				array(
				'title' => __( 'Media Library', 'indigetal-course-workflow-enhancements-for-tutor-lms' ),
				'url'   => admin_url( 'upload.php' ),
				),
				$media_library_icons
			),
			array_merge(
				array(
				'title' => __( 'Interactive Content', 'indigetal-course-workflow-enhancements-for-tutor-lms' ),
				'url'   => admin_url( 'admin.php?page=h5p' ),
				),
				$interactive_content_icons
			),
		);

		return array_merge( $nav_items, $extra_links );
	}

	/**
	 * Redirect dashboard "Edit Course" / "Edit Bundle" URLs to Gutenberg.
	 *
	 * @param string $url     Dashboard URL.
	 * @param string $sub_url Sub-page path/query for the dashboard action.
	 * @return string
	 */
	public static function override_dashboard_edit_buttons( $url, $sub_url ) {
		if ( strpos( $sub_url, 'create-course?course_id=' ) !== false ) {
			parse_str( (string) wp_parse_url( $sub_url, PHP_URL_QUERY ), $query );
			if ( isset( $query['course_id'] ) ) {
				return admin_url( 'post.php?post=' . intval( $query['course_id'] ) . '&action=edit' );
			}
		}

		if ( strpos( $sub_url, 'create-bundle?action=edit&id=' ) !== false ) {
			parse_str( (string) wp_parse_url( $sub_url, PHP_URL_QUERY ), $query );
			if ( isset( $query['id'] ) ) {
				return admin_url( 'post.php?post=' . intval( $query['id'] ) . '&action=edit' );
			}
		}

		return $url;
	}

	/**
	 * Override course edit links in dashboard course cards.
	 *
	 * @param string  $url  Original edit URL.
	 * @param WP_Post $post Course post object.
	 * @return string
	 */
	public static function override_course_edit_links( $url, $post ) {
		unset( $url );

		return admin_url( 'post.php?post=' . intval( $post->ID ) . '&action=edit' );
	}
}
