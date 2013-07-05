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
				PRIMARY KEY  (ID)
			      ) $this->db_charset_collate;";

			dbDelta($sql);
		}

		private function create_templates_categories_relationships() {
			global $wpdb;

			$sql = "CREATE TABLE $this->categories_relationships_table (
				ID bigint(20) unsigned NOT NULL auto_increment,
				cat_id bigint(20) unsigned NOT NULL,
				template_id bigint(20) unsigned NOT NULL,
				PRIMARY KEY  (ID)
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
				'post_category' => $post_category
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
		}

		public function get_template( $id ) {
			global $wpdb;

			$template = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $this->templates_table WHERE ID = %d", $id ) );

			$template->options = maybe_unserialize( $template->options );

			return $template;
		}

		public function get_templates() {
			global $wpdb;

			$results = $wpdb->get_results( "SELECT * FROM $this->templates_table" );

			if ( ! empty( $results ) ) {
				$final_results = array();
				foreach ( $results as $template ) {
					$final_results[$template->ID] = $template;
					$final_results[$template->ID]->options = maybe_unserialize( $template->options );
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

			$wpdb->update(
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
}


