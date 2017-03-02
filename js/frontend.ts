// Declare global modules
declare var jQuery: any;
declare var gform: any;
declare var gformCalculateTotalPrice:any;

/**
 * RecurWPField class
 */
abstract class RecurWPField {

    /** * Gravity Form ID */
    public formId: number;

    /** RecurWPTotal Instance */
    public total:any;

    /**
     * Constructor 
     * 
     * @param   {number}    formId
     */
    constructor(formId: number) {
        /** Define form ID */
        this.formId = formId;   

        /** Instantiate RecurWPTotal */
        this.total = new RecurWPTotal(this.formId);
    }
}

/**
 * Window interface
 */
interface Window {
    RecurWPTotalValue: string;
    RecurWPTotalValuePreCoupon: string;
    recurwp_frontend_strings: object;
}

/**
 * RecurWP Total 
 */
class RecurWPTotal {

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
        gform.addFilter('gform_product_total', function (total:number, formId:number) {
            window.RecurWPTotalValue = total;
            return total;
        }, 50);
    }

    /**
     * Get Total
     * 
     * @returns total {string}
     */
    public get() {
        return window.RecurWPTotalValue;
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
    public set(newTotal: number) {
        /** Update total */
        gform.addFilter('gform_product_total', function (total:number, formId:number) {
            window.RecurWPTotalValue = newTotal;
            return newTotal;
        }, 50);
        gformCalculateTotalPrice(this.formId);
    }
}

/**
 * RecurWP Coupon Field
 */
class RecurWPFieldCoupon extends RecurWPField {

    /**
     * Constructor 
     * 
     * @param   {number}    formId
     */
    constructor(formId: number) {

        /** Parent class constructor */
        super(formId);

        let __this = this;
        
        jQuery('#recurwp_coupon_container_' + this.formId + ' #recurwpCouponApply').on('click', function() {
            let couponCode = jQuery(this).siblings('input.recurwp_coupon_code').val();
            __this.apply(couponCode);
        });

        jQuery(document).on('click', '#recurwp_coupon_container_' + this.formId + ' #recurwpCouponRemove', function(e) {
            e.preventDefault();
            __this.remove();
        })
        
    }

    public apply( couponCode:string ) {

        let __this = this;

        //var couponCode = jQuery('#recurwp_coupon_code_' + formId).val();
        // Make sure coupon provided
        if (couponCode === 'undefined' || couponCode == '') {
            return;
        }

        var $applyButton = jQuery('#recurwp_coupon_container_' + this.formId + ' #recurwpCouponApply'),
        $inputField = jQuery('#recurwp_coupon_container_' + this.formId + ' #recurwp_coupon_code_' + this.formId);
        if ($applyButton.prop('disabled') || $inputField.prop('disabled') ) {
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

    public remove() {
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
    }



    public sanitize(couponCode:string) {
        var safeCouponCode = couponCode.replace(/[^A-Za-z0-9_-]+/g, '');
        return safeCouponCode;
    }

    /**
     * Show/Hide the spinner
     */
    public spinner( state:string = 'show' ) {
        var $spinner = jQuery('#recurwp_coupon_container_' + this.formId + ' #recurwp_coupon_spinner');
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
        var $applyButton = jQuery('#recurwp_coupon_container_' + this.formId + ' #recurwpCouponApply'),
        $inputField = jQuery('#recurwp_coupon_container_' + this.formId + ' #recurwp_coupon_code_' + this.formId);

        if (state == 'disable') {
            $applyButton.prop('disabled', true);
            $inputField.prop('disabled', true);
        } else {
            $applyButton.prop('disabled', false);
            $inputField.prop('disabled', false);
        }
    }

    public couponApplyResponse(isSuccessful:boolean = false, couponCode:string = '', discountValue:string = '') {
        
        var $couponInfo = jQuery('#recurwp_coupon_container_' + this.formId + ' #recurwp_coupon_info');
        var $couponError = jQuery('#recurwp_coupon_container_' + this.formId + ' #recurwp_coupon_error');
        // If correct coupon 
        if (isSuccessful) {
            var couponDetails = `
            <table class="recurwp_coupon_table">
                <tr>
                    <td><a href="#" class="recurwp_coupon_remove" id="recurwpCouponRemove" title="Remove Coupon">x</a> `+couponCode+`</td>
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
 * RECURWP PRODUCT FIELD
 */
class RecurWPFieldProduct extends RecurWPField {
    
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
            jQuery('#gform_'+__this.formId)
                .not('#gform_'+__this.formId+' :input.gform_hidden')
                .on('change', function(e:any) {
                    // e.preventDefault();
                    // e.stopPropagation();
                    __this.init();
            });
        });

        // Trigger form change event 
        //jQuery('#gform_'+__this.formId+' :input').trigger('change');
    }

    /**
     * Initialize
     */
    public init() {
        let visibleInstance = this.getVisibleInstance();
        if (visibleInstance) {
            var currentTotal = this.getInstanceValue(visibleInstance);
            this.total.set(currentTotal);
        } else {
            this.total.set(0);
        }
    }

    /**
     * Get all instances of a selector
     * 
     * @param   {string}    selector
     */
    public getInstances() {
        let i:any = document.querySelectorAll('.recurwp_product_container');
        return i;
    }

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
    public getVisibleInstance() {
        let instances = this.getInstances();
        for (let i of instances) {
            if (i.offsetParent != null) {
                return i;
            }
        }
    }

    /**
     * Get the value (plan_code) of an instance 
     */
    public getInstanceValue(instance:any) {
        var instanceChild = jQuery(instance).children('#recurwp_product_plan_price_' + this.formId);
        return instanceChild.val();
    }
}

/**
 * RecurWP main class
 */
class RecurWP {

    /** Form ID */
    public formId: number;

    /** RecurWPTotal Instance */
    public total:any;

    /** RecurWPFieldCoupon Instance */
    public couponField:any;

    /** RecurWPFieldProduct Instance */
    public productField:any;

    constructor(formId: number) {
        this.formId         = formId;
        this.total          = new RecurWPTotal(this.formId);
        this.couponField    = new RecurWPFieldCoupon(this.formId);
        this.productField   = new RecurWPFieldProduct(this.formId);
    }
}

jQuery(document).bind('gform_post_render', function(event:any, form_id:number){
    var recurwp = new RecurWP(form_id);
    jQuery('#gform_7').on('change', function() {
        // let instance = recurwp.productField.getVisibleInstance();
        // console.log(instance);
    });
   
    recurwp.productField.init();
});
