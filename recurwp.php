<?php
/*
Plugin Name: RecurWP
Plugin URI: https://www.secretstache.com/
Description: RecurWP
Version: 0.1.0
Author: Secret Stache Media
Author URI: https://www.secretstache.com/
Text Domain: recurwp
Domain Path: /languages
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Constants
define( 'RECURWP_VERSION', '0.1.0' );
define( 'RECURWP_URL', plugin_dir_url( __FILE__ ) );
define( 'RECURWP_DIR', plugin_dir_path( __FILE__ ) );

// Grab files
require_once( RECURWP_DIR . 'gf-addon/addon.php' );
