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


	var nbt_cache = {};
	$( "#search_for_blog" ).autocomplete({
	  minLength: 2,
	  source: function( request, response ) {
	    var term = request.term;
	    if ( term in nbt_cache ) {
	      response( nbt_cache[ term ] );
	      return;
	    }
		
		var data = {
			action: 'nbt_get_sites_search',
			term: request.term
		};
		
		//$.post( export_to_text_js.ajaxurl, data, function( data, status, xhr ) {
	    //	console.log(data);
		//	//nbt_cache[ term ] = data;
		//	//response( data );
	    //});
	    

	    $.getJSON( export_to_text_js.ajaxurl, data ).done( function( data, status, xhr ) {
	    	console.log(data);
			//nbt_cache[ term ] = data;
			//response( data );
	    });
	  }
	});


});