<?php 


/*
Plugin Name: Best selling products
Authors: Tobias & Jakob
*/


function best_selling_products_function( $args ) {

	$args =  [
		'post_type'			=> 'product',
		'meta_key' 			=> 'total_sales',
		'orderby'			=> 'meta_value_num',
		'posts_per_page'	=> 10
	];


	$loop = new WP_Query($args);


	if ( $loop->have_posts() ) {

		echo '
			<table>
				<tr>
					<th>Title</th>
					<th>Description</th>
					<th>Price</th>
				</tr>
		';

		while ( $loop->have_posts() ) : $loop->the_post();

			global $product;

			echo '
				<tr>
					<td><a href="'. get_the_permalink() .'">' . get_the_title() . '</a></td>
					<td>' . get_the_excerpt() . '</td>
					<td>' . $product->get_price() . '</td>
				</tr>
			';
			
		endwhile;


		echo '</table>';	


		} else {

			echo __( 'No products found' );

		}

		wp_reset_postdata();
		
}
add_shortcode( 'best_sellers', 'best_selling_products_function' );


