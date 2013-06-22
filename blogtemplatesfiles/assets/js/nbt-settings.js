jQuery(document).ready(function($) {


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

	function nbt_open_settings_tooltips() {

	}
});