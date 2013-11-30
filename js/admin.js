jQuery( document ).ready( function( $ ) {
	$( "#other" ).autocomplete({
      source: ajaxurl + '?action=bpaf_global_friend_suggest'
    });

});
