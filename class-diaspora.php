<?php

/**
 * Transmits a message from Wordpress to Diaspora.
 */
class Diaspora {

	const HTTP  = 'http';
	const HTTPS = 'https';

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

	function __construct() {
		$this->protocol = self::HTTPS;
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
		$processed_message = $this->message;

		if ( ( empty( $this->username ) ) || ( empty( $this->server_domain ) ) ) {
			$diaspora_status = 'Error posting to Diaspora.  Please use your full Diaspora Handle in the form of username@server_name.com';
		}
		else {
			$json_array = array('auth_token' => $this->password, 'text' => $processed_message, 'format' => 'json');
			$json_string = json_encode($json_array);
			$host = $this->protocol . '://' . $this->server_domain . '/activity_streams/notes.json';

			$resultArray = null;
			$diaspora_status = '';

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
					$diaspora_status='You just posted!';
				} else {
					$diaspora_status="Error posting to Diaspora. Retry Code: {$resultArray['http_code']}";
				}
			}

			return $diaspora_status;
		}
	}

}

?>
