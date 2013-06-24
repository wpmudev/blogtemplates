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
	
	<h3><?php _e('Select a template', 'blog_templates') ?></h3>
	<?php foreach ($templates as $tkey => $template) { ?>
		<div class="blog_template-option">
			<label for="blog_template-<?php echo $tkey; ?>">
				<input type="radio" id="blog_template-<?php echo $tkey; ?>" name="blog_template" value="<?php echo $tkey;?>" />
				<strong><?php echo strip_tags($template['name']);?></strong>
			</label>
			<div class="blog_template-description">
				<?php echo nl2br( $template['description'] ); ?>
			</div>
		</div>
	<?php } ?>

</div>

<style>
.blog_template-description {
	margin-left:30px;
}
.blog_template-option {
	margin-bottom: 25px;
	border: 1px solid #DEDEDE;
	border-radius: 5px;
	background: #EFEFEF;
	padding: 15px;
}
</style>