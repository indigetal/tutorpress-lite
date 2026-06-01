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
	 * Register admin hooks (Step 8–9: menu + co-instructor; Step 10+: toggles).
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_lessons_menu_item' ) );
		add_action( 'admin_menu', array( __CLASS__, 'reorder_tutor_submenus' ), 100 );
		add_action( 'init', array( __CLASS__, 'conditionally_hide_builder_button' ) );
		add_action( 'load-post.php', array( __CLASS__, 'fix_tutor_access_check' ), 5 );

		if ( tutorpress_get_setting( 'enable_admin_redirects', false ) ) {
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_overrides_on_courses_page' ) );
			add_action( 'tutor_admin_after_course_list_action', array( __CLASS__, 'enqueue_admin_overrides' ) );
		}
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

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Core post editor screen; post ID validated below.
		$post_id = isset( $_GET['post'] ) ? absint( wp_unslash( $_GET['post'] ) ) : 0;
		if ( ! $post_id ) {
			return;
		}
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

		wp_die( esc_html__( 'Permission Denied', 'tutorpress-lite' ) );
	}

	/**
	 * Conditionally hides the "Edit with Course Builder" button via CSS.
	 */
	public static function conditionally_hide_builder_button() {
		$remove_button = tutorpress_get_setting( 'remove_frontend_builder_button', '0' );
		if ( $remove_button && '1' === $remove_button ) {
			add_action( 'admin_head', array( __CLASS__, 'hide_builder_button_css' ) );
		}
	}

	/**
	 * Injects CSS to hide the frontend builder button from the Gutenberg editor header.
	 */
	public static function hide_builder_button_css() {
		echo '<style>#tutor-frontend-builder-trigger { display: none !important; }</style>';
	}

	/**
	 * Enqueue admin redirect script on the Tutor LMS courses admin page.
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 */
	public static function enqueue_admin_overrides_on_courses_page( $hook_suffix ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Admin screen routing only.
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		$is_tutor_page = ( 'tutor_page_tutor' === $hook_suffix || 'tutor' === $page );

		if ( ! $is_tutor_page ) {
			return;
		}

		self::enqueue_admin_overrides();
	}

	/**
	 * Enqueue admin redirect overrides script.
	 */
	public static function enqueue_admin_overrides() {
		if ( wp_script_is( 'tutorpress-lite-admin-redirects', 'enqueued' ) ) {
			return;
		}

		$script_path = TUTORPRESS_LITE_PATH . 'assets/js/admin-redirects.js';
		if ( ! file_exists( $script_path ) ) {
			return;
		}

		wp_enqueue_script(
			'tutorpress-lite-admin-redirects',
			TUTORPRESS_LITE_URL . 'assets/js/admin-redirects.js',
			array(),
			(string) filemtime( $script_path ),
			true
		);

		wp_localize_script(
			'tutorpress-lite-admin-redirects',
			'TutorPressData',
			array(
				'enableAdminRedirects' => tutorpress_get_setting( 'enable_admin_redirects', false ),
				'adminUrl'             => admin_url(),
			)
		);
	}
}
