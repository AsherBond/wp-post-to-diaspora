<?php

require_once 'class-logger.php';
require_once 'libraries/libdiaspora-php/load.php';

/**
 * Transmits a message from Wordpress to Diaspora.
 */
class Diaspora {

	const HTTP       = 'http';
	const HTTPS      = 'https';
	const PORT_HTTP  = 80;
	const PORT_HTTPS = 443;

	const WP_AVATAR_SIZE              = 50;
	const WP_MESSAGE_PUBLISHED_UPDATE = 1;
	const WP_MESSAGE_PUBLISHED        = 6;

	/**
	 * Fully qualified id in the form of username@server_domain
	 * @var string
	 */
	private $id;

	/**
	 * Activity to send to the server instance
	 * @var DiasporaStreams_Activity
	 */
	private $activity;

	private $oauth2_access_token;
	private $oauth2_authorization_grant;
	private $oauth2_identifier;
	private $oauth2_secret;

	/**
	 * Domain name of the server
	 * @var string
	 */
	private $server_domain;

	/**
	 * Username to sign into the server
	 * @var string
	 */
	private $username;

	/**
	 * Port number to connect to
	 * @var int
	 */
	private $port;

	private $post_id;

	private $protocol;

	/**
	 * Prefix identifier to hold transient (cached) information.
	 *
	 * WordPress does not use sessions.  I prefer not use GET parameters or
	 * cookies to send success/error messages between page requests.
	 * @var string
	 */
	private $transient_name_prefix = 'wp_post_to_diaspora';

	function __construct() {
		$this->protocol = self::HTTPS;

		add_filter( 'post_updated_messages', array( &$this, 'diasporaPostUpdatedMessages' ), 10, 1 );

		$this->logger = Logger::getInstance();
		$this->logger->setFileName( '/tmp/wp-post-to-diaspora.log' );
		$this->logger->setLevel( Logger::DEBUG );
	}

	/**
	 * Make an access token request to the Diaspora server.
	 */
	public function getToken() {
		$host  = $this->getHost();
		$token = null;

		$credentials = array('key'    => $this->oauth2_identifier,
							 'secret' => $this->oauth2_secret);

		$app   = new DiasporaApplication( 'WP Post To Diaspora', $host, $credentials );
		$oauth = new DiasporaOauth( $app );

		$oauth->_scope = 'limited';

		// Diaspora requires the callback uri used in the initial authorization
		// grant request.  Remove the return code.
		$redirect_uri = DiasporaUtils::currentURI();
		$redirect_uri = preg_replace( '/&?code=[^&]*/', '', $redirect_uri );

		try {
			$this->logger->log( "Retrieving oauth2 tokens using redirect_uri of $redirect_uri." );

			$token = $oauth->getAccessToken( $_GET['code'], $redirect_uri );
			add_settings_error( 'general', 'settings_updated', 'Connection successful.', 'updated' );

			$this->logger->log( "Token is " . print_r($token, true) );
		}
		catch (DiasporaException $e) {
			add_settings_error( 'id', 'id', $e->message );
		}

		return $token;
	}

	/**
	 * Make an OAuth2 authorization grant to the Diaspora server.
	 */
	public function getAuthorizationURI() {
		$host = $this->getHost();

		$credentials = array('key'    => $this->oauth2_identifier,
		                     'secret' => $this->oauth2_secret);

		$app   = new DiasporaApplication( 'WP Post To Diaspora', $host, $credentials );
		$oauth = new DiasporaOauth( $app );

		$oauth->_scope = 'limited';

		return $oauth->getAuthorizationURI();
	}

	/**
	 * @todo Append shortened link to $blog->displayName 
	 */
	private function createActivity() {
		$author_avatar    = null;
		$matches          = array();
		$post             = get_post( $this->post_id );
		$post_date_ts     = strtotime( $post->post_date );
		$permalink        = get_permalink( $this->post_id );
		$activity_blog_id = 'tag:' . preg_replace( '/(http|https):\/\//i', '', get_home_url() )
		                    . ',' . date( 'Y', $post_date_ts );

		$avatar_img_html = get_avatar( get_the_author_meta('user_email'), self::WP_AVATAR_SIZE );
		$avatar_img_url  = '';

		preg_match( "/src='([^']+)'/", $avatar_img_html, $matches );
		if ( isset( $matches[1] ) ) {
			$avatar_img_url = $matches[1];
		}

		$activity = new DiasporaStreams_Activity(array(
			'published' => $post_date_ts,
			'verb'      => 'post'
		));

		if ( !empty($avatar_img_url) ) {
			$author_avatar = new DiasporaStreams_MediaLink(array(
				'height' => self::WP_AVATAR_SIZE,
				'width'  => self::WP_AVATAR_SIZE,
				'url'    => $avatar_img_url
			));
		}

		$author = new DiasporaStreams_ActivityObject(array(
			'url'         => get_the_author_meta( 'user_url', $post->post_author ),
			'displayName' => get_the_author_meta( 'display_name', $post->post_author ),
			'image'       => $author_avatar
		));

		$blog = new DiasporaStreams_ActivityObject(array(
			'content'     => $post->post_content,
			'displayName' => $post->post_title,
			'id'          => $activity_blog_id . ':' . $permalink,
			'objectType'  => 'article',
			'url'         => $permalink
		));

		$activity->actor  = $author;
		$activity->object = $blog;

		$target = new DiasporaStreams_ActivityObject(array(
			'displayName' => get_bloginfo( 'name' ),
			'id'          => $activity_blog_id . ':blog',
			'objectType'  => 'blog',
			'url'         => get_home_url()
		));

		$activity->target = $target;
		$this->activity = $activity;
		$this->activity->validate();
	}

	public function getHost() {
		$host = $this->protocol . '://' . $this->server_domain;

		if ( ( $this->port !== self::PORT_HTTP ) && ( $this->port !== self::PORT_HTTPS ) && ( !empty( $this->port ) ) ) {
			$host .= ':' . $this->port;
		}

		return $host;
	}

	public function setId( $id ) {
		$this->id = $id;

		$id_array = explode( '@', $id, 2 );

		if ( count( $id_array ) == 2 ) {	
			$this->username = $id_array[0];
			$this->server_domain = $id_array[1];
		}
	}

	public function setOauth2AccessToken( $oauth2_access_token ) {
		$this->oauth2_access_token = $oauth2_access_token;
	}

	public function setOauth2AuthorizationGrant( $oauth2_authorization_grant ) {
		$this->oauth2_authorization_grant = $oauth2_authorization_grant;
	}

	public function setOauth2Identifier( $oauth2_identifier ) {
		$this->oauth2_identifier = $oauth2_identifier;
	}

	public function setOauth2Secret( $oauth2_secret ) {
		$this->oauth2_secret = $oauth2_secret;
	}

	public function setPort( $port ) {
		$this->port = $port;
	}

	public function setPostId( $post_id ) {
		$this->post_id = $post_id;
	}

	public function setProtocol( $protocol ) {
		$this->protocol = $protocol;

		if (empty($this->port)) {
			if ( $protocol === self::HTTPS ) {
				$this->port = self::PORT_HTTPS;
			}
			else if ( $protocol === self::HTTP) {
				$this->port = self::PORT_HTTP;
			}
		}
	}

	/**
	 * Sends a WordPress post to a Diaspora server.
	 */
	public function postToDiaspora() {
		$diaspora_status   = '';
		$id                = $this->post_id;

		if ( ( empty( $this->username ) ) || ( empty( $this->server_domain ) ) ) {
			$diaspora_status = 'Error posting to Diaspora.  Please use your full Diaspora ID in the form of username@server_name.com';
		}
		else {
			$this->createActivity();

			if ( $this->activity->getLastError() ) {
				$diaspora_status = 'Error creating Diaspora activity.  ' . $this->activity->getLastError();
			}
			else {
				$json_string = $this->activity->encode();

				$activity_url  = $this->getHost() . '/activity_streams/notes.json';

				$resultArray = null;

				$ch = curl_init();

				if ($ch !== false) {
					curl_setopt_array($ch, array(
						CURLOPT_CONNECTTIMEOUT => 30,
						CURLOPT_URL => $activity_url,
						CURLOPT_VERBOSE => 1,
						CURLOPT_RETURNTRANSFER => 1,
						CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
						CURLOPT_POST => 1,
						CURLOPT_HTTPHEADER => array(
							'Content-type: application/json',
							'Authorization: OAuth ' . $this->access_token
						),
						CURLOPT_POSTFIELDS => $json_string,
					));

					$result = curl_exec($ch);

					$this->logger->log( "Posting to URL: $host" );
					$this->logger->log( "Sending activity: $json_string" );

					if ($result !== false) {
						$resultArray = curl_getinfo($ch);
						$this->logger->log( "Server response: " . print_r($resultArray, true) );
					}
					else {
						$diaspora_status = 'Error posting to Diaspora. Error Code: ' . curl_error($ch);
						$this->logger->log( "Server error: " . curl_error($ch), Logger::ERROR );
					}

					curl_close($ch);
				}
				else {
					$diaspora_status = 'Error creating a cURL resource.';
				}

				if (is_array($resultArray)) {
					if ($resultArray['http_code'] == "200") {
						$diaspora_status = 'Posted to Diaspora successfully.';
					}
					else {
						$diaspora_status = "Error posting to Diaspora. Retry Code: {$resultArray['http_code']}";
					}
				}
			}
		}

		set_transient( $this->transient_name_prefix . '_diaspora_status_' . $id, $diaspora_status, 60 );

		return $diaspora_status;
	}

	/**
	 * Append the return status from Diaspora to the published or updated message.
	 *
	 * @param $message Status messages for post and page actions.  Refer to the
	 *                 message array declared in wp-admin/edit-form-advanced.php 
	 * @return array   A message array with the Diaspora return status appended to
	 *                 the text of WordPress return codes of 4 (updated) and 
	 *                 6 (an update to a published post).
	 */
	public function diasporaPostUpdatedMessages( $messages ) {
		$wp_message = '';

		if ( isset( $_GET['message'] ) ) {
			$wp_message = $_GET['message'];
		}

		if ( ( $wp_message == self::WP_MESSAGE_PUBLISHED_UPDATE ) ||
		     ( $wp_message == self::WP_MESSAGE_PUBLISHED) ) {

			$id = get_the_ID();
			$diaspora_status = get_transient( $this->transient_name_prefix . '_diaspora_status_' . $id );

			if ( !empty( $diaspora_status) ) {
				delete_transient( $this->transient_name_prefix . '_diaspora_status_'  . $id);

				$messages['post'][self::WP_MESSAGE_PUBLISHED_UPDATE] .= '. ' . $diaspora_status;
				$messages['post'][self::WP_MESSAGE_PUBLISHED] .= '. ' . $diaspora_status;
			}

		}

		return $messages;
	}

}

?>
