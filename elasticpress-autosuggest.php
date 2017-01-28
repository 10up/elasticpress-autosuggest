<?php
/**
 * Plugin Name: ElasticPress Autosuggest
 * Plugin URI:  http://github.com/10up/ElasticPress-Autosuggest
 * Description: Extend ElasticPress's search inputs to display search suggestions
 * Version:     0.1.0
 * Author:      Aaron Holbrook, 10up
 * Author URI:  http://10up.com
 * License:     GPLv2+
 * Text Domain: epas
 * Domain Path: /languages
 */

/**
 * Copyright (c) 2014 Aaron Holbrook, 10up (email : info@10up.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

define( 'EPAS_VERSION', '0.1.0' );
define( 'EPAS_URL',     plugin_dir_url( __FILE__ ) );

/**
 * Output feature box summary
 * 
 * @since 2.3
 */
function epas_feature_box_summary() {
	?>
	<p><?php esc_html_e( 'Add autosuggest to ElasticPress powered search fields on the front end.', 'elasticpress' ); ?></p>
	<?php
}

/**
 * Output feature box long
 * 
 * @since 2.3
 */
function epas_feature_box_long() {
	?>
	<p><?php esc_html_e( 'Autosuggest is a very powerful search feature. As a user types a search query, they are automatically suggested items. Autosuggest dramatically increases that users will find what they are looking for on your site, improving overall experience.', 'elasticpress' ); ?></p>
	<?php
}

/**
 * Setup feature functionality
 *
 * @since  2.3
 */
function epas_setup() {
	add_action( 'wp_enqueue_scripts', 'epas_enqueue_scripts' );
	add_filter( 'ep_post_sync_args', 'epas_filter_term_suggest', 10, 2 );
}

/**
 * Enqueue our autosuggest script
 */
function epas_enqueue_scripts() {

	$js_url = ( defined( 'SCRIPT_DEBUG' ) && true === SCRIPT_DEBUG ) ? EPAS_URL . 'assets/js/src/elasticpress-autosuggest.js' : EPAS_URL . 'assets/js/elasticpress-autosuggest.min.js';
	$css_url = ( defined( 'SCRIPT_DEBUG' ) && true === SCRIPT_DEBUG ) ? EPAS_URL . 'assets/css/elasticpress-autosuggest.css' : EPAS_URL . 'assets/css/elasticpress-autosuggest.min.css';

	wp_enqueue_script(
		'elasticpress-autosuggest',
		$js_url,
		array( 'jquery' ),
		EPAS_VERSION,
		true
	);

	wp_enqueue_style(
		'elasticpress-autosuggest',
		$css_url,
		array(),
		EPAS_VERSION
	);

	// Output some variables for our JS to use - namely the index name and the post type to use for suggestions
	wp_localize_script( 'elasticpress-autosuggest', 'epas', array(
		'index' => ep_get_index_name( get_current_blog_id() ),
		'host'  => apply_filters( 'epas_host', ep_get_host() ),
		'postType' => apply_filters( 'epas_term_suggest_post_type', 'all' ),
	) );
}

/**
 * Add term suggestions to be indexed
 *
 * @param $post_args
 * @param $post_id
 * @since  2.3
 * @return mixed
 */
function epas_filter_term_suggest( $post_args, $post_id ) {
	$post = get_post( $post_id );

	if ( ! empty( $post ) && ! is_wp_error( $post ) ) {
		$suggest = epas_get_term_suggestions( $post_args, $post );

		// Add suggestion to the 'all' set
		$post_args['term_suggest_all'] = $suggest;

		// Add suggestion to the post type limited set
		if ( ! empty( $post->post_type ) && ! empty( $suggest ) ) {
			$post_args[ 'term_suggest_' . $post->post_type ] = $suggest;
		}
	}

	return $post_args;
}

/**
 * Get term suggestions for a given post based on terms in the taxonomies as well as the post title
 * Filterable for more options/suggestions
 *
 * @param $post
 * @since  2.3
 * @return mixed|void
 */
function epas_get_term_suggestions( $post_args, $post ) {
	$suggest   = array();
	$suggest[] = $post_args['post_title'];
	if ( ! empty( $post_args['terms'] ) ) {
		foreach ( $post_args['terms'] as $taxonomy ) {
			foreach ( $taxonomy as $term ) {
				$suggest[] = $term['name'];
			}
		}
	}

	return apply_filters( 'epas_term_suggest', $suggest, $post_args, $post );
}

/**
 * Determine WC feature reqs status
 *
 * @param  EP_Feature_Requirements_Status $status
 * @since  2.3
 * @return EP_Feature_Requirements_Status
 */
function epas_requirements_status( $status ) {
	$host = ep_get_host();

	if ( ! preg_match( '#elasticpress\.io#i', $host ) ) {
		$status->code = 1;
		$status->message = __( "You aren't using <a href='https://elasticpress.io'>ElasticPress.io</a> so we can't be sure your Elasticsearch instance is secure.", 'elasticpress' );
	}

	return $status;
}

/**
 * Register the feature
 *
 * @since  2.3
 */
add_action( 'ep_setup_features', function() {
	ep_register_feature( 'autosuggest', array(
		'title' => 'Autosuggest',
		'setup_cb' => 'epas_setup',
		'feature_box_summary_cb' => 'epas_feature_box_summary',
		'feature_box_long_cb' => 'epas_feature_box_long',
		'requires_install_reindex' => false,
		'requirements_status_cb' => 'epas_requirements_status',
	) );
} );
