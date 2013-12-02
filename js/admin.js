jQuery( document ).ready( function( $ ) {
	var $addGlobalFriendField = $('#add-global-friend-field');
	var $addGlobalFriendButton = $('#add-global-friend-button');

	$addGlobalFriendField.autocomplete({
    	source: ajaxurl + '?action=bpaf_suggest_global_friend',
      	select: function( event, ui ) {
      		$addGlobalFriendButton.attr('disabled', false);
      		$addGlobalFriendButton.focus();
      		updateFieldTextColor();
      	},
      	search: function( event, ui ) {
      		$addGlobalFriendButton.attr('disabled', true);
      		$addGlobalFriendField.css( 'color', '#aaa' );
      		updateFieldTextColor();
      	}
    });

   function updateFieldTextColor() {
   		var buttonTextColor = $addGlobalFriendButton.css('color');
   		$addGlobalFriendField.css( 'color', buttonTextColor );
   };

});