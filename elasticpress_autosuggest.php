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

/**
 * Built using grunt-wp-plugin
 * Copyright (c) 2013 10up, LLC
 * https://github.com/10up/grunt-wp-plugin
 */

/**
 * This plugin requires ElasticPress
 * http://github.com/10up/ElasticPress
 *
 * Deactivate if ElasticPress is not running
 */
function epas_activate_check() {
	if ( class_exists( 'EP_ElasticPress' ) ) {

		// Useful global constants
		define( 'EPAS_VERSION', '0.1.0' );
		define( 'EPAS_URL',     plugin_dir_url( __FILE__ ) );
		define( 'EPAS_PATH',    dirname( __FILE__ ) . '/' );

		require_once( EPAS_PATH . 'includes/class-epas-integration.php' );
	} else {

		// ElasticPress was unable to be found, deactivate plugin
		// @todo need to think through use cases here - if we have subsites activated, different admin notices
		// @todo also - need to think through dependency situation for network/subsites
		add_action( 'network_admin_notices', 'epas_deactivate_dependency_requirement' );
		add_action( 'admin_notices', 'epas_deactivate_dependency_requirement' );

		add_action( 'admin_init', 'epas_deactivate_plugin' );
	}
}
add_action( 'plugins_loaded', 'epas_activate_check', 8 );

/**
 * Display notice requiring main ElasticPress requirement
 */
function epas_deactivate_dependency_requirement() {
	echo '<div class="error"><p><strong>ElasticPress Autosuggest</strong> requires <a href="http://github.com/10up/ElasticPress">ElasticPress</a>; the plug-in has been <strong>deactivated</strong>.</p></div>';

	if ( isset( $_GET['activate'] ) ) {
		unset( $_GET['activate'] );
	}
}

/**
 * Action to deactivate plugin. Used if main ElasticPress plugin is not currently active
 */
function epas_deactivate_plugin() {
	deactivate_plugins( plugin_basename( __FILE__ ) );
}