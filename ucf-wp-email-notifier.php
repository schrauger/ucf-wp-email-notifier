<?php
/*
Plugin Name: UCF WP Email Notifier
Description: Sends email notifications on every page publish, delete, and update.

Version: 1.2.1

Author: Stephen Schrauger
Plugin URI: https://github.com/schrauger/ucf-wp-email-notifier
Github Plugin URI: schrauger/ucf-wp-email-notifier
*/
/**
 * Created by IntelliJ IDEA.
 * User: stephen
 * Date: 2022-02-21
 * Time: 9:56 AM
 */

namespace ucf_wp_email_notifier;

include plugin_dir_path( __FILE__ ) . '/admin-settings.php'; // define which post types to watch, and what email to send to
include plugin_dir_path( __FILE__ ) . '/post-watcher.php'; // watches for changes and sends emails

// plugin css/js
//add_action( 'enqueue_block_assets', __NAMESPACE__ .  '\\add_css' );
//add_action( 'enqueue_block_assets', __NAMESPACE__ .  '\\add_js' );

// plugin activation hooks
register_activation_hook( __FILE__, __NAMESPACE__ . '\\activation' );
register_deactivation_hook( __FILE__, __NAMESPACE__ . '\\deactivation' );
register_uninstall_hook( __FILE__, __NAMESPACE__ . '\\deactivation' );

// run on plugin activation
function activation() {
}

// run on plugin deactivation
function deactivation() {
}

// run on plugin complete uninstall
function uninstall() {
}