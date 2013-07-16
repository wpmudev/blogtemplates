jQuery(document).ready(function($) {

	var previewer_selected = $('input[name=registration-templates-appearance]:checked').val() == 'previewer';
	if ( ! previewer_selected ) {
		$( '#previewer-button-text' ).hide();
	}
	
	$('input[name=registration-templates-appearance]').change(function(e) {
		if ( $(this).val() == 'previewer' )
			$( '#previewer-button-text' ).slideDown();
		else
			$( '#previewer-button-text' ).slideUp();
	});

	$('.color-field').wpColorPicker();
	
});