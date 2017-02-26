// Declare global modules
declare var jQuery: any;
declare var gform: any;

class RecurWP {

    constructor() {
        // Set default price
        gform.addFilter('gform_product_total', function (total, formId) {
            jQuery('#recurwp_total_no_discount_' + formId).val(total)
            return total;
        }, 50);
    }

    public applyCoupon(formId: any) {

        let _this = this;

        // Get coupon code
        var couponCode = jQuery('#recurwp_coupon_code_' + formId).val();
        if (couponCode === 'undefined' || couponCode == '') {
            return;
        }

        var $applyButton = jQuery('#recurwp_coupon_container_' + formId + ' #recurwpCouponApply'),
        $inputField = jQuery('#recurwp_coupon_container_' + formId + ' #recurwp_coupon_code_' + formId);
        if ($applyButton.prop('disabled') || $inputField.prop('disabled') ) {
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
                'action':       'get_total_after_coupon',
                'couponCode':   safeCouponCode,
                'total':        parseInt(jQuery('#recurwp_total_no_discount_' + formId).val()),
                'formId':       formId
            }
        }).done(function(response: string) {
            console.log(response);
            var _response = JSON.parse(response);

            // if successful
            if (_response.is_success) {
                console.log(_response.meta);
                var newPrice = _response.meta.new_total,
                discountValue = _response.meta.discount_value;

                // update price
                _this.updateTotal(formId, newPrice);
                _this.couponApplyResponse(formId, true, couponCode, discountValue);
            } else {
                // Failed
                _this.couponApplyResponse(formId);
            }
            _this.couponSpinner(formId, 'hide');
            
        });
    }

    public removeCoupon(formId) {

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
    }

    public totalPreCoupon(formId, oldTotal) {
        window['gform_recurwp_pre_coupon_total_' + formId] = oldTotal;
    }

    /**
     * Show/Hide the spinner
     */
    public couponSpinner( formId:number, state:string = 'show' ) {
        var $spinner = jQuery('#recurwp_coupon_container_' + formId + ' #recurwp_coupon_spinner');
        if (state == 'show') {
            $spinner.show();
        } else {
            $spinner.hide();
        }
    }

    /**
     * Enable/Disable apply button
     */
    public couponFieldsDisable( formId, state:string = 'disable' ) {
        var $applyButton = jQuery('#recurwp_coupon_container_' + formId + ' #recurwpCouponApply'),
        $inputField = jQuery('#recurwp_coupon_container_' + formId + ' #recurwp_coupon_code_' + formId);

        // Disabled if a new price already exists
        var is_disabled = window['new_total_' + formId] == 0;

        if (state == 'disable') {
            $applyButton.prop('disabled', true);
            $inputField.prop('disabled', true);
        } else {
            $applyButton.prop('disabled', false);
            $inputField.prop('disabled', false);
        }
    }

    public couponApplyResponse(formId, isSuccessful:boolean = false, couponCode:string = '', discountValue:string = '') {
        
        var $couponInfo = jQuery('#recurwp_coupon_container_' + formId + ' #recurwp_coupon_info');
        var $couponError = jQuery('#recurwp_coupon_container_' + formId + ' #recurwp_coupon_error');
        // If correct coupon 
        if (isSuccessful) {
            var couponDetails = `
            <table class="recurwp_coupon_table">
                <tr>
                    <td><a href="javascript:void(0);" onclick="recurwp.removeCoupon(`+formId+`)" class="recurwp_coupon_remove" title="Remove Coupon">x</a> `+couponCode+`</td>
                    <td>`+discountValue+`</td>
                </tr>
            </table>
            `;
            $couponInfo.html(couponDetails);

            // Disable apply button
            this.couponFieldsDisable(formId);
        } else {
            $couponError.addClass('isVisible');
            setTimeout(function(){
                $couponError.removeClass('isVisible');
            },3000);
            this.couponFieldsDisable(formId, 'enable');
        }
    }

    /** 
     * Update form total
     */
    public updateTotal(formId, newTotal: Number) {
        gform.addFilter('gform_product_total', function (total, formId) {
            return newTotal;
        });
        window['new_total_' + formId] = newTotal;
        gformCalculateTotalPrice(formId);
    }
}

var recurwp = new RecurWP();
