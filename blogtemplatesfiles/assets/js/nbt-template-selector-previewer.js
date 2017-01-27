jQuery(document).ready(function($) {
    $(document).on( 'click', '.view-demo-button, .select-theme-button', function(e) {
        e.preventDefault();
        var theme_key = $(this).data('theme-key');
        var wrap = $('#theme-previewer-wrap-' + theme_key );
        $('.theme-previewer-wrap').removeClass('blog_template-default_item');
        wrap.addClass('blog_template-default_item');

        $('input[name=blog_template]').attr('checked',false);
        $('#blog-template-radio-' + theme_key).attr('checked',true);
    });
    $(document).on('click', '.view-demo-button', function(e) {
        e.preventDefault();
        window.open($(this).data('blog-url'));
    });
});