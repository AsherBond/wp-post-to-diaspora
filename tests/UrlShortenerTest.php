<?php

require_once 'class-url-shortener.php';

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

		$urlShortener->setServiceName('goo.gl');
		$this->assertEquals('http://goo.gl/4mhy', $urlShortener->shorten($url));

		$urlShortener->setServiceName('is.gd');
		$this->assertEquals('http://is.gd/NVVvCS', $urlShortener->shorten($url));

		$urlShortener->setServiceName('tinyurl.com');
		$this->assertEquals('http://tinyurl.com/34n8w73', $urlShortener->shorten($url));

		$urlShortener->setServiceName('UNSUPPORTED_SHORTENER');
		$this->assertEquals('http://tinyurl.com/34n8w73', $urlShortener->shorten($url));
	}

}

?>
