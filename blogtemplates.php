<?php
/*
Plugin Name: New Blog Templates
Plugin URI: http://premium.wpmudev.org/project/new-blog-template
Description: Allows the site admin to create new blogs based on templates, to speed up the blog creation process
Author: Jason DeVelvis, Ulrich Sossou (Incsub), Ignacio Cruz (Incsub)
Author URI: http://premium.wpmudev.org/
Version: 1.9
Network: true
Text Domain: blog_templates
WDP ID: 130
*/

/*  Copyright 2010-2011 Incsub (http://incsub.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

define( 'NBT_PLUGIN_VERSION', '1.9' );
if ( !is_multisite() )
	exit( __( 'The New Blog Template plugin is only compatible with WordPress Multisite.', 'blog_templates' ) );

define( 'NBT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'NBT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
require_once( NBT_PLUGIN_DIR . '/blogtemplatesfiles/filters.php' );
require_once( NBT_PLUGIN_DIR . '/blogtemplatesfiles/model.php' );
require_once( NBT_PLUGIN_DIR . '/blogtemplatesfiles/upgrade.php' );
require_once( NBT_PLUGIN_DIR . '/blogtemplatesfiles/blog_templates_theme_selection_toolbar.php' );
require_once( NBT_PLUGIN_DIR . '/blogtemplatesfiles/admin/main_menu.php' );
require_once( NBT_PLUGIN_DIR . '/blogtemplatesfiles/admin/categories_menu.php' );
require_once( NBT_PLUGIN_DIR . '/blogtemplatesfiles/admin/settings_menu.php' );
require_once( NBT_PLUGIN_DIR . '/blogtemplatesfiles/blog_templates.php' );
require_once( NBT_PLUGIN_DIR . '/blogtemplatesfiles/blog_templates_lock_posts.php' );


if ( is_network_admin() ) {
	require_once( NBT_PLUGIN_DIR . '/blogtemplatesfiles/tables/templates_table.php' );
	require_once( NBT_PLUGIN_DIR . '/blogtemplatesfiles/tables/categories_table.php' );
}


function nbt_get_default_screenshot_url( $blog_id ) {
	switch_to_blog($blog_id);
	$img = untrailingslashit(dirname(get_stylesheet_uri())) . '/screenshot.png';
	restore_current_blog();	
	return $img;
}

function nbt_render_theme_selection_item( $type, $tkey, $template, $options = array() ) {
	if ( 'previewer' == $type ) {
		$img = ( ! empty( $template['screenshot'] ) ) ? $template['screenshot'] : nbt_get_default_screenshot_url( $template['blog_id'] );
		$tplid = $template['name'];
		$default = @$options['default'] == $tkey ? "blog_template-default_item" : "";
		$blog_url = get_site_url( $template['blog_id'] );
		?>
			<div class="theme-previewer-wrap <?php echo $default; ?>" id="theme-previewer-wrap-<?php echo $tkey;?>">
				<a href="#<?php echo $tplid; ?>" class="blog_template-item_selector">
					<img src="<?php echo $img;?>" />
					<input type="radio" name="blog_template" id="blog-template-radio-<?php echo $tkey;?>" <?php checked( ! empty( $default ) ); ?> value="<?php echo $tkey;?>" style="display: none" />
					<div class="theme-previewer-overlay">

						<span class="template-name"><?php echo $tplid; ?></span> <button class="view-demo-button" data-blog-url="<?php echo $blog_url;?>"><?php _e( 'View demo', 'blog_templates' ); ?></button><br/><br/>
						<button class="select-theme-button" data-theme-key="<?php echo $tkey;?>"><?php echo $options['previewer_button_text']; ?></button>
					</div>
				</a>
			</div>
		<?php
	}
	elseif ( 'screenshot' === $type ) {
		$img = ( ! empty( $template['screenshot'] ) ) ? $template['screenshot'] : nbt_get_default_screenshot_url( $template['blog_id'] );
		$tplid = preg_replace('/[^a-z0-9]/i', '', strtolower($template['name'])) . "-{$tkey}";
		$default = @$options['default'] == $tkey ? "blog_template-default_item" : "";
		?>
		<div class="blog_template-item">
			<a href="#<?php echo $tplid; ?>" class="blog_template-item_selector <?php echo $default; ?>">
				<img src="<?php echo $img;?>" />
				<input type="radio" name="blog_template" value="<?php echo $tkey;?>" style="display: none" />
			</a>
		</div>
		<?php 
	}
	elseif ( 'screenshot_plus' === $type ) {
		$img = ( ! empty( $template['screenshot'] ) ) ? $template['screenshot'] : nbt_get_default_screenshot_url( $template['blog_id'] );
		$tplid = preg_replace('/[^a-z0-9]/i', '', strtolower($template['name'])) . "-{$tkey}";
		$default = @$options['default'] == $tkey ? "blog_template-default_item" : "";
		?>
		<div class="blog_template-item">
			<h4><?php echo strip_tags($template['name']);?></h4><br />
			<a href="#<?php echo $tplid; ?>" class="blog_template-item_selector <?php echo $default; ?>">
				<img src="<?php echo $img;?>" />
				<input type="radio" name="blog_template" value="<?php echo $tkey;?>" style="display: none" />
			</a>
			<p class="blog_template-description">
				<?php echo nl2br($template['description']); ?>
			</p>
		</div>
		<?php 
	}
}

function nbt_render_theme_selection_scripts( $type ) {
	if ( 'previewer' == $type ) {
		?>
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
				.theme-previewer-wrap:nth-child(even) {
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
				$(document).on( 'click', '.view-demo-button, .select-theme-button', function(e) {
					e.preventDefault();
					var theme_key = $(this).data('theme-key');
					var wrap = $('#theme-previewer-wrap-' + theme_key );
					$('.theme-previewer-wrap').removeClass('blog_template-default_item');
					wrap.addClass('blog_template-default_item');

					$('input[name=blog_template]').attr('checked',false);
					$('#blog-template-radio-'+theme_key).attr('checked',true);
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
			<style type="text/css">
				.blog_template-option {
					overflow: hidden;
				}
				.blog_template-item_selector {
					display: block;
					float: left;
					padding: 12px;
					border: 3px solid transparent;
					background: transparent;
				}
				.blog_template-item img {
					max-width:100%;
				}
				.blog_template-item {
					width:45%;
					margin-right:10%;
					margin-bottom:25px;
					box-sizing:border-box;
					float: left;
					padding: 12px;
					border: 2px solid transparent;
					background: transparent;
				}
				.blog_template-item:nth-child(odd) {
					margin-right:0px;
				}
				.blog_template-selected {
					border: 3px solid #ccc;
					background: #eee;
				}
				#blog_template-selection {
					clear:both;
				}
				</style>
				<script type="text/javascript">
					(function ($) {
					$(function () {

					$(document).on( 'click', ".blog_template-item_selector", function () {
						$(".blog_template-item_selector").removeClass("blog_template-selected")
						$(".blog_template-item_selector :radio").attr("checked", false);
						$(this)
							.addClass("blog_template-selected")
							.find(":radio").attr("checked", true)
						;
						return false;	
					});
					if ($(".blog_template-item_selector.blog_template-default_item").length) $(".blog_template-item_selector.blog_template-default_item").trigger("click");
					});
					})(jQuery);
				</script>
		<?php
	}
	elseif ( 'screenshot_plus' === $type ) {
		?>
		<style type="text/css">
			.blog_template-option {
				overflow: hidden;
			}
			.blog_template-item img {
				max-width:100%;
			}
			.blog_template-item {
				width:45%;
				margin-right:10%;
				margin-bottom:25px;
				box-sizing:border-box;
				float: left;
				padding: 12px;
				border: 2px solid transparent;
				background: transparent;
			}
			.blog_template-item:nth-child(odd) {
				margin-right:0px;
			}
			.blog_template-selected {
				border: 2px solid #ccc;
				background: #eee;
			}
			#blog_template-selection {
				clear:both;
			}
			</style>
			<script type="text/javascript">
				(function ($) {
				$(function () {

				$(document).on('click', ".blog_template-item_selector", function () {
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
		<?php
	}
}

/**
 * Show notification if WPMUDEV Update Notifications plugin is not installed
 **/
if ( !function_exists( 'wdp_un_check' ) ) {
	add_action( 'admin_notices', 'wdp_un_check', 5 );
	add_action( 'network_admin_notices', 'wdp_un_check', 5 );

	function wdp_un_check() {
		if ( !class_exists( 'WPMUDEV_Update_Notifications' ) && current_user_can( 'edit_users' ) )
			echo '<div class="error fade"><p>' . __('Please install the latest version of <a href="http://premium.wpmudev.org/project/update-notifications/" title="Download Now &raquo;">our free Update Notifications plugin</a> which helps you stay up-to-date with the most stable, secure versions of WPMU DEV themes and plugins. <a href="http://premium.wpmudev.org/wpmu-dev/update-notifications-plugin-information/">More information &raquo;</a>', 'wds') . '</a></p></div>';
	}
}





