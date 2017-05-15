var __extends = (this && this.__extends) || (function () {
    var extendStatics = Object.setPrototypeOf ||
        ({ __proto__: [] } instanceof Array && function (d, b) { d.__proto__ = b; }) ||
        function (d, b) { for (var p in b) if (b.hasOwnProperty(p)) d[p] = b[p]; };
    return function (d, b) {
        extendStatics(d, b);
        function __() { this.constructor = d; }
        d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
    };
})();
/**
 * GFRecurlyField class
 */
var GFRecurlyField = (function () {
    /**
     * Constructor
     *
     * @param   {number}    formId
     */
    function GFRecurlyField(formId) {
        /** Define form ID */
        this.formId = formId;
        /** Instantiate GFRecurlyTotal */
        this.total = new GFRecurlyTotal(this.formId);
    }
    return GFRecurlyField;
}());
/**
 * GF Recurly Total
 */
var GFRecurlyTotal = (function () {
    /**
     * Constructor
     *
     * @param   {number}    formId
     */
    function GFRecurlyTotal(formId) {
        this.formId = formId;
        this.init();
    }
    /**
     * Initialize total field
     */
    GFRecurlyTotal.prototype.init = function () {
        /** Set total */
        gform.addFilter('gform_product_total', function (total, formId) {
            window.GFRecurlyTotalValue = total;
            return total;
        }, 50);
    };
    /**
     * Get Total
     *
     * @returns total {string}
     */
    GFRecurlyTotal.prototype.get = function () {
        return window.GFRecurlyTotalValue;
    };
    GFRecurlyTotal.prototype.getNumber = function () {
        var _total = this.get();
        var total = _total.split(",").join("");
        return Number(total);
    };
    /**
     * Set Total
     *
     * @param newTotal {number}
     */
    GFRecurlyTotal.prototype.set = function (newTotal) {
        /** Update total */
        gform.addFilter('gform_product_total', function (total, formId) {
            window.GFRecurlyTotalValue = newTotal;
            return newTotal;
        }, 50);
        gformCalculateTotalPrice(this.formId);
    };
    return GFRecurlyTotal;
}());
/**
 * GF Recurly Coupon Field
 */
var GFRecurlyFieldCoupon = (function (_super) {
    __extends(GFRecurlyFieldCoupon, _super);
    /**
     * Constructor
     *
     * @param   {number}    formId
     */
    function GFRecurlyFieldCoupon(formId) {
        var _this = 
        /** Parent class constructor */
        _super.call(this, formId) || this;
        var __this = _this;
        jQuery('#gf_recurly_coupon_container_' + _this.formId + ' #gfRecurlyCouponApply').on('click', function () {
            var couponCode = jQuery(this).siblings('input.gf_recurly_coupon_code').val();
            __this.apply(couponCode);
        });
        jQuery(document).on('click', '#gf_recurly_coupon_container_' + _this.formId + ' #gfRecurlyCouponRemove', function (e) {
            e.preventDefault();
            __this.remove();
        });
        return _this;
    }
    GFRecurlyFieldCoupon.prototype.apply = function (couponCode) {
        var __this = this;
        //var couponCode = jQuery('#gf_recurly_coupon_code_' + formId).val();
        // Make sure coupon provided
        if (couponCode === 'undefined' || couponCode == '') {
            return;
        }
        var $applyButton = jQuery('#gf_recurly_coupon_container_' + this.formId + ' #gfRecurlyCouponApply'), $inputField = jQuery('#gf_recurly_coupon_container_' + this.formId + 'input.gf_recurly_coupon_code');
        if ($applyButton.prop('disabled') || $inputField.prop('disabled')) {
            return;
        }
        // Filter everything except alphanumeric, hyphen and underscore
        var safeCouponCode = this.sanitize(couponCode);
        // Show spinner and disable apply btn
        this.spinner();
        this.disableFields();
        // Store precoupon value
        window.GFRecurlyTotalValuePreCoupon = this.total.get();
        // Ajax post coupon code to recurly API
        jQuery.ajax({
            method: 'POST',
            url: window.gf_recurly_frontend_strings.ajaxurl,
            data: {
                'action': 'get_total_after_coupon',
                'couponCode': safeCouponCode,
                'total': __this.total.getNumber(),
                'formId': __this.formId
            }
        }).done(function (response) {
            var _response = JSON.parse(response);
            // if successful
            if (_response.is_success) {
                var newPrice = _response.meta.new_total, discountValue = _response.meta.discount_value;
                // update price
                __this.total.set(newPrice);
                __this.couponApplyResponse(true, couponCode, discountValue);
            }
            else {
                // Failed
                __this.couponApplyResponse();
            }
            __this.spinner('hide');
        });
    };
    /**
     * Remove Coupon and recalculate
     *
     * @returns void
     */
    GFRecurlyFieldCoupon.prototype.remove = function () {
        var $couponInfo = jQuery('#gf_recurly_coupon_container_' + this.formId + ' #gf_recurly_coupon_info');
        var preCouponTotal = window.GFRecurlyTotalValuePreCoupon;
        // Show spinner
        this.spinner();
        // Enable fields
        this.disableFields('enable');
        // Empty coupon info
        $couponInfo.empty();
        // Reset form total
        this.total.set(preCouponTotal);
        // Hide spinner
        this.spinner('hide');
    };
    /**
     * Empty coupon container
     *
     * @returns void
     */
    GFRecurlyFieldCoupon.prototype.empty = function () {
        var $couponInfo = jQuery('#gf_recurly_coupon_container_' + this.formId + ' #gf_recurly_coupon_info');
        // Enable fields
        this.disableFields('enable');
        // Empty coupon info
        $couponInfo.empty();
    };
    /**
     * Sanitize coupon code
     *
     * @param couponCode {string}
     */
    GFRecurlyFieldCoupon.prototype.sanitize = function (couponCode) {
        var safeCouponCode = couponCode.replace(/[^A-Za-z0-9_-]+/g, '');
        return safeCouponCode;
    };
    /**
     * Show/Hide the spinner
     */
    GFRecurlyFieldCoupon.prototype.spinner = function (state) {
        if (state === void 0) { state = 'show'; }
        var $spinner = jQuery('#gf_recurly_coupon_container_' + this.formId + ' #gf_recurly_coupon_spinner');
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
    GFRecurlyFieldCoupon.prototype.disableFields = function (state) {
        if (state === void 0) { state = 'disable'; }
        var $applyButton = jQuery('#gf_recurly_coupon_container_' + this.formId + ' #gfRecurlyCouponApply'), $inputField = jQuery('#gf_recurly_coupon_container_' + this.formId + ' #gf_recurly_coupon_code_' + this.formId);
        if (state == 'disable') {
            $applyButton.prop('disabled', true);
            $inputField.prop('disabled', true);
        }
        else {
            $applyButton.prop('disabled', false);
            $inputField.prop('disabled', false);
        }
    };
    GFRecurlyFieldCoupon.prototype.couponApplyResponse = function (isSuccessful, couponCode, discountValue) {
        if (isSuccessful === void 0) { isSuccessful = false; }
        if (couponCode === void 0) { couponCode = ''; }
        if (discountValue === void 0) { discountValue = ''; }
        var $couponInfo = jQuery('#gf_recurly_coupon_container_' + this.formId + ' #gf_recurly_coupon_info');
        var $couponError = jQuery('#gf_recurly_coupon_container_' + this.formId + ' #gf_recurly_coupon_error');
        // If correct coupon
        if (isSuccessful) {
            var couponDetails = "\n            <table class=\"gf_recurly_coupon_table\">\n                <tr>\n                    <td><a href=\"#\" class=\"gf_recurly_coupon_remove\" id=\"gfRecurlyCouponRemove\" title=\"Remove Coupon\">x</a> " + couponCode + "</td>\n                    <td>" + discountValue + "</td>\n                </tr>\n            </table>\n            ";
            $couponInfo.html(couponDetails);
            // Disable apply button
            this.disableFields();
        }
        else {
            $couponError.addClass('isVisible');
            setTimeout(function () {
                $couponError.removeClass('isVisible');
            }, 3000);
            this.disableFields('enable');
        }
    };
    return GFRecurlyFieldCoupon;
}(GFRecurlyField));
/**
 * GF Recurly PRODUCT FIELD
 */
var GFRecurlyFieldProduct = (function (_super) {
    __extends(GFRecurlyFieldProduct, _super);
    /**
     * Constructor
     *
     * @param   {number}    formId
     */
    function GFRecurlyFieldProduct(formId) {
        var _this = 
        /** Parent class constructor */
        _super.call(this, formId) || this;
        /** Scope thingy */
        var __this = _this;
        jQuery(document).ready(function () {
            __this.init();
            jQuery(document).bind("gform_post_conditional_logic", function () {
                __this.init();
            });
        });
        return _this;
    }
    /**
     * Initialize
     */
    GFRecurlyFieldProduct.prototype.init = function () {
        var __this = this;
        jQuery(document).ready(function () {
            var visibleInstance = __this.getVisibleInstance();
            if (visibleInstance) {
                __this.unsetInstancesValues();
                // Mark instance as selected
                __this.updateInstanceValue(visibleInstance);
                // Get data-plan-price attr from instance
                var currentTotal = __this.getPlanPriceFromInstance(visibleInstance);
                __this.total.set(currentTotal);
            }
            else {
                __this.total.set(0);
            }
        });
    };
    /**
     * Get all instances of a selector
     *
     * @param   {string}    selector
     */
    GFRecurlyFieldProduct.prototype.getInstances = function () {
        var i = document.querySelectorAll('.gf_recurly_product_container');
        return i;
    };
    /**
     * Get the visible instance
     *
     * GF_Recurly treats the first visible instance as the selected
     * plan.
     *
     * @param   {array} instances   All instances of a selector
     *
     * @since v1.0
     */
    GFRecurlyFieldProduct.prototype.getVisibleInstance = function () {
        var instances = this.getInstances();
        console.log(instances);
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
    GFRecurlyFieldProduct.prototype.getPlanPriceFromInstance = function (instance) {
        var instanceChild = jQuery(instance).children('input');
        return instanceChild.data('planPrice');
    };
    /**
     * Note: The following functions handle addition and
     * removal of 'gfRecurlySelectedPlan_x_' string. If
     * this string is prefixed to a particular recurly plan
     * field, it means that the field is visible/active.
     * We then treat the field as the provider of plan_code
     * in submission data. Not a neat way, should have been
     * better ways, if only gravity forms liked developers.
     */
    /**
     * Remove gfRecurlySelectedPlan_x_ from provided instance
     *
     * @param instance
     */
    GFRecurlyFieldProduct.prototype.unsetInstanceValue = function (instance) {
        var instanceInput = jQuery(instance).children('input');
        var instanceCurrentValue = instanceInput.val();
        var splitValues = instanceCurrentValue.split('_x_');
        var instanceNewValue = splitValues[1];
        if (splitValues[1]) {
            instanceInput.val(instanceNewValue);
        }
    };
    /**
     * Remove gfRecurlySelectedPlan_x_ from every instance
     */
    GFRecurlyFieldProduct.prototype.unsetInstancesValues = function () {
        var instances = this.getInstances();
        for (var _i = 0, instances_2 = instances; _i < instances_2.length; _i++) {
            var i = instances_2[_i];
            this.unsetInstanceValue(i);
        }
    };
    /**
     * Add gfRecurlySelectedPlan_x_ to provided instance
     *
     * @param instance
     */
    GFRecurlyFieldProduct.prototype.updateInstanceValue = function (instance) {
        var instanceInput = jQuery(instance).children('input');
        var instanceCurrentValue = instanceInput.val();
        var instanceNewValue = 'gfRecurlySelectedPlan_x_' + instanceCurrentValue;
        instanceInput.val(instanceNewValue);
    };
    return GFRecurlyFieldProduct;
}(GFRecurlyField));
/**
 * GF_Recurly main class
 */
var GF_Recurly = (function () {
    function GF_Recurly(formId) {
        this.formId = formId;
        this.total = new GFRecurlyTotal(this.formId);
        this.couponField = new GFRecurlyFieldCoupon(this.formId);
        this.productField = new GFRecurlyFieldProduct(this.formId);
    }
    return GF_Recurly;
}());
jQuery(document).bind('gform_post_render', function (event, form_id) {
    /** Instantiate GF Recurly */
    var gf_recurly = new GF_Recurly(form_id);
    /** Watch for total change */
    jQuery('.ginput_total_' + form_id).next('input').on('change', function (e) {
        e.stopPropagation();
        gf_recurly.couponField.empty();
    });
    gf_recurly.productField.init();
});
