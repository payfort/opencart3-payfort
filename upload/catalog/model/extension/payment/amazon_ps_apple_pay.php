<?php
class ModelExtensionPaymentAmazonPSApplePay extends Model {
	private $amazonpspaymentservices;

	public function __construct($registry)
	{
		$this->amazonpspaymentservices = new AmazonPSPaymentServices($registry);
		$this->registry = $registry;
	}

	public function getMethod($address, $total) {
		$this->load->language('extension/payment/amazon_ps');		
		$status = $this->check_availability();
        $method_data = array();
		if ($status) {
			$certificate_path              = DIR_UPLOAD . $this->amazonpspaymentservices->getApplePayCertificateFileName();
			$apple_pay_merchant_identifier = openssl_x509_parse( file_get_contents( $certificate_path ) )['subject']['UID'];
			?>
			<script type="text/javascript">
				var apple_merchant_identifier = "<?php echo $apple_pay_merchant_identifier; ?>";
			</script>
			<script type="text/javascript" src="catalog/view/javascript/amazon_ps/amazon_ps_apple_button.js"/>
			<?php
			$method_data = array(
				'code'       => AmazonPSConstant::AMAZON_PS_PAYMENT_METHOD_APPLE_PAY,
				'title'      => $this->language->get('text_title_apple_pay'),
				'terms'      => '',
				'sort_order' => $this->config->get('payment_amazon_ps_apple_pay_sort_order')
			);
		}

		return $method_data;
	}
	public function get_icon() {
		$icon_html       = '';
		$image_directory = 'catalog/view/theme/default/image/amazon_ps/';
		$visa_logo       = $image_directory . 'visa-logo.png';
		
		$icon_html .= '<img src="' . $visa_logo . '" alt="visa" class="payment-icons" />';
		return $icon_html;
	}

	public function get_card_inline_icon() {
		$icon_html       = '';
		$image_directory = 'catalog/view/theme/default/image/amazon_ps/';
		$visa_logo       = $image_directory . 'visa-logo.png';		
		$icon_html .= '<img src="' . $visa_logo . '" alt="visa" class="card-visa card-icon" />';
		return $icon_html;
	}

	public function check_availability() {
		$is_enabled = $this->amazonpspaymentservices->isApplePayActive();
		return $is_enabled;
	}

	public function getCountryByISOCode2($iso_code2) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "country WHERE iso_code_2 = '" . $this->db->escape($iso_code2) . "' AND status = '1'");

		return $query->row;
	}

	public function getZoneByZoneCode($zone_code, $country_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone WHERE code = '" . $this->db->escape($zone_code) . "' AND country_id = '" . (int)$country_id . "' AND status = '1'");

		return $query->row;
	}
}
