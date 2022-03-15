<?php
class ControllerExtensionPaymentAmazonPS extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/payment/amazon_ps');
		$this->document->setTitle($this->language->get('heading_title'));
		$data['heading_title'] = $this->language->get('heading_title')." ".AmazonPSConstant::AMAZON_PS_VERSION;

		$this->load->model('setting/setting');

		$this->trimIntegrationDetails();

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('payment_amazon_ps', $this->request->post);

			$this->installPaymentMethods();

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		$data['error_amazon_ps_merchant_identifier'] = '';
		$data['error_amazon_ps_access_code'] = '';
		$data['error_amazon_ps_request_sha_phrase'] = '';
		$data['error_amazon_ps_response_sha_phrase'] = '';
		$data['amazon_ps_payment_method_required'] = '';

		if (isset($this->error['error_amazon_ps_merchant_identifier'])) {
			$data['error_amazon_ps_merchant_identifier'] = $this->error['error_amazon_ps_merchant_identifier'];
		}
		if (isset($this->error['error_amazon_ps_access_code'])) {
			$data['error_amazon_ps_access_code'] = $this->error['error_amazon_ps_access_code'];
		}
		if (isset($this->error['error_amazon_ps_request_sha_phrase'])) {
			$data['error_amazon_ps_request_sha_phrase'] = $this->error['error_amazon_ps_request_sha_phrase'];
		}
		if (isset($this->error['error_amazon_ps_response_sha_phrase'])) {
			$data['error_amazon_ps_response_sha_phrase'] = $this->error['error_amazon_ps_response_sha_phrase'];
		}
		if (isset($this->error['amazon_ps_payment_method_required'])) {
			$data['amazon_ps_payment_method_required'] = $this->error['amazon_ps_payment_method_required'];
		}


		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/payment/amazon_ps', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/payment/amazon_ps', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);


		$fields = ['payment_amazon_ps_status',
					'payment_amazon_ps_merchant_identifier',
					'payment_amazon_ps_access_code',
					'payment_amazon_ps_request_sha_phrase',
					'payment_amazon_ps_response_sha_phrase',
					'payment_amazon_ps_sandbox_mode',
					'payment_amazon_ps_command',
					'payment_amazon_ps_sha_type',
					'payment_amazon_ps_gateway_currency',
					'payment_amazon_ps_debug',
					'payment_amazon_ps_order_status_id',
					'payment_amazon_ps_cc_status',
					'payment_amazon_ps_cc_integration_type',
					'payment_amazon_ps_cc_show_mada_branding',
					'payment_amazon_ps_cc_show_meeza_branding',
					'payment_amazon_ps_cc_mada_bins',
					'payment_amazon_ps_cc_meeza_bins',
					'payment_amazon_ps_cc_tokenization',
					'payment_amazon_ps_cc_hide_delete_token',
					'payment_amazon_ps_cc_sort_order',
					'payment_amazon_ps_visa_checkout_status',
					'payment_amazon_ps_visa_checkout_integration_type',
					'payment_amazon_ps_visa_checkout_api_key',
					'payment_amazon_ps_visa_checkout_profile_name',
					'payment_amazon_ps_visa_checkout_sort_order',
					'payment_amazon_ps_installments_status',
					'payment_amazon_ps_installments_integration_type',
					'payment_amazon_ps_installments_aed_order_min_value',
					'payment_amazon_ps_installments_egp_order_min_value',
					'payment_amazon_ps_installments_sar_order_min_value',
					'payment_amazon_ps_installments_issuer_name',
					'payment_amazon_ps_installments_issuer_logo',
					'payment_amazon_ps_installments_sort_order',
					'payment_amazon_ps_naps_status',
					'payment_amazon_ps_naps_sort_order',
					'payment_amazon_ps_knet_status',
					'payment_amazon_ps_knet_sort_order',
					'payment_amazon_ps_valu_status',
					'payment_amazon_ps_valu_order_min_value',
					'payment_amazon_ps_valu_sort_order',
					'payment_amazon_ps_apple_pay_status',
					'payment_amazon_ps_apple_pay_product_page',
					'payment_amazon_ps_apple_pay_cart_page',
					'payment_amazon_ps_apple_pay_sha_type',
					'payment_amazon_ps_apple_pay_btn_type',
					'payment_amazon_ps_apple_pay_access_code',
					'payment_amazon_ps_apple_pay_request_sha_phrase',
					'payment_amazon_ps_apple_pay_response_sha_phrase',
					'payment_amazon_ps_apple_pay_domain_name',
					'payment_amazon_ps_apple_pay_display_name',
					'payment_amazon_ps_apple_pay_supported_network',
					'payment_amazon_ps_apple_pay_production_key',
					'payment_amazon_ps_apple_pay_sort_order',
					'payment_amazon_ps_check_status_cron_duration',
				];

		foreach ($fields as $key => $field) {				
			if (isset($this->request->post[$field])) {
				$data[$field] = $this->request->post[$field];
			} else {
				$data[$field] = $this->config->get($field);
			}
		}

		$cards = array(
				'amex' => $this->language->get('text_amex'),
				'visa' => $this->language->get('text_visa'),
				'masterCard' => $this->language->get('text_masterCard'),
				'mada' => $this->language->get('text_mada')
			);
		$data['cards'] = $cards;
		if ($data['payment_amazon_ps_apple_pay_supported_network'] == null) {
			$data['payment_amazon_ps_apple_pay_supported_network'] = array();
		}
		

		$url = new Url(HTTP_CATALOG, $this->config->get('config_secure') ? HTTPS_CATALOG : HTTP_CATALOG);
        $host_to_host_url = $url->link('extension/payment/amazon_ps/response', '', 'SSL');
        $data['host_to_host_url'] = $host_to_host_url;

       
        $cron_recurring_url = $url->link('extension/payment/amazon_ps/recurring', '', 'SSL');
        $data['cron_recurring_url'] = $cron_recurring_url;

        
        $cron_check_status_url = $url->link('extension/payment/amazon_ps/checkPaymentStatus', '', 'SSL');
        $data['cron_check_status_url'] = $cron_check_status_url;

        $apple_pay_certificate_url = $this->url->link('extension/payment/amazon_ps_apple_pay/certificate', 'user_token=' . $this->session->data['user_token'], true);
        $data['apple_pay_certificate_url'] = $apple_pay_certificate_url;

        $data['payment_amazon_ps_installments_sar_order_min_value'] = isset($data['payment_amazon_ps_installments_sar_order_min_value']) ? $data['payment_amazon_ps_installments_sar_order_min_value'] : 1000;

        $data['payment_amazon_ps_installments_aed_order_min_value'] = isset($data['payment_amazon_ps_installments_aed_order_min_value']) ? $data['payment_amazon_ps_installments_aed_order_min_value'] : 1000;

        $data['payment_amazon_ps_installments_egp_order_min_value'] = isset($data['payment_amazon_ps_installments_egp_order_min_value']) ? $data['payment_amazon_ps_installments_egp_order_min_value'] : 1000;

        $data['payment_amazon_ps_valu_order_min_value'] = isset($data['payment_amazon_ps_valu_order_min_value']) ? $data['payment_amazon_ps_valu_order_min_value'] : 500;

        $data['payment_amazon_ps_cc_mada_bins'] = isset($data['payment_amazon_ps_cc_mada_bins']) ? $data['payment_amazon_ps_cc_mada_bins'] : AmazonPSConstant::MADA_BINS;

        $data['payment_amazon_ps_cc_meeza_bins'] = isset($data['payment_amazon_ps_cc_meeza_bins']) ? $data['payment_amazon_ps_cc_meeza_bins'] : AmazonPSConstant::MEEZA_BINS;

        $data['apple_pay_button_types'] = $this->apple_pay_button_types();
		//$this->load->model('localisation/order_status');
		//$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/payment/amazon_ps', $data));
	}


	public function install() {
		try{
			$this->load->model('extension/payment/amazon_ps');
			$this->model_extension_payment_amazon_ps->install();
			$this->model_extension_payment_amazon_ps->deleteEvents();
			$this->model_extension_payment_amazon_ps->addEvents();
		} catch (Exception $e) {
			echo $e->getMessage();
		}
	}

	public function uninstall() {
		$this->load->model('extension/payment/amazon_ps');
		$this->model_extension_payment_amazon_ps->uninstall();
		$this->model_extension_payment_amazon_ps->deleteEvents();
	}

	public function order(){

		$this->load->language('extension/payment/amazon_ps');
		$orderId = (int)$this->request->get['order_id'];
		$paymentMethod = AmazonPSConstant::AMAZON_PS_PAYMENT_METHOD_CC;

		$this->load->model('extension/payment/amazon_ps');
        $data = $this->model_extension_payment_amazon_ps->getOrderData($orderId, $paymentMethod);
		$data['order_id'] = $orderId;
		$data['user_token'] = $this->session->data['user_token'];
		$data['payment_method'] =$paymentMethod;
		return $this->load->view('extension/payment/amazon_ps_order', $data);
	}

	public function capture() {
		$this->load->language('extension/payment/amazon_ps');
		$json = array();

		if (isset($this->request->post['order_id']) && $this->request->post['order_id'] != '' && isset($this->request->post['amount']) && $this->request->post['amount'] > 0) {

			$this->load->model('extension/payment/amazon_ps');
			$json = $this->model_extension_payment_amazon_ps->captureVoid($this->request->post['order_id'], $this->request->post['amount'], AmazonPSConstant::AMAZON_PS_COMMAND_CAPTURE, $this->request->post['payment_method']);
		} else {
			$json['error'] = true;
			$json['msg'] = $this->language->get('error_data_missing');
		}

		$this->response->setOutput(json_encode($json));
	}

	public function refund() {
		/*$this->load->library('amazonpsconstant');*/
		$this->load->language('extension/payment/amazon_ps');
		$json = array();

		if (isset($this->request->post['order_id']) && $this->request->post['order_id'] != '' && isset($this->request->post['amount']) && $this->request->post['amount'] > 0) {

			$this->load->model('extension/payment/amazon_ps');
			$json = $this->model_extension_payment_amazon_ps->refund($this->request->post['order_id'], $this->request->post['amount'], $this->request->post['payment_method']);
		} else {
			$json['error'] = true;
			$json['msg'] = $this->language->get('error_data_missing');
		}

		$this->response->setOutput(json_encode($json));
	}

	public function void() {
		$this->load->language('extension/payment/amazon_ps');
		$json = array();

		if (isset($this->request->post['order_id']) && $this->request->post['order_id'] != '' && isset($this->request->post['amount']) && $this->request->post['amount'] > 0) {

			$this->load->model('extension/payment/amazon_ps');
			$json = $this->model_extension_payment_amazon_ps->captureVoid($this->request->post['order_id'], $this->request->post['amount'], AmazonPSConstant::AMAZON_PS_COMMAND_VOID_AUTHORIZATION, $this->request->post['payment_method']);
		} else {
			$json['error'] = true;
			$json['msg'] = $this->language->get('error_data_missing');
		}

		$this->response->setOutput(json_encode($json));
	}

	protected function validate()  {
    	if (!$this->user->hasPermission('modify', 'extension/payment/amazon_ps')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		
        if (!$this->request->post['payment_amazon_ps_merchant_identifier']) {
            $this->error['error_amazon_ps_merchant_identifier'] = $this->language->get('error_amazon_ps_merchant_identifier');
        }
        
        if (!$this->request->post['payment_amazon_ps_access_code']) {
            $this->error['error_amazon_ps_access_code'] = $this->language->get('error_amazon_ps_access_code');
        }
        
        if (!$this->request->post['payment_amazon_ps_request_sha_phrase']) {
            $this->error['error_amazon_ps_request_sha_phrase'] = $this->language->get('error_amazon_ps_request_sha_phrase');
        }
        
        if (!$this->request->post['payment_amazon_ps_response_sha_phrase']) {
            $this->error['error_amazon_ps_response_sha_phrase'] = $this->language->get('error_amazon_ps_response_sha_phrase');
        }
        
        if (!$this->request->post['payment_amazon_ps_cc_status']
            && !$this->request->post['payment_amazon_ps_naps_status']
            && !$this->request->post['payment_amazon_ps_installments_status']
            && !$this->request->post['payment_amazon_ps_visa_checkout_status']
            && !$this->request->post['payment_amazon_ps_knet_status']
            && !$this->request->post['payment_amazon_ps_valu_status']
            && $this->request->post['payment_amazon_ps_status'] == 1
        ) {
            $this->error['amazon_ps_payment_method_required'] = $this->language->get('amazon_ps_payment_method_required');
        }
        return !$this->error;
    }

    private function filterPaymentSetting($post, $code) {
        $newPost = array();
        foreach($post as $key => $value) {
            
            if (strpos($key, $code) !== false) {
            	if(isset($this->request->post[$key])) {
			    	$newPost[$key] = $this->request->post[$key]; 
			    }
			}
        }
        return $newPost;
    }

    private function installPaymentMethods()
    {
    	$this->load->model('extension/payment/amazon_ps');
    	$this->load->model('setting/extension');
        $installed_modules = $this->model_setting_extension->getInstalled('payment');
    
		$paymentMethods = [
		'amazon_ps_installments' => 'installments', 
		'amazon_ps_visa_checkout' => 'visa_checkout',
		'amazon_ps_knet' => 'knet',
		'amazon_ps_naps' => 'naps',
		'amazon_ps_valu' => 'valu',
		'amazon_ps_apple_pay' => 'apple_pay'];


		foreach ($paymentMethods as $key => $value) {
			if (!in_array($key, $installed_modules)) {
	            $this->load->model('setting/extension');
	            $this->model_setting_extension->install('payment', $key);
	            
	            $this->load->model('user/user_group');
				$this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/payment/'.$key);
	            $this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/payment/'.$key);

	        }

            $payment_setting = $this->filterPaymentSetting($this->request->post, $value);
            $this->model_setting_setting->editSetting('payment_'.$key, $payment_setting);
		}

		$this->model_extension_payment_amazon_ps->deleteEvents();
		$this->model_extension_payment_amazon_ps->addEvents();
    }

    protected function trimIntegrationDetails() {
        $integration_keys = array(
            'payment_amazon_ps_merchant_identifier',
            'payment_amazon_ps_access_code',
            'payment_amazon_ps_request_sha_phrase',
            'payment_amazon_ps_response_sha_phrase',
            'payment_amazon_ps_visa_checkout_api_key',
            'payment_amazon_ps_visa_checkout_profile_name',
            'payment_amazon_ps_apple_pay_access_code',
            'payment_amazon_ps_apple_pay_request_sha_phrase',
            'payment_amazon_ps_apple_pay_response_sha_phrase',
            'payment_amazon_ps_apple_pay_domain_name',
            'payment_amazon_ps_apple_pay_display_name',
            'payment_amazon_ps_apple_pay_production_key',
            'payment_amazon_ps_cc_mada_bins',
            'payment_amazon_ps_cc_meeza_bins'

        );

        foreach ($this->request->post as $key => $value) {
            if (in_array($key, $integration_keys)) {
                $this->request->post[$key] = trim($value);
            }
        }
    }

    protected function apple_pay_button_types(){
    	$btn = array(
				"apple-pay-buy"        => "BUY",
				"apple-pay-donate"     => "DONATE",
				"apple-pay-plain"      => "PLAIN",
				"apple-pay-set-up"     => "SETUP",
				"apple-pay-book"       => "BOOK",
				"apple-pay-check-out"  => "CHECKOUT",
				"apple-pay-subscribe"  => "SUBSCRIBE",
				"apple-pay-add-money"  => "ADDMONEY",
				"apple-pay-contribute" => "CONTRIBUTE",
				"apple-pay-order"      => "ORDER",
				"apple-pay-reload"     => "RELOAD",
				"apple-pay-rent"       => "RENT",
				"apple-pay-support"    => "SUPPORT",
				"apple-pay-tip"        => "TIP",
				"apple-pay-top-up"     => "TOPUP"
    		);
    	return $btn;
    }
}
