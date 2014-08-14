jQuery(document).ready(function($) {

	var type_selection_selected = $('input[name=registration-templates-appearance]:checked').val();

	var hidden_fields = $( '.selection-type-hidden-fields' );
	hidden_fields.hide();
	$( '.' + type_selection_selected + '-hidden-fields' ).show();

	$('input[name=registration-templates-appearance]').change(function(e) {
		var value = $(this).val();
		hidden_fields.hide();
		$( '.' + value + '-hidden-fields' ).slideDown();
	});


	$('.color-field').wpColorPicker();
	
});