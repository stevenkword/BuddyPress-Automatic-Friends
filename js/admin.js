jQuery( document ).ready( function( $ ) {
	var $addGlobalFriendField = $('#add-global-friend-field');
	var $addGlobalFriendButton = $('#add-global-friend-button');
	var $tableContainer = $('#global-friend-table-container');

	var nonce = $('#bpaf_nonce').val();
	var params = { 'nonce':nonce };
	var cache = {};

	$addGlobalFriendField.autocomplete({
		autoFocus: true,
		minLength: 1,
		source: function(request, response) {
			var term = request.term;
			// Has the request been made before?
			if ( term in cache ) {
				response( cache[ term ] );
				return;
			}

			// Add the search term to the request
			params.term = request.term;
			// Remote Source
			$.ajax({
				url: ajaxurl + '?action=bpaf_suggest_global_friend&search=' + params.term,
				dataType: "json",
					data: jQuery.param(params),
					success: function(data){
						//Cache this response since it is expensive
						cache[ term ] = $.ui.autocomplete.filter(data, term);
						response(cache[ term ]);
						return;
				}
			});
		},
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
	}

	// Add a Global Friend
	$addGlobalFriendButton.click( function(e) {
		var $self = $(this);
		var $parentTable = $('.wp-list-table'); // TODO: way too general
		var nonce = $('#bpaf_nonce').val();
		var params = { 'username':$addGlobalFriendField.val(), 'nonce':nonce };

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
			success: function(response) {
				$addGlobalFriendButton.attr('disabled', true);
				$addGlobalFriendField.css( 'color', '#aaa' );
				updateFieldTextColor();
				$addGlobalFriendField.val('Search by Username');

				$('.spinner').hide();

				// Return the excerpt from the editor ???
				$('.bpaf-empty-table-row').remove();

				$tableContainer.html(response);
			}
		});
	});

	// Remove a Global Friend
	$tableContainer.on( 'click', '.trash', function(e) {
		e.preventDefault();
		var confirmDelete = confirm("Removing this user will delete ALL friendships related to this user. 'Cancel' to stop, 'OK' to delete.");
		if( false === confirmDelete )
			return;

		var $self = $(this);
		var $parentTable = $('.wp-list-table'); // TODO: way too general
		var $parentTableRow = $self.parents('tr');
		var userID = $parentTableRow.find('.bpaf-user-id').val();
		var nonce = $('#bpaf_nonce').val();
		var params = { 'ID':userID, 'nonce':nonce };

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
			success: function( response ) {
				$('.spinner').hide();
				$tableContainer.html(response);
			}
		});
	});

});
