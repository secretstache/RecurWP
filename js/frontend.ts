function recurwpApplyCoupon(formId) {

    // Get coupon code
    var couponCode = jQuery('#recurwp_coupon_code_' + formId).val();
    if (couponCode === 'undefined' || couponCode == '') {
        return;
    }


    // Show spinner and disable apply btn
    jQuery('#recurwp_coupon_container_' + formId + ' #recurwp_coupon_spinner').show();
    jQuery('#recurwp_coupon_container_' + formId + ' #recurwpDisableApplyBtn').prop('disabled', true);


    jQuery.ajax({
        method: 'POST',
        url: recurwp_frontend_strings.ajaxurl,
        data: {
            'action':'get_total_after_coupon',
            'couponCode': couponCode,
            'total': jQuery('#recurwp_total_no_discount_' + formId).val(),
            'formId': formId

        }
    }).done(function(response) {
        console.log(response);
        var _response = JSON.parse(response);

        // if successful
        if (_response.is_success) {

            // update price
            gform.addFilter('gform_product_total', function (total, formId) {
                return _response.meta.newPrice;
            });
            gformCalculateTotalPrice(formId);

            jQuery('#recurwp_coupon_container_' + formId + ' #recurwp_coupon_spinner').hide();
            window['new_total_' + formId] = _response.meta.newPrice;
        }
    });
}

function DisableApplyButton(formId) {
    var is_disabled = window['new_total_' + formId] == 0 || jQuery('#recurwp_coupon_code_' + formId).val() == '';

    if (is_disabled) {
        jQuery('#recurwp_coupon_container_' + formId + ' #recurwpDisableApplyBtn').prop('disabled', true);
    } else {
        jQuery('#recurwp_coupon_container_' + formId + ' #recurwpDisableApplyBtn').prop('disabled', false);
    }
}


gform.addFilter('gform_product_total', function (total, formId) {
    // Ignore forms that don't have a coupon field.
    if (jQuery('#recurwp_coupon_code_' + formId).length == 0) {
        return total;
    }

    jQuery('#recurwp_total_no_discount_' + formId).val(total);

    var coupon_code = gformIsHidden(jQuery('#recurwp_coupon_code_' + formId)) ? '' : jQuery('#recurwp_coupon_codes_' + formId).val(),
        has_coupon = coupon_code != '' || jQuery('#recurwp_coupons_' + formId).val() != '',
        new_total = total;

    return new_total;
}, 50);