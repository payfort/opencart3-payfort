<?php

class AmazonPSOrderPayment {
    private $session;
    private $url;
    private $config;
    private $log;
    private $customer;
    private $currency;
    private $registry;
	private $cart;
    private $tax;

    private $order = [];
    private $aps_model;
    private $aps_token;
    private $payment;
    
	private $model_checkout_order;
	private $model_catalog_product;
	private $model_catalog_category;
    private $model_checkout_recurring;
    private $model_account_recurring;
    private $model_localisation_language;
    
    public function __construct($registry) {
        $this->registry = $registry;
        $this->session = $registry->get('session');
        $this->url = $registry->get('url');
        $this->config = $registry->get('config');
        $this->log = $registry->get('log');
        $this->customer = $registry->get('customer');
        $this->currency = $registry->get('currency');
        $this->db = $registry->get('db');
        $this->language = $registry->get('language');
        $this->cart = $registry->get('cart');
        $this->tax = $registry->get('tax');

        $this->amazonpspaymentservices = new AmazonPSPaymentServices($registry);
		
		$registry->get('load')->model('extension/payment/amazon_ps');
		$this->aps_model = $registry->get('model_extension_payment_amazon_ps');

		$registry->get('load')->model('checkout/order');
		$this->model_checkout_order = $registry->get('model_checkout_order');
		
		$registry->get('load')->model('catalog/product');
		$this->model_catalog_product = $registry->get('model_catalog_product');
		
		$registry->get('load')->model('catalog/category');
		$this->model_catalog_category = $registry->get('model_catalog_category');

        $registry->get('load')->model('checkout/recurring');
        $this->model_checkout_recurring = $registry->get('model_checkout_recurring');

        $registry->get('load')->model('account/recurring');
        $this->model_account_recurring = $registry->get('model_account_recurring');

        $registry->get('load')->model('localisation/language');
        $this->model_localisation_language = $registry->get('model_localisation_language');

        $registry->get('load')->model('extension/payment/amazon_ps_tokens');
		$this->aps_token = $registry->get('model_extension_payment_amazon_ps_tokens');
    }    
	
	
	
	
    public function getPaymentRequestParams($paymentMethod, $integrationType = AmazonPSConstant::AMAZON_PS_INTEGRATION_TYPE_REDIRECTION, $extras = array())
    {

        $orderId = $this->getSessionOrderId();
        $order = $this->loadOrder($orderId);

        $gatewayParams = array(
            'merchant_identifier' => $this->amazonpspaymentservices->getMerchantIdentifier(),
            'access_code'         => $this->amazonpspaymentservices->getAccessCode(),
            'merchant_reference'  => $orderId,
            'language'            => $this->amazonpspaymentservices->getLanguage(),
        );
        if ($integrationType == AmazonPSConstant::AMAZON_PS_INTEGRATION_TYPE_REDIRECTION) {
            $baseCurrency                    = $this->amazonpspaymentservices->getBaseCurrency();
            $orderCurrency                   = $this->getOrderCurrencyCode($order);  
            $currency                        = $this->amazonpspaymentservices->getGatewayCurrencyCode($baseCurrency, $orderCurrency);
            $gatewayParams['currency']       = strtoupper($currency);
            $gatewayParams['amount']         = $this->amazonpspaymentservices->convertGatewayAmount($this->getOrderTotal($order), $this->getOrderCurrencyValue($order), $currency);
            $gatewayParams['customer_email'] = $this->getOrderEmail($order);
            $gatewayParams['command']        = $this->amazonpspaymentservices->getCommand($paymentMethod);
            $gateway_params['order_description'] = 'Order#' . $orderId;
            $gatewayParams['return_url']     = $this->amazonpspaymentservices->getReturnUrl($paymentMethod.'/responseOnline');
			if ( isset( $extras['aps_payment_token'] ) && ! empty( $extras['aps_payment_token'] ) ) {
				$gatewayParams['token_name'] = $extras['aps_payment_token'];
			}
            if ($paymentMethod == AmazonPSConstant::AMAZON_PS_PAYMENT_METHOD_KNET) {
                $gatewayParams['payment_option'] = 'KNET';
            }elseif ($paymentMethod == AmazonPSConstant::AMAZON_PS_PAYMENT_METHOD_NAPS) {
                $gatewayParams['payment_option']    = 'NAPS';
            }
            elseif ($paymentMethod == AmazonPSConstant::AMAZON_PS_PAYMENT_METHOD_INSTALLMENTS) {
                $gatewayParams['installments'] = 'STANDALONE';
                $gatewayParams['command']      = 'PURCHASE';                
            }elseif ( $paymentMethod ==  AmazonPSConstant::AMAZON_PS_PAYMENT_METHOD_VISA_CHECKOUT) {
                $gatewayParams['digital_wallet'] = 'VISA_CHECKOUT';
            }
            $plugin_params  = $this->amazonpspaymentservices->plugin_params();
            $gatewayParams = array_merge( $gatewayParams, $plugin_params );
        }
        else{
            if($paymentMethod ==  AmazonPSConstant::AMAZON_PS_PAYMENT_METHOD_VISA_CHECKOUT){
                unset($gatewayParams['service_command']);
                $baseCurrency                    = $this->amazonpspaymentservices->getBaseCurrency();
                $orderCurrency                   = $this->getOrderCurrencyCode($order);  
                $currency                        = $this->amazonpspaymentservices->getGatewayCurrencyCode($baseCurrency, $orderCurrency);
                $gatewayParams['currency']       = strtoupper($currency);
                $gatewayParams['amount']         = $this->amazonpspaymentservices->convertGatewayAmount($this->getOrderTotal($order), $this->getOrderCurrencyValue($order), $currency);
                $gatewayParams['customer_email'] = $this->getOrderEmail($order);
                $gatewayParams['command']        = $this->amazonpspaymentservices->getCommand($paymentMethod);
                $gatewayParams['return_url']     = $this->amazonpspaymentservices->getReturnUrl($paymentMethod.'/responseOnline');                
            }else{
                $gatewayParams['service_command'] = AmazonPSConstant::AMAZON_PS_COMMAND_TOKENIZATION; //'TOKENIZATION';
                $gatewayParams['return_url']      = $this->amazonpspaymentservices->getReturnUrl($paymentMethod.'/merchantPageResponse');
                if($paymentMethod == AmazonPSConstant::AMAZON_PS_PAYMENT_METHOD_INSTALLMENTS && ($integrationType == AmazonPSConstant::AMAZON_PS_INTEGRATION_TYPE_STANDARD_CHECKOUT )){
                    $baseCurrency                    = $this->amazonpspaymentservices->getBaseCurrency();
                    $orderCurrency                   = $this->getOrderCurrencyCode($order);
                    $currency                        = $this->amazonpspaymentservices->getGatewayCurrencyCode($baseCurrency, $orderCurrency);
                    $gatewayParams['currency']       = strtoupper($currency);
                    $gatewayParams['installments']   = 'STANDALONE';
                    $gatewayParams['amount']         = $this->amazonpspaymentservices->convertGatewayAmount($this->getOrderTotal($order), $this->getOrderCurrencyValue($order), $currency);

                }
				if ( isset( $extras['aps_payment_token'] ) && ! empty( $extras['aps_payment_token'] ) ) {
					$gatewayParams['token_name'] = trim( $extras['aps_payment_token'], ' ' );
					
					if ( isset( $extras['aps_card_bin'] ) && ! empty( $extras['aps_card_bin'] ) ) {
						$gatewayParams['card_bin'] = trim( $extras['aps_card_bin'], ' ' );
					}
					if ( isset( $extras['aps_payment_cvv'] ) && ! empty( $extras['aps_payment_cvv'] ) ) {
						$gatewayParams['card_security_code'] = trim( $extras['aps_payment_cvv'], ' ' );
					}
                    $this->amazonpspaymentservices->log("aps notify tokenization_purchase" );
                    $host2HostParams = $this->merchantPageNotifyFort($gatewayParams, $orderId, $paymentMethod, $integrationType);
                    $redirect_url =  $this->handleAmazonPSResponse($host2HostParams, 'online', 'cc_merchant_page_h2h', true);
                    if($redirect_url === true){
                        $redirect_url = $this->url->link('checkout/success');
                    }else if($redirect_url === false){
                        $redirect_url = $this->url->link('checkout/checkout');
                    }
                    $this->amazonpspaymentservices->log("tokenization_purchase".$redirect_url );
                    return array('url' => '', 'params' => '', 'redirect_url' => $redirect_url);
				}
            }
        }
        $signature                  = $this->amazonpspaymentservices->calculateSignature($gatewayParams, 'request');
        $gatewayParams['signature'] = $signature;

        //In case of recurring on we explictly set remember_me to yes
        if ( $integrationType == AmazonPSConstant::AMAZON_PS_INTEGRATION_TYPE_HOSTED_CHECKOUT && $this->cart->hasRecurringProducts() ) {
            $gatewayParams['remember_me'] = 'YES';
        }

        $gatewayUrl = $this->amazonpspaymentservices->getGatewayUrl();
        
        $this->amazonpspaymentservices->log(print_r($gatewayParams, 1),"Request Params for payment method ($paymentMethod) ".$gatewayUrl.$paymentMethod );
        
        return array('url' => $gatewayUrl, 'params' => $gatewayParams, 'redirect_url' => '');
    }

    public function getPaymentRequestForm($paymentMethod, $integrationType = AmazonPSConstant::AMAZON_PS_INTEGRATION_TYPE_REDIRECTION, $extraParams = array())
    {
        $paymentRequestParams = $this->getPaymentRequestParams($paymentMethod, $integrationType, $extraParams);
         
        $form = '<form style="display:none" name="frm_payfort_fort_payment" id="frm_payfort_fort_payment" method="post" action="' . $paymentRequestParams['url'] . '">';
        foreach ($paymentRequestParams['params'] as $k => $v) {
            $form .= '<input type="hidden" name="' . $k . '" value="' . $v . '">';
        }
        $form .= '<input type="submit">';
        return $form;
    }

    public function handleAmazonPSResponse($apsParams = array(), $responseMode = 'online', $integrationType = 'redirection', $tokenization_purchase = false)
    {
        $order = '';
        try {


            $this->language->load('extension/payment/amazon_ps');

            $responseParams  = $apsParams;
            $success         = false;
            $responseMessage = isset( $responseParams['response_message'] ) && ! empty( $responseParams['response_message'] ) ? $responseParams['response_message'] : $this->language->get('error_transaction_error_1');
            //check response param
            if (empty($responseParams)) {
                $this->amazonpspaymentservices->log('Empty fort response parameters (' . $responseMode . ')');
                throw new Exception($responseMessage);
            }

            if (!isset($responseParams['merchant_reference']) || empty($responseParams['merchant_reference'])) {
                $this->amazonpspaymentservices->log("Invalid fort response parameters. merchant_reference not found ($responseMode) \n\n" . json_encode($responseParams));
                throw new Exception($responseMessage);
            }

            $orderId = $responseParams['merchant_reference'];       
            $order = $this->loadOrder($orderId);

            // check get order id if webhook call for valu refund or order webhook
            $valu_order_id_by_reference = '';
            if( ! ($this->getOrderId($order)) ){
                if( ( isset( $responseParams['command'] ) && in_array($responseParams['command'], array('REFUND', 'CAPTURE', 'VOID_AUTHORIZATION')) ) || ( isset($responseParams['payment_option']) && 'VALU' === $responseParams['payment_option'] && 'offline' === $responseMode ) ) {
                    $valu_order_id_by_reference = $this->aps_model->find_valu_order_by_reference( $responseParams['merchant_reference'] );
                }
            }

            $paymentMethod = '';
            if( ($this->getOrderId($order)) ){
                $paymentMethod = $this->getPaymentMethod($order);
            }
            $this->amazonpspaymentservices->log("Fort response parameters ($responseMode) for payment method ($paymentMethod) \n\n" . print_r($responseParams, 1));

            $responseType          = $responseParams['response_message'];
            $signature             = $responseParams['signature'];
            $responseOrderId       = $responseParams['merchant_reference'];
            $responseStatus        = isset($responseParams['status']) ? $responseParams['status'] : '';
            $responseCode          = isset($responseParams['response_code']) ? $responseParams['response_code'] : '';
            $responseStatusMessage = $responseType;

            // exclude signature and route from params to check sign
            $notIncludedParams = array('signature', 'route');
            $responseGatewayParams = $responseParams;
            foreach ($responseGatewayParams as $k => $v) {
                if (in_array($k, $notIncludedParams)) {
                    unset($responseGatewayParams[$k]);
                }
            }
            $signature_type     = isset( $responseParams['digital_wallet'] ) && 'APPLE_PAY' === $responseParams['digital_wallet'] ? 'apple_pay' : 'regular';

            //check webhook call for apple pay
            if( isset( $responseParams['command'] ) && in_array($responseParams['command'], array('REFUND', 'CAPTURE', 'VOID_AUTHORIZATION')) ){
                if( isset($responseParams['access_code']) && $responseParams['access_code'] == $this->amazonpspaymentservices->getApplePayAccessCode() ){
                    $signature_type = 'apple_pay';
                }
            }

            $responseSignature = $this->amazonpspaymentservices->calculateSignature($responseGatewayParams, 'response', $signature_type);


            //update order id if webhook call for valu refund
            $valu_order_id_by_reference = '';
            if( $valu_order_id_by_reference != '' && (! ($this->getOrderId($order))) ){
                $orderId = $valu_order_id_by_reference;
                $responseParams['merchant_reference'] = $orderId;       
                $order = $this->loadOrder($orderId);
                $paymentMethod = $this->getPaymentMethod($order);
            }

            // check the signature
            if (strtolower($responseSignature)  !== strtolower($signature)) {
                $responseMessage = $this->language->get('error_invalid_signature');
                $this->amazonpspaymentservices->log(sprintf('Invalid Signature. Calculated Signature: %1s, Response Signature: %2s', $signature, $responseSignature));
                $this->amazonpspaymentservices->log('signature_type'.$signature_type);
                // There is a problem in the response we got
                $this->onHoldOrder( $order, $responseMessage );
                throw new Exception($responseMessage);
                return true;
            }
           
            if ( AmazonPSConstant::AMAZON_PS_PAYMENT_CANCEL_RESPONSE_CODE === $responseCode ) {
                $responseMessage = isset( $responseParams['response_message'] ) && ! empty( $responseParams['response_message'] ) ? $responseParams['response_message'] : $this->language->get('error_transaction_cancelled');

                $r = $this->declineOrder($order, $responseParams, $responseMessage);
                if ($r) {
                    throw new Exception($responseMessage);
                }
            }

            // standard & hosted checkout
            if ($integrationType == 'cc_merchant_page_h2h') {
                if (AmazonPSConstant::AMAZON_PS_MERCHANT_SUCCESS_RESPONSE_CODE === $responseCode && isset($responseParams['3ds_url'])) {
                    if($paymentMethod == AmazonPSConstant::AMAZON_PS_PAYMENT_METHOD_VISA_CHECKOUT && $this->amazonpspaymentservices->getVisaCheckoutIntegrationType() == AmazonPSConstant::AMAZON_PS_INTEGRATION_TYPE_HOSTED_CHECKOUT || $tokenization_purchase){
                        return $responseParams['3ds_url'];
                    }                    
                    if($integrationType == AmazonPSConstant::AMAZON_PS_INTEGRATION_TYPE_HOSTED_CHECKOUT) {
                        $this->amazonpspaymentservices->log("PHP 3DS_URL Called " . filter_var($responseParams['3ds_url'], FILTER_SANITIZE_URL));
                        header('location:' . filter_var($responseParams['3ds_url'], FILTER_SANITIZE_URL));
                    }
                    else{
                        $this->amazonpspaymentservices->log("JS 3DS_URL Called " . filter_var($responseParams['3ds_url'], FILTER_SANITIZE_URL)  );
                        echo '<script>window.top.location.href = "'.filter_var($responseParams['3ds_url'], FILTER_SANITIZE_URL).'"</script>';
                    }
                    exit;
                }
            }

            if ( AmazonPSConstant::AMAZON_PS_PAYMENT_SUCCESS_RESPONSE_CODE === $responseCode || AmazonPSConstant::AMAZON_PS_PAYMENT_AUTHORIZATION_SUCCESS_RESPONSE_CODE === $responseCode ) {
                $this->successOrder($order, $responseParams, $responseMode);
            }elseif ( in_array( $responseCode, AmazonPSConstant::AMAZON_PS_ONHOLD_RESPONSE_CODES, true ) ) {
                $this->onHoldOrder( $order, $responseMessage );
            } elseif ( AmazonPSConstant::AMAZON_PS_CAPTURE_SUCCESS_RESPONSE_CODE === $responseCode || AmazonPSConstant::AMAZON_PS_PAYMENT_AUTHORIZATION_SUCCESS_RESPONSE_CODE === $responseCode ) {
                $this->capture_order($order, $responseParams, $responseMode );
            } elseif ( AmazonPSConstant::AMAZON_PS_REFUND_SUCCESS_RESPONSE_CODE === $responseCode ) {
                $this->refund_order($order, $responseParams, $responseMode );
            } elseif ( AmazonPSConstant::AMAZON_PS_AUTHORIZATION_VOIDED_SUCCESS_RESPONSE_CODE === $responseCode || AmazonPSConstant::AMAZON_PS_PAYMENT_AUTHORIZATION_SUCCESS_RESPONSE_CODE === $responseCode ) {
                 $this->amazonpspaymentservices->log( 'Void Order called');
                $this->void_order($order, $responseParams, $responseMode );
            }elseif (AmazonPSConstant::AMAZON_PS_TOKENIZATION_SUCCESS_RESPONSE_CODE === $responseCode || AmazonPSConstant::AMAZON_PS_UPDATE_TOKENIZATION_SUCCESS_RESPONSE_CODE === $responseCode || AmazonPSConstant::AMAZON_PS_SAFE_TOKENIZATION_SUCCESS_RESPONSE_CODE === $responseCode ){
                if (($paymentMethod == AmazonPSConstant::AMAZON_PS_PAYMENT_METHOD_CC && ($integrationType == AmazonPSConstant::AMAZON_PS_INTEGRATION_TYPE_STANDARD_CHECKOUT || $integrationType == AmazonPSConstant::AMAZON_PS_INTEGRATION_TYPE_HOSTED_CHECKOUT)) || ($paymentMethod == AmazonPSConstant::AMAZON_PS_PAYMENT_METHOD_INSTALLMENTS && $integrationType == AmazonPSConstant::AMAZON_PS_INTEGRATION_TYPE_STANDARD_CHECKOUT) || ($paymentMethod == AmazonPSConstant::AMAZON_PS_PAYMENT_METHOD_INSTALLMENTS && $integrationType == AmazonPSConstant::AMAZON_PS_INTEGRATION_TYPE_HOSTED_CHECKOUT)) {
                    $host2HostParams = $this->merchantPageNotifyFort($responseParams, $orderId, $paymentMethod, $integrationType);
                    return $this->handleAmazonPSResponse($host2HostParams, 'online', 'cc_merchant_page_h2h');
                }
            }else {
                $capture_void_refund = array(
                    AmazonPSConstant::AMAZON_PS_COMMAND_CAPTURE,
                    AmazonPSConstant::AMAZON_PS_COMMAND_VOID_AUTHORIZATION,
                    AmazonPSConstant::AMAZON_PS_COMMAND_REFUND,
                );
                if(isset($responseParams['command']) && in_array($responseParams['command'], $capture_void_refund)){

                    $responseMessage = isset( $responseParams['response_message'] ) && ! empty( $responseParams['response_message'] ) ? $responseParams['response_message'] : '';

                    $this->updateOrderStatus($this->getOrderId($order), $this->getOrderStatusId($order), 'Failed '.$responseParams['command']. ' Error : '.$responseMessage);
                }else{
                    $responseMessage = isset( $responseParams['response_message'] ) && ! empty( $responseParams['response_message'] ) ? $responseParams['response_message'] : $this->language->get('error_response_unknown');
                   // $responseMessage = sprintf($this->language->get('error_transaction_error_2'), $responseMessage);
                    $r = $this->declineOrder($order, $responseParams, $responseMessage);
                    if ($r) {
                        throw new Exception($responseMessage);
                    }
                }
            }
        } catch (Exception $e) {
            $this->amazonpspaymentservices->setFlashMsg($this->language->get('technical_error')." : ".  $e->getMessage(), AmazonPSConstant::AMAZON_PS_FLASH_MSG_ERROR);
            $this->amazonpspaymentservices->log("ERROR : Fort response parameters ($responseMode) for payment method \n\n" . $e->getMessage());
            // if order payment processed then return true
            if (in_array($this->getOrderStatusId($order), [AmazonPSConstant::PROCESSING_ORDER_STATUS_ID, AmazonPSConstant::SHIPPED_ORDER_STATUS_ID, AmazonPSConstant::COMPLETE_ORDER_STATUS_ID, AmazonPSConstant::PROCESSED_ORDER_STATUS_ID, AmazonPSConstant::REFUNDED_ORDER_STATUS_ID])){
                unset($this->session->data['error']);
                return true;
            }
            return false;
        }
        $this->amazonpspaymentservices->log("Complete handleAmazonPSResponse");
        return true;
    }

    public function visaCheckoutHosted($responseParams, $paymentMethod)
    {
        $orderId = $this->getSessionOrderId();
        $host2HostParams = $this->merchantPageNotifyFort($responseParams, $orderId, $paymentMethod, AmazonPSConstant::AMAZON_PS_INTEGRATION_TYPE_STANDARD_CHECKOUT);
        return $this->handleAmazonPSResponse($host2HostParams, 'online', 'cc_merchant_page_h2h');
    }

    private function merchantPageNotifyFort($apsParams, $orderId, $paymentMethod, $integrationType)
    {
        //send host to host
        $order = $this->loadOrder($orderId);

        $baseCurrency  = $this->amazonpspaymentservices->getBaseCurrency();
        $orderCurrency = $this->getOrderCurrencyCode($order);
        $currency      = $this->amazonpspaymentservices->getGatewayCurrencyCode($baseCurrency, $orderCurrency);
        $language      = $this->amazonpspaymentservices->getLanguage();
        $paymentMethod = $this->getPaymentMethod($order);

        $command = AmazonPSConstant::AMAZON_PS_COMMAND_PURCHASE;
        
        $em_plan_code = $this->aps_model->getAmazonPSMetaValue($orderId, 'em_installment_plan_code');
        if($this->cart->hasRecurringProducts()){
            $command = AmazonPSConstant::AMAZON_PS_COMMAND_PURCHASE;
        } elseif ( isset( $apsParams['card_bin'] ) && ! empty( $apsParams['card_bin'] ) ) {
            $command = $this->amazonpspaymentservices->getCommand( $paymentMethod, $apsParams['card_bin'] );
        } elseif ( isset( $apsParams['card_number'] ) && ! empty( $apsParams['card_number'] ) ) {
            $command = $this->amazonpspaymentservices->getCommand( $paymentMethod, substr( $apsParams['card_number'], 0, 6 ) );
        } else {
            $command = $this->amazonpspaymentservices->getCommand( $paymentMethod );
        }

        if ( isset( $apsParams['token_name'] ) && ! empty( $apsParams['token_name'] ) ) {
            $card_type = $this->aps_token->getTokenCardType($apsParams['token_name']);
                if ( ! empty( $card_type ) ) {
                    $command = $this->amazonpspaymentservices->getCommand( $paymentMethod, null, strtoupper( $card_type ) );
                }
        }

        $postData      = array(
            'merchant_reference'  => $orderId,
            'access_code'         => $this->amazonpspaymentservices->getAccessCode(),
            'command'             => $command,
            'merchant_identifier' => $this->amazonpspaymentservices->getMerchantIdentifier(),
            'customer_ip'         => $this->amazonpspaymentservices->getCustomerIp(),
            'amount'              => $this->amazonpspaymentservices->convertGatewayAmount($this->getOrderTotal($order), $this->getOrderCurrencyValue($order), $currency),
            'currency'            => strtoupper($currency),
            'customer_email'      => $this->getOrderEmail($order),
            'language'            => $language,
            'return_url'          => $this->amazonpspaymentservices->getReturnUrl($paymentMethod.'/responseOnline')
        );

        if($paymentMethod == AmazonPSConstant::AMAZON_PS_PAYMENT_METHOD_VISA_CHECKOUT){
            $postData['digital_wallet'] = 'VISA_CHECKOUT'; 
            $postData['call_id']   = $apsParams['visa_checkout_call_id'];
        }else{
            $postData['token_name']    = $apsParams['token_name'];
            if ( isset( $apsParams['card_security_code'] ) ) {
                $postData['card_security_code'] = $apsParams['card_security_code'];
            }   
        }
        
        if($paymentMethod == AmazonPSConstant::AMAZON_PS_PAYMENT_METHOD_INSTALLMENTS && $integrationType == AmazonPSConstant::AMAZON_PS_INTEGRATION_TYPE_STANDARD_CHECKOUT) {
            $postData['installments']            = 'YES';
            $postData['plan_code']               = $apsParams['plan_code'];
            $postData['issuer_code']             = $apsParams['issuer_code'];
            $postData['command']                 = 'PURCHASE';
        }elseif($paymentMethod == AmazonPSConstant::AMAZON_PS_PAYMENT_METHOD_INSTALLMENTS && $integrationType == AmazonPSConstant::AMAZON_PS_INTEGRATION_TYPE_HOSTED_CHECKOUT) {

            //$this->load->model('catalog/extension/payment/amazon_ps');
			
            $plan_code = $this->aps_model->getAmazonPSMetaValue($orderId, 'installment_plan_code');
            $issuer_code = $this->aps_model->getAmazonPSMetaValue($orderId, 'installment_issuer_code');
            $postData['installments']            = 'HOSTED';
            $postData['plan_code']               = $plan_code;
            $postData['issuer_code']             = $issuer_code;
            $postData['command']                 = 'PURCHASE';
        }
        if ( isset( $em_plan_code ) && ! empty( $em_plan_code ) ) {
            $postData['installments'] = 'HOSTED';
            $postData['plan_code']    = $em_plan_code;
            $postData['issuer_code']  = $this->aps_model->getAmazonPSMetaValue($orderId, 'em_installment_issuer_code');
            $postData['command']      = 'PURCHASE';
        }
        
        $customerName = $this->getCustomerName($order);
        if (!empty($customerName)) {
            $postData['customer_name'] = $this->getCustomerName($order);
        }
        $postData['eci'] = AmazonPSConstant::AMAZON_PS_COMMAND_ECOMMERCE;

        if ( isset( $apsParams['remember_me'] ) && ! isset( $apsParams['card_security_code'] ) && AmazonPSConstant::AMAZON_PS_PAYMENT_METHOD_VISA_CHECKOUT !== $paymentMethod ) {
            $postData['remember_me'] = isset( $apsParams['remember_me'] ) ? $apsParams['remember_me'] : 'NO';
        }

        $plugin_params  = $this->amazonpspaymentservices->plugin_params();
        $postData       = array_merge( $postData, $plugin_params );

        //calculate request signature
        $signature             = $this->amazonpspaymentservices->calculateSignature($postData, 'request');
        $postData['signature'] = $signature;

        $gatewayUrl = $this->amazonpspaymentservices->getGatewayUrl('notificationApi');
        $this->amazonpspaymentservices->log('Merchant Page Notify Api Request Params : '.$gatewayUrl . print_r($postData, 1));

        $response = $this->callApi($postData, $gatewayUrl);

        $this->amazonpspaymentservices->log('Merchant Page Notify Api Response Params : '.$gatewayUrl . json_encode($response, 1));

        return $response;
    }

    public function merchantPageCancel()
    {

        $this->language->load('extension/payment/amazon_ps');
        $orderId = $this->getSessionOrderId();
        $order = $this->loadOrder($orderId);

        if ($orderId) {
            $this->cancelOrder($order);
            $this->amazonpspaymentservices->setFlashMsg($this->language->get('text_payment_canceled'));
        }
        return true;
    }

    public function callApi($postData, $gatewayUrl)
    {
        //open connection
        $ch = curl_init();

        //set the url, number of POST vars, POST data
        $useragent = "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:20.0) Gecko/20100101 Firefox/20.0";
        curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json;charset=UTF-8',
        ));
        curl_setopt($ch, CURLOPT_URL, $gatewayUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_FAILONERROR, 1);
        curl_setopt($ch, CURLOPT_ENCODING, "compress, gzip");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0); // DON'T allow redirects
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0); // The number of seconds to wait while trying to connect
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
            $this->amazonpspaymentservices->log('Api Curl Call error : '.$error_msg);
        }
        curl_close($ch);

        $array_result = json_decode($response, true);

        if (!$response || empty($array_result)) {
            return false;
        }
        return $array_result;
    }

    /**
     * Find bin in plans
     *
     * @return issuer_key int
     */
    private function find_bin_in_plans( $card_bin, $issuer_data ) {
        $issuer_key = null;
        if ( ! empty( $issuer_data ) ) {
            foreach ( $issuer_data as $key => $row ) {
                $card_regex  = '';
                $issuer_bins = array_column( $row['bins'], 'bin' );
                if ( ! empty( $issuer_bins ) ) {
                    $card_regex = '/^' . implode( '|', $issuer_bins ) . '/';
                    if ( preg_match( $card_regex, $card_bin ) ) {
                        $issuer_key = $key;
                        break;
                    }
                }
            }
        }
        return $issuer_key;
    }

    private function get_installment_plan( $cardnumber ) {
        $this->language->load('extension/payment/amazon_ps');
        $retarr = array(
            'status'           => 'success',
            'installment_data' => array(),
            'code'             => 200,
            'message'          => 'List of plans',
        );
        try {
            
            $orderId = $this->getSessionOrderId();
            $order = $this->loadOrder($orderId);

            $gatewayParams = array(
                'merchant_identifier' => $this->amazonpspaymentservices->getMerchantIdentifier(),
                'access_code'         => $this->amazonpspaymentservices->getAccessCode(),
                'language'            => $this->amazonpspaymentservices->getLanguage(),
                'query_command'       => 'GET_INSTALLMENTS_PLANS'
            );
            

            $baseCurrency                    = $this->amazonpspaymentservices->getBaseCurrency();
            $orderCurrency                   = $this->getOrderCurrencyCode($order);  
            $currency                        = $this->amazonpspaymentservices->getGatewayCurrencyCode($baseCurrency, $orderCurrency);
            $gatewayParams['currency']       = strtoupper($currency);
            $gatewayParams['amount']         = $this->amazonpspaymentservices->convertGatewayAmount($this->getOrderTotal($order), $this->getOrderCurrencyValue($order), $currency);

            //calculate request signature
            $signature             = $this->amazonpspaymentservices->calculateSignature($gatewayParams, 'request');
            $gatewayParams['signature'] = $signature;

            $gatewayUrl = $this->amazonpspaymentservices->getGatewayUrl('notificationApi');
            $this->amazonpspaymentservices->log('Get installment plan Request Params : '.$gatewayUrl . print_r($gatewayParams, 1));

            $response = $this->callApi($gatewayParams, $gatewayUrl);

            if ( AmazonPSConstant::AMAZON_PS_GET_INSTALLMENT_SUCCESS_RESPONSE_CODE == $response['response_code'] ) {
                $response['installment_detail']['issuer_detail'] = array_filter(
                    $response['installment_detail']['issuer_detail'],
                    function ( $row ) {
                        return ! empty( $row['plan_details'] ) ? true : false;
                    }
                );
                if ( empty( $response['installment_detail']['issuer_detail'] ) ) {
                    throw new Exception($this->language->get('text_no_plans'));
                }
                $issuer_key = $this->find_bin_in_plans( $cardnumber, $response['installment_detail']['issuer_detail'] );
                if ( empty( $issuer_key ) && ! isset( $response['installment_detail']['issuer_detail'][ $issuer_key ] ) ) {
                    throw new Exception($this->language->get('text_no_plans_avail'));
                }
                $retarr['installment_data'] = $response['installment_detail']['issuer_detail'][ $issuer_key ];
            } else {
                throw new Exception($response['response_message']);
            }
        } catch ( Exception $e ) {
            $retarr['status']  = 'error';
            $retarr['code']    = '400';
            $retarr['message'] = $e->getMessage();
            $this->amazonpspaymentservices->log("ERROR : installment_plans response  for payment method \n\n" . $e->getMessage());

        }
        $this->amazonpspaymentservices->log("Fort response installment_plans for payment method" . json_encode($response));
        $this->amazonpspaymentservices->log("Fort retarr installment_plans for payment method" . print_r($retarr, 1));
        return $retarr;
    }

    /**
     * Get Installment plans ajax handler
     */
    public function get_installment_handler($cardnumber, $embedded_hosted_checkout) {
        $this->language->load('extension/payment/amazon_ps');
        $cardnumber = str_replace( ' ', '', $cardnumber );

        $orderId = $this->getSessionOrderId();
        $order = $this->loadOrder($orderId);
        /*embedded hosted checkout check minimum cart total*/
        $pay_full_payment = '';
        if($embedded_hosted_checkout == 1){
            $this->registry->get('load')->model('extension/payment/amazon_ps_installments');
            $installment_model = $this->registry->get('model_extension_payment_amazon_ps_installments');

            $installment_min_limit = $installment_model->checkInstallmentTotalMinLimit($order['total']);
            if(! $installment_min_limit){
                $retarr['status']  = 'error';
                $retarr['message'] = 'Order amount is less than currency minimum limit.';
                return $retarr;
            }
            $pay_full_payment = "<div class='slide'>
                <div class='emi_box' data-interest ='' data-amount='' data-plan-code='' data-issuer-code='' data-full-payment='1'>
                    <p class='with_full_payment'>".$this->language->get('text_full_payment')."</p>
                </div>
            </div>";

        }

        $response   = $this->get_installment_plan( $cardnumber );
        $retarr     = array(
            'status'          => 'success',
            'plans_html'      => '',
            'plan_info'       => '',
            'issuer_info'     => '',
            'message'         => '',
            'confirmation_en' => '',
            'confirmation_ar' => '',
        );
        if ( 'success' === $response['status'] && ! empty( $response['installment_data'] ) ) {
            $all_plans  = $response['installment_data']['plan_details'];
            $banking_system = $response['installment_data']['banking_system'];
            $interest_text  = 'Non Islamic' === $banking_system ? $this->language->get('text_interest') : $this->language->get('text_profile_rate');
            $months_text    = $this->language->get('text_months');
            $month_text     = $this->language->get('text_month');

            $plans_html = "<div class='emi_carousel'>";
            if ( ! empty( $all_plans ) ) {
                $plans_html .= $pay_full_payment;
                $baseCurrency                    = $this->amazonpspaymentservices->getBaseCurrency();
                $orderCurrency                   = $this->getOrderCurrencyCode($order);  
                $currency                        = $this->amazonpspaymentservices->getGatewayCurrencyCode($baseCurrency, $orderCurrency);
                $currency       = strtoupper($currency);

                foreach ( $all_plans as $key => $plan ) {
                    $interest    = $this->amazonpspaymentservices->convert_dec_amount( $plan['fee_display_value'], $currency );
                    $interest_info = $interest . ( 'Percentage' === $plan['fees_type'] ? '%' : '' ) . ' ' . $interest_text;

                    $plans_html .= "<div class='slide'>
                        <div class='emi_box' data-interest ='" . $interest_info . "' data-amount='" . $plan['amountPerMonth'] . "' data-plan-code='" . $plan['plan_code'] . "' data-issuer-code='" . $response['installment_data']['issuer_code'] . "' >
                            <p class='installment'>" . $plan['number_of_installment'] .$months_text ."</p>
                            <p class='emi'><strong>" . ( $plan['amountPerMonth'] ) . '</strong> ' . $plan['currency_code'] . "/".$month_text."</p>                           
                            <p class='int_rate'>" . $interest . ( 'Percentage' === $plan['fees_type'] ? '%' : '' ) . ' ' . $interest_text . '</p>
                        </div>
                    </div>';
                }
            }
            $plans_html .= '</div>';
            //Plan info
            $terms_url          = $response['installment_data'][ 'terms_and_condition_' . $this->amazonpspaymentservices->getLanguage() ];
            $processing_content = $response['installment_data'][ 'processing_fees_message_' . $this->amazonpspaymentservices->getLanguage() ];
            $issuer_text        = $response['installment_data'][ 'issuer_name_' . $this->amazonpspaymentservices->getLanguage() ];
            $issuer_logo        = $response['installment_data'][ 'issuer_logo_' . $this->amazonpspaymentservices->getLanguage() ];

            $terms_text         = '';
            if ( $this->amazonpspaymentservices->getInstallmentsIssuerLogo() ) {
                $terms_text .= "<img src='" . $issuer_logo . "' class='issuer-logo'/>";
            }

            $terms_text        .= sprintf($this->language->get('text_installment_agree'), $terms_url);
            $plan_info          = '<input type="checkbox" name="installment_term" id="installment_term" required/>' . $terms_text;
            $plan_info         .= '<div><label class="aps_installment_terms_error aps_error"></label></div>';
            $plan_info         .= '<p> ' . $processing_content . '</p>';

            $issuer_info = '';
            if (  $this->amazonpspaymentservices->getInstallmentsIssuerName() ) {
                $issuer_info .= "<div class='issuer_info'> <p> " .$this->language->get('text_issuer_name')." : ". $issuer_text . '</p> </div>';
            }

            $retarr['plans_html'] = $plans_html;
            $retarr['plan_info']  = $plan_info;
            $retarr['issuer_info']     = $issuer_info;
            $retarr['confirmation_en'] = $response['installment_data']['confirmation_message_en'];
            $retarr['confirmation_ar'] = $response['installment_data']['confirmation_message_ar'];
        } else {
            $retarr['status']  = 'error';
            $retarr['message'] = $response['message'];
        }
        return $retarr;
    }
   
   private function get_valu_products_data() {
        $products      = array();
        $product_name  = '';
        $category_name = '';

        $orderId = $this->getSessionOrderId();
        $order   = $this->loadOrder($orderId);
	
        $order_products = $this->model_checkout_order->getOrderProducts($orderId);

        $currency      = $this->amazonpspaymentservices->getGatewayCurrencyCode();
        foreach ($order_products as $product) {
            $product_name       = $this->amazonpspaymentservices->clean_string( $product['name'] );
            $product_id         = $product['product_id'];
            $categories = $this->model_catalog_product->getCategories($product_id);
            foreach ($categories as $category) {
                $category_info = $this->model_catalog_category->getCategory($category['category_id']);
                $category_name = $this->amazonpspaymentservices->clean_string($category_info['name']);
                break;
            }
            break;
        }
        $amount = $this->amazonpspaymentservices->convertGatewayAmount($this->getOrderTotal($order), $this->getOrderCurrencyValue($order), $currency);
        if ( count( $order_products ) > 1 ) {
            $products[] = array(
                'product_name'     => 'MutipleProducts',
                'product_price'    => $amount,
                'product_category' => $category_name,
            );
        } else {
            $products[] = array(
                'product_name'     => $product_name,
                'product_price'    => $amount,
                'product_category' => $category_name,
            );
        }
        return $products;
    }

    /**
     * Valu verify customer
     *
     * @return array
     */
    public function valu_verify_customer( $mobile_number, $down_payment, $tou, $cashback ) {
        $this->language->load('extension/payment/amazon_ps');

        $status  = 'success';
        $message = $this->language->get('customer_verfied');
        $orderId = $this->getSessionOrderId();
        try {
            $reference_id                = $orderId.hexdec(bin2hex(openssl_random_pseudo_bytes(5)));
            $gatewayParams              = array(
                'merchant_identifier' => $this->amazonpspaymentservices->getMerchantIdentifier(),
                'access_code'         => $this->amazonpspaymentservices->getAccessCode(),
                'language'            => $this->amazonpspaymentservices->getLanguage(),
                'service_command'     => 'CUSTOMER_VERIFY',
                'payment_option'      => 'VALU',
                'merchant_reference'  => $reference_id,                
                'phone_number'        => $mobile_number,                
            );
            $signature = $this->amazonpspaymentservices->calculateSignature( $gatewayParams, 'request' );
            $gatewayParams['signature'] = $signature;
            //execute post
            $gatewayUrl = $this->amazonpspaymentservices->getGatewayUrl('notificationApi');
            $this->amazonpspaymentservices->log('Customer verfiy Request Params : '.$gatewayUrl . json_encode($gatewayParams));

            $response = $this->callApi($gatewayParams, $gatewayUrl);
            $this->amazonpspaymentservices->log("Valu verfiy customer response " . json_encode($response));

            $valuapi_stop_message = $this->language->get('valu_api_failed'); 
            if ( isset( $response['status'] ) && AmazonPSConstant::AMAZON_PS_VALU_CUSTOMER_VERIFY_SUCCESS_RESPONSE_CODE === $response['response_code'] ) {
                $this->session->data['amazon_ps_valu']['reference_id']  = $reference_id;
                $this->session->data['amazon_ps_valu']['mobile_number'] = $mobile_number;
                $this->session->data['amazon_ps_valu']['down_payment'] = $down_payment;
                $this->session->data['amazon_ps_valu']['tou'] = $tou;
                $this->session->data['amazon_ps_valu']['cashback'] = $cashback;
            } elseif ( isset( $response['response_code'] ) && AmazonPSConstant::AMAZON_PS_VALU_CUSTOMER_VERIFY_FAILED_RESPONSE_CODE === $response['response_code'] ) {
                $status  = 'error';
                $message = isset( $response['response_message'] ) && ! empty( $response['response_message'] ) ? $this->language->get('customer_not_exist') : $valuapi_stop_message;
                if(isset($this->session->data['amazon_ps_valu'])){
                    unset( $this->session->data['amazon_ps_valu'] );
                }
            } else {
                $status  = 'error';
                $message = isset( $response['response_message'] ) && ! empty( $response['response_message'] ) ? $response['response_message'] : $valuapi_stop_message;
                if(isset($this->session->data['amazon_ps_valu'])){
                    unset( $this->session->data['amazon_ps_valu'] );
                }
            }
        } catch ( Exception $e ) {
            $status  = 'error';
            $message = $this->language->get('technical_error');
        }
        $response_arr = array(
            'status'  => $status,
            'message' => $message,
        );
        return $response_arr;
    }

    /**
     * Valu generate OTP
     *
     * @return array
     */
    public function valu_generate_otp( $mobile_number, $reference_id, $down_payment, $tou, $cashback ) {
        $this->language->load('extension/payment/amazon_ps');
        if($this->amazonpspaymentservices->getValuDownPaymentStatus()) {
            if (empty($down_payment) || $down_payment == "") {
                $down_payment = $this->amazonpspaymentservices->getValuDownPaymentValue();
            }
        }else{$down_payment=0;}
        $status  = 'success';
        $message = $this->language->get('otp_generated');
        $tenure_html = '';
        try {
            $orderId                  = $this->getSessionOrderId();
            $order                    = $this->loadOrder($orderId);
            $products                 = $this->get_valu_products_data();
            $currency                 = $this->amazonpspaymentservices->getGatewayCurrencyCode();
            $language                 = $this->amazonpspaymentservices->getLanguage();
            $gatewayParams            = array(
                'merchant_identifier' => $this->amazonpspaymentservices->getMerchantIdentifier(),
                'access_code'         => $this->amazonpspaymentservices->getAccessCode(),
                'language'            => $language,
                'merchant_reference'  => $reference_id,
                'payment_option'      => 'VALU',
                'service_command'     => 'OTP_GENERATE',
                'merchant_order_id'   => $orderId,
                'phone_number'        => $mobile_number,
                'amount'              => $this->amazonpspaymentservices->convertGatewayAmount($this->getOrderTotal($order), $this->getOrderCurrencyValue($order), $currency),
                'currency'            => $currency,
                'products'            => $products[0],
                'total_downpayment'  =>intval($down_payment)*100,
                'wallet_amount'       =>intval($tou)*100,
                'cashback_wallet_amount' =>intval($cashback)*100,
                "include_installments" =>"YES"
            );

            if ($down_payment + $tou + $cashback > $this->amazonpspaymentservices->convertGatewayAmount($this->getOrderTotal($order), $this->getOrderCurrencyValue($order), $currency) / 100) {

                $message = $this->language->get('error_downpayment_cashback_tou_amount');
                $status = 'error';

                throw new Exception( $message);
            }
                     
            $signature = $this->amazonpspaymentservices->calculateSignature( $gatewayParams, 'request' );
            $gatewayParams['signature'] = $signature;
            $gatewayParams['products'] = $products;
            //execute post
            $gatewayUrl = $this->amazonpspaymentservices->getGatewayUrl('notificationApi');
            $this->amazonpspaymentservices->log('valu OTP generate Request : '.$gatewayUrl . print_r($gatewayParams, 1));

            $response = $this->callApi($gatewayParams, $gatewayUrl);
            $this->amazonpspaymentservices->log("valu OTP generate response " . json_encode($response));
            $valuapi_stop_message = $this->language->get('valu_api_failed');


            if ( isset( $response['response_code'] ) && AmazonPSConstant::AMAZON_PS_VALU_OTP_GENERATE_SUCCESS_RESPONSE_CODE === $response['response_code'] ) {
                $status   = 'success';

                $mobile_number  = AmazonPSConstant::AMAZON_PS_VALU_EG_COUNTRY_CODE.$mobile_number;
                if($language == 'ar'){
                    $mobile_number = str_replace("+", "", $mobile_number)."+";
                }

                $message  = sprintf($this->language->get('text_otp_sent_to_mobile'), $mobile_number);
                $this->session->data['amazon_ps_valu']['order_id']       = $orderId;
                $this->session->data['amazon_ps_valu']['transaction_id'] = $response['merchant_order_id'];
            } else {
                $status  = 'genotp_error';
                $message = isset( $response['response_message'] ) && ! empty( $response['response_message'] ) ? $response['response_message'] : $valuapi_stop_message;
                if(isset($this->session->data['amazon_ps_valu'])){
                    unset( $this->session->data['amazon_ps_valu'] );
                }
            }
            if ( isset( $response['response_code'] ) && AmazonPSConstant::AMAZON_PS_VALU_OTP_GENERATE_SUCCESS_RESPONSE_CODE === $response['response_code'] ) {
                //$this->session->data['amazon_ps_valu']['otp'] = $otp;
                $status                          = 'success';
                $message                         = $this->language->get('valu_otp_verified');
                $tenure_html                     = "<div class='tenure_carousel'>";
                if ( isset( $response['installment_detail']['plan_details'] ) ) {
                    foreach ( $response['installment_detail']['plan_details'] as $key => $ten ) {
                        $tenure_html .= "<div class='slide'>
                                <div class='tenureBox' data-tenure='" . $ten['number_of_installments'] . "' data-tenure-amount='" . $ten['amount_per_month'] ."' data-tenure-admin-fee='" . $ten['fees_amount'] ."'>
                                    <p class='tenure'>" . $ten['number_of_installments'] ." ".$this->language->get('text_months')."</p>
                                    <p class='emi'><strong>" . ( number_format($ten['amount_per_month']/100,2,'.','') ) . "</strong> EGP/".$this->language->get('text_month')."</p>
                                    <p class='admin_fees'><strong class='alert-success'>" .$this->language->get('Admin Fee')."</strong>"." ".( number_format($ten['fees_amount']/100,2,'.','') )."</p>

                                </div>
                            </div>";
                    }
                }
                $tenure_html .= '</div>';
            } else {
                $status  = 'error';
                $message = isset( $response['response_message'] ) && ! empty( $response['response_message'] ) ? $response['response_message'] : $valuapi_stop_message;
            }
        } catch ( Exception $e ) {
            $status  = 'error';
            //$message = $this->language->get('technical_error');
        }
        $response_arr = array(
            'status'  => $status,
            'message' => $message,
            'tenure_html' => $tenure_html,
        );
        return $response_arr;
    }

    /**
     * Valu verify OTP
     *
     * @return array
     */
    public function valu_verfiy_otp( $mobile_number, $reference_id, $otp ) {
        $this->language->load('extension/payment/amazon_ps');
        $status      = '';
        $message     = '';
        $tenure_html = '';
        try {
            $orderId = $this->getSessionOrderId();
            $order   = $this->loadOrder($orderId);
            $currency                    = $this->amazonpspaymentservices->getGatewayCurrencyCode();

            $gatewayParams              = array(
                'merchant_identifier' => $this->amazonpspaymentservices->getMerchantIdentifier(),
                'access_code'         => $this->amazonpspaymentservices->getAccessCode(),
                'language'            => $this->amazonpspaymentservices->getLanguage(),
                'service_command'     => 'OTP_VERIFY',
                'payment_option'      => 'VALU',
                'merchant_reference'  => $reference_id,                
                'phone_number'        => $mobile_number,
                'amount'              => $this->amazonpspaymentservices->convertGatewayAmount($this->getOrderTotal($order), $this->getOrderCurrencyValue($order), $currency),
                'merchant_order_id'   => $orderId,
                'currency'            => $currency,
                'otp'                 => $otp,
                'total_downpayment'   => 0,
            );
            $signature = $this->amazonpspaymentservices->calculateSignature( $gatewayParams, 'request' );
            $gatewayParams['signature'] = $signature;
            //execute post
            $gatewayUrl = $this->amazonpspaymentservices->getGatewayUrl('notificationApi');
            $this->amazonpspaymentservices->log('valu OTP verify Request Params : '.$gatewayUrl . print_r($gatewayParams, 1));

            $response = $this->callApi($gatewayParams, $gatewayUrl);
            $this->amazonpspaymentservices->log("Valu OTP verify response " . json_encode($response));

            $valuapi_stop_message = $this->language->get('valu_api_failed');

//            if ( isset( $response['response_code'] ) && AmazonPSConstant::AMAZON_PS_VALU_OTP_VERIFY_SUCCESS_RESPONSE_CODE === $response['response_code'] ) {
//                $this->session->data['amazon_ps_valu']['otp'] = $otp;
//                $status                          = 'success';
//                $message                         = $this->language->get('valu_otp_verified');
//                $tenure_html                     = "<div class='tenure_carousel'>";
//                if ( isset( $response['tenure']['TENURE_VM'] ) ) {
//                    foreach ( $response['tenure']['TENURE_VM'] as $key => $ten ) {
//                        $tenure_html .= "<div class='slide'>
//                                <div class='tenureBox' data-tenure='" . $ten['TENURE'] . "' data-tenure-amount='" . $ten['EMI'] . "' data-tenure-interest='" . $ten['InterestRate'] . "' >
//                                    <p class='tenure'>" . $ten['TENURE'] ." ".$this->language->get('text_months')."</p>
//                                    <p class='emi'><strong>" . ( $ten['EMI'] ) . "</strong> EGP/".$this->language->get('text_month')."</p>
//                                    <p class='int_rate'>" . $ten['InterestRate'] . "% ".$this->language->get('text_interest')."</p>
//                                </div>
//                            </div>";
//                    }
//                }
//                $tenure_html .= '</div>';
//            } else {
//                $status  = 'error';
//                $message = isset( $response['response_message'] ) && ! empty( $response['response_message'] ) ? $response['response_message'] : $valuapi_stop_message;
//            }
        } catch ( Exception $e ) {
            $status  = 'error';
            $message = $this->language->get('technical_error');
        }
        return array(
            'status'      => $status,
            'message'     => $message,
            'tenure_html' => $tenure_html,
        );
    }

    /**
     * Valu generate OTP
     *
     * @return array
     */
    public function valu_execute_purchase( $mobile_number, $reference_id, $otp, $transaction_id , $active_tenure, $down_payment, $tou, $cashback) {
        $this->language->load('extension/payment/amazon_ps');
        $status  = 'success';
        $message = '';
        $order   = '';
        if($this->amazonpspaymentservices->getValuDownPaymentStatus()) {
            if (empty($down_payment) || $down_payment == "") {
                $down_payment = $this->amazonpspaymentservices->getValuDownPaymentValue();;
            }
        }else{$down_payment=0;}
        try {

            $orderId = $this->getSessionOrderId();
            $order   = $this->loadOrder($orderId);
            $currency                    = $this->amazonpspaymentservices->getGatewayCurrencyCode();

            $gatewayParams = array(
                'merchant_identifier' => $this->amazonpspaymentservices->getMerchantIdentifier(),
                'access_code'         => $this->amazonpspaymentservices->getAccessCode(),
                'language'            => $this->amazonpspaymentservices->getLanguage(),
                'command'              => 'PURCHASE',                
                'payment_option'       => 'VALU',
                'merchant_reference'   => $reference_id,
                'phone_number'         => $mobile_number,
                'amount'               => $this->amazonpspaymentservices->convertGatewayAmount($this->getOrderTotal($order), $this->getOrderCurrencyValue($order), $currency),
                'merchant_order_id'    => $orderId,
                'currency'             => strtoupper( $currency ),
                'otp'                  => $otp,
                'tenure'               => $active_tenure,
                'total_down_payment'   => intval($down_payment)*100,
                'wallet_amount'        =>intval($tou)*100,
                'cashback_wallet_amount' =>intval($cashback)*100,
                'customer_code'        => $mobile_number,
                'customer_email'       => $this->getOrderEmail($order),
                'purchase_description' => 'Order' . $orderId,
                'transaction_id'       => $transaction_id,
            );

            if(empty($otp)){

                $status  = 'error';
                $message = $this->language->get('empty_otp');
                throw new \Exception( $message );
            }
            if ($down_payment + $tou + $cashback > $this->amazonpspaymentservices->convertGatewayAmount($this->getOrderTotal($order), $this->getOrderCurrencyValue($order), $currency) / 100) {

                $message = $this->language->get('error_downpayment_cashback_tou_amount');
                $status = 'error';

                throw new \Exception($message);

            }

            $plugin_params  = $this->amazonpspaymentservices->plugin_params();
            $gatewayParams  = array_merge( $gatewayParams, $plugin_params );

            $signature = $this->amazonpspaymentservices->calculateSignature( $gatewayParams, 'request' );
            $gatewayParams['signature'] = $signature;
            //execute post
            $gatewayUrl = $this->amazonpspaymentservices->getGatewayUrl('notificationApi');
            $this->amazonpspaymentservices->log('valu purchase Request : '.$gatewayUrl . print_r($gatewayParams, 1));

            $response = $this->callApi($gatewayParams, $gatewayUrl);
            $this->amazonpspaymentservices->log("Valu purchase response " . json_encode($response));

            $valuapi_stop_message = $this->language->get('valu_api_failed');
            if ( isset( $response['response_code'] ) && AmazonPSConstant::AMAZON_PS_PAYMENT_SUCCESS_RESPONSE_CODE === $response['response_code'] ) {
                $status  = 'success';
                $message = $this->language->get('valu_transaction_success');
                $this->successOrder($order, $response, 'online');
            } else {
                $status  = 'error';
                $message = isset( $response['response_message'] ) && ! empty( $response['response_message'] ) ? $response['response_message'] : $valuapi_stop_message;
                $this->declineOrder($order, $response, $message);
                throw new Exception( $message );

            }
            unset( $this->session->data['amazon_ps_valu'] );
        } catch ( Exception $e ) {
            $status  = 'error';
            $message = $this->language->get('technical_error')." : ".  $e->getMessage();
            // if order payment processed then return success
            if (in_array($this->getOrderStatusId($order), [AmazonPSConstant::PROCESSING_ORDER_STATUS_ID, AmazonPSConstant::SHIPPED_ORDER_STATUS_ID, AmazonPSConstant::COMPLETE_ORDER_STATUS_ID, AmazonPSConstant::PROCESSED_ORDER_STATUS_ID, AmazonPSConstant::REFUNDED_ORDER_STATUS_ID])){
                $status   = 'success';
                unset($this->session->data['error']);
            }
        }
        return array(
            'status'  => $status,
            'message' => $message,
            'valu_transaction_id'=>$response['valu_transaction_id'],
            'loan_number'=>$response['loan_number']
        );
    }
	
	
	
	
	/**
     * amazon_ps_order file
     *
     * @return order details
     */
	public function loadOrder($orderId)
    {
        $this->order = $this->getOrderById($orderId);
        return $this->order;
    }

    public function getSessionOrderId()
    {
        $order_id = 0;
        if (isset($this->session->data['order_id'])) {
            return $this->session->data['order_id'];
        }
        return $order_id;
    }
    
    public function getOrderId($order)
    {
        return isset($order['order_id']) ? $order['order_id'] : 0;
    }

    public function getOrderById($orderId)
    {
        if($orderId){
            //$this->load->model('catalog/checkout/order');
		
            return $this->model_checkout_order->getOrder($orderId);
        }
        return;
    }

    public function getOrderEmail($order)
    {
        return isset($order['email']) ? $order['email'] : '';
    }

    public function getCustomerName($order)
    {
        $fullName  = '';
        $firstName = isset($order['payment_firstname']) ? $order['payment_firstname'] : '';
        $lastName  = isset($order['payment_lastname']) ? $order['payment_lastname'] : '';

        $fullName = trim($firstName . ' ' . $lastName);
        return $fullName;
    }

    public function getOrderCurrencyCode($order)
    {
        return isset($order['currency_code']) ? $order['currency_code'] : '';
    }

    public function getOrderCurrencyValue($order)
    {
        return isset($order['currency_value']) ? $order['currency_value'] : 0;
    }

    public function getOrderTotal($order)
    {
        return isset($order['total']) ? $order['total'] : 0;
    }

    public function getPaymentMethod($order){
        return isset($order['payment_code']) ? $order['payment_code'] : '';
    }
    
    public function getOrderStatusId($order){
        return isset($order['order_status_id']) ? $order['order_status_id'] : 0;
    }
    
    public function updateOrderStatus($orderId, $statusId, $comment){
        //$this->load->model('catalog/checkout/order');
		
        $this->model_checkout_order->addOrderHistory($orderId, $statusId, $comment, false);
    }

    public function getOrderLanguage($language_id){
        $language_info = $this->model_localisation_language->getLanguage($language_id);

        if ($language_info) {
            $language_code = $language_info['code'];
        } else {
            $language_code = $this->config->get('config_language');
        }
        if(strpos($language_code, 'ar')!==false){
            return 'ar';
        }
        return 'en';
    }
    
    public function declineOrder($order, $response_params, $reason) 
    {
        if ( isset( $response_params['service_command'] ) && AmazonPSConstant::AMAZON_PS_COMMAND_TOKENIZATION === $response_params['service_command'] ) {
            $this->failed_order($order, $reason );
        } elseif ( isset( $response_params['payment_option'] ) && in_array( $response_params['payment_option'], AmazonPSConstant::AMAZON_PS_RETRY_PAYMENT_OPTIONS, true ) ) {
            $this->failed_order($order, $reason );
        } elseif ( isset( $response_params['digital_wallet'] ) && in_array( $response_params['digital_wallet'], AmazonPSConstant::AMAZON_PS_RETRY_DIGITAL_WALLETS, true ) ) {
            $this->failed_order($order, $reason );
        } elseif ( isset( $response_params['response_code'] ) && in_array( $response_params['response_code'], AmazonPSConstant::AMAZON_PS_FAILED_RESPONSE_CODES, true ) ) {
            if ( isset( $response_params['payment_option'] ) && in_array( $response_params['payment_option'], AmazonPSConstant::AMAZON_PS_RETRY_PAYMENT_OPTIONS, true ) ) {
                $this->failed_order($order, $reason );
            } else {
                $this->cancelOrder($order, $reason );
            }
        } else {
            $this->cancelOrder($order, $reason );
        }
        return true;
    }

    public function onHoldOrder( $order, $reason ){
        $status = AmazonPSConstant::PENDING_ORDER_STATUS_ID;  //pending order
        if($this->getOrderStatusId($order) == $status) {
            return true;
        }
        if($this->getOrderId($order)) {
            $this->updateOrderStatus($this->getOrderId($order), $status, 'Payment Pending'.$reason);
        }
        return true;
    }

    public function failed_order($order, $reason){
        $status = AmazonPSConstant::FAILED_ORDER_STATUS_ID;
        if($this->getOrderStatusId($order) == $status) {
            return true;
        }
        // Don't failed order if already payment success
        if ( in_array($this->getOrderStatusId($order), [AmazonPSConstant::PROCESSING_ORDER_STATUS_ID, AmazonPSConstant::SHIPPED_ORDER_STATUS_ID, AmazonPSConstant::COMPLETE_ORDER_STATUS_ID, AmazonPSConstant::PROCESSED_ORDER_STATUS_ID, AmazonPSConstant::REFUNDED_ORDER_STATUS_ID]) ) {
            return true;
        }
        if($this->getOrderId($order)) {
            $this->updateOrderStatus($this->getOrderId($order), $status, 'Payment Failed '.$reason);
            // update recurring order and make recurring order cancel
            $this->updateRecurringOrder($order['order_id'], false);
        }
        return true;

    }
    
    public function cancelOrder($order, $reason=null) 
    {
        $status = AmazonPSConstant::CANCEL_ORDER_STATUS_ID;
        if($this->getOrderStatusId($order) == $status) {
            return true;
        }
        // Don't cancelled order if already payment success
        if ( in_array($this->getOrderStatusId($order), [AmazonPSConstant::PROCESSING_ORDER_STATUS_ID, AmazonPSConstant::SHIPPED_ORDER_STATUS_ID, AmazonPSConstant::COMPLETE_ORDER_STATUS_ID, AmazonPSConstant::PROCESSED_ORDER_STATUS_ID, AmazonPSConstant::REFUNDED_ORDER_STATUS_ID]) ) {
            return true;
        }

        if($this->getOrderId($order)) {
            $this->updateOrderStatus($this->getOrderId($order), $status, 'Payment Canceled '.$reason);
            // update recurring order and make recurring order cancel
            $this->updateRecurringOrder($order['order_id'], false);
        }
        return true;
    }

    public function successOrder($order, $response_params, $response_mode) 
    {
        $this->amazonpspaymentservices->log( 'success order '.$response_mode );
        $status = AmazonPSConstant::PROCESSING_ORDER_STATUS_ID;
        if($this->getOrderStatusId($order) == $status) {
            if(isset($response_params['token_name'])) {
                $this->aps_token->saveApsTokens($order, $response_params, $response_mode );
            }
            return true;
        }
        $orderId = $this->getOrderId($order);
        if($orderId) {
            if ( 'online' === $response_mode ) {
                
            }
            if ( ! empty( $response_params ) ) {
               $meta_id =  $this->aps_model->updateAmazonPSMetaData($orderId, 'amazon_ps_payment_response', $response_params, true);
               $this->amazonpspaymentservices->log( 'success order #'.$orderId.' meta updated id'.$meta_id );
            }
            if(isset($response_params['token_name'])) {
                $this->aps_token->saveApsTokens($order, $response_params, $response_mode );
            }

            $this->updateOrderStatus($this->getOrderId($order), $status, 'Paid: Reference ID ' . $response_params['fort_id']);
            $this->amazonpspaymentservices->log( 'success order status updated #'.$orderId.'--'.$response_mode );
            // update recurring order and make first recurring transaction
            $this->updateRecurringOrder($order['order_id']);
        }
        return true;
    }


    /*
    Amazon payment services recurring order related function
    */
        /**
     * Process recurring payment
     */
    public function process_recurring_payment( $order_id, $recurring_id ,$recurring_amount, $reference_id ) {

        $order   = $this->loadOrder($order_id);
        $aps_response = $this->aps_model->getAmazonPSMetaValue($order_id, 'amazon_ps_payment_response', true);
        $currency               = $order['currency_code'];
        $language               = $this->getOrderLanguage($order['language_id']);
        $gateway_params         = array(
            'merchant_identifier' => $this->amazonpspaymentservices->getMerchantIdentifier(),
            'access_code'         => $this->amazonpspaymentservices->getAccessCode(),
            'command'             => AmazonPSConstant::AMAZON_PS_COMMAND_PURCHASE,  

            'merchant_reference'  => $reference_id,
            'amount'              => $this->amazonpspaymentservices->convertGatewayAmount($recurring_amount, $this->getOrderCurrencyValue($order), $currency),
            'currency'            => strtoupper( $currency ),
            'customer_ip'         => $this->amazonpspaymentservices->getCustomerIp(),
            'language'            => $language,
            'customer_email'      => $this->getOrderEmail($order),
            'eci'                 => AmazonPSConstant::AMAZON_PS_COMMAND_RECURRING,
            'token_name'          => $aps_response['token_name'],
            'return_url'          => $this->amazonpspaymentservices->getReturnUrl('amazon_ps/responseOnline')
        );


        $customerName = $this->getCustomerName($order);
        if (!empty($customerName)) {
            $gateway_params['customer_name'] = $customerName;
        }
            
        //calculate request signature
        $signature             = $this->amazonpspaymentservices->calculateSignature($gateway_params, 'request');
        $gateway_params['signature'] = $signature;

        $gatewayUrl = $this->amazonpspaymentservices->getGatewayUrl('notificationApi');
        $this->amazonpspaymentservices->log('Recurring Payment Api Request Params : '.$gatewayUrl . print_r($gateway_params, 1));

        $response = $this->callApi($gateway_params, $gatewayUrl);

        $this->amazonpspaymentservices->log( 'APS recurring response \n\n' . print_r( $response, true ) );
        return $response;
    }
	
    public function createRecurringOrder($order, $payment_method){
        $this->language->load('extension/payment/amazon_ps');
        $this->amazonpspaymentservices->log("create recurringOrder ".$payment_method);
        //only credit/debit cart support recurring order payment
        $orderId = $this->getOrderId($order);
        if(! $orderId){
            $this->amazonpspaymentservices->log("recurringOrder order id :".$orderId);
            return;
        }
        if($payment_method == AmazonPSConstant::AMAZON_PS_PAYMENT_METHOD_CC){
            if($this->cart->hasRecurringProducts()){
                $this->amazonpspaymentservices->log("Order has recurringOrder" );

                foreach ($this->cart->getRecurringProducts() as $item) {

                    if ($item['recurring']['trial']) {
                        $trial_price = $this->tax->calculate($item['recurring']['trial_price'] * $item['quantity'], $item['tax_class_id']);
                        $trial_amt = $this->currency->format($trial_price, $this->getOrderCurrencyCode($order));
                        $trial_text =  sprintf($this->language->get('text_trial_desc'), $trial_amt, $item['recurring']['trial_cycle'], $item['recurring']['trial_frequency'], $item['recurring']['trial_duration']);

                        $item['recurring']['trial_price'] = $trial_price;
                    } else {
                        $trial_text = '';
                    }

                    $recurring_price = $this->tax->calculate($item['recurring']['price'] * $item['quantity'], $item['tax_class_id']);
                    $recurring_amt = $this->currency->format($recurring_price, $this->getOrderCurrencyCode($order));
                    $recurring_description = $trial_text . sprintf($this->language->get('text_recurring_desc'), $recurring_amt, $item['recurring']['cycle'], $item['recurring']['frequency']);

                    $item['recurring']['price'] = $recurring_price;

                    if ($item['recurring']['duration'] > 0) {
                        $recurring_description .= sprintf($this->language->get('text_recurring_length_desc'), $item['recurring']['duration']);
                    }

                    if (!$item['recurring']['trial']) {
                        // We need to override this value for the proper calculation in updateRecurringExpired
                        $item['recurring']['trial_duration'] = 0;
                    }
                   $this->amazonpspaymentservices->log("recurringOrder order id error".$orderId.$recurring_description);
                   if (version_compare(VERSION, '3.0.3.7') < 0) {
                        $item = array_merge($item, $item['recurring']);
                   }
                   $this->amazonpspaymentservices->log(print_r($item, 1),"recurringOrder Item");
                   $order_recurring_id = $this->model_checkout_recurring->addRecurring($orderId, $recurring_description, $item);
                }
            }
        }
    }

    public function updateRecurringOrder($orderId, $success = true){
        /*$this->load->model('checkout/recurring');
        $this->load->model('account/recurring');*/

        $recurring_orders = $this->aps_model->getRecurringByOrderId($orderId);

        foreach ($recurring_orders as $recurring) {
            $order_recurring_id = $recurring['order_recurring_id'];

            $this->amazonpspaymentservices->log("recurringOrder recurring order id".$order_recurring_id);

            //do first recurring transaction for each recurring order
            if($success == true){
                $is_first_transaction = true;
                $this->doRecurringOrderTransaction($recurring, $is_first_transaction);
            }else{
                $this->model_account_recurring->editOrderRecurringStatus($order_recurring_id, AmazonPSConstant::RECURRING_CANCELLED);
            }            
        }
    }

    public function doRecurringOrderTransaction($recurring, $is_first_transaction = false){
        $this->amazonpspaymentservices->log("doRecurringOrderTransaction");
        
        $order_recurring_id = $recurring['order_recurring_id'];
        $order_id = $recurring['order_id'];

        $price = (float)($recurring['trial'] ? $recurring['trial_price'] : $recurring['recurring_price']);
        $amount = $price * $recurring['product_quantity'];

        try {
            if ($amount != 0) {

                $status = AmazonPSConstant::TRANSACTION_DATE_ADDED;
                $reference =  $order_recurring_id.hexdec(bin2hex(openssl_random_pseudo_bytes(5)));

                //add recurring transaction with date_added type before process payment
                $this->aps_model->addRecurringTransaction($order_recurring_id, $reference, $amount, $status);

                //make payment of recurring transaction
                $response = $this->process_recurring_payment($order_id, $order_recurring_id, $amount, $reference );

                if ( AmazonPSConstant::AMAZON_PS_PAYMENT_SUCCESS_RESPONSE_CODE === $response['response_code'] ) {

                    $status = AmazonPSConstant::TRANSACTION_PAYMENT;
                    //update recurring transaction status
                    $this->aps_model->updateRecurringTransaction($reference, $status);
                }                   
            } else {
                // if transaction amount 0 directly create recurring transaction
                $amount = 0;
                $reference = '';
                $status = AmazonPSConstant::TRANSACTION_PAYMENT;
                $this->aps_model->addRecurringTransaction($order_recurring_id, $reference, $amount, $status);
            }

            $trial_expired = false;
            $recurring_expired = false;
            $profile_suspended = false;

            if ($status == AmazonPSConstant::TRANSACTION_PAYMENT) {
                // update recurring order status to active for first transaction
                if($is_first_transaction){                        
                    $this->model_account_recurring->editOrderRecurringStatus($order_recurring_id, AmazonPSConstant::RECURRING_ACTIVE);
                }

                $trial_expired = $this->aps_model->updateRecurringTrial($order_recurring_id);

                $recurring_expired = $this->aps_model->updateRecurringExpired($order_recurring_id);
            } else {
                // Transaction was not successful. Suspend the recurring profile.
                $profile_suspended = $this->aps_model->suspendRecurringProfile($order_recurring_id);
            }

            $order = $this->model_checkout_order->getOrder($order_id);

            $order_status_id = $order['order_status_id'];
            $this->amazonpspaymentservices->log("recurringOrderTransaction order_status_id \n".$order_status_id);
            if ($order_status_id) {
                if ($amount != 0) {
                    $order_status_comment =  $status;
                } else {
                    $order_status_comment = '';
                }

                if ($profile_suspended) {
                    $order_status_comment .= $this->language->get('text_amazon_ps_profile_suspended');
                }

                if ($trial_expired) {
                    $order_status_comment .= $this->language->get('text_amazon_ps_trial_expired');
                }

                if ($recurring_expired) {
                    $order_status_comment .= $this->language->get('text_amazon_ps_recurring_expired');
                }
            $this->amazonpspaymentservices->log("recurringOrderTransaction order_status_comment \n".$order_id.'==='. $order_status_id.'---'.$order_status_comment);
                $this->model_checkout_order->addOrderHistory($order_id, $order_status_id, trim($order_status_comment) );
            }
        } catch (Exception $e) {
            $this->amazonpspaymentservices->log("ERROR : recurring payment \n" .$order_recurring_id. $e->getMessage());
        }                 
    }

    public function doCheckPaymentStatus($order){
        $response = $this->aps_payment_status_checker($order);
        if ( ! empty( $response ) && isset( $response['response_code'] ) ) {
            $response_code    = $response['response_code'];

            $transaction_code = isset($response['transaction_code'])?$response['transaction_code'] : '';
            if ( AmazonPSConstant::AMAZON_PS_CHECK_STATUS_SUCCESS_RESPONSE_CODE === $response_code )
            {
                $this->aps_model->updateAmazonPSMetaData($order['order_id'], 'amazon_ps_check_status_response', $response, true);
                if ( AmazonPSConstant::AMAZON_PS_PAYMENT_SUCCESS_RESPONSE_CODE === $transaction_code || AmazonPSConstant::AMAZON_PS_PAYMENT_AUTHORIZATION_SUCCESS_RESPONSE_CODE === $transaction_code ) {
                    $status = AmazonPSConstant::PROCESSING_ORDER_STATUS_ID;
                    if ( $status !== $order['order_status_id'] ) {
                        $order_note = 'Payment complete by Amazon Payment Services Check status';
                        $this->updateOrderStatus($order['order_id'], $status, $order_note);
                        // update recurring order and make first recurring transaction
                        $this->updateRecurringOrder($order['order_id']);

                    }
                }else {
                    $status = AmazonPSConstant::CANCEL_ORDER_STATUS_ID;
                    if ( $status !== $order['order_status_id'] ) {
                        $order_note = 'Payment cancelled by Amazon Payment Services Check status';
                        $this->updateOrderStatus($order['order_id'], $status, $order_note);
                        // update recurring order and make recurring order cancel
                        $this->updateRecurringOrder($order['order_id'], false);
                    }
                }
            }else if($response_code == AmazonPSConstant::AMAZON_PS_CHECK_STATUS_ORDER_NOT_FOUND_RESPONSE_CODE){
                $status = AmazonPSConstant::CANCEL_ORDER_STATUS_ID;
                if ( $status !== $order['order_status_id'] ) {
                    $order_note = 'Payment cancelled by Amazon Payment Services Check status ('.$response['response_message'].').';
                    $this->updateOrderStatus($order['order_id'], $status, $order_note);
                    // update recurring order and make recurring order cancel
                    $this->updateRecurringOrder($order['order_id'], false);
                }
            } else {
                $order_note = 'Amazon Payment Services Check Failed '.$response['response_message'];
                $this->updateOrderStatus($order['order_id'], $order['order_status_id'], $order_note);
            }
        }
    }

    public function aps_payment_status_checker($order){
        $merchant_reference = $order['order_id'];
        if($order['payment_code'] == AmazonPSConstant::AMAZON_PS_PAYMENT_METHOD_VALU){
            $merchant_reference = $this->aps_model->find_valu_reference_by_order($order['order_id']);
            $this->amazonpspaymentservices->log('Check Status valu reference : '.$order['order_id']."--".$merchant_reference );
            if(empty($merchant_reference) ){
                $merchant_reference = $order['order_id'];
            }
        }

        $access_code = $this->amazonpspaymentservices->getAccessCode();
        $signature_type = 'regular';
        if($order['payment_code'] == AmazonPSConstant::AMAZON_PS_PAYMENT_METHOD_APPLE_PAY){
            $access_code = $this->amazonpspaymentservices->getApplePayAccessCode();
            $signature_type = 'apple_pay';
        }

        $gatewayParams = array(
            'merchant_identifier' => $this->amazonpspaymentservices->getMerchantIdentifier(),
            'access_code'         => $access_code,
            'language'            => $this->getOrderLanguage($order['language_id']),
            'query_command'       => AmazonPSConstant::AMAZON_PS_COMMAND_CHECK_STATUS,
            'merchant_reference'  => $merchant_reference
        );
        
        //calculate request signature
        $signature             = $this->amazonpspaymentservices->calculateSignature($gatewayParams, 'request', $signature_type);
        $gatewayParams['signature'] = $signature;

        $gatewayUrl = $this->amazonpspaymentservices->getGatewayUrl('notificationApi');
        $this->amazonpspaymentservices->log('Check Status Request Params : '.$gatewayUrl . print_r($gatewayParams, 1));

        $response = $this->callApi($gatewayParams, $gatewayUrl);

        $this->amazonpspaymentservices->log('Check Status Response Params : '. print_r($response, 1));
        return $response;
    }

    /**
     * Capture order webhook
     */
    public function capture_order($order,  $responseParams, $responseMode ) {
        $currency = $order['currency_code'];
        $value = $order['currency_value'];
        $amount = $responseParams['amount'];
        //amount convert back to original amount
        $this->amazonpspaymentservices->log( 'APS capture meta update with currency id'.$currency);
        $amount = $this->amazonpspaymentservices->convertGatewayToOrderAmount( $amount, $currency , $value);
        $this->amazonpspaymentservices->log( 'APS capture meta update with amount'.$amount);
        $meta_id = $this->aps_model->updateAmazonPSMetaData( $order['order_id'], AmazonPSConstant::AMAZON_PS_COMMAND_CAPTURE, $amount );
        $this->amazonpspaymentservices->log( 'APS capture meta update with meta id'.$meta_id);
    }

    /**
     * Void order webhook
     */
    public function void_order($order,  $responseParams, $responseMode ) {
        $this->amazonpspaymentservices->log( 'APS void webhook called order#'.$order['order_id']);
        $status = AmazonPSConstant::VOIDED_ORDER_STATUS_ID;
        if($this->getOrderStatusId($order) == $status) {
            $this->amazonpspaymentservices->log( 'Order already void');
            return true;
        }

        $amount = $order['total'];
        $meta_id = $this->aps_model->updateAmazonPSMetaData( $order['order_id'], AmazonPSConstant::AMAZON_PS_COMMAND_VOID_AUTHORIZATION, $amount );
        $this->amazonpspaymentservices->log( 'APS void meta update with meta id'.$meta_id);
        $this->updateOrderStatus($this->getOrderId($order), $status, 'Payment Voided');
        $this->amazonpspaymentservices->log( 'Order #'.$order['order_id'].'voided');
        
    }
    /**
     * Refund order webhook
     */
    public function refund_order($order,  $responseParams, $responseMode ) {
        $status = AmazonPSConstant::REFUNDED_ORDER_STATUS_ID;
        if($this->getOrderStatusId($order) == $status) {
            return true;
        }

        $currency = $order['currency_code'];
        $value = $order['currency_value'];
        //amount convert back to original amount
        $amount = $responseParams['amount']; 
        $amount = $this->amazonpspaymentservices->convertGatewayToOrderAmount( $amount, $currency , $value);       
        $meta_id = $this->aps_model->updateAmazonPSMetaData( $order['order_id'], AmazonPSConstant::AMAZON_PS_COMMAND_REFUND, $amount );

        if ($amount == $order['total']){
            $this->updateOrderStatus($this->getOrderId($order), $status, 'Payment Refunded');
        }else{
            $refund_history = $this->aps_model->getAmazonPSMetaData( $order['order_id'], AmazonPSConstant::AMAZON_PS_COMMAND_REFUND );

            $total_refunded = array_sum( array_column( $refund_history, 'meta_value' ) );
            if ($total_refunded == $order['total']){
                $this->updateOrderStatus($this->getOrderId($order), $status, 'Payment Refunded');
            }
        }

        $this->amazonpspaymentservices->log( 'APS refund meta update with meta id'.$meta_id);
              
    }

    /**
     * Get apple pay order
     */
    public function get_apple_order_data() {
        $apple_order = array(
			'sub_total'      => 0.00,
			'tax_total'      => 0.00,
			'shipping_total' => 0.00,
			'discount_total' => 0.00,
			'grand_total'    => 0.00,
			'order_items'    => array(),
		);
        $orderId        = $this->getSessionOrderId();
        $order          = $this->loadOrder($orderId);
        $baseCurrency   = $this->amazonpspaymentservices->getBaseCurrency();
        $orderCurrency  = $this->getOrderCurrencyCode($order);  
        $currency       = $this->amazonpspaymentservices->getGatewayCurrencyCode($baseCurrency, $orderCurrency);
        $currency_value = $this->getOrderCurrencyValue($order);
        $orderProducts = $this->model_checkout_order->getOrderProducts($orderId);
        $orderTotalData = $this->model_checkout_order->getOrderTotals($orderId);
        
        if( !empty($orderProducts)) {
            foreach( $orderProducts as $item) {
                $apple_order['order_items'][] = array(
					'product_name'     => $item['name'],
					'product_subtotal' => $this->amazonpspaymentservices->convertGatewayAmount($item['total'], $currency_value, $currency, true )
				);
            } 
        }
        if( !empty($orderTotalData)) {
            foreach( $orderTotalData as $item) {
                $code = $item['code']; 
                if( 'sub_total' === $code ) {
                    $apple_order['sub_total'] = $this->amazonpspaymentservices->convertGatewayAmount($item['value'], $currency_value, $currency, true );
                } elseif( 'coupon' === $code ) {
                    $apple_order['discount_total'] = $this->amazonpspaymentservices->convertGatewayAmount($item['value'], $currency_value, $currency, true );
                } elseif( 'shipping' === $code ) {
                    $apple_order['shipping_total'] = $this->amazonpspaymentservices->convertGatewayAmount($item['value'], $currency_value, $currency, true );
                } elseif( 'total' === $code ) {
                    $apple_order['grand_total'] = $this->amazonpspaymentservices->convertGatewayAmount($item['value'], $currency_value, $currency, true );
                }
            } 
        }
        return $apple_order;
    }

    /**
	 * Init apple pay payment
	 */
	public function init_apple_pay_payment( $response_params ) {
		$status   = 'success';
		$orderId = 0;
        $order   = '';
		try {
			$orderId        = $this->getSessionOrderId();
            $order          = $this->loadOrder($orderId);
			$currency       = $this->getOrderCurrencyCode($order);  
			$gateway_params = array(
				'digital_wallet'      => 'APPLE_PAY',
				'command'             => $this->amazonpspaymentservices->getCommand(AmazonPSConstant::AMAZON_PS_PAYMENT_METHOD_APPLE_PAY),
				'merchant_identifier' => $this->amazonpspaymentservices->getMerchantIdentifier(),
				'access_code'         => $this->amazonpspaymentservices->getApplePayAccessCode(),
				'merchant_reference'  => $orderId,
				'language'            => $this->getOrderLanguage($order['language_id']),
				'amount'              => $this->amazonpspaymentservices->convertGatewayAmount( $this->getOrderTotal($order), $this->getOrderCurrencyValue($order), $currency ),
				'currency'            => strtoupper( $currency ),
				'customer_email'      => $this->getOrderEmail($order),
				'apple_data'          => $response_params->data->paymentData->data,
				'apple_signature'     => $response_params->data->paymentData->signature,
				'customer_ip'         => $this->amazonpspaymentservices->getCustomerIp(),
			);
			foreach ( $response_params->data->paymentData->header as $key => $value ) {
				$gateway_params['apple_header'][ 'apple_' . $key ] = $value;
			}
			foreach ( $response_params->data->paymentMethod as $key => $value ) {
				$gateway_params['apple_paymentMethod'][ 'apple_' . $key ] = $value;
			}
			$signature                   = $this->amazonpspaymentservices->calculateSignature( $gateway_params, 'request', 'apple_pay' );
			$gateway_params['signature'] = $signature;

            $gateway_url = $this->amazonpspaymentservices->getGatewayUrl('notificationApi');
            //Apple pay request log
            $this->amazonpspaymentservices->log( 'Apple payment request ' . json_encode( $gateway_params ) );
			
            $response = $this->callApi($gateway_params, $gateway_url);
            //Apple pay response log
            $this->amazonpspaymentservices->log( 'Apple payment response ' . json_encode( $response ) );
			if ( AmazonPSConstant::AMAZON_PS_PAYMENT_SUCCESS_RESPONSE_CODE === $response['response_code'] || AmazonPSConstant::AMAZON_PS_PAYMENT_AUTHORIZATION_SUCCESS_RESPONSE_CODE === $response['response_code'] ) {
				$this->successOrder( $order, $response, 'online' );
				$status = 'success';
			} elseif ( in_array( $response['response_code'], AmazonPSConstant::AMAZON_PS_ONHOLD_RESPONSE_CODES, true ) ) {
				$this->onHoldOrder( $order, $response['response_message'] );
				$aps_error_log = "APS apple pay on hold stage : \n\n" . json_encode( $response, true );
				$this->amazonpspaymentservices->log( $aps_error_log );
				$status = 'success';
			} else {
				$result = $this->declineOrder( $order, $response, $response['response_message'] );
				$status = 'error';
				if ( $result ) {
					throw new Exception( $response['response_message'] );
				}
			}
		} catch ( \Exception $e ) {
			$status                = 'error';
            $this->amazonpspaymentservices->setFlashMsg($e->getMessage());
            // if order payment processed then return success
            if (in_array($this->getOrderStatusId($order), [AmazonPSConstant::PROCESSING_ORDER_STATUS_ID, AmazonPSConstant::SHIPPED_ORDER_STATUS_ID, AmazonPSConstant::COMPLETE_ORDER_STATUS_ID, AmazonPSConstant::PROCESSED_ORDER_STATUS_ID, AmazonPSConstant::REFUNDED_ORDER_STATUS_ID])){
                $status   = 'success';
                unset($this->session->data['error']);
            }
		}
		return array(
			'status'   => $status,
			'order_id' => $orderId,
		);
	}

        /**
     * APS Delete Token
     */
    public function delete_aps_token($token ) {
        $status  = 'success';
        $message = '';
        try{
            $random_key = hexdec(bin2hex(openssl_random_pseudo_bytes(5)));
            $gateway_params              = array(
                'service_command'     => 'UPDATE_TOKEN',
                'merchant_identifier' => $this->amazonpspaymentservices->getMerchantIdentifier(),
                'access_code'         => $this->amazonpspaymentservices->getAccessCode(),
                'merchant_reference'  => $random_key,
                'language'            => $this->amazonpspaymentservices->getLanguage(),
                'token_name'          => $token,
                'token_status'        => 'INACTIVE',
            );
            $signature                   = $this->amazonpspaymentservices->calculateSignature( $gateway_params, 'request' );
            $gateway_params['signature'] = $signature;

            $gateway_url = $this->amazonpspaymentservices->getGatewayUrl('notificationApi');
            //Delete token request log
            $this->amazonpspaymentservices->log( 'APS Delete token request ' . json_encode( $gateway_params ) );
            $response = $this->callApi($gateway_params, $gateway_url);

            $this->amazonpspaymentservices->log( 'APS delete token \n\n' . json_encode( $response, true ) );

            if ( isset( $response['response_code'] ) && AmazonPSConstant::AMAZON_PS_PAYMENT_TOKEN_UPDATE_RESPONSE_CODE === $response['response_code'] ) {
                $status  = 'success';
                $message = $response['response_message'];
            } else {
                $status  = 'error';
                $message = $response['response_message'];
                throw new Exception( $message );

            }
        } catch ( \Exception $e ) {
            $status   = 'error';
            $message  = $e->getMessage();
        }
        return array(
            'status'   => $status,
            'message'  => $message,
        );
    }
}
