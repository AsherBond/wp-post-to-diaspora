<?php

require_once 'class-diaspora.php';
require_once 'class-plugin-options.php';

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
		$page = add_options_page ('WP Post To Diaspora', 'WP Post To Diaspora', 'manage_options', $this->uid, array( &$this, 'renderOptionsPage' ) );

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

		$this->field_args_by_id['id'] = array(
			'label'         => 'Diaspora ID',
			'name'          => 'id',
			'type'          => 'text',
			'validate'      => array(
				'filter'       => FILTER_VALIDATE_EMAIL,
				'filter_error' => 'Enter an ID in the format of username@joindiaspora.com or another pod location if applicable.',
				'required'    => true
			)
		);

		$this->field_args_by_id['port'] = array(
			'label'         => 'Diaspora Port (Optional)',
			'name'          => 'port',
			'type'          => 'text',
			'validate'      => array(
				'filter'         => FILTER_VALIDATE_INT,
				'filter_options' => array('min_range' => 1,
									      'max_range' => 65536),
				'filter_error'   => 'Valid Diaspora Port ranges are from 1 to 65536.'
			)
		);

		// Temporary until OAuth2 is integrated
		$this->field_args_by_id['oauth2_identifier'] = array(
			'label'         => 'OAuth2 ID',
			'name'          => 'oauth2_identifier',
			'type'          => 'text',
			'validate'      => array(
				'required'    => true
			)
		);

		$this->field_args_by_id['oauth2_secret'] = array(
			'label'         => 'OAuth2 Secret',
			'name'          => 'oauth2_secret',
			'type'          => 'text',
			'validate'      => array(
				'required'    => true
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

		add_action( 'all_admin_notices', array( &$this, 'renderConnectLink' ) );
		add_action( 'post_submitbox_misc_actions', array( &$this, 'postMiscOptions' ) );
		add_action( 'update_option', array( &$this, 'updateOption' ), 10, 3 );
		add_filter( 'redirect_post_location', array( &$this, 'redirectPost' ), 10, 2 );

		if  ( ( isset($_GET['auth-request']) ) || ( isset( $_GET['authorize'] ) ) ) {
			$this->connectToDiaspora();
		}
	}

	private function connectToDiaspora() {
		$options = get_option( $this->options_name );

		$diaspora = new Diaspora();
		$diaspora->setId( $options['id'] );
		$diaspora->setOauth2Identifier( $options['oauth2_identifier'] );
		$diaspora->setOauth2Secret( $options['oauth2_secret'] );
		$diaspora->setPort( $options['port'] );
		$diaspora->setProtocol( $options['protocol'] );

		if ( !isset( $_GET['authorize'] ) ) {
			$diaspora->authorizationRequest();			
		}
		else if ( ( isset( $_GET['code'] ) ) && ( !empty( $_GET['code'] ) ) ) {
			$authorization_grant = $_GET['code'];

			$diaspora->setOauth2AuthorizationGrant( $authorization_grant );
			$token = $diaspora->getToken();

			if ( $token !== null ) {
				$options['oauth2_access_token']  = $token->accessToken;
				$options['oauth2_refresh_token'] = $token->refreshToken;
				$options['oauth2_lifetime']      = $token->lifeTime;

				update_option( $this->options_name, $options );
			}

		}
	}

	public function renderConnectLink() {
		$options = get_option( $this->options_name );

		if ( ( !empty($options['id'] ) )
			&& ( !empty($options['oauth2_identifier'] ) ) 
			&& ( !empty($options['oauth2_secret'] ) ) 
			&& ( empty( $options['oauth2_access_token'] ) ) 
			&& ( isset( $_GET['page'] ) )
			&& ( $_GET['page'] == $this->uid ) ) {

			$msg = 'Connect with your Diaspora server by clicking <a href="?page=' . $this->uid . '&auth-request">here</a>.';

			if ( isset($_GET['error'] ) ) {
				$msg .= ' Error: ' . $_GET['error'];
			}

			$saved_errors = get_transient( 'settings_errors' );

			if ( ( !empty($saved_errors) ) && ( is_array($saved_errors) ) ) {

				// Locate the 'Settings saved.' message that appears during a postback call and append
				// the connect message to it.
				foreach ( $saved_errors as $index=> $saved_error ) {
					if ( $saved_error['code'] == 'settings_updated' ) {
						$saved_errors[$index]['message'] .= ' ' . $msg;
						break;
					}
				}

				set_transient( 'settings_errors', $saved_errors );
			}
			else {
				add_settings_error( 'general', 'connect_needed', $msg );
			}
		}
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
	 *
	 * Emits a warning if a handle and password are not set.
	 */
	public function postMiscOptions() {
		$options = get_option( $this->options_name );

		$img_class = 'diaspora-faded';
		$share_with = '0';

		if ( ( isset( $_GET['wptd_share'] ) ) && ( $_GET['wptd_share'] == 'diaspora' ) ) {
			$img_class = '';
			$share_with = '1';
		}

		echo '<div class="misc-pub-section" id="diaspora-share-with">';
		echo '  <label for="diaspora-share-with-options">Click to share with:</label>';
		echo '  <img alt="Diaspora" class="' . $img_class . '" id="diaspora" src="' . $this->plugin_uri . '/images/icons/diaspora-16x16.png" title="Diaspora" />';
		echo '  <input type="hidden" name="' . $this->options_name . '_share_with[diaspora]" value="' . $share_with .'" />';

		if ( ( empty( $options['id']) )
			|| ( empty($options['oauth2_identifier'] ) )
			|| ( empty($options['oauth2_secret'] ) ) ) {
			echo '<p class="diaspora-warning">Attention: <a href="' . get_admin_url() . 'options-general.php?page=wp-post-to-diaspora">Configure before using.</a></p>';
		}

		echo '</div>';

	}

	/**
	 * Renders the options page that appears on
	 * Settings -> WP Post to Diaspora.
	 */
	public function renderOptionsPage() {
		echo '<div class="wrap">';
		echo '	<h2>WP Post To Diaspora</h2>';
		echo '	<form method="post" action="options.php">';

		settings_fields( 'wp_post_to_diaspora_options' );
		do_settings_sections( 'general' );

		echo '		<p class="submit"><input type="submit" name="update" value="' . __('Save Changes') . '" /></p>';
		echo '	</form>';
		echo '</div>';
	}

	/**
	 * Retains that state of the Click to share with Diaspora button when a draft post is saved. 
	 * An extra GET parameter of wptd_share is appended to URL.
	 *
	 * Some people may want scheduled posts automatically sent to Diaspora later down the road.
	 * In that case this information will need to be stored as a custom field within each post.
	 *
	 * @param string $location The URL being redirected to.
	 * @param int $post_id Post id from get_the_ID().
	 */
	public function redirectPost( $location, $post_id ) {
		if ( isset( $_POST[$this->options_name . '_share_with']['diaspora'] ) &&
		  ( $_POST[$this->options_name . '_share_with']['diaspora'] === '1' ) ) {
			$location .= '&wptd_share=diaspora';
		}

		return $location;
	}

	/**
	 * Clear the OAuth2 tokens if the Diaspora id (handle) has changed.
	 *
	 * @param string $option_name Admin option on the settings page
	 * @param mixed $old_value Previous value
	 * @param mixed $new_value Changed value
	 */
	public function updateOption($option_name, $old_value, $new_value) {
		if ( $option_name == $this->options_name ) {
			if ( isset($new_value['id']) 
				&& ( isset($old_value['id']) )
				&& ( strcasecmp($old_value['id'], $new_value['id']) !== 0 ) ) {

				unset($new_value['oauth2_access_token']);
				unset($new_value['oauth2_refresh_token']);
				unset($new_value['oauth2_lifetime']);
			}
		}
	}

}

?>
