jQuery(document).ready(function($) {
    $(document).on( 'click', '.select-theme-button', function(e) {
        e.preventDefault();
        var form_action = $('#setupform').attr('action');
        if ( 'wp-signup.php' !== form_action ) {
            var signup_url = $(this).data('signup-url');
            location.href = signup_url;
        } else {
            var template_id = $( this ).parent().parent().data('tkey');
            $( 'input[name="blog_template"]').val( template_id );

            $( this ).parent().parent().addClass( 'blog_template-default_item' );
            $( this ).parent().parent().siblings().removeClass( 'blog_template-default_item' );
        }
    });
    $(document).on('click', '.view-demo-button', function(e) {
        e.preventDefault();
        window.open($(this).data('blog-url'));
    });
});