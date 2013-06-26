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
}


