<?php

class EPATestMultisite extends EPA_Test_Base {

	/**
	 * Setup each test.
	 *
	 * @since 0.1.0
	 */
	public function setUp() {
		global $wpdb;
		parent::setUp();
		$wpdb->suppress_errors();

		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );

		$this->factory->blog->create_many( 2, array( 'user_id' => $admin_id ) );

		$sites = ep_get_sites();
		$indexes = array();

		foreach ( $sites as $site ) {
			switch_to_blog( $site['blog_id'] );

			ep_delete_index();
			ep_put_mapping();

			$indexes[] = ep_get_index_name();

			restore_current_blog();
		}

		ep_activate();

		ep_delete_network_alias();
		ep_create_network_alias( $indexes );

		wp_set_current_user( $admin_id );

		EP_WP_Query_Integration::factory()->setup();

		$this->setup_test_post_type();

	}

	/**
	 * Clean up after each test. Reset our mocks
	 *
	 * @since 0.1.0
	 */
	public function tearDown() {
		parent::tearDown();

		$this->fired_actions = array();

		$sites = ep_get_sites();
		$indexes = array();

		foreach ( $sites as $site ) {
			switch_to_blog( $site['blog_id'] );

			ep_delete_index();

			restore_current_blog();
		}

		ep_delete_network_alias();
	}

	/**
	 * Test a simple post sync
	 *
	 * @since 0.9
	 */
	public function testPostSync() {
		$sites = ep_get_sites();

		foreach( $sites as $site ) {
			switch_to_blog( $site['blog_id'] );

			add_action( 'ep_sync_on_transition', array( $this, 'action_sync_on_transition' ), 10, 0 );

			$post_id = epa_create_and_sync_post();

			ep_refresh_index();

//			$this->assertTrue( ! empty( $this->fired_actions['ep_sync_on_transition'] ) );

			$post = ep_get_post( $post_id );
			$this->assertTrue( ! empty( $post ) );

			$this->fired_actions = array();

			restore_current_blog();
		}
	}
}