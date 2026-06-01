<?php
/**
 * TutorPress Lite permissions helper.
 *
 * @package TutorPress_Lite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cross-cutting permission policies for co-instructor / collaborative editing.
 */
class TutorPress_Lite_Permissions {

	/**
	 * Check if user can access a course.
	 *
	 * @param int      $course_id Course ID.
	 * @param int|null $user_id   User ID (defaults to current user).
	 * @return bool Whether user can access course.
	 */
	public function can_user_access_course( int $course_id, ?int $user_id = null ): bool {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		if ( function_exists( 'tutor_utils' ) ) {
			return tutor_utils()->can_user_edit_course( $user_id, $course_id );
		}

		return current_user_can( 'edit_post', $course_id );
	}

	/**
	 * Check if user can edit course settings.
	 *
	 * @param int      $course_id Course ID.
	 * @param int|null $user_id   User ID (defaults to current user).
	 * @return bool Whether user can edit course settings.
	 */
	public function can_user_edit_course_settings( int $course_id, ?int $user_id = null ): bool {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		if ( function_exists( 'tutor_utils' ) ) {
			$can_edit = tutor_utils()->can_user_edit_course( $user_id, $course_id );

			return apply_filters( 'tutorpress_can_edit_course_settings', $can_edit, $course_id, $user_id );
		}

		$can_edit = current_user_can( 'manage_options' ) || current_user_can( 'edit_post', $course_id );
		return apply_filters( 'tutorpress_can_edit_course_settings', $can_edit, $course_id, $user_id );
	}

	/**
	 * Check if user can access a lesson.
	 *
	 * @param int      $lesson_id Lesson ID.
	 * @param int|null $user_id   User ID (defaults to current user).
	 * @return bool Whether user can access lesson.
	 */
	public function can_user_access_lesson( int $lesson_id, ?int $user_id = null ): bool {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		if ( current_user_can( 'edit_post', $lesson_id ) ) {
			return true;
		}

		$course_id = get_post_meta( $lesson_id, '_tutor_course_id_for_lesson', true );
		if ( $course_id ) {
			return $this->can_user_access_course( (int) $course_id, $user_id );
		}

		return false;
	}

	/**
	 * Check if user can manage enrollments.
	 *
	 * @param int|null $course_id Course ID (optional).
	 * @param int|null $user_id   User ID (defaults to current user).
	 * @return bool Whether user can manage enrollments.
	 */
	public function can_manage_enrollments( ?int $course_id = null, ?int $user_id = null ): bool {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		if ( $course_id && current_user_can( 'tutor_instructor' ) ) {
			return current_user_can( 'edit_post', $course_id );
		}

		if ( current_user_can( 'tutor_instructor' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * REST/builder feature access — not used in Lite (full plugin feature flags).
	 *
	 * @param string   $feature Feature name.
	 * @param int|null $user_id User ID (defaults to current user).
	 * @return bool Always false in Lite.
	 */
	public function can_user_access_feature( string $feature, ?int $user_id = null ): bool {
		unset( $feature, $user_id );
		return false;
	}

	/**
	 * Check if user can edit course content (lesson, assignment, quiz, etc).
	 *
	 * @param int      $post_id Post ID.
	 * @param int|null $user_id User ID (defaults to current user).
	 * @return bool Whether user can edit this content.
	 */
	public function can_user_edit_course_content( int $post_id, ?int $user_id = null ): bool {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		$course_id = $this->get_course_id_for_content( $post_id );
		if ( ! $course_id ) {
			return false;
		}

		return $this->is_user_course_instructor( $user_id, $course_id );
	}

	/**
	 * Get course ID for a piece of content (lesson, assignment, quiz).
	 *
	 * @param int $post_id Post ID.
	 * @return int Course ID or 0 if not found.
	 */
	private function get_course_id_for_content( int $post_id ): int {
		if ( function_exists( 'tutor_utils' ) ) {
			$course_id = tutor_utils()->get_course_id_by( 'lesson', $post_id );
			if ( $course_id ) {
				return (int) $course_id;
			}
		}

		$meta_course_id = get_post_meta( $post_id, '_tutor_course_id_for_lesson', true );
		if ( $meta_course_id ) {
			return (int) $meta_course_id;
		}

		return 0;
	}

	/**
	 * Check if user is an instructor for a course (author or co-instructor).
	 *
	 * @param int $user_id   User ID.
	 * @param int $course_id Course ID.
	 * @return bool
	 */
	private function is_user_course_instructor( int $user_id, int $course_id ): bool {
		$course_author = get_post_field( 'post_author', $course_id );
		if ( $course_author && (int) $course_author === $user_id ) {
			return true;
		}

		$co_instructors = get_post_meta( $course_id, '_tutor_course_instructors', true );
		if ( is_array( $co_instructors ) && in_array( $user_id, $co_instructors, true ) ) {
			return true;
		}

		$user_meta_entries = get_user_meta( $user_id, '_tutor_instructor_course_id', false );
		if ( ! empty( $user_meta_entries ) && in_array( (string) $course_id, array_map( 'strval', $user_meta_entries ), true ) ) {
			return true;
		}

		return false;
	}
}
