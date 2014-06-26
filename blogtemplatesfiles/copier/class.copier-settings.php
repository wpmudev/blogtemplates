<?php

class NBT_Template_Copier_Settings extends NBT_Template_Copier {

    public function __construct( $source_blog_id, $template, $args = array(), $user_id = 0 ) {
		parent::__construct( $source_blog_id, $template, $user_id );
	}

	public function get_default_args() {}

	public function copy() {
		global $wpdb;

        wp_cache_flush();

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

        $themes_mods = $wpdb->get_results( "SELECT option_name, option_value FROM $wpdb->options WHERE option_name LIKE 'theme_mods_%' ");
        restore_current_blog();

        $new_prefix = $wpdb->prefix;

        foreach ( $src_blog_settings as $row ) {
            //Make sure none of the options are using wp_X_ convention, and if they are, replace the value with the new blog ID
            $row->option_name = str_replace( $template_prefix, $new_prefix, $row->option_name );
            if ( 'sidebars_widgets' != $row->option_name ) /* <-- Added this to prevent unserialize() call choking on badly formatted widgets pickled array */
                $row->option_value = str_replace( $template_prefix, $new_prefix, $row->option_value );

            $row = apply_filters( 'blog_templates-copy-options_row', $row, $this->template, get_current_blog_id(), $this->user_id );

            if ( ! $row )
                continue; // Prevent empty row insertion

            $added = add_option( $row->option_name, maybe_unserialize( $row->option_value ), null, $row->autoload );

        }

        // Themes mods
        
        foreach ( $themes_mods as $mod ) {
            $theme_slug = str_replace( 'theme_mods_', '', $mod->option_name );
            $mods = maybe_unserialize( $mod->option_value );

            if ( isset( $mods['nav_menu_locations'] ) )
                unset( $mods['nav_menu_locations'] );

            if ( apply_filters( 'nbt_change_attachments_urls', true ) )
                array_walk_recursive( $mods, array( &$this, 'set_theme_mods_url' ), array( $this->source_blog_id, get_current_blog_id() ) );

            update_option( "theme_mods_$theme_slug", $mods );    
        }
        

        do_action( 'blog_templates-copy-options', $this->template, $this->source_blog_id, $this->user_id );

        return true;
	}

    function set_theme_mods_url( &$item, $key, $userdata = array() ) {
        $template_blog_id = $userdata[0];
        $new_blog_id = $userdata[1];


        if ( is_object( $item ) && ! empty( $item->attachment_id ) ) {
            // Let's copy this attachment and replace it
            $args = array(
                'attachment_id' => $item->attachment_id
            );
            $attachment_copier = nbt_get_copier( 'attachment', $this->source_blog_id, $this->template, $args, $this->user_id );
            $result = $attachment_copier->copy();
            if ( ! is_wp_error( $result ) ) {
                $attachment_id = $result['new_attachment_id'];
                $url = wp_get_attachment_url( $attachment_id );
                $item->attachment_id = $attachment_id;
                $item->url = $url;
                $item->thumbnail_url = $url;
            }
        }


    }

}