<?php

include_once( 'class.copier-post-types.php' );
class NBT_Template_Copier_Menus extends NBT_Template_Copier {

	public function __construct( $source_blog_id, $template, $args = array(), $user_id = 0 ) {
		parent::__construct( $source_blog_id, $template, $user_id );
	}

	public function get_default_args() {
		return array();
	}

	public function copy() {
		global $wpdb;

        do_action( 'blog_templates-copying_menu', $this->source_blog_id, get_current_blog_id() );

        switch_to_blog( $this->source_blog_id );
        $templated_posts_table = $wpdb->posts;
        $templated_postmeta_table = $wpdb->postmeta;
        $templated_terms_table = $wpdb->terms;
        $templated_term_taxonomy_table = $wpdb->term_taxonomy;
        $templated_term_relationships_table = $wpdb->term_relationships;

        $menu_locations = get_nav_menu_locations();
        restore_current_blog();

        $new_posts_table = $wpdb->posts;
        $new_postmeta_table = $wpdb->postmeta;
        $new_terms_table = $wpdb->terms;
        $new_term_taxonomy_table = $wpdb->term_taxonomy;
        $new_term_relationships_table = $wpdb->term_relationships;

        $new_blog_locations = $menu_locations;

        set_theme_mod( 'nav_menu_locations', $new_blog_locations );

        // First, the menus
        $menus_ids = implode( ',', $menu_locations );

        if ( empty( $menus_ids ) )
            return;

        $menus = $wpdb->get_results(
            "SELECT * FROM $templated_terms_table
            WHERE term_id IN ( $menus_ids )"
        );


        if ( ! empty( $menus ) ) {

            foreach ( $menus as $menu ) {

                // Inserting the menu
                $wpdb->query(
                    $wpdb->prepare(
                        "INSERT IGNORE INTO $new_terms_table
                        (term_id, name, slug, term_group)
                        VALUES
                        (%d, %s, %s, %d)",
                        $menu->term_id,
                        $menu->name,
                        $menu->slug,
                        $menu->term_group
                    )
                );




                // Terms taxonomies
                $term_taxonomies = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT * FROM $templated_term_taxonomy_table
                        WHERE term_id = %d",
                        $menu->term_id
                    )
                );


                $terms_taxonomies_ids = array();
                foreach ( $term_taxonomies as $term_taxonomy ) {
                    $terms_taxonomies_ids[] = $term_taxonomy->term_taxonomy_id;

                    // Inserting terms taxonomies
                    $wpdb->query(
                        $wpdb->prepare(
                            "INSERT IGNORE INTO $new_term_taxonomy_table
                            (term_taxonomy_id, term_id, taxonomy, description, parent, count)
                            VALUES
                            (%d, %d, %s, %s, %d, %d)",
                            $term_taxonomy->term_taxonomy_id,
                            $term_taxonomy->term_id,
                            $term_taxonomy->taxonomy,
                            empty( $term_taxonomy->description ) ? '' : $term_taxonomy->description,
                            $term_taxonomy->parent,
                            $term_taxonomy->count
                        )
                    );
                }


                $terms_taxonomies_ids = implode( ',', $terms_taxonomies_ids );

                $term_relationships = $wpdb->get_results(
                    "SELECT * FROM $templated_term_relationships_table
                    WHERE term_taxonomy_id IN ( $terms_taxonomies_ids )"
                );



                $objects_ids = array();
                foreach ( $term_relationships as $term_relationship ) {
                    $objects_ids[] = $term_relationship->object_id;

                    // Inserting terms relationships
                    $wpdb->query(
                        $wpdb->prepare(
                            "INSERT IGNORE INTO $new_term_relationships_table
                            (object_id, term_taxonomy_id, term_order)
                            VALUES
                            (%d, %d, %d)",
                            $term_relationship->object_id,
                            $term_relationship->term_taxonomy_id,
                            $term_relationship->term_order
                        )
                    );
                }

                // We need to split the queries here due to MultiDB issues

                // Inserting the objects
                $objects_ids = implode( ',', $objects_ids );

                $objects = $wpdb->get_results( "SELECT * FROM $templated_posts_table
                    WHERE ID IN ( $objects_ids )", ARRAY_N );

                foreach ( $objects as $object ) {
                    $values = '("' . implode( '","', $object ) . '")';
                    $wpdb->query( "INSERT IGNORE INTO $new_posts_table VALUES $values" );
                }


                // Inserting the objects meta
                $objects_meta = $wpdb->get_results( "SELECT * FROM $templated_postmeta_table
                    WHERE post_id IN ( $objects_ids )", ARRAY_N );

                foreach ( $objects_meta as $object_meta ) {
                    $values = '("' . implode( '","', $object_meta ) . '")';
                    $wpdb->query( "INSERT IGNORE INTO $new_postmeta_table VALUES $values" );
                }

            }

        }
	}




	

}