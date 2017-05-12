<?php

if ( ! class_exists( 'GFForms' ) ) {
    return;
}

class GF_Field_Recurly_Coupon extends GF_Field {

    /**
     * @var string $type The field type.
     */
    public $type = 'recurly-coupon';

    /**
     * Return the field title, for use in the form editor.
     *
     * @return string
     */
    public function get_form_editor_field_title() {
        return esc_attr__( 'Recurly Coupon', 'gravityformsrecurly' );
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
            'recurly_coupon_setting',
            'conditional_logic_field_setting',
            'label_setting',
            'admin_label_setting',
            'css_class_setting',
            'description_setting',
            'placeholder_setting',
            'visibility_setting',
            'prepopulate_field_setting',
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

        $form_id         = absint( $form['id'] );
		$is_entry_detail = $this->is_entry_detail();
		$is_form_editor  = $this->is_form_editor();
        $id              = (int) $this->id;
        $field_id    = $is_entry_detail || $is_form_editor || $form_id == 0 ? "input_$id" : 'input_' . $form_id . "_$id";

        $value        = esc_attr( $value );

        $size         = $this->size;
		$class_suffix = $is_entry_detail ? '_admin' : '';
		$class        = $size . $class_suffix . ' gf_recurly_coupon_code';

        $tabindex              = $this->get_tabindex();

        if ( $is_entry_detail ) {
            $input = "<input type='hidden' id='input_{$id}' name='input_{$id}' value='{$value}' />";

            return $input . '<br/>' . esc_html__( 'Coupon fields are not editable', 'gravityformsrecurly' );
        }

        $disabled_text         = $this->is_form_editor() ? 'disabled="disabled"' : '';
        $logic_event           = $this->get_conditional_logic_event( 'keyup' );
        $placeholder_attribute = $this->get_field_placeholder_attribute();
        $coupons_detail        = rgpost( "gf_recurly_coupons{$form_id}" );
        $coupon_codes          = empty( $coupons_detail ) ? '' : rgpost( "input_{$id}" );
        $value_class           = ($value ? 'has-value' : '');

        $input = "<div class='ginput_container gf_recurly_coupon_container {$value_class}' id='gf_recurly_coupon_container_{$form_id}'>" .
                 "<input name='input_{$id}' id='{$field_id}' type='text' value='{$value}' class='{$class}'  {$tabindex} {$logic_event} {$disabled_text}/>" .
                 "<input type='button' data-form-id='{$form_id}' value='" . esc_attr__( 'Apply', 'gravityformsrecurly' ) . "' id='gfRecurlyCouponApply' class='button' {$disabled_text} " . $this->get_tabindex() . '/> ' .
                 "<img style='display:none;' id='gf_recurly_coupon_spinner' src='" . GFCommon::get_base_url() . "/images/spinner.gif' alt='" . esc_attr__( 'please wait', 'gravityformsrecurly' ) . "'/>" .
		         "<div id='gf_recurly_coupon_info' class='gf_recurly_coupon_info'></div>" .
                 "<div id='gf_recurly_coupon_error' class='gf_recurly_coupon_error'><span>Invalid Coupon.</span></div>" .
		         "</div>";

        return $input;

    }
}

GF_Fields::register( new GF_Field_Recurly_Coupon() );
