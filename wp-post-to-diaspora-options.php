<?php
	define('MAX_CONTENT_CHAR_LENGTH', 4096);

	if (isset ($_POST['content']) ) {
		if (strlen($handle) > 0 && strlen($pass) > 0) {
			$content = trim ($_POST['content']);
			if (strlen($content) > 0 && strlen ($content) < MAX_CONTENT_CHAR_LENGTH) {

				require_once dirname (__FILE__) . '/Diaspora.php';

				$diaspora = new Diaspora();

				$diaspora->setHandle( $handle );
				$diaspora->setPassword( $pass );
				$diaspora->setMessage( wp_post_to_diaspora_process_content( $content ) );

				$diaspora_response = __( $diaspora->postToDiaspora() );

				if ($diaspora_response == 'Error posting to diaspora. Retry') {
					echo '<div id="notice" class="error"><p>' . $diaspora_response . '</p></div>';
				} else {
					echo '<div id="notice" class="updated fade"><p>' . $diaspora_response . '</p></div>';
				}
			} else {
				echo '<div id="notice" class="error"><p>' . __('Your post must be greater than 0 characters long and less than ' . MAX_CONTENT_CHAR_LENGTH) . '</p></div>';
			}
		} else {
			echo '<div id="notice" class="error"><p>' . __('Please enter your diaspora handle and password.') . '</p></div>';
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
<?php
			settings_fields( 'wp_post_to_diaspora_options' );
			do_settings_sections( 'general' );
?>
                	<p class="submit"><input type="submit" name="update" value="<?=__('Save Changes');?>" /></p>
		</form>
            <form id="diaspora-form" method="post" action="?page=wp-post-to-diaspora.php">
            	<table class="form-table">
            		<tr>
            			<th scope="row"></th>
						<td>
							<table class="diaspora-mimic">
								<tr>
									<th scope="row">
										<h3>What are you doing?</h3>
									</th>
									<td class="diaspora-char-limit">
										<p><?php echo MAX_CONTENT_CHAR_LENGTH; ?></p>
									</td>
								</tr>
							</table>
						</td>
					</tr>
                   	<tr>
                       	<th scope="row"><label for="content"><?=__('Message');?></label>  <a href="#" id="shrink"><?=__('Shrink URL\'s');?></a></th>
                        <td><textarea name="content" id="content" cols="57" rows="5"></textarea></td>
                    </tr>
                </table>
                <p class="submit"><input class="button-primary" type="submit" name="submit" value="<?=__('Post');?>" /></p>
            </form>
        </div>
		<script type="text/javascript">
			<!--//
				(function($){
					$(document).ready(function(){
						var max_chars = <?php echo MAX_CONTENT_CHAR_LENGTH ?>;
						$('#content').bind('keyup', function(e){
							var content = $(this);
							var total = content.val().length;
							var isnow = max_chars - total;
							$('.diaspora-char-limit p').text(isnow);
							if (isnow <= 0) {
								content.val(content.val().substr(0, max_chars - 1));
							}
						});
						$('#shrink').bind('click', function(){
							var content   = $('#content').val();
							var re      = new RegExp(/(((ht|f)tp(s?))\:\/\/)?(www.|[a-zA-Z].)[a-zA-Z0-9\-\.]+\.(com|edu|gov|mil|net|org|biz|info|name|museum|us|ca|uk|ly)(\:[0-9]+)*(\/($|[a-zA-Z0-9\.\,\;\?\'\\\+&amp;%\$#\=~_\-]+))*/gi);
							var matches = re.exec(content);
							if (matches == null) return;
							if (matches.length > 0) {
								$('#content').attr('disabled', 'disabled');
								var data = {'action':'js_shrink_urls','content':content};
								$.post(ajaxurl, data, function (response) {
									$('#content').val(response);
									$('#content').attr('disabled', '');
								});
							}	
						});
					});
				})(jQuery);
			//-->
		</script>
