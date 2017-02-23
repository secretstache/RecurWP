<?php

if ( ! class_exists( 'GFForms' ) ) {
    die();
}

class RecurWP_Coupon_GF_Field extends GF_Field {

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
        return esc_attr__( 'Recurly Coupon', 'recurwp' );
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
        // $id              = absint( $this->id );
        // $form_id         = absint( $form['id'] );
        // $is_entry_detail = $this->is_entry_detail();
        // $is_form_editor  = $this->is_form_editor();

        // // Prepare the value of the input ID attribute.
        // $field_id = $is_entry_detail || $is_form_editor || $form_id == 0 ? "input_$id" : 'input_' . $form_id . "_$id";

        // $value = esc_attr( $value );

        // // Get the value of the inputClass property for the current field.
        // $inputClass = $this->inputClass;

        // // Prepare the input classes.
        // $size         = $this->size;
        // $class_suffix = $is_entry_detail ? '_admin' : '';
        // $class        = $size . $class_suffix . ' ' . $inputClass;

        // // Prepare the other input attributes.
        // $tabindex              = $this->get_tabindex();
        // $logic_event           = ! $is_form_editor && ! $is_entry_detail ? $this->get_conditional_logic_event( 'keyup' ) : '';
        // $placeholder_attribute = $this->get_field_placeholder_attribute();
        // $required_attribute    = $this->isRequired ? 'aria-required="true"' : '';
        // $invalid_attribute     = $this->failed_validation ? 'aria-invalid="true"' : 'aria-invalid="false"';
        // $disabled_text         = $is_form_editor ? 'disabled="disabled"' : '';

        // // Prepare the input tag for this field.
        // $input = "<input name='input_{$id}' id='{$field_id}' type='text' value='{$value}' class='{$class}' {$tabindex} {$logic_event} {$placeholder_attribute} {$required_attribute} {$invalid_attribute} {$disabled_text}/>";

        // return sprintf( "<div class='ginput_container ginput_container_%s'>%s</div>", $this->type, $input );

        $form_id         = $form['id'];
        $is_entry_detail = $this->is_entry_detail();
        $id              = (int) $this->id;

        if ( $is_entry_detail ) {
            $input = "<input type='hidden' id='input_{$id}' name='input_{$id}' value='{$value}' />";

            return $input . '<br/>' . esc_html__( 'Coupon fields are not editable', 'recurwp' );
        }

        $disabled_text         = $this->is_form_editor() ? 'disabled="disabled"' : '';
        $logic_event           = $this->get_conditional_logic_event( 'change' );
        $placeholder_attribute = $this->get_field_placeholder_attribute();
        $coupons_detail        = rgpost( "recurwp_coupons{$form_id}" );
        $coupon_codes          = empty( $coupons_detail ) ? '' : rgpost( "input_{$id}" );

       $input = "<div class='ginput_container' id='recurwp_coupon_container_{$form_id}'>" .
		         "<input id='recurwp_coupon_code_{$form_id}' class='recurwp_coupon_code' onkeyup='DisableApplyButton({$form_id});' onchange='DisableApplyButton({$form_id});' onpaste='setTimeout(function(){DisableApplyButton({$form_id});}, 50);' type='text'  {$disabled_text} {$placeholder_attribute} " . $this->get_tabindex() . '/>' .
		         "<input type='button' disabled='disabled' onclick='recurwpApplyCoupon({$form_id});' value='" . esc_attr__( 'Apply', 'gravityformscoupons' ) . "' id='recurwpDisableApplyBtn' class='button' {$disabled_text} " . $this->get_tabindex() . '/> ' .
		         "<img style='display:none;' id='recurwp_coupon_spinner' src='" . GFCommon::get_base_url() . "/images/spinner.gif' alt='" . esc_attr__( 'please wait', 'gravityformscoupons' ) . "'/>" .
		         "<div id='gf_coupon_info'></div>" .
		         "<input type='hidden' id='recurwp_coupon_codes_{$form_id}' name='input_{$id}' value='" . esc_attr( $coupon_codes ) . "' {$logic_event} />" .
		         "<input type='hidden' id='recurwp_total_no_discount_{$form_id}'/>" .
		         "<input type='hidden' id='recurwp_coupons{$form_id}' name='recurwp_coupons{$form_id}' value='" . esc_attr( $coupons_detail ) . "' />" .
		         "</div>";

        return $input;

    }
}

GF_Fields::register( new RecurWP_Coupon_GF_Field() );
