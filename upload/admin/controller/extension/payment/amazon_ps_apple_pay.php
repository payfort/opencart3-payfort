<?php
class ControllerExtensionPaymentAmazonPSApplePay extends Controller {
	private $error = array();

	public function certificate()
	{
		$this->load->language('extension/payment/amazon_ps');
		$this->load->language('extension/payment/amazon_ps_apple_pay');
		$this->document->setTitle($this->language->get('heading_title_apple_pay'));

		if (isset($this->session->data['upload_error'])) {
			$data['error_warning'] = $this->session->data['upload_error'];
			unset($this->session->data['upload_error']);
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->session->data['upload_success'])) {
			$data['upload_success'] = $this->language->get('upload_success');
			unset($this->session->data['upload_success']);
		} else {
			$data['upload_success'] = '';
		}

		$data['error_amazon_ps_merchant_identifier'] = '';
		$data['amazon_ps_payment_method_required'] = '';

		if (isset($this->error['error_amazon_ps_response_sha_phrase'])) {
			$data['error_amazon_ps_response_sha_phrase'] = $this->error['error_amazon_ps_response_sha_phrase'];
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
			'text' => $this->language->get('heading_title_apple_pay'),
			'href' => $this->url->link('extension/payment/amazon_ps', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('apple_pay_certificate'),
			'href' => $this->url->link('extension/payment/amazon_ps_apple_pay/certificate', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/payment/amazon_ps_apple_pay/upload', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('extension/payment/amazon_ps', 'user_token=' . $this->session->data['user_token'], true);


		$fields = [	'amazon_ps_apple_pay_certificate_file',
					'amazon_ps_apple_pay_certificate_key_file',
				];

		foreach ($fields as $key => $field) {				
			if (isset($this->request->post[$field])) {
				$data[$field] = DIR_UPLOAD.$this->request->post[$field];
			} else {
				$data[$field] = DIR_UPLOAD.$this->config->get($field);
			}
		}
		
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/payment/amazon_ps_apple_pay_certificate', $data));
	}

	public function upload(){
		$this->load->language('extension/payment/amazon_ps_apple_pay');

		$error = '';
		$succes =true;

		// Check user has permission
		if (!$this->user->hasPermission('modify', 'extension/payment/amazon_ps_apple_pay')) {
			$error = $this->language->get('error_permission');
		}

		
		if (!$error) {
			if ((!empty($this->request->files['certificate_file']['name']) && is_file($this->request->files['certificate_file']['tmp_name']))|| 
				(!empty($this->request->files['certificate_key_file']['name']) && is_file($this->request->files['certificate_key_file']['tmp_name']))) {				
				// Return any upload error
				if ($this->request->files['certificate_file']['error'] != UPLOAD_ERR_OK) {
					$error = $this->language->get('error_upload_' . $this->request->files['certificate_file']['error']);
				}
				if ($this->request->files['certificate_key_file']['error'] != UPLOAD_ERR_OK) {
					$error = $this->language->get('error_upload_' . $this->request->files['certificate_key_file']['error']);
				}
				if(!$error){
					$this->uploadFile('certificate_file');
					$this->uploadFile('certificate_key_file');
				}
			} else {
				$error = $this->language->get('error_upload');
			}
		}
		if($error){
			$this->session->data['upload_error'] = $error;
			$succes = false;
		}else{
			$this->session->data['upload_success'] = $succes;
			$this->load->model('setting/setting');
			$this->model_setting_setting->editSetting('amazon_ps_apple_pay', $this->request->post);
		}
		$this->response->redirect($this->url->link('extension/payment/amazon_ps_apple_pay/certificate', 'user_token=' . $this->session->data['user_token'], true));
	}

	public function uploadFile($file){
		try{
			// Where the file is going to be stored
			$path = pathinfo($this->request->files[$file]['name']);

			$filename = $path['filename'];
			$ext = $path['extension'];

			$temp_name = $this->request->files[$file]['tmp_name'];

			$path_filename_ext = DIR_UPLOAD . basename(html_entity_decode($filename.".".$ext, ENT_QUOTES, 'UTF-8'));
			 
			if (move_uploaded_file($temp_name, $path_filename_ext)) {

			}else {
				$msg = "An error occurred while file upload";
				throw new Exception($msg);
			}
			$this->request->post['amazon_ps_apple_pay_'.$file] = $filename.".".$ext;
		}catch(Exception $e){
			$this->session->data['upload_error'] = $e->getMessage();
		}
	}

	public function order(){

		$this->load->language('extension/payment/amazon_ps');
		$orderId = (int)$this->request->get['order_id'];
		$paymentMethod = AmazonPSConstant::AMAZON_PS_PAYMENT_METHOD_APPLE_PAY;

		$this->load->model('extension/payment/amazon_ps');
		$data = $this->model_extension_payment_amazon_ps->getOrderData($orderId, $paymentMethod);
		$data['order_id'] = $orderId;
		$data['user_token'] = $this->session->data['user_token'];
		$data['payment_method'] =$paymentMethod;
		return $this->load->view('extension/payment/amazon_ps_order', $data);
	}
}
?>