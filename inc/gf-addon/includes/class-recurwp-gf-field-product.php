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
		$form_id         = absint( $form['id'] );
		$is_entry_detail = $this->is_entry_detail();
		$is_form_editor  = $this->is_form_editor();

		$html_input_type = 'hidden';

		$logic_event = ! $is_form_editor && ! $is_entry_detail ? $this->get_conditional_logic_event( 'keyup' ) : '';
		$id          = (int) $this->id;
		$field_id    = $is_entry_detail || $is_form_editor || $form_id == 0 ? "input_$id" : 'input_' . $form_id . "_$id";

		$value        = esc_attr( $value );

		$size         = $this->size;
		$class_suffix = $is_entry_detail ? '_admin' : '';
		$class        = $size . $class_suffix;

		$max_length = is_numeric( $this->maxLength ) ? "maxlength='{$this->maxLength}'" : '';

		$tabindex              = $this->get_tabindex();
		$disabled_text         = $is_form_editor ? 'disabled="disabled"' : '';

        $fields          = $form['fields'];
        $field_obj;

        foreach($fields as $field) {
            if ($field['id'] == $id) {
                $field_obj = $field;
            }
        }
        if ($field_obj) {
            $recurly               = new GF_Recurly_Helper();
            $plan_code             = $field_obj['recurwpFieldPlan'];
            $plan_price_cents      = $recurly->get_plan_price($plan_code);
            $plan_price            = $recurly->cents_to_dollars($plan_price_cents);
            $value                 = esc_attr( $plan_code );
        }

		$input = "<input name='input_{$id}' id='{$field_id}' type='{$html_input_type}' value='{$value}' data-plan-price='{$plan_price}' class='{$class}'  {$tabindex} {$logic_event} {$disabled_text}/>";

		return sprintf( "<div class='ginput_container ginput_container_text recurwp_product_container'>%s</div>", $input );
	}
}

GF_Fields::register( new RecurWP_GF_Field_Product() );
