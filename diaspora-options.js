var $jQ = jQuery.noConflict();

$jQ(function($){
	$(document).ready(function() {
		$('#diaspora-share-with img').bind('click', function() {
			$(this).toggleClass('diaspora-faded');

			var hidden_element_name = 'wp_post_to_diaspora_options_share_with[' + $(this).attr('id') + ']';
			var share_with_value = 0;

			if ($(this).hasClass('diaspora-faded') === false) {
				share_with_value = 1;
			}

			$('input[name="' + hidden_element_name + '"]').val(share_with_value);
		});
	});
});
