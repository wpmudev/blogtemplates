<?php
/**
 * Theme screenshot selection with titles and description template. 
 * 
 * Copy this file into your theme directory and edit away!
 * You can also use $templates array to iterate through your templates.
 */
?>
<?php if (defined('BP_VERSION') && 'bp-default' == get_blog_option(bp_get_root_blog_id(), 'stylesheet')) echo '<br style="clear:both" />'; ?>
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
		<div class="blog_template-item">
			<strong><?php echo strip_tags($template['name']);?></strong><br />
			<a href="#<?php echo $tplid; ?>" class="blog_template-item_selector <?php echo $default; ?>">
				<img src="<?php echo $img;?>" />
				<input type="radio" name="blog_template" value="<?php echo $tkey;?>" style="display: none" />
			</a>
			<div class="blog_template-description">
				<?php echo nl2br(strip_tags($template['description'])); ?>
			</div>
		</div>
	<?php } ?>
	</div>
</div>
<style type="text/css">
.blog_template-option {
	overflow: hidden;
}
.blog_template-item {
	float: left;
	padding: 12px;
	border: 3px solid transparent;
	background: transparent;
}
.blog_template-selected {
	border: 3px solid #ccc;
	background: #eee;
}
</style>
<script type="text/javascript">
(function ($) {
$(function () {

$(".blog_template-item_selector").click(function () {
	$(".blog_template-item").removeClass("blog_template-selected")
	$(".blog_template-item_selector :radio").attr("checked", false);
	$(this)
		.parents(".blog_template-item").addClass("blog_template-selected").end()
		.find(":radio").attr("checked", true)
	;
	return false;	
});
if ($(".blog_template-item_selector.blog_template-default_item").length) $(".blog_template-item_selector.blog_template-default_item").trigger("click");
});
})(jQuery);
</script>
