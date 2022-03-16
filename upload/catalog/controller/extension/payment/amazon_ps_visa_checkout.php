<?php
class ControllerExtensionPaymentAmazonPSVisaCheckout extends Controller {

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
        $data['button_confirm'] = $this->language->get('button_confirm');
        $data['text_general_error']  = $this->language->get('text_general_error');
        $data['text_error_card_decline'] = $this->language->get('text_error_card_decline');

        $data['payment_method'] = 'amazon_ps_visa_checkout';
        $template = "amazon_ps";
        if($this->amazonpspaymentservices->getVisaCheckoutIntegrationType() == AmazonPSConstant::AMAZON_PS_INTEGRATION_TYPE_HOSTED_CHECKOUT) {
            $template   = 'amazon_ps_visa_checkout_hosted';

            $data = $this->amazonpsorderpayment->getPaymentRequestParams(AmazonPSConstant::AMAZON_PS_PAYMENT_METHOD_VISA_CHECKOUT, AmazonPSConstant::AMAZON_PS_INTEGRATION_TYPE_HOSTED_CHECKOUT);
            $data['params']['api_key'] = $this->amazonpspaymentservices->getVisaCheckoutApiKey();
            $data['params']['profile_name'] = $this->amazonpspaymentservices->getVisaCheckoutProfileName();
            $data['params']['button_link']  = $this->amazonpspaymentservices->getVisaCheckoutButton();
            $data['params']['js_link']  = $this->amazonpspaymentservices->getVisaCheckoutJS();
            $data['params']['merchant_message'] = $this->config->get("config_name");

            $config_country_id = $this->config->get('config_country_id');
            $this->load->model('localisation/country');
            $country = $this->model_localisation_country->getCountry($config_country_id);

            $country_iso_code_2 = (isset($country['iso_code_2']) ? $country['iso_code_2'] : 'US');
            $data['params']['country_iso_code_2'] = $country_iso_code_2;
        }
		return $this->load->view('extension/payment/'.$template, $data);
	}

	public function send()
    {
        $orderId = $this->amazonpsorderpayment->getSessionOrderId();
    	if( isset($this->request->post['visa_checkout_call_id']) ){
            $url = $this->amazonpsorderpayment->visaCheckoutHosted($this->request->post, AmazonPSConstant::AMAZON_PS_PAYMENT_METHOD_VISA_CHECKOUT);

            $success = 0;
            if($url != false){
                $success = 1;
            }
            $this->aps_model->updateAmazonPSMetaData($orderId, 'aps_redirected_order', 1);
            $this->amazonpspaymentservices->handleRedirectionIssue();
            $this->response->setOutput(json_encode( array('success' => $success, 'url' => $url) ) );
        }else{
            $form = $this->amazonpsorderpayment->getPaymentRequestForm(AmazonPSConstant::AMAZON_PS_PAYMENT_METHOD_VISA_CHECKOUT, $this->amazonpspaymentservices->getVisaCheckoutIntegrationType());
        
            $json = array('form' => $form);
            $this->aps_model->updateAmazonPSMetaData($orderId, 'aps_redirected_order', 1);
            $this->amazonpspaymentservices->handleRedirectionIssue();
            $this->response->setOutput(json_encode($json));
        }
    }

    public function responseOnline()
    {
        $this->amazonpspaymentservices->handleRedirectionIssue();
        $this->_handleResponse('online');
    }

    public function merchantPageResponse()
    {
        $this->amazonpspaymentservices->log('amazon_ps_visa_checkout merchantPageResponse Call: ');
        $this->amazonpspaymentservices->handleRedirectionIssue();
        $integrationType = $this->amazonpspaymentservices->getCcIntegrationType();
        $this->_handleResponse('online', $integrationType);
    }

    private function _handleResponse($response_mode = 'online', $integration_type = AmazonPSConstant::AMAZON_PS_INTEGRATION_TYPE_REDIRECTION)
    {
        $response_params = array_merge($this->request->get, $this->request->post); //never use $_REQUEST, it might include PUT .. etc

        
        $success = $this->amazonpsorderpayment->handleAmazonPSResponse($response_params, $response_mode, $integration_type);

        if ($success) {
            $redirectUrl = 'checkout/success';
        }
        else {
            $redirectUrl = 'checkout/checkout';
        }
        header('location:' . $this->url->link($redirectUrl));

    }
   
}