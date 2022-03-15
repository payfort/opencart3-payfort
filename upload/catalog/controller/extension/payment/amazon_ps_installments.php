<?php
class ControllerExtensionPaymentAmazonPSInstallments extends Controller {

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

		$this->language->load('extension/payment/amazon_ps');
        $this->load->model('extension/payment/amazon_ps_installments');

        $data['button_confirm'] = $this->language->get('button_confirm');
        $data['text_general_error']  = $this->language->get('text_general_error');
        $template               = 'amazon_ps';
        $data['payment_method'] = 'amazon_ps_installments';
        $integrationType        = $this->amazonpspaymentservices->getInstallmentsIntegrationType();

        $data['is_enabled_tokenization'] = 0;
        if ($this->amazonpspaymentservices->isInstallmentsStandardCheckout()) {
            $template           = 'amazon_ps_standard_checkout';
        }elseif ($this->amazonpspaymentservices->isInstallmentsHostedCheckout()) {

           $data['is_enabled_tokenization'] = $this->amazonpspaymentservices->isEnabledTokenization();

            $template           = 'amazon_ps_installments_hosted_checkout';
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
            
            $data['card_inline_icon'] = $this->model_extension_payment_amazon_ps_installments->get_card_inline_icon();
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
        
        
        $tokens_data = array();
        $data['has_recurring_products'] = 0;
        $display_add_new_card = 'style="display:none"';
        if($data['is_enabled_tokenization']){

            $tokens = $this->aps_token->getTokens();
            // installment only support visa & mastercard cards
            $tokens = array_filter(
                $tokens,
                function( $token_row ) {
                    if ( in_array( $token_row['extras']['card_type'], array( 'visa', 'mastercard'), true ) ) {
                        return true;
                    } else {
                        return false;
                    }
                }
            );

            $tokens_data = array(
                'tokens' => $tokens,
                'tokenization_card_icons' => $this->amazonpspaymentservices->getTokenizationCardIcons(),
            );
        }
        if($integrationType == AmazonPSConstant::AMAZON_PS_INTEGRATION_TYPE_HOSTED_CHECKOUT && (!(empty($tokens)) && $data['is_enabled_tokenization'] == 1)){
                $display_add_new_card = '';
        }
        $tokens_data['display_add_new_card'] = $display_add_new_card;
        $data['tokenization_view'] = $this->load->view('extension/payment/amazon_ps_tokenization', $tokens_data );

        $orderId = $this->amazonpsorderpayment->getSessionOrderId();
        $this->aps_model->updatePaymentMethod($orderId, $this->language->get('text_title_installments'));

		return $this->load->view('extension/payment/'.$template, $data);
	}

	public function send()
    {
        $extras = array();
        $this->language->load('extension/payment/amazon_ps');
        $is_valid_hosted_request = true;
        $orderId = $this->amazonpsorderpayment->getSessionOrderId();
    	if ($this->amazonpspaymentservices->isInstallmentsHostedCheckout()) {
            $this->language->load('extension/payment/amazon_ps');

            $installment_plan_code   = filter_input( INPUT_POST, 'aps_installment_plan_code' );
            $installment_issuer_code = filter_input( INPUT_POST, 'aps_installment_issuer_code' );
            $installment_confirmation_en = filter_input( INPUT_POST, 'aps_installment_confirmation_en' );
            $installment_confirmation_ar = filter_input( INPUT_POST, 'aps_installment_confirmation_ar' );
            $installment_interest    = filter_input( INPUT_POST, 'aps_installment_interest' );
            $installment_amount      = filter_input( INPUT_POST, 'aps_installment_amount' );

            if ( isset( $this->request->post['aps_payment_token_cc'] ) && ! empty( $this->request->post['aps_payment_token_cc'] ) ) {
                $extras['aps_payment_token'] = trim( $this->request->post['aps_payment_token_cc'], ' ' );
            }
            if ( isset( $this->request->post['aps_payment_card_bin_cc'] ) && ! empty( $this->request->post['aps_payment_card_bin_cc'] ) ) {
                $extras['aps_card_bin'] = trim( $this->request->post['aps_payment_card_bin_cc'], ' ' );
            }
            if ( isset( $this->request->post['aps_card_security_code'] ) && ! empty( $this->request->post['aps_card_security_code'] ) ) {
                $extras['aps_payment_cvv'] = trim( $this->request->post['aps_card_security_code'], ' ' );
            }

            if ( empty( $installment_plan_code ) || empty( $installment_issuer_code ) ) {
                $json['error'] = true;
                $json['error_message'] = $this->language->get('text_plan_select');
                 $is_valid_hosted_request = false;
            }
            else{
                
                if ( ! empty( $installment_plan_code ) ) {
                    $this->aps_model->updateAmazonPSMetaData($orderId, 'installment_plan_code', $installment_plan_code);
                }
                if ( ! empty( $installment_issuer_code ) ) {
                    $this->aps_model->updateAmazonPSMetaData($orderId, 'installment_issuer_code', $installment_issuer_code);
                }
                if ( ! empty( $installment_confirmation_en ) ) {
                    $this->aps_model->updateAmazonPSMetaData($orderId, 'installment_confirmation_en', $installment_confirmation_en);
                }
                if ( ! empty( $installment_confirmation_ar ) ) {
                    $this->aps_model->updateAmazonPSMetaData($orderId, 'installment_confirmation_ar', $installment_confirmation_ar);
                }
                if ( ! empty( $installment_interest ) ) {
                    $this->aps_model->updateAmazonPSMetaData($orderId, 'installment_interest', $installment_interest );
                }
                if ( ! empty( $installment_amount ) ) {
                    $this->aps_model->updateAmazonPSMetaData($orderId, 'installment_amount', $installment_amount );
                }
            }
        }
        if($this->amazonpspaymentservices->getInstallmentsIntegrationType() == AmazonPSConstant::AMAZON_PS_INTEGRATION_TYPE_REDIRECTION){
            $form = $this->amazonpsorderpayment->getPaymentRequestForm(AmazonPSConstant::AMAZON_PS_PAYMENT_METHOD_INSTALLMENTS, $this->amazonpspaymentservices->getInstallmentsIntegrationType());
            
            $json = array('form' => $form);
        }else if($this->amazonpspaymentservices->isInstallmentsStandardCheckout() || $is_valid_hosted_request){
            $json = $this->amazonpsorderpayment->getPaymentRequestParams(AmazonPSConstant::AMAZON_PS_PAYMENT_METHOD_INSTALLMENTS, $this->amazonpspaymentservices->getInstallmentsIntegrationType(), $extras);
        }
        if($is_valid_hosted_request){
            $this->aps_model->updateAmazonPSMetaData($orderId, 'integration_type', $this->amazonpspaymentservices->getInstallmentsIntegrationType());
        }
        $this->aps_model->updateAmazonPSMetaData($orderId, 'aps_redirected_order', 1);
        $this->amazonpspaymentservices->handleRedirectionIssue();
        $this->response->setOutput(json_encode($json));
    }

    public function getInstallmentPlans()
    {
        $card_bin = filter_input( INPUT_POST, 'card_bin' );
        $card_bin = str_replace( array( ' ', '*' ), array( '', '' ), $card_bin );
        $embedded_hosted_checkout = intval( filter_input( INPUT_POST, 'embedded_hosted_checkout' ) );
        $response = $this->amazonpsorderpayment->get_installment_handler($card_bin, $embedded_hosted_checkout);

        $this->response->setOutput(json_encode($response));
    }

    public function responseOnline()
    {
        $this->amazonpspaymentservices->handleRedirectionIssue();
        $this->_handleResponse('online');
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
        if ($this->amazonpspaymentservices->isInstallmentsStandardCheckout()) {
            echo '<script>window.top.location.href = "' . $this->url->link($redirectUrl) . '"</script>';
            exit;
        }
        else {
            header('location:' . $this->url->link($redirectUrl));
        }

    }

    public function merchantPageResponse()
    {
        $this->amazonpspaymentservices->log('amazon_ps_installments merchantPageResponse Call: ');

        $this->amazonpspaymentservices->handleRedirectionIssue();
        $integrationType = $this->amazonpspaymentservices->getInstallmentsIntegrationType();
        $this->_handleResponse('online', $integrationType);
    }
   
    public function merchantPageCancel()
    {
        $this->amazonpspaymentservices->log('amazon_ps_installments merchantPageCancel Call: ');
        $this->amazonpspaymentservices->handleRedirectionIssue();
        $this->amazonpsorderpayment->merchantPageCancel();
        header('location:' . $this->url->link('checkout/checkout'));
    }
}
