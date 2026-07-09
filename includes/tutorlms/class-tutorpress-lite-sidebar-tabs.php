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
	 * Lite Discussion template slug used by Tutor LMS nav items.
	 */
	private const DISCUSSION_TEMPLATE = 'tutorpress-lite.learning-area.lesson.discussion';

	/**
	 * Slash-converted Lite Discussion template path used by Tutor LMS.
	 */
	private const DISCUSSION_TEMPLATE_PATH = 'tutorpress-lite/learning-area/lesson/discussion';

	/**
	 * Tutor LMS native lesson comment templates to suppress.
	 */
	private const TUTOR_COMMENTS_TEMPLATES = array(
		'single.lesson.parts.comments',
		'single.lesson.comment',
		'single.lesson.comments-loop',
		'learning-area.lesson.comments',
	);

	/**
	 * Register sidebar tab hooks when the feature is enabled.
	 */
	public static function init() {
		if ( ! tutorpress_get_setting( 'enable_sidebar_tabs', false ) ) {
			return;
		}

		add_filter( 'tutor_lesson/single/lesson_sidebar', array( __CLASS__, 'modify_sidebar' ) );
		add_filter( 'tutor_lesson_single_nav_items', array( __CLASS__, 'filter_nav_items' ) );
		add_filter( 'tutor_lesson_single_nav_contents', array( __CLASS__, 'filter_nav_contents' ) );
		add_filter( 'should_tutor_load_template', array( __CLASS__, 'should_load_tutor_template' ), 10, 3 );
		add_filter( 'tutor_get_template_path', array( __CLASS__, 'filter_template_path' ), 10, 2 );
		add_filter( 'tutor_not_found_template_warning_msg', array( __CLASS__, 'suppress_discussion_template_warning' ) );
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
				<button type="button" class="tutor-hide-course-single-sidebar tutor-iconic-btn" aria-label="<?php esc_attr_e( 'Close sidebar', 'indigetal-course-workflow-enhancements-for-tutor-lms' ); ?>">×</button>
			</div>
			<ul class="tutorpress-tabs">
				<li class="tutorpress-tab active" data-tab="course-content"><?php esc_html_e( 'Course Content', 'indigetal-course-workflow-enhancements-for-tutor-lms' ); ?></li>
				<li class="tutorpress-tab" data-tab="discussion"><?php esc_html_e( 'Discussion', 'indigetal-course-workflow-enhancements-for-tutor-lms' ); ?></li>
			</ul>
			<div class="tutorpress-tab-content" id="course-content">
				<?php
				echo wp_kses( $sidebar_content, self::get_allowed_sidebar_html() );
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
	 * Gets Tutor LMS's configured lesson learning mode.
	 *
	 * @return string Tutor LMS learning mode.
	 */
	private static function get_learning_mode() {
		if ( function_exists( 'tutor_utils' ) && is_object( tutor_utils() ) && method_exists( tutor_utils(), 'get_option' ) ) {
			return (string) tutor_utils()->get_option( 'learning_mode' );
		}

		return '';
	}

	/**
	 * Checks whether Tutor LMS is using a learning-area mode.
	 *
	 * @return bool True when modern or kids learning mode is active.
	 */
	private static function is_learning_area_mode() {
		return in_array( self::get_learning_mode(), array( 'modern', 'kids' ), true );
	}

	/**
	 * Filters Tutor LMS lesson nav items.
	 *
	 * @param array<mixed> $nav_items Tutor LMS lesson nav items.
	 * @return array<mixed> Filtered nav items.
	 */
	public static function filter_nav_items( $nav_items ) {
		foreach ( $nav_items as $key => $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$template         = isset( $item['template'] ) ? $item['template'] : '';
			$is_comments_item = 'comments' === $key
				|| ( isset( $item['id'] ) && 'comments' === $item['id'] )
				|| ( isset( $item['value'] ) && 'comments' === $item['value'] )
				|| in_array( $template, self::TUTOR_COMMENTS_TEMPLATES, true );

			if ( ! $is_comments_item ) {
				continue;
			}

			if ( self::is_learning_area_mode() ) {
				$nav_items[ $key ] = array_merge(
					$item,
					array(
						'id'       => 'comments',
						'label'    => __( 'Discussion', 'indigetal-course-workflow-enhancements-for-tutor-lms' ),
						'template' => self::DISCUSSION_TEMPLATE,
					)
				);
				continue;
			}

			unset( $nav_items[ $key ] );
		}

		return $nav_items;
	}

	/**
	 * Filters Tutor LMS lesson nav contents.
	 *
	 * @param array<mixed> $nav_contents Tutor LMS lesson nav contents.
	 * @return array<mixed> Filtered nav contents.
	 */
	public static function filter_nav_contents( $nav_contents ) {
		foreach ( $nav_contents as $key => $content ) {
			if ( ! is_array( $content ) ) {
				continue;
			}

			$template_path       = isset( $content['template_path'] ) ? $content['template_path'] : '';
			$is_comments_content = 'comments' === $key
				|| ( isset( $content['value'] ) && 'comments' === $content['value'] )
				|| 'single.lesson.parts.comments' === $template_path;

			if ( $is_comments_content ) {
				unset( $nav_contents[ $key ] );
			}
		}

		return $nav_contents;
	}

	/**
	 * Determines whether Tutor LMS should load a requested template.
	 *
	 * @param bool   $should_load Whether Tutor LMS should load the template.
	 * @param string $template    Template slug being requested.
	 * @param array  $variables   Template variables.
	 * @return bool Whether Tutor LMS should load the template.
	 */
	public static function should_load_tutor_template( $should_load, $template, $variables ) {
		if ( in_array( $template, self::TUTOR_COMMENTS_TEMPLATES, true ) ) {
			return false;
		}

		return $should_load;
	}

	/**
	 * Filters Tutor LMS resolved template paths.
	 *
	 * @param string $template_path Resolved template path.
	 * @param string $template      Template slug being requested.
	 * @return string Filtered template path.
	 */
	public static function filter_template_path( $template_path, $template ) {
		if ( ! in_array( $template, array( self::DISCUSSION_TEMPLATE, self::DISCUSSION_TEMPLATE_PATH ), true ) ) {
			return $template_path;
		}

		$plugin_path = defined( 'TUTORPRESS_LITE_PATH' ) ? TUTORPRESS_LITE_PATH : dirname( __DIR__, 2 ) . '/';

		return $plugin_path . 'templates/tutorpress-lite/learning-area/lesson/discussion.php';
	}

	/**
	 * Suppresses Tutor's premature missing-template warning for the routed Lite template.
	 *
	 * @param string $warning_msg Tutor LMS missing-template warning.
	 * @return string Filtered warning.
	 */
	public static function suppress_discussion_template_warning( $warning_msg ) {
		if ( false !== strpos( $warning_msg, self::DISCUSSION_TEMPLATE_PATH . '.php' ) ) {
			return '';
		}

		return $warning_msg;
	}

	/**
	 * Get allowed HTML for Tutor LMS sidebar markup.
	 *
	 * @return array<string, array<string, bool|string[]>>
	 */
	private static function get_allowed_sidebar_html() {
		$allowed_html = wp_kses_allowed_html( 'post' );

		$global_attributes = array(
			'aria-*' => true,
			'class'  => true,
			'data-*' => true,
			'id'     => true,
			'role'   => true,
			'style'  => true,
			'title'  => true,
		);

		foreach ( $allowed_html as $tag => $attributes ) {
			$allowed_html[ $tag ] = array_merge( $attributes, $global_attributes );
		}

		$allowed_html['button'] = array_merge(
			$global_attributes,
			array(
				'disabled' => true,
				'name'     => true,
				'type'     => true,
				'value'    => true,
			)
		);

		$allowed_html['input'] = array_merge(
			$global_attributes,
			array(
				'checked'     => true,
				'disabled'    => true,
				'name'        => true,
				'placeholder' => true,
				'type'        => true,
				'value'       => true,
			)
		);

		$allowed_html['svg'] = array_merge(
			$global_attributes,
			array(
				'fill'        => true,
				'height'      => true,
				'viewbox'     => true,
				'xmlns'       => true,
				'width'       => true,
			)
		);

		$allowed_html['path'] = array(
			'd'    => true,
			'fill' => true,
		);

		return $allowed_html;
	}
}
