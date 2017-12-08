<?php 


/*
Plugin Name: Best selling products
Authors: Tobias & Jakob
*/



function best_selling_products_function( $args ) {

	if ( storefront_is_woocommerce_activated() ) {
		$args = apply_filters( 'storefront_best_selling_products_args', array(
		'limit'   => 10,
		'columns' => 3,
		'title'      => esc_attr__( 'Best Sellers', 'storefront' ),
		) );
		
		echo '<section class=”storefront-product-section storefront-best-selling-products” aria-label=”Best Selling Products”>';


			echo storefront_do_shortcode( 'best_selling_products', array(
				'per_page' => intval( $args['limit'] ),
				'columns'  => intval( $args['columns'] ),
				) 
			);
	
		echo '</section>';
	}
}
add_shortcode( 'best_sellers', 'best_selling_products_function' );


