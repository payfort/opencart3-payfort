<?php
class ModelExtensionPaymentAmazonPSTabby extends Model {
    private $amazonpspaymentservices;

    public function __construct($registry)
    {
        $this->registry = $registry;
        $this->amazonpspaymentservices = new AmazonPSPaymentServices($registry);
    }

    public function get_icon() {
        $icon_html       = '';
        $image_directory = 'catalog/view/theme/default/image/amazon_ps/';
        $tabby_logo       = $image_directory . 'tabby-logo.png';

        $icon_html .= '<img style="max-height: 1.898em; margin: 5px !important;" src="' . $tabby_logo . '" alt="valu" class="payment-icons" />';
        return $icon_html;
    }

    public function getMethod($address, $total) {
        $this->load->language('extension/payment/amazon_ps');

        $enabled = $this->amazonpspaymentservices->isTabbyActive();


        $status = true;

        if (!$enabled) {
            $status = false;
        }

        $frontCurrency = $this->amazonpspaymentservices->getFrontCurrency();
        $baseCurrency  = $this->amazonpspaymentservices->getBaseCurrency();
        $currency      = $this->amazonpspaymentservices->getGatewayCurrencyCode($baseCurrency, $frontCurrency);

        $supported_currencies = ['SAR', 'AED'];
        if (! in_array($currency, $supported_currencies)) {
            return false;
        }

        $method_data = array();

        if ($status) {
            $method_data = array(
                'code'       => AmazonPSConstant::AMAZON_PS_PAYMENT_METHOD_TABBY,
                'title'      => $this->language->get('text_title_tabby'). $this->get_icon(),
                'terms'      => '',
                'sort_order' => $this->config->get('payment_amazon_ps_tabby_sort_order')
            );
        }

        return $method_data;
    }
}