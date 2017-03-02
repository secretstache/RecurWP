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
 * RecurWPField class
 */
var RecurWPField = (function () {
    /**
     * Constructor
     *
     * @param   {number}    formId
     */
    function RecurWPField(formId) {
        /** Define form ID */
        this.formId = formId;
        /** Instantiate RecurWPTotal */
        this.total = new RecurWPTotal(this.formId);
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
        this.formId = formId;
        this.init();
    }
    /**
     * Initialize total field
     */
    RecurWPTotal.prototype.init = function () {
        /** Set total */
        gform.addFilter('gform_product_total', function (total, formId) {
            window.RecurWPTotalValue = total;
            return total;
        }, 50);
    };
    /**
     * Get Total
     *
     * @returns total {string}
     */
    RecurWPTotal.prototype.get = function () {
        return window.RecurWPTotalValue;
    };
    RecurWPTotal.prototype.getNumber = function () {
        var _total = this.get();
        var total = _total.split(",").join("");
        return Number(total);
    };
    /**
     * Set Total
     *
     * @param newTotal {number}
     */
    RecurWPTotal.prototype.set = function (newTotal) {
        /** Update total */
        gform.addFilter('gform_product_total', function (total, formId) {
            window.RecurWPTotalValue = newTotal;
            return newTotal;
        }, 50);
        gformCalculateTotalPrice(this.formId);
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
        /** Parent class constructor */
        _super.call(this, formId) || this;
        var __this = _this;
        jQuery('#recurwp_coupon_container_' + _this.formId + ' #recurwpCouponApply').on('click', function () {
            var couponCode = jQuery(this).siblings('input.recurwp_coupon_code').val();
            __this.apply(couponCode);
        });
        jQuery(document).on('click', '#recurwp_coupon_container_' + _this.formId + ' #recurwpCouponRemove', function (e) {
            e.preventDefault();
            __this.remove();
        });
        return _this;
    }
    RecurWPFieldCoupon.prototype.apply = function (couponCode) {
        var __this = this;
        //var couponCode = jQuery('#recurwp_coupon_code_' + formId).val();
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
        this.spinner();
        this.disableFields();
        // Store precoupon value 
        window.RecurWPTotalValuePreCoupon = this.total.get();
        // Ajax post coupon code to recurly API
        jQuery.ajax({
            method: 'POST',
            url: window.recurwp_frontend_strings.ajaxurl,
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
    RecurWPFieldCoupon.prototype.remove = function () {
        var $couponInfo = jQuery('#recurwp_coupon_container_' + this.formId + ' #recurwp_coupon_info');
        var preCouponTotal = window.RecurWPTotalValuePreCoupon;
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
    RecurWPFieldCoupon.prototype.sanitize = function (couponCode) {
        var safeCouponCode = couponCode.replace(/[^A-Za-z0-9_-]+/g, '');
        return safeCouponCode;
    };
    /**
     * Show/Hide the spinner
     */
    RecurWPFieldCoupon.prototype.spinner = function (state) {
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
    RecurWPFieldCoupon.prototype.disableFields = function (state) {
        if (state === void 0) { state = 'disable'; }
        var $applyButton = jQuery('#recurwp_coupon_container_' + this.formId + ' #recurwpCouponApply'), $inputField = jQuery('#recurwp_coupon_container_' + this.formId + ' #recurwp_coupon_code_' + this.formId);
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
            var couponDetails = "\n            <table class=\"recurwp_coupon_table\">\n                <tr>\n                    <td><a href=\"#\" class=\"recurwp_coupon_remove\" id=\"recurwpCouponRemove\" title=\"Remove Coupon\">x</a> " + couponCode + "</td>\n                    <td>" + discountValue + "</td>\n                </tr>\n            </table>\n            ";
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
    return RecurWPFieldCoupon;
}(RecurWPField));
/**
 * RECURWP PRODUCT FIELD
 */
var RecurWPFieldProduct = (function (_super) {
    __extends(RecurWPFieldProduct, _super);
    /**
     * Constructor
     *
     * @param   {number}    formId
     */
    function RecurWPFieldProduct(formId) {
        var _this = 
        /** Parent class constructor */
        _super.call(this, formId) || this;
        /** Scope thingy */
        var __this = _this;
        jQuery(document).ready(function () {
            __this.init();
            jQuery('#gform_' + __this.formId)
                .not('#gform_' + __this.formId + ' :input.gform_hidden')
                .on('change', function (e) {
                // e.preventDefault();
                // e.stopPropagation();
                __this.init();
            });
        });
        return _this;
        // Trigger form change event 
        //jQuery('#gform_'+__this.formId+' :input').trigger('change');
    }
    /**
     * Initialize
     */
    RecurWPFieldProduct.prototype.init = function () {
        var visibleInstance = this.getVisibleInstance();
        if (visibleInstance) {
            var currentTotal = this.getInstanceValue(visibleInstance);
            this.total.set(currentTotal);
        }
        else {
            this.total.set(0);
        }
    };
    /**
     * Get all instances of a selector
     *
     * @param   {string}    selector
     */
    RecurWPFieldProduct.prototype.getInstances = function () {
        var i = document.querySelectorAll('.recurwp_product_container');
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
    RecurWPFieldProduct.prototype.getVisibleInstance = function () {
        var instances = this.getInstances();
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
        var instanceChild = jQuery(instance).children('#recurwp_product_plan_price_' + this.formId);
        return instanceChild.val();
    };
    return RecurWPFieldProduct;
}(RecurWPField));
/**
 * RecurWP main class
 */
var RecurWP = (function () {
    function RecurWP(formId) {
        this.formId = formId;
        this.total = new RecurWPTotal(this.formId);
        this.couponField = new RecurWPFieldCoupon(this.formId);
        this.productField = new RecurWPFieldProduct(this.formId);
    }
    return RecurWP;
}());
jQuery(document).bind('gform_post_render', function (event, form_id) {
    var recurwp = new RecurWP(form_id);
    jQuery('#gform_7').on('change', function () {
        // let instance = recurwp.productField.getVisibleInstance();
        // console.log(instance);
    });
    recurwp.productField.init();
});
