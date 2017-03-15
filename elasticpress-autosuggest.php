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

define( 'EPAS_VERSION', '0.1.2' );
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
	add_filter( 'ep_config_mapping', 'epas_completion_mapping' );
	add_filter( 'ep_post_sync_args', 'epas_filter_term_suggest', 10, 2 );
	add_filter( 'ep_post_sync_args_post_prepare_meta', 'epas_no_blank_title', 10, 2 );
}

/**
 * Blank titles dont work with the completion mapping type
 *
 * @param  array $post_args
 * @param  int $post_id
 * @since  2.2
 * @return array
 */
function epas_no_blank_title( $post_args, $post_id ) {
	if ( empty( $post_args['post_title'] ) ) {
		unset( $post_args['post_title'] );
	}

	return $post_args;
}

/**
 * Add mapping for completion fields
 * 
 * @param  array $mapping
 * @return array
 */
function epas_completion_mapping( $mapping ) {
	$mapping['mappings']['post']['properties']['post_title']['fields']['completion'] = array(
		'type' => 'completion',
		'analyzer' => 'simple',
		'search_analyzer' => 'simple',
	);

	$mapping['mappings']['post']['properties']['term_suggest'] = array(
		'type' => 'completion',
		'analyzer' => 'simple',
		'search_analyzer' => 'simple',
	);

	return $mapping;
}

/**
 * Add term suggestions to be indexed
 *
 * @param $post_args
 * @param $post_id
 * @return array
 */
function epas_filter_term_suggest( $post_args, $post_id ) {
	$suggest = array();

	if ( ! empty( $post_args['terms'] ) ) {
		foreach ( $post_args['terms'] as $taxonomy ) {
			foreach ( $taxonomy as $term ) {
				$suggest[] = $term['name'];
			}
		}
	}

	if ( ! empty( $suggest ) ) {
		$post_args['term_suggest'] = $suggest;
	}

	return $post_args;
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
		'requires_install_reindex' => true,
		'requirements_status_cb' => 'epas_requirements_status',
	) );
} );
