<?php

class NBT_Template_Copier_Settings extends NBT_Template_Copier {

    public function __construct( $source_blog_id, $template, $args = array(), $user_id = 0 ) {
		parent::__construct( $source_blog_id, $template, $user_id );
	}

	public function get_default_args() {}

	public function copy() {
		global $wpdb;

		$exclude_settings = array(
            'siteurl',
            'blogname',
            'admin_email',
            'new_admin_email',
            'home',
            'upload_path',
            'db_version',
            'secret',
            'fileupload_url',
            'nonce_salt',
            'nbt-pending-template'
        );

        $exclude_settings = apply_filters( 'blog_templates_exclude_settings', $exclude_settings );

        $exclude_settings_where = "`option_name` != '" . implode( "' AND `option_name` != '", $exclude_settings ) . "'";
        $exclude_settings = apply_filters( 'blog_template_exclude_settings', $exclude_settings_where );

        $wpdb->query( "DELETE FROM $wpdb->options WHERE $exclude_settings_where" );

        if ( $wpdb->last_error )
            return new WP_Error( 'settings_error', __( 'Error copying settings' ) );

        switch_to_blog( $this->source_blog_id );
        $src_blog_settings = $wpdb->get_results( "SELECT * FROM $wpdb->options WHERE $exclude_settings_where" );
        $template_prefix = $wpdb->prefix;
        restore_current_blog();

        $new_prefix = $wpdb->prefix;

        foreach ( $src_blog_settings as $row ) {
            //Make sure none of the options are using wp_X_ convention, and if they are, replace the value with the new blog ID
            $row->option_name = str_replace( $template_prefix, $new_prefix, $row->option_name );
            if ( 'sidebars_widgets' != $row->option_name ) /* <-- Added this to prevent unserialize() call choking on badly formatted widgets pickled array */
                $row->option_value = str_replace( $template_prefix, $new_prefix, $row->option_value );

            $row = apply_filters( 'blog_templates-copy-options_row', $row, $this->template, $this->source_blog_id, $this->user_id );

            if ( ! $row )
                continue; // Prevent empty row insertion

            add_option( $row->option_name, maybe_unserialize( $row->option_value ), null, $row->autoload );

        }

        do_action( 'blog_templates-copy-options', $this->template,$this->source_blog_id, $this->user_id );

        return true;
	}

}