<?php
class ModelExtensionPaymentAmazonPSKnet extends Model {
    private $amazonpspaymentservices;

    public function __construct($registry)
	{
		$this->registry = $registry;
        $this->amazonpspaymentservices = new AmazonPSPaymentServices($registry);
	}

	public function getMethod($address, $total) {
		$this->load->language('extension/payment/amazon_ps');
		
		$enabled = $this->amazonpspaymentservices->isKnetActive();
		

		$status = true;

        if (!$enabled) {
            $status = false;
        }

        $frontCurrency = $this->amazonpspaymentservices->getFrontCurrency();
        $baseCurrency  = $this->amazonpspaymentservices->getBaseCurrency();
        $currency      = $this->amazonpspaymentservices->getGatewayCurrencyCode($baseCurrency, $frontCurrency);

        $supported_currencies = ['KWD'];        
        if (! in_array($currency, $supported_currencies)) {
        	return false;
        }

		$method_data = array();

		if ($status) {
			$method_data = array(
				'code'       => AmazonPSConstant::AMAZON_PS_PAYMENT_METHOD_KNET,
				'title'      => $this->language->get('text_title_knet'),
				'terms'      => '',
				'sort_order' => $this->config->get('payment_amazon_ps_knet_sort_order')
			);
		}

		return $method_data;
	}
}