<?php
/**
 * TutorPress Lite admin customizations.
 *
 * @package TutorPress_Lite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin menu and UX adjustments for Tutor LMS.
 */
class TutorPress_Lite_Admin {

	/**
	 * Register admin hooks (Step 8: lessons menu; Step 9+ adds co-instructor and toggles).
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_lessons_menu_item' ) );
		add_action( 'admin_menu', array( __CLASS__, 'reorder_tutor_submenus' ), 100 );
	}

	/**
	 * Add "Lessons" menu item under Tutor LMS in the WordPress admin menu.
	 */
	public static function add_lessons_menu_item() {
		add_submenu_page(
			'tutor',
			__( 'Lessons', 'tutorpress-lite' ),
			__( 'Lessons', 'tutorpress-lite' ),
			'edit_tutor_lesson',
			'edit.php?post_type=lesson'
		);
	}

	/**
	 * Move "Lessons" submenu below "Courses".
	 */
	public static function reorder_tutor_submenus() {
		global $submenu;

		if ( ! isset( $submenu['tutor'] ) ) {
			return;
		}

		foreach ( $submenu['tutor'] as $key => $item ) {
			if ( 'edit.php?post_type=lesson' === $item[2] ) {
				$lesson_menu = $submenu['tutor'][ $key ];
				unset( $submenu['tutor'][ $key ] );
				array_splice( $submenu['tutor'], 1, 0, array( $lesson_menu ) );
				break;
			}
		}
	}
}
