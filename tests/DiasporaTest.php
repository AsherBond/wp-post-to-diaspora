<?php

require_once 'class-diaspora.php';

class DiasporaTest extends PHPUnit_Framework_TestCase {

	public function testPost() {
		$diaspora = new Diaspora();

		$response = $diaspora->postToDiaspora();
		$this->assertContains('Error posting to Diaspora.', $response);

		// @todo handle posting a test message to a Diaspora instance
	}

	public function testSetId() {
		$diaspora = new Diaspora();

		$diaspora->setId( 'test_user@test_server.com' );
		$this->assertAttributeEquals('test_user', 'username', $diaspora);
		$this->assertAttributeEquals('test_server.com', 'server_domain', $diaspora);

	}

}

?>
