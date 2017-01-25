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
     * Handles hooks and loading of language files.
     */
    public function init() {
        parent::init();
        add_filter( 'gform_register_init_scripts', array( $this, 'register_init_scripts' ), 10, 3 );
        add_filter( 'gform_field_content', array( $this, 'add_recurly_inputs' ), 10, 5 );
        add_filter( 'gform_field_validation', array( $this, 'pre_validation' ), 10, 4 );
        add_filter( 'gform_submit_button', array( $this, 'form_submit_button' ), 10, 2 );
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
                'src'       => $this->get_base_url() . '/js/frontend.js',
                'version'   => $this->_version,
                'deps'      => array( 'jquery', 'recurly.js', 'gform_json' ),
                'in_footer' => false,
                'enqueue'   => array(
                    array( $this, 'frontend_script_callback' ),
                ),
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

        // Get default payment feed settings fields.
        $default_settings = parent::feed_settings_fields();

        // Prepare customer information fields.
        $customer_info_field = array(
            'name'       => 'customerInformation',
            'label'      => esc_html__( 'Customer Information', 'recurwp' ),
            'type'       => 'field_map',
            'field_map'  => array(
                array(
                    'name'       => 'email',
                    'label'      => esc_html__( 'Email', 'recurwp' ),
                    'required'   => true,
                    'field_type' => array( 'email', 'hidden' ),
                ),
                array(
                    'name'     => 'description',
                    'label'    => esc_html__( 'Description', 'recurwp' ),
                    'required' => false,
                ),
                array(
                    'name'       => 'coupon',
                    'label'      => esc_html__( 'Coupon', 'recurwp' ),
                    'required'   => false,
                    'field_type' => array( 'coupon', 'text' ),
                    'tooltip'    => '<h6>' . esc_html__( 'Coupon', 'recurwp' ) . '</h6>' . esc_html__( 'Select which field contains the coupon code to be applied to the recurring charge(s). The coupon must also exist in your Recurly Dashboard.', 'recurwp' ),
                ),
            ),
        );

        // Replace default billing information fields with customer information fields.
        $default_settings = $this->replace_field( 'billingInformation', $customer_info_field, $default_settings );

        // Define end of Metadata tooltip based on transaction type.
        if ( 'subscription' === $this->get_setting( 'transactionType' ) ) {
            $info = esc_html__( 'You will see this data when viewing a customer page.', 'recurwp' );
        } else {
            $info = esc_html__( 'You will see this data when viewing a payment page.', 'recurwp' );
        }

        // Remove the fields we don't need
        $default_settings = $this->remove_field( 'recurringTimes', $default_settings );
        $default_settings = $this->remove_field( 'trial', $default_settings );
        $default_settings = $this->remove_field( 'setupFee', $default_settings );
        $default_settings = $this->remove_field( 'billingCycle', $default_settings );

        return $default_settings;

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
        $form                = $this->get_current_form();
        $recurly_plans       = RecurWP_Recurly::get_plans();
        $recurring_choices   = $this->get_payment_choices( $form );
        foreach ($recurly_plans as $plan) {
            $recurring_choices[] = array(
                'label' => $plan['name'],
                'value' => $plan['plan_code']
            );
        }

        return $recurring_choices;
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
    // /**
    //  * Enable feed duplication on feed list page and during form duplication.
    //  *
    //  * @since  1.0.0
    //  * @access public
    //  *
    //  * @param int|array $id The ID of the feed to be duplicated or the feed object when duplicating a form.
    //  *
    //  * @return false
    //  */
    // public function can_duplicate_feed( $id ) {
    //
    //     return false;
    //
    // }

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
     * Register Recurly script when displaying form.
     *
     * @since  1.0.0
     * @access public
     *
     * @param array $form         Form object.
     * @param array $field_values Current field values. Not used.
     * @param bool  $is_ajax      If form is being submitted via AJAX.
     *
     * @return void
     */
    public function register_init_scripts( $form, $field_values, $is_ajax ) {

        // If form does not have a Recurly feed and does not have a credit card field, exit.
        if ( ! $this->has_feed( $form['id'] ) ) {
            return;
        }

        $cc_field = $this->get_credit_card_field( $form );

        if ( ! $cc_field ) {
            return;
        }

        // Prepare Recurly Javascript arguments.
        $args = array(
            'apiKey'     => '', // TODO: Get key dynamically
            'formId'     => $form['id'],
            'ccFieldId'  => $cc_field->id,
            'ccPage'     => $cc_field->pageNumber,
            'isAjax'     => $is_ajax,
            'cardLabels' => $this->get_card_labels(),
        );

        // Initialize Recurly script.
        $script = 'new RecurWP( ' . json_encode( $args ) . ' );';

        // Add Recurly script to form scripts.
        GFFormDisplay::add_init_script( $form['id'], 'recurly', GFFormDisplay::ON_PAGE_RENDER, $script );

    }

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
     * Add required Recurly inputs to form.
     *
     * @since  1.0.0
     * @access public
     *
     * @param string  $content The field content to be filtered.
     * @param object  $field   The field that this input tag applies to.
     * @param integer $form_id The current Form ID.
     *
     * @return string $content HTML formatted content.
     */
    public function add_recurly_inputs( $content, $field, $form_id ) {

        // If this form does not have a Recurly feed or if this is not a credit card field, return field content.
        if ( ! $this->has_feed( $form_id ) || 'creditcard' !== $field->get_input_type() ) {
            return $content;
        }
        $content .= '<input type="hidden" name="recurly-token" data-recurly="token">';

        return $content;

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
     * Initialize the AJAX hooks.
     *
     * @since  1.0.0
     * @access public
     *
     * @return void
     */
    public function init_ajax() {

        parent::init_ajax();

        add_action( 'wp_ajax_gf_validate_secret_key', array( $this, 'ajax_validate_secret_key' ) );
    }

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
                        'name'              => 'recurly_public_key',
                        'tooltip'           => esc_html__( '', 'recurwp' ),
                        'label'             => esc_html__( 'Recurly API Private Key', 'recurwp' ),
                        'type'              => 'text',
                        'class'             => 'small'
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


    // # RECURLY HELPERS -------------------------------------------------------------------------------------------------------


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
