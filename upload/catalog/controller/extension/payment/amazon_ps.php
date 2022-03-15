<?php
class ControllerExtensionPaymentAmazonPS extends Controller {

	protected $registry;
	private $aps_token;
	private $aps_model;
    private $amazonpspaymentservices;
    private $amazonpsorderpayment;
	
	public function __construct($registry)
    {
    	$this->registry = $registry;

        $this->load->model('extension/payment/amazon_ps_tokens');
        $this->aps_token = $this->model_extension_payment_amazon_ps_tokens;

        $this->load->model('extension/payment/amazon_ps');
        $this->aps_model = $this->model_extension_payment_amazon_ps;

        $this->amazonpspaymentservices = new AmazonPSPaymentServices($registry);
        $this->amazonpsorderpayment    = new AmazonPSOrderPayment($registry);
    }

	public function index() {

		$this->amazonpspaymentservices->log('Amazon_ps Index Call: ');

		$this->language->load('extension/payment/amazon_ps');
        
        $data['button_confirm'] = $this->language->get('button_confirm');
        $data['text_general_error']  = $this->language->get('text_general_error');
        $data['text_error_card_decline'] = $this->language->get('text_error_card_decline');
	
		
		$integrationType = $this->amazonpspaymentservices->getCcIntegrationType();
        $data['amazon_ps_cc_integration_type'] = $integrationType;

        $data['payment_request_params'] = '';
        $template = 'amazon_ps';
        $data['payment_method'] = 'amazon_ps';

        if ($this->amazonpspaymentservices->isCcStandardCheckout()) {
            $template                             = 'amazon_ps_standard_checkout';
        }
         elseif ($this->amazonpspaymentservices->isCcHostedCheckout()) {
            $template                               = 'amazon_ps_hosted_checkout';
            $data['text_credit_card'] = $this->language->get('text_credit_card');
            $data['text_card_holder_name'] = $this->language->get('text_card_holder_name');
            $data['text_card_number'] = $this->language->get('text_card_number');
            $data['text_expiry_date'] = $this->language->get('text_expiry_date');
            $data['text_cvc_code'] = $this->language->get('text_cvc_code');
            $data['help_cvc_code'] = $this->language->get('help_cvc_code');
                       
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
            $data['card_inline_icon'] = $this->aps_model->get_card_inline_icon();
            $data['mada_bins'] = $this->amazonpspaymentservices->getMadaBins();
            $data['meeza_bins'] = $this->amazonpspaymentservices->getMeezaBins();
        }
        $arr_js_messages =  array(
            'invalid_card_length'      => $this->language->get('invalid_card_length'),
            'card_empty'               => $this->language->get('card_empty'),
            'invalid_card'             => $this->language->get('invalid_card'),
            'invalid_card_holder_name' => $this->language->get('invalid_card_holder_name'),
            'invalid_card_cvv'         => $this->language->get('invalid_card_cvv'),
            'invalid_expiry_month'     => $this->language->get('invalid_expiry_month'),
            'invalid_expiry_year'      => $this->language->get('invalid_expiry_year'),
            'invalid_expiry_date'      => $this->language->get('invalid_expiry_date'),
            'required_field'           => $this->language->get('required_field'),
        );
                
        $data['amazon_ps_error_js_msg'] = $this->amazonpspaymentservices->loadJsMessages($arr_js_messages);

        $data['is_enabled_tokenization'] = $this->amazonpspaymentservices->isEnabledTokenization();

        $tokens_data = array();
        $data['has_recurring_products'] = 0;
        $display_add_new_card = '';
        if($data['is_enabled_tokenization']){
            $tokens = $this->aps_token->getTokens();
            if($this->cart->hasRecurringProducts()){
                $data['has_recurring_products'] = 1;
                $tokens = array_filter(
                    $tokens,
                    function( $token_row ) {
                        if ( in_array( $token_row['extras']['card_type'], array( 'visa', 'mastercard', 'amex' ), true ) ) {
                            return true;
                        } else {
                            return false;
                        }
                    }
                );
            }
    		$tokens_data = array(
                'tokens' => $tokens,
                'tokenization_card_icons' => $this->amazonpspaymentservices->getTokenizationCardIcons(),
            );
        }

        if($integrationType == AmazonPSConstant::AMAZON_PS_INTEGRATION_TYPE_HOSTED_CHECKOUT && (empty($tokens) || !($data['is_enabled_tokenization']))){
                $display_add_new_card = 'style="display:none"';
        }
        $tokens_data['display_add_new_card'] = $display_add_new_card;
        $data['tokenization_view'] = $this->load->view('extension/payment/amazon_ps_tokenization', $tokens_data );

        $data['embedded_hosted_checkout'] = $this->amazonpspaymentservices->get_enabled_embedded_hosted_checkout();

        $orderId = $this->amazonpsorderpayment->getSessionOrderId();
        $this->aps_model->updatePaymentMethod($orderId, $this->language->get('text_title'));

        return $this->load->view('extension/payment/'.$template, $data);
	}

	public function send()
    {
        $this->language->load('extension/payment/amazon_ps');
        $orderId = $this->amazonpsorderpayment->getSessionOrderId();

        $this->amazonpspaymentservices->log('Amazon_ps Send Call orderId#: '.$orderId);
        $extras = array();
		
        if ( isset( $this->request->post['aps_payment_token_cc'] ) && ! empty( $this->request->post['aps_payment_token_cc'] ) ) {
            $extras['aps_payment_token'] = trim( $this->request->post['aps_payment_token_cc'], ' ' );
        }
        if ( isset( $this->request->post['aps_payment_card_bin_cc'] ) && ! empty( $this->request->post['aps_payment_card_bin_cc'] ) ) {
            $extras['aps_card_bin'] = trim( $this->request->post['aps_payment_card_bin_cc'], ' ' );
        }
        if ( isset( $this->request->post['aps_card_security_code'] ) && ! empty( $this->request->post['aps_card_security_code'] ) ) {
            $extras['aps_payment_cvv'] = trim( $this->request->post['aps_card_security_code'], ' ' );
        }

        /*embdeed hosted checkout*/
        $em_installment_plan_code       = filter_input( INPUT_POST, 'aps_em_installment_plan_code' );
        $em_installment_issuer_code    = filter_input( INPUT_POST, 'aps_em_installment_issuer_code' );
        $em_installment_confirmation_en = filter_input( INPUT_POST, 'aps_em_installment_confirmation_en' );
        $em_installment_confirmation_ar = filter_input( INPUT_POST, 'aps_em_installment_confirmation_ar' );
        $em_installment_interest        = filter_input( INPUT_POST, 'aps_em_installment_interest' );
        $em_installment_amount          = filter_input( INPUT_POST, 'aps_em_installment_amount' );
		if ( ! empty( $em_installment_plan_code ) ) {
            $this->aps_model->updateAmazonPSMetaData($orderId, 'em_installment_plan_code', $em_installment_plan_code);
        }

        if ( ! empty( $em_installment_issuer_code ) ) {
            $this->aps_model->updateAmazonPSMetaData($orderId, 'em_installment_issuer_code', $em_installment_issuer_code);
        }

        if ( ! empty( $em_installment_confirmation_en ) ) {
            $this->aps_model->updateAmazonPSMetaData($orderId, 'em_installment_confirmation_en', $em_installment_confirmation_en);
        }

        if ( ! empty( $em_installment_confirmation_ar ) ) {
            $this->aps_model->updateAmazonPSMetaData($orderId, 'em_installment_confirmation_ar', $em_installment_confirmation_ar);
        }

        if ( ! empty( $em_installment_interest ) ) {
            $this->aps_model->updateAmazonPSMetaData($orderId, 'em_installment_interest', $em_installment_interest);
        }

        if ( ! empty( $em_installment_amount ) ) {
            $this->aps_model->updateAmazonPSMetaData($orderId, 'em_installment_amount', $em_installment_amount);
        }


        if($this->amazonpspaymentservices->getCcIntegrationType() == AmazonPSConstant::AMAZON_PS_INTEGRATION_TYPE_REDIRECTION){
            $form = $this->amazonpsorderpayment->getPaymentRequestForm(AmazonPSConstant::AMAZON_PS_PAYMENT_METHOD_CC, $this->amazonpspaymentservices->getCcIntegrationType(), $extras );
            
            $json = array('form' => $form);
        }else{
            $json = $this->amazonpsorderpayment->getPaymentRequestParams(AmazonPSConstant::AMAZON_PS_PAYMENT_METHOD_CC, $this->amazonpspaymentservices->getCcIntegrationType(), $extras );
        }
        // if cart have recurring product, create recurring order with type date_added
        if($this->cart->hasRecurringProducts()){            
            $order = $this->amazonpsorderpayment->loadOrder($orderId);
            $this->amazonpsorderpayment->createRecurringOrder($order, AmazonPSConstant::AMAZON_PS_PAYMENT_METHOD_CC);
        }
        $this->aps_model->updateAmazonPSMetaData($orderId, 'aps_redirected_order', 1);
        $this->amazonpspaymentservices->handleRedirectionIssue();
        $this->response->setOutput(json_encode($json));
    }

    public function response()
    {
    	$this->amazonpspaymentservices->log('Amazon_ps Response Call: ');
        $this->_handleResponse('offline');
    }

    public function responseOnline()
    {
    	$this->amazonpspaymentservices->log('Amazon_ps ResponseOnline Call: ');
        $this->amazonpspaymentservices->handleRedirectionIssue();
        $this->_handleResponse('online');
    }

    // for cron job to make recurring transaction
    public function recurring($order_recurring_id = 0){
        $this->amazonpspaymentservices->log("recurringOrderTransaction recurring \n");

        /*$this->load->model('checkout/order');*/
        foreach ($this->aps_model->nextRecurringOrderPayments($order_recurring_id) as $payment) {
            $recurring = $this->aps_model->getRecurring($payment['order_recurring_id']);
            $this->amazonpsorderpayment->doRecurringOrderTransaction($recurring);
        };
    }

    /**
     * cron job for check order payment status for pending order
     */
    public function checkPaymentStatus(){
        $this->amazonpspaymentservices->log("checkPaymentStatus \n");
        foreach ($this->aps_model->getPaymentPendingOrders() as $order) {
            $this->amazonpsorderpayment->doCheckPaymentStatus($order);
        };
    }

    private function _handleResponse($response_mode = 'online', $integration_type = AmazonPSConstant::AMAZON_PS_INTEGRATION_TYPE_REDIRECTION)
    {
        $response_params = array_merge($this->request->get, $this->request->post); //never use $_REQUEST
        if ( empty( $response_params ) ) {
            $params = file_get_contents( 'php://input' );
            if (!empty($params)) {
                $response_params = json_decode(filter_var($params, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES), true);
                $this->amazonpspaymentservices->log('Webhook params '.print_r($response_params,1));
            }
        }

        $success = $this->amazonpsorderpayment->handleAmazonPSResponse($response_params, $response_mode, $integration_type);

        if ($success) {
            $redirectUrl = 'checkout/success';
        }
        else {
            $redirectUrl = 'checkout/checkout';
        }
        if ( 'offline' === $response_mode ) {
            $this->amazonpspaymentservices->log('Webhook processed ');
            header( 'HTTP/1.1 200 OK' );
            exit;
        }
        if ($this->amazonpspaymentservices->isCcStandardCheckout()) {
            echo '<script>window.top.location.href = "' . $this->url->link($redirectUrl) . '"</script>';
            exit;
        }
        else {
            header('location:' . $this->url->link($redirectUrl));
        }
    }

    public function merchantPageResponse()
    {
    	$this->amazonpspaymentservices->log('Amazon_ps merchantPageResponse Call: ');
        $this->amazonpspaymentservices->handleRedirectionIssue();
		$integrationType = $this->amazonpspaymentservices->getCcIntegrationType();
        $this->_handleResponse('online', $integrationType);
    }

    public function merchantPageCancel()
    {
    	$this->amazonpspaymentservices->log('Amazon_ps merchantPageCancel Call: ');
        $this->amazonpspaymentservices->handleRedirectionIssue();
    	$this->amazonpsorderpayment->merchantPageCancel();
        header('location:' . $this->url->link('checkout/checkout'));
    }

    public function displayPaymentData(&$route, &$data, &$output){
        if (isset($this->session->data['aps_order_id'])) {
            $result = [];
            $this->load->model('checkout/order');
            $order = $this->model_checkout_order->getOrder($this->session->data['aps_order_id']);
            if($order && isset($order['payment_code'])){
                $aps_payment_methods = array(
                    AmazonPSConstant::AMAZON_PS_PAYMENT_METHOD_CC,
                    AmazonPSConstant::AMAZON_PS_PAYMENT_METHOD_KNET,
                    AmazonPSConstant::AMAZON_PS_PAYMENT_METHOD_VALU,
                    AmazonPSConstant::AMAZON_PS_PAYMENT_METHOD_INSTALLMENTS,
                );
                if(in_array($order['payment_code'], $aps_payment_methods)){
                    $this->language->load('extension/payment/amazon_ps');
                    $orderId         = $order['order_id'];
                    $payment_method  = $order['payment_code']; 
                    $amazon_ps_data  = $this->aps_model->getAmazonPSMetaValue($orderId, 'amazon_ps_payment_response', true);
                    if($payment_method == AmazonPSConstant::AMAZON_PS_PAYMENT_METHOD_KNET){
                        $result['title'] =  $this->language->get('knet_details');
                        if(isset($amazon_ps_data['third_party_transaction_number'])){
                            $result['order_data'][] = array(
                                'label' => $this->language->get('third_party_transaction_number'),
                                'value' => $amazon_ps_data['third_party_transaction_number'],
                            );
                        }
                        if(isset($amazon_ps_data['knet_ref_number'])){
                            $result['order_data'][] = array(
                                'label' => $this->language->get('knet_ref_number'),
                                'value' => $amazon_ps_data['knet_ref_number'],
                            );
                        }
                    }
                    $order_data = $this->load->view('extension/payment/amazon_ps_display_payment_data', $result );
                            // Insert the tags before the closing <head> tag
                    $output = str_replace('</head>', $order_data . '</head>', $output);
                }
            }
            unset($this->session->data['aps_order_id']);
        }
    }
}
