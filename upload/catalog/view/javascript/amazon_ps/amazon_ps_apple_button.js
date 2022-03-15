(function( $ ) {
	'use strict';

	/**
	 * All of the code for your checkout functionality placed here.
	 * should reside in this file.
	 */
	var debug = false;
	if (window.ApplePaySession) {
		if (ApplePaySession.canMakePayments) {
		} else {
            $('input[name="payment_method"][value="amazon_ps_apple_pay"]').parents('div.radio').remove();
		}
	} else {
        $('input[name="payment_method"][value="amazon_ps_apple_pay"]').parents('div.radio').remove();
	}

})( jQuery );
