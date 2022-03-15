<?php
class ModelExtensionPaymentAmazonPSValu extends Model {
	private $supported_currencies;
	private $amazonpspaymentservices;
	public function __construct($registry)
	{
		$this->registry = $registry;
		$this->amazonpspaymentservices = new AmazonPSPaymentServices($registry);
        $this->supported_currencies = array( 'EGP' );

	}

	public function getMethod($address, $total) {
		$this->load->language('extension/payment/amazon_ps');

		//$this->load->model('extension/payment/amazon_payment_services');
		
		$status = $this->check_availability($total);
        
		$method_data = array();

		if ($status) {
			$method_data = array(
				'code'       => AmazonPSConstant::AMAZON_PS_PAYMENT_METHOD_VALU,
				'title'      => $this->language->get('text_title_valu'). $this->get_icon(),
				'terms'      => '',
				'sort_order' => $this->config->get('payment_amazon_ps_valu_sort_order')
			);
		}

		return $method_data;
	}
	public function get_icon() {
		$icon_html       = '';
		$image_directory = 'catalog/view/theme/default/image/amazon_ps/';
		$valu_logo       = $image_directory . 'valu-logo.png';
		
		$icon_html .= '<img style="max-height: 1.898em; margin: 5px !important;" src="' . $valu_logo . '" alt="valu" class="payment-icons" />';
		return $icon_html;
	}

	
	public function check_availability($total) {
		$is_enabled = 0;
	    $front_currency = $this->amazonpspaymentservices->getFrontCurrency();
		if ( $this->amazonpspaymentservices->isValuActive() && in_array( strtoupper( $front_currency ), $this->supported_currencies, true ) ) {

			$is_enabled = 1;

			$min_limit = $this->amazonpspaymentservices->getValuOrderMinValue();

			$currency  = $this->amazonpspaymentservices->getGatewayCurrencyCode();
        	$currency_value  = $this->currency->getValue($currency);

        	$gateway_currency = $this->amazonpspaymentservices->getGatewayCurrency();
        	if ($gateway_currency == 'front') {
	  	    	$amount      = $this->amazonpspaymentservices->convertGatewayAmount($total, $currency_value, $currency);
			}else{
				$amount  = $this->amazonpspaymentservices->convertDecimalToIntAmount($total*$this->currency->getValue($front_currency), $front_currency);
			}
	        $min_limit  = $this->amazonpspaymentservices->convertDecimalToIntAmount($min_limit, $front_currency);
			if ( $amount < $min_limit ) {
				$is_enabled = 0;
			}
		}
		return $is_enabled;
	}
}