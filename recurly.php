<?php
/*
Plugin Name: Gravity Forms Recurly Add-On
Plugin URI: https://www.secretstache.com/
Description: Integrates Gravity Forms with Recurly, enabling end users to purchase goods and services through Gravity Forms.
Version: 1.0.0
Author: Secret Stache Media
Author URI: https://www.secretstache.com/
Text Domain: gravityformsrecurly
Domain Path: /languages
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Global Constants
define( 'GF_RECURLY_VERSION', '1.0.0' );
define( 'GF_RECURLY_URL', plugin_dir_url( __FILE__ ) );
define( 'GF_RECURLY_DIR', plugin_dir_path( __FILE__ ) );

// Path Constants
define( 'GF_RECURLY_DIR_INC', trailingslashit(GF_RECURLY_DIR . '/inc') );
define( 'GF_RECURLY_DIR_LIB', trailingslashit(GF_RECURLY_DIR . '/lib') );

// Grab files
if ( ! class_exists('Recurly_Client') ) {
    require_once( GF_RECURLY_DIR_LIB . 'recurly.php' );
}
require_once( GF_RECURLY_DIR_INC . 'class-gf-recurly-helper.php' );

// If Gravity Forms is loaded, bootstrap the Gravityforms Recurly Add-On.
add_action( 'gform_loaded', array( 'GF_Recurly_Bootstrap', 'load' ), 5 );

/**
 * Class GF_Recurly_Bootstrap
 *
 * Handles the loading of the Recurly GF Add-On and registers with the Add-On framework.
 *
 * @since 1.0.0
 */
class GF_Recurly_Bootstrap {

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

        require_once( GF_RECURLY_DIR_INC . 'gf-addon/class-addon.php' );

        GFAddOn::register( 'GF_Recurly' );

    }
}

/**
 * Obtains and returns an instance of the GF_Recurly class
 *
 * @since  1.0.0
 * @access public
 *
 * @uses GF_Recurly::get_instance()
 *
 * @return object GF_Recurly
 */
function gf_recurly() {
    return GF_Recurly::get_instance();
}

/**
 * Ajax apply coupon
 *
 * @return void
 */
function gf_recurly_ajax_apply_coupon() {
    if ( isset($_REQUEST) ) {
        $coupon_code    = $_REQUEST['couponCode'];
        $form_id        = $_REQUEST['formId'];
        $total          = $_REQUEST['total'];
        $recurly = new GF_Recurly_Helper();
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
add_action( 'wp_ajax_get_total_after_coupon', 'gf_recurly_ajax_apply_coupon');
add_action( 'wp_ajax_nopriv_get_total_after_coupon', 'gf_recurly_ajax_apply_coupon' );

/**
 * Add plan_code and payment_amount to submission_data
 */
function gf_recurly_submission_data( $submission_data, $feed, $form, $entry ) {

    // Instantiate GF_Recurly_Helper
    $recurly_helper = new GF_Recurly_Helper();

    $plan_code = $recurly_helper->extract_plan_code_from_entry($entry);
    if ($plan_code) {
        $plan_price_in_cents = $recurly_helper->get_plan_price($plan_code);
        $submission_data['plan_code'] = $plan_code;
        $submission_data['payment_amount'] = $recurly_helper->cents_to_dollars($plan_price_in_cents);
    }

    return $submission_data;
}
add_filter( 'gform_submission_data_pre_process_payment', 'gf_recurly_submission_data', 10, 4 );
