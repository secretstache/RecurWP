<?php
/*
Plugin Name: RecurWP
Plugin URI: https://www.secretstache.com/
Description: RecurWP
Version: 1.0.0
Author: Secret Stache Media
Author URI: https://www.secretstache.com/
Text Domain: recurwp
Domain Path: /languages
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Global Constants
define( 'RECURWP_VERSION', '1.0.0' );
define( 'RECURWP_URL', plugin_dir_url( __FILE__ ) );
define( 'RECURWP_DIR', plugin_dir_path( __FILE__ ) );

// Path Constants
define( 'RECURWP_DIR_INC', trailingslashit(RECURWP_DIR . '/inc') );
define( 'RECURWP_DIR_LIB', trailingslashit(RECURWP_DIR . '/lib') );

// Grab files
if ( ! class_exists('Recurly_Client') )
    require_once( RECURWP_DIR_LIB . 'recurly.php' );
require_once( RECURWP_DIR_INC . 'class-recurwp.php' );

// If Gravity Forms is loaded, bootstrap the RecurWP Recurly Add-On.
add_action( 'gform_loaded', array( 'RecurWP_Bootstrap', 'load' ), 5 );

/**
 * Class RecurWP_Bootstrap
 *
 * Handles the loading of the Recurly GF Add-On and registers with the Add-On framework.
 *
 * @since 1.0.0
 */
class RecurWP_Bootstrap {

    /**
     * If the Payment Add-On Framework exists, Recurly Add-On is loaded.
     *
     * @since  1.0.0
     * @access public
     *
     * @uses GFAddOn::register()
     *
     * @return void
     */
    public static function load() {

        if ( ! method_exists( 'GFForms', 'include_payment_addon_framework' ) ) {
            return;
        }

        require_once( RECURWP_DIR_INC . 'gf-addon/class-addon.php' );

        GFAddOn::register( 'RecurWP_GF_Recurly' );

    }
}

/**
 * Obtains and returns an instance of the RecurWP_GF_Recurly class
 *
 * @since  1.0.0
 * @access public
 *
 * @uses RecurWP_GF_Recurly::get_instance()
 *
 * @return object RecurWP_GF_Recurly
 */
function recurwp_gfaddon() {
    return RecurWP_GF_Recurly::get_instance();
}




/**
 * Ajax apply coupon
 *
 * @return void 
 */
function recurwp_ajax_apply_coupon() {
    if ( isset($_REQUEST) ) {
        $coupon_code    = $_REQUEST['couponCode'];
        $form_id        = $_REQUEST['formId'];
        $total          = $_REQUEST['total'];
        $recurly = new RecurWP_Recurly();
        // Make sure coupon not empty
        if ( empty( $coupon_code ) ) {
            die( json_encode( $recurly->send_response(false, 'Please provide a coupon code.' ) ) );
        }
        
        $new_price = $recurly->apply_coupon($total, $coupon_code);

        if ($new_price['is_success']) {
            $response = $new_price['meta'];
            die( json_encode( $recurly->send_response(true, '', $response) ) ); 
        } else {
            die( json_encode( $recurly->send_response(false, 'Coupon not found.' ) ) ); 
        }
    }
}
// Ajax action for coupon discount calculation
add_action( 'wp_ajax_get_total_after_coupon', 'recurwp_ajax_apply_coupon');
add_action( 'wp_ajax_nopriv_get_total_after_coupon', 'recurwp_ajax_apply_coupon' );


// $r = new RecurWP_Recurly();
// print_r($r->get_plans());
