(function( $ ) {
	'use strict';

	/**
	 * All of the code for your checkout functionality placed here.
	 * should reside in this file.
	 */
	var debug = false;
	if (window.ApplePaySession) {
		if (ApplePaySession.canMakePayments) {
			setTimeout(function(){
				$( '#applePay' ).removeClass( 'hide-me' );
			},2000);
		}
	}

	function initApplePayment( apple_order, evt ) {
		var shippingOption = "";
		var shipping_total = 0;
		var cart_array         = [];
		var supported_networks = [];
		apple_vars.supported_networks.forEach(
			function (network) {
				supported_networks.push( network );
			}
		);

		var runningTotal = function(apple_order) { 
			var x                  = 0;
			var runningAmount      =  parseFloat( apple_order.grand_total );
			var subtotal           = apple_order.sub_total;
			var tax_total          = apple_order.tax_total;
			shipping_total     = apple_order.shipping_total;
			var discount_total     = apple_order.discount_total;

			cart_array[x++] = {type: 'final',label: 'Subtotal', amount: parseFloat( subtotal ).toFixed( 2 ) };
			cart_array[x++] = {type: 'final',label: 'Shipping fees', amount: parseFloat( shipping_total ).toFixed( 2 ) };

			if ( Math.abs(parseFloat( discount_total )) > 0 ) {
				cart_array[x++] = {type: 'final',label: 'Discount', amount: parseFloat( discount_total ).toFixed( 2 ) };
			}
			cart_array[x++] = {type: 'final',label: 'Tax', amount: parseFloat( tax_total ).toFixed( 2 ) };
			return  parseFloat( runningAmount).toFixed( 2 );
		}

		function getShippingOptions(){
			var shippingMethods     = [];
			var domesticlOption     = {label: 'Domestic Shipping', amount: getShippingCosts( 'domestic_std', true ), detail: '15-30 days', identifier: 'domestic_std'};
			var internationalOption = {label: 'International Shipping', amount: getShippingCosts( 'international', true ), detail: '5-10 days', identifier: 'international'};
			shippingMethods.push( domesticlOption );
			shippingMethods.push( internationalOption );
			return shippingMethods;
		}

		function getShippingCosts(shippingIdentifier, updateRunningPP ){

			var shippingCost = 0;

			switch (shippingIdentifier) {
				case 'domestic_std':
					shippingCost = 80;
			break;
				case 'domestic_exp':
					shippingCost = 0;
			break;
				case 'international':
					shippingCost = 10;
			break;
				default:
					shippingCost = 0;
			}

			if (updateRunningPP == true) {
				runningPP = shippingCost;
			}

			return shippingCost;

		}

		if ( shipping_total > 0) {
			var paymentRequest = {
				currencyCode: apple_vars.currency_code,
				countryCode: apple_vars.country_code,
				requiredShippingContactFields: ['name', 'email'],
				lineItems: cart_array,
				total: {
					label: apple_vars.display_name,
					amount: runningTotal(apple_order)
				},
				supportedNetworks: supported_networks,
				merchantCapabilities: [ 'supports3DS' ]
			};
		} else {
			var paymentRequest = {
				currencyCode: apple_vars.currency_code,
				countryCode: apple_vars.country_code,
				requiredShippingContactFields: ['postalAddress', 'name', 'email'],
				lineItems: cart_array,
				total: {
					label: apple_vars.display_name,
					amount: runningTotal(apple_order)
				},
				supportedNetworks: supported_networks,
				merchantCapabilities: [ 'supports3DS' ]
			};
		}
		var supported_networks_level = 3;
		if($.inArray('mada', supported_networks) != -1){
			supported_networks_level = 5;
		}
		var session = new ApplePaySession(supported_networks_level, paymentRequest );

		// Merchant Validation
		session.onvalidatemerchant = function (event) {
			var promise = performValidation( event.validationURL );
			promise.then(
				function (merchantSession) {
					session.completeMerchantValidation( merchantSession );
				}
			);
		}

		function performValidation(apple_url) {
			return new Promise(
				function(resolve, reject) {
					$.ajax(
						{
							url: apple_vars.validate_url,
							type: 'POST',
							data: {
								action: 'validate_apple_url',
								apple_url
							},
							success:function(data) {
								if ( ! data) {
									reject;
								} else {
									data = JSON.parse( data );
									resolve( data );
								}
							},
							error:function() {
								reject;
							}
						}
					)
				}
			);
		}

		session.onpaymentmethodselected = function(event) {
			var newTotal     = { type: 'final', label: apple_vars.display_name, amount: runningTotal(apple_order) };
			var newLineItems = cart_array;

			session.completePaymentMethodSelection( newTotal, newLineItems );

		}

		session.onshippingcontactselected = function(event) {
			var promise = validationShippingAddress( event.shippingContact );
			promise.then(
				function(data) {
					var status             = ApplePaySession.STATUS_SUCCESS;
					var newShippingMethods = [];
					apple_order = data;
					var finalTotal = {
						label: apple_vars.display_name,
						amount: runningTotal( data )
					};
					session.completeShippingContactSelection( status, newShippingMethods, finalTotal, cart_array );
				},
				function(error) {
					var zipAppleError = new ApplePayError( "shippingContactInvalid", "postalCode", "Invalid Address" );
					session.completeShippingContactSelection(
						{
							newShippingMethods: [],
							newTotal: { label: "error", amount: parseFloat(1).toFixed(2) , type: "pending" },
							newLineItems: [],
							errors: [zipAppleError],
						}
					);
				}
			);
		}

		function validationShippingAddress( address_obj ) {
			return new Promise(
				function(resolve, reject) {
					$.ajax(
						{
							url: apple_vars.validate_shipping_address,
							type: 'POST',
							dataType: 'json',
							data: {
								action: 'validate_shipping_address',
								address_obj
							},
							async: false,
							success:function(data) {
								if ( data.status === 'success' ) {
									resolve( data.apple_order );
								} else {
									reject( data.error_msg );
								}
							},
							error:function() {
								reject( 'Invalid Address' );
							}
						}
					);
				}
			);
		}

		session.onpaymentauthorized = function (event) {
			var promise = sendPaymentToken( event.payment.token );
			promise.then(
				function (success) {
					var status;
					if (success) {
						document.getElementById( "applePay" ).style.display = "none";
						if (event.payment.shippingContact) {
							var cart_promise = create_cart_order( event.payment.shippingContact );
							cart_promise.then(
								function(data) {
									status = ApplePaySession.STATUS_SUCCESS;
									sendPaymentToAps( event.payment.token );
								}
							);
						} else {
							var cart_promise = create_cart_order( [] );
							cart_promise.then(
								function(data) {
									status = ApplePaySession.STATUS_SUCCESS;
									sendPaymentToAps( event.payment.token );
								}
							);
						}
					} else {
						status = ApplePaySession.STATUS_FAILURE;
					}

					session.completePayment( status );
				}
			);
		}

		function create_cart_order( address_obj ) {
			return new Promise(
				function(resolve, reject) {
					$.ajax(
						{
							url: apple_vars.create_cart_order,
							type: 'POST',
							dataType: 'json',
							data: {
								action: 'create_cart_order',
								address_obj
							},
							async: false,
							success:function(data) {
								if ( data.status === 'success' ) {
									resolve();
								} else {
									reject( data.error_msg );
								}
							},
							error:function() {
								reject( 'error in creating order' );
							}
						}
					);
				}
			);
		}

		function sendPaymentToken(paymentToken) {
			return new Promise(
				function(resolve, reject) {
					resolve( true );
				}
			);
		}

		function sendPaymentToAps(data) {
			var formId = 'frm_aps_fort_apple_payment';
			if (jQuery( "#" + formId ).length > 0) {
				jQuery( "#" + formId ).remove();
			}

			$( '<form id="' + formId + '" action="#" method="POST"></form>' ).appendTo( 'body' );
			var response  = {};
			response.data = JSON.stringify( { "data" : data} );
			$.each(
				response,
				function (k, v) {
					$( '<input>' ).attr(
						{
							type: 'hidden',
							id: k,
							name: k,
							value: v
						}
					).appendTo( $( '#' + formId ) );
				}
			);

			$( '#' + formId + ' input[name=form_key]' ).attr( "disabled", "disabled" );

			$( '#' + formId ).attr( 'action', apple_vars.response_url );
			$( '#' + formId ).submit();
		}

		session.oncancel = function(event) {
			window.location.href = apple_vars.cancel_url;
		}

		session.begin();
	}
	$( document ).on(
		'click',
		'#applePay',
		function(evt) {
			$.ajax(
				{
					'url':'index.php?route=checkout/cart/add',
					'type': 'POST',
					'dataType': 'json',
					'data': $('#product input[type=\'text\'], #product input[type=\'hidden\'], #product input[type=\'radio\']:checked, #product input[type=\'checkbox\']:checked, #product select, #product textarea'),
					'async': false
				}
			).complete(
				function (json) {
					$('.alert-dismissible, .text-danger').remove();
					$('.form-group').removeClass('has-error');
					json = json.responseJSON;
					if (json['error']) {
						if (json['error']['option']) {
							for (i in json['error']['option']) {
								var element = $('#input-option' + i.replace('_', '-'));

								if (element.parent().hasClass('input-group')) {
									element.parent().after('<div class="text-danger">' + json['error']['option'][i] + '</div>');
								} else {
									element.after('<div class="text-danger">' + json['error']['option'][i] + '</div>');
								}
							}
						}

						if (json['error']['recurring']) {
							$('select[name=\'recurring_id\']').after('<div class="text-danger">' + json['error']['recurring'] + '</div>');
						}

						// Highlight any found errors
						$('.text-danger').parent().addClass('has-error');
					}

					if (json['success']) {
						$('#cart > button').html('<span id="cart-total"><i class="fa fa-shopping-cart"></i> ' + json['total'] + '</span>');
						$('#cart > ul').load('index.php?route=common/cart/info ul li');

						$.ajax(
							{
								'url': apple_vars.get_cart_data,
								'type': 'POST',
								'dataType': 'json',
								'data': {
									action: 'get_cart_data'
								},
								'async': false
							}
						).complete(
							function (response) {
								response = JSON.parse(response.responseText);
								if ( response.status === 'success' ) {
									initApplePayment( response.apple_order, evt );
								}
							}
						);
					}
				}
			);
		}
	);

})( jQuery );