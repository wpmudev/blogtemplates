<?php

function nbt_theme_selection_toolbar( $templates ) {
    require_once( NBT_PLUGIN_DIR . 'blogtemplatesfiles/premium/front/theme-selection-toolbar.php' );
	$settings = nbt_get_settings();
	$toolbar = new Blog_Templates_Signup_Toolbar( $settings['registration-templates-appearance'] );
	$toolbar->display();
	$category_id = $toolbar->default_category_id; 

	if ( $category_id !== 0 ) {
		$model = nbt_get_model();
		$templates = $model->get_templates_by_category( $category_id );
	}

	return $templates;
}

function nbt_get_template_selection_types() {
	return apply_filters( 'nbt_get_template_selection_types', array(
        0 => __('As simple selection box', 'blog_templates'),
        'description' => __('As radio-box selection with descriptions', 'blog_templates'),
        'screenshot' => __('As theme screenshot selection', 'blog_templates'),
        'screenshot_plus' => __('As theme screenshot selection with titles and description', 'blog_templates'),
        'previewer' => __('As a theme previewer', 'blog_templates'),
        'page_showcase' => __('As a showcase inside a page', 'blog_templates')
    ) );
}


function nbt_render_theme_selection_scripts( $options ) {
	$type = $options['registration-templates-appearance'];
	$selected_color = $options['selected-background-color'];
	$unselected_color = $options['unselected-background-color'];
	$overlay_color = $options['overlay_color'];
	$screenshots_width = $options['screenshots_width'];

	?>
		<style>
			.theme-previewer-wrap,
			.theme-page-showcase-wrap,
			.theme-screenshot-wrap {
				width:45%;
				float:left;
				margin-right: 13px;
				margin-bottom:25px;
				box-sizing:border-box;
				position:relative;
				background:<?php echo $unselected_color; ?>;
				padding:3px;
				max-width:600px;
			}
			.blog_template-default_item {
				background:<?php echo $selected_color; ?> !important;
			}
			.theme-previewer-wrap:nth-child(even),
			.theme-screenshot-wrap:nth-child(even),
			.theme-screenshot-plus-wrap:nth-child(even) {
				margin-right:0px;
			}
			.blog_template-item_selector img {
				max-width:100%;
				max-height:100%;
				display: block;
				border-radius:0px;
			}
			.nbt-desc-pointer {
				display:none;
				background:#FFFFFF;
				color:#333;
				padding: 20px;
				z-index: 100;
				border: 3px solid #DDD;
				margin-top: 10px;
				position:absolute;
				font-size:12px;
				min-width:200px;
			}
			.nbt-desc-pointer:after {
				border-bottom:10px solid #DDD;
    			border-left:10px solid transparent;
			    border-right:10px solid transparent;
			    width:0;
			    height:0;
			    
			    content:"";
			    display:block;
			    position:absolute;
			    bottom:100%;
			    left:1em;
			}
		</style>
		<script type="text/javascript">
			jQuery(document).ready(function($) {
				var position_top = 0;
				var position_left = 0;
				var hovering_out = true;
				$('.nbt-desc-pointer').appendTo($('body'));
				$(document).mousemove(function(e) {
					position_top = e.pageY;
					position_left = e.pageX;
				});
				$('.template-signup-item').hover(
					function(e) {
						hovering_out = false;
						container = $(this);
						var tkey = container.data('tkey');
						pointer = $( '#nbt-desc-pointer-' + tkey );
						setTimeout(function() {
							if ( hovering_out )
								return;
							var margin_top = position_top;
							var margin_left = position_left;
							pointer.css({
								left: margin_left - 15 + 'px',
								top: margin_top + 25,
								width: container.outerWidth() / 2 + 'px'
							}).stop(true,true).fadeIn()
				       }, 200);
					},
					function(e) {
						hovering_out = true;
						var tkey = container.data('tkey');
						pointer = $('#nbt-desc-pointer-'+tkey).stop().fadeOut();
					}
				);
			});
		</script>
	<?php
	if ( 'previewer' == $type ) {
		?>
			<style>				
				.theme-previewer-wrap:hover img {
					opacity:0.5;
				}
				.theme-previewer-wrap:hover .theme-previewer-overlay {
					display:block;
				}			
				.theme-previewer-overlay {
					display:none;
					position:absolute;
					top:50%;
					opacity:1;
					width:100%;
					margin-top:-25%;
					box-sizing:border-box;
					white-space: nowrap;
					text-align: center;
				}
				.view-demo-button {
					font-size:90%;
				}
				.select-theme-button {
					font-size:100%;
					font-weight:bold;
				}
				.template-name {
					display:inline-block;
					font-size:100%;
					color:white;
					text-shadow:1px 1px 1px black;
				}
			</style>
			<script type="text/javascript">
			jQuery(document).ready(function($) {
				$(document).on( 'click', '.view-demo-button, .select-theme-button', function(e) {
					e.preventDefault();
					var theme_key = $(this).data('theme-key');
					var wrap = $('#theme-previewer-wrap-' + theme_key );
					$('.theme-previewer-wrap').removeClass('blog_template-default_item');
					wrap.addClass('blog_template-default_item');

					$('input[name=blog_template]').attr('checked',false);
					$('#blog-template-radio-' + theme_key).attr('checked',true);
				});
				$(document).on('click', '.view-demo-button', function(e) {
					e.preventDefault();
					window.open($(this).data('blog-url'));
				});
			});
			</script>
		<?php
	}
	elseif ( 'page_showcase' == $type ) {

		?>
			<style>
				.theme-page-showcase-wrap {
					background:<?php echo $overlay_color; ?>;
					width:<?php echo $screenshots_width; ?>px;
					padding:2px;
				}
				.theme-page-showcase-wrap:hover img {
					opacity:0.5;
				}
				.theme-page-showcase-wrap:hover .theme-page-showcase-overlay {
					display:block;
				}			
				.theme-page-showcase-overlay {
					display:none;
					position:absolute;
					top:50%;
					opacity:1;
					width:100%;
					margin-top:-25%;
					box-sizing:border-box;
					white-space: nowrap;
					text-align: center;
				}
				.view-demo-button {
					font-size:90%;
				}
				.select-theme-button {
					font-size:100%;
					font-weight:bold;
				}
				.template-name {
					display:inline-block;
					font-size:100%;
					color:white;
					text-shadow:1px 1px 1px black;
				}
			</style>
			<script type="text/javascript">
			jQuery(document).ready(function($) {
				$(document).on( 'click', '.select-theme-button', function(e) {
					e.preventDefault();
					var signup_url = $(this).data('signup-url')
					location.href = signup_url;
				});
				$(document).on('click', '.view-demo-button', function(e) {
					e.preventDefault();
					window.open($(this).data('blog-url'));
				});
			});
			</script>
		<?php
	}
	elseif ( 'screenshot' === $type ) {
		?>
			<script type="text/javascript">
				jQuery(document).ready(function($) {
					$(document).on( 'click', '.blog_template-item_selector', function(e) {
						e.preventDefault();
						var theme_key = $(this).data('theme-key');
						var wrap = $('#theme-screenshot-wrap-' + theme_key );
						$('.theme-screenshot-wrap').removeClass('blog_template-default_item');
						wrap.addClass('blog_template-default_item');

						$('input[name=blog_template]').attr('checked',false);
						$('#blog-template-radio-' + theme_key).attr('checked',true);
					});
				});
			</script>
		<?php
	}
	elseif ( 'screenshot_plus' === $type ) {
		?>
			<style>
				.theme-screenshot-plus-wrap {
					width:45%;
					float:left;
					margin-right:10%;
					margin-bottom:25px;
					box-sizing:border-box;
					position:relative;
					
				}
				.theme-screenshot-plus-image-wrap {
					background:<?php echo $unselected_color; ?>;
					padding:3px;
				}
			</style>
			<script type="text/javascript">
				jQuery(document).ready(function($) {
					$(document).on( 'click', '.blog_template-item_selector', function(e) {
						e.preventDefault();
						var theme_key = $(this).data('theme-key');
						var wrap = $('#theme-screenshot-plus-image-wrap-' + theme_key );
						$('.theme-screenshot-plus-image-wrap').removeClass('blog_template-default_item');
						wrap.addClass('blog_template-default_item');

						$('input[name=blog_template]').attr('checked',false);
						$('#blog-template-radio-' + theme_key).attr('checked',true);
					});
				});
			</script>
		<?php
	}
	elseif ( 'description' === $type ) {
		?>
			<style>
				.theme-radio-wrap {
					margin-bottom: 25px;
					border: 1px solid #DEDEDE;
					border-radius: 5px;
					background: #EFEFEF;
					padding: 15px;
					
				}
				.blog_template-description {
					margin-left:30px;
				}
			</style>
		<?php
	}
}