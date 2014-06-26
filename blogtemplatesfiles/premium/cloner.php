<?php

class NBT_Cloner {

	public $admin_menu_id;

	public function __construct() {

		add_filter( 'manage_sites_action_links', array( &$this, 'add_site_action_link' ), 10, 2 );
		add_action( 'network_admin_menu', array( &$this, 'add_admin_menu' ) );
		
	}

	public function add_site_action_link( $links, $blog_id ) {
		$clone_url = add_query_arg( 'blog_id', $blog_id, network_admin_url( 'index.php?page=clone_site' ) );

		if ( ! is_main_site( $blog_id ) && $blog_id !== 1 )
			$links['clone'] = '<span class="clone"><a href="' . $clone_url . '">Clone</a></span>';
		return $links;
	}

	public function add_admin_menu() {
		$this->admin_menu_id = add_submenu_page( null, __( 'Clone Site', NBT_PLUGIN_LANG_DOMAIN ), __( 'Clone Site', NBT_PLUGIN_LANG_DOMAIN ), 'manage_network', 'clone_site', array( &$this, 'render_admin_menu' ) );
		add_action( 'load-' . $this->admin_menu_id, array( $this, 'sanitize_clone_form' ) );
	}

	public function render_admin_menu() {
		global $current_site;

		if ( isset( $_REQUEST['cloned'] ) ) {
			$messages = array(
				sprintf( __( 'Your new site has been cloned. <a href="%s">Go to dashboard</a>', NBT_PLUGIN_LANG_DOMAIN ), get_admin_url( $_REQUEST['cloned'] ) )
			);
		}

		$blog_id = absint( $_REQUEST['blog_id'] );
		?>
			<div class="wrap">
				<h2><?php _e( 'Clone Site', NBT_PLUGIN_LANG_DOMAIN ); ?></h2>
				
					<?php
					if ( ! empty( $messages ) ) {
						foreach ( $messages as $msg )
							echo '<div id="message" class="updated"><p>' . $msg . '</p></div>';
					} ?>
					<form method="post" action="<?php echo add_query_arg( 'action', 'clone', network_admin_url( 'index.php?page=clone_site' ) ); ?>">

						<?php wp_nonce_field( 'clone-site-' . $blog_id, '_wpnonce_clone-site' ) ?>
						<input type="hidden" name="blog_id" value="<?php echo $blog_id; ?>" />

						<table class="form-table">
							<tr class="form-field form-required">
								<th scope="row"><?php _e( 'Site Address' ) ?></th>
								<td>
									<?php if ( is_subdomain_install() ) { ?>
										<input name="blog" type="text" class="regular-text" title="<?php esc_attr_e( 'Domain' ) ?>"/><span class="no-break">.<?php echo preg_replace( '|^www\.|', '', $current_site->domain ); ?></span>
									<?php } else {
										echo $current_site->domain . $current_site->path ?><input name="blog" class="regular-text" type="text" title="<?php esc_attr_e( 'Domain' ) ?>"/>
									<?php }
									echo '<p>' . __( 'Only lowercase letters (a-z) and numbers are allowed.' ) . '</p>';
									?>
								</td>
							</tr>
						</table>
						<?php submit_button( __( 'Clone Site', NBT_PLUGIN_LANG_DOMAIN ), 'primary', 'clone-site-submit' ); ?>
					</form>
			</div>
		<?php
	}

	function sanitize_clone_form() {
		$blog_id = ! empty( $_REQUEST['blog_id'] ) ? absint( $_REQUEST['blog_id'] ) : 0;
		$blog_details = get_blog_details( $blog_id );

		if ( ! $blog_id || empty( $blog_details ) )
			wp_die( __( 'The blog that you are trying to copy does not exist', NBT_PLUGIN_LANG_DOMAIN ) );

		if ( ! empty( $_REQUEST['clone-site-submit'] ) ) {
			// Submitting form
			check_admin_referer( 'clone-site-' . $blog_id, '_wpnonce_clone-site' );

			if ( empty( $_REQUEST['blog'] ) )
				wp_die( __( 'Can&#8217;t create an empty site.' ) );

			$blog = $_REQUEST['blog'];
			$domain = '';
			if ( preg_match( '|^([a-zA-Z0-9-])+$|', $blog ) )
				$domain = strtolower( $blog );

			if ( empty( $domain ) )
				wp_die( __( 'Missing or invalid site address.' ) );

			$dest_blog_details = get_blog_details( $domain );

			if ( ! isset( $_REQUEST['confirm'] ) && ! empty( $dest_blog_details ) ) {

				if ( $dest_blog_details->blog_id == $blog_id )
					wp_die( 'You cannot copy a blog to itself', NBT_PLUGIN_LANG_DOMAIN );
				
				$clone_link = add_query_arg( 
					array(
						'action' => 'clone',
						'blog' => $domain,
						'blog_id' => $blog_id,
						'confirm' => 'true',
						'clone-site-submit' => 'true'
					), 
					network_admin_url( 'index.php?page=clone_site' ) 
				);

				$clone_link = wp_nonce_url( $clone_link, 'clone-site-' . $blog_id, '_wpnonce_clone-site' );
				ob_start();
				?>
					<p><?php printf( __( 'You have chosen a URL that already exists. If you choose ‘Continue’, all existing site content and settings on %s will be completely overwritten with content and settings from %s. This change is permanent and can’t be undone, so please be careful. ', NBT_PLUGIN_LANG_DOMAIN ), '<strong>' . get_site_url( $dest_blog_details->blog_id ) . '</strong>', '<strong>' . get_site_url( $blog_details->blog_id ) . '</strong>' ); ?></p>
					<a href="<?php echo $clone_link; ?>" class="button button-primary"><?php _e( 'Continue', NBT_PLUGIN_LANG_DOMAIN ); ?></a>
				<?php
				$content = ob_get_clean();
				wp_die( $content );
			}
			
			$blog_details = get_blog_details( $domain );
		    if ( ! empty( $blog_details ) ) {
		        if ( $blog_details->blog_id === 1 || is_main_site( $blog_details->blog_id ) )
		            wp_die( __( 'Sorry, main site cannot be overwritten' ) );

		        if ( $blog_details->blog_id == $blog_id )
		            wp_die( __( 'You cannot clone a blog to its own domain' ) );

		        $args['override'] = absint( $blog_details->blog_id );
		    }

			
			// New Blog Templates
			$action_order = defined('NBT_APPLY_TEMPLATE_ACTION_ORDER') && NBT_APPLY_TEMPLATE_ACTION_ORDER ? NBT_APPLY_TEMPLATE_ACTION_ORDER : 9999;
			remove_action( 'wpmu_new_blog', array( 'blog_templates', 'set_blog_defaults'), apply_filters('blog_templates-actions-action_order', $action_order), 6); // Set to *very high* so this runs after every other action; also, accepts 6 params so we can get to meta

			$current_site = get_current_site();

			if ( is_subdomain_install() ) {
				$domain = $domain . '.' . $current_site->domain;
				$path = '';
			}
			else {
				$path = '/' . $domain;
				$domain = $current_site->domain;
			}

			$result = $this->pre_clone_actions( $blog_id, $domain, $path, $args );

			if ( is_integer( $result ) ) {

				$redirect_to = add_query_arg(
					array(
						'cloned' => $result,
						'blog_id' => $blog_id
					),
					network_admin_url( 'index.php?page=clone_site' )
				);

				wp_redirect( $redirect_to );	
				exit;
			}
			
		}
	}

	public function pre_clone_actions( $source_blog_id, $domain, $path, $args ) {
        global $wpdb;

        $defaults = array(
            'title' => 'Test Site',
            'override' => false
        );
        $args = wp_parse_args( $args, $defaults );
        extract( $args );

        $blog_details = get_blog_details( $override );
        if ( empty( $blog_details ) )
            $override = false;

        $new_blog_id = $override;
        if ( ! $override )
            $new_blog_id = create_empty_blog( $domain, $path, $title );            

        if ( ! is_integer( $new_blog_id ) )
            return new WP_Error( 'create_empty_blog', strip_tags( $new_blog_id ) );


        $result = nbt_set_copier_args( $source_blog_id, $new_blog_id );

        return $new_blog_id;
    }

}

$nbt_cloner = new NBT_Cloner();