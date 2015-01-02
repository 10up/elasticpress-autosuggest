<?php

class EPATestSingleSite extends EPA_Test_Base {

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

		wp_set_current_user( $admin_id );

		ep_delete_index();
		ep_put_mapping();

		ep_activate();

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
	}

	/**
	 * Test a simple user search
	 *
	 * @since 0.9
	 */
	public function testUserSync() {
		$user_id = epa_create_and_sync_user();

//		ep_refresh_index();

//		$this->assertTrue( ! empty( $this->fired_actions['ep_sync_on_transition'] ) );

//		$post = ep_get_post( $post_id );
		$this->assertTrue( ! empty( $user_id ) && is_int( $user_id ));

	}
}