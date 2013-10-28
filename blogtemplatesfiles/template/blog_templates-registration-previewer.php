<div id="blog_template-selection">
	<h3><?php _e('Select a template', 'blog_templates') ?></h3>

	<?php
		if ( $settings['show-categories-selection'] ) {
			$toolbar = new blog_templates_theme_selection_toolbar( $settings['registration-templates-appearance'] );
		    $toolbar->display();
		}
    ?>
    
	<div class="blog_template-option">
		
	<?php 
	foreach ($templates as $tkey => $template) { 
		nbt_render_theme_selection_item( 'previewer', $tkey, $template, $settings );
	}
	?>
	<div style="clear:both;"></div>
	</div>
</div>
