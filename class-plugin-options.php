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

	public function initialize() {
		$this->field_args_by_id = array();
	}

	/**
	 * Renders a field on the screen.
	 *
	 * @param array $args	An array of field arguments.
	 */
	public function renderField( $args = array() ) {
		$default_value = $args['default_value'];
		$options = get_option( $this->options_name );

		$value = $options[$args['name']];

		if ( empty( $value ) ) {
			$value = $default_value;
		}

		switch ( $args['type'] ) {
			case 'text':
			case 'password':

				echo "<input id='{$args['id']}' class='{$args['class']}' name='wp_post_to_diaspora_options[{$args['name']}]' type='{$args['type']}' value='$value' />";

				break;

			case 'checkbox':

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

					echo "<input id='{$args['id']}' class='{$args['class']}' name='wp_post_to_diaspora_options[{$args['name']}][]' type='{$args['type']}' value='{$option['value']}' $checked /> {$option['label']}";
				}

				break;

			case 'select':
			case 'select-one':

				echo "<select id='{$args['id']}' class='{$args['class']}' name='wp_post_to_diaspora_options[{$args['name']}][]'>";

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

				echo "</select>";
				break;

			default:

				break;

		}
	}

}

?>
