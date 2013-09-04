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
	<p class="blog_template-option">
		<label for="blog_template"><?php _e('Select a template', 'blog_templates') ?></label>
		<?php
			// Print templates dropdown selection box.
			// Pass false as second argument to force a template to be selected.
			$this->get_template_dropdown('blog_template', true);
		?>
	</p>
</div>