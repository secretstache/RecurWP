<?php

// No direct accesss
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'RecurWP' ) ) {
    /**
     * Recurly Class
     *
     * @package    RecurWP
     * @since      0.1.0
     * @access     public
     */
    class RecurWP {

        /**
         * Validate Recurly Info
         *
         * @param string $private_key Recurly private Key
         * @param string $sub_domain Recurly Subdomain
         * @param string $cache Validates recurly info again if false
         *
         * @return bool
        **/
        public function validate_recurly_info( $private_key = null, $sub_domain = null, $cache = true ) {

            $recurly_key_info = get_transient( 'recurwp_recurly_key_info' );

            if ( ! $cache ) {
                $recurly_key_info = null;

                // Flush cache
                delete_transient( 'recurwp_recurly_key_info' );
            }

            // Check if already validated
            if ( $recurly_key_info ) {
                return true;
            }

            // Recurly_Client config
            Recurly_Client::$subdomain = $sub_domain;
            Recurly_Client::$apiKey = $private_key;

            // Instantiate recurly client
            $recurly_client = new Recurly_Client();

            // Try to get accounts
            try {
                $get = $recurly_client->request('GET', '/accounts');
                if ( $get->statusCode == '200' ) {

                    $recurly_key_info = array( 'is_valid_key' => '1' );

                    // Caching response
                    set_transient( 'recurwp_recurly_key_info', $recurly_key_info, 86400 ); //caching for 24 hours

                    return true;
                } else {

                    return false;
                }
            } catch (Exception $e) {

                // Get rid of error for now
                unset($e);

                return false;
            }
        }
    }
}
