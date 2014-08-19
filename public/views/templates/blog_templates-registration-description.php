<?php
/**
 * Radio-box selection with descriptions template. 
 * 
 * Copy this file into your theme directory and edit away!
 */
?>

<br style="clear:both" />

<div id="blog_template-selection">
	
	<h3><?php _e('Select a template', 'blog_templates') ?></h3>

	<?php do_action( 'nbt_before_templates_signup' ); ?>

    <div class="blog_template-option">
	    <?php if ( nbt_have_templates() ): while ( nbt_have_templates() ): nbt_the_template(); ?>
	   			<div class="template-signup-item theme-radio-wrap" id="theme-screenshot-radio-<?php echo nbt_get_the_template_ID();?>">
					<label for="blog_template-<?php echo nbt_get_the_template_ID(); ?>">
						<input type="radio" id="blog_template-<?php echo nbt_get_the_template_ID(); ?>" name="blog_template" <?php checked( nbt_get_the_template_ID() == nbt_get_selected_template_ID() ); ?> value="<?php echo nbt_get_the_template_ID();?>" />
						<strong><?php echo nbt_get_the_template_name(); ?></strong>
					</label>
					<div class="blog_template-description">
						<?php echo nbt_get_the_template_description(); ?>
					</div>
				</div>
			<?php endwhile; ?>
    	<?php endif; ?>			
	</div>

</div>