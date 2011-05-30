<?php
	if (isset ($_POST['tweet']) ) {
		if (strlen($user) > 0 && strlen($pass) > 0) {
			$tweet = trim ($_POST['tweet']);
			if (strlen ($tweet) > 0 && strlen ($tweet) < 140) {
				require_once dirname (__FILE__ . '/diaspora.php');
				$twmessage = __(postTodiaspora ($user, $pass, wp_post_to_diaspora_process_tweet($tweet)));
				if ($twmessage == 'Error posting to diaspora. Retry') {
					echo '<div id="notice" class="error"><p>' . $twmessage . '</p></div>';
				} else {
					echo '<div id="notice" class="updated fade"><p>' . $twmessage . '</p></div>';
				}
			} else {
				echo '<div id="notice" class="error"><p>' . __('Your tweet must be greater than 0 characters long and less than 140') . '</p></div>';
			}
		} else {
			echo '<div id="notice" class="error"><p>' . __('Please enter your diaspora username and password.') . '</p></div>';
		}
	}
?>
	<style title="text/css">
		.diaspora-mimic, .diaspora-mimic tr, .diaspora-mimic th, .diaspora-mimic td, .diaspora-mimic h3, .diaspora-mimic p {
			margin: 0;
			padding: 0;
		}
		.diaspora-mimic h3 {
			width: 400px;
			font-size: 20px; 
			color: #333;
		}
		.diaspora-mimic p {
			margin-top: -5px;
			font-size: 24px;
			color: #ccc;
			width: 85px;
			text-align: right;
		}
	</style>
   	<div class="wrap">
	    <h2>WP Post To diaspora</h2>
		<form method="post" action="options.php">
        	<?php wp_nonce_field('update-options'); ?>
            <table id="diaspora-setting-form" class="form-table">
                <tr>
                    <th scope="row"><label for="diaspora_username"><?=__('Diaspora Username');?></label></th>
                    <td><input type="text" class="regular-text" name="wp_post_to_diaspora_diaspora_username" id="diaspora_username" value="<?=$user;?>" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="diaspora_password"><?=__('Diaspora Password');?></label></th>
                    <td><input type="password" class="regular-text" name="wp_post_to_diaspora_diaspora_password" id="diaspora_password" value="<?=$pass;?>" /></td>
                </tr>
            </table>
            	<input type="hidden" name="action" value="update" />
            	<input type="hidden" name="page_options" value="wp_post_to_diaspora_diaspora_username,wp_post_to_diaspora_diaspora_password" />
                <p class="submit"><input type="submit" name="update" value="<?=__('Save Changes');?>" /></p>
            </form>
            <form id="tweet-form" method="post" action="?page=wp-post-to-diaspora%2Fwp-post-to-diaspora.php">
            	<table class="form-table">
            		<tr>
            			<th scope="row"></th>
						<td>
							<table class="diaspora-mimic">
								<tr>
									<th scope="row">
										<h3>What are you doing?</h3>
									</th>
									<td class="diaspora-word-limit">
										<p>140</p>
									</td>
								</tr>
							</table>
						</td>
					</tr>
                   	<tr>
                       	<th scope="row"><label for="tweet"><?=__('Message');?></label>  <a href="#" id="shrink"><?=__('Shrink URL\'s');?></a></th>
                        <td><textarea name="tweet" id="tweet" cols="57" rows="5"></textarea></td>
                    </tr>
                </table>
                <p class="submit"><input class="button-primary" type="submit" name="submit" value="<?=__('Tweet');?>" /></p>
            </form>
        </div>
		<script type="text/javascript">
			<!--//
				(function($){
					$(document).ready(function(){
						var max_chars = 140;
						$('#tweet').bind('keyup', function(e){
							var tweet = $(this);
							var total = tweet.val().length;
							var isnow = max_chars - total;
							$('.diaspora-word-limit p').text(isnow);
							if (isnow <= 0) {
								tweet.val(tweet.val().substr(0, max_chars - 1));
							}
						});
						$('#shrink').bind('click', function(){
							var tweet   = $('#tweet').val();
							var re      = new RegExp(/(((ht|f)tp(s?))\:\/\/)?(www.|[a-zA-Z].)[a-zA-Z0-9\-\.]+\.(com|edu|gov|mil|net|org|biz|info|name|museum|us|ca|uk|ly)(\:[0-9]+)*(\/($|[a-zA-Z0-9\.\,\;\?\'\\\+&amp;%\$#\=~_\-]+))*/gi);
							var matches = re.exec(tweet);
							if (matches == null) return;
							if (matches.length > 0) {
								$('#tweet').attr('disabled', 'disabled');
								var data = {'action':'js_shrink_urls','tweet':tweet};
								$.post(ajaxurl, data, function (response) {
									$('#tweet').val(response);
									$('#tweet').attr('disabled', '');
								});
							}	
						});
					});
				})(jQuery);
			//-->
		</script>
