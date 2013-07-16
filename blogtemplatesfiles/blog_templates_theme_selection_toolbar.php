<?php

class blog_templates_theme_selection_toolbar {

	public $categories;

	public function __construct( $type ) {
		$this->type = $type;
		$this->set_categories();

		add_action( 'wp_footer', array( &$this, 'add_javascript' ) );
		add_action( 'wp_footer', array( &$this, 'add_styles' ) );

	}

	public function add_javascript() {
		wp_enqueue_script( 'nbt-toolbar-scripts', NBT_PLUGIN_URL . 'blogtemplatesfiles/assets/js/toolbar.js', array( 'jquery' ) );
		$params = array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'imagesurl' => NBT_PLUGIN_URL . 'blogtemplatesfiles/assets/images/'
		);
		wp_localize_script( 'nbt-toolbar-scripts', 'export_to_text_js', $params );
	}

	public function add_styles() {
	}

	public function display() {
		$this->render_css();
		?>
			<div id="nbt-toolbar" data-toolbar-type="<?php echo $this->type; ?>">
				<div class="nbt-toolbar-title">
					<h3><?php _e( 'Filter by category', 'blog_templates' ); ?></h3>
				</div>
				<ul>
					<li class="toolbar-item"><a href="#" data-cat-id="0"><?php _e( 'Show all', 'blog_templates' ); ?></a>
					<?php foreach ( $this->categories as $category ): ?>
						<li class="toolbar-item">
							<a href="#" data-cat-id="<?php echo $category['ID']; ?>"><?php echo $category['name']; ?></a>
						</li>
					<?php endforeach; ?>
				</ul>
				<div style="clear:both"></div>
			</div>
		<?php
	}

	private function render_css() {
		$options = get_site_option( 'blog_templates_options' );
		?>
		<style>
			#nbt-toolbar {
			    width: 100%;

			    box-sizing: border-box;
			    border: 1px solid <?php echo $options['toolbar-border-color']; ?>;
			    margin-bottom: 25px;
			    background: <?php echo $options['toolbar-color']; ?>;
			}

			#nbt-toolbar ul {
			    border-top: 1px solid <?php echo $options['toolbar-border-color']; ?>;
			}


			#nbt-toolbar li.toolbar-item {
			    float: left;

			    list-style: none;
			    padding: 10px 25px;
			    border-right: 1px solid <?php echo $options['toolbar-border-color']; ?>;
			    margin-left: 0;
			}

			#nbt-toolbar h3,
			#nbt-toolbar li.toolbar-item a {
			    text-decoration: none;
			    color: <?php echo $options['toolbar-text-color']; ?>;
			}

			#nbt-toolbar li.toolbar-item:last-child {
				border-right:none;
			}
			#nbt-toolbar li.toolbar-item:first-child {
				border-left:none;
			}

			#nbt-toolbar .nbt-toolbar-title {
			    padding: 10px 25px;
			}

			#toolbar-loader {
				text-align:center;
				padding: 50px 0;
			}
		</style>
		<?php
	}

	private function set_categories() {
		$model = blog_templates_model::get_instance();

		$this->categories = $model->get_templates_categories();
	}
}


function nbt_filter_categories() {
	$cat_id = absint( $_POST['category_id'] );
	$type = $_POST['type'];

	$model = blog_templates_model::get_instance();
	$templates = $model->get_templates_by_category( $cat_id );

	$options = get_site_option( 'blog_templates_options' );
	foreach( $templates as $tkey => $template ) {
		nbt_render_theme_selection_item( $type, $tkey, $template, $options );
	}
	

	die();
}
add_action( 'wp_ajax_nbt_filter_categories', 'nbt_filter_categories' );
add_action( 'wp_ajax_nopriv_nbt_filter_categories', 'nbt_filter_categories' );