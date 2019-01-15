<?php

require_once DIR_SYSTEM . '/library/payfortFort/init.php';

class ControllerPaymentPayfortFort extends Controller {

    public $paymentMethod;
    public $integrationType;
    public $pfConfig;
    public $pfPayment;
    public $pfHelper;
    public $pfOrder;

    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->pfConfig        = Payfort_Fort_Config::getInstance($registry);
        $this->pfPayment       = Payfort_Fort_Payment::getInstance($registry);
        $this->pfHelper        = Payfort_Fort_Helper::getInstance($registry);
        $this->pfOrder         = new Payfort_Fort_Order($registry);
        $this->integrationType = $this->pfConfig->getCcIntegrationType();
        $this->paymentMethod   = PAYFORT_FORT_PAYMENT_METHOD_CC;
    }
    
    public function index() {
        
        $this->language->load('extension/payment/payfort_fort');
        $data['button_confirm'] = $this->language->get('button_confirm');
        $data['text_general_error']  = $this->language->get('text_general_error');
        $data['text_error_card_decline'] = $this->language->get('text_error_card_decline');

        $data['payfort_fort_cc_integration_type'] = $this->integrationType;
        
        $this->load->model('extension/payment/payfort_fort');
        $data['payment_request_params'] = '';
        $template = 'payfort_fort';
        if ($this->pfConfig->isCcMerchantPage()) {
            $template                             = 'payfort_fort_merchant_page';
            $data['payment_request_params'] = $this->pfPayment->getPaymentRequestParams($this->paymentMethod, $this->integrationType);
            //$this->model_checkout_order->addOrderHistory($order_id, 1, 'Pending Payment', false);
        }
         elseif ($this->pfConfig->isCcMerchantPage2()) {
            $template                             = 'payfort_fort_merchant_page2';
            $data['payment_request_params'] = $this->pfPayment->getPaymentRequestParams($this->paymentMethod, $this->integrationType);
            
            $data['text_credit_card'] = $this->language->get('text_credit_card');
            $data['text_card_holder_name'] = $this->language->get('text_card_holder_name');
            $data['text_card_number'] = $this->language->get('text_card_number');
            $data['text_expiry_date'] = $this->language->get('text_expiry_date');
            $data['text_cvc_code'] = $this->language->get('text_cvc_code');
            $data['help_cvc_code'] = $this->language->get('help_cvc_code');
            
            $arr_js_messages = 
                    array(
                        'error_invalid_card_number' => $this->language->get('error_invalid_card_number'),
                        'error_invalid_card_holder_name' => $this->language->get('error_invalid_card_holder_name'),
                        'error_invalid_expiry_date' => $this->language->get('error_invalid_expiry_date'),
                        'error_invalid_cvc_code' => $this->language->get('error_invalid_cvc_code'),
                        'error_invalid_cc_details' => $this->language->get('error_invalid_cc_details'),
                    );
                    
            $data['arr_js_messages'] = $this->pfHelper->loadJsMessages($arr_js_messages);
            $data['months'] = array();

            for ($i = 1; $i <= 12; $i++) {
                    $data['months'][] = array(
                            'text'  => strftime('%B', mktime(0, 0, 0, $i, 1, 2000)), 
                            'value' => sprintf('%02d', $i)
                    );
            }

            $today = getdate();

            $data['year_expire'] = array();

            for ($i = $today['year']; $i < $today['year'] + 11; $i++) {
                    $data['year_expire'][] = array(
                            'text'  => strftime('%Y', mktime(0, 0, 0, 1, 1, $i)),
                            'value' => strftime('%y', mktime(0, 0, 0, 1, 1, $i)) 
                    );
            }
        }
        
        if (version_compare(VERSION, '2.2.0.0') >= 0) {
            $this->template = 'extension/payment/'.$template;
        }
        else {
            if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/extension/payment/' . $template)) {
                $this->template = $this->config->get('config_template') . '/template/extension/payment/' . $template;
            } else {
                $this->template = 'default/template/extension/payment/' . $template;
            }
        }
        return $this->load->view($this->template, $data);
    }
    
    public function send()
    {
        $form = $this->pfPayment->getPaymentRequestForm($this->paymentMethod);
        
        $json = array('form' => $form);
        $this->response->setOutput(json_encode($json));
    }
    
    public function response()
    {
        $this->_handleResponse('offline');
    }

    public function responseOnline()
    {
        $this->_handleResponse('online');
    }

    public function merchantPageResponse()
    {
        $this->_handleResponse('online', $this->integrationType);
    }
    
    private function _handleResponse($response_mode = 'online', $integration_type = PAYFORT_FORT_INTEGRATION_TYPE_REDIRECTION)
    {
        $response_params = array_merge($this->request->get, $this->request->post); //never use $_REQUEST, it might include PUT .. etc

        $success = $this->pfPayment->handleFortResponse($response_params, $response_mode, $integration_type);
        if ($success) {
            $redirectUrl = 'extension/payment/payfort_fort/success';
        }
        else {
            $redirectUrl = 'checkout/checkout';
        }
        if ($this->pfConfig->isCcMerchantPage()) {
            echo '<script>window.top.location.href = "' . $this->url->link($redirectUrl) . '"</script>';
            exit;
        }
        else {
            header('location:' . $this->url->link($redirectUrl));
        }
    }

    public function merchantPageCancel()
    {
        $this->pfPayment->merchantPageCancel();
        header('location:' . $this->url->link('checkout/checkout'));
    }
    
    public function success() {
		$this->load->language('checkout/success');
		$this->load->language('extension/payment/payfort_fort');

		if (isset($this->session->data['order_id'])) {
			$this->cart->clear();

			// Add to activity log
			$this->load->model('account/activity');

			if ($this->customer->isLogged()) {
				$activity_data = array(
					'customer_id' => $this->customer->getId(),
					'name'        => $this->customer->getFirstName() . ' ' . $this->customer->getLastName(),
					'order_id'    => $this->session->data['order_id']
				);

				$this->model_account_activity->addActivity('order_account', $activity_data);
			} else {
				$activity_data = array(
					'name'     => $this->session->data['guest']['firstname'] . ' ' . $this->session->data['guest']['lastname'],
					'order_id' => $this->session->data['order_id']
				);

				$this->model_account_activity->addActivity('order_guest', $activity_data);
			}

			unset($this->session->data['shipping_method']);
			unset($this->session->data['shipping_methods']);
			unset($this->session->data['payment_method']);
			unset($this->session->data['payment_methods']);
			unset($this->session->data['guest']);
			unset($this->session->data['comment']);
			unset($this->session->data['order_id']);
			unset($this->session->data['coupon']);
			unset($this->session->data['reward']);
			unset($this->session->data['voucher']);
			unset($this->session->data['vouchers']);
			unset($this->session->data['totals']);
		}

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_basket'),
			'href' => $this->url->link('checkout/cart')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_checkout'),
			'href' => $this->url->link('checkout/checkout', '', 'SSL')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_p_success'),
			'href' => $this->url->link('checkout/success')
		);

		$data['heading_title'] = $this->language->get('heading_success_title');

		if ($this->customer->isLogged()) {
			$data['text_message'] = sprintf($this->language->get('text_success_customer'), $this->url->link('account/account', '', 'SSL'), $this->url->link('account/order', '', 'SSL'), $this->url->link('account/download', '', 'SSL'), $this->url->link('information/contact'));
		} else {
			$data['text_message'] = sprintf($this->language->get('text_success_guest'), $this->url->link('information/contact'));
		}

		$data['button_continue'] = $this->language->get('button_continue');

		$data['continue'] = $this->url->link('common/home');

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

                $template = '';
                if (version_compare(VERSION, '2.2.0.0') >= 0) {
                    $template = 'common/success';
                }
                else {
                    if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/common/success')) {
                        $template = $this->config->get('config_template') . '/template/common/success';
                    } else {
                        $template = 'default/template/common/success';
                    }
                }

                $this->response->setOutput($this->load->view($template, $data));
	}
}

