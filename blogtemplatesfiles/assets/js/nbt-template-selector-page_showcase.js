jQuery(document).ready(function($) {
    $(document).on( 'click', '.select-theme-button', function(e) {
        e.preventDefault();
        var signup_url = $(this).data('signup-url');
        location.href = signup_url;
    });
    $(document).on('click', '.view-demo-button', function(e) {
        e.preventDefault();
        window.open($(this).data('blog-url'));
    });
});