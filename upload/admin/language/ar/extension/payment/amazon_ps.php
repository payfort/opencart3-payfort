<?php
// Heading
$_['heading_title']               = 'Amazon Payment Services';

// Text
$_['text_extension']    = 'Extensions';
$_['text_success']                = 'Success: You have modified Amazon Payment Services account details!';
$_['text_edit']                   = 'Edit Amazon Payment Services';
$_['text_pay']                    = 'Amazon Payment Services';
$_['text_card']			 = 'Credit Card';
$_['text_amazon_ps'] = '<img src="view/image/payment/amazon_ps.png" alt="Amazon Payment Services" title="Amazon Payment Services" style="border: 1px solid #EEEEEE;" />';

$_['text_purchase']               = 'Purchase';
$_['text_authorization']          = 'Authorization';
$_['text_sha256']                 = 'SHA-256';
$_['text_sha512']                 = 'SHA-512';
$_['text_hmac256']                = 'HMAC-256';
$_['text_hmac512']                = 'HMAC-512';
$_['text_payment']                = 'Payments';
$_['text_redirection']            = 'Redirection';
$_['text_standard_checkout']      = 'Standard Checkout';
$_['text_hosted_checkout']        = 'Hosted Checkout';
$_['text_embedded_hosted_checkout'] = 'Embedded Hosted Checkout';
$_['text_base_currency']          = 'Base';
$_['text_front_currency']         = 'Front';

$_['text_amex'] 				  = 'American Express';
$_['text_visa']                   = 'Visa';
$_['text_masterCard']             = 'MasterCard';
$_['text_mada']                   = 'mada';

// Entry
$_['text_enabled']                = 'Yes';
$_['text_disabled']               = 'No';


$_['entry_status']                = 'Enabled';
$_['entry_merchant_identifier']   = 'Merchant Identifier';
$_['entry_access_code']           = 'Access Code';
$_['entry_request_sha_phrase']    = 'Request SHA Phrase';
$_['entry_response_sha_phrase']   = 'Response SHA Phrase';
$_['entry_sandbox']               = 'Sandbox Mode';
$_['entry_command']               = 'Command';
$_['entry_sha_type']              = 'SHA Type';
$_['entry_gateway_currency']      = 'Gateway Currency';
$_['entry_debug']                 = 'Debug mode:';
$_['entry_order_status']        = 'Order Status';
$_['entry_api_key']               = 'API Key';
$_['entry_profile_name']          = 'Profile Name';
$_['entry_integration_type']      = 'Integration Type';
$_['entry_sort_order']            = 'Sort Order';
$_['entry_show_mada_branding']    = 'Show mada Branding';
$_['entry_show_meeza_branding']   = 'Show Meeza Branding';
$_['entry_tokenization']          = 'Enable Tokenization';
$_['entry_hide_delete_token']          = 'Hide delete Token button';
$_['entry_valu_order_min_value']  = 'VALU Order Purchase minimum limit in EGP';
$_['entry_installments_sar_order_min_value']  = 'Installments Order Purchase minimum limit(SAR)';
$_['entry_installments_aed_order_min_value']  = 'Installments Order Purchase minimum limit(AED)';
$_['entry_installments_egp_order_min_value']  = 'Installments Order Purchase minimum limit(EGP)';
$_['entry_installments_issuer_name']  = 'Show issuer name';
$_['entry_installments_issuer_logo']  = 'Show issuer logo';
$_['entry_apple_pay_btn_type']    = 'Apple Pay Button Types';
$_['entry_domain_name']           = 'Domain Name';
$_['entry_display_name']          = 'Display Name';
$_['entry_production_key']        = 'Production Key';
$_['entry_supported_network']     = 'Supported Network';
$_['entry_mada_bins']             = 'mada Bins';
$_['entry_meeza_bins']            = 'Meeza Bins';
$_['entry_recurring_cron']        = 'CRON: Recurring Order';
$_['entry_check_status_cron']     = 'CRON: Check Order Payment';
$_['entry_check_status_cron_duration']  = 'CRON: Check Status Duration ';
$_['entry_status_apple_pay_product_page'] = 'Enabled Apple Pay in product page';
$_['entry_status_apple_pay_cart_page'] = 'Enabled Apple Pay in cart page';

// Help
$_['help_bins_text']              = 'Please do not change any of the below BINs configuration unless it is instructed by APS Integration team. For further inquiries: integration-ps@amazon.com';
$_['help_display_name_text']      = 'A string of 64 or fewer UTF-8 characters containing the canonical name for your store, suitable for display. Do not localize the name.';
$_['help_recurring_cron']        = 'Run CRON to make recurring orders transactions. Set it up to run at least once per day.';
$_['help_check_status_cron']     = 'Run CRON to check payment status for order which status is pending.';
$_['help_check_status_cron_duration'] = 'Order place duration to check payment status for order which  payment is pending. (Ex. Order Place before 15 Minutes).';
$_['help_gateway_currency']    = 'Currency should be sent to the payment gateway.';
$_['help_debug']               = 'Logs additional information in /system/storage/logs/amazon_ps_*.log';
$_['label_merchant_config']    = 'Amazon Payment Services Merchant Configuration';
$_['label_global_config']      = 'Amazon Payment Services Global Configuration';
$_['label_sign_up_url']              = 'Click here to sign up for Amazon Payment Services';
$_['label_host_to_host_url']         = 'Host to Host URL';

// Error
$_['error_permission']	          = 'Warning: You do not have permission to modify payment Amazon Payment Services!';
$_['error_merchant']	 = 'Merchant Identifier Required!';
$_['error_amazon_ps_merchant_identifier'] = 'Merchant Identifier Required!';
$_['error_amazon_ps_access_code']         = 'Access Code Required!';
$_['error_amazon_ps_request_sha_phrase']  = 'Request SHA Phrase Required!';
$_['error_amazon_ps_response_sha_phrase'] = 'Response SHA Phrase Required!';
$_['amazon_ps_payment_method_required']   = 'At Least 1 Payment Method Should Be Enabled!';


$_['tab_general']                 = 'General';
$_['tab_credit_card']             = 'Credit \ Debit Card';
$_['tab_installments']            = 'Installments';
$_['tab_naps']                    = 'NAPS';
$_['tab_knet']                    = 'KNET';
$_['tab_valu']                    = 'Valu';
$_['tab_visa_checkout']           = 'Visa Checkout';
$_['tab_apple_pay']               = 'Apple Pay';
$_['tab_cron']                    = 'CRON';

$_['text_order_total']            = 'Order Total';
$_['text_total_capture']          = 'Total Capture';
$_['text_remaining_capture']      = 'Remaining Capture';
$_['text_void']                   = 'Void';
$_['text_capture']                = 'Capture';
$_['text_refundable']             = 'Refunable';
$_['text_refunded']               = 'Refunded';
$_['text_refund']                 = 'Refund';
$_['text_transactions']		   	  = 'Transactions';
$_['text_column_amount']		  = 'Amount';
$_['text_column_type']			  = 'Type';
$_['text_column_date']	          = 'Date';
$_['text_column_title']			  = 'Title';
$_['text_column_value']			  = 'Value';
$_['text_confirm_capture']		  = 'Are you sure you want to capture the payment?';
$_['text_confirm_void']		      = 'Are you sure you want to Void the payment?';
$_['text_confirm_refund']		  = 'Are you sure you want to refund the payment?';
$_['text_15m']					  = '15 Minutes';
$_['text_30m']					  = '30 Minutes';
$_['text_45m']					  = '45 Minutes';
$_['text_1h']					  = '1 Hour';
$_['text_2h']					  = '2 Hour';
$_['text_upload']                 = 'Upload';
$_['apple_pay_certificate']       = 'Apple Pay Certificate';
$_['text_upload_certificate']     = 'Upload Apple Pay Certificates';

$_['error_data_missing'] 		  = 'Required data is missing';

// Buttons
$_['button_capture']              = 'Capture';
$_['button_void']                 = 'Void';
$_['button_refund']				  = 'Refund';
