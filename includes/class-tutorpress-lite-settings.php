<?php
/**
 * TutorPress Lite settings (Settings API).
 *
 * @package TutorPress_Lite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers shared TutorPress settings for Lite (no template_overrides UI).
 */
class TutorPress_Lite_Settings {

	/**
	 * Settings keys grouped in the dashboard redirects section.
	 *
	 * @var string[]
	 */
	private static $dashboard_section_keys = array(
		'enable_admin_redirects',
		'remove_frontend_builder_button',
		'enable_dashboard_redirects',
	);

	/**
	 * Bootstrap settings hooks.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_settings_page' ), 11 );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
	}

	/**
	 * Add settings submenu under Tutor LMS.
	 */
	public static function add_settings_page() {
		add_submenu_page(
			'tutor',
			__( 'TutorPress Lite for Tutor LMS', 'tutorpress-lite' ),
			__( 'TutorPress Lite', 'tutorpress-lite' ),
			'manage_options',
			'tutorpress-settings',
			array( __CLASS__, 'render_settings_page' )
		);
	}

	/**
	 * Register settings, sections, and fields.
	 */
	public static function register_settings() {
		register_setting(
			'tutorpress_settings_group',
			'tutorpress_settings',
			array(
				'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ),
			)
		);

		add_settings_section(
			'tutorpress_main_section',
			__( 'Enable or Disable Features', 'tutorpress-lite' ),
			null,
			'tutorpress-settings'
		);

		add_settings_section(
			'tutorpress_dashboard_section',
			__( 'Editor & Dashboard Redirects', 'tutorpress-lite' ),
			null,
			'tutorpress-settings'
		);

		foreach ( self::get_defined_settings() as $key => $setting ) {
			$section = in_array( $key, self::$dashboard_section_keys, true )
				? 'tutorpress_dashboard_section'
				: 'tutorpress_main_section';

			add_settings_field(
				$key,
				$setting['label'],
				array( __CLASS__, 'render_toggle' ),
				'tutorpress-settings',
				$section,
				array(
					'key'    => $key,
					'helper' => $setting['helper'],
				)
			);
		}
	}

	/**
	 * Merge sanitizer: update only known toggle keys; preserve unknown keys and template_overrides.
	 *
	 * @param mixed $input Raw option input from the settings form.
	 * @return array<string, mixed>
	 */
	public static function sanitize_settings( $input ) {
		if ( ! is_array( $input ) ) {
			$input = array();
		}

		$merged = get_option( 'tutorpress_settings', array() );
		if ( ! is_array( $merged ) ) {
			$merged = array();
		}

		foreach ( self::get_defined_settings() as $key => $setting ) {
			unset( $setting );

			if ( isset( $input[ $key ] ) && '1' === $input[ $key ] ) {
				$merged[ $key ] = '1';
			} else {
				unset( $merged[ $key ] );
			}
		}

		return $merged;
	}

	/**
	 * Lite toggle field definitions (five keys; no template_overrides).
	 *
	 * @return array<string, array{label: string, helper: string}>
	 */
	private static function get_defined_settings() {
		return array(
			'enable_admin_redirects'          => array(
				'label'  => __( 'Redirect Backend Course Editing to Gutenberg', 'tutorpress-lite' ),
				'helper' => '',
			),
			'remove_frontend_builder_button'  => array(
				'label'  => __( 'Remove Button to Frontend Builder in Course Editor', 'tutorpress-lite' ),
				'helper' => '',
			),
			'enable_dashboard_redirects'      => array(
				'label'  => __( 'Redirect Frontend Dashboard Editing to Gutenberg', 'tutorpress-lite' ),
				'helper' => '',
			),
			'enable_sidebar_tabs'             => array(
				'label'  => __( 'Enable Discussion Tab in Lessons', 'tutorpress-lite' ),
				'helper' => __( 'Adds a Discussion tab to the sidebar of inner course pages and removes the Comments link from the main content area. This feature also adds compatibility with many comment plugins to enhance the social learning experience.', 'tutorpress-lite' ),
			),
			'enable_extra_dashboard_links'    => array(
				'label'  => __( 'Add Media Library & H5P Links to Instructor Dashboard', 'tutorpress-lite' ),
				'helper' => __( 'Includes links to the Instructor menu in the frontend dashboard. If you do not want instructors to use these backend pages, leave this disabled.', 'tutorpress-lite' ),
			),
		);
	}

	/**
	 * Render a toggle field with switch styling.
	 *
	 * @param array{key: string, helper?: string} $args Field arguments.
	 */
	public static function render_toggle( $args ) {
		$opts = get_option( 'tutorpress_settings', array() );
		$key  = $args['key'];
		$val  = isset( $opts[ $key ] ) ? $opts[ $key ] : '0';

		echo '<label class="tutorpress-switch">';
		printf(
			'<input type="checkbox" name="tutorpress_settings[%1$s]" value="1" %2$s />',
			esc_attr( $key ),
			checked( '1', $val, false )
		);
		echo '<span class="tutorpress-slider"></span></label>';

		if ( ! empty( $args['helper'] ) ) {
			printf(
				'<p class="description" style="max-width: 600px; margin-top: 0;">%s</p>',
				esc_html( $args['helper'] )
			);
		}
	}

	/**
	 * Render the settings page.
	 */
	public static function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap tutorpress-lite-settings-wrap">
			<style>
				/* Visual frame for the Editor & Dashboard Redirects section */
				#tutorpress_dashboard_section + table.form-table {
					border: 1px solid #e5e7eb;
					padding: 12px;
					border-radius: 6px;
					background: #fff;
				}
				#tutorpress_dashboard_section {
					margin-bottom: 8px;
				}
				/* Do not use class "notice" — wp-admin moves .notice to the top of .wrap */
				.tutorpress-lite-full-about {
					max-width: 800px;
					margin: 16px 0 0;
					padding: 12px;
					border: 1px solid #c3c4c7;
					border-left-width: 4px;
					border-left-color: #72aee6;
					background: #fff;
					box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04);
				}
				.tutorpress-lite-full-about h2 {
					margin-top: 0;
					font-size: 1.1em;
				}
				.tutorpress-lite-full-about p:last-child {
					margin-bottom: 0;
				}
			</style>
			<h1><?php echo esc_html__( 'TutorPress Lite for Tutor LMS', 'tutorpress-lite' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'tutorpress_settings_group' );
				do_settings_sections( 'tutorpress-settings' );
				submit_button();
				self::render_full_tutorpress_about();
				?>
			</form>
			<style>
				.tutorpress-switch {
					position: relative;
					display: inline-block;
					width: 34px;
					height: 20px;
				}
				.tutorpress-switch input {
					display: none;
				}
				.tutorpress-slider {
					position: absolute;
					cursor: pointer;
					top: 0;
					left: 0;
					right: 0;
					bottom: 0;
					background-color: #ccc;
					transition: 0.4s;
					border-radius: 20px;
				}
				.tutorpress-slider:before {
					position: absolute;
					content: "";
					height: 14px;
					width: 14px;
					left: 3px;
					bottom: 3px;
					background-color: #fff;
					transition: 0.4s;
					border-radius: 50%;
				}
				input:checked + .tutorpress-slider {
					background-color: #2196f3;
				}
				input:checked + .tutorpress-slider:before {
					transform: translateX(14px);
				}
			</style>
		</div>
		<?php
	}

	/**
	 * About box for full TutorPress (settings page only; see TUTORPRESS_FULL_PRODUCT_URL).
	 *
	 * Hidden when full TutorPress is active.
	 */
	public static function render_full_tutorpress_about() {
		if ( defined( 'TUTORPRESS_VERSION' ) ) {
			return;
		}

		if ( ! defined( 'TUTORPRESS_FULL_PRODUCT_URL' ) ) {
			return;
		}

		$product_url = TUTORPRESS_FULL_PRODUCT_URL;
		?>
		<div class="tutorpress-lite-full-about">
			<h2>
				<?php
				esc_html_e(
					'Get the full version of TutorPress: a Gutenberg Course Builder for Tutor LMS',
					'tutorpress-lite'
				);
				?>
			</h2>
			<p>
				<?php
				esc_html_e(
					'TutorPress Lite shares the same settings and UX improvements as the full version. The full version of TutorPress adds a Gutenberg-native Course Builder in full parity with Tutor LMS\'s frontend course builder, as well as deeper compatibility with the wider WordPress ecosystem.',
					'tutorpress-lite'
				);
				?>
			</p>
			<p>
				<?php
				esc_html_e(
					'The full version of TutorPress is available with a 14-day free trial from Indigetal WebCraft (the trial applies to the paid plugin, not to this free Lite release). After you activate full TutorPress, deactivate TutorPress Lite.',
					'tutorpress-lite'
				);
				?>
			</p>
			<p>
				<a href="<?php echo esc_url( $product_url ); ?>" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'Learn more about TutorPress', 'tutorpress-lite' ); ?>
				</a>
			</p>
		</div>
		<?php
	}
}
