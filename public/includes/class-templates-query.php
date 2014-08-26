<?php

function nbt_have_templates() {
	return blog_templates()->signup->templates_query->have_templates();
}

function nbt_the_template() {
	global $nbt_template;
	blog_templates()->signup->templates_query->the_template();	
}

function nbt_get_the_template_ID() {
	global $nbt_template;
	return absint( $nbt_template['ID'] );
}

function nbt_the_template_ID() {
	echo nbt_get_the_template_ID();
}

function nbt_get_the_template_name() {
	global $nbt_template;
	return strip_tags( $nbt_template['name'] );
}

function nbt_get_the_template_description() {
	global $nbt_template;
	return nl2br( $nbt_template['description'] );
}

function nbt_is_default_template() {
	global $nbt_template;
	return (bool)$nbt_template['is_default'];
}

function nbt_get_default_template_ID() {
	$model = nbt_get_model();
	return $model->get_default_template_id();
}

function nbt_get_selected_template_ID() {
	$selected = isset( $_REQUEST['blog_template'] ) ? absint( $_REQUEST['blog_template'] ) : '';

	if ( empty( $selected ) )
		$selected = nbt_get_default_template_ID() ? nbt_get_default_template_ID() : '';

	return $selected;
}

function nbt_get_the_template_screenshot() {
	
}


class Blog_Templates_Templates_Query {
	public $current_template = -1;

	public $templates = array();

	public function __construct() {
		$model = nbt_get_model();

		$this->templates = array_values( $model->get_templates() );
		$this->templates = apply_filters( 'nbt_signup_templates', $this->templates );

		$this->templates_count = count( $this->templates );
	}

	public function have_templates() {
		if ( $this->current_template + 1 < $this->templates_count ) {
			return true;
		}

		return false;
	}

	public function the_template() {
		global $nbt_template;

		$nbt_template = $this->next_template();
	}

	private function next_template() {
		$this->current_template++;

		$this->template = $this->templates[$this->current_template];
		return $this->template;
	}


}


