<?php
/**
 * TutorPress Lite lesson sidebar tabs.
 *
 * @package TutorPress_Lite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tabbed lesson sidebar navigation and Tutor comment template blocking.
 */
class TutorPress_Lite_Sidebar_Tabs {

	/**
	 * Register sidebar tab hooks when the feature is enabled.
	 */
	public static function init() {
		if ( ! tutorpress_get_setting( 'enable_sidebar_tabs', false ) ) {
			return;
		}

		add_filter( 'tutor_lesson/single/lesson_sidebar', array( __CLASS__, 'modify_sidebar' ) );
		add_filter( 'tutor_get_template', array( __CLASS__, 'block_tutor_comments_templates' ), 10, 2 );
	}

	/**
	 * Modifies the Tutor LMS lesson sidebar to include tabbed navigation.
	 *
	 * @param string $sidebar_content Existing sidebar content.
	 * @return string Modified sidebar content with tabbed navigation.
	 */
	public static function modify_sidebar( $sidebar_content ) {
		ob_start();
		?>
		<div class="tutorpress-sidebar-tabs">
			<div class="tutor-sidebar-close-mobile">
				<button type="button" class="tutor-hide-course-single-sidebar tutor-iconic-btn" aria-label="<?php esc_attr_e( 'Close sidebar', 'tutorpress-lite' ); ?>">×</button>
			</div>
			<ul class="tutorpress-tabs">
				<li class="tutorpress-tab active" data-tab="course-content"><?php esc_html_e( 'Course Content', 'tutorpress-lite' ); ?></li>
				<li class="tutorpress-tab" data-tab="discussion"><?php esc_html_e( 'Discussion', 'tutorpress-lite' ); ?></li>
			</ul>
			<div class="tutorpress-tab-content" id="course-content">
				<?php
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Tutor LMS sidebar HTML from filter.
				echo $sidebar_content;
				?>
			</div>
			<div class="tutorpress-tab-content" id="discussion" style="display: none;">
				<?php comments_template(); ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Prevent Tutor LMS from loading its custom comment templates.
	 *
	 * @param string $template      Current template file.
	 * @param string $template_name Template being requested.
	 * @return string Modified template file (empty if blocked).
	 */
	public static function block_tutor_comments_templates( $template, $template_name ) {
		if ( 'single.lesson.comment' === $template_name || 'single.lesson.comments-loop' === $template_name ) {
			return '';
		}

		return $template;
	}
}
