<?php
class ControllerExtensionPaymentAmazonPSValu extends Controller {

	protected $registry;
    private $aps_model;
    private $amazonpspaymentservices;
    private $amazonpsorderpayment;

    public function __construct($registry)
    {
    	$this->registry = $registry;

        $this->load->model('extension/payment/amazon_ps');
        $this->aps_model = $this->model_extension_payment_amazon_ps;

        $this->amazonpspaymentservices = new AmazonPSPaymentServices($registry);
        $this->amazonpsorderpayment    = new AmazonPSOrderPayment($registry);
    }

	public function index() {

		$this->language->load('extension/payment/amazon_ps');
        $this->load->model('extension/payment/amazon_ps_valu');

        $data['button_confirm'] = $this->language->get('button_confirm');
        $data['button_request_otp'] = $this->language->get('button_request_otp');  
        $data['button_verify_otp'] = $this->language->get('button_verify_otp');  
        $data['text_field_mobile_no'] = $this->language->get('text_field_mobile_no');  
        $data['text_field_otp'] = $this->language->get('text_field_otp');
        $data['text_valu_agree'] = sprintf($this->language->get('text_valu_agree'),$this->url->link('extension/payment/amazon_ps_valu/valuTerms'));
        $data['text_valu_select_plan'] = $this->language->get('text_valu_select_plan');
        $data['language'] = $this->amazonpspaymentservices->getLanguage();

        $arr_js_messages =  array(
            'required_field'    => $this->language->get('required_field'),
            'valu_pending_msg'  => $this->language->get('valu_pending_msg'),
            'valu_select_plan'  => $this->language->get('valu_select_plan'),
            'valu_terms_msg'    => $this->language->get('valu_terms_msg'),
            'valu_invalid_mobile'    => $this->language->get('valu_invalid_mobile'),
        );
        $data['amazon_ps_error_js_msg'] = $this->amazonpspaymentservices->loadJsMessages($arr_js_messages);
        
        $data['country_code'] = AmazonPSConstant::AMAZON_PS_VALU_EG_COUNTRY_CODE;
        $template = 'amazon_ps_valu';
        $data['payment_method'] = 'amazon_ps_valu';

        $orderId = $this->amazonpsorderpayment->getSessionOrderId();
        $this->aps_model->updatePaymentMethod($orderId, $this->language->get('text_title_valu'));

		return $this->load->view('extension/payment/'.$template, $data);
	}

	public function send()
    {
        $this->language->load('extension/payment/amazon_ps');
        $active_tenure = filter_input( INPUT_POST, 'active_tenure' );
        $tenure_amount = filter_input( INPUT_POST, 'tenure_amount' );
        $tenure_interest = filter_input( INPUT_POST, 'tenure_interest' );
        $orderId = $this->amazonpsorderpayment->getSessionOrderId();
        if ( empty( $active_tenure ) ) {
            $result['error'] = true;
            $result['error_message'] = $this->language->get('text_plan_select');
        }else{
            if(isset($this->session->data['amazon_ps_valu'])){
                $reference_id          = $this->session->data['amazon_ps_valu']['reference_id'];
                $mobile_number         = $this->session->data['amazon_ps_valu']['mobile_number'];
                $otp            = $this->session->data['amazon_ps_valu']['otp'];
                $transaction_id = $this->session->data['amazon_ps_valu']['transaction_id'];

                $response = $this->amazonpsorderpayment->valu_execute_purchase($mobile_number, $reference_id, $otp, $transaction_id, $active_tenure );
                $redirect_link     = '';
                if ( 'success' === $response['status'] ) {

                    if ( ! empty( $active_tenure ) ) {
                        $this->aps_model->updateAmazonPSMetaData($orderId, 'valu_active_tenure', $active_tenure);
                    }
                    if ( ! empty( $tenure_amount ) ) {
                        $this->aps_model->updateAmazonPSMetaData($orderId, 'valu_tenure_amount', $tenure_amount);
                    }
                    if ( ! empty( $tenure_interest ) ) {
                        $this->aps_model->updateAmazonPSMetaData($orderId, 'valu_tenure_interest', $tenure_interest);
                    }
                    $redirect_link = 'checkout/success';
                } else {
                    $redirect_link         = 'checkout/checkout';
                    $this->amazonpspaymentservices->setFlashMsg($response['message'], AmazonPSConstant::AMAZON_PS_FLASH_MSG_ERROR);
                }
            }else{
                $redirect_link         = 'checkout/checkout';                
                $this->amazonpspaymentservices->setFlashMsg($this->language->get('error_transaction_error_1'), AmazonPSConstant::AMAZON_PS_FLASH_MSG_ERROR);
            }
            $result = array(
                'result'        => 'success',
                'redirect_link' => $this->url->link($redirect_link)
            );
        }
        $this->aps_model->updateAmazonPSMetaData($orderId, 'aps_redirected_order', 1);
        $this->amazonpspaymentservices->handleRedirectionIssue();
        $this->response->setOutput(json_encode($result));
    }

    public function valuCustomerVerify()
    {
        $mobile_number = filter_input( INPUT_POST, 'mobile_number' );

        if ( empty( $mobile_number )  ) {
            $response['error'] = true;
            $response['error_message'] = $this->language->get('text_moblie_missing');
        }else{
            $response = $this->amazonpsorderpayment->valu_verify_customer($mobile_number);
        }
        $this->response->setOutput(json_encode($response));
    }

    public function valuGenerateOtp()
    {
        $mobile_number = filter_input( INPUT_POST, 'mobile_number' );

        if ( empty( $mobile_number )  ) {
            $response['error'] = true;
            $response['error_message'] = $this->language->get('text_moblie_missing');
        }else{
            $reference_id          = $this->session->data['amazon_ps_valu']['reference_id'];
            $mobile_number         = $this->session->data['amazon_ps_valu']['mobile_number'];
            $response = $this->amazonpsorderpayment->valu_generate_otp( $mobile_number, $reference_id);
            $orderId = $this->amazonpsorderpayment->getSessionOrderId();
            $this->aps_model->saveUpdateValuOrderReferenceId($orderId, $reference_id);
        }
        $this->response->setOutput(json_encode($response));
    }

    public function valuOtpVerify()
    {
        $otp = filter_input( INPUT_POST, 'otp' );

        if ( empty( $otp )  ) {
            $response['error'] = true;
            $response['error_message'] = $this->language->get('text_otp_missing');
        }else{
            $reference_id          = $this->session->data['amazon_ps_valu']['reference_id'];
            $mobile_number         = $this->session->data['amazon_ps_valu']['mobile_number'];
            $response = $this->amazonpsorderpayment->valu_verfiy_otp($mobile_number, $reference_id, $otp);
        }
        $this->response->setOutput(json_encode($response));
    }

    public function valuTerms(){

        if ( $this->amazonpspaymentservices->getLanguage() == 'ar'){
            $output = html_entity_decode($this->load->view('extension/payment/amazon_ps_valu_terms_ar', array()), ENT_QUOTES, 'UTF-8') . "\n";
        }else{
            $output = html_entity_decode($this->load->view('extension/payment/amazon_ps_valu_terms_en', array()), ENT_QUOTES, 'UTF-8') . "\n";
        }
        $this->response->addHeader('X-Robots-Tag: noindex');

        $this->response->setOutput($output);
    }   
}