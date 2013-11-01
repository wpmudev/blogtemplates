<?php

class NBT_Template_copier {

	private $settings;
	private $template_blog_id;
	private $new_blog_id;
	private $user_id;

	public function __construct( $src_blog_id, $new_blog_id, $user_id, $args ) {
		$this->settings = wp_parse_args( $args, $this->get_default_args() );

		$this->template_blog_id = $src_blog_id;
		$this->new_blog_id = $new_blog_id;
		$this->user_id = $user_id;

		$model = nbt_get_model();
		$this->template = $model->get_template( $this->settings['template_id'] );

        if ( empty( $this->template ) )
            $this->template = array();
	}

	private function get_default_args() {
		return array(
			'to_copy' => array(
				'settings' 	=> false,
				'posts'		=> false,
				'pages'		=> false,
				'terms'		=> false,
				'users'		=> false,
				'menus'		=> false,
				'files'		=> false
			),
			'pages_ids'		=> array( 'all-pages' ),
			'post_category' => array( 'all-categories' ),
			'template_id'	=> 0,
			'additional_tables' => array(),
            'block_posts_pages' => false,
            'update_dates' => false
		);
	}

	public function execute() {
		global $wpdb;

        switch_to_blog( $this->new_blog_id );
        //Begin the transaction
        $wpdb->query("BEGIN;");

		// In case we are not copying posts, we'll have to reset the terms count to 0
        if ( $this->settings['to_copy']['posts'] || $this->settings['to_copy']['pages'] ) {
            $this->clear_table($wpdb->posts);
            $this->clear_table($wpdb->postmeta);
            $this->clear_table($wpdb->comments);
            $this->clear_table($wpdb->commentmeta);
        }

		foreach ( $this->settings['to_copy'] as $setting => $value ) {
			if ( $value )
				call_user_func( array( $this, 'copy_' . $setting ) );
		}

		if ( ! empty( $this->settings['additional_tables'] ) )
			$this->copy_additional_tables();

        if ( $this->settings['block_posts_pages'] ) {
            $posts_ids = $wpdb->get_col( "SELECT ID FROM $wpdb->posts" );
            if ( $posts_ids ) {
                foreach ( $posts_ids as $post_id )
                    add_post_meta( $post_id, 'nbt_block_post', true );
            }
        }

        $this->set_content_urls( $this->template['blog_id'], $this->new_blog_id );

        if ( ! empty( $this->settings['update_dates'] ) ) {
            $this->update_posts_dates('post');
            do_action('blog_templates-update-posts-dates', $this->template, $this->new_blog_id, $this->user_id );
        }
        if ( ! empty( $this->settings['update_dates'] ) ) {
            $this->update_posts_dates('page');
            do_action('blog_templates-update-pages-dates', $this->template, $this->new_blog_id, $this->user_id );
        }
                    
        // Now we need to update the blog status because of a conflict with Multisite Privacy Plugin
        if ( isset( $this->settings['copy_status'] ) && $this->settings['copy_status'] &&  is_plugin_active( 'sitewide-privacy-options/sitewide-privacy-options.php' ) )
            update_blog_status( $this->new_blog_id, 'public', get_blog_status( $this->templatd_blog_id, 'public' ) );

        $wpdb->query("COMMIT;"); //If we get here, everything's fine. Commit the transaction

        do_action( "blog_templates-copy-after_copying", $this->template, $this->new_blog_id, $this->user_id );
        restore_current_blog();


	}

    function update_posts_dates( $post_type ) {
        global $wpdb;

        $sql = $wpdb->prepare( "UPDATE $wpdb->posts
            SET post_date = %s,
            post_date_gmt = %s,
            post_modified = %s,
            post_modified_gmt = %s
            WHERE post_type = %s
            AND post_status = 'publish'",
            current_time( 'mysql', false ),
            current_time( 'mysql', true ),
            current_time( 'mysql', false ),
            current_time( 'mysql', true ),
            $post_type
        );

        $wpdb->query( $sql );
    }

    function set_content_urls() {
        global $wpdb;

        $pattern = '/^(http|https):\/\//';
        switch_to_blog( $this->template_blog_id );
        $templated_home_url = preg_replace( $pattern, '', home_url() );
        restore_current_blog();

        switch_to_blog( $this->new_blog_id );
        $new_home_url = preg_replace( $pattern, '', home_url() );

        $sql = $wpdb->prepare( "SELECT * FROM $wpdb->posts WHERE post_content LIKE %s AND post_status = 'publish';", '%' . $templated_home_url . '%' );
        $results = $wpdb->get_results( $sql );

        foreach ( $results as $row ) {
            //UPDATE 
            $post_content = str_replace( $templated_home_url, $new_home_url, $row->post_content );
            $sql = $wpdb->prepare( "UPDATE $wpdb->posts SET post_content = %s WHERE ID = %d;", $post_content, $row->ID );
            $wpdb->query( $sql );
        }
        restore_current_blog();
    }

	public function copy_settings() {
		global $wpdb;

		$exclude_settings = apply_filters( 'blog_template_exclude_settings', "`option_name` != 'siteurl' AND `option_name` != 'blogname' AND `option_name` != 'admin_email' AND `option_name` != 'new_admin_email' AND `option_name` != 'home' AND `option_name` != 'upload_path' AND `option_name` != 'db_version' AND `option_name` != 'secret' AND `option_name` != 'fileupload_url' AND `option_name` != 'nonce_salt'" );
		$new_prefix = $wpdb->prefix;

		//Delete the current options, except blog-specific options
        $wpdb->query("DELETE FROM $wpdb->options WHERE $exclude_settings");

        if ( ! $wpdb->last_error ) {
        	//No error. Good! Now copy over the old settings

            //Switch to the template blog, then grab the settings/plugins/templates values from the template blog
            switch_to_blog( $this->template_blog_id );

            $src_blog_settings = $wpdb->get_results("SELECT * FROM $wpdb->options WHERE $exclude_settings");
            $template_prefix = $wpdb->prefix;

            //Switch back to the newly created blog
            restore_current_blog(); 

            //Now, insert the templated settings into the newly created blog
            foreach ( $src_blog_settings as $row ) {
                //Make sure none of the options are using wp_X_ convention, and if they are, replace the value with the new blog ID
                $row->option_name = str_replace( $template_prefix, $new_prefix, $row->option_name );
                if ( 'sidebars_widgets' != $row->option_name ) /* <-- Added this to prevent unserialize() call choking on badly formatted widgets pickled array */ 
                	$row->option_value = str_replace( $template_prefix, $new_prefix, $row->option_value );

                //To prevent duplicate entry errors, since we're not deleting ALL of the options, there could be an ID collision
                unset( $row->option_id );

                // For template blogs with deprecated DB schema (WP3.4+)
                if ( ! ( defined('NBT_TIGHT_ROW_DUPLICATION') && NBT_TIGHT_ROW_DUPLICATION ) ) 
                	unset( $row->blog_id );

                // Add further processing for options row
                $row = apply_filters( 'blog_templates-copy-options_row', $row, $this->template, $this->new_blog_id, $this->user_id );
                
                if ( ! $row ) 
                	continue; // Prevent empty row insertion

                //Insert the row
                $wpdb->insert( $wpdb->options, (array)$row );

                //Check for errors
                if ( ! empty( $wpdb->last_error ) ) {
                    $error = '<div id="message" class="error"><p>' . sprintf( __( 'Insertion Error: %s - The template was not applied. (New Blog Templates - While inserting templated settings)', 'blog_templates' ), $wpdb->last_error ) . '</p></div>';
                    $wpdb->query("ROLLBACK;");

                    //We've rolled it back and thrown an error, we're done here
                    restore_current_blog();
                    wp_die( $error );
                }
            }

            // Membership integration
            if( class_exists( 'membershipadmin' ) ) {
                nbt_add_membership_caps( $this->user_id, $this->new_blog_id );
            }

            do_action( 'blog_templates-copy-options', $this->template );
        } 
        else {
            $error = '<div id="message" class="error"><p>' . sprintf( __( 'Deletion Error: %s - The template was not applied. (New Blog Templates - While removing auto-generated settings)', 'blog_templates' ), $wpdb->last_error ) . '</p></div>';
            $wpdb->query("ROLLBACK;");
            restore_current_blog(); //Switch back to our current blog
            wp_die($error);
        }
	}

	public function copy_posts() {

		$categories = in_array( 'all-categories', $this->settings['post_category'] ) ? false : $this->settings['post_category'];

        $this->copy_posts_table( $this->template_blog_id, 'posts', $categories );
        do_action( 'blog_templates-copy-posts', $this->template, $this->new_blog_id, $this->user_id );

        $this->copy_posts_table( $this->template_blog_id, 'postmeta' );
        do_action( 'blog_templates-copy-postmeta', $this->template, $this->new_blog_id, $this->user_id );
	}

	public function copy_pages() {
		$pages_ids = in_array( 'all-pages', $this->settings['pages_ids'] ) ? false : $this->settings['pages_ids'];

        $this->copy_posts_table( $this->template_blog_id, "pages", $pages_ids );
        do_action( 'blog_templates-copy-pages', $this->template, $this->new_blog_id, $this->user_id );

        $this->copy_posts_table( $this->template_blog_id, "pagemeta" );
        do_action( 'blog_templates-copy-pagemeta', $this->template, $this->new_blog_id, $this->user_id );
	}

	public function copy_terms() {
		global $wpdb;

		$this->clear_table( $wpdb->links );
        $this->copy_table( $this->template_blog_id, "links" );
        do_action( 'blog_templates-copy-links', $this->template, $this->new_blog_id, $this->user_id );

        $this->clear_table( $wpdb->terms );
        $this->copy_table( $this->template_blog_id, "terms");
        do_action( 'blog_templates-copy-terms', $this->template, $this->new_blog_id, $this->user_id );

        $this->clear_table( $wpdb->term_relationships );
        $this->copy_table( $this->template_blog_id, "term_relationships");
        do_action( 'blog_templates-copy-term_relationships', $this->template, $this->new_blog_id, $this->user_id );

        $this->clear_table( $wpdb->term_taxonomy );
        $this->copy_table( $this->template_blog_id, "term_taxonomy");
        do_action( 'blog_templates-copy-term_taxonomy', $this->template, $this->new_blog_id, $this->user_id );

        if ( ! $this->settings['to_copy']['posts'] ) {
            // The new blog will not have any post
            // So we have to set the terms count to 0
            $this->reset_terms_counts();
        }	
	}

	public function copy_users() {
		global $wpdb;

		switch_to_blog( $this->template_blog_id );
        $template_users = get_users();
        restore_current_blog(); 

        if ( ! empty( $template_users ) ) {
        	foreach( $template_users as $user ) {
        		$user = apply_filters( 'blog_templates-copy-user_entry', $user, $this->template, $this->new_blog_id, $this->user_id );
        		if ( $user->ID == $this->user_id ) {
        			add_user_to_blog( $this->new_blog_id, $user->ID, 'administrator' );
        		}
        		else {
        			add_user_to_blog( $this->new_blog_id, $user->ID, $user->roles[0] );
        		}
        	}
        }

        do_action( 'blog_templates-copy-users', $this->template, $this->new_blog_id, $this->user_id );
	}

	public function copy_menus() {
		global $wp_version;
        if ( version_compare( $wp_version, '3.6', '>=' ) ) {
            $this->copy_menu( $this->template_blog_id, $this->new_blog_id );
        }
        else {
            $this->old_copy_menu( $this->template_blog_id, $this->new_blog_id );
        }
        $this->set_menus_urls( $this->template_blog_id, $this->new_blog_id );
	}

	public function copy_files() {
		global $wp_filesystem;

		$new_content_url = get_bloginfo('wpurl');

    	switch_to_blog( $this->template_blog_id );
        $theme_slug = get_option( 'stylesheet' );

        // Attachments URL for the template blogç
        $template_attachments = get_posts( array( 'post_type' => 'attachment' ) );
        $template_content_url = get_bloginfo('wpurl');
        //Now, go back to the new blog that was just created
        restore_current_blog();

        $dir_to_copy = $this->_get_files_fs_path( $this->template_blog_id ); //ABSPATH . 'wp-content/blogs.dir/' . $this->template_blog_id . '/files';
        $dir_to_copy = apply_filters( 'blog_templates-copy-source_directory', $dir_to_copy, $this->template, $this->new_blog_id, $this->user_id );

        if ( defined('NBT_LEGACY_PATH_RESOLUTION') && NBT_LEGACY_PATH_RESOLUTION ) {
            switch_to_blog( $this->new_blog_id );
            $dir_to_copy_into = WP_CONTENT_DIR . '/blogs.dir/' . $this->new_blog_id . '/files/';
            restore_current_blog();
        }
        else {
            $dir_to_copy_into = $this->_get_files_fs_path( $this->new_blog_id ); //ABSPATH .'wp-content/blogs.dir/' . $blog_id . '/files';
        }
        $dir_to_copy_into = apply_filters('blog_templates-copy-target_directory', $dir_to_copy_into, $this->template, $this->new_blog_id, $this->user_id);

        if ( is_dir( $dir_to_copy ) ) {
            $result = wp_mkdir_p( $dir_to_copy_into );
            if ($result) {

                include_once( ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php' );
                include_once( ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php' );

                if ( is_object( $wp_filesystem ) )
                    $orig_filesystem = wp_clone( $wp_filesystem );
                else
                    $orig_filesystem = $wp_filesystem;

                $wp_filesystem = new WP_Filesystem_Direct( false );

                if ( ! defined('FS_CHMOD_DIR') )
                    define('FS_CHMOD_DIR', 0755 );
                if ( ! defined('FS_CHMOD_FILE') )
                    define('FS_CHMOD_FILE', 0644 );

                $result = copy_dir( $dir_to_copy, $dir_to_copy_into );

                unset( $wp_filesystem );
                
                if ( is_object( $orig_filesystem ) )
                    $wp_filesystem = wp_clone( $orig_filesystem );
                else
                    $wp_filesystem = $orig_filesystem;

                if ( @file_exists( $dir_to_copy_into . '/sitemap.xml' ) )
                    @unlink( $dir_to_copy_into . '/sitemap.xml' );

                // If we set the same theme, we need to replace URLs in theme mods
                if ( $this->settings['to_copy']['settings'] ) {
                    $mods = get_theme_mods();
                    array_walk_recursive( $mods, array( &$this, 'set_theme_mods_url' ), array( $template_content_url, $new_content_url, $this->template_blog_id, $this->new_blog_id ) );
                    update_option( "theme_mods_$theme_slug", $mods );
                }

                // We need now to change the attachments URLs
                $attachment_guids = array();
                foreach ( $template_attachments as $attachment ) {
                    $new_url = str_replace( $template_content_url, $new_content_url, dirname( $attachment->guid ) );
                    $new_url = str_replace( 'sites/' . $this->template_blog_id, 'sites/' . $this->new_blog_id, $new_url );
                    $new_url = str_replace( 'blogs.dir/' . $this->template_blog_id, 'blogs.dir/' . $this->new_blog_id, $new_url );
                
                    // We get an array with key = old_url and value = new_url
                    $attachment_guids[ dirname( $attachment->guid ) ] = $new_url;
                }
                
                $this->set_attachments_urls( $attachment_guids );
                

            } else {
                $error = '<div id="message" class="error"><p>' . sprintf( __( 'File System Error: Unable to create directory %s. (New Blog Templates - While copying files)', 'blog_templates' ), $dir_to_copy_into ) . '</p></div>';
                $wpdb->query( 'ROLLBACK;' );
                restore_current_blog();
                wp_die( $error );

            }
        }
	}

	public function copy_additional_tables() {
		global $wpdb;

		$new_prefix = $wpdb->prefix;

		switch_to_blog( $this->template_blog_id );
        $template_prefix = $wpdb->prefix;
        restore_current_blog();

        foreach ( $this->settings['additional_tables'] as $add ) {
            $add = mysql_escape_string( $add ); //Just in case

            $result = $wpdb->get_results( "SHOW TABLES LIKE '" . str_replace( $template_prefix, $new_prefix, $add) . "'", ARRAY_N );
            if (!empty($result)) { //Is the table present? Clear it, then copy
                //echo ("table exists: $add<br/>");
                $this->clear_table($add);
                //Copy the DB
                $this->copy_table($template['blog_id'],str_replace($template_prefix,'',$add));
            } else { //The table's not present, add it and copy the data from the old one
                //echo ('table doesn\'t exist<br/>');
/* -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- */
                // Changed
                if (class_exists("m_wpdb")) {
                    $conns = $wpdb->dbh_connections;
                    //$multi_db = $conns['global']['name'] . '.';
                    unset($conns['global']);
                    $current = current($conns);
                    $current_db = $current['name'] . '.';
                    $add_table = explode('.', $add);
                    $add_table = $add_table[1];
                } else {
                    $multi_db = $current_db = '';
                    $add_table = $add;
                }
                $wpdb->query("CREATE TABLE IF NOT EXISTS {$current_db}" . str_replace($template_prefix,$new_prefix,$add_table) . " LIKE {$add}");
                $wpdb->query("INSERT {$current_db}" . str_replace($template_prefix,$new_prefix,$add_table) . " SELECT * FROM {$add}");
                // End changed
/* -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- */

                if (!empty($wpdb->last_error)) {
                    $error = '<div id="message" class="error"><p>' . sprintf( __( 'Insertion Error: %s - The template was not applied. (New Blog Templates - With CREATE TABLE query for Additional Tables)', 'blog_templates' ), $wpdb->last_error ) . '</p></div>';
                    $wpdb->query("ROLLBACK;");

                    //We've rolled it back and thrown an error, we're done here
                    restore_current_blog();
                    wp_die($error);
                }
            }
        }
	}

	/**
     * Proper blog filesystem path finding.
     * @param  int $blog_id Blog ID to check
     * @return string Filesystem path
     */
    private function _get_files_fs_path( $blog_id ) {
        if ( ! is_numeric( $blog_id ) ) 
        	return false;

        switch_to_blog( $blog_id );
        $info = wp_upload_dir();
        restore_current_blog();

        return ! empty( $info['basedir'] ) ? $info['basedir'] : false;
    }

    

    function set_theme_mods_url( &$item, $key, $userdata = array() ) {
        $template_upload_url = $userdata[0];
        $new_upload_url = $userdata[1];
        $template_blog_id = $userdata[2];
        $new_blog_id = $userdata[3];

        if ( ! $template_upload_url || ! $new_upload_url )
            return;
        
        if ( is_string( $item ) ) {
            $item = str_replace( $template_upload_url, $new_upload_url, $item );
            $item = str_replace( 'sites/' . $template_blog_id . '/', 'sites/' . $new_blog_id . '/', $item );
            $item = str_replace( 'blogs.dir/' . $template_blog_id . '/', 'blogs.dir/' . $new_blog_id . '/', $item );
        }

    }     

    /**
     * Changes the base URL for all attachments
     * 
     * @since 1.6.5
     */
    function set_attachments_urls( $attachment_guids ) {
        global $wpdb;

        $queries = array();
        foreach ( $attachment_guids as $old_guid => $new_guid ) {
            $queries[] = $wpdb->prepare( "UPDATE $wpdb->posts SET guid = REPLACE( guid, '%s', '%s' ) WHERE post_type = 'attachment'",
                $old_guid,
                $new_guid
            );
        }

        foreach ( $queries as $query )
            $wpdb->query( $query );

    }


	 /**
    * Copy the templated blog posts table. Bit different from the
    * previous one, it can make difference between
    * posts and pages
    *
    * @param int $templated_blog_id The ID of the blog to copy
    * @param string $type post, page, postmeta or pagemeta
    *
    * @since 1.0
    */
    function copy_posts_table( $templated_blog_id, $type, $categories = false ) {
        global $wpdb;

        switch( $type ) {
            case 'posts': $table = 'posts'; break;
            case 'postmeta': $table = 'postmeta'; break;
            case 'pages': $table = 'posts'; break;
            case 'pagemeta': $table = 'postmeta'; break;
            case 'attachment': $table = 'post'; break;
            case 'attachmentmeta': $table = 'postmeta'; break;
        }
        
        do_action('blog_templates-copying_table', $table, $templated_blog_id);

        //Switch to the template blog, then grab the values
        switch_to_blog($templated_blog_id);
        $query = "SELECT t1.* FROM {$wpdb->$table} t1 ";

        if ( 'posts' == $type ) {
            if ( is_array( $categories ) && count( $categories ) > 0 ) 
                $query .= " INNER JOIN $wpdb->term_relationships t2 ON t2.object_id = t1.ID ";

            $query .= "WHERE t1.post_type != 'page'";

            if ( is_array( $categories ) && count( $categories ) > 0 ) {
                $categories_list = '(' . implode( ',', $categories ) . ')';
                $query .= " AND t2.term_taxonomy_id IN $categories_list GROUP BY t1.ID";
            }

        }
        elseif ( 'postmeta' == $type ) {
            $query .= "INNER JOIN $wpdb->posts t2 ON t1.post_id = t2.ID WHERE t2.post_type != 'page'";
        }
        elseif ( 'pages' == $type ) {
            $query .= "WHERE t1.post_type = 'page'";

            $pages_ids = $categories;
            if ( is_array( $pages_ids ) && count( $pages_ids ) > 0 ) {
                $query .= " AND t1.ID IN (" . implode( ',', $pages_ids ) . ")";
            }
        }
        elseif ( 'pagemeta' == $type ) {
            $query .= "INNER JOIN $wpdb->posts t2 ON t1.post_id = t2.ID WHERE t2.post_type = 'page'";

            $pages_ids = $categories;
            if ( is_array( $pages_ids ) && count( $pages_ids ) > 0 ) {
                $query .= " AND t2.ID IN (" . implode( ',', $pages_ids ) . ")";
            }
        }

        $templated = $wpdb->get_results( $query );
        restore_current_blog(); //Switch back to the newly created blog

        if ( count( $templated ) )
            $to_remove = $this->get_fields_to_remove( $table, $templated[0] );

        //Now, insert the templated settings into the newly created blog
        foreach ( $templated as $row ) {
            $row = (array)$row;

            foreach ( $row as $key => $value ) {
                if ( in_array( $key, $to_remove ) )
                    unset( $row[$key] );
            }
            
            $process = apply_filters( 'blog_templates-process_row', $row, $table, $templated_blog_id );
            if ( ! $process ) 
            	continue; 

            $wpdb->insert( $wpdb->$table, $process );
            if ( ! empty( $wpdb->last_error ) ) {
                $error = '<div id="message" class="error"><p>' . sprintf( __( 'Insertion Error: %1$s - The template was not applied. (New Blog Templates - While copying %2$s)', 'blog_templates' ), $wpdb->last_error, $table ) . '</p></div>';
                $wpdb->query("ROLLBACK;");

                //We've rolled it back and thrown an error, we're done here
                restore_current_blog();
                wp_die($error);
            }
        }
    }

    /**
    * Added to automate comparing the two tables, and making sure no old fields that have been removed get copied to the new table
    *
    * @param mixed $new_table_name
    * @param mixed $old_table_row
    *
    * @since 1.0
    */
    function get_fields_to_remove( $new_table_name, $old_table_row ) {
        //make sure we have something to compare it to
        if ( empty( $old_table_row ) ) 
        	return false;

        //We need the old table row to be in array format, so we can use in_array()
        $old_table_row = (array)$old_table_row;

        global $wpdb;

        //Get the new table structure
        $new_table = (array)$wpdb->get_results( "SHOW COLUMNS FROM {$wpdb->$new_table_name}" );

        $new_fields = array();
        foreach( $new_table as $row ) {
            $new_fields[] = $row->Field;
        }

        $results = array();

        //Now, go through the columns in the old table, and check if there are any that don't show up in the new table
        foreach ( $old_table_row as $key => $value ) {
            if ( ! in_array( $key,$new_fields ) ) { //If the new table doesn't have this field
                //There's a column that isn't in the new one, make note of that
                $results[] = $key;
            }
        }

        //Return the results array, which should contain all of the fields that don't appear in the new table
        return $results;
    }

    /**
    * Deletes everything from a table
    *
    * @param string $table The name of the table to clear
    *
    * @since 1.0
    */
    public function clear_table( $table ) {
        global $wpdb;

        do_action('blog_templates-clearing_table', $table);

        //Delete the current categories
        $wpdb->query("DELETE FROM $table");

        if ($wpdb->last_error) { //No error. Good! Now copy over the terms from the templated blog
            $error = '<div id="message" class="error"><p>' . sprintf( __( 'Deletion Error: %1$s - The template was not applied. (New Blog Templates - While clearing %2$s)', 'blog_templates' ), $wpdb->last_error, $table ) . '</p></div>';
            $wpdb->query("ROLLBACK;");
            restore_current_blog(); //Switch back to our current blog
            wp_die($error);
        }
    }

    /**
    * Copy the templated blog table
    *
    * @param int $templated_blog_id The ID of the blog to copy
    * @param string $table The name of the table to copy
    *
    * @since 1.0
    */
    function copy_table( $templated_blog_id, $table ) {
        global $wpdb;
        
        do_action( 'blog_templates-copying_table', $table, $templated_blog_id );

        //Switch to the template blog, then grab the values
        switch_to_blog( $templated_blog_id );
        $templated = $wpdb->get_results( "SELECT * FROM {$wpdb->$table}" );
        restore_current_blog(); //Switch back to the newly created blog

        if ( count( $templated ) )
            $to_remove = $this->get_fields_to_remove($table, $templated[0]);

        //Now, insert the templated settings into the newly created blog
        foreach ($templated as $row) {
            $row = (array)$row;

            foreach ( $row as $key => $value ) {
                if ( in_array( $key, $to_remove ) )
                    unset( $row[ $key ] );
            }
            
            $process = apply_filters('blog_templates-process_row', $row, $table, $templated_blog_id);
            if ( ! $process ) 
            	continue; 

            //$wpdb->insert($wpdb->$table, $row);
            $wpdb->insert( $wpdb->$table, $process );
            if ( ! empty( $wpdb->last_error ) ) {
                $error = '<div id="message" class="error"><p>' . sprintf( __( 'Insertion Error: %1$s - The template was not applied. (New Blog Templates - While copying %2$s)', 'blog_templates' ), $wpdb->last_error, $table ) . '</p></div>';
                $wpdb->query("ROLLBACK;");

                //We've rolled it back and thrown an error, we're done here
                restore_current_blog();
                wp_die($error);
            }
        }
    }

     /**
     * Copy the templated menu and locations
     * 
     * @since 1.6.6
     * 
     * @param int $templated_blog_id The ID of the blog to copy
     * @param int $new_blog_id The ID of the new blog
     *
     */
    function old_copy_menu( $templated_blog_id, $new_blog_id ) {
        global $wpdb;

        do_action( 'blog_templates-copying_menu', $templated_blog_id, $new_blog_id );

        switch_to_blog( $templated_blog_id );
        $templated_posts_table = $wpdb->posts;
        $templated_postmeta_table = $wpdb->postmeta;
        $templated_term_relationships_table = $wpdb->term_relationships;

        $menu_locations = get_nav_menu_locations();
        restore_current_blog();

        switch_to_blog( $new_blog_id );
        $new_posts_table = $wpdb->posts;
        $new_postmeta_table = $wpdb->postmeta;
        $new_term_relationships_table = $wpdb->term_relationships;

        $new_blog_locations = $menu_locations;

        restore_current_blog();
        
        $menus = $wpdb->get_col(
            "SELECT ID FROM $templated_posts_table
            WHERE post_type = 'nav_menu_item'"
        );

        if ( ! empty( $menus ) ) {

            // Duplicating the menu locations
            set_theme_mod( 'nav_menu_locations', $new_blog_locations );

            // Duplicating every menu item
            // We cannot use nav-menu functions as we need
            // to keep all the old IDs
            $menus = '(' . implode( ',', $menus ) . ')';
            $wpdb->query(
                "INSERT IGNORE INTO $new_posts_table
                SELECT * FROM $templated_posts_table
                WHERE ID IN $menus"
            );

            $wpdb->query(
                "INSERT IGNORE INTO $new_postmeta_table
                SELECT * FROM $templated_postmeta_table
                WHERE post_id IN $menus"
            );

            $wpdb->query(
                "INSERT IGNORE INTO $new_term_relationships_table
                SELECT * FROM $templated_term_relationships_table
                WHERE object_id IN $menus"
            );


        }
    }

    function copy_menu( $templated_blog_id, $new_blog_id ) {
        global $wpdb;

        do_action( 'blog_templates-copying_menu', $templated_blog_id, $new_blog_id);

        switch_to_blog( $templated_blog_id );
        $templated_posts_table = $wpdb->posts;
        $templated_postmeta_table = $wpdb->postmeta;
        $templated_terms_table = $wpdb->terms;
        $templated_term_taxonomy_table = $wpdb->term_taxonomy;
        $templated_term_relationships_table = $wpdb->term_relationships;

        $menu_locations = get_nav_menu_locations();
        restore_current_blog();

        switch_to_blog( $new_blog_id );
        $new_posts_table = $wpdb->posts;
        $new_postmeta_table = $wpdb->postmeta;
        $new_terms_table = $wpdb->terms;
        $new_term_taxonomy_table = $wpdb->term_taxonomy;
        $new_term_relationships_table = $wpdb->term_relationships;

        $new_blog_locations = $menu_locations;

        set_theme_mod( 'nav_menu_locations', $new_blog_locations );
        restore_current_blog();
        
        // First, the menus
        $menus_ids = implode( ',', $menu_locations );
        $menus = $wpdb->get_results(
            "SELECT * FROM $templated_terms_table
            WHERE term_id IN ( $menus_ids )"
        );


        //$menus = $wpdb->get_col(
        //    "SELECT ID FROM $templated_posts_table
        //    WHERE post_type = 'nav_menu_item'"
        //);

        if ( ! empty( $menus ) ) {

            foreach ( $menus as $menu ) {

                // Inserting the menu
                $wpdb->insert(
                    $new_terms_table,
                    array(
                        'term_id' => $menu->term_id,
                        'name' => $menu->name,
                        'slug' => $menu->slug,
                        'term_group' => $menu->term_group
                    ),
                    array( '%d', '%s', '%s', '%d' )
                );

                // Terms taxonomies
                $term_taxonomies = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT * FROM $templated_term_taxonomy_table
                        WHERE term_id = %d",
                        $menu->term_id
                    )
                );

                $terms_taxonomies_ids = array();
                foreach ( $term_taxonomies as $term_taxonomy ) {
                    $terms_taxonomies_ids[] = $term_taxonomy->term_taxonomy_id;

                    // Inserting terms taxonomies
                    $wpdb->insert(
                        $new_term_taxonomy_table,
                        array(
                            'term_taxonomy_id' => $term_taxonomy->term_taxonomy_id,
                            'term_id' => $term_taxonomy->term_id,
                            'taxonomy' => $term_taxonomy->taxonomy,
                            'description' => empty( $term_taxonomy->description ) ? '' : $term_taxonomy->description,
                            'parent' => $term_taxonomy->parent,
                            'count' => $term_taxonomy->count
                        ),
                        array( '%d', '%d', '%s', '%d', '%d' )
                    );
                }

                $terms_taxonomies_ids = implode( ',', $terms_taxonomies_ids );

                $term_relationships = $wpdb->get_results(
                    "SELECT * FROM $templated_term_relationships_table
                    WHERE term_taxonomy_id IN ( $terms_taxonomies_ids )"
                );

                $objects_ids = array();
                foreach ( $term_relationships as $term_relationship ) {
                    $objects_ids[] = $term_relationship->object_id;

                    // Inserting terms relationships
                    $wpdb->insert(
                        $new_term_relationships_table,
                        array(
                            'object_id' => $term_relationship->object_id,
                            'term_taxonomy_id' => $term_relationship->term_taxonomy_id,
                            'term_order' => $term_relationship->term_order,
                        ),
                        array( '%d', '%d', '%d' )
                    );
                }

                $objects_ids = implode( ',', $objects_ids );

                // Inserting the objects
                $objects = $wpdb->get_results(
                    "INSERT IGNORE INTO $new_posts_table
                    SELECT * FROM $templated_posts_table
                    WHERE ID IN ( $objects_ids )"
                );

                // Inserting the objects meta
                $objects_meta = $wpdb->get_results(
                    "INSERT IGNORE INTO $new_postmeta_table
                    SELECT * FROM $templated_postmeta_table
                    WHERE post_id IN ( $objects_ids )"
                );

            }

        }
    }

    /**
     * Replace the manually inserted links in menus
     * 
     * @return type
     */
    function set_menus_urls( $templated_blog_id, $blog_id ) {
        global $wpdb;

        $pattern = '/^(http|https):\/\//';
        switch_to_blog( $templated_blog_id );
        $templated_home_url = preg_replace( $pattern, '', home_url() );
        restore_current_blog();

        switch_to_blog( $blog_id );
        $new_home_url = preg_replace( $pattern, '', home_url() );

        $sql = "SELECT * FROM $wpdb->postmeta WHERE meta_key = '_menu_item_url';";
        $results = $wpdb->get_results( $sql );

        foreach ( $results as $row ) {
            $meta_value = preg_replace( $pattern, '', $row->meta_value );
            if ( strpos( $meta_value, $templated_home_url ) !== false ) {
                //UPDATE 
                $meta_value = str_replace( $templated_home_url, $new_home_url, $row->meta_value );
                $sql = $wpdb->prepare( "UPDATE $wpdb->postmeta SET meta_value = %s WHERE meta_id = %d;", $meta_value, $row->meta_id );
                $wpdb->query( $sql );
            }
        }
        restore_current_blog();
    }

    /**
     * Reset the terms counts to 0
     * 
     * @param Integer $blog_id 
     */
    function reset_terms_counts() {
        
        global $wpdb;
        $result = $wpdb->query( "UPDATE $wpdb->term_taxonomy SET count = 0" );
    }
}