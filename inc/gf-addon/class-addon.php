<?php

// Include the payment add-on framework.
GFForms::include_payment_addon_framework();

/**
 * Class RecurWPGFAddOn
 *
 * Primary class to manage the RecurWP Gravity Form add-on.
 *
 * @since 1.0
 *
 * @uses GFPaymentAddOn
**/
class RecurWPGFAddOn extends GFPaymentAddOn {

    /**
     * Contains an instance of this class, if available.
     *
     * @since  1.0
     * @access private
     *
     * @used-by RecurWPGFAddOn::get_instance()
     *
     * @var object $_instance If available, contains an instance of this class.
     */
    private static $_instance = null;

    /**
     * Defines the version of the Stripe Add-On.
     *
     * @since  1.0
     * @access protected
     *
     * @used-by RecurWPGFAddOn::scripts()
     *
     * @var string $_version Contains the version, defined from stripe.php
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
    protected $_path = RECURWP_DIR_INC . 'gf-addon/addon.php';

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
     * @used-by RecurWPGFAddOn::maybe_override_field_value()
     *
     * @var string $_current_meta_key The meta key currently being processed.
     */
    protected $_current_meta_key = '';

    /**
     * Get an instance of this class.
     *
     * @return RecurWPGFAddOn
     */
    public static function get_instance() {
        if ( self::$_instance == null ) {
            self::$_instance = new RecurWPGFAddOn();
        }

        return self::$_instance;
    }

    /**
     * Handles hooks and loading of language files.
     */
    public function init() {
        parent::init();
        add_filter( 'gform_submit_button', array( $this, 'form_submit_button' ), 10, 2 );
        add_action( 'gform_after_submission', array( $this, 'after_submission' ), 10, 2 );
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
                    array(
                        'admin_page' => array( 'plugin_settings' ),
                        'tab'        => array( $this->_slug, $this->get_short_title() ),
                    )
                )
            ),

        );

        return array_merge( parent::scripts(), $scripts );
    }


    // # FRONTEND FUNCTIONS --------------------------------------------------------------------------------------------

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
     * @return array
     */
    public function plugin_settings_fields() {
        if ( isset( $_POST['gform-settings-save'] ) ) {

            // Instantiate RecurWP
            $recurwp = new RecurWP();

            // Revalidate Recurly info
            $recurwp->validate_recurly_info($this->get_plugin_setting( 'recurly_subdomain' ), $this->get_plugin_setting( 'recurly_private_key' ), false);
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


    // # HELPERS -------------------------------------------------------------------------------------------------------

    /**
     * A helper function for Recurly Subdomain and Private API Key callbacks
     *
     * @return bool
     */
    public function is_valid_recurly_info() {

        // Instantiate RecurWP
        $recurwp = new RecurWP();
        $subdomain = $this->get_plugin_setting( 'recurly_subdomain' );
        $api_key = $this->get_plugin_setting( 'recurly_private_key' );

        // Make sure subdomain and API key are not empty
        if ( ! rgblank( $subdomain ) && ! rgblank( $api_key ) ) {

            // Validate info
            $validate_info = $recurwp->validate_recurly_info($api_key, $subdomain);

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

        $is_valid_recurly_info = self::is_valid_recurly_info();

        return $is_valid_recurly_info;
    }

    /**
     * The feedback callback for the 'Recurly API Private Key' setting on the plugin settings
     *
     * @param string $api_key The setting value.
     *
     * @return bool
     */
    public function is_valid_recurly_key( $api_key = null ) {

        $is_valid_recurly_info = self::is_valid_recurly_info();

        return $is_valid_recurly_info;
    }
}
