<?php

if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class blog_templates_categories_table extends WP_List_Table {

    private $data;

	function __construct(){
        //Set parent defaults
        parent::__construct( array(
            'singular'  => 'category',  
            'plural'    => 'categories', 
            'ajax'      => false        
        ) );
        
    }

    function column_default( $item, $column_name ){
        $value = '';
    	switch ( $column_name ) {
            default		: $value = $item[ $column_name ]; break;
    	}
        return $value;
    }


    function get_columns(){
        $columns = array(
            'name'      => __( 'Name', 'blog_templates' ),
            'description'   => __( 'Description', 'blog_templates' )
        );
        return $columns;
    }


    function column_name( $item ) {
        $delete_link = add_query_arg( 
            array( 
                'action' => 'delete',
                'category' => (int)$item['ID'] 
            )
        );


        $edit_link = add_query_arg( 
            array( 
                'action' => 'edit',
                'category' => (int)$item['ID'] 
            )
        );

        $actions = array(
            'edit' => sprintf( __( '<a href="%s">Edit</a>', 'blog_templates' ), $edit_link ),
            
        );


        if ( ! $item['is_default'] ) {
            $actions['delete'] = sprintf( __( '<a href="%s">Delete</a>', 'blog_templates' ), $delete_link );
        }
        return $item['name'] . $this->row_actions($actions);
    }

    function column_description( $item ) {
        echo nl2br( $item['description'] );
    }

 

    function prepare_items() {

        $model = nbt_get_model();

        if( 'delete' === $this->current_action() ) {
            if ( isset( $_GET['category'] ) && $category = absint( $_GET['category'] ) )
                $model->delete_template_category( $category );
        }

    	$per_page = 7;

        $hidden = array();
    	$columns = $this->get_columns();
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array(
        	$columns, 
        	$hidden, 
        	$sortable
        );

        $current_page = $this->get_pagenum();
        
        $data = $model->get_templates_categories();

        $total_items = count( $data );

        $data = array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page );

        $this->items = $data;

        $this->set_pagination_args( array(
            'total_items' => $total_items,                
            'per_page'    => $per_page,                   
            'total_pages' => ceil($total_items/$per_page) 
        ) );

    }

}
?>