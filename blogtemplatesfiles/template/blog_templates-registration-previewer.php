<div id="blog_template-selection">
	<div class="blog_template-option">
		<label for="blog_template"><?php _e('Select a template', 'blog_templates') ?></label>
	<?php 
	foreach ($templates as $tkey => $template) { 
		switch_to_blog($template['blog_id']);
		$img = untrailingslashit(dirname(get_stylesheet_uri())) . '/screenshot.png';
		restore_current_blog();	
		$tplid = preg_replace('/[^a-z0-9]/i', '', strtolower($template['name'])) . "-{$tkey}";
		$default = @$this->options['default'] == $tkey ? "blog_template-default_item" : "";
	?>
		<a href="#<?php echo $tplid; ?>" class="blog_template-item_selector <?php echo $default; ?>">
			<img src="<?php echo $img;?>" />
			<input type="radio" name="blog_template" value="<?php echo $tkey;?>" style="display: none" />
		</a>
	<?php } ?>
	</div>
</div>