<?php
class ControllerExtensionPaymentAmazonPSVisaCheckout extends Controller {
	private $error = array();

	public function order(){

		/*$this->load->library('amazonpsconstant');*/
		$this->load->language('extension/payment/amazon_ps');
		$orderId = (int)$this->request->get['order_id'];
		$paymentMethod = AmazonPSConstant::AMAZON_PS_PAYMENT_METHOD_VISA_CHECKOUT;

		$this->load->model('extension/payment/amazon_ps');
        $data = $this->model_extension_payment_amazon_ps->getOrderData($orderId, $paymentMethod);
		$data['order_id'] = $orderId;
		$data['user_token'] = $this->session->data['user_token'];
		$data['payment_method'] =$paymentMethod;
		return $this->load->view('extension/payment/amazon_ps_order', $data);
	}

	public function capture() {
		/*$this->load->library('amazonpsconstant');*/
		$this->load->language('extension/payment/amazon_ps');
		$json = array();

		if (isset($this->request->post['order_id']) && $this->request->post['order_id'] != '' && isset($this->request->post['amount']) && $this->request->post['amount'] > 0) {

			$this->load->model('extension/payment/amazon_ps');
			$json = $this->model_extension_payment_amazon_ps->captureVoid($this->request->post['order_id'], $this->request->post['amount'], AmazonPSConstant::AMAZON_PS_COMMAND_CAPTURE, AmazonPSConstant::AMAZON_PS_PAYMENT_METHOD_VISA_CHECKOUT);
		} else {
			$json['error'] = true;
			$json['msg'] = $this->language->get('error_data_missing');
		}

		$this->response->setOutput(json_encode($json));
	}

	public function void() {
		/*$this->load->library('amazonpsconstant');*/
		$this->load->language('extension/payment/amazon_ps');
		$json = array();

		if (isset($this->request->post['order_id']) && $this->request->post['order_id'] != '' && isset($this->request->post['amount']) && $this->request->post['amount'] > 0) {

			$this->load->model('extension/payment/amazon_ps');
			$json = $this->model_extension_payment_amazon_ps->captureVoid($this->request->post['order_id'], $this->request->post['amount'], AmazonPSConstant::AMAZON_PS_COMMAND_VOID_AUTHORIZATION, AmazonPSConstant::AMAZON_PS_PAYMENT_METHOD_VISA_CHECKOUT);
		} else {
			$json['error'] = true;
			$json['msg'] = $this->language->get('error_data_missing');
		}

		$this->response->setOutput(json_encode($json));
	}
}
?>