jQuery(document).ready(function($) {

	var previewer_selected = $('input[name=registration-templates-appearance]:checked').val() == 'previewer';
	if ( ! previewer_selected ) {
		$( '#previewer-button-text' ).hide();
	}
	

	var all_checks = $('#categorychecklist input[type=checkbox]').slice(1);
	$('#poststuff').hide();

	$( '#select-category-link' ).click( function(e) {
		e.preventDefault();
		$('#poststuff').slideToggle();
	});

	if ( $('#in-all-categories').attr('checked') ) {
		all_checks.each(function(i) {
			$(this).attr('checked',false);
			$(this).attr('disabled',true);
		});
	}

	$( '#in-all-categories' ).change( function(e) {
		if ( $(this).attr('checked') == 'checked' ) {
			all_checks.each(function(i) {
				$(this).attr('checked',false);
				$(this).attr('disabled',true);
			});
		}
		else {
			all_checks.each(function(i) {
				$(this).attr('disabled',false);
			});
		}
	});

	$('input[name=registration-templates-appearance]').change(function(e) {
		if ( $(this).val() == 'previewer' )
			$( '#previewer-button-text' ).slideDown();
		else
			$( '#previewer-button-text' ).slideUp();
	});
	
});