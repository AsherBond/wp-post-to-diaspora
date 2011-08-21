<?php

require 'class-plugin-options.php';

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

	public function testValidateFilter() {
		$method = new ReflectionMethod( 'PluginOptions', 'validateFilter' );
		$method->setAccessible( true );

		$options = new PluginOptions();

		$field_args = array(
			'label'    => 'Test Field',
			'name'     => 'a_field',
			'validate' => array(
				'filter'       => FILTER_VALIDATE_EMAIL,
				'filter_error' => 'Invalid field.'
			)
		);

		$options->setFieldArgsById( 'a_field', $field_args );

		$this->assertFalse( $method->invoke($options, 'a_field', 'invalid_email' ) );
		$this->assertTrue( $method->invoke($options, 'a_field', 'valid.email@localhost.localdomain' ) );

		$options = new PluginOptions();

		$field_args = array(
			'label'    => 'Test Field',
			'name'     => 'a_field',
			'validate' => array(
				'filter'       => FILTER_VALIDATE_INT,
				'filter_error' => 'Invalid field.',
				'filter_options' => array('min_range' => 1)
			)
		);

		$options->setFieldArgsById( 'a_field', $field_args );
		$this->assertFalse( $method->invoke($options, 'a_field', -1 ) );
		$this->assertFalse( $method->invoke($options, 'a_field', 'invalid_int' ) );
		$this->assertTrue( $method->invoke($options, 'a_field', 32 ) );
		
	}

	public function testValidateRegex() {
		$method = new ReflectionMethod( 'PluginOptions', 'validateRegex' );
		$method->setAccessible( true );

		$options = new PluginOptions();

		$field_args = array(
			'label'    => 'Test Field',
			'name'     => 'a_field',
			'validate' => array(
				'regex'       => '/^exact-match$/',
				'regex_error' => 'Invalid field.'
			)
		);

		$options->setFieldArgsById( 'a_field', $field_args );

		$this->assertFalse( $method->invoke($options, 'a_field', 'not-an-exact-match' ) );
		$this->assertTrue( $method->invoke($options, 'a_field', 'exact-match' ) );
	}

	public function testValidateRequired() {
		$method = new ReflectionMethod( 'PluginOptions', 'validateRequired' );
		$method->setAccessible( true );

		$options = new PluginOptions();

		$field_args = array(
			'label'    => 'Test Field',
			'name'     => 'a_field',
			'validate' => array(
				'required' => true
			)
		);

		$this->assertFalse( $method->invoke($options, 'a_field', '', &$field_args ) );
		$this->assertTrue( $method->invoke($options, 'a_field', 'a_value', &$field_args ) );
	}


	private function renderField( PluginOptions &$pluginOptions, array $field_args ) {
		ob_start();

		$pluginOptions->renderField( $field_args );

		$result = ob_get_contents();
		ob_end_clean();

		return $result;
	}

}

?>
