<?php

class AmazonPSPaymentServices {
    private $session;
    private $url;
    private $config;
    private $log;
    private $customer;
    private $currency;
    private $registry;
    private $request;
    private $cart;
	
	private $status;
	private $merchant_identifier;
	private $access_code;
	private $request_sha_phrase;
	private $response_sha_phrase;
	private $sandbox_mode;
	private $command;
	private $sha_type;
	private $gateway_currency;
	private $debug;
	private $host_to_host_url;
	private $order_status_id;
	private $cc_status;
	private $cc_integration_type;
	private $cc_show_mada_branding;
	private $cc_show_meeza_branding;
    private $cc_mada_bins;
    private $cc_meeza_bins;
    private $cc_tokenization;
	private $cc_hide_delete_token;
	private $cc_sort_order;
	private $visa_checkout_status;
	private $visa_checkout_integration_type;
	private $visa_checkout_api_key;
	private $visa_checkout_profile_name;
	private $visa_checkout_sort_order;
	private $installments_status;
	private $installments_integration_type;
    private $installments_sar_order_min_value;
    private $installments_aed_order_min_value;
    private $installments_egp_order_min_value;
    private $installments_issuer_name;
    private $installments_issuer_logo;
	private $installments_sort_order;
	private $naps_status;
	private $naps_sort_order;
	private $knet_status;
	private $knet_sort_order;
	private $valu_status;
	private $valu_order_min_value;
	private $valu_sort_order;
    private $apple_pay_status;
    private $apple_pay_sha_type;
    private $apple_pay_btn_type;
    private $apple_pay_access_code;
    private $apple_pay_request_sha_phrase;
    private $apple_pay_response_sha_phrase;
    private $apple_pay_domain_name;
    private $apple_pay_display_name;
    private $apple_pay_supported_network;
    private $apple_pay_production_key;
    private $apple_pay_sort_order;
    private $apple_pay_product_page;
    private $apple_pay_cart_page;
	private $gatewayProductionHostUrl;
	private $gatewaySandboxHostUrl;
    private $gatewayProductionNotiApiUrl;
    private $gatewaySandboxNotiApiUrl;
	private $logFileDir;
    private $order;    
    private $check_status_cron_duration;

   
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
        $this->request = $registry->get('request');
        $this->cart = $registry->get('cart');
		
		$aps_config_fields = ['status',
							'merchant_identifier',
							'access_code',
							'request_sha_phrase',
							'response_sha_phrase',
							'sandbox_mode',
							'command',
							'sha_type',
							'gateway_currency',
							'debug',
							'order_status_id',
							'host_to_host_url',
							'cc_status',
							'cc_integration_type',
							'cc_show_mada_branding',
							'cc_show_meeza_branding',
                            'cc_mada_bins',
                            'cc_meeza_bins',
                            'cc_tokenization',
							'cc_hide_delete_token',
							'cc_sort_order',
							'visa_checkout_status',
							'visa_checkout_integration_type',
							'visa_checkout_api_key',
							'visa_checkout_profile_name',
							'visa_checkout_sort_order',
							'installments_status',
							'installments_integration_type',
                            'installments_sar_order_min_value',
                            'installments_aed_order_min_value',
                            'installments_egp_order_min_value',
                            'installments_issuer_name',
                            'installments_issuer_logo',
							'installments_sort_order',
							'naps_status',
							'naps_sort_order',
							'knet_status',
							'knet_sort_order',
							'valu_status',
							'valu_order_min_value',
							'valu_sort_order',
                            'apple_pay_status',
                            'apple_pay_sha_type',
                            'apple_pay_btn_type',
                            'apple_pay_access_code',
                            'apple_pay_request_sha_phrase',
                            'apple_pay_response_sha_phrase',
                            'apple_pay_domain_name',
                            'apple_pay_display_name',
                            'apple_pay_supported_network',
                            'apple_pay_production_key',
                            'apple_pay_sort_order',
                            'apple_pay_product_page',
                            'apple_pay_cart_page',
                            'check_status_cron_duration'];

		foreach ($aps_config_fields as $key => $field) {
			$this->$field = $this->_getAPSConfig($field);
		}

		$this->gatewayProductionHostUrl    = AmazonPSConstant::GATEWAY_PRODUCTION_URL;
        $this->gatewaySandboxHostUrl = AmazonPSConstant::GATEWAY_SANDBOX_URL;
        $this->gatewayProductionNotiApiUrl = AmazonPSConstant::GATEWAY_PRODUCTION_NOTIFICATION_API_URL;
        $this->gatewaySandboxNotiApiUrl  = AmazonPSConstant::GATEWAY_SANDBOX_NOTIFICATION_API_URL;
        $this->logFileDir         = 'amazon_ps_'.date('Y-m-d').'.log';
    }
	
	
	
	private function _getAPSConfig($key)
    {
        return $this->registry->get('config')->get('payment_amazon_ps_' . $key);
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getMerchantIdentifier()
    {
        return $this->merchant_identifier;
    }

    public function getAccessCode()
    {
        return $this->decodeValue($this->access_code);
    }

	public function getRequestShaPhrase()
    {
        return $this->decodeValue($this->request_sha_phrase);
    }

    public function getResponseShaPhrase()
    {
        return $this->decodeValue($this->response_sha_phrase);
    }

    public function getSandboxMode()
    {
        return $this->sandbox_mode;
    }

    public function decodeValue($value){
       return html_entity_decode($value,ENT_QUOTES, 'UTF-8');
    }

    public function getCommand($paymentMethod, $card_number = null, $card_type = null )
    {
        $mada_regex  = '/^' . $this->getMadaBins() . '/';
        $meeza_regex = '/^' . $this->getMeezaBins() . '/';

        $command            = $this->command;
        $authorized_methods = array(
            AmazonPSConstant::AMAZON_PS_PAYMENT_METHOD_CC,
            AmazonPSConstant::AMAZON_PS_PAYMENT_METHOD_VISA_CHECKOUT,
            AmazonPSConstant::AMAZON_PS_PAYMENT_METHOD_APPLE_PAY,
        );
        if ( 'AUTHORIZATION' === $command && ! in_array( $paymentMethod, $authorized_methods, true ) ) {
            $command = 'PURCHASE';
        }
        if ( 'AUTHORIZATION' === $command && AmazonPSConstant::AMAZON_PS_PAYMENT_METHOD_CC === $paymentMethod ) {
            if ( ! empty( $card_number ) ) {
                if ( preg_match( $mada_regex, $card_number ) || preg_match( $meeza_regex, $card_number ) ) {
                    $command = 'PURCHASE';
                }
            } elseif ( ! empty( $card_type ) ) {
                if ( 'MADA' === $card_type || 'MEEZA' === $card_type ) {
                    $command = 'PURCHASE';
                }
            }
        }
        if($this->cart->hasRecurringProducts()){
            $command = 'PURCHASE';
        }
        return $command;
    }

    public function getShaType()
    {
        return $this->sha_type;
    }

	public function getGatewayCurrency()
    {
        return $this->gateway_currency;
    }
      
    public function getDebugMode()
    {
        return $this->debug;
    }

    public function getHostUrl()
    {
        return $this->hostUrl;
    }

    public function getCcStatus()
    {
        return $this->cc_status;
    }

    public function getCcIntegrationType()
    {
        return $this->cc_integration_type;
    }

	public function getCcShowMeezaBranding()
    {
        return $this->cc_show_meeza_branding;
    }

    public function getCcShowMadaBranding()
    {
        return $this->cc_show_mada_branding;
    }
	
    public function isEnabledTokenization()
    {
        if($this->cc_tokenization){
            return true;
        }
        return false;
    }
	
    public function isHideDeleteToken()
    {
        if($this->cc_hide_delete_token){
            return true;
        }
        return false;
    }
	
	public function getEnabledTokenization() {
		return ! empty( $this->cc_tokenization ) ? $this->cc_tokenization : 1;
	}
	
	public function getHideDeleteToken() {
		return ! empty( $this->cc_hide_delete_token ) ? $this->cc_hide_delete_token : 0;
	}

	public function getCcSortOrder()
    {
        return $this->cc_sort_order;
    }

    public function getNapsStatus()
    {
        return $this->naps_status;
    }

    public function getNapsSortOrder()
    {
        return $this->naps_sort_order;
    }

    public function getInstallmentsStatus(){
        return $this->installments_status;
    }
  
    public function getInstallmentsIntegrationType(){
        return $this->installments_integration_type;
    }
    
    public function getInstallmentsSAROrderMinValue(){
        return ! empty( $this->installments_sar_order_min_value ) ? $this->installments_sar_order_min_value : 1000;
    }

    public function getInstallmentsAEDOrderMinValue(){
        return ! empty( $this->installments_aed_order_min_value ) ? $this->installments_aed_order_min_value : 1000;
    }

    public function getInstallmentsEGPOrderMinValue(){
        return ! empty( $this->installments_egp_order_min_value ) ? $this->installments_egp_order_min_value : 1000;
    }

    public function getInstallmentsIssuerName(){
        return $this->installments_issuer_name;
    }

    public function getInstallmentsIssuerLogo(){
        return $this->installments_issuer_logo;
    }

    public function getInstallmentsSortOrder()
    {
        return $this->installments_sort_order;
    }
    
    public function getKnetStatus()
    {
        return $this->knet_status;
    }

    public function getKnetSortOrder()
    {
        return $this->knet_sort_order;
    }

    public function getValuStatus()
    {
        return $this->valu_status;
    }

    public function getValuOrderMinValue()
    {
        return $this->valu_order_min_value;
    }

    public function getValuSortOrder()
    {
        return $this->valu_sort_order;
    }

	public function getVisaCheckoutStatus()
    {
        return $this->visa_checkout_status;
    }

    public function getVisaCheckoutIntegrationType(){
        return $this->visa_checkout_integration_type;
    }
    
    public function getVisaCheckoutApiKey()
    {
        return $this->decodeValue($this->visa_checkout_api_key);
    }

    public function getVisaCheckoutProfileName()
    {
        return $this->visa_checkout_profile_name;
    }

    public function getVisaCheckoutSortOrder()
    {
        return $this->visa_checkout_sort_order;
    }

    public function getVisaCheckoutButton()
    {
        if($this->isSandboxMode()){
            return AmazonPSConstant::VISA_CHECKOUT_BUTTON_SANDBOX;
        }
        return AmazonPSConstant::VISA_CHECKOUT_BUTTON_PRODUCTION;
    }

    public function getVisaCheckoutJS()
    {
       if($this->isSandboxMode()){
            return AmazonPSConstant::VISA_CHECKOUT_JS_SANDBOX;
        }
        return AmazonPSConstant::VISA_CHECKOUT_JS_PRODUCTION;
    }

    public function getApplePayStatus(){
        return $this->apple_pay_status;
    }

    public function getApplePayShaType(){
        return $this->apple_pay_sha_type;
    }

    public function getApplePayButtonType(){
        return $this->apple_pay_btn_type;
    }

    public function getApplePayAccessCode(){
        return $this->decodeValue($this->apple_pay_access_code);
    }

    public function getApplePayRequestShaPhrase(){
        return $this->decodeValue($this->apple_pay_request_sha_phrase);
    }

    public function getApplePayResponseShaPhrase(){
        return $this->decodeValue($this->apple_pay_response_sha_phrase);
    }

    public function getApplePayDomainName(){
        return $this->apple_pay_domain_name;
    }

    public function getApplePayDisplayName(){
        return $this->apple_pay_display_name;
    }

    public function getApplePaySupportedNetwork(){
        return $this->apple_pay_supported_network;
    }

    public function getApplePayProductionKey(){
        return $this->apple_pay_production_key;
    }

    public function getApplePaySortOrder(){
        return $this->apple_pay_sort_order;
    }

	public function isActive()
    {
        if ($this->status) {
            return true;
        }
        return false;
    }    

    public function isSandboxMode()
    {
        if ($this->sandbox_mode) {
            return true;
        }
        return false;
    }

    public function isDebugMode()
    {
        if ($this->debug) {
            return true;
        }
        return false;
    }

    public function isCcActive()
    {
        if ($this->cc_status) {
            return true;
        }
        return false;
    }

    public function isCcStandardCheckout()
    {
        if ($this->cc_integration_type == AmazonPSConstant::AMAZON_PS_INTEGRATION_TYPE_STANDARD_CHECKOUT) {
            return true;
        }
        return false;
    }

    public function isCcHostedCheckout()
    {
        if ($this->cc_integration_type == AmazonPSConstant::AMAZON_PS_INTEGRATION_TYPE_HOSTED_CHECKOUT) {
            return true;
        }
        return false;
    }

    public function isNapsActive()
    {
        if ($this->naps_status) {
            return true;
        }
        return false;
    }

    public function isInstallmentsActive()
    {
        if ($this->installments_status) {
            return true;
        }
        return false;
    }

    public function isInstallmentsStandardCheckout()
    {
        if ($this->installments_integration_type == AmazonPSConstant::AMAZON_PS_INTEGRATION_TYPE_STANDARD_CHECKOUT) {
            return true;
        }
        return false;
    }

    public function isInstallmentsHostedCheckout()
    {
        if ($this->installments_integration_type == AmazonPSConstant::AMAZON_PS_INTEGRATION_TYPE_HOSTED_CHECKOUT) {
            return true;
        }
        return false;
    }

    public function isKnetActive()
    {
        if ($this->knet_status) {
            return true;
        }
        return false;
    }

    public function isValuActive()
    {
        if ($this->valu_status) {
            return true;
        }
        return false;
    }

    public function isVisaCheckoutActive()
    {
        if ($this->visa_checkout_status) {
            return true;
        }
        return false;
    }

    public function isMeezaBranding()
    {
        if($this->cc_show_meeza_branding){
            return true;
        }
        return false;
    }

    public function isMadaBranding()
    {
        if($this->cc_show_mada_branding){
            return true;
        }
        return false;
    }

    public function isApplePayActive()
    {
        if ($this->apple_pay_status) {
            return true;
        }
        return false;
    } 

    public function getApplePayCertificateFileName(){
        return $this->registry->get('config')->get('amazon_ps_apple_pay_certificate_file');
    }

    public function getApplePayCertificateKeyFileName(){
        return $this->registry->get('config')->get('amazon_ps_apple_pay_certificate_key_file');
    }

    public function isEnabledApplePayProductPage(){
        if ($this->apple_pay_product_page) {
            return true;
        }
        return false;
    }
    public function isEnabledApplePayCartPage(){
        if ($this->apple_pay_cart_page) {
            return true;
        }
        return false;
    }

    public function getGatewayProdHostUrl()
    {
        return $this->gatewayProductionHostUrl;
    }

    public function getGatewaySandboxHostUrl()
    {
        return $this->gatewaySandboxHostUrl;
    }

    public function getGatewayProductionNotiApiUrl(){
        return $this->gatewayProductionNotiApiUrl;
    }

    public function getGatewaySandboxNotiApiUrl(){
        return $this->gatewaySandboxNotiApiUrl;
    }

    public function getMadaBins(){
        return $this->cc_mada_bins;
    }

    public function getMeezaBins(){
        return $this->cc_meeza_bins;
    }

    public function getCheckStatusCronDuration(){
        return $this->check_status_cron_duration;
    }

    public function getLogFileDir()
    {
        return $this->logFileDir;
    }

    public function getLanguage()
    {
        $language_code = !empty($this->session->data['language']) ? $this->session->data['language'] : $this->config->get('config_language');

        if(strpos($language_code, 'ar')!==false){
            return 'ar';
        }
        return 'en';
    }
 
    public function prodcutString(){
        $product_name = 'MacBookAir';
        $product_price = '201000';
        $category_name = 'LaptopsampNotebooks';

        $producs_string = '[{product_name=' . $product_name . ', product_price=' . $product_price . ', product_category=' . $category_name . '}]';
        return $producs_string;
    }
    public function calculateSignature($arrData, $signType = 'request', $type = 'regular')
    {
        $shaString = '';
        $hmac_key = '';
        $hash_algorithm = '';

        ksort($arrData);
        foreach ( $arrData as $k => $v ) {
			if ( 'products' === $k ) {
				$productString = '[{';
                foreach ($v as $next_key => $next_value) {
                    $productString.= "$next_key=$next_value, ";
                }
                $productString = rtrim( $productString, ', ' );
                $productString .= '}]';
                $shaString .= "$k=" . $productString;
			} elseif ( 'apple_header' === $k || 'apple_paymentMethod' === $k ) {
				$shaString .= $k . '={';
				foreach ( $v as $i => $j ) {
					$shaString .= $i . '=' . $j . ', ';
				}
				$shaString  = rtrim( $shaString, ', ' );
				$shaString .= '}';
			} else {
				$shaString .= "$k=$v";
			}
		}

        if ( 'apple_pay' === $type ) {
			$hash_algorithm = $this->getApplePayShaType();
		} else {
			$hash_algorithm = $this->getShaType();
		}
        if ( 'apple_pay' === $type ) {
            if ($signType == 'request') {
                $shaString = $this->getApplePayRequestShaPhrase() . $shaString . $this->getApplePayRequestShaPhrase();
                $hmac_key  = $this->getApplePayRequestShaPhrase();
            }
            else {
                $shaString = $this->getApplePayResponseShaPhrase() . $shaString . $this->getApplePayResponseShaPhrase();
                $hmac_key  = $this->getApplePayResponseShaPhrase();
            }
        } else {
            if ($signType == 'request') {
                $shaString = $this->getRequestShaPhrase() . $shaString . $this->getRequestShaPhrase();
                $hmac_key  = $this->getRequestShaPhrase();
            }
            else {
                $shaString = $this->getResponseShaPhrase() . $shaString . $this->getResponseShaPhrase();
                $hmac_key  = $this->getResponseShaPhrase();
            }
        }

        if ( in_array( $hash_algorithm, array( 'sha256', 'sha512' ), true ) ) {
            $signature = hash($hash_algorithm, $shaString);
        } elseif ( 'hmac256' === $hash_algorithm) {
            $signature = hash_hmac( 'sha256', $shaString, $hmac_key );
        } elseif ( 'hmac512' === $hash_algorithm ) {
            $signature = hash_hmac( 'sha512', $shaString, $hmac_key );
        }

        return $signature;
    }


    public function log($messages, $title = null, $forceDebug = false)
    {
        $debugMode = $this->isDebugMode();
        if (!$debugMode && !$forceDebug) {
            return;
        }
        $log = new Log($this->getLogFileDir());
        $log->write($title .':'. json_encode($messages));
    }

    public function setFlashMsg($message, $status = AmazonPSConstant::AMAZON_PS_FLASH_MSG_ERROR, $title = '')
    {
        $this->session->data['error'] = $message;
    }

    public function loadJsMessages($messages, $isReturn = true, $category = 'amazon_ps')
    {
        $result = '';
        foreach($messages as $label => $translation) {
            $result .= "amazon_ps_error_js_msg['{$category}.{$label}']='" . $translation ."';\n";
        }
        if($isReturn) {
            return $result;
        }
        else{
            echo $result; 
        }
    }


    public function getCustomerIp()
    {
        //return $this->request->server['REMOTE_ADDR'] ;
		
		$ipaddress = '';
		if (isset($_SERVER['HTTP_CLIENT_IP'])) {
			$ipaddress = $_SERVER['HTTP_CLIENT_IP'];
		}
		else if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
		}
		else if (isset($_SERVER['HTTP_X_FORWARDED'])) {
			$ipaddress = $_SERVER['HTTP_X_FORWARDED'];
		}
		else if (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
			$ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
		}
		else if (isset($_SERVER['HTTP_FORWARDED'])) {
			$ipaddress = $_SERVER['HTTP_FORWARDED'];
		}
		else if (isset($_SERVER['REMOTE_ADDR'])) {
			$ipaddress = $_SERVER['REMOTE_ADDR'];
		}
		else {
			$ipaddress = 'UNKNOWN';
		}
		return $ipaddress;

    }

    public function getGatewayUrl($type = 'redirection')
    {
        $testMode = $this->isSandboxMode();
        if ($type == 'notificationApi') {
            $gatewayUrl = $testMode ?  $this->getGatewaySandboxNotiApiUrl() :  $this->getGatewayProductionNotiApiUrl();
        }
        else {
            $gatewayUrl = $testMode ? $this->getGatewaySandboxHostUrl() : $this->getGatewayProdHostUrl();
        }

        return $gatewayUrl;
    }

    public function getCurrencyDecimalPoints($currency)
    {
        $decimalPoint  = 2;
        //todo add more
        $arrCurrencies = array(
            'JOD' => 3,
            'KWD' => 3,
            'OMR' => 3,
            'TND' => 3,
            'BHD' => 3,
            'LYD' => 3,
            'IQD' => 3,
        );
        if (isset($arrCurrencies[$currency])) {
            $decimalPoint = $arrCurrencies[$currency];
        }
        return $decimalPoint;
    }

    public function convertGatewayAmount($amount, $currency_value, $currency_code, $iso = false)
    {
        $gateway_currency = $this->getGatewayCurrency();
        $new_amount       = 0;

        $decimal_points   = $this->getCurrencyDecimalPoints($currency_code);
        if ($gateway_currency == 'front') {
            $new_amount = round($amount * $currency_value, $decimal_points);
        }
        else {
            $new_amount = round($amount, $decimal_points);
        }
        if ( 0 !== $decimal_points ) {
            $new_amount = $new_amount * (pow(10, $decimal_points));
        }
        if( true === $iso ) {
            $new_amount = $this->convert_dec_amount( $new_amount, $currency_code );
        }
        return "$new_amount";
    }

    /** 
     * Convert decimal amount to int amount
     * example 100.00 to 10000
    */
    public function convertDecimalToIntAmount($amount, $currency_code)
    {
        $decimal_points   = $this->getCurrencyDecimalPoints($currency_code);
        if ( 0 !== $decimal_points ) {
            $amount = $amount * (pow(10, $decimal_points));
        }
        return $amount;
    }

    /**
     * convert int to decimal amount
     * example USD 10075 = 100.75
    */
    public function convert_dec_amount( $amount, $currency_code ) {
        $new_amount     = 0;
        $decimal_points = $this->getCurrencyDecimalPoints( $currency_code );
        $divide_by      = intval( str_pad( 1, $decimal_points + 1, 0, STR_PAD_RIGHT ) );
        if ( 0 === $decimal_points ) {
            $new_amount = $amount;
        } else {
            $new_amount = $amount / $divide_by;
        }
        return $new_amount;
    }

    public function convertGatewayToOrderAmount( $amount, $currency_code , $value) {
        //convert int amount to decimal amount
        $amount = $this->convert_dec_amount($amount, $currency_code);
        $decimal_points = $this->getCurrencyDecimalPoints( $currency_code );

        $gateway_currency = $this->getGatewayCurrency();
        if ($gateway_currency == 'front') {
           $amount = round($amount/$value, (int)$decimal_points);
        }
        return $amount;

    }

    public function getBaseCurrency()
    {
        return $this->registry->get('config')->get('config_currency');
    }

    public function getFrontCurrency()
    {
        return $this->session->data['currency'];
    }
    
    public function getGatewayCurrencyCode($baseCurrencyCode = 'USD', $currentCurrencyCode = 'EGP')
    {
        $baseCurrencyCode    = $this->getBaseCurrency();
        $currentCurrencyCode = $this->getFrontCurrency();
        $gateway_currency = $this->getGatewayCurrency();
        $currencyCode     = $baseCurrencyCode;
        if ($gateway_currency == 'front') {
            $currencyCode = $currentCurrencyCode;
        }
        return $currencyCode;
    }

    public function getReturnUrl($path)
    {
        return $this->getUrl($path);
    }

    public function getUrl($path)
    {
        return $this->url->link('extension/payment/'.$path, '', true);
    }

    public function clean_string( $string ) {
        $string = str_replace( array( ' ', '-' ), array( '', '' ), $string );
        return preg_replace( '/[^A-Za-z0-9\-]/', '', $string );
    }
    
    public function getTokenizationCardIcons() {
        $icon_html       = '';
        $image_directory = 'catalog/view/theme/default/image/amazon_ps/';
        $mada_logo       = $image_directory . 'mada-logo.png';
        $visa_logo       = $image_directory . 'visa-logo.png';
        $mastercard_logo = $image_directory . 'mastercard-logo.png';
        $amex_logo       = $image_directory . 'amex-logo.png';
        $meeza_logo      = $image_directory . 'meeza-logo.jpg';

        $card_icons      = array(
            'mada'       => $mada_logo,
            'visa'       => $visa_logo,
            'mastercard' => $mastercard_logo,
            'amex'       => $amex_logo,
            'meeza'      => $meeza_logo,
        );
        return $card_icons;
    }

    /**
     * Return enabled embedded hosted checkout(credit card with installment)
     *
     * @return string
     */
    public function get_enabled_embedded_hosted_checkout() {
        $enabled_embedded_hosted_checkout = '0';
        if ($this->installments_integration_type == AmazonPSConstant::AMAZON_PS_INTEGRATION_TYPE_EMBEDDED_HOSTED_CHECKOUT){
            $enabled_embedded_hosted_checkout = '1';
            if ($this->cart->hasRecurringProducts() ) {
                $enabled_embedded_hosted_checkout = '0';
            }
        }
        return $enabled_embedded_hosted_checkout;
    }

    /**
     * Get Plugin params
     *
     * @return plugin_params array
     */
    public function plugin_params() {
        return array(
            'app_programming'    => 'PHP',
            'app_framework'      => 'Opencart',
            'app_ver'            => 'v' . VERSION,
            'app_plugin'         => 'Opencart',
            'app_plugin_version' => 'v' . AmazonPSConstant::AMAZON_PS_VERSION,
        );
    }

    public function handleRedirectionIssue(){
        $key = $this->config->get('session_name');
        if (PHP_VERSION_ID >= 70300) {
            setcookie($key, $this->session->getId(),
                [
                    'expires'  => time() + 180,
                    'path'     => ini_get('session.cookie_path'),
                    'domain'   => ini_get('session.cookie_domain'),
                    'samesite' => 'None',
                    'secure'   => true,
                    'httponly' => ini_get('session.cookie_httponly')
                ]
            );
        } else {
            $time = gmdate("D, d-M-Y H:i:s T", strtotime( '+3 minutes' ));
            header('Set-Cookie: '.$key.'=' . $this->session->getId(). '; expires='.$time.'; Path='.ini_get('session.cookie_path').'; SameSite=None; Secure=true; httponly='.ini_get('session.cookie_httponly'));
        }
    }
}
