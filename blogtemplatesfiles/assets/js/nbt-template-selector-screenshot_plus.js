jQuery(document).ready(function($) {
    $(document).on( 'click', '.blog_template-item_selector', function(e) {
        e.preventDefault();
        var theme_key = $(this).data('theme-key');
        var wrap = $('#theme-screenshot-plus-image-wrap-' + theme_key );
        $('.theme-screenshot-plus-image-wrap').removeClass('blog_template-default_item');
        wrap.addClass('blog_template-default_item');

        $('input[name=blog_template]').attr('checked',false);
        $('#blog-template-radio-' + theme_key).attr('checked',true);
    });
});