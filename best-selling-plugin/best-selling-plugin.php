<?php 


/*
Plugin Name: Best selling products
Authors: Tobias & Jakob
*/



function best_selling_products_function( $args ) {

 
	$args =  [
		'post_type'			=> 'product',
		'meta_key' 			=> 'total_sales',
		'order_by'			=> 'meta_value_num',
		'posts_per_page'	=> 10
	];


	$loop = new WP_Query($args);
	exit(var_dump($loop));

	if ( $loop->have_posts() ) {
			while ( $loop->have_posts() ) : $loop->the_post();
				the_title();
				the_excerpt();
				echo $loop->price;
			endwhile;
		} else {
			echo __( 'No products found' );
		}
		wp_reset_postdata();
		
}
add_shortcode( 'best_sellers', 'best_selling_products_function' );


