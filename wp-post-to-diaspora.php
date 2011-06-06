<?php
	/*
		 WP Post To diaspora
		 Info for WordPress:
		 ==============================================================================
		 Plugin Name: WP Post To diaspora
     Plugin URI: http://github.com/maxwell/wp-post-to-diaspora
     Description: beta plugin to post your new blog posts straight to your diaspora account 
		Installation
		
		All you have to do is download V1 from the link below, unzip it, upload it to your plugins folder and activate it from the plugins menu.

    Version: 1.0.0a
    Author: Maxwell Salzberg

    Based on: http://www.skidoosh.co.uk/wordpress-plugins/wordpress-plugin-wp-post-to-twitter/

		Original-Author: Glyn Mooney
		Original-Author URI: http://www.skidoosh.com/
	*/
	require_once dirname (__FILE__) . '/class-diaspora.php';
	require_once dirname (__FILE__) . '/class-diaspora-options.php';
	require_once dirname (__FILE__) . '/class-url-shortener.php';

	$diasporaOptions = new DiasporaOptions();
	
	function wp_post_to_diaspora_install () {
		add_option ('wp_post_to_diaspora_options', '');
	}
	
	function wp_post_to_diaspora_remove () {
		delete_option ('wp_post_to_diaspora_options');
	}

	function wp_post_to_diaspora_process_content($content) {
		$options = get_option ( 'wp_post_to_diaspora_options' );
		$urlShortener    = new UrlShortener();

		$url_shortener  = $options['url_shortener'];
		$pattern = '/(?#Protocol)(?:(?:ht|f)tp(?:s?)\:\/\/|~\/|\/)?(?#Username:Password)(?:\w+:\w+@)?(?#Subdomains)(?:(?:[-\w]+\.)+(?#TopLevel Domains)(?:com|org|net|gov|mil|biz|info|mobi|name|aero|jobs|museum|travel|[a-z]{2}))(?#Port)(?::[\d]{1,5})?(?#Directories)(?:(?:(?:\/(?:[-\w~!$+|.,=]|%[a-f\d]{2})+)+|\/)+|\?|#)?(?#Query)(?:(?:\?(?:[-\w~!$+|.,*:]|%[a-f\d{2}])+=?(?:[-\w~!$+|.,*:=]|%[a-f\d]{2})*)(?:&(?:[-\w~!$+|.,*:]|%[a-f\d{2}])+=?(?:[-\w~!$+|.,*:=]|%[a-f\d]{2})*)*)*(?#Anchor)(?:#(?:[-\w~!$+|.,*:=]|%[a-f\d]{2})*)?/';
		preg_match_all($pattern, $content, $matches);

		if ((isset($matches[0])) && (is_array($matches[0]))) {
			foreach ($matches[0] as $match) {
				$shortened_url = $urlShortener->shorten($url_shortener, $match);
				if ($shortened_url !== false) {
					$content = str_replace($match, $urlShortener->shorten($url_shortener, $match), $content);
				}
			}
		}

		return $content;
	}
	
	function wp_post_to_diaspora_process_content_to_string () {
		echo wp_post_to_diaspora_process_content($_POST['content']);

		die();
	}

	function wp_post_to_diaspora_add_admin_page () {
		add_options_page ('Post To diaspora', 'Post To diaspora', 'manage_options', 'wp-post-to-diaspora', 'wp_post_to_diaspora_options');
	}
	
	function wp_post_to_diaspora_options () {
		$options = get_option( 'wp_post_to_diaspora_options' );

		$handle = $options['handle'];
		$pass = $options['password'];
		$protocol = $options['protocol'];

		require_once 'wp-post-to-diaspora-options.php';
	}
	
	function wp_post_to_diaspora_post_to_diaspora ($postID) {
		if (!wp_is_post_revision($postID)) {
			$options = get_option ( 'wp_post_to_diaspora_options' );
			$urlShortener    = new UrlShortener();

			$handle		= $options['handle'];
			$pass		= $options['password'];
			$protocol	= $options['protocol'];
			$url_shortener  = $options['url_shortener'];

			$post = get_post ($postID);
			$str = '%s - %s';
			$permalink = get_permalink($postID);

			$shortened_url = $urlShortener->shorten( $url_shortener, $permalink );
			if ($shortened_url !== false) {
				$content = sprintf($str, $post->post_title, $shortened_url);
			}
			else {
				$content = sprintf($str, $post->post_title, $permalink);
			}

			if ((!empty($handle))  && (!empty($pass))) {
				$diaspora = new Diaspora();

				$diaspora->setHandle( $handle );
				$diaspora->setPassword( $pass );
				$diaspora->setMessage( $content );
				$diaspora->setProtocol( $protocol );

				$diaspora->postToDiaspora();
			} else {
				//Just chillax :)
			}
		}
	}
	
	register_activation_hook(__FILE__, 'wp_post_to_diaspora_install');
	register_deactivation_hook(__FILE__, 'wp_post_to_diaspora_remove');
	add_action('publish_post', 'wp_post_to_diaspora_post_to_diaspora');
	add_action('wp_ajax_js_shrink_urls', 'wp_post_to_diaspora_process_content_to_string');
?>
