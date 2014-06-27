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

        switch_to_blog( $this->source_blog_id );
        $template_users = get_users();
        
        restore_current_blog();

        if ( empty( $template_users ) )
            return true;

        $current_users = get_users();
        $current_users_ids = wp_list_pluck( $current_users, 'ID' );
        
        $copy_users = array();
        foreach ( $template_users as $user ) {
            if ( ! in_array( $user->ID, $current_users_ids ) )
                $copy_users[] = $user;
        }
        foreach( $copy_users as $user ) {
            $user = apply_filters( 'blog_templates-copy-user_entry', $user, $this->template, $this->new_blog_id, $this->user_id );
            if ( $user->ID == $this->user_id ) {
                add_user_to_blog( $this->new_blog_id, $user->ID, 'administrator' );
            }
            else {
                add_user_to_blog( $this->new_blog_id, $user->ID, $user->roles[0] );
            }
        }

        do_action( 'blog_templates-copy-users', $this->template, $this->new_blog_id, $this->user_id );

        return true;
	}


}