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
				<a href="#" id="item-0" class="toolbar-item" data-cat-id="0"><?php _e( 'ALL', 'blog_templates' ); ?></a>
				<?php foreach ( $this->categories as $category ): ?>
					<a href="#" id="item-<?php echo $category['ID']; ?>" class="toolbar-item" style="opacity: 0.62;" data-cat-id="<?php echo $category['ID']; ?>"><?php echo $category['name']; ?></a>
				<?php endforeach; ?>
				<div style="clear:both"></div>
			</div>
		<?php
	}

	private function render_css() {
		$options = nbt_get_settings();
		?>
		<style>
			#nbt-toolbar {
			    width: 100%;
			    box-sizing: border-box;
			    margin-bottom: 25px;
			    border-top: 1px solid <?php echo $options['toolbar-border-color']; ?>;
			    text-align: center;
			    padding-top:25px;
			}

			#nbt-toolbar a {
				text-transform: uppercase;
				display: inline-block;
				background: <?php echo $options['toolbar-color']; ?>;
				color: <?php echo $options['toolbar-text-color']; ?>;
				-moz-border-radius: 3px;
				-webkit-border-radius: 3px;
				border-radius: 3px;
				margin: 0 5px 5px 0;
				padding: 0 0.5em;
				text-decoration: none;
				-moz-transition: all 0.2s ease-in-out;
				-o-transition: all 0.2s ease-in-out;
				-webkit-transition: all 0.2s ease-in-out;
				transition: all 0.2s ease-in-out;
				font-size:1.1em;
				line-height:1.5;
			}

			#nbt-toolbar a:hover {
				opacity:1 !important;
			}

			#toolbar-loader {
				text-align:center;
				padding: 50px 0;
			}
		</style>
		<?php
	}

	private function set_categories() {
		$model = nbt_get_model();

		$this->categories = $model->get_templates_categories();
	}
}


function nbt_filter_categories() {
	$cat_id = absint( $_POST['category_id'] );
	$type = $_POST['type'];

	$model = nbt_get_model();
	$templates = $model->get_templates_by_category( $cat_id );

	$options = nbt_get_settings();
	foreach( $templates as $tkey => $template ) {
		nbt_render_theme_selection_item( $type, $template['ID'], $template, $options );
	}
	
	echo '<div style="clear:both"></div>';

	die();
}
add_action( 'wp_ajax_nbt_filter_categories', 'nbt_filter_categories' );
add_action( 'wp_ajax_nopriv_nbt_filter_categories', 'nbt_filter_categories' );