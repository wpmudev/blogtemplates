<?php
// Source: WooCommerce and http://mikejolley.com/2013/12/deprecating-plugin-functions-hooks-woocommmerce/

/**
 * Filte that contains all deprecated functions/hooks
 */
global $wc_map_deprecated_filters;

$wc_map_deprecated_filters = array(
	'woocommerce_cart_item_class'       => 'woocommerce_cart_table_item_class',
	'woocommerce_cart_item_product_id'  => 'hook_woocommerce_in_cart_product_id',
	'woocommerce_cart_item_thumbnail'   => 'hook_woocommerce_in_cart_product_thumbnail',
	'woocommerce_cart_item_price'       => 'woocommerce_cart_item_price_html',
	'woocommerce_cart_item_name'        => 'woocommerce_in_cart_product_title',
	'woocommerce_order_item_class'      => 'woocommerce_order_table_item_class',
	'woocommerce_order_item_name'       => 'woocommerce_order_table_product_title',
	'woocommerce_order_amount_shipping' => 'woocommerce_order_amount_total_shipping',
	'woocommerce_package_rates'         => 'woocommerce_available_shipping_methods'
);

foreach ( $wc_map_deprecated_filters as $new => $old )
	add_filter( $new, 'woocommerce_deprecated_filter_mapping' );

function woocommerce_deprecated_filter_mapping( $data, $arg_1 = '', $arg_2 = '', $arg_3 = '' ) {
	global $wc_map_deprecated_filters;

	$filter = current_filter();

	if ( isset( $wc_map_deprecated_filters[ $filter ] ) )
		if ( has_filter( $wc_map_deprecated_filters[ $filter ] ) ) {
			$data = apply_filters( $wc_map_deprecated_filters[ $filter ], $data, $arg_1, $arg_2, $arg_3 );
			_deprecated_function( 'The ' . $wc_map_deprecated_filters[ $filter ] . ' filter', '2.1', $filter );
		}

	return $data;
}