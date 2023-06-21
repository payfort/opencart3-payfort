var apsPayment = (function () {
	return{
		standardCheckout: function( gatewayUrl, responseParams, redirect_url, payment_method ) {
			if ( ! ! redirect_url ) {
				setTimeout(function(){window.location.href = redirect_url},100);
				return;
			}
			var frame_selector = 'payfort_merchant_page';
			if($("#"+frame_selector).size()) {
				$( "#"+frame_selector ).remove();
			}
			
			$.each(
				responseParams,
				function(k, v){
					$( '<input>' ).attr(
						{
							type: 'hidden',
							id: k,
							name: k,
							value: v
						}
					).appendTo( '#payfort_payment_form');
				}
			);
			var iFrame         = '#div-pf-iframe';
			var iFrameContent  = 'pf_iframe_content';
			var paymentFormId  = '#payfort_payment_form';
			
			$( '<iframe name="' + frame_selector + '" id="' + frame_selector + '" height="650px" frameborder="0" scrolling="no" style="display:none" onload="apsPayment.iframeLoaded(this)" ></iframe>' ).appendTo( $(iFrame).find( '#' + iFrameContent ) );
			$(iFrame).find( '.pf-iframe-spin' ).show();
			$(iFrame).find( '.pf-iframe-close' ).hide();
			$(iFrame).find( '#' + frame_selector ).attr( "src", gatewayUrl );
			
			$( paymentFormId ).attr("action",gatewayUrl);
			$( paymentFormId ).attr( "target",frame_selector );
			$( paymentFormId ).submit();
			
			$(iFrame).show();
		},
		hostedCheckout: function( gatewayUrl, responseParams, redirect_url, payment_method ) {
			if ( ! ! redirect_url ) {
				setTimeout(function(){window.location.href = redirect_url},100);
			}
			if (null === payment_method || undefined === payment_method ) {
				payment_method = 'amazon_ps';
			}
			var payment_box = $("#"+$.trim(payment_method)+"_form");
			$('<form id="amazon_ps_'+$.trim(payment_method)+'" action="' + gatewayUrl + '" method="POST"><input type="submit"/></form>' ).appendTo( 'body' );
			var formParams = responseParams;
			if ( 'amazon_ps' === payment_method || 'amazon_ps_installments' === payment_method ) {
				if (payment_box.find( '.aps_token_radio:checked' ).val() == "")
				{
					formParams.card_number        = payment_box.find( '.aps_card_number' ).val().trim();
					formParams.card_holder_name   = payment_box.find( '.aps_card_holder_name' ).val();
					formParams.expiry_date        = payment_box.find( '.aps_expiry_year' ).val() + payment_box.find( '.aps_expiry_month' ).val();
					formParams.card_security_code = payment_box.find( '.aps_card_security_code' ).val();
					if(!(formParams.remember_me)){
						if (payment_box.find( '.aps_card_remember_me' ).is( ':checked' ))
						{
							formParams.remember_me = "YES";
						}
						else{
							formParams.remember_me = "NO";
						}
					}
				}
				else {
					formParams.token_name        	= payment_box.find( '.aps-radio:checked' ).val();
					formParams.card_bin        	    = payment_box.find( '.aps-radio:checked' ).data("cardbin");
					formParams.card_security_code   = payment_box.find( '.aps_card_security_code' ).val();
				}
			}

			$.each(
				formParams,
				function(k, v){
					$( '<input>' ).attr(
						{
							type: 'hidden',
							id: k,
							name: k,
							value: v
						}
					).appendTo('#amazon_ps_'+$.trim(payment_method));
				}
			);
			$('#amazon_ps_'+$.trim(payment_method)).submit();
		},
		closePopup: function(payment_method) {
			$( "#div-pf-iframe" ).hide();
			$( "#payfort_merchant_page" ).remove();
			window.location = 'index.php?route=extension/payment/'+payment_method+'/merchantPageCancel';
		},
		iframeLoaded: function(){
			$('.pf-iframe-spin').hide();
			$('.pf-iframe-close').show();
			$('#payfort_merchant_page').show();
		},
		validatePayment: function ( payment_method ) {
			var status = true;
			if ( payment_method ) {
				var payment_box        = $( '#'+payment_method+'_form');
				var card_value         = payment_box.find( ".aps_card_number" ).val();
				var holdername_value   = payment_box.find( ".aps_card_holder_name" ).val();
				var cvv_value          = payment_box.find( ".aps_card_security_code" ).val();
				var expiry_month       = payment_box.find( ".aps_expiry_month" ).val();
				var expiry_year        = payment_box.find( ".aps_expiry_year" ).val();
				
				/*var validateCardCVV    = APSValidation.validateCVV( cvv_value );
				if ( validateCardCVV.validity === false ) {
					status = false;
					payment_box.find( ".aps_card_cvv_error" ).html( validateCardCVV.msg );
				} else {
					payment_box.find( ".aps_card_cvv_error" ).html( '' );
				}
				var usingToken         = payment_box.find( ".aps-radio:checked" ).val();*/

				//if (usingToken == "")
				if ( $( '.aps_hosted_form' ).is( ':visible' ) ) 
				{
					var validateCard       = APSValidation.validateCard( card_value );
					var validateHolderName = APSValidation.validateHolderName( holdername_value );
					var validateExpiry     = APSValidation.validateCardExpiry( expiry_month, expiry_year );
					var validateCardCVV    = APSValidation.validateSavedCVV( cvv_value, payment_box.find( ".aps_card_security_code" ).attr( 'maxlength' ) );

					if ( validateCard.validity === false ) {
						payment_box.find( ".aps_card_error" ).html( validateCard.msg );
						status = false;
					} else {
						if ( ! $( '#amazon_ps_installments_form .aps_card_error' ).hasClass( 'installment_error' ) ) {
							payment_box.find( ".aps_card_error" ).html( '' );
						}
					}
					if ( validateHolderName.validity === false ) {
						payment_box.find( ".aps_card_name_error" ).html( validateHolderName.msg );
						status = false;
						} else {
						payment_box.find( ".aps_card_name_error" ).html( '' );
					}
					if ( validateCardCVV.validity === false ) {
						status = false;
						payment_box.find( ".aps_card_cvv_error" ).html( validateCardCVV.msg );
					} else {
						payment_box.find( ".aps_card_cvv_error" ).html( '' );
					}

					if ( validateExpiry.validity === false ) {
						payment_box.find( ".aps_card_expiry_error" ).html( validateExpiry.msg );
						status = false;
						} else {
						payment_box.find( ".aps_card_expiry_error" ).html( '' );
					}
				}
				
				if( 'amazon_ps_installments' === payment_method ) {
					if( $( '.emi_box.selected' ).length >= 1 ) {
						$( "#installment_plans .aps_plan_error" ).html( '' );
						} else {
						if( $.trim( $('#installment_plans .plans .emi_box').html() ).length ) {
							$( "#installment_plans .aps_plan_error" ).html( APSValidation.translate('required_field') );
							status = false;
						}
					}
					if (!$('#installment_plans #installment_term').is(':checked')) {
						$( '#installment_plans .aps_installment_terms_error' ).html(  APSValidation.translate('required_field')  );
						status = false;
						} else {
						$( '#installment_plans .aps_installment_terms_error' ).html('');
					}
				}else if ( 'amazon_ps' === payment_method ) {
					// check emi & procedded with full payment exist
					if ( $( '#em_installment_plans .emi_box' ).attr( 'data-full-payment' ) == '1' ) {
						if ( payment_box.find( '.emi_box.selected' ).length >= 1 ) {
							payment_box.find( ".aps_plan_error" ).html( '' );
						} else {
							payment_box.find( ".aps_plan_error" ).html( APSValidation.translate('required_field') );
							status = false;
						}
						if(! $( '#em_installment_plans .emi_box.selected' ).attr( 'data-full-payment' ) == '1' ){
							if ( ! payment_box.find( 'input[name="installment_term"]' ).is( ':checked' ) ) {
								payment_box.find( ".aps_installment_terms_error" ).html( APSValidation.translate('required_field') );
								status = false;
							} 
						}else {
							payment_box.find( ".aps_installment_terms_error" ).html( '' );
						}
					}
				}
			}
			return status;
		},
		valuOtpVerifyBox: function ( response ) {
			$( '.otp_generation_msg' ).html( response.message );
			$( '.valu_form.active' ).slideUp().removeClass( 'active' );
			$( '#verfiy_otp_sec' ).slideDown().addClass( 'active' );
		},
		valuTenureBox: function( response ) {
			//$( '.valu_form.active' ).slideUp().removeClass( 'active' );
			$( '#tenure_sec' ).slideDown().addClass( 'active' );
			$( '#tenure_sec .tenure' ).html( response.tenure_html );
			$( '#tenure_sec .tenure .tenure_carousel' ).slick(
				{
					dots: false,
					infinite: false,
					slidesToShow: 3,
					slidesToScroll: 1,
					rtl: $( 'body' ).hasClass( 'rtl' ) ? true : false,
					arrows: true,
					prevArrow: '<i class="fa fa-chevron-left tenure-carousel-left-arr"></i>',
					nextArrow: '<i class="fa fa-chevron-right tenure-carousel-right-arr"></i>'
				}
			);
		}
	};
})();


//Validation control
var APSValidation = {
	validateCard: function ( card_number ) {
		var card_type     = "";
		var card_validity = true;
		var message       = '';
		var card_length   = 0;
		if ( card_number ) {
			card_number = card_number.replace( / /g,'' ).replace( /-/g,'' );
			// Visa
			var visa_regex = new RegExp( '^4[0-9]{0,15}$' );
			
			// MasterCard
			var mastercard_regex = new RegExp( '^5$|^5[0-5][0-9]{0,16}$' );
			
			// American Express
			var amex_regex = new RegExp( '^3$|^3[47][0-9]{0,13}$' );
			
			//mada
			var mada_regex = new RegExp( '/^' + mada_bins + '/', 'm' );
			
			//meeza
			var meeza_regex = new RegExp( meeza_bins, 'gm' );
			
			if ( card_number.match( mada_regex ) ) {
				if ( has_recurring_products != '0') {
					card_validity = false;
					message       = APSValidation.translate('invalid_card');
				} else {
					card_length = 19;
				}
				card_type   = 'mada';
			} else if ( card_number.match( meeza_regex ) ) {
				if ( has_recurring_products != '0') {
					card_validity = false;
					message       = APSValidation.translate('invalid_card');
				} else {
					card_length = 19;
				}
				card_type   = 'meeza';
			} else if( card_number.match( visa_regex ) ) {
				card_type = 'visa';
				card_length = 16;
			} else if ( card_number.match( mastercard_regex ) ) {
				card_type = 'mastercard';
				card_length = 16;
			} else if ( card_number.match( amex_regex ) ) {
				card_type = 'amex';
				card_length = 15;
			} else {
				card_validity = false;
				message       = APSValidation.translate('invalid_card');
			}
			
			if ( card_number.length < 15 ) {
				card_validity = false;
				message       = APSValidation.translate('invalid_card_length');
			}
		} else {
			message       = APSValidation.translate('card_empty');
			card_validity = false;
		}
		return {
			card_type,
			validity: card_validity,
			msg: message,
			card_length
		}
	},
	validateHolderName: function ( card_holder_name ) {
		var validity     = true;
		var message      = '';
		card_holder_name = card_holder_name.trim();
		if (card_holder_name.length > 255 ) {
			validity = false;
			message  = APSValidation.translate('invalid_card_holder_name');
		}
		return {
			validity,
			msg: message
		}
	},
	validateCVV: function( card_cvv,  ) {
		var validity = true;
		var message  = '';
		card_cvv     = card_cvv.trim();
		if (card_cvv.length > 4 || card_cvv.length == 0) {
			validity = false;
			message  = APSValidation.translate('invalid_card_cvv');
		}
		return {
			validity,
			msg: message
		}
	},
	validateSavedCVV: function( card_cvv, length ) {
		var validity = true;
		var message  = '';
		card_cvv     = card_cvv.trim();
		if ( card_cvv.length != length || card_cvv.length == 0 || card_cvv == '000' ) {
			validity = false;
			message  = APSValidation.translate('invalid_card_cvv');
		}
		return {
			validity,
			msg: message
		}
	},
	validateCardExpiry: function( card_expiry_month, card_expiry_year ) {
		var validity = true;
		var message  = '';
		if ( card_expiry_month === '' || ! card_expiry_month ) {
			validity = false;
			message  = APSValidation.translate('invalid_expiry_month');
		} else if ( card_expiry_year === '' || ! card_expiry_year ) {
			validity = false;
			message  = APSValidation.translate('invalid_expiry_year');;
		} else if ( parseInt( card_expiry_month ) <= 0 || parseInt( card_expiry_month ) > 12  ) {
			validity = false;
			message  = APSValidation.translate('invalid_expiry_month');
		} else {
			var cur_date, exp_date;
			card_expiry_month = ('0' + parseInt( card_expiry_month - 1 )).slice( -2 );
			cur_date          = new Date();
			exp_date          = new Date( parseInt( '20' + card_expiry_year ), card_expiry_month, 30 );
			if (exp_date.getTime() < cur_date.getTime()) {
				message  = APSValidation.translate('invalid_expiry_date');
				validity = false;
			}
		}
		return {
			validity,
			msg: message
		}
	},
	validateHostedSavedCVV: function(){
		if ( $( '.aps-radio' ).is( ':checked' ) ) {
			var aps_cvv = $( '.aps-radio:checked' ).parents( '.aps_token_row' ).find( '.aps_saved_card_security_code' );
			if ( ! APSValidation.validateSavedCVV( aps_cvv.val(), aps_cvv.attr( 'maxlength' ) ).validity ) {
				$( '.field-error' ).removeClass( 'field-error' );
				aps_cvv.addClass( 'field-error' );
				$( 'html, body' ).animate(
					{
						scrollTop: $( '.field-error' ).offset().top - 50
					},
					1000
				);
				return false;
			} else {
				$('.aps_hosted_form .aps_card_security_code').val(aps_cvv.val().trim());
				aps_cvv.removeClass( 'field-error' );
			}
		}
		return true;
	},
	translate: function(key, category) {
		if(!this.isDefined(category)) {
			category = 'amazon_ps';
		}
		var message = (amazon_ps_error_js_msg[category + '.' + key]) ? amazon_ps_error_js_msg[category + '.' + key] : key;
		return message;
	},
	isDefined: function(variable) {
		if (typeof (variable) === 'undefined' || typeof (variable) === null) {
			return false;
		}
		return true;
	},
	filterInput: function(user_input) {
		user_input = $.trim(user_input);
		return user_input.replace(/[^a-z0-9 ]/gi, '');
	}
}
var AmazonPSCall = {
	makePayment: function (payment_method, payment_integration_type) {
		var isValid = true;
		if ( 'amazon_ps_valu' === payment_method ) {
			
			if ( $( '.tenureBox.selected' ).length === 1 ) {
				$( ".valu_process_error" ).html( "" );
			} else {
				if($(" #tenure_sec .tenure").children().length===0){
					$( ".valu_process_error" ).html( APSValidation.translate('valu_pending_msg') );
				}else{
					$( ".valu_process_error" ).html( APSValidation.translate('valu_select_plan') );
				}
				$( '#div-aps-loader' ).hide();
				return false;
			}
			
			if ( !($( "#valu_terms" ).is( ':checked' ))) {
				$( ".tenure_term_error" ).html( APSValidation.translate('valu_terms_msg') );
				$( '#div-aps-loader' ).hide();
				return false;
			}
			$( '#div-aps-loader' ).show();
		}
		
		if('hosted_checkout' == payment_integration_type){
			isValid = apsPayment.validatePayment(payment_method);
			if(!APSValidation.validateHostedSavedCVV()){
				return false;
			}
		}
		if (isValid) {
			if('standard_checkout' == payment_integration_type){
				if ( $( '.aps-radio' ).is( ':checked' ) ) {
					var aps_cvv = $( '.aps-radio:checked' ).parents( '.aps_token_row' ).find( '.aps_saved_card_security_code' );
					if ( ! APSValidation.validateSavedCVV( aps_cvv.val(), aps_cvv.attr( 'maxlength' ) ).validity ) {
						$( '.field-error' ).removeClass( 'field-error' );
						aps_cvv.addClass( 'field-error' );
						$( 'html, body' ).animate(
							{
								scrollTop: $( '.field-error' ).offset().top - 50
							},
							1000
						);
						return false;
					} else {
						aps_cvv.removeClass( 'field-error' );
					}
				}
				var aps_input_token_cc        = APSValidation.filterInput($('input[name=aps_payment_token_cc]:checked').val());
				var aps_input_saved_card_code = APSValidation.filterInput($('input[name=aps_saved_card_security_code]').val());
				var aps_input_token_cc        = APSValidation.filterInput($('input[name=aps_payment_token_cc]:checked').data('cardbin'));
				var fdama = `aps_payment_token_cc=${aps_input_token_cc}&aps_card_security_code=${aps_input_saved_card_code}&aps_payment_card_bin_cc=${aps_input_token_cc}`;

			}
			else{
				// filter card number from request
				var fdama = $("#frm_payfort_fort_payment :input")
				    .filter(function(index, element) {
						return $(element).attr('id') != 'aps_card_number';
				    })
				    .serialize();
			}
			$.ajax({
				url: 'index.php?route=extension/payment/'+payment_method+'/send',
				type: 'post',
				data: fdama,
				//async: false,
				beforeSend: function () {
					$('#button-confirm').attr('disabled', true);
				},
				success: function (json) {
					json = JSON.parse(json);
					if (json['error']) {
						$('#button-payment-method').button('reset');
						$('#installment_plans .plans').append('<div class="alert alert-danger alert-dismissible">' + json['error_message'] + '<button type="button" class="close" data-dismiss="alert">&times;</button></div>');
					}
					else if (json) {
						if('standard_checkout' == payment_integration_type){
							if(json.params == '' && json.redirect_url == ''){
								//alert(text_general_error);
								window.location.reload();
								return false;
							}
							apsPayment.standardCheckout( json.url, json.params, json.redirect_url, payment_method );
						} else if ('hosted_checkout' == payment_integration_type ){
							apsPayment.hostedCheckout( json.url, json.params, json.redirect_url, payment_method );
						}else if('amazon_ps_valu' === payment_method ) {
							window.location.href = json.redirect_link;
						}
					}
					
					else{
						alert(text_general_error);
					}                       
					$( '#div-aps-loader' ).hide();
				},
				error: function(xhr, ajaxOptions, thrownError) {
					$( '#div-aps-loader' ).hide();
					alert(thrownError + "\r\n" + xhr.statusText + "\r\n");
				}
			});
		}
	}
}

$( document.body ).on(
	'blur',
	'#amazon_ps_installments_form .aps_card_number',
	function(e) {
		var ajaxurl    = 'index.php?route=extension/payment/amazon_ps_installments/getInstallmentPlans';
		var cardnumber = $( this ).val();
		$( '#installment_plans .plans' ).html( '' );
		$( '#installment_plans .plan_info' ).html( '' );
		$( '#installment_plans .issuer_info' ).html( '' );
		$( '#aps_installment_confirmation_en' ).val( '' );
		$( '#aps_installment_confirmation_ar' ).val( '' );
		$( '#aps_installment_plan_code' ).val( '' );
		$( '#aps_installment_issuer_code' ).val( '' );
		$( '#aps_installment_interest' ).val( '' );
		$( '#aps_installment_amount' ).val( '' );
		if ( cardnumber.length >= 15 ) {
			card_bin = cardnumber.substring( 0,6 );
			$( '#div-aps-loader' ).show();
			$.ajax(
				{
					url:ajaxurl,
					data:{
						card_bin,
					},
					type:'POST',
					success:function( response ) {
						$( '#div-aps-loader' ).hide();
						response = JSON.parse( response );
						if ( 'success' === response.status ) {
							$( '#amazon_ps_installments_form .aps_card_error.installment_error' ).removeClass( 'installment_error' );
							$( '#amazon_ps_installments_form .aps_card_error' ).html( "" );
							$( '#installment_plans .plans' ).html( response.plans_html );
							$( '#installment_plans .plan_info' ).html( response.plan_info );
							$( '#installment_plans .issuer_info' ).html( response.issuer_info );
							$( '#installment_plans .plans .emi_carousel' ).slick(
								{
									dots: false,
									infinite: false,
									slidesToShow: 3,
									slidesToScroll: 1,
									rtl: $( 'body' ).hasClass( 'rtl' ) ? true : false,
									arrows: true,
									prevArrow: '<i class="fa fa-chevron-left emi-carousel-left-arr"></i>',
									nextArrow: '<i class="fa fa-chevron-right emi-carousel-right-arr"></i>'
								}
							);
							$( '#aps_installment_confirmation_en' ).val( response.confirmation_en );
							$( '#aps_installment_confirmation_ar' ).val( response.confirmation_ar );
						} else {
							$( '#amazon_ps_installments_form .aps_card_error' ).addClass( 'installment_error' );
							$( '#amazon_ps_installments_form .aps_card_error' ).html( response.message );
							$( '#installment_plans .plans' ).html( response.plans_html );
							$( '#installment_plans .plan_info' ).html( response.plan_info );
						}
					}
				}
			);
		}
	}
);

$( document.body ).on(
	'blur',
	'#amazon_ps_installments_form .aps_saved_card_security_code',
	function(e) {
		var ajaxurl    = 'index.php?route=extension/payment/amazon_ps_installments/getInstallmentPlans';
		var cvv      = $( this ).val();
		var ele      = $( this );
		var card_bin = $( this ).parents( '.aps_token_row' ).find( '.aps-radio:checked' ).attr("data-cardbin");
		$( '#installment_plans .plans' ).html( '' );
		$( '#installment_plans .plan_info' ).html( '' );
		$( '#installment_plans .issuer_info' ).html( '' );
		$( '#aps_installment_confirmation_en' ).val( '' );
		$( '#aps_installment_confirmation_ar' ).val( '' );
		if ( card_bin.length >= 6 && cvv.length >= 3 ) {
			$( 'div.aps_token_group' ).find( '.aps_install_token_error' ).html( "" );
			$( '#div-aps-loader' ).show();
			$.ajax(
				{
					url:ajaxurl,
					data:{
						card_bin,
					},
					type:'POST',
					success:function( response ) {
						$( '#div-aps-loader' ).hide();
						response = JSON.parse( response );
						if ( 'success' === response.status ) {
							ele.parents( 'div.aps_token_group' ).find( '.aps_install_token_error' ).html( "" );
							$( '#amazon_ps_installments_form .aps_card_error' ).html( "" );
							$( '#installment_plans .plans' ).html( response.plans_html );
							$( '#installment_plans .plan_info' ).html( response.plan_info );
							$( '#installment_plans .issuer_info' ).html( response.issuer_info );
							$( '#installment_plans .plans .emi_carousel' ).slick(
								{
									dots: false,
									infinite: false,
									slidesToShow: 3,
									slidesToScroll: 1,
									rtl: $( 'body' ).hasClass( 'rtl' ) ? true : false,
									arrows: true,
									prevArrow: '<i class="fa fa-chevron-left emi-carousel-left-arr"></i>',
									nextArrow: '<i class="fa fa-chevron-right emi-carousel-right-arr"></i>'
								}
							);
							$( '#aps_installment_confirmation_en' ).val( response.confirmation_en );
							$( '#aps_installment_confirmation_ar' ).val( response.confirmation_ar );
						} else {
							ele.parents( 'div.aps_token_group' ).find( '.aps_install_token_error' ).html( response.message );
						}
					}
				}
			);
		}
	}
);

$( document.body ).on(
	'keyup',
	'#amazon_ps_form .aps_card_number',
	function(e) {
		var cardnumber = $( this ).val().trim();
		$( '.card-icon.active' ).removeClass( 'active' );
		if ( cardnumber.length >= 4 ) {
			$( '#amazon_ps_form .aps_card_error' ).html( '' );
			var validateCard = APSValidation.validateCard( cardnumber );
			if ( validateCard.card_type ) {
				$( '.card-' + validateCard.card_type + '.card-icon' ).addClass( 'active' );
				if ( 'amex' === validateCard.card_type ) {
					$( this ).parents( '#amazon_ps_form' ).find( '.aps_card_security_code' ).attr( 'maxlength', 4 );
				} else {
					$( this ).parents( '#amazon_ps_form' ).find( '.aps_card_security_code' ).attr( 'maxlength', 3 );
				}
				if ( validateCard.card_length >= 1 ) {
					$( this ).attr( 'maxlength', validateCard.card_length );
				}
				if ( validateCard.validity === true ) {
					$( '#amazon_ps_form .aps_card_error' ).html( '' );
				}
			}
		}
	}
);

$( document.body ).on(
	'keyup',
	'#amazon_ps_installments_form .aps_card_number',
	function(e) {
		var cardnumber = $( this ).val().trim();
		$( '.card-icon.active' ).removeClass( 'active' );
		if ( cardnumber.length >= 4 ) {
			$( '#amazon_ps_installments_form .aps_card_error' ).html( "" );
			var validateCard = APSValidation.validateCard( cardnumber );
			if ( validateCard.card_type ) {
				$( '.card-' + validateCard.card_type + '.card-icon' ).addClass( 'active' );
				
				if ( 'amex' === validateCard.card_type ) {
					$( this ).parents( '#amazon_ps_installments_form' ).find( '.aps_card_security_code' ).attr( 'maxlength', 4 );
				} else {
					$( this ).parents( '#amazon_ps_installments_form' ).find( '.aps_card_security_code' ).attr( 'maxlength', 3 );
				}
				if ( validateCard.card_length >= 1 ) {
					$( this ).attr( 'maxlength', validateCard.card_length );
				}
				if ( validateCard.validity === true ) {
					if ( ! $( '#amazon_ps_installments_form .aps_card_error' ).hasClass( 'installment_error' ) ) {
						$( '#amazon_ps_installments_form .aps_card_error' ).html( '' );
					}
				}
			}
		}
	}
);


/* Embedded hosted checkout
 * Credit card with installment in hosted integration
*/
$( document.body ).on(
	'blur',
	'#amazon_ps_form .aps_card_number',
	function(e) {
		if(embedded_hosted_checkout === '1'){
			var ajaxurl    = 'index.php?route=extension/payment/amazon_ps_installments/getInstallmentPlans';
			var cardnumber = $( this ).val();
			$( '#em_installment_plans .plans' ).html( '' );
			$( '#em_installment_plans .plan_info' ).html( '' );
			$( '#em_installment_plans .issuer_info' ).html( '' );
			$( '#aps_em_installment_confirmation_en' ).val( '' );
			$( '#aps_em_installment_confirmation_ar' ).val( '' );
			$( '#aps_em_installment_plan_code' ).val( '' );
			$( '#aps_em_installment_issuer_code' ).val( '' );
			$( '#aps_em_installment_interest' ).val( '' );
			$( '#aps_em_installment_amount' ).val( '' );
			if ( cardnumber.length >= 15 ) {
				card_bin = cardnumber.substring( 0,6 );
				$( '#div-aps-loader' ).show();
				$.ajax(
					{
						url:ajaxurl,
						data:{
							card_bin,
							embedded_hosted_checkout :1,
						},
						type:'POST',
						success:function( response ) {
							$( '#div-aps-loader' ).hide();
							response = JSON.parse( response );
							if ( 'success' === response.status ) {
								$( '#amazon_ps_form .aps_card_error.installment_error' ).removeClass( 'installment_error' );
								$( '#amazon_ps_form .aps_card_error' ).html( "" );
								$( '#em_installment_plans .plans' ).html( response.plans_html );
								$( '#em_installment_plans .plan_info' ).html( response.plan_info );
								$( '#em_installment_plans .issuer_info' ).html( response.issuer_info );
								$( '#em_installment_plans .plans .emi_carousel' ).slick(
									{
										dots: false,
										infinite: false,
										slidesToShow: 3,
										slidesToScroll: 1,
										rtl: $( 'body' ).hasClass( 'rtl' ) ? true : false,
										arrows: true,
										prevArrow: '<i class="fa fa-chevron-left emi-carousel-left-arr"></i>',
										nextArrow: '<i class="fa fa-chevron-right emi-carousel-right-arr"></i>'
									}
								);
								$( '#aps_em_installment_confirmation_en' ).val( response.confirmation_en );
								$( '#aps_em_installment_confirmation_ar' ).val( response.confirmation_ar );
								$( '#em_installment_plans' ).addClass( 'active' );
								$( ".with_full_payment" ).parents(".emi_box").height($(".emi_box:not([data-full-payment])").height());
							} else {
								$( '#em_installment_plans' ).removeClass( 'active' );
							}
						}
					}
				);
			}
		}
	}
);

$( document.body ).on(
	'blur',
	'#amazon_ps_form .aps_saved_card_security_code',
	function(e) {
		if(embedded_hosted_checkout === '1'){
			var ajaxurl    = 'index.php?route=extension/payment/amazon_ps_installments/getInstallmentPlans';
			var cvv      = $( this ).val();
			var ele      = $( this );
			var card_bin = $( this ).parents( '.aps_token_row' ).find( '.aps-radio:checked' ).attr("data-cardbin");
			$( '#em_installment_plans .plans' ).html( '' );
			$( '#em_installment_plans .plan_info' ).html( '' );
			$( '#em_installment_plans .issuer_info' ).html( '' );
			$( '#aps_em_installment_confirmation_en' ).val( '' );
			$( '#aps_em_installment_confirmation_ar' ).val( '' );
			$( '#aps_em_installment_plan_code' ).val( '' );
			$( '#aps_em_installment_issuer_code' ).val( '' );
			$( '#aps_em_installment_interest' ).val( '' );
			$( '#aps_em_installment_amount' ).val( '' );
			if ( card_bin.length >= 6 && cvv.length >= 3 ) {
				$( 'div.aps_token_group' ).find( '.aps_install_token_error' ).html( "" );
				$( '#div-aps-loader' ).show();
				$.ajax(
					{
						url:ajaxurl,
						data:{
							card_bin,
							embedded_hosted_checkout :1,
						},
						type:'POST',
						success:function( response ) {
							$( '#div-aps-loader' ).hide();
							response = JSON.parse( response );
							if ( 'success' === response.status ) {
								ele.parents( 'div.aps_token_group' ).find( '.aps_install_token_error' ).html( "" );
								$( '#amazon_ps_form .aps_card_error' ).html( "" );
								$( '#em_installment_plans .plans' ).html( response.plans_html );
								$( '#em_installment_plans .plan_info' ).html( response.plan_info );
								$( '#em_installment_plans .issuer_info' ).html( response.issuer_info );
								$( '#em_installment_plans .plans .emi_carousel' ).slick(
									{
										dots: false,
										infinite: false,
										slidesToShow: 3,
										slidesToScroll: 1,
										rtl: $( 'body' ).hasClass( 'rtl' ) ? true : false,
										arrows: true,
										prevArrow: '<i class="fa fa-chevron-left emi-carousel-left-arr"></i>',
										nextArrow: '<i class="fa fa-chevron-right emi-carousel-right-arr"></i>'
									}
								);
								$( '#aps_em_installment_confirmation_en' ).val( response.confirmation_en );
								$( '#aps_em_installment_confirmation_ar' ).val( response.confirmation_ar );
								$( '#em_installment_plans' ).addClass( 'active' );
								$( ".with_full_payment").parents(".emi_box").height($(".emi_box:not([data-full-payment])").height());
							} else {
								$( '#em_installment_plans' ).removeClass( 'active' );
							}
						}
					}
				);
			}
		}
	}
);
/*Embedded hosted checkout end*/

$(document).ready(function(){
	$(window).resize(function(){
		clearTimeout(window.resizedFinished);
		window.resizedFinished = setTimeout(function(){
		if ( $( '.with_full_payment' ).length >= 1 ) {
				$(".with_full_payment").parents(".emi_box").height($(".emi_box:not([data-full-payment])").height());
			}
		}, 250);
  });
});

$( document.body ).on(
	'keypress',
	'.onlynum',
	function(e) {
		var key = e.which || e.keyCode;
		if ( key >= 48 && key <= 57 ) {
			return true;
		}
		return false;
	}
);

$( document.body ).on(
	'keypress',
	'.aps_card_holder_name',
	function(e) {
		var key = e.which || e.keyCode;
		if ( ( key >= 65 && key <= 90 ) || ( key >= 97 && key <= 122 ) || key == 32 ) {
			return true;
		}
		return false;
	}
);
$( document.body ).on(
	'paste',
	'.aps_card_number',
	function(e) {
		return false;
	}
);

$( document.body ).on(
	'click',
	'.emi_box',
	function(e) {
		$( '.emi_box.selected' ).removeClass( 'selected' );
		$( this ).addClass( 'selected' );
		var plan_code   = $( this ).attr( 'data-plan-code' );
		var issuer_code = $( this ).attr( 'data-issuer-code' );
		var interest_text = $( this ).attr( 'data-interest' );
		var interest_amount = $( this ).attr( 'data-amount' );
		var plan_type       = $( this ).parents( '.plan_box' ).attr( 'id' );
		if(plan_type == 'em_installment_plans'){
			$( '#aps_em_installment_plan_code' ).val( plan_code );
			$( '#aps_em_installment_issuer_code' ).val( issuer_code );
			$( '#aps_em_installment_interest' ).val( interest_text );
			$( '#aps_em_installment_amount' ).val( interest_amount );
			if($( this ).attr( 'data-full-payment' ) == '1'){
				if(!$('#em_installment_plans .plan_info').hasClass('validation-off')){
					$('#em_installment_plans .plan_info').addClass('validation-off');
				}
			}else{
				$('#em_installment_plans .plan_info').removeClass('validation-off');
			}
		}else{
			$( '#aps_installment_plan_code' ).val( plan_code );
			$( '#aps_installment_issuer_code' ).val( issuer_code );
			$( '#aps_installment_interest' ).val( interest_text );
			$( '#aps_installment_amount' ).val( interest_amount );
		}
		$( '.aps_plan_error').html( '' );
	}
);

$( document.body ).on(
	'click',
	'.valu_customer_verify',
	function(e) {
		var ajaxurl       = 'index.php?route=extension/payment/amazon_ps_valu/valuCustomerVerify';
		var mobile_number = $( '.aps_valu_mob_number' ).val().trim();
		var down_payment = $( '.aps_valu_down_payment' ).val();
		var tou = $( '.aps_valu_tou' ).val();
		var cashback = $( '.aps_valu_cashback' ).val();
		$( ".valu_process_error" ).html( "" );
		if(mobile_number.length == 0){
			$( ".valu_process_error" ).html( APSValidation.translate('required_field') );
			} else if (mobile_number.length >= 11 && mobile_number.length <= 19 && mobile_number.match(/^\d+$/) ) {
			$( ".valu_process_error" ).html( "" );
			$( '#div-aps-loader' ).show();
			$.ajax(
				{
					url: ajaxurl,
					type:'POST',
					data: {
						mobile_number,
						down_payment,
						tou,
						cashback
					},
					beforeSend: function () {
						$('.valu_customer_verify').attr('disabled', true);
					},
					success: function(response) {
						response = JSON.parse( response );
						if ( 'success' === response.status ) {
							$.ajax({
								'url': 'index.php?route=extension/payment/amazon_ps_valu/valuGenerateOtp',
								'type': 'POST',
								'async': false,
								data: {
									mobile_number,
								},
								success:function (otp_response) {
									$( '#div-aps-loader' ).hide();
									otp_response = JSON.parse(otp_response);
									if ( 'genotp_error' === otp_response.status ) {
										$( '.valu_process_error' ).html( otp_response.message );
										$( "#request_otp_sec" ).hide();
									} else if ( 'error' === otp_response.status ) {
										$( '.valu_process_error' ).html( otp_response.message );
									} else if ( 'success' === otp_response.status ) {
										apsPayment.valuOtpVerifyBox( otp_response );
										apsPayment.valuTenureBox( otp_response );
									}
								}
							});
						} else {
							$( '#div-aps-loader' ).hide();
							$( '.valu_process_error' ).html( response.message );
							$('.valu_customer_verify').attr('disabled', false);
						}
					}
				}
			);
		} else {
			$( ".valu_process_error" ).html( APSValidation.translate('valu_invalid_mobile') );
		}
		$('.valu_customer_verify').attr('disabled', false);
	}
);

$( document.body ).on(
	'click',
	'.valu_otp_verify',
	function(e) {
		var ajaxurl = 'index.php?route=extension/payment/amazon_ps_valu/valuOtpVerify';
		var otp     = $( '.aps_valu_otp' ).val();
		$( ".valu_process_error" ).html( "" );
		$( '#div-aps-loader' ).show();
		$.ajax(
			{
				url: ajaxurl,
				type:'POST',
				data: {
					otp,
				},
				success: function(response) {
					$( '#div-aps-loader' ).hide();
					$( ".valu_process_error" ).html( "" );
					response = JSON.parse( response );
					if ( 'success' === response.status ) {
						apsPayment.valuTenureBox( response );
						} else {
						$( '.valu_process_error' ).html( response.message );
					}
				}
			}
		)
	}
);

$( document.body ).on(
	'click',
	'#tenure_sec .tenureBox',
	function(e) {
		var ele     = $( this );
		var tenure  = ele.attr( 'data-tenure' );
		var tenure_amount   = ele.attr( 'data-tenure-amount' );
		var tenure_interest = ele.attr( 'data-tenure-admin-fee' );
		var aps_otp         = $( '.aps_valu_otp' ).val();
		$( '#aps_active_tenure' ).val( tenure );
		$( '#aps_tenure_amount' ).val( tenure_amount );
		$( '#aps_tenure_interest' ).val( tenure_interest );
		$( '#aps_otp' ).val( aps_otp );
		$( '.tenureBox.selected' ).removeClass( 'selected' );
		ele.addClass( 'selected' );
	}
);

$('.aps_token_radio').change(function () {
    $('.token-box input[type="text"]').remove();
	$('.aps_token_radio:checked').attr("required","required");
	$('.aps-radio').removeAttr("required");
});

$('.aps-radio').change(function () {
    $('.token-box input[type="text"]').remove();
    var card_type = $('.aps-radio:checked').data("cardtype");
    cvv_length = 3;
    if(card_type == 'amex'){
    	cvv_length = 4;
    }
	$('.aps-radio:checked').parents('.aps_token_row').append('<input type="text" id="aps_saved_card_security_code" name="aps_saved_card_security_code" class="aps_saved_card_security_code saved_cvv onlynum" autocomplete="off" maxlength="'+cvv_length+'" required placeholder="CVV">');
	$('.aps_saved_card_security_code').focus();
	$('.aps-radio:checked').attr("required","required");
	$('.aps_token_radio').removeAttr("required");
});

$( document.body ).on(
	'blur',
	'.saved_cvv.aps_saved_card_security_code',
	function(e) {
		if ( ! APSValidation.validateSavedCVV( $( this ).val(), $( this ).attr( 'maxlength' ) ).validity ) {
			$( '.field-error' ).removeClass( 'field-error' );
			$( this ).addClass( 'field-error' );
			$( 'html, body' ).animate(
				{
					scrollTop: $( '.field-error' ).offset().top - 10
				},
				1000
			);
			return false;
		} else {
			$( this ).removeClass( 'field-error' );
		}
	}
);

$('#frm_payfort_fort_payment .aps-radio').change(function () {
	$('.aps_hosted_form').hide();
	if($('#em_installment_plans').hasClass('active')){
		$('#em_installment_plans').removeClass('active');
		$( '#em_installment_plans .plans' ).html( '' );
		$( '#em_installment_plans .plan_info' ).html( '' );
		$( '#em_installment_plans .issuer_info' ).html( '' );
		if(!$('#em_installment_plans .plan_info').hasClass('validation-off')){
			$('#em_installment_plans .plan_info').addClass('validation-off');
		}
	}
	$('.aps_hosted_form .aps_card_number').val('');
	$('.aps_hosted_form .aps_card_holder_name').val('');
	$('.aps_hosted_form .aps_expiry_month').val('');
	$('.aps_hosted_form .aps_expiry_year').val('');
	$('.aps_hosted_form .aps_card_security_code').val('');
});
$('#frm_payfort_fort_payment .aps_token_radio').change(function () {
	$('.aps_hosted_form').show();
	if($('#em_installment_plans').hasClass('active')){
		$('#em_installment_plans').removeClass('active');
		$( '#em_installment_plans .plans' ).html( '' );
		$( '#em_installment_plans .plan_info' ).html( '' );
		$( '#em_installment_plans .issuer_info' ).html( '' );
		if(!$('#em_installment_plans .plan_info').hasClass('validation-off')){
			$('#em_installment_plans .plan_info').addClass('validation-off');
		}
	}
	$('.aps_hosted_form .aps_card_number').val('');
	$('.aps_hosted_form .aps_card_holder_name').val('');
	$('.aps_hosted_form .aps_expiry_month').val('');
	$('.aps_hosted_form .aps_expiry_year').val('');
	$('.aps_hosted_form .aps_card_security_code').val('');
});
