<?php
/**
 * Radio-box selection with descriptions template. 
 * 
 * Copy this file into your theme directory and edit away!
 * You can also use $templates array to iterate through your templates.
 */
?>
<?php if (defined('BP_VERSION') && 'bp-default' == get_blog_option(bp_get_root_blog_id(), 'stylesheet')) echo '<br style="clear:both" />'; ?>
<div id="blog_template-selection">
	<div class="blog_template-option">
		<label for="blog_template"><?php _e('Select a template', 'blog_templates') ?></label>
	<?php foreach ($templates as $tkey => $template) { ?>
		<label for="blog_template-<?php echo $tkey; ?>">
			<input type="radio" id="blog_template-<?php echo $tkey; ?>" name="blog_template" value="<?php echo $tkey;?>" />
			<strong><?php echo strip_tags($template['name']);?></strong>
		</label>
		<div class="blog_template-description">
			<?php echo nl2br(strip_tags($template['description'])); ?>
		</div>
	<?php } ?>
	</div>
</div>