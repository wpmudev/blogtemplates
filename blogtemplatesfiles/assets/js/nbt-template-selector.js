jQuery(document).ready(function($) {
    var position_top = 0;
    var position_left = 0;
    var hovering_out = true;
    $('.nbt-desc-pointer').appendTo($('body'));
    $(document).mousemove(function(e) {
        position_top = e.pageY;
        position_left = e.pageX;
    });
    $('.template-signup-item').hover(
        function(e) {
            hovering_out = false;
            container = $(this);
            var tkey = container.data('tkey');
            pointer = $( '#nbt-desc-pointer-' + tkey );
            setTimeout(function() {
                if ( hovering_out )
                    return;
                var margin_top = position_top;
                var margin_left = position_left;
                pointer.css({
                    left: margin_left - 15 + 'px',
                    top: margin_top + 25,
                    width: container.outerWidth() / 2 + 'px'
                }).stop(true,true).fadeIn()
            }, 200);
        },
        function(e) {
            hovering_out = true;
            var tkey = container.data('tkey');
            pointer = $('#nbt-desc-pointer-'+tkey).stop().fadeOut();
        }
    );
});
