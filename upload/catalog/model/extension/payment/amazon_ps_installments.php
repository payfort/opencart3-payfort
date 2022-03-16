<?php
class ModelExtensionPaymentAmazonPSInstallments extends Model {
	private $supported_currencies;
	private $amazonpspaymentservices;
	public function __construct($registry)
	{
		$this->registry = $registry;
        $this->supported_currencies = array( 'AED', 'SAR', 'EGP' );
        $this->amazonpspaymentservices = new AmazonPSPaymentServices($registry);
	}

	public function getMethod($address, $total) {
		$this->load->language('extension/payment/amazon_ps');
		
		$status = $this->check_availability($total);
        
		$method_data = array();

		if ($status) {
			$method_data = array(
				'code'       => AmazonPSConstant::AMAZON_PS_PAYMENT_METHOD_INSTALLMENTS,
				'title'      => $this->language->get('text_title_installments'). $this->get_icon(),
				'terms'      => '',
				'sort_order' => $this->config->get('payment_amazon_ps_installments_sort_order')
			);
		}

		return $method_data;
	}
	public function get_icon() {
		$icon_html       = '';
		$image_directory = 'catalog/view/theme/default/image/amazon_ps/';
		$visa_logo       = $image_directory . 'visa-logo.png';
		$mastercard_logo = $image_directory . 'mastercard-logo.png';
		
		$icon_html .= '<img style="margin: 5px !important;"  src="' . $visa_logo . '" alt="visa" class="payment-icons" />';
		$icon_html .= '<img style="margin: 5px !important;"  src="' . $mastercard_logo . '" alt="mastercard" class="payment-icons"/>';
		return $icon_html;
	}

	public function get_card_inline_icon() {
		$icon_html       = '';
		$image_directory = 'catalog/view/theme/default/image/amazon_ps/';
		$visa_logo       = $image_directory . 'visa-logo.png';
		$mastercard_logo = $image_directory . 'mastercard-logo.png';
		
		$icon_html .= '<img src="' . $visa_logo . '" alt="visa" class="card-visa card-icon" />';
		$icon_html .= '<img src="' . $mastercard_logo . '" alt="mastercard" class="card-mastercard card-icon"/>';
		return $icon_html;
	}

	public function check_availability($total) {
		$is_enabled = $this->amazonpspaymentservices->isInstallmentsActive();
		if($this->amazonpspaymentservices->getInstallmentsIntegrationType() == AmazonPSConstant::AMAZON_PS_INTEGRATION_TYPE_EMBEDDED_HOSTED_CHECKOUT){
			$is_enabled = false;
		}
		if($is_enabled){
			$is_enabled = $this->checkInstallmentTotalMinLimit($total);
		}
		return $is_enabled;
	}

	public function checkInstallmentTotalMinLimit($total){
		$is_min_total_limit = 1;
		$front_currency = $this->amazonpspaymentservices->getFrontCurrency();
		if (in_array( strtoupper( $front_currency ), $this->supported_currencies, true ) ) {

			$min_limit = 0;
			if ( 'SAR' === $front_currency ) {
				$cart_min_limit = $this->amazonpspaymentservices->getInstallmentsSAROrderMinValue();
			} elseif ( 'AED' === $front_currency ) {
				$min_limit = $this->amazonpspaymentservices->getInstallmentsAEDOrderMinValue();
			} elseif ( 'EGP' === $front_currency ) {
				$min_limit = $this->amazonpspaymentservices->getInstallmentsEGPOrderMinValue();
			}
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
				$is_min_total_limit = 0;
			}
		}
		return $is_min_total_limit;
	}
}
