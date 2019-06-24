<?php
namespace Elementor;

use Elementor\Core\Files\Assets\Svg\Svg_Handler;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Elementor icons manager.
 *
 * Elementor icons manager handler class
 *
 * @since 2.4.0
 */
class Icons_Manager {

	const NEEDS_UPDATE_OPTION = 'icon_manager_needs_update';
	/**
	 * Tabs.
	 *
	 * Holds the list of all the tabs.
	 *
	 * @access private
	 * @static
	 * @since 2.4.0
	 * @var array
	 */
	private static $tabs;

	private static function get_needs_upgrade_option() {
		return get_option( 'elementor_' . self::NEEDS_UPDATE_OPTION, null );
	}

	/**
	 * Init Tabs
	 *
	 * Initiate Icon Manager Tabs.
	 *
	 * @access private
	 * @static
	 * @since 2.4.0
	 */
	private static function init_tabs() {
		self::$tabs = apply_filters( 'elementor/icons_manager/native', [
			'regular' => [
				'name' => 'regular',
				'label' => __( 'Font Awesome - Regular', 'elementor' ),
				'url' => self::get_asset_url( 'regular' ),
				'enqueue' => [ self::get_asset_url( 'fontawesome' ) ],
				'prefix' => 'fa-',
				'displayPrefix' => 'far',
				'labelIcon' => 'fab fa-font-awesome-flag',
				'ver' => '5.9.0',
				'fetchJson' => self::get_asset_url( 'regular', 'json', false ),
			],
			'solid' => [
				'name' => 'solid',
				'label' => __( 'Font Awesome - Solid', 'elementor' ),
				'url' => self::get_asset_url( 'solid' ),
				'enqueue' => [ self::get_asset_url( 'fontawesome' ) ],
				'prefix' => 'fa-',
				'displayPrefix' => 'fas',
				'labelIcon' => 'fab fa-font-awesome-alt',
				'ver' => '5.9.0',
				'fetchJson' => self::get_asset_url( 'solid', 'json', false ),
			],
			'brands' => [
				'name' => 'brands',
				'label' => __( 'Font Awesome - Brands', 'elementor' ),
				'url' => self::get_asset_url( 'brands' ),
				'enqueue' => [ self::get_asset_url( 'fontawesome' ) ],
				'prefix' => 'fa-',
				'displayPrefix' => 'fab',
				'labelIcon' => 'fab fa-font-awesome',
				'ver' => '5.9.0',
				'fetchJson' => self::get_asset_url( 'brands', 'json', false ),
			],
		] );
	}

	/**
	 * Get Icon Manager Tabs
	 * @return array
	 */
	public static function get_icon_manager_tabs() {
		if ( ! self::$tabs ) {
			self::init_tabs();
		}
		$additional_tabs = apply_filters( 'elementor/icons_manager/additional_tabs', [] );
		return array_merge( self::$tabs, $additional_tabs );
	}

	public static function enqueue_shim() {
		if ( did_action( 'elementor_pro/icons_manager/shim_enqueued' ) ) {
			return;
		}
		do_action( 'elementor_pro/icons_manager/shim_enqueued' );
		wp_enqueue_script(
			'font-awesome-4-shim',
			self::get_asset_url( 'v4-shim', 'js' ),
			[],
			ELEMENTOR_VERSION
		);
		wp_enqueue_style(
			'font-awesome-5-all',
			self::get_asset_url( 'all' ),
			[],
			ELEMENTOR_VERSION
		);
		wp_enqueue_style(
			'font-awesome-4-shim',
			self::get_asset_url( 'v4-shim' ),
			[],
			ELEMENTOR_VERSION
		);
	}

	private static function get_asset_url( $filename, $ext_type = 'css', $add_suffix = true ) {
		static $is_test_mode = null;
		if ( null === $is_test_mode ) {
			$is_test_mode = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG || defined( 'ELEMENTOR_TESTS' ) && ELEMENTOR_TESTS;
		}
		$url = ELEMENTOR_ASSETS_URL . 'lib/font-awesome/' . $ext_type . '/' . $filename;
		if ( ! $is_test_mode && $add_suffix ) {
			$url .= '.min';
		}
		return $url . '.' . $ext_type;
	}

	public static function get_icon_manager_tabs_config() {
		$tabs = [
			'all' => [
				'name' => 'all',
				'label' => __( 'All Icons', 'elementor' ),
				'labelIcon' => 'eicon-filter',
			],
		];
		return array_values( array_merge( $tabs, self::get_icon_manager_tabs() ) );
	}

	private static function render_svg_icon( $value ) {
		if ( ! isset( $value['id'] ) ) {
			return '';
		}
		return Svg_Handler::get_inline_svg( $value['id'] );
	}

	private static function render_icon_html( $icon, $attributes = [], $tag = 'i' ) {
		$icon_types = self::get_icon_manager_tabs();
		if ( isset( $icon_types[ $icon['library'] ]['render_callback'] ) && is_callable( $icon_types[ $icon['library'] ]['render_callback'] ) ) {
			return call_user_func_array( $icon_types[ $icon['library'] ]['render_callback'], [ $icon, $attributes, $tag ] );
		}

		if ( empty( $attributes['class'] ) ) {
			$attributes['class'] = $icon['value'];
		} else {
			if ( is_array( $attributes['class'] ) ) {
				$attributes['class'][] = $icon['value'];
			} else {
				$attributes['class'] .= ' ' . $icon['value'];
			}
		}
		return '<' . $tag . ' ' . Utils::render_html_attributes( $attributes ) . '></' . $tag . '>';
	}

	/**
	 * Render Icon
	 *
	 * Used to render Icon for \Elementor\Controls_Manager::ICONS
	 * @param array $icon             Icon Type, Icon value
	 * @param array $attributes       Icon HTML Attributes
	 * @param string $tag             Icon HTML tag, defaults to <i>
	 *
	 * @return mixed|string
	 */
	public static function render_icon( $icon, $attributes = [], $tag = 'i' ) {
		if ( empty( $icon['library'] ) ) {
			return false;
		}
		$output = '';
		// handler SVG Icon
		if ( 'svg' === $icon['library'] ) {
			$output = self::render_svg_icon( $icon['value'] );
		} else {
			$output = self::render_icon_html( $icon, $attributes, $tag );
		}
		echo $output;
		return true;
	}

	/**
	 * is_migration_allowed
	 * @return bool
	 */
	public static function is_migration_allowed() {
		$migration_allowed = null === self::get_needs_upgrade_option();
		/**
		 * allowed to filter migration allowed
		 */
		return apply_filters( 'elementor/icons_manager/migration_allowed', $migration_allowed );
	}

	/**
	 * Register_Admin Settings
	 *
	 * adds Font Awesome migration / update admin settings
	 * @param Settings $settings
	 */
	public function register_admin_settings( Settings $settings ) {
		$settings->add_field(
			Settings::TAB_ADVANCED,
			Settings::TAB_ADVANCED,
			'load_fa4_shim',
			[
				'label' => __( 'Load Font Awesome 4 Support', 'elementor' ),
				'field_args' => [
					'type' => 'select',
					'std' => 1,
					'options' => [
						'' => __( 'No', 'elementor' ),
						1 => __( 'Yes', 'elementor' ),
					],
					'desc' => __( 'Font Awesome 4 support script (shim.js) is a script that makes sure all previously selected Font Awesome 4 icons are displayed correctly while using Font Awesome 5 library.', 'elementor' ),
				],
			]
		);
	}

	public function register_admin_tools_settings( Tools $settings ) {
		$settings->add_tab( 'fontawesome4_migration', [ 'label' => __( 'Font Awesome Migration', 'elementor' ) ] );

		$settings->add_section( 'fontawesome4_migration', 'fontawesome4_migration', [
			'callback' => function() {
				echo '<hr><h2>' . esc_html__( 'Font Awesome Migration', 'elementor' ) . '</h2>';
				echo '<p>' .
				esc_html__( 'Access 1,500+ amazing Font Awesome 5 icons and enjoy faster performance and design flexibility.', 'elementor' ) . '<br>' .
				esc_html__( 'By upgrading, whenever you edit a page containing a Font Awesome 4 icon, Elementor will convert it to the new Font Awesome 5 icon.', 'elementor' ) .
				'</p><p><strong>' .
				esc_html__( 'Please note that due to minor design changes made to some Font Awesome 5 icons, some of your updated Font Awesome 4 icons may look a bit different.', 'elementor' ) .
				'</strong></p><p>' .
				esc_html__( 'This action is not reversible and cannot be undone by rolling back to previous versions.', 'elementor' ) .
				'</p>';
			},
			'fields' => [
				[
					'label'      => __( 'Font Awesome Migration', 'elementor' ),
					'field_args' => [
						'type' => 'raw_html',
						'html' => sprintf( '<span data-action="%s" data-_nonce="%s" class="button elementor-button-spinner" id="elementor_upgrade_fa_button">%s</span>',
							self::NEEDS_UPDATE_OPTION . '_upgrade',
							wp_create_nonce( self::NEEDS_UPDATE_OPTION ),
							__( 'Migrate To Font Awesome 5', 'elementor' )
						),
					],
				],
			],
		] );
	}

	/**
	 * Ajax Upgrade to FontAwesome 5
	 */
	public function ajax_upgrade_to_fa5() {
		check_ajax_referer( self::NEEDS_UPDATE_OPTION, '_nonce' );

		delete_option( 'elementor_' . self::NEEDS_UPDATE_OPTION );

		wp_send_json_success( [ 'message' => __( 'Hurray! The migration process to FontAwesome 5 was completed successfully.', 'elementor' ) ] );
	}

	/**
	 * Add Update Needed Flag
	 * @param array $settings
	 *
	 * @return array;
	 */
	public function add_update_needed_flag( $settings ) {
		$settings['icons_update_needed'] = true;
		return $settings;
	}

	public function enqueue_fontawesome_css() {
		if ( ! self::is_migration_allowed() ) {
			wp_enqueue_style(
				'font-awesome',
				self::get_asset_url( 'font-awesome' ),
				[],
				'4.7.0'
			);
		} else {
			self::enqueue_shim();
		}
	}

	public function add_admin_strings( $settings ) {
		$settings['i18n']['confirm_fa_migration_admin_modal_body']  = __( 'I understand that by upgrading to Font Awesome 5, I acknowledge that some changes may affect my website and that this action cannot be undone.', 'elementor' );
		$settings['i18n']['confirm_fa_migration_admin_modal_head']  = __( 'Font Awesome 5 Migration', 'elementor' );
		return $settings;
	}

	/**
	 * Icons Manager constructor
	 */
	public function __construct() {
		if ( is_admin() ) {
			// @todo: remove once we deprecate fa4
			add_action( 'elementor/admin/after_create_settings/' . Settings::PAGE_ID, [ $this, 'register_admin_settings' ], 100 );
			add_action( 'elementor/admin/localize_settings', [ $this, 'add_admin_strings' ] );
		}

		do_action( 'elementor/editor/after_enqueue_styles', [ $this, 'enqueue_fontawesome_css' ] );
		do_action( 'elementor/frontend/after_enqueue_styles', [ $this, 'enqueue_fontawesome_css' ] );

		if ( ! self::is_migration_allowed() ) {
			add_action( 'elementor/editor/localize_settings', [ $this, 'add_update_needed_flag' ] );
			add_action( 'elementor/admin/after_create_settings/' . Tools::PAGE_ID, [ $this, 'register_admin_tools_settings' ], 100 );

			if ( ! empty( $_POST ) ) { // phpcs:ignore -- nonce validation done in callback
				add_action( 'wp_ajax_' . self::NEEDS_UPDATE_OPTION . '_upgrade', [ $this, 'ajax_upgrade_to_fa5' ] );
			}
		}
	}
}