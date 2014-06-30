<?php

include_once( NBT_PLUGIN_DIR . 'blogtemplatesfiles/premium/admin/settings-menu.php' );
include_once( NBT_PLUGIN_DIR . 'blogtemplatesfiles/premium/admin/categories-menu.php' );
include_once( NBT_PLUGIN_DIR . 'blogtemplatesfiles/premium/admin/categories-table.php' );

remove_action( 'nbt_display_create_template_form', array( 'blog_templates_main_menu', 'remove_add_new_template_form' ), 99 );

add_action( 'nbt_object_create', 'nbt_add_network_settings_menu' );
function nbt_add_network_settings_menu() {
	if ( is_network_admin() ) {       
        new blog_templates_settings_menu();
        if ( apply_filters( 'nbt_activate_categories_feature', true ) )
        	new blog_templates_categories_menu();
    }
}

add_action( 'nbt_edit_template_menu_after_content', 'nbt_add_template_categories_postbox', 10, 2 );
function nbt_add_template_categories_postbox( $template, $t ) {
	if ( ! apply_filters( 'nbt_activate_categories_feature', true ) )
		return;

	$model = nbt_get_model();
	$categories = $model->get_templates_categories(); 
	$template_categories_tmp = $model->get_template_categories( $template );

	$template_categories = array();
	foreach ( $template_categories_tmp as $row ) {
		$template_categories[] = absint( $row['ID'] );
	}

	?>
	<div id="postbox-container-1" class="postbox-container">
		<div id="side-sortables" class="meta-box-sortables ui-sortable">
			<div id="categorydiv" class="postbox ">
				<div class="handlediv" title=""><br></div><h3 class="hndle"><span><?php _e( 'Template categories' ); ?></span></h3>
				<div class="inside">
					<div id="taxonomy-category" class="categorydiv">
						<div id="category-all" class="tabs-panel">
							<ul id="templatecategorychecklist" class="categorychecklist form-no-clear">
								<?php foreach ( $categories as $category ): ?>
									<li id="template-cat-<?php echo $category['ID']; ?>"><label class="selectit"><input value="<?php echo $category['ID']; ?>" <?php checked( in_array( $category['ID'], $template_categories ) ); ?> type="checkbox" name="template_category[]"> <?php echo $category['name']; ?></label></li>
								<?php endforeach; ?>
							</ul>
						</div>
					</div>
				</div>
			</div>
		</div>			
	</div>
	<?php
}

add_action( 'nbt_update_template', 'nbt_update_template_categories' );
function nbt_update_template_categories( $t ) {
	$model = nbt_get_model();

	if ( ! isset( $_POST['template_category'] ) ) {
    	$template_category = array( $model->get_default_category_id() );
    }
    else {
    	$categories = $_POST['template_category'];

    	$template_category = array();
		foreach( $categories as $category ) {
			if ( ! is_numeric( $category ) )
				continue;

			$template_category[] = absint( $category );
		}
    }

    $model->update_template_categories( $t, $template_category );
}

add_action( 'nbt_template_settings_after_content', 'nbt_add_lock_posts_setting' );
function nbt_add_lock_posts_setting( $template ) {
	if ( ! apply_filters( 'nbt_activate_block_posts_feature', true ) )
		return;

	?>
		<tr valign="top">
            <th scope="row"><label for="site_name"><?php _e( 'Lock Posts/Pages', 'blog_templates' ); ?></label></th>
            <td>
                <input type='checkbox' name='block_posts_pages' id='nbt-block-posts-pages' <?php checked( $template['block_posts_pages'] ); ?>>
        		<label for='nbt-block-posts-pages'><?php _e( 'If selected, pages and posts included in the template will not be allowed to be edited (even for the blog administrator). Only Super Admins will be able to edit the text of copied posts/pages.', 'blog_templates' ); ?></label>
            </td>
        </tr>
		
	<?php
}	



add_filter( 'nbt_templates_table_actions', 'nbt_add_default_template_actions', 10, 2 );
function nbt_add_default_template_actions( $actions, $item ) {
	$url_default = add_query_arg(
        array(
            'page' => 'blog_templates_main',
            'default' => $item['ID']
        ),
        $pagenow
    );
    $url_default = wp_nonce_url( $url_default, 'blog_templates-make_default' );

    $url_remove_default = add_query_arg(
        array(
            'page' => 'blog_templates_main',
            'remove_default' => $item['ID']
        ),
        $pagenow
    );
    $url_remove_default = wp_nonce_url( $url_remove_default, 'blog_templates-remove_default' );

    if ( $item['is_default'] ) {
        $actions['remove_default'] = sprintf( __( '<a href="%s">Remove default</a>', 'blog_templates' ), $url_remove_default );
        $default = ' <strong>' . __( '(Default)', 'blog_templates' ) . '</strong>';
    }
    else {
        $actions['make_default'] = sprintf( __( '<a href="%s">Make default</a>', 'blog_templates' ), $url_default );
        $default = '';
    }

    return $actions;
}

add_action( 'nbt_main_menu_processed', 'nbt_set_default_template' );
function nbt_set_default_template( $main_menu_obj ) {
	$model = nbt_get_model();
	
	if( isset( $_GET['remove_default'] ) ) {
        if ( ! wp_verify_nonce($_GET['_wpnonce'], 'blog_templates-remove_default') )
            wp_die( __( 'Whoops! There was a problem with the data you posted. Please go back and try again. (Generated by New Blog Templates)', 'blog_templates' ) );
       	
       	$model->remove_default_template();
       	$settings = nbt_get_settings();

       	$settings['default'] = '';
       	nbt_update_settings( $settings );

        $main_menu_obj->updated_message = __( 'The default template was successfully turned off.', 'blog_templates' );
        add_action( 'network_admin_notices', array( &$main_menu_obj, 'show_admin_notice' ) );

    } elseif ( isset( $_GET['default'] ) && is_numeric( $_GET['default'] ) ) {

        if (! wp_verify_nonce($_GET['_wpnonce'], 'blog_templates-make_default') )
            wp_die( __( 'Whoops! There was a problem with the data you posted. Please go back and try again. (Generated by New Blog Templates)', 'blog_templates' ) );

		$default_updated = $model->set_default_template( absint( $_GET['default'] ) );

		if ( $default_updated ) {
			$settings = nbt_get_settings();
			$settings['default'] = $_GET['default'];
           	nbt_update_settings( $settings );
        }

        $main_menu_obj->updated_message =  __( 'The default template was successfully updated.', 'blog_templates' );
        add_action( 'network_admin_notices', array( &$main_menu_obj, 'show_admin_notice' ) );

    }
}
