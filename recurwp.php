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

// Global Constants
define( 'RECURWP_VERSION', '0.1.0' );
define( 'RECURWP_URL', plugin_dir_url( __FILE__ ) );
define( 'RECURWP_DIR', plugin_dir_path( __FILE__ ) );

// Path Constants
define( 'RECURWP_DIR_INC', trailingslashit(RECURWP_DIR . '/inc') );
define( 'RECURWP_DIR_LIB', trailingslashit(RECURWP_DIR . '/lib') );

// Grab files
require_once( RECURWP_DIR_INC . 'class-recurwp.php' );
require_once( RECURWP_DIR_INC . 'gf-addon/addon.php' );
if ( ! class_exists('Recurly_Base') )
    require_once( RECURWP_DIR_LIB . 'recurly.php' );
