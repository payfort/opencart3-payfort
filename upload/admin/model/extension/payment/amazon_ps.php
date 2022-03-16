<?php
class ModelExtensionPaymentAmazonPS extends Model {

	protected $registry;
	public function __construct($registry)
	{
		$this->registry = $registry;
	}
	public function install() {
		try{
			$this->db->query("
				CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "amazon_ps_order_meta_data` (
					`amazon_ps_order_id` INT(11) NOT NULL AUTO_INCREMENT,
					`order_id` int(11) NOT NULL,
					`meta_key` varchar(255) NOT NULL,
					`meta_value` longtext NULL,
					`date_added` DATETIME NOT NULL,
					PRIMARY KEY `amazon_ps_order_id` (`amazon_ps_order_id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
			");

	        $this->db->query("
				CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "amazon_ps_tokens` (
					`ID` INT(11) NOT NULL AUTO_INCREMENT,
					`customer_id` int(11) NOT NULL,
					`token` varchar(255) NOT NULL,
					`created_at` DATETIME NOT NULL,
	                `updated_at` DATETIME NOT NULL,
					PRIMARY KEY `ID` (`ID`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
			");

	        $this->db->query("
				CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "amazon_ps_token_meta_data` (
					`ID` INT(11) NOT NULL AUTO_INCREMENT,
					`token_id` int(11) NOT NULL,
					`meta_key` varchar(255) NOT NULL,
					`meta_value` longtext NULL,
					`created_at` DATETIME NOT NULL,
	                `updated_at` DATETIME NOT NULL,
					PRIMARY KEY `ID` (`ID`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
			");
	    }catch(Exception $e){
			$this->log("APS install error".$e->getMessage() );
	    }
	}

	public function addEvents(){
		$this->load->model('setting/event');
		$this->model_setting_event->addEvent('amazon_ps_apple_pay_btn', 'catalog/view/product/product/after', 'extension/payment/amazon_ps_apple_pay/productBtn');
		$this->model_setting_event->addEvent('amazon_ps_apple_pay_btn', 'catalog/view/checkout/cart/after', 'extension/payment/amazon_ps_apple_pay/cartBtn');

		$this->model_setting_event->addEvent('amazon_ps', 'catalog/view/common/success/after', 'extension/payment/amazon_ps/displayPaymentData');
	}
	public function deleteEvents(){
		$this->load->model('setting/event');
		$this->model_setting_event->deleteEventByCode('amazon_ps_apple_pay_btn');
		$this->model_setting_event->deleteEventByCode('amazon_ps');
	}

	public function uninstall() {
		//$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "amazon_ps_order_meta_data`;");
	}

	public function updateAmazonPSMetaData($order_id, $meta_key, $meta_value, $mix_value = false) {
		if($mix_value){
			$meta_value = serialize($meta_value);
		}
		$this->db->query("INSERT INTO `" . DB_PREFIX . "amazon_ps_order_meta_data` SET `order_id` = '" . (int)$order_id . "',`meta_key` = '" . $this->db->escape($meta_key) . "',`meta_value` = '" . $this->db->escape($meta_value) . "',  `date_added` = now()");
		return $this->db->getLastId();
	}

	public function getAmazonPSMetaValue($order_id, $meta_key, $mix_value = false) {
		$qry = $this->db->query("SELECT meta_value FROM `" . DB_PREFIX . "amazon_ps_order_meta_data` WHERE `order_id` = '" . (int)$order_id . "' AND `meta_key` = '" . $this->db->escape($meta_key) . "' LIMIT 1");

		if ( $qry->num_rows ) {
			if($mix_value){
				if($qry->row['meta_value']){
					return unserialize($qry->row['meta_value']);
				}
			}
			return $qry->row['meta_value'];
		} else {
			return false;
		}
	}

	public function getAmazonPSMetaDataByMetaId($meta_id) {
		$qry = $this->db->query("SELECT * FROM `" . DB_PREFIX . "amazon_ps_order_meta_data` WHERE `amazon_ps_order_id` = '" . (int)$meta_id . "' LIMIT 1");

		if ( $qry->num_rows ) {
			return $qry->row;
		} else {
			return false;
		}
	}

	public function getAmazonPSMetaData($order_id, $meta_key, $mix_value = false) {
		$qry = $this->db->query("SELECT * FROM `" . DB_PREFIX . "amazon_ps_order_meta_data` WHERE `order_id` = '" . (int)$order_id . "' AND `meta_key` = '" . $this->db->escape($meta_key) . "' ORDER BY `amazon_ps_order_id` DESC");

		$result = [];
		if ( $qry->num_rows ) {
			foreach ($qry->rows as $row) {
				$result[] = $row;
			}
		}
		return $result;
	}

	public function paymentRelatedAdditionData($amazon_ps_data, $orderId, $paymentMethod){
		$data['order_extra_data'] = array();
		if ( isset( $amazon_ps_data['command'] ) && ! empty( $amazon_ps_data['command'] ) ) {
			$data['order_extra_data'][] =  array(
				'label' => 'Command',
				'value' => $amazon_ps_data['command'],
			);
		}
		if ( isset( $amazon_ps_data['query_command'] ) && ! empty( $amazon_ps_data['query_command'] ) ) {
			$data['order_extra_data'][] =  array(
				'label' => 'Query Command',
				'value' => $amazon_ps_data['query_command'],
			);
		}
		if ( isset( $amazon_ps_data['merchant_reference'] ) && ! empty( $amazon_ps_data['merchant_reference'] ) ) {
			$data['order_extra_data'][] =  array(
				'label' => 'Merchant Reference',
				'value' => $amazon_ps_data['merchant_reference'],
			);
		}
		if ( isset( $amazon_ps_data['fort_id'] ) && ! empty( $amazon_ps_data['fort_id'] ) ) {
			$data['order_extra_data'][] =  array(
				'label' => 'Fort Id',
				'value' => $amazon_ps_data['fort_id'],
			);
		}
		if ( isset( $amazon_ps_data['payment_option'] ) && ! empty( $amazon_ps_data['payment_option'] ) ) {
			$data['order_extra_data'][] =  array(
				'label' => 'Payment Option',
				'value' => $amazon_ps_data['payment_option'],
			);
		}

		if ( AmazonPSConstant::AMAZON_PS_PAYMENT_METHOD_INSTALLMENTS === $paymentMethod ) {
			$installment_amount   = $this->getAmazonPSMetaValue( $orderId, 'installment_amount');
			$installment_interest = $this->getAmazonPSMetaValue( $orderId, 'installment_interest');
		}else{
			$installment_amount   = $this->getAmazonPSMetaValue( $orderId, 'em_installment_amount');
			$installment_interest = $this->getAmazonPSMetaValue( $orderId, 'em_installment_interest');
		}
		if ( isset( $amazon_ps_data['installments'] ) && ! empty( $amazon_ps_data['installments'] ) ) {
			$data['order_extra_data'][] = array(
				'label' => 'Installments',
				'value' => $amazon_ps_data['installments'],
			);
		}

		if ( isset( $amazon_ps_data['number_of_installments'] ) && ! empty( $amazon_ps_data['number_of_installments'] ) ) {
			$data['order_extra_data'][] = array(
				'label' => 'No of Installments',
				'value' => $amazon_ps_data['number_of_installments'],
			);
		}
		if ( ! empty( $installment_amount ) ) {
			$data['order_extra_data'][] = array(
				'label' => 'Installment Amount',
				'value' => $installment_amount,
			);
		}
		if ( ! empty( $installment_interest ) ) {
			$data['order_extra_data'][] = array(
				'label' => 'Installment Interest',
				'value' => $installment_interest,
			);
		}

		if ( AmazonPSConstant::AMAZON_PS_PAYMENT_METHOD_VALU === $paymentMethod ) {
			$tenure          = $this->getAmazonPSMetaValue( $orderId, 'valu_active_tenure');
			$tenure_amount   = $this->getAmazonPSMetaValue( $orderId, 'valu_tenure_amount');
			$tenure_interest = $this->getAmazonPSMetaValue( $orderId, 'valu_tenure_interest');
			if ( ! empty( $tenure ) ) {
				$data['order_extra_data'][] = array(
					'label' => 'Tenure',
					'value' => $tenure,
				);
			}
			if ( ! empty( $tenure_amount ) ) {
				$data['order_extra_data'][] = array(
					'label' => 'Tenure Amount',
					'value' => $tenure_amount . ' ' . $amazon_ps_data['currency'],
				);
			}
			if ( ! empty( $tenure_interest ) ) {
				$data['order_extra_data'][] = array(
					'label' => 'Tenure Interest',
					'value' => $tenure_interest . '%',
				);
			}
		}

		if ( isset( $amazon_ps_data['token_name'] ) && ! empty( $amazon_ps_data['token_name'] ) ) {
			$data['order_extra_data'][] = array(
				'label' => 'Card Token',
				'value' => $amazon_ps_data['token_name'],
			);
		}
		if ( isset( $amazon_ps_data['expiry_date'] ) && ! empty( $amazon_ps_data['expiry_date'] ) ) {
			$data['order_extra_data'][] = array(
				'label' => 'Card Expiry',
				'value' => $amazon_ps_data['expiry_date'],
			);
		}
		if ( isset( $amazon_ps_data['card_number'] ) && ! empty( $amazon_ps_data['card_number'] ) ) {
			$data['order_extra_data'][] = array(
				'label' => 'Card Number',
				'value' => $amazon_ps_data['card_number'],
			);
		}
		if ( isset( $amazon_ps_data['authorization_code'] ) && ! empty( $amazon_ps_data['authorization_code'] ) ) {
			$data['order_extra_data'][] = array(
				'label' => 'Authorization Code',
				'value' => $amazon_ps_data['authorization_code'],
			);
		}
		if ( isset( $amazon_ps_data['response_code'] ) && ! empty( $amazon_ps_data['response_code'] ) ) {
			$data['order_extra_data'][] = array(
				'label' => 'Response Code',
				'value' => $amazon_ps_data['response_code'],
			);
		}
		if ( isset( $amazon_ps_data['acquirer_response_code'] ) && ! empty( $amazon_ps_data['acquirer_response_code'] ) ) {
			$data['order_extra_data'][] = array(
				'label' => 'Acquier Response Code',
				'value' => $amazon_ps_data['acquirer_response_code'],
			);
		}
		if ( isset( $amazon_ps_data['reconciliation_reference'] ) && ! empty( $amazon_ps_data['reconciliation_reference'] ) ) {
			$data['order_extra_data'][] = array(
				'label' => 'Reconciliation Reference',
				'value' => $amazon_ps_data['reconciliation_reference'],
			);
		}
		if ( isset( $amazon_ps_data['acquirer_response_message'] ) && ! empty( $amazon_ps_data['acquirer_response_message'] ) ) {
			$data['order_extra_data'][] = array(
				'label' => 'Acquirer Response Message',
				'value' => $amazon_ps_data['acquirer_response_message'],
			);
		}
		if ( isset( $amazon_ps_data['customer_ip'] ) && ! empty( $amazon_ps_data['customer_ip'] ) ) {
			$data['order_extra_data'][] = array(
				'label' => 'Customer IP',
				'value' => $amazon_ps_data['customer_ip'],
			);
		}
		if ( isset( $amazon_ps_data['customer_email'] ) && ! empty( $amazon_ps_data['customer_email'] ) ) {
			$data['order_extra_data'][] = array(
				'label' => 'Customer Email',
				'value' => $amazon_ps_data['customer_email'],
			);
		}
		if ( isset( $amazon_ps_data['phone_number'] ) && ! empty( $amazon_ps_data['phone_number'] ) ) {
			$data['order_extra_data'][] = array(
				'label' => 'Phone Number',
				'value' => $amazon_ps_data['phone_number'],
			);
		}
		if ( isset( $amazon_ps_data['third_party_transaction_number'] ) && ! empty( $amazon_ps_data['third_party_transaction_number'] ) ) {
			$data['order_extra_data'][] =  array(
				'label' => 'Third Party Transaction Number',
				'value' => $amazon_ps_data['third_party_transaction_number'],
			);
		}
		if ( isset( $amazon_ps_data['knet_ref_number'] ) && ! empty( $amazon_ps_data['knet_ref_number'] ) ) {
			$data['order_extra_data'][] =  array(
				'label' => 'KNET Ref Number',
				'value' => $amazon_ps_data['knet_ref_number'],
			);
		}
		return $data['order_extra_data'];
	}
	public function getOrderData($orderId, $paymentMethod){
		

        $amazon_ps_data = $this->getAmazonPSMetaValue($orderId, 'amazon_ps_payment_response', true);
        $amazon_ps_check_data = $this->getAmazonPSMetaValue($orderId, 'amazon_ps_check_status_response', true);

        $amazon_ps_data = array_merge((array)$amazon_ps_data, (array)$amazon_ps_check_data);

        $data['order_extra_data'] = [];        
        if($amazon_ps_data){
        	$data['order_extra_data'] = $this->paymentRelatedAdditionData($amazon_ps_data, $orderId, $paymentMethod);
        }

        $data['is_authorization'] = 0;
        $total_captured           = 0;
        $total_void               = 0;
        $this->load->model('sale/order');
        $order_info = $this->model_sale_order->getOrder($orderId);
        $order_total                 = $order_info['total'];
        $data['order_total']         = $this->getConvertedAmt($order_total, $order_info);
        $data['formatted_order_total'] = $this->getConvertedAmt($order_total, $order_info, true);
        $data['capture_history'] = array();

		if ( (! empty( $amazon_ps_data ) && isset( $amazon_ps_data['command'] ) && $amazon_ps_data['command'] == 'AUTHORIZATION' )||(! empty( $amazon_ps_data ) && isset( $amazon_ps_data['query_command'] ) && $amazon_ps_data['query_command'] == 'CHECK_STATUS' && $amazon_ps_data['transaction_code'] == AmazonPSConstant::AMAZON_PS_PAYMENT_AUTHORIZATION_SUCCESS_RESPONSE_CODE)
		) { 
			$data['is_authorization'] = 1;
			
			$data['capture_history'] = $this->getAmazonPSMetaData( $orderId, 'CAPTURE' );
			$total_captured          = array_sum( array_column( $data['capture_history'], 'meta_value' ) );

			$data['void_history'] = $this->getAmazonPSMetaData( $orderId, 'VOID_AUTHORIZATION' );
			$total_void           = array_sum( array_column( $data['void_history'], 'meta_value' ) );

			$data['capture_history'] = array_merge((array)$data['capture_history'], (array)$data['void_history']);


			$remain_capture = $order_total - $total_captured;
			$data['total_captured'] = $this->getConvertedAmt($total_captured, $order_info);
			$data['total_void'] = $this->getConvertedAmt($total_void, $order_info);

			$data['remain_capture'] = $this->getConvertedAmt($remain_capture, $order_info);

			$data['formatted_total_captured'] = $this->getConvertedAmt($total_captured, $order_info, true);
			$data['formatted_total_void'] = $this->getConvertedAmt($total_void, $order_info, true);

			$data['formatted_remain_capture'] = $this->getConvertedAmt($remain_capture, $order_info, true);
		}
		$data['refund_history'] = $this->getAmazonPSMetaData( $orderId, AmazonPSConstant::AMAZON_PS_COMMAND_REFUND );
		$data['transaction_history'] = array_merge((array)$data['capture_history'], (array)$data['refund_history']);

		foreach ($data['transaction_history'] as $key => $value) {
			$data['transaction_history'][$key]['meta_value'] = $this->getConvertedAmt($value['meta_value'], $order_info, true);
		}

		$total_refunded = array_sum( array_column( $data['refund_history'], 'meta_value' ) );
		$data['total_refunded'] = $this->getConvertedAmt($total_refunded, $order_info);

		if($data['is_authorization']){
			$total_refundable = $total_captured-$total_refunded;
		}else{
			$total_refundable = $order_total-$total_refunded;
		}
		// KNET not support refund
		if($order_info['payment_code'] == AmazonPSConstant::AMAZON_PS_PAYMENT_METHOD_KNET){
			$total_refundable = 0;
		}
		$data['total_refundable'] = $this->getConvertedAmt($total_refundable, $order_info);
		$data['formatted_total_refundable'] = $this->getConvertedAmt($total_refundable, $order_info, true);
		$data['formatted_total_refunded'] = $this->getConvertedAmt($total_refunded, $order_info, true);
		return $data;
	}

	public function getConvertedAmt($amount, $order_info, $format = false){
		return $this->currency->format($amount, $order_info['currency_code'], $order_info['currency_value'], $format);	
	}

	public function refund($orderId, $amount, $paymentMethod){
		$json = array();
		$refund_response = $this->doRefund($orderId, $amount, $paymentMethod);
		if ($refund_response['status'] == 'success') {
			
			$json['data'] = $this->getOrderData($orderId, $paymentMethod);
			/*$json['data']['meta_key'] = $refund_response['data']['meta_key'];
			$json['data']['meta_value'] = $refund_response['data']['meta_value'];
			$json['data']['date_added'] = $refund_response['data']['date_added'];*/

			$json['error'] = false;
		} else {
			$json['error'] = true;
			$json['msg'] = isset($refund_response['message']) && !empty($refund_response['message']) ? (string)$refund_response['message'] : 'Unable to refund';
		}
		return $json;
	}

	public function captureVoid($orderId, $amount, $command_type, $paymentMethod){
			$json = array();
			$capture_response = $this->doCaptureVoid($orderId, $amount, $command_type, $paymentMethod);
			if ($capture_response['status'] == 'success') {
				
				$json['data'] = $this->getOrderData($orderId, $paymentMethod);
				/*$json['data']['meta_key'] = $capture_response['data']['meta_key'];
				$json['data']['meta_value'] = $capture_response['data']['meta_value'];
				$json['data']['date_added'] = $capture_response['data']['date_added'];*/

				$json['error'] = false;
			} else {
				$json['error'] = true;
				$json['msg'] = isset($capture_response['message']) && !empty($capture_response['message']) ? (string)$capture_response['message'] : 'Unable to capture';
			}
			return $json;
	}

	public function doCaptureVoid($orderId, $amount, $command_type, $paymentMethod){
		$response_arr = array(
			'status'  => 'success',
			'message' => '',
			'data' => array(),
		);
		try {
			$this->load->model('sale/order');
            $order_info = $this->model_sale_order->getOrder($orderId);

			$amazon_ps_data = $this->getAmazonPSMetaValue($orderId, 'amazon_ps_payment_response', true);

			$amazon_ps_check_data = $this->getAmazonPSMetaValue($orderId, 'amazon_ps_check_status_response', true);

        	$amazon_ps_data = array_merge((array)$amazon_ps_data, (array)$amazon_ps_check_data);

			$access_code = $this->config->get('payment_amazon_ps_access_code');
			$signature_type = 'regular';
			if($paymentMethod == AmazonPSConstant::AMAZON_PS_PAYMENT_METHOD_APPLE_PAY){
				$access_code = $this->config->get('payment_amazon_ps_apple_pay_access_code');
				$signature_type = 'apple_pay';
			}

			$gateway_params  = array(
				'merchant_identifier' => $this->config->get('payment_amazon_ps_merchant_identifier'),
				'access_code'         => $access_code,
				'merchant_reference'  => $orderId,
				'language'            => $this->getOrderLanguage($order_info['language_id'])
			);
		    $total_amount               = $this->convertedToGatewayAmount( $amount, strtoupper($order_info['currency_code'] ), $order_info['currency_value']);
		    if($command_type == AmazonPSConstant::AMAZON_PS_COMMAND_CAPTURE){
				$gateway_params['amount']   = $total_amount;
				$gateway_params['currency'] = $this->getGatewayCurrencyCode($order_info['currency_code']);
			}
			$gateway_params['command']           = $command_type;
			//$gateway_params['fort_id']           = $amazon_ps_data['fort_id'];
			$gateway_params['order_description'] = 'Order#' . $orderId;

			$signature                           = $this->calculateSignature( $gateway_params, 'request', $signature_type );
			$gateway_params['signature']         = $signature;
			$gateway_url                         = $this->getGatewayUrl( 'notificationApi' );
			$this->log( 'APS Capture Void request \n\n' . $gateway_url . json_encode( $gateway_params, true ) );
			$response = $this->callApi( $gateway_params, $gateway_url );
			$this->log( 'APS Capture Void response \n\n' . json_encode( $response, true ) );
			if ( AmazonPSConstant::AMAZON_PS_CAPTURE_SUCCESS_RESPONSE_CODE === $response['response_code'] || AmazonPSConstant::AMAZON_PS_AUTHORIZATION_VOIDED_SUCCESS_RESPONSE_CODE === $response['response_code']) {
				$this->log( 'APS Capture Void response success \n\n');
			} else {
				throw new \Exception( $response['response_message'] );
			}
		} catch ( Exception $e ) {
			$this->log( 'Submit Capture Error \n\n' . $e->getMessage() );
			$response_arr['status']  = 'error';
			$response_arr['message'] = $e->getMessage();
		}
		return $response_arr;
	}

	public function doRefund($orderId, $amount, $paymentMethod){
		$response_arr = array(
			'status'  => 'success',
			'message' => '',
			'data' => array(),
		);
		try {
			$this->load->model('sale/order');
            $order_info = $this->model_sale_order->getOrder($orderId);

            if($paymentMethod == AmazonPSConstant::AMAZON_PS_PAYMENT_METHOD_VALU || $paymentMethod == AmazonPSConstant::AMAZON_PS_PAYMENT_METHOD_NAPS){
            	$total = $this->getConvertedAmt($order_info['total'], $order_info);
            	if($amount < $total){
            		throw new \Exception( 'Partial refund is not available in this payment method' );            		
            	}
            }

            $merchant_reference = $orderId;
            if($paymentMethod == AmazonPSConstant::AMAZON_PS_PAYMENT_METHOD_VALU ){
				$merchant_reference = $this->find_valu_reference_by_order( $orderId );
			}

            $access_code = $this->config->get('payment_amazon_ps_access_code');
            $signature_type = 'regular';
            if($paymentMethod == AmazonPSConstant::AMAZON_PS_PAYMENT_METHOD_APPLE_PAY){
            	$access_code = $this->config->get('payment_amazon_ps_apple_pay_access_code');
            	$signature_type = 'apple_pay';
            }

			$amazon_ps_data = $this->getAmazonPSMetaValue($orderId, 'amazon_ps_payment_response', true);

			$amazon_ps_check_data = $this->getAmazonPSMetaValue($orderId, 'amazon_ps_check_status_response', true);

        	$amazon_ps_data = array_merge((array)$amazon_ps_data, (array)$amazon_ps_check_data);

			$gateway_params  = array(
				'merchant_identifier' => $this->config->get('payment_amazon_ps_merchant_identifier'),
				'access_code'         => $access_code,
				'merchant_reference'  => $merchant_reference,
				'language'            => $this->getOrderLanguage($order_info['language_id'])
			);
		    $total_amount               = $this->convertedToGatewayAmount( $amount, strtoupper($order_info['currency_code'] ), $order_info['currency_value']);
			$gateway_params['amount']   = $total_amount;
			$gateway_params['currency'] = $this->getGatewayCurrencyCode($order_info['currency_code']);
			$gateway_params['command']  = AmazonPSConstant::AMAZON_PS_COMMAND_REFUND;
			//$gateway_params['fort_id']           = $amazon_ps_data['fort_id'];
			$gateway_params['order_description'] = 'Order#' . $orderId;

			$signature                           = $this->calculateSignature( $gateway_params, 'request', $signature_type);
			$gateway_params['signature']         = $signature;
			$gateway_url                         = $this->getGatewayUrl( 'notificationApi' );
			$this->log( 'APS Refund request \n\n' . $gateway_url . json_encode( $gateway_params, true ) );
			$response = $this->callApi( $gateway_params, $gateway_url );
			$this->log( 'APS refund response \n\n' . json_encode( $response, true ) );
			if ( AmazonPSConstant::AMAZON_PS_REFUND_SUCCESS_RESPONSE_CODE === $response['response_code'] ) {
				$this->log( 'APS refund response success\n\n');
			} else {
				throw new \Exception( $response['response_message'] );
			}
		} catch ( Exception $e ) {
			$this->log( 'Submit refund Error \n\n' . $e->getMessage() );
			$response_arr['status']  = 'error';
			$response_arr['message'] = $e->getMessage();
		}
		return $response_arr;
	}

	public function isSandboxMode()
    {
        if ($this->registry->get('config')->get('payment_amazon_ps_sandbox_mode')) {
            return true;
        }
        return false;
    }

    public function getGatewaySandboxNotiApiUrl(){
        return AmazonPSConstant::GATEWAY_SANDBOX_NOTIFICATION_API_URL;
    }

    public function getGatewayProductionNotiApiUrl(){
        return AmazonPSConstant::GATEWAY_PRODUCTION_NOTIFICATION_API_URL;
    }

    public function getGatewaySandboxHostUrl(){
        return AmazonPSConstant::GATEWAY_SANDBOX_URL;
    }

    public function getGatewayProdHostUrl(){
        return AmazonPSConstant::GATEWAY_PRODUCTION_URL;
    }

    public function getRequestShaPhrase(){
        return $this->registry->get('config')->get('payment_amazon_ps_request_sha_phrase');
    }

    public function getResponseShaPhrase(){
        return $this->registry->get('config')->get('payment_amazon_ps_response_sha_phrase');
    }

    public function getShaType(){
        return $this->registry->get('config')->get('payment_amazon_ps_sha_type');
    }

    public function getApplePayShaType(){
        return $this->registry->get('config')->get('payment_amazon_ps_apple_pay_sha_type');
    } 

    public function getApplePayRequestShaPhrase(){
        return $this->registry->get('config')->get('payment_amazon_ps_apple_pay_request_sha_phrase');
    }

    public function getApplePayResponseShaPhrase(){
        return $this->registry->get('config')->get('payment_amazon_ps_apple_pay_response_sha_phrase');
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

    public function isDebugMode()
    {
        if ($this->registry->get('config')->get('payment_amazon_ps_debug')) {
            return true;
        }
        return false;
    }

    public function log($messages, $title = null, $forceDebug = false)
    {
        $debugMode = $this->isDebugMode();
        if (!$debugMode && !$forceDebug) {
            return;
        }
        $log = new Log('amazon_ps_'.date('Y-m-d').'.log');
        $log->write($title .':'. json_encode($messages));
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


	public function convertGatewayAmount($amount, $currency_value, $currency_code)
    {
        $gateway_currency = $this->registry->get('config')->get('payment_amazon_ps_gateway_currency');
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
        return "$new_amount";
    }

    public function convertedToGatewayAmount($amount, $currency_code, $currency_value)
    {
    	$gateway_currency = $this->registry->get('config')->get('payment_amazon_ps_gateway_currency');
    	if ($gateway_currency != 'front') {
    		$amount = $amount/$currency_value;
    	}

        $decimal_points   = $this->getCurrencyDecimalPoints($currency_code);
        if ( 0 !== $decimal_points ) {
            $amount = $amount * (pow(10, $decimal_points));
        }
        return $amount;
    }

    public function getBaseCurrency()
    {
    	return $this->registry->get('config')->get('config_currency');
    }

    public function getGatewayCurrencyCode($currentCurrencyCode)
    {
        $baseCurrencyCode    = $this->getBaseCurrency();
        $gateway_currency = $this->registry->get('config')->get('payment_amazon_ps_gateway_currency');

        $currencyCode     = $baseCurrencyCode;
        if ($gateway_currency == 'front') {
            $currencyCode = $currentCurrencyCode;
        }
        return strtoupper($currencyCode);
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

    public function find_valu_reference_by_order($orderId){
        $qry = $this->db->query("SELECT meta_value FROM `" . DB_PREFIX . "amazon_ps_order_meta_data` WHERE `order_id` = '" . $this->db->escape($orderId ). "' AND `meta_key` = 'valu_reference_id' LIMIT 1");

        if ( $qry->num_rows ) {
            return $qry->row['meta_value'];
        } else {
            return false;
        }
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
                //'Accept: application/json, application/*+json',
                //'Connection:keep-alive'
        ));
        curl_setopt($ch, CURLOPT_URL, $gatewayUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_FAILONERROR, 1);
        curl_setopt($ch, CURLOPT_ENCODING, "compress, gzip");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // allow redirects     
        //curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // return into a variable
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0); // The number of seconds to wait while trying to connect
        //curl_setopt($ch, CURLOPT_TIMEOUT, Yii::app()->params['apiCallTimeout']); // timeout in seconds
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
            $this->log('Api Curl Call error : '.$error_msg);
        }
        curl_close($ch);

        $array_result = json_decode($response, true);

        if (!$response || empty($array_result)) {
            return false;
        }
        return $array_result;
    }
}
