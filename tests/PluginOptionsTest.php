<?php

require 'class-plugin-options.php';

/**
 */
class PluginOptionsTest extends PHPUnit_Framework_TestCase {

	public function testRenderTextField() {

		$options = new PluginOptions();

		$result = $this->renderField($options, array('name' => 'username',
			'type' => 'text'
		));

		$this->assertStringStartsWith('<input', $result);	
		$this->assertContains("type='text'", $result);	
		$this->assertContains("name='username'", $result);	


		$options = new PluginOptions();

		$result = $this->renderField($options, array('class' => 'required',
			'name' => 'username',
			'type'  => 'text',
			'default_value' => 'robinson'
		));

		$this->assertStringStartsWith('<input', $result);	
		$this->assertContains("class='required'", $result);	
		$this->assertContains("name='username", $result);	
		$this->assertContains("type='text'", $result);	
		$this->assertContains("value='robinson'", $result);	

	}

	public function testRenderPasswordField() {

		$options = new PluginOptions();

		$result = $this->renderField($options, array('name' => 'username',
			'type' => 'password'
		));

		$this->assertStringStartsWith('<input', $result);	
		$this->assertContains("type='password'", $result);	
		$this->assertContains("name='username", $result);	
		

		$options = new PluginOptions();

		$result = $this->renderField($options, array('name' => 'username',
			'class' => 'required',
			'type' => 'password',
			'default_value' => 'Rz!j5Aklp7#'
		));

		$this->assertStringStartsWith('<input', $result);	
		$this->assertContains("class='required'", $result);	
		$this->assertContains("name='username'", $result);	
		$this->assertContains("type='password'", $result);	
		$this->assertContains("value='Rz!j5Aklp7#'", $result);	

	}

	public function testRenderCheckbox() {

		$options = new PluginOptions();

		$result = $this->renderField( $options, array(
			'type' => 'checkbox'
		));

		$this->assertEquals('', $result);	

		$options = new PluginOptions();

		$result = $this->renderField($options, array(
			'name' => 'choices',
			'options' => array(
					array('label' => 'Label_1',
					      'value' => 'Value_1')
			),
			'type' => 'checkbox'
		));

		$this->assertStringStartsWith('<input', $result);	
		$this->assertContains("type='checkbox'", $result);	
		$this->assertContains("name='choices[]'", $result);	
		$this->assertContains("value='Value_1'", $result);	
		$this->assertStringEndsWith("/> Label_1", $result);	

		$options = new PluginOptions();

		$result = $this->renderField($options, array(
			'name' => 'choices',
			'options' => array(
					array('label' => 'Label_1',
					      'value' => 'Value_1'),
					array('label' => 'Label_2',
					      'value' => 'Value_2'),
					array('label' => 'Label_3',
					      'value' => 'Value_3'),
			),
			'type' => 'checkbox'
		));

		$results = preg_split('/Label_[1-3]/', $result, -1, PREG_SPLIT_NO_EMPTY);
		$this->assertEquals(3, count($results));

		if ( is_array($results) ) {
			foreach ($results as $index => $result) {
				$result = trim($result);

				$this->assertStringStartsWith('<input', $result);	
				$this->assertContains("type='checkbox'", $result);	
				$this->assertContains("name='choices[]'", $result);	
				$this->assertContains("value='Value_" . ($index + 1) . "'", $result);	
				$this->assertStringEndsWith("/>", $result);	
			}
		}
	}

	public function testRenderSelect() {
		$options = new PluginOptions();

		$result = $this->renderField( $options, array(
			'name' => 'choice',
			'type' => 'select'
		));

		$this->assertStringStartsWith('<select', $result);	
		$this->assertNotContains('<option', $result);	
		$this->assertStringEndsWith('</select>', $result);	

		$options = new PluginOptions();

		$result = $this->renderField($options, array(
			'name' => 'choices',
			'options' => array(
					array('label' => 'Label_1',
					      'value' => 'Value_1'),
					array('label' => 'Label_2',
					      'value' => 'Value_2'),
					array('label' => 'Label_3',
					      'value' => 'Value_3'),
			),
			'type' => 'select'
		));

		$this->assertSelectCount('select', 1, $result);	
		$this->assertSelectCount('option', 3, $result);	

		$xml = new SimpleXmlElement($result);
		$index = 1;

		$this->assertContains('choices', (string) $xml['name']);

		foreach ($xml->option as $option) {
			$this->assertEquals('Label_' . $index, (string) $option[0]);
			$this->assertEquals('Value_' . $index, (string) $option['value']);

			++$index;
		}	
	}

	private function renderField( PluginOptions &$pluginOptions, array $field_args ) {
		ob_start();

		$pluginOptions->renderField( $field_args );

		$result = ob_get_contents();
		ob_end_clean();

		return $result;
	}

}

/**
 * Mock WP hook.
 */
function add_action( $a, $b ) {

}

/**
 * Mock WP hook.
 */
function get_option( $a ) {
	return array();
}

?>
