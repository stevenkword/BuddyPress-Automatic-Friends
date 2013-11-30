jQuery( document ).ready( function( $ ) {
	$other = $('#other');

	$formField.suggest(ajaxurl + '?action=bpaf_suggest', {
		resultsClass: 'ac_results dl_suggest_results',
		onSelect: function() {
			$latest.val('');
			matches = this.value.match(/\[(\d+)\](.+)/);

			if(matches) $other.val(matches[2]);

			$postID.val(matches[1]);
			loadPreview();
		}
	});
});
