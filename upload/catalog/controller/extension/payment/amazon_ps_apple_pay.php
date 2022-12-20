<?php
class ControllerExtensionPaymentAmazonPSApplePay extends Controller {

	protected $registry;
	private $aps_model;
	private $amazonpspaymentservices;
	private $amazonpsorderpayment;

	public function __construct($registry) {
		$this->registry = $registry;
		$this->load->model('extension/payment/amazon_ps');
		$this->aps_model = $this->model_extension_payment_amazon_ps;

		$this->amazonpspaymentservices = new AmazonPSPaymentServices($registry);
		$this->amazonpsorderpayment    = new AmazonPSOrderPayment($registry);
	}

	public function index() {
        $this->language->load('extension/payment/amazon_ps');
        $currency = $this->amazonpspaymentservices->getGatewayCurrencyCode();
        $certificate_path              = DIR_UPLOAD . $this->amazonpspaymentservices->getApplePayCertificateFileName();
		$apple_pay_merchant_identifier = openssl_x509_parse( file_get_contents( $certificate_path ) )['subject']['UID'];

		$config_country_id = $this->config->get('config_country_id');
		$this->load->model('localisation/country');
		$country = $this->model_localisation_country->getCountry($config_country_id);

		$country_iso_code_2 = (isset($country['iso_code_2']) ? $country['iso_code_2'] : 'US');

        $apple_vars                    = array(
			'response_url'               => $this->amazonpspaymentservices->getReturnUrl('amazon_ps_apple_pay/send'),
			'cancel_url'                 => $this->amazonpspaymentservices->getReturnUrl('amazon_ps_apple_pay/cancel'),
			'merchant_identifier'        => $apple_pay_merchant_identifier,
			'country_code'               => $country_iso_code_2,
			'currency_code'              => strtoupper( $currency ),
			'display_name'               => $this->amazonpspaymentservices->getApplePayDisplayName(),
			'validate_url'                   => $this->amazonpspaymentservices->getReturnUrl('amazon_ps_apple_pay/validate'),
			'supported_networks'         => $this->amazonpspaymentservices->getApplePaySupportedNetwork(),
		);
        $apple_order = $this->amazonpsorderpayment->get_apple_order_data();
        $data['apple_pay_button_class'] = $this->amazonpspaymentservices->getApplePayButtonType();
        $data['text_general_error']  = $this->language->get('text_general_error');
        $data['text_error_card_decline'] = $this->language->get('text_error_card_decline');
	
        $data['payment_method'] = 'amazon_ps_apple_pay';
        $data['apple_vars'] = json_encode($apple_vars);
        $data['amazon_ps_apple_order'] = json_encode($apple_order);
		return $this->load->view('extension/payment/amazon_ps_apple_pay', $data);
	}

	public function send()
    {
    	$redirect_url   = '';
		$apple_pay_data = filter_input_array( INPUT_POST, FILTER_SANITIZE_STRING );
		if ( isset( $apple_pay_data['data'] ) && ! empty( $apple_pay_data['data'] ) ) {
			$params          = html_entity_decode( $apple_pay_data['data'] );
			$response_params = json_decode(filter_var($params, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
			$this->amazonpspaymentservices->log('apple params2: '.print_r($response_params,1));

			$orderId = $this->amazonpsorderpayment->getSessionOrderId();
			$this->aps_model->updateAmazonPSMetaData($orderId, 'aps_redirected_order', 1);
			$apple_payment   = $this->amazonpsorderpayment->init_apple_pay_payment( $response_params );
			if ( 'success' === $apple_payment['status'] ) {
				$redirect_url = $this->url->link('checkout/success');
			} else {
                $redirect_url = $this->url->link('checkout/checkout');
			}
		} else {
            $this->amazonpspaymentservices->setFlashMsg($this->language->get('text_payment_canceled'));
			$redirect_url = $this->url->link('checkout/checkout');
		}
		echo '<script>window.top.location.href = "' . $redirect_url . '"</script>';
		exit;
    }

    public function cancel() {
        $this->amazonpspaymentservices->log('apple pay cancel Call: ');
        $this->amazonpspaymentservices->handleRedirectionIssue();

        $this->amazonpsorderpayment->merchantPageCancel();
        header('location:' . $this->url->link('checkout/checkout'));
    }

    public function validate() {
        try {
			$apple_url = filter_input( INPUT_POST, 'apple_url' );
			$apple_url = filter_var($apple_url, FILTER_SANITIZE_URL);


            if ( empty( $apple_url ) ) {
				throw new \Exception( 'Apple pay url is missing' );
			}
			if ( ! filter_var( $apple_url, FILTER_VALIDATE_URL ) ) {
				throw new \Exception( 'Apple pay url is invalid' );
			}
			$parse_apple = parse_url( $apple_url );
			$matched_apple = preg_match('/^(?:[^.]+\.)*apple\.com[^.]+$/', $apple_url);
			if ( ! isset( $parse_apple['scheme'] ) || ! in_array( $parse_apple['scheme'], array( 'https' ), true ) || ! $matched_apple) {
				throw new \Exception( 'Apple pay url is invalid' );
			}
			echo $this->init_apple_pay_api( $apple_url );
		} catch ( \Exception $e ) {
			echo json_encode( array( 'error' => $e->getMessage() ) );
		}
		die();
    }

    /**
	 * Call apple pay api
	 *
	 * @return json
	 */
	public function init_apple_pay_api( $apple_url ) {
		$ch                            = curl_init();
		$domain_name                   = $this->amazonpspaymentservices->getApplePayDomainName();
		$apple_pay_display_name        = $this->amazonpspaymentservices->getApplePayDisplayName();
		$production_key                = $this->amazonpspaymentservices->getApplePayProductionKey();
		$certificate_path              = DIR_UPLOAD . $this->amazonpspaymentservices->getApplePayCertificateFileName();
		$apple_pay_merchant_identifier = openssl_x509_parse( file_get_contents( $certificate_path ) )['subject']['UID'];
		$certificate_key               = DIR_UPLOAD . $this->amazonpspaymentservices->getApplePayCertificateKeyFileName();
        $data                          = '{"merchantIdentifier":"' . $apple_pay_merchant_identifier . '", "domainName":"' . $domain_name . '", "displayName":"' . $apple_pay_display_name . '"}';
		curl_setopt( $ch, CURLOPT_URL, $apple_url );
		curl_setopt( $ch, CURLOPT_SSLCERT, $certificate_path );
		curl_setopt( $ch, CURLOPT_SSLKEY, $certificate_key );
		curl_setopt( $ch, CURLOPT_SSLKEYPASSWD, $production_key );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_POST, 1 );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
		curl_setopt( $ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS );
		$response = curl_exec( $ch );
		curl_close( $ch );
		return $response;
	}

	public function productBtn(&$route, &$data, &$output) {
		// In case the extension is disabled, do nothing
		if(!$this->amazonpspaymentservices->isApplePayActive()){
			return;
		}
		// In case the button not enable on product page, do nothing
		if(!$this->amazonpspaymentservices->isEnabledApplePayProductPage()){
			return;
		}
		//In case of product is recurring, do nothing
		$this->load->model('catalog/product');
		$recurrings = $this->model_catalog_product->getProfiles($this->request->get['product_id']);
		if(!empty($recurrings)){
			return;
		}
		//In case of cart contain recurring product, do nothing
		if($this->cart->hasRecurringProducts()){
			return;
		}
		//in case product is out of stock and stock checkout no
		if (!$this->cart->hasStock() && (!$this->config->get('config_stock_checkout') )) {
			return;
		}
		$product_info = $this->model_catalog_product->getProduct($this->request->get['product_id']);
		if ($product_info) {
			if ($product_info['quantity'] <= 0 || $product_info['quantity'] < $product_info['minimum']) {
				return;
			}
		} else {
			return;
		}
		$this->applePayButton('product', $output);
	}

	public function cartBtn(&$route, &$data, &$output) {
		// In case the extension is disabled, do nothing
		if(!$this->amazonpspaymentservices->isApplePayActive()){
			return;
		}
		// In case the button not enable on product page, do nothing
		if(!$this->amazonpspaymentservices->isEnabledApplePayCartPage()){
			return;
		}
		//In case of cart contain recurring product, do nothing
		if($this->cart->hasRecurringProducts()){
			return;
		}
		//in case product is out of stock and stock checkout no
		if (!$this->cart->hasStock() && (!$this->config->get('config_stock_checkout') )) {
			return;
		}
		$this->applePayButton('cart', $output);
	}

	public function applePayButton($button_page, &$output){
		$this->language->load('extension/payment/amazon_ps');
		$currency = $this->amazonpspaymentservices->getGatewayCurrencyCode();
        $certificate_path              = DIR_UPLOAD . $this->amazonpspaymentservices->getApplePayCertificateFileName();
		$apple_pay_merchant_identifier = openssl_x509_parse( file_get_contents( $certificate_path ) )['subject']['UID'];

		$config_country_id = $this->config->get('config_country_id');
		$this->load->model('localisation/country');
		$country = $this->model_localisation_country->getCountry($config_country_id);

		$country_iso_code_2 = (isset($country['iso_code_2']) ? $country['iso_code_2'] : 'US');

        $apple_vars                    = array(
			'response_url'               => $this->amazonpspaymentservices->getReturnUrl('amazon_ps_apple_pay/send'),
			'cancel_url'                 => $this->amazonpspaymentservices->getReturnUrl('amazon_ps_apple_pay/cancel'),
			'merchant_identifier'        => $apple_pay_merchant_identifier,
			'country_code'               => $country_iso_code_2,
			'currency_code'              => strtoupper( $currency ),
			'display_name'               => $this->amazonpspaymentservices->getApplePayDisplayName(),
			'validate_url'               => $this->amazonpspaymentservices->getReturnUrl('amazon_ps_apple_pay/validate'),
			'validate_shipping_address'  => $this->amazonpspaymentservices->getReturnUrl('amazon_ps_apple_pay/validateShippingAddress'),
			'create_cart_order'			 => $this->amazonpspaymentservices->getReturnUrl('amazon_ps_apple_pay/createCartOrder'),
			'get_cart_data'				 => $this->amazonpspaymentservices->getReturnUrl('amazon_ps_apple_pay/get_apple_pay_cart_data'),
			'supported_networks'         => $this->amazonpspaymentservices->getApplePaySupportedNetwork(),
		);
        $data['button_page']             = $button_page;
        $data['apple_pay_button_class']  = $this->amazonpspaymentservices->getApplePayButtonType();
        $data['text_general_error']      = $this->language->get('text_general_error');
        $data['text_error_card_decline'] = $this->language->get('text_error_card_decline');
	
        $data['payment_method']          = 'amazon_ps_apple_pay';
        $data['apple_vars']              = json_encode($apple_vars);

		$productBtn = $this->load->view('extension/payment/amazon_ps_apple_pay_button', $data );
		// Insert the tags before the closing <head> tag
	    $output = str_replace('</head>', $productBtn . '</head>', $output);
	}

	public function get_apple_pay_cart_data(){
		$data = $this->get_cart_data();
		$this->response->setOutput(json_encode($data));
	}

	public function get_cart_data(){
		$status      = 'success';
		$apple_order = array( 
			'sub_total'      => 0.00,
			'tax_total'      => 0.00,
			'shipping_total' => 0.00,
			'discount_total' => 0.00,
			'grand_total'    => 0.00,
			'order_items'    => array(),
		);
		try {
			$currency       = $this->amazonpspaymentservices->getGatewayCurrencyCode();
			$currency_value = $this->currency->getValue($currency);

			// Totals
			$this->load->model('setting/extension');

			$totals = array();
			$taxes = $this->cart->getTaxes();
			$total = 0;
			// Because __call can not keep var references so we put them into an array.
			$total_data = array(
				'totals' => &$totals,
				'taxes'  => &$taxes,
				'total'  => &$total
			);

			$sort_order = array();

			$results = $this->model_setting_extension->getExtensions('total');
			foreach ($results as $key => $value) {
				$sort_order[$key] = $this->config->get('total_' . $value['code'] . '_sort_order');
			}
			array_multisort($sort_order, SORT_ASC, $results);
			foreach ($results as $result) {
				if ($this->config->get('total_' . $result['code'] . '_status')) {
					$this->load->model('extension/total/' . $result['code']);

					// We have to put the totals in an array so that they pass by reference.
					$this->{'model_extension_total_' . $result['code']}->getTotal($total_data);
				}
			}

			$sort_order = array();

			foreach ($totals as $key => $value) {
				$sort_order[$key] = $value['sort_order'];
			}

			array_multisort($sort_order, SORT_ASC, $totals);


			$data['totals'] = array();

			foreach ($totals as $total) {
				$amount 	= $total['value'];
				switch ($total['code']) {
					case 'sub_total':
						$apple_order['sub_total'] = $this->amazonpspaymentservices->convertGatewayAmount($amount, $currency_value, $currency, true );
						break;
					case 'coupon':
						$apple_order['discount_total'] = $this->amazonpspaymentservices->convertGatewayAmount($amount, $currency_value, $currency, true );
						break;
					case 'shipping':
						$apple_order['shipping_total'] = $this->amazonpspaymentservices->convertGatewayAmount($amount, $currency_value, $currency, true );
						break;
					case 'total':
						$apple_order['grand_total'] = $this->amazonpspaymentservices->convertGatewayAmount($amount, $currency_value, $currency, true );
						break;
					case 'reward':
						//todo
						break;
					case 'voucher':
						//todo
						break;
					case 'credit':
						//todo
						break;
					case 'handling':
						//todo
						break;
					case 'low_order_fee':
						//todo
						break;
				}
			}

			foreach ($taxes as $key => $tax) {
				$apple_order['tax_total'] += $this->amazonpspaymentservices->convertGatewayAmount($tax, $currency_value, $currency, true );
			}

			foreach ($this->cart->getProducts() as $product) {
				$apple_order['order_items'][] = array(
					'product_name'     => $product['name'],
					'product_subtotal' => $this->amazonpspaymentservices->convertGatewayAmount($product['price'], $currency_value, $currency, true )
				);
			}

		} catch ( \Exception $e ) {
			$status = 'error';
		}

		$result = array(
			'status'      => $status,
			'apple_order' => $apple_order,
		);
		return $result;
	}

	public function validateShippingAddress(){

		$this->language->load('extension/payment/amazon_ps');
		$status         = 'success';
		$error_msg      = '';
		$shipping_total = 0.00;
		$postalCode     = '';
		$city		    = '';
		$country_info   = [];
		$zone_info		= [];
		$shipping_method = [];

		$this->load->model('extension/payment/amazon_ps_apple_pay');
		if ( isset( $this->request->post['address_obj'] ) ) {
			$address_data = $this->request->post['address_obj'];
			$this->amazonpspaymentservices->log( 'APS address data for validate\n\n' . json_encode( $address_data, true ) );

			if ( isset( $address_data['countryCode'] ) && ! empty( $address_data['countryCode'] ) ) {
				$country_info = $this->model_extension_payment_amazon_ps_apple_pay->getCountryByISOCode2($address_data['countryCode']);
			}
			if ( isset( $address_data['administrativeArea'] ) && ! empty( $address_data['administrativeArea'] ) ) {
				$country_id = 0;
				if(isset($country_info)){
					$country_id = $country_info['country_id'];
				}
				$zone_info = $this->model_extension_payment_amazon_ps_apple_pay->getZoneByZoneCode($address_data['administrativeArea'], $country_id);
			}
			if ( isset( $address_data['postalCode'] ) && ! empty( $address_data['postalCode'] ) ) {
				$postalCode = $address_data['postalCode'];
			}
			if ( isset( $address_data['locality'] ) && ! empty( $address_data['locality'] ) ) {
				$city  = $address_data['locality'];
			}
		}

		if ( $this->cart->hasShipping() ) {
			if ( isset( $this->request->post['address_obj'] ) ) {
				$response =  $this->getshppingMethod($country_info, $zone_info, $postalCode, $city);
				if($response == ''){
					$status = 'error';
					$error_msg = $this->language->get('error_no_shipping');
				}
			}
		}
		$this->session->data['payment_address']['postcode'] = $postalCode;
		$this->session->data['payment_address']['city'] = $city;
		if ($country_info) {
			$this->session->data['payment_address']['country_id'] = $country_info['country_id'];
			$this->session->data['payment_address']['country'] = $country_info['name'];
			$this->session->data['payment_address']['iso_code_2'] = $country_info['iso_code_2'];
			$this->session->data['payment_address']['iso_code_3'] = $country_info['iso_code_3'];
			$this->session->data['payment_address']['address_format'] = $country_info['address_format'];
		} else {
			$this->session->data['payment_address']['country_id'] = '';
			$this->session->data['payment_address']['country'] = '';
			$this->session->data['payment_address']['iso_code_2'] = '';
			$this->session->data['payment_address']['iso_code_3'] = '';
			$this->session->data['payment_address']['address_format'] = '';
		}

		$this->session->data['payment_address']['custom_field'] = array();
		if ($zone_info) {
			$this->session->data['payment_address']['zone_id'] = $zone_info['id'];
			$this->session->data['payment_address']['zone'] = $zone_info['name'];
			$this->session->data['payment_address']['zone_code'] = $zone_info['code'];
		} else {
			$this->session->data['payment_address']['zone_id'] = '';
			$this->session->data['payment_address']['zone'] = '';
			$this->session->data['payment_address']['zone_code'] = '';
		}

		$result = array(
			'status'    => $status,
			'error_msg' => $error_msg,
		);
		$cart_data = $this->get_cart_data();
		$result['apple_order']      = $cart_data['apple_order'];
		$this->amazonpspaymentservices->log( 'APS validate apple pay address data \n\n' . json_encode( $result, true ) );
		$this->response->setOutput(json_encode($result));
	}

	public function getshppingMethod($country_info, $zone_info, $postalCode, $city){
		$country_id = 0;
		$zone_id    = 0;
		$selected_shipping_method = '';
		$lowest_cost = 0;
		$select_on_sort_order = 1;//select shipping method on sort order
		$select_on_low_price = 0; //select shpping method on low shipping

		if ($country_info) {
			$country_id = $country_info['country_id'];
			$country = $country_info['name'];
			$iso_code_2 = $country_info['iso_code_2'];
			$iso_code_3 = $country_info['iso_code_3'];
			$address_format = $country_info['address_format'];
		} else {
			$country = '';
			$iso_code_2 = '';
			$iso_code_3 = '';
			$address_format = '';
		}

		if ($zone_info) {
			$zone_id = $zone_info['zone_id'];
			$zone = $zone_info['name'];
			$zone_code = $zone_info['code'];
		} else {
			$zone = '';
			$zone_code = '';
		}
		$this->tax->setShippingAddress($country_id, $zone_id);

		$this->session->data['shipping_address'] = array(
			'firstname'      => '',
			'lastname'       => '',
			'company'        => '',
			'address_1'      => '',
			'address_2'      => '',
			'postcode'       => $postalCode,
			'city'           => $city,
			'zone_id'        => $zone_id,
			'zone'           => $zone,
			'zone_code'      => $zone_code,
			'country_id'     => $country_id,
			'country'        => $country,
			'iso_code_2'     => $iso_code_2,
			'iso_code_3'     => $iso_code_3,
			'address_format' => $address_format
		);

		$quote_data = array();

		$this->load->model('setting/extension');

		$results = $this->model_setting_extension->getExtensions('shipping');

		$first_method = 0;
		foreach ($results as $result) {
			if ($this->config->get('shipping_' . $result['code'] . '_status')) {
				$this->load->model('extension/shipping/' . $result['code']);

				$quote = $this->{'model_extension_shipping_' . $result['code']}->getQuote($this->session->data['shipping_address']);

				if ($quote) {
					// selct shipping method which shipping charges is lowest.
					if($select_on_low_price){
						$shipping_method_cost = $quote['quote'][$result['code']]['cost'];
						if($first_method == 0){
							$lowest_cost = $shipping_method_cost;
							$first_method++;
						}
						if($shipping_method_cost <= $lowest_cost){
							$lowest_cost = 	$shipping_method_cost;
							$selected_shipping_method = $result['code'];
						}
					}
					$quote_data[$result['code']] = array(
						'title'      => $quote['title'],
						'quote'      => $quote['quote'],
						'sort_order' => $quote['sort_order'],
						'error'      => $quote['error']
					);
				}
			}
		}

		$sort_order = array();

		foreach ($quote_data as $key => $value) {
			$sort_order[$key] = $value['sort_order'];
		}

		array_multisort($sort_order, SORT_ASC, $quote_data);
		$this->session->data['shipping_methods'] = $quote_data;

		//select payment method which have lowest shipping cost
		if($quote_data && $selected_shipping_method != '' && $select_on_low_price){
			$this->session->data['shipping_method'] = $this->session->data['shipping_methods'][$selected_shipping_method]['quote'][$selected_shipping_method];
		}
		if($quote_data && $select_on_sort_order){
			//select payment method according to sort order
			foreach ($quote_data as $key => $value) {
				$this->session->data['shipping_method'] = $this->session->data['shipping_methods'][$key]['quote'][$key];
				$selected_shipping_method = $key;
				break;
			}
		}
		return $selected_shipping_method;
	}

	public function createCartOrder(){
		$status    = 'success';
		$error_msg = '';
		$redirect = '';
		try {
			if ($this->cart->hasShipping()) {
				// Validate if shipping address has been set.
				if (!isset($this->session->data['shipping_address'])) {
					$redirect = $this->url->link('checkout/checkout', '', true);
					$this->amazonpspaymentservices->log( 'APS apple pay order: shipping address not validate' );
				}

				// Validate if shipping method has been set.
				if (!isset($this->session->data['shipping_method'])) {
					$redirect = $this->url->link('checkout/checkout', '', true);
					$this->amazonpspaymentservices->log( 'APS apple pay order: shipping method not set' );
				}
			} else {
				unset($this->session->data['shipping_address']);
				unset($this->session->data['shipping_method']);
				unset($this->session->data['shipping_methods']);
			}

			// Validate cart has products and has stock.
			if ((!$this->cart->hasProducts() && empty($this->session->data['vouchers'])) || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout'))) {
				$redirect = $this->url->link('checkout/cart');
				$this->amazonpspaymentservices->log( 'APS apple pay order: cart product not validate' );
			}

			// Validate minimum quantity requirements.
			$products = $this->cart->getProducts();

			foreach ($products as $product) {
				$product_total = 0;

				foreach ($products as $product_2) {
					if ($product_2['product_id'] == $product['product_id']) {
						$product_total += $product_2['quantity'];
					}
				}

				if ($product['minimum'] > $product_total) {
					$redirect = $this->url->link('checkout/cart');
					$this->amazonpspaymentservices->log( 'APS apple pay order: product quantity not validate' );
					break;
				}
			}

			if (!$redirect) {
				$order_data = array();

				$totals = array();
				$taxes = $this->cart->getTaxes();
				$total = 0;

				// Because __call can not keep var references so we put them into an array.
				$total_data = array(
					'totals' => &$totals,
					'taxes'  => &$taxes,
					'total'  => &$total
				);

				$this->load->model('setting/extension');

				$sort_order = array();

				$results = $this->model_setting_extension->getExtensions('total');

				foreach ($results as $key => $value) {
					$sort_order[$key] = $this->config->get('total_' . $value['code'] . '_sort_order');
				}

				array_multisort($sort_order, SORT_ASC, $results);

				foreach ($results as $result) {
					if ($this->config->get('total_' . $result['code'] . '_status')) {
						$this->load->model('extension/total/' . $result['code']);

						// We have to put the totals in an array so that they pass by reference.
						$this->{'model_extension_total_' . $result['code']}->getTotal($total_data);
					}
				}

				$sort_order = array();

				foreach ($totals as $key => $value) {
					$sort_order[$key] = $value['sort_order'];
				}

				array_multisort($sort_order, SORT_ASC, $totals);

				$order_data['totals'] = $totals;

				$this->load->language('checkout/checkout');

				$order_data['invoice_prefix'] = $this->config->get('config_invoice_prefix');
				$order_data['store_id'] = $this->config->get('config_store_id');
				$order_data['store_name'] = $this->config->get('config_name');

				if ($order_data['store_id']) {
					$order_data['store_url'] = $this->config->get('config_url');
				} else {
					if ($this->request->server['HTTPS']) {
						$order_data['store_url'] = HTTPS_SERVER;
					} else {
						$order_data['store_url'] = HTTP_SERVER;
					}
				}

				$this->load->model('account/customer');

				$address_data = array();
				$address_1 = $address_2 = '';
				$firstname = $lastname = $email = $telephone = '';
				if ( isset( $this->request->post['address_obj'] ) ) {
					$address_data = $this->request->post['address_obj'];
					if ( isset( $address_data['addressLines'] ) && ! empty( $address_data['addressLines'] ) ) {
						if ( isset( $address_data['addressLines'][0] ) && ! empty( $address_data['addressLines'][0] ) ) {
							$address_1 = $address_data['addressLines'][0];
						}
						if ( isset( $address_data['addressLines'][1] ) && ! empty( $address_data['addressLines'][1] ) ) {
							$address_2 = $address_data['addressLines'][1];
						}
					}
				}

				if ($this->customer->isLogged()) {
					$customer_info = $this->model_account_customer->getCustomer($this->customer->getId());

					$order_data['customer_id'] = $this->customer->getId();
					$order_data['customer_group_id'] = $customer_info['customer_group_id'];
					$order_data['firstname'] = $customer_info['firstname'];
					$order_data['lastname'] = $customer_info['lastname'];
					$order_data['email'] = $customer_info['email'];
					$order_data['telephone'] = $customer_info['telephone'];
					$order_data['custom_field'] = json_decode($customer_info['custom_field'], true);
				} else{
					$order_data['customer_id'] = 0;
					$order_data['customer_group_id'] = 1;

					if ( isset( $address_data['givenName'] ) && ! empty( $address_data['givenName'] ) ) {
						$firstname = $address_data['givenName'];
					}
					if ( isset( $address_data['familyName'] ) && ! empty( $address_data['familyName'] ) ) {
						$lastname = $address_data['familyName'];
					}
					if ( isset( $address_data['emailAddress'] ) && ! empty( $address_data['emailAddress'] ) ) {
						$email = $address_data['emailAddress'];
					}
					if ( isset( $address_data['phoneNumber'] ) && ! empty( $address_data['phoneNumber'] ) ) {
						$telephone = $address_data['phoneNumber'];
					}

					$order_data['firstname'] = $firstname;
					$order_data['lastname']  = $lastname;
					$order_data['email']     = $email;
					$order_data['telephone'] = $telephone;
					$order_data['custom_field'] = array();
				}

				$order_data['payment_firstname'] = $order_data['firstname'];
				$order_data['payment_lastname']  = $order_data['lastname'];
				$order_data['payment_company']   = '';
				$order_data['payment_address_1'] = $address_1;
				$order_data['payment_address_2'] = $address_2;
				$order_data['payment_city'] = $this->session->data['payment_address']['city'];
				$order_data['payment_postcode'] = $this->session->data['payment_address']['postcode'];
				$order_data['payment_zone'] = $this->session->data['payment_address']['zone'];
				$order_data['payment_zone_id'] = $this->session->data['payment_address']['zone_id'];
				$order_data['payment_country'] = $this->session->data['payment_address']['country'];
				$order_data['payment_country_id'] = $this->session->data['payment_address']['country_id'];
				$order_data['payment_address_format'] = $this->session->data['payment_address']['address_format'];
				$order_data['payment_custom_field'] = (isset($this->session->data['payment_address']['custom_field']) ? $this->session->data['payment_address']['custom_field'] : array());


				$order_data['payment_method'] = 'Apple pay';
				$order_data['payment_code'] = AmazonPSConstant::AMAZON_PS_PAYMENT_METHOD_APPLE_PAY;

				if ($this->cart->hasShipping()) {
					$order_data['shipping_firstname'] = $order_data['firstname'];
					$order_data['shipping_lastname'] = $order_data['lastname'];
					$order_data['shipping_company'] = '';
					$order_data['shipping_address_1'] = $address_1;
					$order_data['shipping_address_2'] = $address_2;
					$order_data['shipping_city'] = $this->session->data['shipping_address']['city'];
					$order_data['shipping_postcode'] = $this->session->data['shipping_address']['postcode'];
					$order_data['shipping_zone'] = $this->session->data['shipping_address']['zone'];
					$order_data['shipping_zone_id'] = $this->session->data['shipping_address']['zone_id'];
					$order_data['shipping_country'] = $this->session->data['shipping_address']['country'];
					$order_data['shipping_country_id'] = $this->session->data['shipping_address']['country_id'];
					$order_data['shipping_address_format'] = $this->session->data['shipping_address']['address_format'];
					$order_data['shipping_custom_field'] = (isset($this->session->data['shipping_address']['custom_field']) ? $this->session->data['shipping_address']['custom_field'] : array());

					if (isset($this->session->data['shipping_method']['title'])) {
						$order_data['shipping_method'] = $this->session->data['shipping_method']['title'];
					} else {
						$order_data['shipping_method'] = '';
					}

					if (isset($this->session->data['shipping_method']['code'])) {
						$order_data['shipping_code'] = $this->session->data['shipping_method']['code'];
					} else {
						$order_data['shipping_code'] = '';
					}
				} else {
					$order_data['shipping_firstname'] = '';
					$order_data['shipping_lastname'] = '';
					$order_data['shipping_company'] = '';
					$order_data['shipping_address_1'] = '';
					$order_data['shipping_address_2'] = '';
					$order_data['shipping_city'] = '';
					$order_data['shipping_postcode'] = '';
					$order_data['shipping_zone'] = '';
					$order_data['shipping_zone_id'] = '';
					$order_data['shipping_country'] = '';
					$order_data['shipping_country_id'] = '';
					$order_data['shipping_address_format'] = '';
					$order_data['shipping_custom_field'] = array();
					$order_data['shipping_method'] = '';
					$order_data['shipping_code'] = '';
				}

				$order_data['products'] = array();

				foreach ($this->cart->getProducts() as $product) {
					$option_data = array();

					foreach ($product['option'] as $option) {
						$option_data[] = array(
							'product_option_id'       => $option['product_option_id'],
							'product_option_value_id' => $option['product_option_value_id'],
							'option_id'               => $option['option_id'],
							'option_value_id'         => $option['option_value_id'],
							'name'                    => $option['name'],
							'value'                   => $option['value'],
							'type'                    => $option['type']
						);
					}

					$order_data['products'][] = array(
						'product_id' => $product['product_id'],
						'name'       => $product['name'],
						'model'      => $product['model'],
						'option'     => $option_data,
						'download'   => $product['download'],
						'quantity'   => $product['quantity'],
						'subtract'   => $product['subtract'],
						'price'      => $product['price'],
						'total'      => $product['total'],
						'tax'        => $this->tax->getTax($product['price'], $product['tax_class_id']),
						'reward'     => $product['reward']
					);
				}

				// Gift Voucher
				$order_data['vouchers'] = array();

				if (!empty($this->session->data['vouchers'])) {
					foreach ($this->session->data['vouchers'] as $voucher) {
						$order_data['vouchers'][] = array(
							'description'      => $voucher['description'],
							'code'             => token(10),
							'to_name'          => $voucher['to_name'],
							'to_email'         => $voucher['to_email'],
							'from_name'        => $voucher['from_name'],
							'from_email'       => $voucher['from_email'],
							'voucher_theme_id' => $voucher['voucher_theme_id'],
							'message'          => $voucher['message'],
							'amount'           => $voucher['amount']
						);
					}
				}

				$order_data['comment'] = '';
				$order_data['total'] = $total_data['total'];

				if (isset($this->request->cookie['tracking'])) {
					$order_data['tracking'] = $this->request->cookie['tracking'];

					$subtotal = $this->cart->getSubTotal();

					// Affiliate
					$affiliate_info = $this->model_account_customer->getAffiliateByTracking($this->request->cookie['tracking']);

					if ($affiliate_info) {
						$order_data['affiliate_id'] = $affiliate_info['customer_id'];
						$order_data['commission'] = ($subtotal / 100) * $affiliate_info['commission'];
					} else {
						$order_data['affiliate_id'] = 0;
						$order_data['commission'] = 0;
					}

					// Marketing
					$this->load->model('checkout/marketing');

					$marketing_info = $this->model_checkout_marketing->getMarketingByCode($this->request->cookie['tracking']);

					if ($marketing_info) {
						$order_data['marketing_id'] = $marketing_info['marketing_id'];
					} else {
						$order_data['marketing_id'] = 0;
					}
				} else {
					$order_data['affiliate_id'] = 0;
					$order_data['commission'] = 0;
					$order_data['marketing_id'] = 0;
					$order_data['tracking'] = '';
				}

				$order_data['language_id'] = $this->config->get('config_language_id');
				$order_data['currency_id'] = $this->currency->getId($this->session->data['currency']);
				$order_data['currency_code'] = $this->session->data['currency'];
				$order_data['currency_value'] = $this->currency->getValue($this->session->data['currency']);
				$order_data['ip'] = $this->request->server['REMOTE_ADDR'];

				if (!empty($this->request->server['HTTP_X_FORWARDED_FOR'])) {
					$order_data['forwarded_ip'] = $this->request->server['HTTP_X_FORWARDED_FOR'];
				} elseif (!empty($this->request->server['HTTP_CLIENT_IP'])) {
					$order_data['forwarded_ip'] = $this->request->server['HTTP_CLIENT_IP'];
				} else {
					$order_data['forwarded_ip'] = '';
				}

				if (isset($this->request->server['HTTP_USER_AGENT'])) {
					$order_data['user_agent'] = $this->request->server['HTTP_USER_AGENT'];
				} else {
					$order_data['user_agent'] = '';
				}

				if (isset($this->request->server['HTTP_ACCEPT_LANGUAGE'])) {
					$order_data['accept_language'] = $this->request->server['HTTP_ACCEPT_LANGUAGE'];
				} else {
					$order_data['accept_language'] = '';
				}

				$this->load->model('checkout/order');

				$this->session->data['order_id'] = $this->model_checkout_order->addOrder($order_data);
				$this->amazonpspaymentservices->log( 'APS apple pay order: Order ID#'.$this->session->data['order_id'] );
			}else{
				$status   = $status;
				$error_msg = 'error while creating order';
			}
		} catch ( \Exception $e ) {
			$status = 'error';
		}

		$result = array(
			'status'      => $status,
			'error_msg' => $error_msg,
		);
		$this->response->setOutput(json_encode($result));
	}
}