jQuery(document).ready(function($) {
	$( 'li.toolbar-item > a').click(function(e) {
		e.preventDefault();
		var cat_id = $(this).data('cat-id');

		var data = {
			category_id: cat_id,
			action: 'nbt_filter_categories',
			type: $('#nbt-toolbar').data('toolbar-type')
		};


		var the_content = $('.blog_template-option');
	    $.ajax({
			url: export_to_text_js.ajaxurl,
			data: data,
			type: 'post',
			beforeSend: function() {

				the_content.html('<div id="toolbar-loader"><img id="toolbar-loader-image" src="' + export_to_text_js.imagesurl + 'ajax-loader.gif" /></div>');
				console.log( export_to_text_js.imagesurl + 'ajax-loader.gif' );
			}
		}).done(function( data ) {
			the_content.html(data);
		});
	})
});