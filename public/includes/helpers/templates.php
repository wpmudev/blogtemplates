<?php

function nbt_get_toolbar() {
	$signup = blog_templates()->signup;
	return $signup::$toolbar;
}

function nbt_toolbar_get_toolbar_id() {
	return apply_filters( 'nbt_toolbar_id', 'nbt-toolbar' );
}

function nbt_toolbar_attributes() {
	echo apply_filters( 'nbt_toolbar_attributes', 'id="' . esc_attr( nbt_toolbar_get_toolbar_id() ) . '" data-toolbar-type="' . nbt_get_the_toolbar_type() . '"' );
}

function nbt_get_the_toolbar_type() {
	return nbt_get_toolbar()->get_the_type();
}

function nbt_get_toolbar_default_tab_ID() {
	$default_tab = nbt_get_toolbar()->get_default_tab();
	return $default_tab['cat_id'];
}

function nbt_toolbar_get_the_tab_ID() {
	global $nbt_toolbar_tab;
	return absint( $nbt_toolbar_tab['cat_id'] );
}

function nbt_toolbar_get_the_tab_name() {
	global $nbt_toolbar_tab;
	return $nbt_toolbar_tab['name'];
}

function nbt_toolbar_the_tab_class() {
	$class = "toolbar-item";

	if ( nbt_get_toolbar_default_tab_ID() == nbt_toolbar_get_the_tab_ID() )
		$class .= ' toolbar-item-selected';

	echo apply_filters( 'nbt_toolbar_tab_class', $class );
}

function nbt_toolbar_tab_attributes() {
	$atts = 'data-cat-id="' . nbt_toolbar_get_the_tab_ID() . '"';
	echo apply_filters( 'nbt_toolbar_tab_attributes', $atts );
}

function nbt_toolbar_have_tabs() {
	return nbt_get_toolbar()->have_tabs();
}

function nbt_toolbar_the_tab() {
	return nbt_get_toolbar()->the_tab();	
}