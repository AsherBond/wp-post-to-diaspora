<?php

/**
 * Transmits a message from Wordpress to Diaspora.
 */
class Diaspora {

	const HTTP  = 'http';
	const HTTPS = 'https';

	const WP_MESSAGE_PUBLISHED_UPDATE = 1;
	const WP_MESSAGE_PUBLISHED        = 6;

	/**
	 * Fully qualified handle in the form of username@server_domain
	 * @var string
	 */
	private $handle;

	/**
	 * Password to the username
	 * @var string
	 */
	private $password;

	/**
	 * Message to send to the server instance
	 * @var string
	 */
	private $message;

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
	}

	public function setHandle( $handle ) {
		$this->handle = $handle;

		$handle_array = explode( '@', $handle, 2 );

		if ( count( $handle_array ) == 2 ) {	
			$this->username = $handle_array[0];
			$this->server_domain = $handle_array[1];
		}
	}

	public function setMessage( $message ) {
		$this->message = $message;
	}

	public function setPassword( $password ) {
		$this->password = $password;
	}

	public function setProtocol( $protocol ) {
		$this->protocol = $protocol;
	}

	/**
	 * Sends a WordPress post to a Diaspora server.
	 */
	function postToDiaspora() {
		$id                = get_the_ID();
		$processed_message = $this->message;

		if ( ( empty( $this->username ) ) || ( empty( $this->server_domain ) ) ) {
			$diaspora_status = 'Error posting to Diaspora.  Please use your full Diaspora Handle in the form of username@server_name.com';
		}
		else {
			$json_array = array('auth_token' => $this->password, 'text' => $processed_message, 'format' => 'json');
			$json_string = json_encode($json_array);
			$host = $this->protocol . '://' . $this->server_domain . '/activity_streams/notes.json';

			$resultArray = null;

			$ch = curl_init();

			if ($ch !== false) {
				curl_setopt_array($ch, array(
					CURLOPT_CONNECTTIMEOUT => 30,
					CURLOPT_URL => $host,
					CURLOPT_VERBOSE => 1,
					CURLOPT_RETURNTRANSFER => 1,
					//CURLOPT_USERPWD => "$username:$password",
					CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
					CURLOPT_POST => 1,
					CURLOPT_HTTPHEADER => array('Content-type: application/json'),
					CURLOPT_POSTFIELDS => $json_string
				));

				$result = curl_exec($ch);
				if ($result !== false) {
					$resultArray = curl_getinfo($ch);
				}
				else {
					$diaspora_status = 'Error posting to Diaspora. Error Code: ' . curl_error($ch);
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
