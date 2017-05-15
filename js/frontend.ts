// Declare global modules
declare var jQuery: any;
declare var gform: any;
declare var gformCalculateTotalPrice:any;

/**
 * GFRecurlyField class
 */
abstract class GFRecurlyField {

    /** * Gravity Form ID */
    public formId: number;

    /** GFRecurlyTotal Instance */
    public total:any;

    /**
     * Constructor
     *
     * @param   {number}    formId
     */
    constructor(formId: number) {
        /** Define form ID */
        this.formId = formId;

        /** Instantiate GFRecurlyTotal */
        this.total = new GFRecurlyTotal(this.formId);
    }
}

/**
 * Window interface
 */
interface Window {
    GFRecurlyTotalValue: string;
    GFRecurlyTotalValuePreCoupon: string;
    gf_recurly_frontend_strings: object;
    gf_form_conditional_logic: [any];
}

/**
 * GF Recurly Total
 */
class GFRecurlyTotal {

    /** Gravity Form ID */
    public formId: number;

    /**
     * Constructor
     *
     * @param   {number}    formId
     */
    constructor(formId: number) {
        this.formId = formId;
        this.init();
    }

    /**
     * Initialize total field
     */
    public init() {
        /** Set total */
        gform.addFilter('gform_product_total', function (total:string, formId:number) {
            window.GFRecurlyTotalValue = total;
            return total;
        }, 50);
    }

    /**
     * Get Total
     *
     * @returns total {string}
     */
    public get() {
        return window.GFRecurlyTotalValue;
    }

    public getNumber() {
        var _total = this.get();
        var total = _total.split(",").join("");
        return Number(total);
    }

    /**
     * Set Total
     *
     * @param newTotal {number}
     */
    public set(newTotal: string) {
        /** Update total */
        gform.addFilter('gform_product_total', function (total:string, formId:number) {
            window.GFRecurlyTotalValue = newTotal;
            return newTotal;
        }, 50);
        gformCalculateTotalPrice(this.formId);
    }
}

/**
 * GF Recurly Coupon Field
 */
class GFRecurlyFieldCoupon extends GFRecurlyField {

    /**
     * Constructor
     *
     * @param   {number}    formId
     */
    constructor(formId: number) {

        /** Parent class constructor */
        super(formId);

        let __this = this;

        jQuery('#gf_recurly_coupon_container_' + this.formId + ' #gfRecurlyCouponApply').on('click', function() {
            let couponCode = jQuery(this).siblings('input.gf_recurly_coupon_code').val();
            __this.apply(couponCode);
        });

        jQuery(document).on('click', '#gf_recurly_coupon_container_' + this.formId + ' #gfRecurlyCouponRemove', function(e) {
            e.preventDefault();
            __this.remove();
        })

    }

    public apply( couponCode:string ) {

        let __this = this;

        //var couponCode = jQuery('#gf_recurly_coupon_code_' + formId).val();
        // Make sure coupon provided
        if (couponCode === 'undefined' || couponCode == '') {
            return;
        }

        var $applyButton = jQuery('#gf_recurly_coupon_container_' + this.formId + ' #gfRecurlyCouponApply'),
        $inputField = jQuery('#gf_recurly_coupon_container_' + this.formId + 'input.gf_recurly_coupon_code');
        if ($applyButton.prop('disabled') || $inputField.prop('disabled') ) {
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
                'action':           'get_total_after_coupon',
                'couponCode':       safeCouponCode,
                'total':            __this.total.getNumber(),
                'formId':           __this.formId
            }
        }).done(function(response: string) {
            var _response = JSON.parse(response);
            // if successful
            if (_response.is_success) {
                var newPrice = _response.meta.new_total,
                discountValue = _response.meta.discount_value;

                // update price
                __this.total.set(newPrice);
                __this.couponApplyResponse(true, couponCode, discountValue);
            } else {
                // Failed
                __this.couponApplyResponse();
            }
            __this.spinner('hide');

        });
    }

    /**
     * Remove Coupon and recalculate
     *
     * @returns void
     */
    public remove() {
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
    }

    /**
     * Empty coupon container
     *
     * @returns void
     */
    public empty() {
        var $couponInfo = jQuery('#gf_recurly_coupon_container_' + this.formId + ' #gf_recurly_coupon_info');
        // Enable fields
        this.disableFields('enable');
        // Empty coupon info
        $couponInfo.empty();
    }


    /**
     * Sanitize coupon code
     *
     * @param couponCode {string}
     */
    public sanitize(couponCode:string) {
        var safeCouponCode = couponCode.replace(/[^A-Za-z0-9_-]+/g, '');
        return safeCouponCode;
    }

    /**
     * Show/Hide the spinner
     */
    public spinner( state:string = 'show' ) {
        var $spinner = jQuery('#gf_recurly_coupon_container_' + this.formId + ' #gf_recurly_coupon_spinner');
        if (state == 'show') {
            $spinner.show();
        } else {
            $spinner.hide();
        }
    }

    /**
     * Enable/Disable apply button
     */
    public disableFields( state:string = 'disable' ) {
        var $applyButton = jQuery('#gf_recurly_coupon_container_' + this.formId + ' #gfRecurlyCouponApply'),
        $inputField = jQuery('#gf_recurly_coupon_container_' + this.formId + ' #gf_recurly_coupon_code_' + this.formId);

        if (state == 'disable') {
            $applyButton.prop('disabled', true);
            $inputField.prop('disabled', true);
        } else {
            $applyButton.prop('disabled', false);
            $inputField.prop('disabled', false);
        }
    }

    public couponApplyResponse(isSuccessful:boolean = false, couponCode:string = '', discountValue:string = '') {

        var $couponInfo = jQuery('#gf_recurly_coupon_container_' + this.formId + ' #gf_recurly_coupon_info');
        var $couponError = jQuery('#gf_recurly_coupon_container_' + this.formId + ' #gf_recurly_coupon_error');
        // If correct coupon
        if (isSuccessful) {
            var couponDetails = `
            <table class="gf_recurly_coupon_table">
                <tr>
                    <td><a href="#" class="gf_recurly_coupon_remove" id="gfRecurlyCouponRemove" title="Remove Coupon">x</a> `+couponCode+`</td>
                    <td>`+discountValue+`</td>
                </tr>
            </table>
            `;
            $couponInfo.html(couponDetails);

            // Disable apply button
            this.disableFields();
        } else {
            $couponError.addClass('isVisible');
            setTimeout(function(){
                $couponError.removeClass('isVisible');
            },3000);
            this.disableFields('enable');
        }
    }

}

/**
 * GF Recurly PRODUCT FIELD
 */
class GFRecurlyFieldProduct extends GFRecurlyField {

    /**
     * Constructor
     *
     * @param   {number}    formId
     */
    constructor(formId: number) {

        /** Parent class constructor */
        super(formId);

        /** Scope thingy */
        let __this = this;

        jQuery( document ).ready(function() {
            __this.init();
            jQuery(document).bind("gform_post_conditional_logic", function () {
                __this.init();
            });
        });
    }

    /**
     * Initialize
     */
    public init() {
        let __this = this;
        jQuery(document).ready(function() {
            let visibleInstance = __this.getVisibleInstance();
            if (visibleInstance) {
                __this.unsetInstancesValues();

                // Mark instance as selected
                __this.updateInstanceValue(visibleInstance);

                // Get data-plan-price attr from instance
                var currentTotal = __this.getPlanPriceFromInstance(visibleInstance)

                __this.total.set(currentTotal);
            } else {
                __this.total.set(0);
            }
        })

    }

    /**
     * Get all instances of a selector
     *
     * @param   {string}    selector
     */
    public getInstances() {
        let i:any = document.querySelectorAll('.gf_recurly_product_container');
        return i;
    }

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
    public getVisibleInstance() {
        let instances = this.getInstances();
        console.log(instances);
        for (let i of instances) {
            if (i.offsetParent != null) {
                return i;
            }
        }
    }

    /**
     * Get the value (plan_code) of an instance
     */
    public getPlanPriceFromInstance(instance:any) {
        var instanceChild = jQuery(instance).children('input');
        return instanceChild.data('planPrice');
    }

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
    public unsetInstanceValue(instance:any) {
        var instanceInput = jQuery(instance).children('input');
        var instanceCurrentValue = instanceInput.val();
        var splitValues = instanceCurrentValue.split('_x_');
        var instanceNewValue = splitValues[1];
        if (splitValues[1]) {
            instanceInput.val(instanceNewValue);
        }
    }

    /**
     * Remove gfRecurlySelectedPlan_x_ from every instance
     */
    public unsetInstancesValues() {
        let instances = this.getInstances();
        for (let i of instances) {
            this.unsetInstanceValue(i);
        }
    }

    /**
     * Add gfRecurlySelectedPlan_x_ to provided instance
     *
     * @param instance
     */
    public updateInstanceValue(instance:any) {
        var instanceInput = jQuery(instance).children('input');
        var instanceCurrentValue = instanceInput.val();
        var instanceNewValue = 'gfRecurlySelectedPlan_x_' + instanceCurrentValue;

        instanceInput.val(instanceNewValue);
    }


}

/**
 * GF_Recurly main class
 */
class GF_Recurly {

    /** Form ID */
    public formId: number;

    /** GFRecurlyTotal Instance */
    public total:any;

    /** GFRecurlyFieldCoupon Instance */
    public couponField:any;

    /** GFRecurlyFieldProduct Instance */
    public productField:any;

    constructor(formId: number) {
        this.formId         = formId;
        this.total          = new GFRecurlyTotal(this.formId);
        this.couponField    = new GFRecurlyFieldCoupon(this.formId);
        this.productField   = new GFRecurlyFieldProduct(this.formId);
    }
}

jQuery(document).bind('gform_post_render', function(event:any, form_id:number){

    /** Instantiate GF Recurly */
    var gf_recurly = new GF_Recurly(form_id);

    /** Watch for total change */
    jQuery('.ginput_total_'+form_id).next('input').on('change', function(e) {
        e.stopPropagation();
        gf_recurly.couponField.empty();
    });

    gf_recurly.productField.init();
});
