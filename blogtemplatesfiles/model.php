<?php

if ( ! class_exists( 'blog_templates_model' ) ) {
	class blog_templates_model {
			/**
			 * Singleton
			 */
			public static $instance;

			/**
			 * Tables
			 */
			public $templates_table;
			public $categories_table;
			public $categories_relationships_table;

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
			public function __construct() {}

			/**
			 * Creates all tables
			 * 
			 * @since 1.8
			 */
			public function create_tables() {}

			public function delete_tables() {}

			

			public function add_template( $blog_id, $name, $description, $settings ) {
				global $wpdb, $current_site;

				$template = array(
					'blog_id' =>  $blog_id,
					'name' => $name,
					'description' => $description,
					'options' => $settings,
					'is_default' => true
				);

				$added = update_site_option( 'nbt_template', $template );

				if ( $added )
					return 1;
				else
					return false;
			}

			/**
			 * Check if a blog is a template or not
			 * 
			 * @param Integer $blog_id Blog ID to check
			 * @return Boolean
			 */
			public function is_template( $blog_id ) {
				global $wpdb;

				$template = get_site_option( 'nbt_template' );

				if ( ! empty( $template ) )
					return true;

				return false;
			}

			public function update_template( $id, $args ) {
				global $wpdb;

				$template = get_site_option( 'nbt_template' );

				if ( ! $template )
					return false;

				extract( $args );

				$template['to_copy'] = $to_copy;
				$template['additional_tables'] = $additional_tables;
				$template['copy_status'] = $copy_status;
				$template['block_posts_pages'] = $block_posts_pages;
				$template['post_category'] = $post_category;
				$template['screenshot'] = ! empty( $screenshot ) ? $screenshot : false;
				$template['pages_ids'] = $pages_ids;
				$template['update_dates'] = $update_dates;

				$updated = update_site_option( 'nbt_template', $template );

			}

			public function delete_template( $id ) {
				global $wpdb, $current_site;

				delete_site_option( 'nbt_template' );
			}

			public function get_template( $id ) {
				$template = get_site_option( 'nbt_template' );
				$template['ID'] = 1;
				return $template;
				
			}


			public function get_templates() {

				$template = get_site_option( 'nbt_template' );
				
				if ( ! empty( $template ) ) {
					$template['ID'] = 1;
					return array( 1 => $template );
				}

				return array();

			}




	}

}
