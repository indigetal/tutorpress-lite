<?php
/**
 * TutorPress Lite capability fixes.
 *
 * Grants capabilities Tutor LMS omits for assignments and instructor REST/editor access.
 *
 * @package TutorPress_Lite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Capability grants for Tutor LMS gaps.
 */
class TutorPress_Lite_Capability_Fixes {

	/**
	 * Grant capabilities that Tutor LMS forgot to add.
	 */
	public static function grant_missing_capabilities() {
		if ( ! function_exists( 'tutor' ) ) {
			return;
		}

		self::grant_assignment_capabilities();
		self::grant_instructor_rest_api_capabilities();
	}

	/**
	 * Grant assignment capabilities to admin and instructor roles.
	 */
	private static function grant_assignment_capabilities() {
		$assignment_caps = array(
			'edit_tutor_assignment',
			'read_tutor_assignment',
			'delete_tutor_assignment',
			'edit_tutor_assignments',
			'edit_others_tutor_assignments',
			'edit_published_tutor_assignments',
			'edit_private_tutor_assignments',
			'publish_tutor_assignments',
			'read_private_tutor_assignments',
			'delete_tutor_assignments',
			'delete_others_tutor_assignments',
			'delete_published_tutor_assignments',
			'delete_private_tutor_assignments',
		);

		$admin = get_role( 'administrator' );
		if ( $admin ) {
			foreach ( $assignment_caps as $cap ) {
				if ( ! $admin->has_cap( $cap ) ) {
					$admin->add_cap( $cap );
				}
			}
		}

		$instructor = get_role( tutor()->instructor_role );
		if ( $instructor ) {
			foreach ( $assignment_caps as $cap ) {
				if ( ! $instructor->has_cap( $cap ) ) {
					$instructor->add_cap( $cap );
				}
			}
		}
	}

	/**
	 * Grant edit_posts and read to instructors for REST and block editor access.
	 */
	private static function grant_instructor_rest_api_capabilities() {
		$instructor = get_role( tutor()->instructor_role );
		if ( ! $instructor ) {
			return;
		}

		if ( ! $instructor->has_cap( 'edit_posts' ) ) {
			$instructor->add_cap( 'edit_posts' );
		}

		if ( ! $instructor->has_cap( 'read' ) ) {
			$instructor->add_cap( 'read' );
		}
	}
}
