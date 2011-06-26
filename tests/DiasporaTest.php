<?php

require 'class-diaspora.php';

class DiasporaTest extends PHPUnit_Framework_TestCase {

	public function testPost() {
		$diaspora = new Diaspora();

		$response = $diaspora->postToDiaspora();
		$this->assertContains('Error posting to Diaspora.', $response);

		// @todo handle posting a test message to a Diaspora instance
	}

	public function testSetHandle() {
		$diaspora = new Diaspora();

		$diaspora->setHandle( 'test_user@test_server.com' );
		$this->assertAttributeEquals('test_user', 'username', $diaspora);
		$this->assertAttributeEquals('test_server.com', 'server_domain', $diaspora);

	}

}

/**
 * Mock WP functions.
 */
function add_filter( $a, $b ) {

}

function get_the_ID() {

}

function get_transient() {

}

function set_transient($a, $b, $c) {

}

?>
