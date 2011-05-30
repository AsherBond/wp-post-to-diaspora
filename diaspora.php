<?php
	function postTodiaspora($username,$password,$message){
		$processed_message = $message;
		$json_array = array(auth_token=>$password, text=> $processed_message, format => 'json');
		$json_string = json_encode($json_array);
	    $host = "https://pivots.joindiaspora.com/activity_streams/notes.json";
	    $ch = curl_init();
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
	    $resultArray = curl_getinfo($ch);
	    curl_close($ch);
	    if($resultArray['http_code'] == "200"){
	         $diaspora_status='You just tweeted!';
	    } else {
	         $diaspora_status="Error posting to Diaspora. Retry Code:".$resultArray['http_code'];
	    }
		return $diaspora_status;
	}
?>
