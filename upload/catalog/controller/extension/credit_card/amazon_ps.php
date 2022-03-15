<?php

class ControllerExtensionCreditCardAmazonPS extends Controller {

    protected $registry;
    private $amazonpspaymentservices;
    private $amazonpsorderpayment;

    public function __construct($registry)
    {
        $this->registry = $registry;
        $this->amazonpspaymentservices = new AmazonPSPaymentServices($registry);
        $this->amazonpsorderpayment    = new AmazonPSOrderPayment($registry);
    }

    public function index() {
        if (!$this->customer->isLogged()) {
            $this->session->data['redirect'] = $this->url->link('account/account', '', true);

            $this->response->redirect($this->url->link('account/login', '', true));
        }

        $this->load->language('extension/credit_card/amazon_ps');
        $this->load->model('extension/payment/amazon_ps_tokens');
        $this->document->setTitle($this->language->get('heading_title'));

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home')
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_account'),
            'href' => $this->url->link('account/account', '', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/credit_card/amazon_ps', '', true)
        );

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];

            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        } 

        if (isset($this->session->data['error'])) {
            $data['error'] = $this->session->data['error'];

            unset($this->session->data['error']);
        } else {
            $data['error'] = '';
        } 

        $data['back'] = $this->url->link('account/account', '', true);
        $data['cards'] = array();

        foreach ($this->model_extension_payment_amazon_ps_tokens->getTokens() as $card) {

            $brand = ($card['extras']['card_type'] == 'mada') ? strtolower($card['extras']['card_type']) : strtoupper($card['extras']['card_type']);

            $masking_card = substr($card['extras']['masking_card'],-4);

            $token = $card['token'];
            $expire = $card['extras']['expiry_month']."/".$card['extras']['expiry_year'];
            $data['cards'][] = array(
                'text' => sprintf($this->language->get('text_card_ends_in'), $brand, $masking_card),
                'expire' => $expire,
                'delete' => $this->url->link('extension/credit_card/amazon_ps/forget', 'token=' . $token, true)
            );
        }

        $data['hide_delete_token'] = $this->amazonpspaymentservices->isHideDeleteToken();

        $data['column_left']  = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top']  = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

        $this->response->setOutput($this->load->view('extension/credit_card/amazon_ps', $data));
    }

    public function forget() {
        if (!$this->customer->isLogged()) {
            $this->session->data['redirect'] = $this->url->link('account/account', '', true);
            $this->response->redirect($this->url->link('account/login', '', true));
        }

        $this->load->language('extension/credit_card/amazon_ps');
        $this->load->model('extension/payment/amazon_ps_tokens');

        $token = !empty($this->request->get['token']) ?
            $this->request->get['token'] : 0;

        if ($this->model_extension_payment_amazon_ps_tokens->verifyTokenCustomer($token, $this->customer->getId())) {         
            try {
                $response = $this->amazonpsorderpayment->delete_aps_token($token);
                if($response['status'] == 'success'){
                    $this->model_extension_payment_amazon_ps_tokens->deleteToken($token, $this->customer->getId());
                }else{
                    throw new Exception($response['message']);
                }
                $this->session->data['success'] = $this->language->get('text_success_card_delete');
            } catch (Exception $e) {
                $this->session->data['error'] = $e->getMessage();
            }
        }
        $this->response->redirect($this->url->link('extension/credit_card/amazon_ps', '', true));
    }
}