<?php

class blog_templates_model {
		/**
		 * Singleton
		 */
		public static $instance;

		/**
		 * Tables
		 */
		private $templates_table;
		private $categories_table;
		private $categories_relationships_table;

		/**
		 * Database charset and collate
		 */
		private $db_charset_collate = '';

		/**
		 * Singleton Pattern
		 * 
		 * Gets the instance of the class
		 * 
		 * @since 1.8
		 */
		public static function get_instance() {
			if ( empty( self::$instance ) )
				self::$instance = new blog_templates_model();
			return self::$instance;
		}

		/**
		 * Constructor
		 * 
		 * @since 1.8
		 */
		public function __construct() {
			global $wpdb;

			$this->templates_table 					= $wpdb->base_prefix . 'nbt_templates';
			$this->categories_table 				= $wpdb->base_prefix . 'nbt_templates_categories';
			$this->categories_relationships_table 	= $wpdb->base_prefix . 'nbt_categories_relationships_table';

			 // Get the correct character collate
			if ( ! empty($wpdb->charset) )
				$this->db_charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
			if ( ! empty($wpdb->collate) )
				$this->db_charset_collate .= " COLLATE $wpdb->collate";
		}

		/**
		 * Creates all tables
		 * 
		 * @since 1.8
		 */
		public function create_tables() {

			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

			$this->create_templates_table();
			$this->create_templates_categories_table();
			$this->create_templates_categories_relationships();
		}

		private function create_templates_table() {
			global $wpdb;

			$sql = "CREATE TABLE $this->templates_table (
				ID bigint(20) NOT NULL AUTO_INCREMENT,
				blog_id bigint(20) NOT NULL,
				name varchar(255) NOT NULL,
				description mediumtext,
				is_default int(1) DEFAULT 0,
				options longtext NOT NULL,
				PRIMARY KEY  (ID)
			      ) $this->db_charset_collate;";

			dbDelta($sql);
		}

		private function create_templates_categories_table() {
			global $wpdb;

			$sql = "CREATE TABLE $this->categories_table (
				ID bigint(20) NOT NULL AUTO_INCREMENT,
				name varchar(255) NOT NULL,
				description mediumtext,
				is_default int(1) NOT NULL DEFAULT '0',
				PRIMARY KEY  (ID)
			      ) $this->db_charset_collate;";

			dbDelta($sql);
		}

		private function create_templates_categories_relationships() {
			global $wpdb;

			$sql = "CREATE TABLE $this->categories_relationships_table (
				cat_id bigint(20) unsigned NOT NULL,
				template_id bigint(20) unsigned NOT NULL,
				PRIMARY KEY  (cat_id,template_id),
				KEY cat_id (cat_id)
			      ) $this->db_charset_collate;";

			dbDelta($sql);
		}

		public function add_template( $blog_id, $name, $description, $settings ) {
			global $wpdb;

			$wpdb->insert( 
				$this->templates_table,
				array(
					'blog_id' =>  $blog_id,
					'name' => $name,
					'description' => $description,
					'options' => maybe_serialize( $settings )
				),
				array(
					'%d',
					'%s',
					'%s',
					'%s'
				)
			);

			return $wpdb->insert_id;
		}

		public function update_template( $id, $args ) {
			global $wpdb;

			extract( $args );

			$options = maybe_serialize( array(
				'to_copy' => $to_copy,
				'additional_tables' => $additional_tables,
				'copy_status' => $copy_status,
				'block_posts_pages' => $block_posts_pages,
				'post_category' => $post_category,
				'screenshot' => ! empty( $screenshot ) ? $screenshot : false
			) );

			$wpdb->update( 
				$this->templates_table,
				array(
					'name' => $name,
					'description' => $description,
					'options' => $options
				),
				array( 'ID' => $id ),
				array( '%s', '%s', '%s' ),
				array( '%d' )
			);
		}

		public function delete_template( $id ) {
			global $wpdb;

			$wpdb->query( $wpdb->prepare( "DELETE FROM $this->templates_table WHERE ID = %d", $id ) );
			$wpdb->query( $wpdb->prepare( "DELETE FROM $this->categories_relationships_table WHERE template_id = %d", $id ) );
		}

		public function get_template( $id ) {
			global $wpdb;

			$template = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $this->templates_table WHERE ID = %d", $id ), ARRAY_A );

			$template = array_merge( maybe_unserialize( $template['options'] ), $template );

			return $template;
		}

		public function get_templates() {
			global $wpdb;

			$results = $wpdb->get_results( "SELECT * FROM $this->templates_table", ARRAY_A );

			if ( ! empty( $results ) ) {
				$final_results = array();
				foreach ( $results as $template ) {
					$final_results[$template['ID']] = $template;
					$final_results[$template['ID']] = array_merge( maybe_unserialize( $template['options'] ), $final_results[$template['ID']] );
				}
				return $final_results;
			}
			else {
				return array();
			}

		}

		public function set_default_template( $id ) {
			global $wpdb;

			$this->remove_default_template();

			return $wpdb->update(
				$this->templates_table,
				array( 'is_default' => 1 ),
				array( 'ID' => $id ),
				array( '%d' ),
				array( '%d' )
			);
		}

		public function remove_default_template() {
			global $wpdb;

			$wpdb->query( "UPDATE $this->templates_table SET is_default = 0" );
		}

		public function add_default_template_category() {
			global $wpdb;

			$this->add_template_category( __( 'Default category', 'blog_templates' ), '', true );
		}

		public function get_categories_count() {
			global $wpdb;

			return $wpdb->get_var( "SELECT COUNT(ID) FROM $this->categories_table" );
		}

		public function get_template_category( $cat_id ) {
			global $wpdb;

			$results = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $this->categories_table WHERE ID = %d", $cat_id ), ARRAY_A );

			if ( empty( $results ) )
				return false;
			else
				return $results;
		}

		public function get_templates_categories() {
			global $wpdb;

			$results = $wpdb->get_results( "SELECT * FROM $this->categories_table", ARRAY_A );

			return $results;
		}

		public function delete_template_category( $cat_id ) {
			global $wpdb;

			$wpdb->query( $wpdb->prepare( "DELETE FROM $this->categories_table WHERE ID = %d", $cat_id ) );

			$wpdb->query( $wpdb->prepare( "DELETE FROM $this->categories_relationships_table WHERE cat_id = %d", $cat_id ) );
		}

		public function add_template_category( $name, $description, $is_default = false ) {
			global $wpdb;

			$wpdb->insert( 
				$this->categories_table, 
				array( 
					'name' => $name, 
					'description' => $description,
					'is_default' => ( $is_default ) ? 1 : 0
				), 
				array( 
					'%s', 
					'%s',
					'%d'
				) 
			);
		}

		public function update_template_category( $id, $name, $description ) {
			global $wpdb;

			$wpdb->update( 
				$this->categories_table, 
				array( 
					'name' => $name, 
					'description' => $description 
				), 
				array( 'ID' => $id ),
				array( 
					'%s', 
					'%s' 
				),
				array( '%d' )
			);
		}

		public function is_default_category( $id ) {
			global $wpdb;

			return $wpdb->get_var( $wpdb->prepare( "SELECT is_default FROM $this->categories_table WHERE ID = %d", $id ) );
		}

		public function get_default_category_id() {
			global $wpdb;

			return $wpdb->get_var( "SELECT ID FROM $this->categories_table WHERE is_default = '1'" );
		}

		public function get_template_categories( $id ) {
			global $wpdb;

			$results =  $wpdb->get_results(
				$wpdb->prepare(
					"SELECT rel.cat_id ID, cat.name, cat.description, cat.is_default 
					FROM  `wp_nbt_templates_categories` cat
					INNER JOIN wp_nbt_categories_relationships_table rel ON rel.cat_id = cat.ID
					WHERE rel.template_id = %d",
					$id
				),
				ARRAY_A
			);

			if ( empty( $results ) ) {
				$def_cat_id = $this->get_default_category_id();
                $category = $this->get_template_category( $def_cat_id );
                $results = array( $category );
			}
			
			return $results;
		}

		public function update_template_categories( $tid, $cats ) {
			global $wpdb;

			$wpdb->query( $wpdb->prepare( "DELETE FROM $this->categories_relationships_table WHERE template_id = %d", $tid ) );

			foreach ( $cats as $cat ) {
				$query = $wpdb->prepare(
					"INSERT INTO $this->categories_relationships_table (cat_id,template_id) VALUES (%d,%d)",
					$cat,
					$tid
				);
				$wpdb->query( $query );
			}
		}

		public function exist_relation( $tid, $cat_id ) {
			global $wpdb;

			$result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $this->categories_relationships_table WHERE template_id = %d AND cat_id = %d", $tid, $cat_id ) );

			if ( ! empty( $result ) )
				return true;

			return false;
		}

		public function get_templates_by_category( $cat_id ) {

			global $wpdb;

			if ( ! $cat_id )
				return $this->get_templates();

			$query = $wpdb->prepare(
				"SELECT t.* FROM $this->templates_table t
				INNER JOIN $this->categories_relationships_table r
				ON t.ID = r.template_id
				WHERE r.cat_id = %d",
				$cat_id
			);

			$results = $wpdb->get_results( $query, ARRAY_A );

			if ( ! empty( $results ) ) {
				$new_results = array();
				foreach ( $results as $template ) {
					$tmp_template = $template;
					$tmp_template = array_merge( maybe_unserialize( $template['options'] ), $tmp_template );
					unset( $tmp_template['options'] );
					$new_results[] = $tmp_template;
				}
				$results = $new_results;
			}

			return $results;
		}
}


