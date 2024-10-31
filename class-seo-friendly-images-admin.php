<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SEO_Friendly_Images_Admin {

	private static $_instance = null;
	public $namespace = '';
	public $text_domain = 'seo-friendly-images';
	public $settings = array();

	public function __construct() {
		$this->namespace = SEO_FRIENDLY_IMAGES_NAMESPACE;
		$this->admin_helper = new SEO_Friendly_Images_Admin_Helper();
		add_action( 'init', array( $this, 'init_settings' ), 10 );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_menu', array( $this, 'add_menu_item' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'add_settings_link' ) );
	}

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function init_settings() {
		$this->settings = $this->settings_fields();
	}

	private function settings_fields() {

		$settings['standard'] = array(
			'title'       => __( '', $this->text_domain ),
			'description' => __( 'This plugin automagically insert/override all the image ALT text to increase SEO image search ranking.<br>' .
'It can also automagically insert/override all the image Title.<br>' .
'<br>Please refer to the <a href="http://www.optimalplugins.com/doc/seo-friendly-images-for-wp" target="_blank">User guide</a> for details.'
, $this->text_domain ),
			'fields'      => array(
				array(
					'id'          => 'image_alt_format',
					'label'       => __( 'Image ALT Text Format', $this->text_domain ),
					'description' => __( '', $this->text_domain ),
					'type'        => 'text',
					'default'     => '%name',
					'placeholder' => __( '%name', $this->text_domain )
				),

				array(
					'id'          => 'enable_image_alt',
					'label'       => __( 'Enable auto image ALT text', $this->text_domain ),
					'description' => __( '', $this->text_domain ),
					'type'        => 'checkbox',
					'default'     => true,
					'description' => __( 'Insert ALT text if ALT text is blank', $this->text_domain ),
				),

				array(
					'id'          => 'override_image_alt',
					'label'       => __( 'Override image ALT text', $this->text_domain ),
					'description' => __( '', $this->text_domain ),
					'type'        => 'checkbox',
					'default'     => false,
					'description' => __( 'Override existing ALT text', $this->text_domain ),
				),

				array(
					'id'          => 'image_title_format',
					'label'       => __( 'Image Title Format', $this->text_domain ),
					'description' => __( '', $this->text_domain ),
					'type'        => 'text',
					'default'     => 'Image of %name',
					'placeholder' => __( 'Image of %name', $this->text_domain )
				),

				array(
					'id'          => 'enable_image_title',
					'label'       => __( 'Enable auto image title', $this->text_domain ),
					'description' => __( '', $this->text_domain ),
					'type'        => 'checkbox',
					'default'     => true,
					'description' => __( 'Insert image title if image title is blank', $this->text_domain ),
				),

				array(
					'id'          => 'override_image_title',
					'label'       => __( 'Override image title', $this->text_domain ),
					'description' => __( '', $this->text_domain ),
					'type'        => 'checkbox',
					'default'     => '',
					'description' => __( 'Override existing image title', $this->text_domain ),
				)
			)
		);

		$settings = apply_filters( $this->namespace . '_settings_fields', $settings );

		return $settings;
	}

	public function add_menu_item() {
		add_menu_page( __( 'SEO Friendly Images', $this->text_domain ),
			__( 'SEO Images', $this->text_domain ), 'manage_options',
			$this->namespace . '_settings', array( $this, 'settings_page' ), 'dashicons-format-image', 87 );

	}

	public function add_settings_link( $links ) {
		$settings_link = array(
			'<a href="' . admin_url( 'options-general.php?page=' . $this->namespace . '_settings' ) . '">' . __( 'Settings', $this->text_domain ) . '</a>');

		return array_merge( $links, $settings_link );
	}

	public function register_settings() {
		if ( is_array( $this->settings ) ) {

			// Check posted/selected tab
			$current_section = '';
			if ( isset( $_POST['tab'] ) && $_POST['tab'] ) {
				$current_section = $_POST['tab'];
			} else {
				if ( isset( $_GET['tab'] ) && $_GET['tab'] ) {
					$current_section = $_GET['tab'];
				}
			}

			foreach ( $this->settings as $section => $data ) {

				if ( $current_section && $current_section != $section ) {
					continue;
				}

				// Add section to page
				add_settings_section( $section, $data['title'], array(
					$this,
					'settings_section'
				), $this->namespace . '_settings' );

				foreach ( $data['fields'] as $field ) {

					// Validation callback for field
					$validation = '';
					if ( isset( $field['callback'] ) ) {
						$validation = $field['callback'];
					}

					// Register field
					$option_name = $this->namespace . $field['id'];
					register_setting( $this->namespace . '_settings', $option_name, $validation );

					// Add field to page
					add_settings_field( $field['id'], $field['label'], array(
						$this->admin_helper,
						'display_field'
					), $this->namespace . '_settings', $section, array( 'field' => $field, 'prefix' => $this->namespace ) );
				}

				if ( ! $current_section ) {
					break;
				}
			}
		}
	}

	public function settings_section( $section ) {
		$html = '<p> ' . $this->settings[ $section['id'] ]['description'] . '</p>' . "\n";
		echo $html;
	}

	public function settings_page() {

		// Build page HTML
		$html = '<div class="wrap" id="' . $this->namespace . '_settings">' . "\n";
		$html .= '<h2>' . __( 'SEO Friendly Images', $this->text_domain ) . '</h2>' . "\n";

		$tab = '';
		if ( isset( $_GET['tab'] ) && $_GET['tab'] ) {
			$tab .= $_GET['tab'];
		}

		// Show page tabs
		if ( is_array( $this->settings ) && 1 < count( $this->settings ) ) {

			$html .= '<h2 class="nav-tab-wrapper">' . "\n";

			$c = 0;
			foreach ( $this->settings as $section => $data ) {

				// Set tab class
				$class = 'nav-tab';
				if ( ! isset( $_GET['tab'] ) ) {
					if ( 0 == $c ) {
						$class .= ' nav-tab-active';
					}
				} else {
					if ( isset( $_GET['tab'] ) && $section == $_GET['tab'] ) {
						$class .= ' nav-tab-active';
					}
				}

				// Set tab link
				$tab_link = add_query_arg( array( 'tab' => $section ) );
				if ( isset( $_GET['settings-updated'] ) ) {
					$tab_link = remove_query_arg( 'settings-updated', $tab_link );
				}

				// Output tab
				$html .= '<a href="' . $tab_link . '" class="' . esc_attr( $class ) . '">' . esc_html( $data['title'] ) . '</a>' . "\n";

				++ $c;
			}

			$html .= '</h2>' . "\n";
		}

		$html .= '<form method="post" action="options.php" enctype="multipart/form-data">' . "\n";

		ob_start();
		settings_fields( $this->namespace . '_settings' );
		do_settings_sections( $this->namespace . '_settings' );
		$html .= ob_get_clean();

		$html .= '<p class="submit">' . "\n";
		$html .= '<input type="hidden" name="tab" value="' . esc_attr( $tab ) . '" />' . "\n";
		$html .= '<input name="Submit" type="submit" class="button-primary" value="' . esc_attr( __( 'Save Settings', $this->text_domain ) ) . '" />' . "\n";
		$html .= '</p>' . "\n";
		$html .= '</form>' . "\n";
		$html .= '</div>' . "\n";

		echo $html;
	}
}

?>