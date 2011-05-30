<?php
	/*
		 WP Post To diaspora
		 Info for WordPress:
		 ==============================================================================
		 Plugin Name: WP Post To diaspora
		 Plugin URI: http://www.skidoosh.co.uk/wordpress-plugins/wordpress-plugin-wp-post-to-diaspora/
		 Description: This Wordpress diaspora plugin is a nice simple plugin. All it does is post updates from your blog to your diaspora account when you create a fresh post and allows you to tweet from you blog. Some other features are:
		<ul>
    		<li>Auto shorten URL’s with tinyurl</li>
    		<li>Validation to make sure you can tweet or warns you if you cant</li>
    		<li>Automatic character limitation to make sure you don’t post anything over 140 characters</li>
    		<li>Good for SEO, creating automatic links to your blog and it’s posts</li>
    	</ul>

		Installation
		
		All you have to do is download V1 from the link below, unzip it, upload it to your plugins folder and activate it from the plugins menu.

		 Version: 1.5.1
		 Author: Glyn Mooney
		 Author URI: http://www.skidoosh.com/
	*/
	require_once dirname (__FILE__) . '/diaspora.php';	
	
	function wp_post_to_diaspora_install () {
		add_option ('wp_post_to_diaspora_diaspora_username', '');
		add_option ('wp_post_to_diaspora_diaspora_password', '');
	}
	
	function wp_post_to_diaspora_remove () {
		delete_option ('wp_post_to_diaspora_diaspora_username');
		delete_option ('wp_post_to_diaspora_diaspora_password');
	}
		
	function wp_post_to_diaspora_process_tweet ($tweet) {
		$pattern = '/(?#Protocol)(?:(?:ht|f)tp(?:s?)\:\/\/|~\/|\/)?(?#Username:Password)(?:\w+:\w+@)?(?#Subdomains)(?:(?:[-\w]+\.)+(?#TopLevel Domains)(?:com|org|net|gov|mil|biz|info|mobi|name|aero|jobs|museum|travel|[a-z]{2}))(?#Port)(?::[\d]{1,5})?(?#Directories)(?:(?:(?:\/(?:[-\w~!$+|.,=]|%[a-f\d]{2})+)+|\/)+|\?|#)?(?#Query)(?:(?:\?(?:[-\w~!$+|.,*:]|%[a-f\d{2}])+=?(?:[-\w~!$+|.,*:=]|%[a-f\d]{2})*)(?:&(?:[-\w~!$+|.,*:]|%[a-f\d{2}])+=?(?:[-\w~!$+|.,*:=]|%[a-f\d]{2})*)*)*(?#Anchor)(?:#(?:[-\w~!$+|.,*:=]|%[a-f\d]{2})*)?/';
		preg_match_all($pattern, $tweet, $matches);
		foreach ($matches[0] as $match) {
			$tweet = str_replace($match, wp_post_to_diaspora_shrink_url($match), $tweet);
		}
		return $tweet;
	}
	
	function wp_post_to_diaspora_process_tweet_to_string () {
		echo wp_post_to_diaspora_process_tweet($_POST['tweet']); die();
	}
	
	function wp_post_to_diaspora_shrink_url ($url) {
		$target = 'http://tinyurl.com/api-create.php?url=';
		$ch    = curl_init();
		curl_setopt($ch, CURLOPT_URL, $target . $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
		$url = curl_exec($ch);
		curl_close($ch);
		return $url;
	}
	
	function wp_post_to_diaspora_add_admin_page () {
		add_options_page ('Post To diaspora', 'Post To diaspora', 8, __FILE__, 'wp_post_to_diaspora_options');
	}
	
	function wp_post_to_diaspora_options () {
		$user = get_option ('wp_post_to_diaspora_diaspora_username');
		$pass = get_option ('wp_post_to_diaspora_diaspora_password');
		require_once 'wp-post-to-diaspora-options.php';
	}
	
	function wp_post_to_diaspora_post_to_diaspora ($postID) {
		if (!wp_is_post_revision($postID)) {
			require_once dirname(__FILE__) . '/diaspora.php';
			$user = get_option ('wp_post_to_diaspora_diaspora_username');
			$pass = get_option ('wp_post_to_diaspora_diaspora_password');
			$post = get_post ($postID);
			$str = '%s - %s';
			$tweet = sprintf ($str, $post->post_title, wp_post_to_diaspora_shrink_url(get_permalink ($postID)));
			if (strlen ($user) > 0 && strlen ($pass) > 0) {
				postTodiaspora ($user, $pass, $tweet);
			} else {
				//Just chillax :)
			}
		}
	}
	
	register_activation_hook(__FILE__, 'wp_post_to_diaspora_install');
	register_deactivation_hook(__FILE__, 'wp_post_to_diaspora_remove');
	add_action('admin_menu', 'wp_post_to_diaspora_add_admin_page');
	add_action('publish_post', 'wp_post_to_diaspora_post_to_diaspora');
	add_action('wp_ajax_js_shrink_urls', 'wp_post_to_diaspora_process_tweet_to_string');
?>
