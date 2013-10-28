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