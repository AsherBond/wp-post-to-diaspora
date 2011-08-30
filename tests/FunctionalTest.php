<?php

require_once 'PHPUnit/Extensions/SeleniumTestCase.php';

/**
 * Runs automatted functional tests using your web browser to ensure updating admin settings
 * and posting to Diaspora works as expected.  At the time of this writing, posting to Diaspora is
 * not working.
 *
 * Requires that Selenium 2.0 is installed (http://seleniumhq.org/download/). Run the stand-alone
 * server with: java -jar selenium-server-standalone-<version>.jar
 *
 * Copy application.xml.example to application.xml and fill in the appropiate credential and url information
 * for your test WordPress and Diaspora installations.
 */
class FunctionalTest extends PHPUnit_Extensions_SeleniumTestCase {

	private $d_id;
	private static $d_oauth2_identifier;
	private static $d_oauth2_secret;
	private $d_username;
	private $d_password;
	private $d_port;
	private $d_protocol;
	private $d_url;

	private $wp_username;
	private $wp_password;
	private $wp_url;

	/**
	 * Parses application.xml for Wordpress and Diaspora settings.
 	 */
	protected function setUp() {
		$config = simplexml_load_file( 'application.xml' );

		foreach ($config->sites->site as $site) {
			switch ( $site['type'] ) {
				case 'wordpress':
					$this->wp_username     = (string) $site->username;
					$this->wp_password     = (string) $site->password;
					$this->wp_url          = (string) $site->url;
					break;
				case 'diaspora':
					$this->d_username          = (string) $site->username;
					$this->d_password          = (string) $site->password;
					$this->d_port              = (string) $site->port;
					$this->d_url               = (string) $site->url;

					if ( count( preg_match( '@^(http|https)://([^/]+)@i', $this->d_url, $matches ) === 3 ) ) {
						$this->d_id    = $this->d_username . '@' . $matches[2];

						if ( strcasecmp( $matches[1], 'http' ) === 0 ) {
							$this->d_protocol  = 'http';
						}
						else if ( strcasecmp( $matches[1], 'https' ) === 0 ) {
							$this->d_protocol = 'https';
						}
					}

					if ( empty( $this->d_id ) ) {
						$this->fail( 'Diaspora url and/or username not set in application.xml' );
					}

					if ( empty( $this->d_password ) ) {
						$this->fail( 'Diaspora password not set in application.xml' );
					}

					if ( empty( $this->d_password ) ) {
						$this->fail( 'Diaspora url not set in application.xml' );
					}

					break;
			}
		}

		$this->setBrowser( (string) $config->browser );
		$this->setBrowserUrl( $this->wp_url );
	}

	/**
	 * Logs into WordPress.
	 */
	private function login() {
		$this->open( $this->wp_url . '/wp-admin' );
		$this->type( 'user_login', $this->wp_username );
		$this->type( 'user_pass', $this->wp_password );
		$this->clickAndWait( 'wp-submit' );
	}

	/**
	 * Logs into WordPress and navigates to Settings -> WP Post To Diaspora.
	 */
	private function loginAndBrowseToSettings() {
		$this->login();

		$this->clickAndWait( 'link=Settings' );
		$this->clickAndWait( 'link=WP Post To Diaspora' );
		$this->assertElementContainsText('css=div#wpbody-content h2', 'WP Post To Diaspora');
	}

	/**
 	 * Tests that valid admin settings are successfully saved and stored.
	 */
	public function testValidSettings() {
		$this->loginAndBrowseToSettings();

		$this->type('id', 'test@joindiaspora.com');
		$this->type('port', '');
		$this->type('oauth2_identifier', '123456');
		$this->type('oauth2_secret', 'abcdef');
		$this->check('protocol');
		$this->select('url_shortener', 'Is.gd');
		$this->clickAndWait( 'css=input[value="Save Changes"]' );
sleep(5);
		$this->assertElementContainsText('css=div#setting-error-settings_updated strong', 'Settings saved');

		$this->clickAndWait( 'link=Posts' );
		$this->clickAndWait( 'link=Settings' );
		$this->clickAndWait( 'link=WP Post To Diaspora' );

		$this->assertElementValueEquals('id', 'test@joindiaspora.com');
		$this->assertChecked('protocol');
		$this->assertSelected('url_shortener', 'Is.gd');

		$this->type('id', 'test@subdomain.joindiaspora.com');
		$this->clickAndWait( 'css=input[value="Save Changes"]' );
		$this->assertElementContainsText('css=div#setting-error-settings_updated strong', 'Settings saved');

		$this->type( 'port', '3000' );
		$this->clickAndWait( 'css=input[value="Save Changes"]' );
		$this->assertElementContainsText( 'css=div#setting-error-settings_updated strong', 'Settings saved' );
	}

	/**
	 * Tests that a partial or empty id generates an error.
	 */
	public function testSettingsIdErrors() {
		$this->loginAndBrowseToSettings();

		$this->type('id', 'test');
		$this->clickAndWait( 'css=input[value="Save Changes"]' );
		$this->assertElementPresent('css=div#setting-error-id_error strong');

		$this->type('id', '');
		$this->clickAndWait( 'css=input[value="Save Changes"]' );
		$this->assertElementPresent('css=div#setting-error-id_error strong');
	}

	/**
	 * Tests for invalid port number settings.
	 */
	public function testSettingsPortErrors() {
		$this->loginAndBrowseToSettings();

		$this->type('port', '70000');
		$this->clickAndWait( 'css=input[value="Save Changes"]' );
		$this->assertElementPresent('css=div#setting-error-port_error strong');
		
		$this->type('port', 'Seventy thousand');
		$this->clickAndWait( 'css=input[value="Save Changes"]' );
		$this->assertElementPresent('css=div#setting-error-port_error strong');

		$this->type('port', '-80');
		$this->clickAndWait( 'css=input[value="Save Changes"]' );
		$this->assertElementPresent('css=div#setting-error-port_error strong');
	}

	/**
	 * Tests that a missing password generates a warning on the
	 * Posts -> Add page.
	 *
	 * @depends testSettingsPasswordError
	 */
	public function testAddPostMissConfigured() {
		$this->login();

		$this->clickAndWait( 'link=Posts' );
		$this->clickAndWait( 'link=Add New' );

		$this->assertElementPresent('css=p[class="diaspora-warning"]');
	}

	/**
	 * Preregisters a WordPress blog on Diaspora.
	 * The user must be an admistrator of the Diaspora server
	 * for this to work.
	 */
	public function testPreregisterApplication() {
		$host = $this->d_url;

		if ( !empty($this->d_port) ) {
			$host = $this->d_url . ':' . $this->d_port;
		}

		$this->open( $host );

		$this->clickAndWait( 'link=Log In' );

		$this->loginDiaspora();

		$this->click( 'css=ul#user_menu' );
		$this->clickAndWait( 'link=admin' );

		$this->retrieveOAuth2Credentials();

		if ((empty(self::$d_oauth2_identifier)) || (empty(self::$d_oauth2_secret))) {
			$this->type('o_auth2_provider_models_active_record_client_name', 'blog');
			$this->type('o_auth2_provider_models_active_record_client_description', 'My Blog');
			$this->type('o_auth2_provider_models_active_record_client_application_base_url', $this->wp_url);

			$this->clickAndWait('o_auth2_provider_models_active_record_client_submit');

			$this->retrieveOAuth2Credentials();
		}

		$this->assertNotEmpty(self::$d_oauth2_identifier);
		$this->assertNotEmpty(self::$d_oauth2_secret);
	}

	/**
	 * Retrieves the OAuth2 credentials from the current page.
	 * Assumes that user is at the admin page.
	 */
	private function retrieveOAuth2Credentials() {
		$html_body = $this->getText('css=body');
		preg_match('/oauth_identifier: "([A-z0-9]*)"/', $html_body, $matches);
		if (count($matches) == 2) {
			self::$d_oauth2_identifier = $matches[1];
		}
		preg_match('/oauth_secret: "([A-z0-9]*)"/', $html_body, $matches);
		if (count($matches) == 2) {
			self::$d_oauth2_secret = $matches[1];
		}
	}

	/**
	 * Tests an OAuth2 authorization and token request.
	 *
	 * @depends testPreregisterApplication
	 */
	public function testConnectDiaspora() {
		$this->loginAndBrowseToSettings();

		$this->enterValidSettings();

		$this->clickAndWait( 'css=input[value="Save Changes"]' );

		$this->assertElementPresent( "xpath=//a[contains(@href,'/oauth/authorize')]" );

		$this->clickAndWait( "xpath=//a[contains(@href,'/oauth/authorize')]" );

		$this->assertElementContainsText( 'css=div#flash_alert', 'You need to sign in or sign up before continuing.' );

		$this->loginDiaspora();
		$this->clickAndWait( 'css=input[value="Authorize"]' );

		$this->assertTextPresent( 'Connection successful' );
	}

	/**
	 * Tests sending a message to Diaspora.
	 */
	public function testAddPostAndPublishToDiaspora() {
		$this->loginAndBrowseToSettings();

		$this->enterValidSettings();

		$this->clickAndWait( 'css=input[value="Save Changes"]' );
		$this->assertElementContainsText( 'css=div#setting-error-settings_updated strong', 'Settings saved' );

		$this->clickAndWait( 'link=Posts' );
		$this->clickAndWait( 'link=Add New' );

		$this->assertElementNotPresent( 'css=p[class="diaspora-warning"]' );
		$this->assertElementPresent( 'css=img#diaspora[class="diaspora-faded"]' );
		$this->assertElementValueEquals( 'wp_post_to_diaspora_options_share_with[diaspora]', '0' );

		$this->type( 'title', 'Test Title' );
		$this->type( 'tinymce', 'This is a test post from my WordPress blog.' );

		$this->click( 'css=img#diaspora' );
		$this->assertElementNotPresent( 'css=img#diaspora[class="diaspora-faded"]' );
		$this->assertElementValueEquals( 'wp_post_to_diaspora_options_share_with[diaspora]', '1' );

		$this->clickAndWait( 'publish');
		$this->assertElementPresent( 'wpbody-content div#message' );
		$this->assertElementContainsText( 'wpbody-content div#message', 'Posted to Diaspora successfully' );
	}

	private function enterValidSettings() {
		$this->type('id', $this->d_id);
		$this->type('oauth2_identifier', self::$d_oauth2_identifier);
		$this->type('oauth2_secret', self::$d_oauth2_secret);
		$this->type('port', $this->d_port);

		if ( ( $this->d_protocol == 'https' ) ) {
			$this->check('protocol');
		}
		else if ( ( $this->d_protocol == 'http' ) ) {
			$this->uncheck('protocol');
		}
	}

	private function loginDiaspora() {
		$this->type( 'user[username]', $this->d_username );
		$this->type( 'user[password]', $this->d_password );
		$this->clickAndWait( 'user_submit' );
	}

}

?>
