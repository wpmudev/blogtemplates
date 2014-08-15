<div class="wrap">
	<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

	<?php if ( ! empty( $errors ) ): ?>
		<div class="error">
			<?php foreach ( $errors  as $error ): ?>
				<p><?php echo $error; ?></p>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

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
					<h3><?php _e( 'Add new category', 'blog_templates' ); ?></h3>
					<form id="categories-table-form" action="<?php echo esc_url( remove_query_arg( 'updated' ) ); ?>" method="post">
						<?php wp_nonce_field( 'add-nbt-category' ); ?>
						<div class="form-field">
							<label for="cat_name"><?php _e( 'Category Name', 'blog_templates' ); ?>
								<input name="cat_name" id="cat_name" type="text" value="<?php echo esc_attr( $posted_name ); ?>" size="40" aria-required="true">
							</label>
						</div>
						<div class="form-field">
							<label for="cat_description"><?php _e( 'Category Description', 'blog_templates' ); ?>
								<textarea name="cat_description" rows="5" cols="40"><?php echo esc_textarea( $posted_desc ); ?></textarea>
							</label>
						</div>
						<?php submit_button( __( 'Add New Category', 'blog_templates' ), 'primary', 'submit-nbt-new-category' ); ?>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>