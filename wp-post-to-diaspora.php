<?php
/*
 Plugin Name: WP Post To Diaspora
 Plugin URI: http://github.com/diaspora/wp-post-to-diaspora
 Description: Beta plugin to post your new blog posts straight to your diaspora account.
 Version 1.0.0.a
 Author: Maxwell Salzberg

 Significantly revised version of: http://www.skidoosh.co.uk/wordpress-plugins/wordpress-plugin-wp-post-to-twitter/

 Original-Author: Glyn Mooney
 Original-Author URI: http://www.skidoosh.com/
*/

	require_once dirname (__FILE__) . '/class-diaspora.php';
	require_once dirname (__FILE__) . '/class-diaspora-options.php';
	require_once dirname (__FILE__) . '/class-url-shortener.php';

	$diaspora        = new Diaspora();
	$diasporaOptions = new DiasporaOptions();
	
	function wp_post_to_diaspora_install () {
		add_option ('wp_post_to_diaspora_options', '');
	}
	
	function wp_post_to_diaspora_remove () {
		delete_option ('wp_post_to_diaspora_options');
	}

	function wp_post_to_diaspora_post_to_diaspora ($postID) {

		if (( isset( $_POST['wp_post_to_diaspora_options_share_with']['diaspora'] ) ) 
			&& ( $_POST['wp_post_to_diaspora_options_share_with']['diaspora'] === '1' ) ) {

			if (!wp_is_post_revision($postID)) {
				$options = get_option ( 'wp_post_to_diaspora_options' );

				$id                  = $options['id'];
				$oauth2_access_token = $options['oauth2_access_token'];

				if ((!empty($id))  && (!empty($oauth2_access_token))) {
					$urlShortener    = new UrlShortener();
					$urlShortener->setServiceName( $options['url_shortener'] );

					$diaspora = new Diaspora();

					$diaspora->setId( $id );
					$diaspora->setOauth2AccessToken( $oauth2_access_token );
					$diaspora->setPort( $options['port'] );
					$diaspora->setProtocol( $options['protocol'] );
					$diaspora->setPostId( $postID );
					$diaspora->setUrlShortener( $urlShortener );

					$diaspora->postToDiaspora();
				} else {
					//Just chillax :)
				}
			}
		}

	}
	
	register_activation_hook(__FILE__, 'wp_post_to_diaspora_install');
	register_deactivation_hook(__FILE__, 'wp_post_to_diaspora_remove');
	add_action('publish_post', 'wp_post_to_diaspora_post_to_diaspora');

?>
