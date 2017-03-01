<?php

// Include the payment add-on framework.
GFForms::include_payment_addon_framework();

/**
 * Class RecurWP_GF_Recurly
 *
 * Primary class to manage the RecurWP Gravity Form add-on.
 *
 * @since 1.0.0
 */
class RecurWP_GF_Recurly extends GFPaymentAddOn {

    /**
     * Contains an instance of this class, if available.
     *
     * @since  1.0
     * @access private
     *
     * @var object $_instance If available, contains an instance of this class.
     */
    private static $_instance = null;

    /**
     * Defines the version of the Recurly Add-On.
     *
     * @since  1.0
     * @access protected
     *
     * @var string $_version Contains the version, defined from recurly.php
     */
    protected $_version = RECURWP_VERSION;

    /**
     * Defines the minimum Gravity Forms version required.
     *
     * @since  1.0
     * @access protected
     *
     * @var string $_min_gravityforms_version The minimum version required.
     */
    protected $_min_gravityforms_version = '1.9';

    /**
     * Defines the plugin slug.
     *
     * @since  1.0
     * @access protected
     *
     * @var string $_slug The slug used for this plugin.
     */
    protected $_slug = 'recurwp';

    /**
     * Defines the main plugin file.
     *
     * @since  1.0
     * @access protected
     *
     * @var string $_path The path to the main plugin file, relative to the plugins folder.
     */
    protected $_path = RECURWP_DIR . '/recurwp.php';

    /**
     * Defines the full path to this class file.
     *
     * @since  1.0
     * @access protected
     *
     * @var string $_full_path The full path.
     */
    protected $_full_path = __FILE__;

    /**
     * Defines the URL where this Add-On can be found.
     *
     * @since  1.0
     * @access protected
     *
     * @var string $_url The URL of the Add-On.
     */
    protected $_url = 'https://www.secretstache.com/';

    /**
     * Defines the title of this Add-On.
     *
     * @since  1.0
     * @access protected
     *
     * @var string $_title The title of the Add-On.
     */
    protected $_title = 'RecurWP Gravity Forms Add-On';

    /**
     * Defines the short title of the Add-On.
     *
     * @since  1.0
     * @access protected
     *
     * @var string $_short_title The short title.
     */
    protected $_short_title = 'RecurWP';

    /**
     * Defines if Add-On should use Gravity Forms servers for update data.
     *
     * @since  1.0
     * @access protected
     *
     * @var bool $_enable_rg_autoupgrade true
     */
    protected $_enable_rg_autoupgrade = false;

    /**
     * Defines if user will not be able to create feeds for a form until a credit card field has been added.
     *
     * @since  1.0
     * @access protected
     *
     * @var bool $_requires_credit_card true.
     */
    protected $_requires_credit_card = true;

    /**
     * Defines if callbacks/webhooks/IPN will be enabled and the appropriate database table will be created.
     *
     * @since  1.0
     * @access protected
     *
     * @var bool $_supports_callbacks true
     */
    protected $_supports_callbacks = true;

    /**
     * Recurly requires monetary amounts to be formatted as the smallest unit for the currency being used e.g. cents.
     *
     * @since  1.10.1
     * @access protected
     *
     * @var bool $_requires_smallest_unit true
     */
    protected $_requires_smallest_unit = true;

    /**
     * Holds the custom meta key currently being processed.
     *
     * @since  2.1.1
     * @access protected
     *
     * @var string $_current_meta_key The meta key currently being processed.
     */
    protected $_current_meta_key = '';

    /**
     * Get an instance of this class.
     *
     * @return RecurWP_GF_Recurly
     */
    public static function get_instance() {
        if ( self::$_instance == null ) {
            self::$_instance = new RecurWP_GF_Recurly();
        }

        return self::$_instance;
    }

    /**
     * Include the field early so it is available when entry exports are being performed.
     */
    public function pre_init() {
        parent::pre_init();
        if ( $this->is_gravityforms_supported() && class_exists( 'GF_Field' ) ) {
            require_once( 'includes/class-recurwp-gf-field-coupon.php' );
            require_once( 'includes/class-recurwp-gf-field-product.php' );
        }
    }

    /**
     * Handles hooks and loading of language files.
     */
    public function init() {
        parent::init();
        add_filter( 'gform_submit_button', array( $this, 'form_submit_button' ), 10, 2 );
    }

    public function init_admin() {
        parent::init_admin();

        add_action( 'gform_editor_js', array( $this, 'editor_script'));
        add_action( 'gform_field_standard_settings', array( $this, 'recurwp_gf_field_product_settings' ), 10, 2 );
        add_action( 'gform_field_standard_settings', array( $this, 'recurwp_recurly_coupon_field_settings' ), 10, 2 );
    }

    // # SCRIPTS & STYLES -----------------------------------------------------------------------------------------------

    /**
     * Return the scripts which should be enqueued.
     *
     * @return array
     */
    public function scripts() {
        $scripts = array(
            array(
                'handle'  => 'recurly.js',
                'src'     => 'https://js.recurly.com/v4/recurly.js',
                'version' => $this->_version,
                'deps'    => array( 'jquery' ),
                'enqueue' => array(
                    array( $this, 'frontend_script_callback' )
                )
            ),
            array(
                'handle'    => 'recurwp_frontend',
                'src'       => $this->get_base_url() . '/../../js/frontend.js',
                'version'   => $this->_version,
                'deps'      => array( 'jquery', 'recurly.js', 'gform_json' ),
                'in_footer' => false,
                'enqueue'   => array(
                    array( $this, 'frontend_script_callback' ),
                ),
                'strings'   => array(
                    'ajaxurl'       => admin_url( 'admin-ajax.php' )
                )
            ),
            array(
                'handle'    => 'recurwp_backend',
                'src'       => $this->get_base_url() . '/js/backend.js',
                'version'   => $this->_version,
                'deps'      => array( 'jquery' ),
                'in_footer' => false,
                'enqueue'   => array(
                    array(
                        'admin_page' => array( 'plugin_settings' ),
                        'tab'        => array( $this->_slug, $this->get_short_title() ),
                    ),
                ),
                'strings'   => array(
                    'spinner'          => GFCommon::get_base_url() . '/images/spinner.gif',
                    'validation_error' => esc_html__( 'Error validating this key. Please try again later.', 'recurwp' ),
                ),
            ),

        );

        return array_merge( parent::scripts(), $scripts );
    }

    public function styles() {
        $styles = array(
            array(  
                "handle"    => "recurwp_css",
                "src"       => RECURWP_URL . "/css/recurwp.css",
                "version"   => GFCommon::$version,
                "enqueue"   => array(
                    array( $this),
                )
            )
        );

        return array_merge( parent::styles(), $styles );
    }

    // # CUSTOM FIELDS -----------------------------------------------------------------------------------------------
    /**
     * Recurly Coupon Field settings
     */
    function recurwp_gf_field_product_settings( $position, $form_id ) {

        //create settings on position 25 (right after Field Label)
        if ( $position == 25 ) {
            $recurwp = new RecurWP_Recurly();
            $_plans = $recurwp->get_plans();
            $plans = $_plans['meta']
            ?>
            <li class="recurly_product_setting field_setting">
                <label for="field_admin_label">
                    <?php esc_html_e( 'Recurly Plan', 'recurwp' ); ?>
                </label>
                <select id="recurwp_field_plan" onchange="SetFieldProperty('recurwpFieldPlan', this.value);">
                    <option value="">Choose...</option>
                    <?php foreach( $plans as $plan ) { ?>
                        <option value="<?php echo $plan->plan_code;?>"><?php echo $plan->name;?></option>
                        <?php 
                    } ?>
                </select>
            </li>
            <?php
        }
    }

    /**
     * Recurly Coupon Field settings
     */
    function recurwp_recurly_coupon_field_settings( $position, $form_id ) {

        //create settings on position 25 (right after Field Label)
        if ( $position == 25 ) {
           
            ?>
        
            <?php
        }
    }
    
    function editor_script(){
        ?>
        <script type='text/javascript'>
            jQuery(document).bind('gform_load_field_settings', function(event, field, form){
                // Coupon field value
                jQuery('#recurwp_field_active_coupon').val(field.recurwpFieldActiveCoupon);
                
                // Plan field value  
                jQuery('#recurwp_field_plan').val(field.recurwpFieldPlan);
            });

        </script>
        <?php
    }



    // # FEED SETTINGS -------------------------------------------------------------------------------------------------

    /**
     * Configures the settings which should be rendered on the feed edit page.
     *
     * @since  1.0.0
     * @access public
     *
     * @return array The feed settings.
     */
    public function feed_settings_fields() {

        return array(

            array(
                'description' => '',
                'fields'      => array(
                    array(
                        'name'     => 'feedName',
                        'label'    => esc_html__( 'Name', 'gravityforms' ),
                        'type'     => 'text',
                        'class'    => 'medium',
                        'required' => true,
                        'tooltip'  => '<h6>' . esc_html__( 'Name', 'gravityforms' ) . '</h6>' . esc_html__( 'Enter a feed name to uniquely identify this setup.', 'gravityforms' )
                    )
                )
            ),
            array(
                'title'      => 'Recurly Settings',
                'fields'     => array(
                    array(
                        'name'     => 'recurringAmount',
                        'label'    => esc_html__( 'Subscription Plan', 'gravityforms' ),
                        'type'     => 'select',
                        'choices'  => $this->recurring_amount_choices(),
                        'required' => true,
                        'tooltip'  => '<h6>' . esc_html__( 'Recurring Amount', 'gravityforms' ) . '</h6>' . esc_html__( "Select which field determines the recurring payment amount, or select 'Form Total' to use the total of all pricing fields as the recurring amount.", 'gravityforms' )
                    )
                )
            ),
            array(
                'title'      => esc_html__( 'Other Settings', 'gravityforms' ),
                'fields'     => parent::other_settings_fields()
            ),

        );
    }

    /**
     * Billing info fields
     *
     * @since  1.0.0
     * @access public
     *
     * @return array Billing info fields.
     */
    public function billing_info_fields() {

        $fields = array(
            array( 'name' => 'firstName', 'label' => esc_html__( 'First Name', 'recurwp' ), 'required' => true ),
            array( 'name' => 'lastName', 'label' => esc_html__( 'Last Name', 'recurwp' ), 'required' => true ),
            array( 'name' => 'username', 'label' => esc_html__( 'Username', 'recurwp' ), 'required' => false ),
            array( 'name' => 'email', 'label' => esc_html__( 'Email', 'recurwp' ), 'required' => true ),
            array( 'name' => 'phone', 'label' => esc_html__( 'Phone', 'recurwp' ), 'required' => false ),
            array( 'name' => 'company', 'label' => esc_html__( 'Company', 'recurwp' ), 'required' => false ),
            array( 'name' => 'address', 'label' => esc_html__( 'Address', 'recurwp' ), 'required' => true ),
            array( 'name' => 'address2', 'label' => esc_html__( 'Address 2', 'recurwp' ), 'required' => false ),
            array( 'name' => 'city', 'label' => esc_html__( 'City', 'recurwp' ), 'required' => true ),
            array( 'name' => 'state', 'label' => esc_html__( 'State', 'recurwp' ), 'required' => true ),
            array( 'name' => 'zip', 'label' => esc_html__( 'Zip', 'recurwp' ), 'required' => true ),
            array( 'name' => 'country', 'label' => esc_html__( 'Country', 'recurwp' ), 'required' => true ),
        );

        return $fields;
    }

    /**
     * Get payment choices
     *
     * @since  1.0.0
     * @access public
     *
     * @return array An array of payment choices
     */
    public function get_payment_choices( $form ) {
        $fields  = GFAPI::get_fields_by_type( $form, array( 'product' ) );
        $choices = array(
            array( 'label' => esc_html__( 'Select a Subscription Plan', 'recurwp' ), 'value' => '' ),
        );

        return $choices;
    }

    /**
     * Provides choices (Recurly Plans) for Recurring Amount
     *
     * @since  1.0.0
     * @access public
     *
     * @return array An array of choices (Recurly Plans)
     */
    public function recurring_amount_choices() {

        // Instantiate RecurWP
        $recurly = new RecurWP_Recurly();

        $form                = $this->get_current_form();
        $_plans              = $recurly->get_plans();
        $plans               = $_plans['meta'];
        $recurring_choices   = $this->get_payment_choices( $form );
        foreach ($plans as $plan) {
            $name      = $plan->name;
            $code      = $plan->plan_code;
            //$price     =  $recurly->cents_to_dollars($plan['unit_amount_in_cents']['USD']);
            $recurring_choices[] = array(
                //'label' => $name . ' - $' . $price,
                'label' => $name,
                'value' => $code
            );
        }

        return $recurring_choices;
    }

    /**
     * Get amount column value
     *
     * @since  1.0.0
     * @access public
     *
     * @return string Plan price
     */
    public function get_column_value_amount( $feed ) {
        // Instantiate RecurWP
        $recurly   = new RecurWP_Recurly();
        $form      = $this->get_current_form();
        $plan_code = $feed['meta']['transactionType'] == 'subscription' ? rgars( $feed, 'meta/recurringAmount' ) : '';
        $plan_price_cents = $recurly->get_plan_price($plan_code);
        $plan_price = $recurly->cents_to_dollars($plan_price_cents, true);
        $plan_name = $recurly->get_plan_name($plan_code);

        $plan_description = $plan_name . ' - $' . $plan_price;

        return $plan_description;
    }

    // /**
    //  * Prevent feeds being listed or created if the API keys aren't valid.
    //  *
    //  * @since  1.0.0
    //  * @access public
    //  *
    //  *
    //  * @return bool True if feed creation is allowed. False otherwise.
    //  */
    // public function can_create_feed() {
    //
    //     return true;
    //
    // }
    //
    /**
     * Enable feed duplication on feed list page and during form duplication.
     *
     * @since  1.0.0
     * @access public
     *
     * @param int|array $id The ID of the feed to be duplicated or the feed object when duplicating a form.
     *
     * @return false
     */
    public function can_duplicate_feed( $id ) {

        return true;

    }

    /**
     * Define the markup for the field_map setting table header.
     *
     * @since  1.0.0
     * @access public
     *
     * @return string The header HTML markup.
     */
    public function field_map_table_header() {
        return '<thead>
                    <tr>
                        <th></th>
                        <th></th>
                    </tr>
                </thead>';
    }

    /**
     * Prevent the 'options' checkboxes setting being included on the feed.
     *
     * @since  1.0.0
     * @access public
     *
     * @return false
     */
    public function option_choices() {
        return false;
    }


    // # FRONTEND FUNCTIONS --------------------------------------------------------------------------------------------

    /**
     * Check if the form has an active Recurly feed and a credit card field.
     *
     * @since  1.0.0
     * @access public
     *
     * @param array $form The form currently being processed.
     *
     * @return bool If the script should be enqueued.
     */
    public function frontend_script_callback( $form ) {

        return $form && $this->has_feed( $form['id'] ) && $this->has_credit_card_field( $form );

    }


    /**
    * Add the text in the plugin settings to the bottom of the form if enabled for this form.
    *
    * @param string $button The string containing the input tag to be filtered.
    * @param array $form The form currently being displayed.
    *
    * @return string
    */
    function form_submit_button( $button, $form ) {
        $settings = $this->get_form_settings( $form );
        if ( isset( $settings['enabled'] ) && true == $settings['enabled'] ) {
            $text   = $this->get_plugin_setting( 'mytextbox' );
            $button = "<div>{$text}</div>" . $button;
        }

        return $button;
    }


    // # ADMIN FUNCTIONS -----------------------------------------------------------------------------------------------

    /**
    * Configures the settings which should be rendered on the add-on settings tab.
    *
    * @since  1.0.0
    * @access public
    *
    * @return array
    */
    public function plugin_settings_fields() {

        // If form is updated
        if ( isset( $_POST['gform-settings-save'] ) ) {

            // Instantiate RecurWP
            $recurly = new RecurWP_Recurly();

            // Get options
            $subdomain = $recurly->get_gf_option( 'recurly_subdomain' );
            $private_key = $recurly->get_gf_option( 'recurly_private_key' );

            // Revalidate Recurly info
            $recurly->validate_info( $subdomain, $private_key, false );
        }

        return array(
            array(
                'title'  => esc_html__( 'RecurWP Add-On Settings', 'recurwp' ),
                'fields' => array(
                    array(
                        'name'              => 'recurly_subdomain',
                        'tooltip'           => esc_html__( '', 'recurwp' ),
                        'label'             => esc_html__( 'Recurly Subdomain', 'recurwp' ),
                        'type'              => 'text',
                        'class'             => 'small',
                        'feedback_callback' => array( $this, 'is_valid_recurly_subdomain' ),
                    ),
                    array(
                        'name'              => 'recurly_private_key',
                        'tooltip'           => esc_html__( '', 'recurwp' ),
                        'label'             => esc_html__( 'Recurly API Private Key', 'recurwp' ),
                        'type'              => 'text',
                        'class'             => 'small',
                        'feedback_callback' => array( $this, 'is_valid_recurly_key' ),
                    ),
                    array(
                        'label'           => esc_html__( 'Is Recurly Validated?', 'recurwp' ),
                        'type'            => 'hidden',
                        'name'            => 'recurly_info_validated',
                    ),
                )
            )
        );
    }

    // # FORM SETTINGS -------------------------------------------------------------------------------------------------

    /**
     * Add supported notification events.
     *
     * @since  Unknown
     * @access public
     *
     * @param array $form The form currently being processed.
     *
     * @return array|false The supported notification events. False if feed cannot be found within $form.
     */
    public function supported_notification_events( $form ) {

        // If this form does not have a Recurly feed, return false.
        if ( ! $this->has_feed( $form['id'] ) ) {
            return false;
        }

        // Return Recurly notification events.
        return array(
            'complete_payment'          => esc_html__( 'Payment Completed', 'recurwp' ),
            'refund_payment'            => esc_html__( 'Payment Refunded', 'recurwp' ),
            'fail_payment'              => esc_html__( 'Payment Failed', 'recurwp' ),
            'create_subscription'       => esc_html__( 'Subscription Created', 'recurwp' ),
            'cancel_subscription'       => esc_html__( 'Subscription Canceled', 'recurwp' ),
            'add_subscription_payment'  => esc_html__( 'Subscription Payment Added', 'recurwp' ),
            'fail_subscription_payment' => esc_html__( 'Subscription Payment Failed', 'recurwp' ),
        );

    }

    // # RECURLY TRANSACTIONS -------------------------------------------------------------------------------------------------------

    /**
     * Subscribe the user to a Recurly plan.
     *
     * 1 - Update/Create new account.
     * 2 - Create new subscription by subscribing customer to plan.
     *
     * @since  1.0
     * @access public
     *
     * @param array $feed            The feed object currently being processed.
     * @param array $submission_data The customer and transaction data.
     * @param array $form            The form object currently being processed.
     * @param array $entry           The entry object currently being processed.
     *
     * @return array Subscription details if successful. Contains error message if failed.
     */
    public function subscribe( $feed, $submission_data, $form, $entry ) {

        // Instantiate Recurly
        $recurly = new RecurWP_Recurly();

        // Hold our data for response, we'll set these later in the process
        $is_success = false;
        $error_message = '';
        $subscription_id = '';
        $amount = '';

        /**
         * 1. Create/update Recurly Account
         */
        // Account info for account creation
        $account_info = array(
            'account_code'      => $submission_data['email'],
            'email'             => $submission_data['email'],
            'first_name'        => $submission_data['firstName'],
            'last_name'         => $submission_data['lastName'],
            'username'          => $submission_data['username'],
            'company_name'      => $submission_data['company'],
            // 'address'           => array(
            //     'address1'          => $submission_data['address'],
            //     'address2'          => $submission_data['address2'],
            //     'city'              => $submission_data['city'],
            //     'state'             => $submission_data['state'],
            //     'country'           => $submission_data['country'],
            //     'zip'               => $submission_data['zip']
            // )
        );
        $account_code           = $submission_data['email'];

        // Create user account
        $account_created = $recurly->maybe_create_account( $account_code, $account_info );

        /**
         * 2. Add/Update billing info if account created
         */
        if ( $account_created['is_success'] ) {
            $this->log_debug( __METHOD__ . "(): Recurly account creation SUCCESSFUL for account_code {$account_code}" );

            // Billing info array
            $billing_info = array(
                'account_code'      => $submission_data['email'],
                'first_name'        => $submission_data['firstName'],
                'last_name'         => $submission_data['lastName'],
                'number'            => $recurly->format_cc_number($submission_data['card_number']),
                'verification_value'=> $submission_data['card_security_code'],
                'month'             => $submission_data['card_expiration_date'][0],
                'year'              => $submission_data['card_expiration_date'][1],
                'phone'             => $submission_data['phone'],
                'address1'          => $submission_data['address'],
                'address2'          => $submission_data['address2'],
                'city'              => $submission_data['city'],
                'state'             => $submission_data['state'],
                'country'           => $submission_data['country'],
                'zip'               => $submission_data['zip']
            );

            // Update billing info
            $billing_updated = $recurly->update_billing_info( $billing_info );

            /**
             * 3. Create Subscription
             */
            if ( $billing_updated['is_success'] ) {
                $this->log_debug( __METHOD__ . "(): Billing info updation SUCCESSFUL for account_code {$account_code}" );

                // Get the plan code from feed meta
                $plan_code = $feed['meta']['recurringAmount'];

                // Create subscription
                $subscription_created = $recurly->create_subscription( $account_code, $plan_code );

                if ( $subscription_created['is_success'] ) {

                    // We did it!
                    $is_success = true;

                    // Get the price of the plan subscribed
                    $plan_price_cents = $subscription_created['meta']->unit_amount_in_cents;
                    $plan_price = $plan_price_cents / 100;
                    $amount = (int)$plan_price;

                    // Subscription ID
                    $subscription_id = $subscription_created['meta']->uuid;
                    $this->log_debug( __METHOD__ . "(): Subscription creation SUCCESSFUL for account_code: {$account_code}  {$subscription_created['message']}" );
                } else {

                    // Subscription failed
                    $error_message = "Unable to charge the provided credit card.";
                    $this->log_debug( __METHOD__ . "(): Subscription creation FAILED for account_code: {$account_code}  {$subscription_created['message']}" );
                }
            } else {
                $this->log_debug( __METHOD__ . "(): Billing info updation FAILED for account_code: {$account_code}  {$account_created['message']}" );
                $error_message = "Unable to update Billing Information.";
            }
        } else {
            $this->log_debug( __METHOD__ . "(): Recurly account creation FAILED for account_code: {$account_code}  {$account_created['message']}" );
            $error_message = "Unable to create account.";
        }

        // Return data
        return array(
            'is_success'      => $is_success,
            'error_message'   => $error_message,
            'subscription_id' => $subscription_id,
            'amount'          => $amount,
        );
    }

    // # OTHER HELPERS -------------------------------------------------------------------------------------------------------

    /**
    * Retrieve the labels for the various card types.
    *
    * @since  Unknown
    * @access public
    *
    * @return array The card labels available.
    */
    public function get_card_labels() {

        // Get credit card types.
        $card_types  = GFCommon::get_card_types();

        // Initialize credit card labels array.
        $card_labels = array();

        // Loop through card types.
        foreach ( $card_types as $card_type ) {

            // Add card label for card type.
            $card_labels[ $card_type['slug'] ] = $card_type['name'];

        }

        return $card_labels;

    }

    /**
    * A helper function for Recurly Subdomain and Private API Key callbacks
    *
    * @return bool
    */
    public function is_valid_recurly_info() {

        // Instantiate RecurWP
        $recurwp = new RecurWP_Recurly();
        $subdomain = $recurwp->get_gf_option( 'recurly_subdomain' );
        $api_key = $recurwp->get_gf_option( 'recurly_private_key' );

        // Make sure subdomain and API key are not empty
        if ( ! rgblank( $subdomain ) && ! rgblank( $api_key ) ) {

            // Validate info
            $validate_info = $recurwp->validate_info($api_key, $subdomain);

            // Returns true or false
            return $validate_info;

        } else {

            return false;

        }
    }

    /**
    * The feedback callback for the 'Recurly Subdomain' setting on the plugin settings
    *
    * @param string $subdomain The setting value.
    *
    * @return bool
    */
    public function is_valid_recurly_subdomain( $subdomain = null ) {

        return self::is_valid_recurly_info();

    }

    /**
    * The feedback callback for the 'Recurly API Private Key' setting on the plugin settings
    *
    * @param string $api_key The setting value.
    *
    * @return bool
    */
    public function is_valid_recurly_key( $api_key = null ) {

        return self::is_valid_recurly_info();

    }
}
