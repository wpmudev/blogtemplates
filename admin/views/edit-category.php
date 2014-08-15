<div class="wrap">
	<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

	<form id="categories-table-form" action="" method="post">
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><label for="cat_name"><?php _e( 'Category name', 'blog_templates' ); ?></label></th>
				<td>
					<input type="text" name="cat_name" class="large-text" value="<?php echo esc_attr( $category['name'] ); ?>">
				</td>
			</tr>

			<tr valign="top">
				<th scope="row"><label for="cat_description"><?php _e( 'Category description', 'blog_templates' ); ?></label></th>
				<td>
					<textarea name="cat_description" rows="5" cols="50" class="large-text"><?php echo esc_textarea( $category['description'] ); ?></textarea>
				</td>
			</tr>
		</table>
		<input type="hidden" name="cat_id" value="<?php echo esc_attr( $cat_id ); ?>">
		<?php wp_nonce_field( 'edit-nbt-category', '_wpnonce' ); ?>
		<?php submit_button( null, 'primary', 'submit-edit-nbt-category' ); ?>
	</form>
</div>