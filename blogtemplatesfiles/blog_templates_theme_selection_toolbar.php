<?php

class blog_templates_theme_selection_toolbar {

	public $categories;
	public $default_category_id;

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

		$tabs = array();
		$tabs[0] = __( 'ALL', 'blog_templates' );

		foreach ( $this->categories as $category ) {
			$tabs[ $category['ID'] ] = $category['name'];
		}

		$tabs = apply_filters( 'nbt_selection_toolbar_tabs', $tabs );
		$default_tab = apply_filters( 'nbt_selection_toolbar_default_tab', key( $tabs ) );

		// Default tab should be a category ID
		$this->default_category_id = $default_tab;

		?>
			<div id="nbt-toolbar" data-toolbar-type="<?php echo $this->type; ?>">
				<?php foreach ( $tabs as $tab_key => $tab_name ): ?>
					<a href="#" id="item-<?php echo $tab_key; ?>" class="toolbar-item <?php echo $default_tab == $tab_key ? 'toolbar-item-selected' : ''; ?>" data-cat-id="<?php echo $tab_key; ?>"><?php echo $tab_name; ?></a>
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

			#nbt-toolbar .toolbar-item-selected {
				opacity:0.62;
			}

			#toolbar-loader {
				text-align:center;
				padding: 50px 0;
				max-width:100%;
				width:50px;
				margin:0 auto;
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
	$checked = isset( $options['default'] ) ? $options['default'] : '';

	if ( '' === $type ) {
		echo '<select name="blog_template">';
		if ( empty( $checked ) ) {
   			echo '<option value="none">' . __( 'None', 'blog_templates' ) . '</option>';
   		}
	}


	foreach( $templates as $tkey => $template ) {
		nbt_render_theme_selection_item( $type, $template['ID'], $template, $options );
	}

	if ( '' === $type )
		echo '</select>';
	else
		echo '<div style="clear:both"></div>';

	die();
}
add_action( 'wp_ajax_nbt_filter_categories', 'nbt_filter_categories' );
add_action( 'wp_ajax_nopriv_nbt_filter_categories', 'nbt_filter_categories' );