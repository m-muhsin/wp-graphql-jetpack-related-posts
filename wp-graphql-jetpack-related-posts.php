<?php

/**
 * @wordpress-plugin
 * Plugin Name:       WP GraphQL Jetpack Related Posts
 * Plugin URI:        https://github.com/m-muhsin/wp-graphql-jetpack-related-posts
 * Description:       Gives the related posts a GraphQL Field.
 * Version:           0.0.1
 * Requires at least: 5.0
 * Requires PHP:      7.2
 * Author:            Muhammad Muhsin
 * Author URI:        https://muhammad.dev
 * License:           GNU General Public License v2.0 / MIT License
 * Text Domain:       wp-graphql-reading-time
 * Domain Path:       /languages
 */

function graphql_jetpack_related_posts_register_types() {
	register_graphql_field(
		'post',
		'relatedPosts',
		[
			'type'        =>  [ 'list_of' => 'Integer' ],
			'description' => __( 'Related Posts', 'wp-graphql' ),
			'resolve'     => function ( $post ) {
				$related_posts = wpgql_jetpack_related_posts( $post->ID );
				return $related_posts;
			},
		]
	);
}
add_action( 'graphql_register_types', 'graphql_jetpack_related_posts_register_types' );

function wpgql_jetpack_related_posts( $post_id ) {

	if ( ! class_exists( 'Jetpack_Options' ) ) {
		return 'Please install and activate Jetpack plugin.';
	}

	$rp_class = new \Jetpack_Options();
	$blog_id = $rp_class->get_option( 'id' );
	$size    = 3;
	$body    = [
		'size' => (int) $size,
	];

	$response = wp_remote_post(
		"https://public-api.wordpress.com/rest/v1/sites/{$blog_id}/posts/$post_id/related/",
		array(
			'timeout'    => 10,
			'user-agent' => 'jetpack_related_posts',
			'sslverify'  => true,
			'body'       => $body,
		)
	);

	$results = json_decode( wp_remote_retrieve_body( $response ), true );

	$related_posts = [];

	if ( is_array( $results ) && ! empty( $results['hits'] ) ) {

		foreach ( $results['hits'] as $hit ) {

			$related_posts[] = $hit['fields']['post_id'];

		}
	}

	return $related_posts;

}

