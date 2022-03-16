<?php
class ModelExtensionPaymentAmazonPSVisaCheckout extends Model {
    private $amazonpspaymentservices;
	
    public function __construct($registry)
	{
		$this->registry = $registry;
        $this->amazonpspaymentservices = new AmazonPSPaymentServices($registry);
	}

	public function getMethod($address, $total) {
		$this->load->language('extension/payment/amazon_ps');
		
		
		$status = $this->amazonpspaymentservices->isVisaCheckoutActive();
		

		$method_data = array();

		if ($status) {
			$method_data = array(
				'code'       => AmazonPSConstant::AMAZON_PS_PAYMENT_METHOD_VISA_CHECKOUT,
				'title'      => $this->language->get('text_title_visa_checkout'),
				'terms'      => '',
				'sort_order' => $this->config->get('payment_amazon_ps_visa_checkout_sort_order')
			);
		}

		return $method_data;
	}
}