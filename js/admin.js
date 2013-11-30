jQuery( document ).ready( function( $ ) {
	$( "#other" ).autocomplete({
      source: ajaxurl + '?action=bpaf_global_friend_suggest',
      select: function( event, ui ) {
      	$('#add-global-friend').attr('disabled', false);
      },
      search: function( event, ui ) {
      	$('#add-global-friend').attr('disabled', true);
      }
    });
});
