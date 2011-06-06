<?php

/**
 * Uses URL web shortening services to reduce the length of web address.
 */
class UrlShortener {

	/**
	 * Number of seconds to wait for a response from the service.
	 *
	 * @var int
	 */
	private $timeout = 10;

	function __construct() {

	}

	public function setTimeout( $timeout ) {
		$this->timeout = $timeout;
	}


	/**
	 * Shortens the length of a web address.
	 *
	 * @param string $service_name		The web service to use
	 * @param string $url			The web address to shorten
	 * @return string|bool			The shortened web address.  False on failure.
	 */
	public function shorten( $service_name, $url ) {
		switch ( $service_name ) {
			case 'goo.gl':
				$json_response = $this->sendHttpPostRequest( 'https://www.googleapis.com/urlshortener/v1/url',
									     $url, '{"longUrl": "' . $url . '"}' );
				$url = false;

				if ( ( $json_response !== false ) && ( isset( $json_response['id'] ) ) ) {
					$url = $json_response['id'];
				}
				break;
			case 'is.gd':
				$url = $this->sendHttpGetRequest( 'http://is.gd/create.php?format=simple&url=', $url );
				break;
			default:
				$url = $this->sendHttpGetRequest( 'http://tinyurl.com/api-create.php?url=', $url );
				break;
		}

		return $url;
	}

	/**
	 * Sends a HTTP GET request to a web service to shorten a web address.
	 *
	 * @param string $end_point	The web service url
	 * @param string $url		The web address to shorten
	 * @return string|false		The shortened url.  False is returned if the url
	 *				could not be shortened.
	 */
	private function sendHttpGetRequest( $end_point, $url ) {
		$ch    = curl_init();

		if ( $ch !== false ) {
			curl_setopt( $ch, CURLOPT_URL, $end_point . $url );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, $this->timeout );

			$url = curl_exec( $ch );

			curl_close( $ch );
		}

		return $url;
	}

	/**
	 * Sends a HTTP POST request to a web service to shorten a web address.
	 * At the moment it is assumed that request and response content
	 * is JSON.
	 *
	 * @param string $end_point		The web service url
	 * @param string $url			The web address to shorten
	 * @param string|array $post_fields	Fields to submit in the post request.
	 * @return mixed|false			The JSON response.  False is returned if the url
	 *					could not be shortened.
	 */
	private function sendHttpPostRequest( $end_point, $url, $post_fields ) {
		$ch             = curl_init();
		$json_response  = null;
		$response       = null;

		if ( $ch !== false ) {
			curl_setopt( $ch, CURLOPT_URL, $end_point );
			curl_setopt( $ch, CURLOPT_POST, true );
			curl_setopt( $ch, CURLOPT_POSTFIELDS, $post_fields );
			curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Content-Type: application/json' ) );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
			curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, $this->timeout );

			$response = curl_exec( $ch );

			curl_close( $ch );
		}

		if ( $response !== false ) {
			$json_response = json_decode( $response, true );
		}

		return $json_response;
	}

}

?>
