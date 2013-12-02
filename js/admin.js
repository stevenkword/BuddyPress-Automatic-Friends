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



	// Add a Global Friend
	$addGlobalFriendButton.click( function(e) {
		var $self = $(this);
		var $parentTable = $('.wp-list-table'); // TODO: way too general
		var params = { 'username':$addGlobalFriendField.val() };

		// Send the contents of the existing post
		$.ajax({
			url: ajaxurl + '?action=bpaf_add_global_friend',
			type: 'POST',
			data: jQuery.param(params),
			beforeSend: function() {
				$('.spinner').show();
			},
			complete: function() {
				//$('.spinner').hide();
			},
			success: function(result) {
				$('.spinner').hide();
				// Return the excerpt from the editor
				$parentTable.append(result);
			}
		});
	});

	// Remove a Global Friend
	$('.trash').click( function(e) {
		var $self = $(this);
		var $parentTableRow = $self.parents('tr');
		var userID = $parentTableRow.find('.bpaf-user-id').val();

		var params = { 'ID':userID };

		$.ajax({
			url: ajaxurl + '?action=bpaf_delete_global_friend',
			type: 'POST',
			data: jQuery.param(params),
			beforeSend: function() {
				$('.spinner').show();
			},
			complete: function() {
				//$('.spinner').hide();
			},
			success: function() {
				$('.spinner').hide();
				$parentTableRow.remove();
			}
		});
	});

});