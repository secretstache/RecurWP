<?php

if ( ! class_exists( 'GFForms' ) ) {
    die();
}

class RecurWP_GF_Field_Product extends GF_Field {

    /**
     * @var string $type The field type.
     */
    public $type = 'recurly-product';

    /**
     * Return the field title, for use in the form editor.
     *
     * @return string
     */
    public function get_form_editor_field_title() {
        return esc_attr__( 'Recurly Products', 'recurwp' );
    }

    /**
     * Assign the field button to the Advanced Fields group.
     *
     * @return array
     */
    public function get_form_editor_button() {
        return array(
            'group' => 'pricing_fields',
            'text'  => $this->get_form_editor_field_title(),
        );
    }

    /**
     * The settings which should be available on the field in the form editor.
     *
     * @return array
     */
    function get_form_editor_field_settings() {
        return array(
            'recurly_product_setting',
            'conditional_logic_field_setting',
            'label_setting',
            'admin_label_setting',
            'css_class_setting',
            'description_setting',
            'placeholder_setting',
            'visibility_setting',
            'rules_setting',
            'error_message_setting',
        );
    }

    /**
     * Enable this field for use with conditional logic.
     *
     * @return bool
     */
    public function is_conditional_logic_supported() {
        return true;
    }

    /**
     * The scripts to be included in the form editor.
     *
     * @return string
     */
    public function get_form_editor_inline_script_on_page_render() {

        // set the default field label for the simple type field
        $script = sprintf( "function SetDefaultValues_simple(field) {field.label = '%s';}", $this->get_form_editor_field_title() ) . PHP_EOL;

        // initialize the fields custom settings
        $script .= "jQuery(document).bind('gform_load_field_settings', function (event, field, form) {" .
                   "var inputClass = field.inputClass == undefined ? '' : field.inputClass;" .
                   "jQuery('#input_class_setting').val(inputClass);" .
                   "});" . PHP_EOL;

        // saving the simple setting
        $script .= "function SetInputClassSetting(value) {SetFieldProperty('inputClass', value);}" . PHP_EOL;

        return $script;
    }

    /**
     * Define the fields inner markup.
     *
     * @param array $form The Form Object currently being processed.
     * @param string|array $value The field value. From default/dynamic population, $_POST, or a resumed incomplete submission.
     * @param null|array $entry Null or the Entry Object currently being edited.
     *
     * @return string
     */
    public function get_field_input( $form, $value = '', $entry = null ) {

        $form_id         = $form['id'];
        $is_entry_detail = $this->is_entry_detail();
        $id              = (int) $this->id;

        if ( $is_entry_detail ) {
            $input = "<input type='hidden' id='input_{$id}' name='input_{$id}' value='{$value}' />";

            return $input . '<br/>' . esc_html__( 'product fields are not editable', 'recurwp' );
        }

        $disabled_text         = $this->is_form_editor() ? 'disabled="disabled"' : '';
        $logic_event           = $this->get_conditional_logic_event( 'change' );
        $plan_price            = 199;

        // Instantiate RecurWP
        // $recurly               = new RecurWP_Recurly();
        // $plan_price_cents      = $recurly->get_plan_price($plan_code);
        // $plan_price            = $recurly->cents_to_dollars($plan_price_cents, true);

       $input = "<div class='ginput_container recurwp_product_container' id='recurwp_product_container_{$form_id}'>" .
		         "<input id='recurwp_product_plan_price_{$form_id}' type='hidden' value='{$value}'>" .
		         "</div>";

        return $input;

    }
}

GF_Fields::register( new RecurWP_GF_Field_Product() );
