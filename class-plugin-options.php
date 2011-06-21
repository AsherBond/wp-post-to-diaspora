<?php

/**
 * Registers, validates and displays options on the Settings page. 
 */
class PluginOptions {

	private $default_field_args;

	/**
	 * Field arguments keyed by a field identifier.  These arguments determine
	 * how a field is displayed and validated.
	 *
	 * @var array
	 */
	protected $field_args_by_id;

	/**
	 * Stores WordPress action hooks by a key for later reference.
	 *
	 * @var array
	 */
	protected $action_hooks = array();

	/**
	 * The name to use to save and retrieve the options.
	 *
	 * @var string
	 */
	protected $options_name;

	/**
	 * Reference to the render method.
	 *
	 * @var array
	 */
	protected $render_field_method;

	/**
	 * Unique string that identifies this plugin.
	 *
	 * @var string
	 */
	protected $uid;

	function __construct() {
		$this->default_field_args = array(
			'class'         => 'regular-text',
			'default_value' => '',
			'label'         => '',
			'name'          => '',
			'type'          => 'text',
			'options'       => '',
		);

		add_action( 'admin_init', array( &$this, 'initialize' ) );
		add_action( 'admin_menu', array( &$this, 'addPage' ) );

		$this->render_field_method = array( $this, 'renderField' );
	}

	/**
	 * Makes the page visible under the Settings menu.  Override this method in your class implementation
	 * and use the add_options_page method.
	 */
	public function addPage() {

	}

	/**
	 * Adds a style to a specific page.
	 *
	 * @param string $page			The page hook to apply the style.
	 * @param string $stylesheet		Reference to the stylesheet.
	 * @param string $action_hook		Wordpress action hook to apply the style.
	 * @param string $action_hook_key	Key for referencing the action_hook at a later time.
	 * @param array  $enqueue_function	Reference to a function that actually loads
	 *					the stylesheet.  Since we are dealing
	 *					with objects it takes the 
	 *					form of array( $this, 'function_name' )
	 */
	public function addStyle( $page, $stylesheet, $action_hook, $action_hook_key, $enqueue_function ) {
		wp_register_style( $this->uid . '-' . $action_hook . '-' . $page, $stylesheet );

		add_action( $action_hook . '-' . $page, $enqueue_function );

		$this->hooks[$action_hook_key] = $this->uid . '-' . $action_hook . '-' . $page;
	}

	public function initialize() {
		$this->field_args_by_id = array();
	}

	/**
	 * Renders a field on the screen.
	 *
	 * @param array $args	An array of field arguments.
	 */
	public function renderField( $args = array() ) {
		$class         = '';
		$default_value = '';
		$id            = '';
		$name          = '';
		$type          = '';
		$value         = '';

		$arg_keys      = array( 'class', 'default_value', 'id', 'name', 'type' );

		foreach ( $arg_keys as  $arg_key ) {
			if ( isset( $args[$arg_key] ) ) {
				$$arg_key = $args[$arg_key];
			}
		}

		$options = get_option( $this->options_name );

		if ( empty( $value ) ) {
			$value = $default_value;
		}

		switch ( $type ) {
			case 'text':
			case 'password':

				echo "<input id='$id' class='$class' name='wp_post_to_diaspora_options[{$name}]' type='$type' value='$value' />";

				break;

			case 'checkbox':

				if ( ( isset( $args['options'] ) ) && ( is_array( $args['options'] ) ) ) {
					foreach ($args['options'] as $option) {
						$checked = '';

						if ( is_array( $value ) ) {
							if ( in_array($option['value'], $value ) ) {
								$checked = "checked='checked'";
							}
						}
						else if ( $option['value'] == $value ) {
							$checked = "checked='checked'";
						}

						echo "<input id='$id' class='$class' name='wp_post_to_diaspora_options[{$name}][]' type='$type' value='{$option['value']}' $checked /> {$option['label']}";
					}
				}

				break;

			case 'select':
			case 'select-one':

				echo "<select id='$id' class='$class' name='wp_post_to_diaspora_options[{$name}][]'>";

				if ( ( isset( $args['options'] ) ) && ( is_array( $args['options'] ) ) ) {
					foreach ($args['options'] as $option) {
						$selected = '';

						if ( is_array( $value ) ) {
							if ( in_array($option['value'], $value ) ) {
								$selected = "selected='selected'";
							}
						}
						else if ( $option['value'] == $value ) {
							$selected = "selected='selected'";
						}

						echo "<option value='{$option['value']}' $selected>{$option['label']}</option>";

					}
				}

				echo "</select>";
				break;

			default:

				break;

		}
	}

}

?>
