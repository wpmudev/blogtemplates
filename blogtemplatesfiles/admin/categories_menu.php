<?php

class blog_templates_categories_menu {
	/**
     * @var string $localization_domain Domain used for localization
     *
     * @since 1.0
     */
    var $localization_domain = 'blog_templates';

    /**
    * @var array $options Stores the options for this plugin
    *
    * @since 1.0
    */
    var $options = array();

    var $menu_slug = 'blog_templates_categories';

    var $page_id;

    var $updated_message = '';

    var $current_category;

    var $errors = false;

    function __construct() {

		add_action( 'network_admin_menu', array( $this, 'network_admin_page' ) );

        // Admin notices and data processing
        add_action( 'admin_init', array($this, 'validate_form' ) );

        $this->current_category = array( 'name' => '', 'description' => '' );
	}

	/**
     * Adds the options subpanel
     *
     * @since 1.2.1
     */
    function network_admin_page() {
        $this->page_id = add_submenu_page( 'blog_templates_settings', __( 'Templates categories', $this->localization_domain ), __( 'Templates categories', $this->localization_domain ), 'manage_network', $this->menu_slug, array($this,'render_page'));
    }

    public function render_page() {

    	if ( ! empty( $this->errors ) ) {
    		?>
				<div class="error"><p><?php echo $this->errors; ?></p></div>
    		<?php
    	}
    	elseif ( isset( $_GET['updated'] ) ) {
    		?>
				<div class="updated">
					<p><?php _e( 'Changes have been applied', $this->localization_domain ); ?></p>
				</div>
    		<?php
    	}
    	?>
			<div class="wrap">
				<?php screen_icon(); ?>
				<h2><?php echo get_admin_page_title(); ?></h2>
				
				<?php if ( isset( $_GET['action'] ) && 'edit' == $_GET['action'] && isset( $_GET['category'] ) && $cat_id = absint( $_GET['category'] ) ): ?>
					
					<?php
						$model = blog_templates_model::get_instance();
						$category = $model->get_template_category( $cat_id );

						if ( ! $category )
							wp_die( __( 'The category does not exist', $this->localization_domain ) );

					?>
					<form id="categories-table-form" action="" method="post">
						<table class="form-table">
							<?php
								ob_start();
							?>	
								<input type="text" name="cat_name" class="large-text" value="<?php echo esc_attr( $category['name'] ); ?>">
							<?php
								$this->render_row( __( 'Category name', $this->localization_domain ), ob_get_clean() );
							?>

							<?php
								ob_start();
							?>	
								<textarea name="cat_description" rows="5" cols="50" class="large-text"><?php echo esc_textarea( $category['description'] ); ?></textarea>
							<?php
								$this->render_row( __( 'Category description', $this->localization_domain ), ob_get_clean() );
							?>
						</table>
						<input type="hidden" name="cat_id" value="<?php echo esc_attr( $cat_id ); ?>">
						<?php wp_nonce_field( 'edit-nbt-category', '_wpnonce' ); ?>
						<?php submit_button( null, 'primary', 'submit-edit-nbt-category' ); ?>
					</form>

				<?php else: ?>
					<?php
						$cats_table = new blog_templates_categories_table();
						$cats_table->prepare_items();
				    ?>
			    	<br class="clear">
					<div id="col-container">
						<div id="col-right">
							<div class="col-wrap">
								<div class="form-wrap">
									<form id="categories-table-form" action="" method="post">
										<?php $cats_table->display(); ?>
									</form>
								</div>
							</div>
						</div>
						<div id="col-left">
							<div class="col-wrap">
								<div class="form-wrap">
									<h3><?php _e( 'Add new category', $this->localization_domain ); ?></h3>
									<form id="categories-table-form" action="" method="post">
										<?php wp_nonce_field( 'add-nbt-category' ); ?>
										<div class="form-field">
											<label for="cat_name"><?php _e( 'Category Name', $this->localization_domain ); ?>
												<input name="cat_name" id="cat_name" type="text" value="<?php echo $this->current_category['name']; ?>" size="40" aria-required="true">
											</label>
										</div>
										<div class="form-field">
											<label for="cat_description"><?php _e( 'Category Description', $this->localization_domain ); ?>
												<textarea name="cat_description" rows="5" cols="40"><?php echo esc_textarea( $this->current_category['description'] ); ?></textarea>
											</label>
										</div>
										<?php submit_button( __( 'Add New Category', $this->localization_domain ), 'primary', 'submit-nbt-new-category' ); ?>
									</form>
								</div>
							</div>
						</div>
					</div>
				<?php endif; ?>

			</div>
    	<?php
    }

	public function validate_form() {
		if ( isset( $_POST['submit-edit-nbt-category'] ) ) {
			if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'edit-nbt-category' ) )
				wp_die( __( 'Security check error', $this->localization_domain ) );

			if ( isset( $_POST['cat_name'] ) && ! empty( $_POST['cat_name'] ) && isset( $_POST['cat_id'] ) ) {
				$model = blog_templates_model::get_instance();

				$description = stripslashes( preg_replace('~<\s*\bscript\b[^>]*>(.*?)<\s*\/\s*script\s*>~is', '', $_POST['cat_description'] ) );
				$name = sanitize_text_field( stripslashes_deep( $_POST['cat_name'] ) );
				$model->update_template_category( absint( $_POST['cat_id'] ), $name, $description );

				$link = remove_query_arg( array( 'action', 'category' ) );
				$link = add_query_arg( 'updated', 'true', $link );
				wp_redirect( $link );
			}
			else {
				$this->errors = __( 'Name cannot be empty', $this->localization_domain );
			}
		}

		if ( isset( $_POST['submit-nbt-new-category'] ) ) {
			if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'add-nbt-category' ) )
				wp_die( __( 'Security check error', $this->localization_domain ) );

			$model = blog_templates_model::get_instance();

			$description = stripslashes( preg_replace('~<\s*\bscript\b[^>]*>(.*?)<\s*\/\s*script\s*>~is', '', $_POST['cat_description'] ) );
			$name = sanitize_text_field( stripslashes_deep( $_POST['cat_name'] ) );

			if ( ! empty( $name ) ) {
				$model->add_template_category( $name, $description );
				$link = remove_query_arg( array( 'action', 'category' ) );
				$link = add_query_arg( 'updated', 'true', $link );
				wp_redirect( $link );
			}
			else {
				$this->errors = __( 'Name cannot be empty', $this->localization_domain );
			}
		}
	}

	private function render_row( $title, $markup ) {
		?>
			<tr valign="top">
				<th scope="row"><label for="site_name"><?php echo $title; ?></label></th>
				<td>
					<?php echo $markup; ?>			
				</td>
			</tr>
		<?php
	}

}