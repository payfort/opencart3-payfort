<?php

require_once DIR_SYSTEM . '/library/payfortFort/init.php';

class ModelExtensionPaymentPayfortFortSadad extends Model
{

    public $pfConfig;
    public $pfHelper;

    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->pfConfig = Payfort_Fort_Config::getInstance($registry);
        $this->pfHelper = Payfort_Fort_Helper::getInstance($registry);
    }

    public function getMethod($address, $total)
    {
        $this->language->load('extension/payment/payfort_fort');
        $enabled = $this->config->get('payment_payfort_fort_sadad');

        $status = true;

        if (!$enabled) {
            $status = false;
        }

        $frontCurrency = $this->pfHelper->getFrontCurrency();
        $baseCurrency  = $this->pfHelper->getBaseCurrency();
        $currency      = $this->pfHelper->getFortCurrency($baseCurrency, $frontCurrency);
        if ($currency != 'SAR') {
            return false;
        }

        $method_data = array();

        if ($status) {
            $method_data = array(
                'code'       => PAYFORT_FORT_PAYMENT_METHOD_SADAD,
                'title'      => $this->language->get('text_sadad'),
                'sort_order' => $this->config->get('payfort_fort_sadad_sort_order'),
                'terms'      => ''
            );
        }

        return $method_data;
    }

}

?>