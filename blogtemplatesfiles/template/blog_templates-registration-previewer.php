<div id="blog_template-selection">
	<div class="blog_template-option">
		<label for="blog_template"><?php _e('Select a template', 'blog_templates') ?></label>
	<?php 
	foreach ($templates as $tkey => $template) { 
		switch_to_blog($template['blog_id']);
		$img = untrailingslashit(dirname(get_stylesheet_uri())) . '/screenshot.png';
		$blog_url = site_url();
		restore_current_blog();	
		$tplid = $template['name'];
		$default = @$this->options['default'] == $tkey ? "blog_template-default_item" : "";
	?>
		<div class="theme-previewer-wrap <?php echo $default; ?>" id="theme-previewer-wrap-<?php echo $tkey;?>">
			<a href="#<?php echo $tplid; ?>" class="blog_template-item_selector">
				<img src="<?php echo $img;?>" />
				<input type="radio" name="blog_template" id="blog-template-radio-<?php echo $tkey;?>" <?php checked( ! empty( $default ) ); ?> value="<?php echo $tkey;?>" style="display: none" />
				<div class="theme-previewer-overlay">

					<span class="template-name"><?php echo $tplid; ?></span> <button class="view-demo-button" data-blog-url="<?php echo $blog_url;?>"><?php _e( 'View demo', 'blog_templates' ); ?></button><br/><br/>
					<button class="select-theme-button" data-theme-key="<?php echo $tkey;?>"><?php _e( 'Use this Theme', 'blog_templates' ); ?></button>
				</div>
			</a>
		</div>
	<?php } ?>
	</div>
</div>
<style>
	.theme-previewer-wrap {
		width:45%;
		float:left;
		margin-right:10%;
		margin-bottom:25px;
		box-sizing:border-box;
		position:relative;
		border-color:transparent;
		border-style: solid;
		border-width:1px;
	}
	.blog_template-default_item {
		border-color:#333;
	}
	.theme-previewer-wrap:hover .theme-previewer-overlay {
		opacity:1;
	}
	.theme-previewer-wrap:nth-child(odd) {
		margin-right:0px;
	}
	.blog_template-item_selector img {
		max-width:100%;
	}
	.theme-previewer-overlay {
		opacity:0;
		background:#333;
		background:rgba(51, 51, 51, 0.6);
		height:100%;
		position:absolute;
		top:0;
		left:0;
		width:100%;
		box-sizing:border-box;
		text-align: center;
		padding-top:100px;
	}
	.select-theme-button {
		font-size:1.2em;
		font-weight:bold;
	}
	.template-name {
		display:inline-block;
		font-size:1.1em;
		color:white;
		text-shadow:1px 1px 1px black;
	}
</style>
<script type="text/javascript">
jQuery(document).ready(function($) {
	$('.view-demo-button, .select-theme-button').on( 'click', function(e) {
		e.preventDefault();
		var theme_key = $(this).data('theme-key');
		var wrap = $('#theme-previewer-wrap-' + theme_key );
		$('.theme-previewer-wrap').removeClass('blog_template-default_item');
		wrap.addClass('blog_template-default_item');

		$('input[name=blog_template]').attr('checked',false);
		$('#blog-template-radio-'+theme_key).attr('checked',true);
	});
	$('.view-demo-button').on('click', function(e) {
		e.preventDefault();
		window.open($(this).data('blog-url'));
	});
});
</script>