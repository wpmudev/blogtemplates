<?php
/**
 * Simple selection box template. 
 * 
 * Copy this file into your theme directory and edit away!
 * You can also use $templates array to iterate through your templates.
 */
?>
<?php if (defined('BP_VERSION') && 'bp-default' == get_blog_option(bp_get_root_blog_id(), 'stylesheet')) echo '<br style="clear:both" />'; ?>
<div id="blog_template-selection">
	<h3><?php _e('Select a template', 'blog_templates') ?></h3>

	<?php
		if ( $settings['show-categories-selection'] )
			$templates = nbt_theme_selection_toolbar( $templates );
    ?>

    <div class="blog_template-option" style="text-align:center;margin-bottom:30px;">
    	
    		<?php if ( nbt_have_templates() ): ?>
    			<select name="blog_template">
    				<?php if ( ! nbt_get_default_template_ID() ): ?>
    					<option value="none"><?php _e( 'None', 'blog_templates' ); ?></option>
    				<?php endif; ?>
    				<?php while ( nbt_have_templates() ): nbt_the_template(); ?>
    					<option value="<?php echo nbt_get_the_template_ID();?>" <?php selected( nbt_get_the_template_ID() == nbt_get_selected_template_ID() ); ?>><?php echo esc_html( nbt_get_template_name() );?></option>
    				<?php endwhile; ?>
				<?php endwhile; ?>
				</select>
	    	<?php endif; ?>		
		</select>
	</div>
</div>
