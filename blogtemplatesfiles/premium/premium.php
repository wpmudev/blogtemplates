<?php

include_once( NBT_PLUGIN_DIR . 'blogtemplatesfiles/premium/helpers.php' );
include_once( NBT_PLUGIN_DIR . 'blogtemplatesfiles/premium/model.php' );

if ( apply_filters( 'nbt_activate_block_posts_feature', true ) )
	include_once( NBT_PLUGIN_DIR . 'blogtemplatesfiles/premium/lock-posts.php' );

include_once( NBT_PLUGIN_DIR . 'blogtemplatesfiles/premium/admin/admin.php' );

include_once( NBT_PLUGIN_DIR . 'blogtemplatesfiles/premium/front/front.php' );

if ( defined( 'DOING_AJAX' ) && DOING_AJAX )
	include_once( NBT_PLUGIN_DIR . 'blogtemplatesfiles/premium/ajax.php' );

add_action( 'nbt_init', 'nbt_premium_init' );
function nbt_premium_init() {
	$model = nbt_get_model();
    $categories_count = $model->get_categories_count();
    if ( empty( $categories_count ) ) {
        $model->add_default_template_category();
    }
}
