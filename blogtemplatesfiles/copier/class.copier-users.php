<?php

class NBT_Template_Copier_Users extends NBT_Template_Copier {

    public function __construct( $source_blog_id, $template, $args = array(), $user_id = 0 ) {
		parent::__construct( $source_blog_id, $template, $user_id );
	}

	public function get_default_args() {
        return array();
    }

	public function copy() {
        global $wpdb;

        // Removing users from the current blog
        $current_users = get_users();

        
        foreach ( $current_users as $user ) {
            remove_user_from_blog( $user->ID );    
        }
        
        switch_to_blog( $this->source_blog_id );
        $template_users = get_users();        
        $template_users_ids = wp_list_pluck( $template_users, 'ID' );
        if ( ! empty( $this->user_id ) && ! in_array( $this->user_id, $template_users_ids ) ) {
            $template_users[] = get_user_by( 'id', $this->user_id );
        }
        restore_current_blog();

        
        foreach( $template_users as $user ) {
            $user = apply_filters( 'blog_templates-copy-user_entry', $user, $this->template, get_current_blog_id(), $this->user_id );
            if ( $user->ID == $this->user_id ) {
                add_user_to_blog( get_current_blog_id(), $user->ID, 'administrator' );
            }
            elseif ( ! empty( $user->roles[0] ) ) {
                add_user_to_blog( get_current_blog_id(), $user->ID, $user->roles[0] );
            }
        }

        do_action( 'blog_templates-copy-users', $this->template, get_current_blog_id(), $this->user_id );

        return true;
	}


}