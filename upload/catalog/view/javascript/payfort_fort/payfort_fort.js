var payfortFort = (function () {
   return {
        validateCreditCard: function(element) {
            var isValid = false;
            var eleVal = $(element).val();
            eleVal = this.trimString(element.val());
            eleVal = eleVal.replace(/\s+/g, '');
            $(element).val(eleVal);
            $(element).validateCreditCard(function(result) {
                /*$('.log').html('Card type: ' + (result.card_type == null ? '-' : result.card_type.name)
                         + '<br>Valid: ' + result.valid
                         + '<br>Length valid: ' + result.length_valid
                         + '<br>Luhn valid: ' + result.luhn_valid);*/
                isValid = result.valid;
            });
            return isValid;
        },
        validateCardHolderName: function(element) {
            $(element).val(this.trimString(element.val()));
            var cardHolderName = $(element).val();
            if(cardHolderName.length > 255) {
                return false;
            }
            return true;
        },
        validateCvc: function(element) {
            $(element).val(this.trimString(element.val()));
            var cvc = $(element).val();
            if(cvc.length > 4 || cvc.length == 0) {
                return false;
            }
            if(!this.isPosInteger(cvc)) {
                return false;
            }
            return true;
        },
        translate: function(key, category, replacments) {
            if(!this.isDefined(category)) {
                category = 'payfort_fort';
            }
            var message = (arr_messages[category + '.' + key]) ? arr_messages[category + '.' + key] : key;
            if (this.isDefined(replacments)) {
                $.each(replacments, function (obj, callback) {
                    message = message.replace(obj, callback);
                });
            }
            return message;
        },
        isDefined: function(variable) {
            if (typeof (variable) === 'undefined' || typeof (variable) === null) {
                return false;
            }
            return true;
        },
        isTouchDevice: function() {
            return 'ontouchstart' in window        // works on most browsers 
                || navigator.maxTouchPoints;       // works on IE10/11 and Surface
        },
        trimString: function(str){
            return str.trim();
        },
        isPosInteger: function(data) {
            var objRegExp  = /(^\d*$)/;
            return objRegExp.test( data );
        }
   };
})();

var payfortFortMerchantPage2 = (function () {
    var merchantPage2FormId = 'frm_payfort_fort_payment';
    return {
        validateCcForm: function () {
            this.hideError();
            var isValid = payfortFort.validateCardHolderName($('#payfort_fort_card_holder_name'));
            if(!isValid) {
                this.showError(payfortFort.translate('error_invalid_card_holder_name'));
                return false;
            }
            isValid = payfortFort.validateCreditCard($('#payfort_fort_card_number'));
            if(!isValid) {
                this.showError(payfortFort.translate('error_invalid_card_number'));
                return false;
            }
            isValid = payfortFort.validateCvc($('#payfort_fort_card_security_code'));
            if(!isValid) {
                this.showError(payfortFort.translate('error_invalid_cvc_code'));
                return false;
            }
            var expDate = $('#payfort_fort_expiry_year').val()+''+$('#payfort_fort_expiry_month').val();
            $('#payfort_fort_expiry').val(expDate);
            return true;
        },
        showError: function(msg) {
            $('#payfort_fort_msg').html(msg);
            $('#payfort_fort_msg').show();
        },
        hideError: function() {
            $('#payfort_fort_msg').hide();
        }
    };
})();

var payfortFortMerchantPage = (function () {
    return {
        showMerchantPage: function(gatewayUrl) {
            if($("#payfort_merchant_page").size()) {
                $( "#payfort_merchant_page" ).remove();
            }
            $('<iframe  name="payfort_merchant_page" id="payfort_merchant_page"height="550px" frameborder="0" scrolling="no" onload="payfortFortMerchantPage.iframeLoaded(this)" style="display:none"></iframe>').appendTo('#pf_iframe_content');
            $('.pf-iframe-spin').show();
            $('.pf-iframe-close').hide();
            $( "#payfort_merchant_page" ).attr("src", gatewayUrl);
            $( "#payfort_payment_form" ).attr("action",gatewayUrl);
            $( "#payfort_payment_form" ).attr("target","payfort_merchant_page");
            $( "#payfort_payment_form" ).submit();
            //fix for touch devices
            if (payfortFort.isTouchDevice()) {
                setTimeout(function() {
                    $("html, body").animate({ scrollTop: 0 }, "slow");
                }, 1);
            }
            $( "#div-pf-iframe" ).show();
        },
        closePopup: function() {
            $( "#div-pf-iframe" ).hide();
            $( "#payfort_merchant_page" ).remove();
            window.location = 'index.php?route=payment/payfort_fort/merchantPageCancel';
        },
        iframeLoaded: function(){
            $('.pf-iframe-spin').hide();
            $('.pf-iframe-close').show();
            $('#payfort_merchant_page').show();
        },
    };
})();