<?php

require 'class-url-shortener.php';

class UrlShortenerTest extends PHPUnit_Framework_TestCase {

	/**
	 * Tests third party external web address shorteners.
	 * Should be excluded from standard tests to limit requests sent to them.
	 *
	 * @group third_party_url_shorteners
	 */
	public function testShorten() {
		$urlShortener = new UrlShortener();
		$url = 'http://joindiaspora.com';

		$this->assertEquals('http://goo.gl/C7pJ', $urlShortener->shorten('goo.gl', $url));
		$this->assertEquals('http://is.gd/NVVvCS', $urlShortener->shorten('is.gd', $url));
		$this->assertEquals('http://tinyurl.com/34n8w73', $urlShortener->shorten('tinyurl.com', $url));

		$this->assertEquals('http://tinyurl.com/34n8w73', $urlShortener->shorten('UNSUPPORTED_SHORTENER', $url));
	}

}

?>
