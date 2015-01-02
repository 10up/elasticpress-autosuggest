<?php

class EPAS_Integration {

	/**
	 * Placeholder method
	 *
	 * @since 0.9
	 */
	public function __construct() { }

	/**
	 *
	 */
	public function setup() {

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		add_filter( 'ep_post_sync_args', array( $this, 'filter_term_suggest' ), 10, 2 );
	}

	public function enqueue_scripts() {
		$postfix = ( defined( 'SCRIPT_DEBUG' ) && true === SCRIPT_DEBUG ) ? '' : '.min';

		if ( ! is_admin() ) {
			wp_enqueue_script(
				'elasticpress-autosuggest',
				EPAS_URL . "assets/js/elasticpress_autosuggest{$postfix}.js",
				array( 'jquery' ),
				EPAS_VERSION,
				true
			);
		}
	}

	public function filter_term_suggest( $post_args, $post_id ) {
		$post = get_post( $post_id );

		if ( ! empty( $post ) && ! is_wp_error( $post ) ) {
			$suggest = $this->get_term_suggestions( $post_args, $post );
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
	 *
	 * @return mixed|void
	 */
	public function get_term_suggestions( $post_args, $post ) {
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
	 * Return a singleton instance of the current class
	 *
	 * @since 0.9
	 * @return object
	 */
	public static function factory() {
		static $instance = false;

		if ( ! $instance ) {
			$instance = new self();
			$instance->setup();
		}

		return $instance;
	}
}

EPAS_Integration::factory();