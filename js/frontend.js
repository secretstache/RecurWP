var __extends = (this && this.__extends) || function (d, b) {
    for (var p in b) if (b.hasOwnProperty(p)) d[p] = b[p];
    function __() { this.constructor = d; }
    d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
};
var RecurWPField = (function () {
    /**
     * Constructor
     *
     * @param   {number}    formId
     */
    function RecurWPField(formId) {
        /**
         * Define form ID
         */
        this.formId = formId;
    }
    return RecurWPField;
}());
/**
 * RecurWP Total
 */
var RecurWPTotal = (function () {
    /**
     * Constructor
     *
     * @param   {number}    formId
     */
    function RecurWPTotal(formId) {
        /**
         * Define form ID
         */
        this.formId = formId;
        this.init();
    }
    RecurWPTotal.prototype.init = function () {
        var $totalEl = jQuery('.ginput_container_total input[type="hidden"]');
        var totalVal = $totalEl.val();
        /**
         * Set total
         */
        //window.RecurWPTotal = (totalVal) ? totalVal : 0.00;
        gform.addFilter('gform_product_total', function (total, formId) {
            window.RecurWPTotal = total;
            return total;
        }, 50);
    };
    RecurWPTotal.prototype.get = function () {
        return window.RecurWPTotal;
    };
    RecurWPTotal.prototype.set = function (newTotal) {
        /**
         * Update total
         */
        window.RecurWPTotal = newTotal;
        gform.addFilter('gform_product_total', function (total, formId) {
            return newTotal;
        }, 50);
    };
    return RecurWPTotal;
}());
/**
 * RecurWP Coupon Field
 */
var RecurWPFieldCoupon = (function (_super) {
    __extends(RecurWPFieldCoupon, _super);
    /**
     * Constructor
     *
     * @param   {number}    formId
     */
    function RecurWPFieldCoupon(formId) {
        var _this = 
        /**
         * Parent class constructor
         */
        _super.call(this, formId) || this;
        _this.total = new RecurWPTotal(_this.formId);
        return _this;
    }
    RecurWPFieldCoupon.prototype.applyCoupon = function (formId) {
        var _this = this;
        var couponCode = jQuery('#recurwp_coupon_code_' + formId).val();
        // Make sure coupon provided
        if (couponCode === 'undefined' || couponCode == '') {
            return;
        }
        var $applyButton = jQuery('#recurwp_coupon_container_' + this.formId + ' #recurwpCouponApply'), $inputField = jQuery('#recurwp_coupon_container_' + this.formId + ' #recurwp_coupon_code_' + this.formId);
        if ($applyButton.prop('disabled') || $inputField.prop('disabled')) {
            return;
        }
        // Filter everything except alphanumeric, hyphen and underscore 
        var safeCouponCode = this.sanitize(couponCode);
        // Show spinner and disable apply btn
        this.couponSpinner(this.formId);
        this.couponFieldsDisable();
        // Ajax post coupon code to recurly API
        jQuery.ajax({
            method: 'POST',
            url: recurwp_frontend_strings.ajaxurl,
            data: {
                'action': 'get_total_after_coupon',
                'couponCode': safeCouponCode,
                'total': this.total.get(),
                'formId': this.formId
            }
        }).done(function (response) {
            var _response = JSON.parse(response);
            // if successful
            if (_response.is_success) {
                var newPrice = _response.meta.new_total, discountValue = _response.meta.discount_value;
                // update price
                this.total.set(newPrice);
                _this.couponApplyResponse(true, couponCode, discountValue);
            }
            else {
                // Failed
                _this.couponApplyResponse();
            }
            _this.couponSpinner('hide');
        });
    };
    RecurWPFieldCoupon.prototype.sanitize = function (couponCode) {
        var safeCouponCode = couponCode.replace(/[^A-Za-z0-9_-]+/g, '');
        return safeCouponCode;
    };
    /**
     * Show/Hide the spinner
     */
    RecurWPFieldCoupon.prototype.couponSpinner = function (state) {
        if (state === void 0) { state = 'show'; }
        var $spinner = jQuery('#recurwp_coupon_container_' + this.formId + ' #recurwp_coupon_spinner');
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
    RecurWPFieldCoupon.prototype.couponFieldsDisable = function (state) {
        if (state === void 0) { state = 'disable'; }
        var $applyButton = jQuery('#recurwp_coupon_container_' + this.formId + ' #recurwpCouponApply'), $inputField = jQuery('#recurwp_coupon_container_' + this.formId + ' #recurwp_coupon_code_' + this.formId);
        // Disabled if a new price already exists
        //var is_disabled = window['new_total_' + this.formId] == 0;
        if (state == 'disable') {
            $applyButton.prop('disabled', true);
            $inputField.prop('disabled', true);
        }
        else {
            $applyButton.prop('disabled', false);
            $inputField.prop('disabled', false);
        }
    };
    RecurWPFieldCoupon.prototype.couponApplyResponse = function (isSuccessful, couponCode, discountValue) {
        if (isSuccessful === void 0) { isSuccessful = false; }
        if (couponCode === void 0) { couponCode = ''; }
        if (discountValue === void 0) { discountValue = ''; }
        var $couponInfo = jQuery('#recurwp_coupon_container_' + this.formId + ' #recurwp_coupon_info');
        var $couponError = jQuery('#recurwp_coupon_container_' + this.formId + ' #recurwp_coupon_error');
        // If correct coupon 
        if (isSuccessful) {
            var couponDetails = "\n            <table class=\"recurwp_coupon_table\">\n                <tr>\n                    <td><a href=\"javascript:void(0);\" onclick=\"recurwp.removeCoupon(" + this.formId + ")\" class=\"recurwp_coupon_remove\" title=\"Remove Coupon\">x</a> " + couponCode + "</td>\n                    <td>" + discountValue + "</td>\n                </tr>\n            </table>\n            ";
            $couponInfo.html(couponDetails);
            // Disable apply button
            this.couponFieldsDisable();
        }
        else {
            $couponError.addClass('isVisible');
            setTimeout(function () {
                $couponError.removeClass('isVisible');
            }, 3000);
            this.couponFieldsDisable('enable');
        }
    };
    return RecurWPFieldCoupon;
}(RecurWPField));
/**
 * RECURWP PRODUCT FIELD
 */
var RecurWPFieldProduct = (function () {
    /**
     * Constructor
     *
     * @param   {number}    formId
     */
    function RecurWPFieldProduct(formId) {
        // Define form ID
        this.formId = formId;
        // Because, jQuery
        var _this = this;
        var total = new RecurWPTotal(this.formId);
        jQuery(document).ready(function () {
            var instances = _this.getInstances('.recurwp_product_container');
            jQuery('#gform_' + _this.formId + ' :input').on('change', function (e) {
                e.stopPropagation();
                var visible_instance = _this.getVisibleInstance(instances);
                console.log(visible_instance);
                if (visible_instance) {
                    var current_total = _this.getInstanceValue(visible_instance);
                    total.set(current_total);
                }
                else {
                    total.set(0);
                }
            });
        });
    }
    /**
     * Get all instances of a selector
     *
     * @param   {string}    selector
     */
    RecurWPFieldProduct.prototype.getInstances = function (selector) {
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
    RecurWPFieldProduct.prototype.getVisibleInstance = function (instances) {
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
    RecurWPFieldProduct.prototype.getInstanceValue = function (instance) {
        //var input = instance.getElementById('recurwp_product_plan_price_' + this.formId);
        return instance.childNodes[0].value;
    };
    return RecurWPFieldProduct;
}());
var RecurWP = (function () {
    function RecurWP(formId) {
        this.formId = formId;
        var _this = this;
        var totalField = new RecurWPTotal(this.formId);
        this.coupon = new RecurWPFieldCoupon(this.formId);
        var productField = new RecurWPFieldProduct(this.formId);
    }
    RecurWP.prototype.applyCoupon = function (formId) {
    };
    return RecurWP;
}());
var recurwp = new RecurWP(formId);
