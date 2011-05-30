<?php

	function postTodiaspora($username, $password, $message) {
		$processed_message = $message;
		$json_array = array('auth_token' => $password, 'text' => $processed_message, 'format' => 'json');
		$json_string = json_encode($json_array);
	    $host = "https://pivots.joindiaspora.com/activity_streams/notes.json";
		$resultArray = null;
		$diaspora_status = '';
		$ch = curl_init();

	    if ($ch !== false) {
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
				curl_setopt($ch, CURLOPT_URL, $host);
				curl_setopt($ch, CURLOPT_VERBOSE, 1);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				//curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
				curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
				curl_setopt($ch, CURLOPT_POSTFIELDS, $json_string);

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

?>
