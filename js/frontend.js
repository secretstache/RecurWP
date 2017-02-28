/**
 * RECURWP PRODUCT FIELD
 */
var RecurWPProductField = (function () {
    function RecurWPProductField(formId) {
        // Define form ID
        this.formId = formId;
        // Because, jQuery
        var _this = this;
        jQuery(document).ready(function () {
            var instances = _this.getInstances('.recurwp_product_container');
            jQuery('#gform_' + _this.formId + ' :input').on('change', function (e) {
                e.stopPropagation();
                var visible_instance = _this.getVisibleInstance(instances);
                if (visible_instance) {
                    var current_total = _this.getInstanceValue(visible_instance);
                    _this.updateTotal(current_total);
                }
                else {
                    _this.updateTotal(0);
                }
            });
        });
    }
    /**
     * Get all instances of a selector
     *
     * @param   {string}    selector
     */
    RecurWPProductField.prototype.getInstances = function (selector) {
        var i = document.querySelectorAll(selector);
        return i;
    };
    /**
     * Get the visible instance
     *
     * RecurWP treats the first visible instance as the selected
     * plan.
     *
     * @param   {array} instances   All instances of a selector
     *
     * @since v1.0
     */
    RecurWPProductField.prototype.getVisibleInstance = function (instances) {
        for (var _i = 0, instances_1 = instances; _i < instances_1.length; _i++) {
            var i = instances_1[_i];
            if (i.offsetParent != null) {
                return i;
            }
        }
    };
    /**
     * Get the value (plan_code) of an instance
     */
    RecurWPProductField.prototype.getInstanceValue = function (instance) {
        //var input = instance.getElementById('recurwp_product_plan_price_' + this.formId);
        return instance.childNodes[0].value;
    };
    /**
     * Update form total
     */
    RecurWPProductField.prototype.updateTotal = function (newTotal) {
        window.recurwpTotal = newTotal;
        gform.addFilter('gform_product_total', function (total, formId) {
            return newTotal;
        });
        window['new_total_' + this.formId] = newTotal;
        gformCalculateTotalPrice(this.formId);
    };
    return RecurWPProductField;
}());
var RecurWP = (function () {
    function RecurWP(formId) {
        this.formId = formId;
        // Set default price
        gform.addFilter('gform_product_total', function (total, formId) {
            jQuery('#recurwp_total_no_discount_' + formId).val(total);
            return total;
        }, 50);
        var currentTotal = new RecurWPProductField(this.formId);
    }
    /**
     * RECURWP COUPON FIELD
     */
    RecurWP.prototype.applyCoupon = function (formId) {
        var _this = this;
        // Get coupon code
        var couponCode = jQuery('#recurwp_coupon_code_' + formId).val();
        if (couponCode === 'undefined' || couponCode == '') {
            return;
        }
        var $applyButton = jQuery('#recurwp_coupon_container_' + formId + ' #recurwpCouponApply'), $inputField = jQuery('#recurwp_coupon_container_' + formId + ' #recurwp_coupon_code_' + formId);
        if ($applyButton.prop('disabled') || $inputField.prop('disabled')) {
            return;
        }
        // Preseve pre discount coupon 
        this.totalPreCoupon(formId, parseInt(jQuery('#recurwp_total_no_discount_' + formId).val()));
        // Filter everything except alphanumeric, hyphen and underscore 
        var safeCouponCode = couponCode.replace(/[^A-Za-z0-9_-]+/g, '');
        // Show spinner and disable apply btn
        this.couponSpinner(formId);
        this.couponFieldsDisable(formId);
        // Ajax post coupon code to recurly API
        jQuery.ajax({
            method: 'POST',
            url: recurwp_frontend_strings.ajaxurl,
            data: {
                'action': 'get_total_after_coupon',
                'couponCode': safeCouponCode,
                //'total':        parseInt(jQuery('#recurwp_total_no_discount_' + formId).val()),
                total: _this.getTotal(formId),
                'formId': formId
            }
        }).done(function (response) {
            var _response = JSON.parse(response);
            // if successful
            if (_response.is_success) {
                var newPrice = _response.meta.new_total, discountValue = _response.meta.discount_value;
                // update price
                _this.updateTotal(formId, newPrice);
                _this.couponApplyResponse(formId, true, couponCode, discountValue);
            }
            else {
                // Failed
                _this.couponApplyResponse(formId);
            }
            _this.couponSpinner(formId, 'hide');
        });
    };
    RecurWP.prototype.removeCoupon = function (formId) {
        var $couponInfo = jQuery('#recurwp_coupon_container_' + formId + ' #recurwp_coupon_info');
        var preCouponTotal = window['gform_recurwp_pre_coupon_total_' + formId];
        // Show spinner
        this.couponSpinner(formId);
        // Enable fields
        this.couponFieldsDisable(formId, 'enable');
        // Empty coupon info
        $couponInfo.empty();
        // Reset form total
        this.updateTotal(formId, preCouponTotal);
        // Hide spinner 
        this.couponSpinner(formId, 'hide');
    };
    RecurWP.prototype.totalPreCoupon = function (formId, oldTotal) {
        window['gform_recurwp_pre_coupon_total_' + formId] = oldTotal;
    };
    /**
     * Show/Hide the spinner
     */
    RecurWP.prototype.couponSpinner = function (formId, state) {
        if (state === void 0) { state = 'show'; }
        var $spinner = jQuery('#recurwp_coupon_container_' + formId + ' #recurwp_coupon_spinner');
        if (state == 'show') {
            $spinner.show();
        }
        else {
            $spinner.hide();
        }
    };
    /**
     * Enable/Disable apply button
     */
    RecurWP.prototype.couponFieldsDisable = function (formId, state) {
        if (state === void 0) { state = 'disable'; }
        var $applyButton = jQuery('#recurwp_coupon_container_' + formId + ' #recurwpCouponApply'), $inputField = jQuery('#recurwp_coupon_container_' + formId + ' #recurwp_coupon_code_' + formId);
        // Disabled if a new price already exists
        var is_disabled = window['new_total_' + formId] == 0;
        if (state == 'disable') {
            $applyButton.prop('disabled', true);
            $inputField.prop('disabled', true);
        }
        else {
            $applyButton.prop('disabled', false);
            $inputField.prop('disabled', false);
        }
    };
    RecurWP.prototype.couponApplyResponse = function (formId, isSuccessful, couponCode, discountValue) {
        if (isSuccessful === void 0) { isSuccessful = false; }
        if (couponCode === void 0) { couponCode = ''; }
        if (discountValue === void 0) { discountValue = ''; }
        var $couponInfo = jQuery('#recurwp_coupon_container_' + formId + ' #recurwp_coupon_info');
        var $couponError = jQuery('#recurwp_coupon_container_' + formId + ' #recurwp_coupon_error');
        // If correct coupon 
        if (isSuccessful) {
            var couponDetails = "\n            <table class=\"recurwp_coupon_table\">\n                <tr>\n                    <td><a href=\"javascript:void(0);\" onclick=\"recurwp.removeCoupon(" + formId + ")\" class=\"recurwp_coupon_remove\" title=\"Remove Coupon\">x</a> " + couponCode + "</td>\n                    <td>" + discountValue + "</td>\n                </tr>\n            </table>\n            ";
            $couponInfo.html(couponDetails);
            // Disable apply button
            this.couponFieldsDisable(formId);
        }
        else {
            $couponError.addClass('isVisible');
            setTimeout(function () {
                $couponError.removeClass('isVisible');
            }, 3000);
            this.couponFieldsDisable(formId, 'enable');
        }
    };
    RecurWP.prototype.getTotal = function (formId) {
        return window.recurwpTotal;
    };
    /**
     * Update form total
     */
    RecurWP.prototype.updateTotal = function (formId, newTotal) {
        window.recurwpTotal = newTotal;
        gform.addFilter('gform_product_total', function (total, formId) {
            return newTotal;
        });
        window['new_total_' + formId] = newTotal;
        gformCalculateTotalPrice(formId);
    };
    return RecurWP;
}());
var recurwp = new RecurWP(2);
