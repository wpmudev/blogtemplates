jQuery(document).ready(function($) {

	var previewer_selected = $('input[name=registration-templates-appearance]:checked').val() == 'previewer';
	if ( ! previewer_selected ) {
		$( '#previewer-button-text' ).hide();
	}

	var screenshot_plus = $('input[name=registration-templates-appearance]:checked').val() == 'screenshot_plus';
	var screenshot = $('input[name=registration-templates-appearance]:checked').val() == 'screenshot';

	if ( ! screenshot_plus && ! screenshot && ! previewer_selected ) {
		$( '#screenshot-selection-styles' ).hide();
	}
	
	$('input[name=registration-templates-appearance]').change(function(e) {
		var value = $(this).val();
		if ( value == 'previewer' )
			$( '#previewer-button-text' ).slideDown();
		else
			$( '#previewer-button-text' ).slideUp();
		
		if ( value == 'previewer' || value == 'screenshot_plus' || value == 'screenshot' )
			$( '#screenshot-selection-styles' ).slideDown();
		else
			$( '#screenshot-selection-styles' ).slideUp();



	});

	$('.color-field').wpColorPicker();
	
});