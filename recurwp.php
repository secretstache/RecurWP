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
 * Recurly Coupon Field settings
 */
function recurwp_recurly_coupon_field_settings( $position, $form_id ) {

    //create settings on position 25 (right after Field Label)
    if ( $position == 25 ) {
        $recurwp = new RecurWP_Recurly();
        $coupons = $recurwp->get_coupons();
        ?>
        <li class="recurly_coupon_setting field_setting">
            <label for="field_admin_label">
                <?php esc_html_e( 'Recurly Coupon', 'recurwp' ); ?>
                <?php gform_tooltip( 'form_field_encrypt_value' ) ?>
            </label>
            <select id="field_description_placement">
                <option value="">Choose...</option>
                <?php foreach( $coupons as $coupon ) { 
                    // if coupon is redeemable
                    if( $coupon->state == 'redeemable' ) { ?>
                        <option value="<?php echo $coupon->coupon_code;?>"><?php echo $coupon->coupon_code;?></option>
                        <?php 
                    }
                } ?>
            </select>
        </li>
        <?php
    }
}
add_action( 'gform_field_standard_settings', 'recurwp_recurly_coupon_field_settings', 10, 2 );

// $r = new RecurWP_Recurly();
// print_r($r->calculate_coupon_discount('20', 'save70'));
