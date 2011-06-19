<?php

require_once 'class-plugin-options.php';
require_once 'class-diaspora.php';

/**
 * Registers, validates and displays options on the Post to Diaspora settings page.
 */
class DiasporaOptions extends PluginOptions {

	function __construct() {
		$this->options_name = 'wp_post_to_diaspora_options';
		$this->uid          = 'wp-post-to-diaspora';

		parent::__construct();
	}

	public function addPage() {
		$page = add_options_page ('Post To Diaspora', 'Post To Diaspora', 'manage_options', $this->uid, 'wp_post_to_diaspora_options');

		wp_register_style( $this->uid . '-stylesheets', WP_PLUGIN_URL . '/' . $this->uid . '/diaspora-options.css' );
		wp_enqueue_style( $this->uid . '-stylesheets' );

		wp_register_script( $this->uid . '-scripts', WP_PLUGIN_URL . '/' . $this->uid . '/diaspora-options.js' );
		wp_enqueue_script( $this->uid . '-scripts' );
	}

	/**
	 * Creates and registers field arguments that are displayed on the settings page.
	 */
        public function initialize() {
		parent::initialize();

		register_setting( $this->options_name, $this->options_name, array( &$this, 'validate' ) );

		add_settings_section( 'diaspora_general', 'General', array( $this, 'renderSectionText' ), 'general' );


		$this->field_args_by_id['handle'] = array(
			'label'         => 'Diaspora Handle',
			'name'          => 'handle',
			'type'          => 'text',
			'validate'      => array(
				'regex'       => '/^[A-z0-9_]{1,255}@[A-z0-9][A-z0-9\-]{0,62}\.[A-z]{2,3}$/',
				'regex_error' => 'Enter a handle in the format of username@joindiaspora.com or another pod location if applicable.',
				'required'    => true
			)
		);

		$this->field_args_by_id['password'] = array(
			'label'         => 'Diaspora Password',
			'name'          => 'password',
			'type'          => 'password',
			'validate'      => array(
				'required' => true
			)
		);

		$this->field_args_by_id['protocol'] = array(
			'default_value' => Diaspora::HTTPS,
			'label'         => 'Connection Type',
			'name'          => 'protocol',
			'type'          => 'checkbox',
			'options'       => array (
						array( 'label'  => 'Encrypted',
							'value'  => Diaspora::HTTPS )
						)
		);

		$this->field_args_by_id['url_shortener'] = array(
			'default_value' => 'tinyurl.com',
			'label'         => 'Link Shortener',
			'name'          => 'url_shortener',
			'type'          => 'select',
			'options'       => array (
						array( 'label'  => 'TinyURL',
							'value'  => 'tinyurl.com' ),
						array( 'label'  => 'Goo.gl',
							'value'  => 'goo.gl' ),
						array( 'label'  => 'Is.gd',
							'value'  => 'is.gd' )
			)
		);

		if ( is_array( $this->field_args_by_id ) ) {
			foreach ( $this->field_args_by_id as $id => $field_args ) {
				$field_args = wp_parse_args( $field_args, $default_field_args );

				$field_args['id'] = $id;

				add_settings_field( $id, $field_args['label'], $this->render_field_method, 'general', 'diaspora_general', $field_args );
			}
		}

		add_action( 'post_submitbox_misc_actions', array( $this, 'postMiscOptions' ) );

        }

	/**
	 * Displays a brief description on the settings page.
	 */
	public function renderSectionText() {
		echo '<p>Enter your Diaspora connection information below.</p>';
	}

	/**
	 * Rids input of excess spaces and defaults the protocol to HTTP if none is specified.
	 */
	public function validate( $inputs ) {
		if ( is_array( $inputs ) ) {
			foreach ( $inputs as $name => $value ) {
				if ( is_array ( $value ) && ( count( $value ) === 1 ) && ( isset( $value[0] ) ) ) {
					$inputs[$name] = $value[0];
				}

				if ( !is_array( $value ) ) {
					$inputs[$name] = trim( $value );
				}

				if ( isset($this->field_args_by_id[$name]['validate'] ) ) {
					$this->validateValue( $name, $inputs[$name] );
				}
			}

		}

		if ( !isset($inputs['protocol'] ) ) {
			$inputs['protocol'] = Diaspora::HTTP;
		}

		return $inputs;
	}

	/**
	 * Displays miscellaneous options that appear in the Publish Widget on the post page.
	 * This appears above the Publish/Update button.
	 */
	public function postMiscOptions() {
		$uri = substr(dirname(__FILE__), strrpos(dirname(__FILE__), DIRECTORY_SEPARATOR));
		$images_dir = plugins_url() . $uri . '/images';

		echo '<div class="misc-pub-section" id="diaspora-share-with">';
		echo '  <label for="diaspora-share-with-options">Click to share with:</label>';
		echo '  <img alt="Diaspora" class="diaspora-faded" id="diaspora" src="' . $images_dir . '/icons/diaspora-16x16.png" title="Diaspora" />';
		echo '  <input type="hidden" name="' . $this->options_name . '_share_with[diaspora]" value="0" />';
		echo '</div>';
	}

}

?>
