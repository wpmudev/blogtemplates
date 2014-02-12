<?php

function nbt_get_model() {
	return blog_templates_model::get_instance();
}

function get_settings_handler() {
	return NBT_Plugin_Settings_Handler::get_instance();
}

function nbt_get_settings() {
	$handler = get_settings_handler();
	return $handler->get_settings();
}

function nbt_update_settings( $new_settings ) {
	$handler = get_settings_handler();
	$handler->update_settings( $new_settings );
}

function nbt_get_default_settings() {
	$handler = get_settings_handler();
	return $handler->get_default_settings();
}

function nbt_theme_selection_toolbar( $templates ) {
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

