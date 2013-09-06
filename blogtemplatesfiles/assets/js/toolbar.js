jQuery(document).ready(function($) {
	$( '.toolbar-item').click(function(e) {
		e.preventDefault();

		var toolbar = $('#nbt-toolbar');
		var this_item = $(this);
		var cat_id = this_item.data('cat-id');

		var rest_of_items = toolbar.find( 'a:not(#item-' + cat_id + ')');
		this_item.css( 'opacity', '1' );
		rest_of_items.css('opacity','0.62');


		var data = {
			category_id: cat_id,
			action: 'nbt_filter_categories',
			type: toolbar.data('toolbar-type')
		};


		var the_content = $('.blog_template-option');
	    $.ajax({
			url: export_to_text_js.ajaxurl,
			data: data,
			type: 'post',
			beforeSend: function() {
				the_content.html('<div id="toolbar-loader"><img id="toolbar-loader-image" src="' + export_to_text_js.imagesurl + 'ajax-loader.gif" /></div>');
			}
		}).done(function( data ) {
			the_content.html(data);
		});
	})
});