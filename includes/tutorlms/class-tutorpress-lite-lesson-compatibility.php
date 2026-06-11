<?php
/**
 * TutorPress Lite lesson compatibility fixes.
 *
 * @package TutorPress_Lite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Preserves Tutor LMS lesson media meta during Gutenberg/backend saves.
 */
class TutorPress_Lite_Lesson_Compatibility {

	/**
	 * Lesson thumbnails captured before omitted-image REST saves.
	 *
	 * @var array<int,int>
	 */
	private static $omitted_image_rest_thumbnail_snapshots = array();

	/**
	 * Lesson thumbnails captured before Gutenberg meta-box-loader saves.
	 *
	 * @var array<int,int>
	 */
	private static $meta_box_loader_thumbnail_snapshots = array();

	/**
	 * Lesson attachments captured before omitted-attachment REST saves.
	 *
	 * @var array<int,array<int>>
	 */
	private static $omitted_attachment_rest_snapshots = array();

	/**
	 * Lesson attachments captured before Gutenberg meta-box-loader saves.
	 *
	 * @var array<int,array<int>>
	 */
	private static $meta_box_loader_attachment_snapshots = array();

	/**
	 * Register compatibility hooks.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'ensure_lesson_featured_image_support' ), 20 );
		add_filter( 'rest_pre_insert_lesson', array( __CLASS__, 'capture_omitted_rest_snapshots' ), 10, 2 );
		add_action( 'rest_after_insert_lesson', array( __CLASS__, 'restore_omitted_rest_snapshots' ), 10, 3 );
		add_action( 'save_post_lesson', array( __CLASS__, 'capture_meta_box_loader_snapshots' ), 1, 3 );
		add_action( 'save_post_lesson', array( __CLASS__, 'restore_meta_box_loader_snapshots' ), 1001, 3 );
	}

	/**
	 * Ensure Tutor LMS lessons support WordPress featured images.
	 */
	public static function ensure_lesson_featured_image_support() {
		if ( post_type_exists( 'lesson' ) ) {
			add_post_type_support( 'lesson', 'thumbnail' );
			add_theme_support( 'post-thumbnails' );
		}
	}

	/**
	 * Capture existing media meta before an omitted-field REST lesson update.
	 *
	 * @param stdClass|WP_Post $prepared_post Prepared post object.
	 * @param WP_REST_Request  $request       REST request.
	 * @return stdClass|WP_Post
	 */
	public static function capture_omitted_rest_snapshots( $prepared_post, $request ) {
		$post_id = isset( $prepared_post->ID ) ? absint( $prepared_post->ID ) : 0;
		if ( $post_id > 0 ) {
			unset(
				self::$omitted_image_rest_thumbnail_snapshots[ $post_id ],
				self::$omitted_attachment_rest_snapshots[ $post_id ]
			);
		}

		if ( self::is_omitted_image_core_rest_lesson_update( $prepared_post, $request ) ) {
			$thumbnail_id = absint( get_post_thumbnail_id( $post_id ) );
			if ( self::is_valid_image_attachment( $thumbnail_id ) ) {
				self::$omitted_image_rest_thumbnail_snapshots[ $post_id ] = $thumbnail_id;
			}
		}

		if ( self::is_omitted_attachment_core_rest_lesson_update( $prepared_post, $request ) ) {
			$attachment_ids = self::get_existing_valid_tutor_attachment_ids( $post_id );
			if ( ! empty( $attachment_ids ) ) {
				self::$omitted_attachment_rest_snapshots[ $post_id ] = $attachment_ids;
			}
		}

		return $prepared_post;
	}

	/**
	 * Restore omitted media meta only when Tutor LMS deleted existing values.
	 *
	 * @param WP_Post         $post     Inserted or updated post object.
	 * @param WP_REST_Request $request  REST request.
	 * @param bool            $creating Whether this was a create request.
	 */
	public static function restore_omitted_rest_snapshots( $post, $request, $creating ) {
		$post_id = isset( $post->ID ) ? absint( $post->ID ) : 0;
		if ( ! $post_id ) {
			return;
		}

		$thumbnail_id   = self::$omitted_image_rest_thumbnail_snapshots[ $post_id ] ?? 0;
		$attachment_ids = self::$omitted_attachment_rest_snapshots[ $post_id ] ?? array();
		unset(
			self::$omitted_image_rest_thumbnail_snapshots[ $post_id ],
			self::$omitted_attachment_rest_snapshots[ $post_id ]
		);

		if ( $creating ) {
			return;
		}

		if (
			$thumbnail_id
			&& self::is_omitted_image_core_rest_lesson_update( $post, $request )
			&& ! absint( get_post_thumbnail_id( $post_id ) )
			&& self::is_valid_image_attachment( $thumbnail_id )
		) {
			set_post_thumbnail( $post_id, $thumbnail_id );
		}

		if (
			! empty( $attachment_ids )
			&& self::is_omitted_attachment_core_rest_lesson_update( $post, $request )
			&& empty( get_post_meta( $post_id, '_tutor_attachments', true ) )
		) {
			update_post_meta( $post_id, '_tutor_attachments', $attachment_ids );
		}
	}

	/**
	 * Capture existing media meta before Gutenberg's meta-box-loader save.
	 *
	 * @param int     $post_id Lesson post ID.
	 * @param WP_Post $post    Lesson post object.
	 * @param bool    $update  Whether this is an existing post update.
	 */
	public static function capture_meta_box_loader_snapshots( $post_id, $post, $update ) {
		$post_id = absint( $post_id );
		if ( $post_id > 0 ) {
			unset(
				self::$meta_box_loader_thumbnail_snapshots[ $post_id ],
				self::$meta_box_loader_attachment_snapshots[ $post_id ]
			);
		}

		if ( ! self::is_gutenberg_meta_box_loader_lesson_save( $post_id, $post, $update ) ) {
			return;
		}

		if ( ! self::php_request_has_thumbnail_id() && ! self::php_request_has_core_featured_image_field() ) {
			$thumbnail_id = absint( get_post_thumbnail_id( $post_id ) );
			if ( self::is_valid_image_attachment( $thumbnail_id ) ) {
				self::$meta_box_loader_thumbnail_snapshots[ $post_id ] = $thumbnail_id;
			}
		}

		if ( ! self::php_request_has_tutor_attachments_field() ) {
			$attachment_ids = self::get_existing_valid_tutor_attachment_ids( $post_id );
			if ( ! empty( $attachment_ids ) ) {
				self::$meta_box_loader_attachment_snapshots[ $post_id ] = $attachment_ids;
			}
		}
	}

	/**
	 * Restore media meta deleted by Tutor LMS during Gutenberg's meta-box-loader save.
	 *
	 * @param int     $post_id Lesson post ID.
	 * @param WP_Post $post    Lesson post object.
	 * @param bool    $update  Whether this is an existing post update.
	 */
	public static function restore_meta_box_loader_snapshots( $post_id, $post, $update ) {
		$post_id = absint( $post_id );
		if ( ! $post_id ) {
			return;
		}

		$thumbnail_id   = self::$meta_box_loader_thumbnail_snapshots[ $post_id ] ?? 0;
		$attachment_ids = self::$meta_box_loader_attachment_snapshots[ $post_id ] ?? array();
		unset(
			self::$meta_box_loader_thumbnail_snapshots[ $post_id ],
			self::$meta_box_loader_attachment_snapshots[ $post_id ]
		);

		if ( ! self::is_gutenberg_meta_box_loader_lesson_save( $post_id, $post, $update ) ) {
			return;
		}

		if (
			$thumbnail_id
			&& ! absint( get_post_thumbnail_id( $post_id ) )
			&& self::is_valid_image_attachment( $thumbnail_id )
		) {
			set_post_thumbnail( $post_id, $thumbnail_id );
		}

		if ( ! empty( $attachment_ids ) && empty( get_post_meta( $post_id, '_tutor_attachments', true ) ) ) {
			update_post_meta( $post_id, '_tutor_attachments', $attachment_ids );
		}
	}

	/**
	 * Whether a request is an existing lesson REST update that omitted image fields.
	 *
	 * @param stdClass|WP_Post $prepared_post Prepared post object.
	 * @param mixed            $request       Possible REST request.
	 * @return bool
	 */
	private static function is_omitted_image_core_rest_lesson_update( $prepared_post, $request ) {
		if ( ! self::is_core_rest_lesson_update( $prepared_post, $request ) ) {
			return false;
		}

		if ( $request->has_param( 'featured_media' ) || $request->has_param( '_thumbnail_id' ) || $request->has_param( 'thumbnail_id' ) ) {
			return false;
		}

		return ! self::php_request_has_thumbnail_id() && ! self::php_request_has_core_featured_image_field();
	}

	/**
	 * Whether a request is an existing lesson REST update that omitted attachments.
	 *
	 * @param stdClass|WP_Post $prepared_post Prepared post object.
	 * @param mixed            $request       Possible REST request.
	 * @return bool
	 */
	private static function is_omitted_attachment_core_rest_lesson_update( $prepared_post, $request ) {
		if ( ! self::is_core_rest_lesson_update( $prepared_post, $request ) || self::is_frontend_builder_lesson_save() ) {
			return false;
		}

		if ( $request->has_param( 'tutor_attachments' ) || $request->has_param( '_tutor_attachments' ) ) {
			return false;
		}

		$meta = $request->get_param( 'meta' );
		if ( is_array( $meta ) && array_key_exists( '_tutor_attachments', $meta ) ) {
			return false;
		}

		return ! self::php_request_has_tutor_attachments_field();
	}

	/**
	 * Whether a request is an existing lesson REST update.
	 *
	 * @param stdClass|WP_Post $prepared_post Prepared post object.
	 * @param mixed            $request       Possible REST request.
	 * @return bool
	 */
	private static function is_core_rest_lesson_update( $prepared_post, $request ) {
		if ( ! $request instanceof WP_REST_Request ) {
			return false;
		}

		$post_id = isset( $prepared_post->ID ) ? absint( $prepared_post->ID ) : 0;
		if ( ! $post_id ) {
			return false;
		}

		$post = get_post( $post_id );
		if ( ! $post || 'lesson' !== $post->post_type ) {
			return false;
		}

		if ( ! in_array( $request->get_method(), array( 'POST', 'PUT', 'PATCH' ), true ) ) {
			return false;
		}

		$route_pattern = '#^/wp/v2/lesson/' . $post_id . '$#';
		return (bool) preg_match( $route_pattern, $request->get_route() );
	}

	/**
	 * Whether the current request is Gutenberg's classic meta-box-loader save.
	 *
	 * @param int          $post_id Lesson post ID.
	 * @param WP_Post|null $post    Lesson post object.
	 * @param bool         $update  Whether this is an existing post update.
	 * @return bool
	 */
	private static function is_gutenberg_meta_box_loader_lesson_save( $post_id, $post, $update ) {
		if ( ! $update || ! is_admin() || wp_doing_ajax() || self::is_frontend_builder_lesson_save() ) {
			return false;
		}

		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return false;
		}

		$request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '';
		if ( 'POST' !== strtoupper( $request_method ) ) {
			return false;
		}

		$script_name = isset( $_SERVER['SCRIPT_NAME'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SCRIPT_NAME'] ) ) : '';
		if ( 'post.php' !== basename( $script_name ) ) {
			return false;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Detecting Gutenberg's post editor request shape only.
		$meta_box_loader = isset( $_GET['meta-box-loader'] ) ? sanitize_text_field( wp_unslash( $_GET['meta-box-loader'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Detecting Gutenberg's post editor request shape only.
		$action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Detecting Gutenberg's post editor request shape only.
		$request_post_id = isset( $_GET['post'] ) ? absint( wp_unslash( $_GET['post'] ) ) : 0;

		if ( '1' !== $meta_box_loader || 'edit' !== $action || $request_post_id !== absint( $post_id ) ) {
			return false;
		}

		return $post instanceof WP_Post && 'lesson' === $post->post_type;
	}

	/**
	 * Whether PHP request globals include Tutor LMS's thumbnail field.
	 *
	 * @return bool
	 */
	private static function php_request_has_thumbnail_id() {
		return array_key_exists( 'thumbnail_id', $_POST ) || array_key_exists( 'thumbnail_id', $_REQUEST );
	}

	/**
	 * Whether PHP request globals include WordPress core featured-image fields.
	 *
	 * @return bool
	 */
	private static function php_request_has_core_featured_image_field() {
		return array_key_exists( 'featured_media', $_POST )
			|| array_key_exists( 'featured_media', $_REQUEST )
			|| array_key_exists( '_thumbnail_id', $_POST )
			|| array_key_exists( '_thumbnail_id', $_REQUEST );
	}

	/**
	 * Whether PHP request globals include Tutor LMS attachment fields.
	 *
	 * @return bool
	 */
	private static function php_request_has_tutor_attachments_field() {
		return array_key_exists( 'tutor_attachments', $_POST )
			|| array_key_exists( 'tutor_attachments', $_REQUEST )
			|| array_key_exists( '_tutor_attachments', $_POST )
			|| array_key_exists( '_tutor_attachments', $_REQUEST );
	}

	/**
	 * Whether the current request is Tutor LMS's frontend-builder lesson save.
	 *
	 * @return bool
	 */
	private static function is_frontend_builder_lesson_save() {
		if ( ! isset( $_POST['action'] ) ) {
			return false;
		}

		$action = sanitize_text_field( wp_unslash( $_POST['action'] ) );

		return 'tutor_save_lesson' === $action;
	}

	/**
	 * Get valid existing Tutor LMS exercise attachment IDs.
	 *
	 * @param int $post_id Lesson post ID.
	 * @return array<int>
	 */
	private static function get_existing_valid_tutor_attachment_ids( $post_id ) {
		$attachment_ids = get_post_meta( $post_id, '_tutor_attachments', true );
		if ( ! is_array( $attachment_ids ) ) {
			return array();
		}

		$sanitized_ids = array();
		foreach ( $attachment_ids as $attachment_id ) {
			if ( ! is_numeric( $attachment_id ) ) {
				continue;
			}

			$attachment_id = absint( $attachment_id );
			if ( $attachment_id > 0 && 'attachment' === get_post_type( $attachment_id ) ) {
				$sanitized_ids[] = $attachment_id;
			}
		}

		return array_values( array_unique( $sanitized_ids ) );
	}

	/**
	 * Whether an attachment ID is a valid image attachment.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return bool
	 */
	private static function is_valid_image_attachment( $attachment_id ) {
		$attachment_id = absint( $attachment_id );
		return $attachment_id > 0 && 'attachment' === get_post_type( $attachment_id ) && wp_attachment_is_image( $attachment_id );
	}
}
