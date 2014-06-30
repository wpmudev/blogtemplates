<?php

function nbt_theme_selection_toolbar( $templates ) {
    require_once( NBT_PLUGIN_DIR . 'blogtemplatesfiles/premium/front/theme-selection-toolbar.php' );
	$settings = nbt_get_settings();
	$toolbar = new blog_templates_theme_selection_toolbar( $settings['registration-templates-appearance'] );
	$toolbar->display();
	$category_id = $toolbar->default_category_id; 

	if ( $category_id !== 0 ) {
		$model = nbt_get_model();
		$templates = $model->get_templates_by_category( $category_id );
	}

	return $templates;
}

function nbt_get_template_selection_types() {
	return apply_filters( 'nbt_get_template_selection_types', array(
        0 => __('As simple selection box', 'blog_templates'),
        'description' => __('As radio-box selection with descriptions', 'blog_templates'),
        'screenshot' => __('As theme screenshot selection', 'blog_templates'),
        'screenshot_plus' => __('As theme screenshot selection with titles and description', 'blog_templates'),
        'previewer' => __('As a theme previewer', 'blog_templates'),
        'page_showcase' => __('As a showcase inside a page', 'blog_templates')
    ) );
}