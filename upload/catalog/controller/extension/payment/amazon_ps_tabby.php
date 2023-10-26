<?php
class ControllerExtensionPaymentAmazonPSTabby extends Controller {

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

        $data['payment_method'] = 'amazon_ps_tabby';
        return $this->load->view('extension/payment/amazon_ps', $data);
    }

    public function send()
    {
        $form = $this->amazonpsorderpayment->getPaymentRequestForm(AmazonPSConstant::AMAZON_PS_PAYMENT_METHOD_TABBY, AmazonPSConstant::AMAZON_PS_INTEGRATION_TYPE_REDIRECTION);

        $json = array('form' => $form);
        $orderId = $this->amazonpsorderpayment->getSessionOrderId();
        $this->aps_model->updateAmazonPSMetaData($orderId, 'aps_redirected_order', 1);
        $this->amazonpspaymentservices->handleRedirectionIssue();
        $this->response->setOutput(json_encode($json));
    }

    public function responseOnline()
    {
        $this->amazonpspaymentservices->handleRedirectionIssue();
        $this->session->data['aps_order_id'] = $this->amazonpsorderpayment->getSessionOrderId();
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
        header('location:' . $this->url->link($redirectUrl));

    }

}