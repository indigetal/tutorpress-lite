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
		add_action( 'load-post.php', array( __CLASS__, 'fix_tutor_access_check' ), 5 );
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

	/**
	 * Fix Tutor LMS's broken lesson access check for co-instructors.
	 *
	 * Tutor LMS's Admin::check_if_current_users_post() checks if user is
	 * co-instructor of the LESSON itself, but should check the PARENT COURSE.
	 *
	 * @since 2.0.11
	 */
	public static function fix_tutor_access_check() {
		global $wp_filter;
		if ( isset( $wp_filter['load-post.php'] ) ) {
			foreach ( $wp_filter['load-post.php']->callbacks as $priority => $callbacks ) {
				foreach ( $callbacks as $key => $callback ) {
					if ( is_array( $callback['function'] ) &&
						isset( $callback['function'][0] ) &&
						is_object( $callback['function'][0] ) &&
						get_class( $callback['function'][0] ) === 'TUTOR\Admin' &&
						$callback['function'][1] === 'check_if_current_users_post' ) {
						remove_action( 'load-post.php', $callback['function'], $priority );
					}
				}
			}
		}

		if ( ! function_exists( 'tutor' ) ) {
			return;
		}

		if ( current_user_can( 'administrator' ) || ! current_user_can( tutor()->instructor_role ) ) {
			return;
		}

		if ( empty( $_GET['post'] ) ) {
			return;
		}

		$post_id = (int) $_GET['post'];
		$post    = get_post( $post_id );

		if ( ! $post ) {
			return;
		}

		$tutor_post_types = array(
			tutor()->course_post_type,
			tutor()->lesson_post_type,
			tutor()->assignment_post_type,
		);
		$current_user = get_current_user_id();

		if ( ! in_array( $post->post_type, $tutor_post_types, true ) || (int) $post->post_author === $current_user ) {
			return;
		}

		if ( in_array( $post->post_type, array( tutor()->lesson_post_type, tutor()->assignment_post_type ), true ) ) {
			$course_id = get_post_meta( $post_id, '_tutor_course_id_for_lesson', true );
			if ( ! $course_id && function_exists( 'tutor_utils' ) ) {
				$course_id = tutor_utils()->get_course_id_by( 'lesson', $post_id );
			}

			if ( $course_id && function_exists( 'tutor_utils' ) ) {
				if ( tutor_utils()->can_user_edit_course( $current_user, $course_id ) ) {
					return;
				}
			}
		} elseif ( $post->post_type === tutor()->course_post_type ) {
			if ( function_exists( 'tutor_utils' ) && tutor_utils()->can_user_edit_course( $current_user, $post_id ) ) {
				return;
			}
		}

		wp_die( esc_html__( 'Permission Denied', 'tutor' ) );
	}
}
