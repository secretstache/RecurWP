<?php

// No direct accesss
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'RecurWP_Recurly' ) ) {
    /**
     * Recurly Helper Class
     *
     * @since      1.0.0
     */
    class RecurWP_Recurly {

        /**
         * Fields which can be added/updated for a Recurly Account
         *
         * @since  1.0.0
         * @access protected
         */
        protected static $account_writeable_attributes = array(
          'account_code', 'username', 'first_name', 'last_name', 'vat_number',
          'email', 'company_name', 'accept_language', 'billing_info', 'address',
          'tax_exempt', 'entity_use_code', 'cc_emails', 'shipping_addresses'
        );

        /**
         * Initializes Recurly API
         *
         * @since  1.0.0
         * @access public
         *
         * @return void
         */
        public function include_api() {

            // Get current options
            $sub_domain = self::get_gf_option('recurly_subdomain');
            $api_key = self::get_gf_option('recurly_private_key');

            // Configure Reculy API
            Recurly_Client::$subdomain = $sub_domain;
            Recurly_Client::$apiKey = $api_key;

            $recurly_client = new Recurly_Client();
            return $recurly_client;
        }

        /**
         * Send response status with error
         *
         * @since  1.0.0
         * @access public
         *
         * @return array
         */
        public function send_response( $status = true, $error = '' ) {
            $response = array(
                'status' => $status,
                'error' => $error
            );
            return $response;
        }

        /**
         * XML decode
         *
         * @since  1.0.0
         * @access public
         *
         * @return array
         */
        public function xml_decode( $xml ) {

            // TODO: Improve
            return json_decode( json_encode( (array) simplexml_load_string( $xml ) ), 1);
        }

        /**
         * Convert cents to dollars
         *
         * @since  1.0.0
         * @access public
         *
         * @return string
         */
        public function cents_to_dollars( $cents, $prefix = false ) {
            if ( ! $prefix ) {

                return number_format( ($cents / 100) , 2, '.', ',');
            } else {

                return money_format('$%i', ($cents / 100));
            }
        }

        /**
         * Retrieves RecurWP Gravity Form options
         *
         * @since  1.0.0
         * @access public
         *
         * @param string $option_name   Name of the option
         *
         * @return void
         */
        public function get_gf_option( $option_name ) {

            // Get all options
            $options = get_option( 'gravityformsaddon_recurwp_settings' );

            return isset( $options[ $option_name ] ) ? $options[ $option_name ] : null;
        }

        /**
         * Validate Recurly Info
         *
         * @since  1.0.0
         * @access public
         *
         * @param string $private_key   Recurly private Key
         * @param string $sub_domain    Recurly Subdomain
         * @param bool $cache           Validates recurly info again if false
         *
         * @return bool
         */
        public function validate_info( $private_key = null, $sub_domain = null, $cache = true ) {

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

        /**
         * Get Recurly plans from API
         *
         * @since  1.0.0
         * @access public
         *
         * @return array    All recurly plans
         */
        public function get_plans() {

            try {
                // Instantiate recurly client
                $recurly_client = self::include_api();

                // Get plans
                $plans = $recurly_client->request('GET', '/plans');

                // Test successful connection
                if ($plans->statusCode == '200') {

                    $plans_xml = $plans->body;

                    // Quicky dirty way to convert XML to php array
                    // TODO: Improve
                    $plans = json_decode( json_encode( (array) simplexml_load_string( $plans_xml ) ), 1);

                    return $plans['plan'];

                }
            } catch (Recurly_ValidationError $e) {
                print "Plans not found: $e";
            }
        }

        /**
         * Maybe create recurly account
         *
         * Checks if a user account exists on recurly, creates one if it doesn't exist.
         *
         * @since  1.0.0
         * @access public
         *
         * @param string $email         Treating Email as Recurly Account code
         * @param array $account_data   Data provided by user
         *
         * @return void
         */
        public function maybe_create_account( $account_code, $account_info = array('name' => '', 'value' => '') ) {

            try {

                // Instantiate recurly client
                $api = self::include_api();

                // Get user account
                $account = $api->request('GET', "/accounts/$account_code");

                // Create account if no account
                if ( $account->statusCode != '200' ) {

                        $account = new Recurly_Account($account_code);
                        $account->email = $account_code;
                        $account->create();

                        foreach( $account_info as $info ) {
                            $account->$info['name'] = $info['value'];
                        }

                        // return true;
                        return self::send_response();

                }

                // Update the account with new info
                elseif ( $account->statusCode == '200' ) {

                    // Update info
                    foreach( $account_info as $info ) {
                        $account->$info['name'] = $info['value'];
                    }

                    // return true
                    return self::send_response();
                }

            } catch (Recurly_ValidationError $e) {
                // return false and error
                return self::send_response(false, $e);
            }
        }

        /**
         * Update billing info
         *
         * @since  1.0.0
         * @access public
         *
         * @param string $option_name   Name of the option
         *
         * @return bool
         */
        public function update_billing_info( $user_billing_info = array('name' => '', 'value' => '') ) {
            try {

                $billing_info = new Recurly_BillingInfo();

                foreach( $user_billing_info as $info ) {
                    $billing_info->$info['name'] = $info['value'];
                }

                $billing_info->create();

                // return true
                return self::send_response();
            } catch (Recurly_ValidationError $e) {

                // The data or card are invalid
                return self::send_response(false, "Invalid data or card: $e");
            } catch (Recurly_NotFoundError $e) {

                // Could not find account
                return self::send_response(false, "Not Found: $e");
            }
        }

        /**
         * Create subscription
         *
         * @since  1.0.0
         * @access public
         *
         * @param string $account_code  Recurly Account code
         * @param string $plan_code     Recurly Plan code
         * @param bool $currency        Currency
         *
         * @return bool
         */
        public function create_subscription( $account_code, $plan_code, $currency = 'USD' ) {
            try {
                // Instantiate recurly client
                $api = self::include_api();

                $subscription = new Recurly_Subscription();
                $subscription->plan_code = $plan_code;
                $subscription->currency = $currency;
                $account = Recurly_Account::get($account_code);
                $subscription->account = $account;
                $subscription->create();

                // return true
                return self::send_response();

            } catch (Recurly_ValidationError $e) {

                return self::send_response(false, $e);
            } catch (Recurly_NotFoundError $e) {

                return self::send_response(false, "Account not found.\n");
            }
        }
    }
}
