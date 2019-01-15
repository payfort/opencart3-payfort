<?php

require_once DIR_SYSTEM . '/library/payfortFort/init.php';

class ModelExtensionPaymentPayfortFort extends Model
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
        $enabled = $this->pfConfig->isCcActive();

        $status = true;

        if (!$enabled) {
            $status = false;
        }

        $method_data = array();

        if ($status) {
            $method_data = array(
                'code'       => PAYFORT_FORT_PAYMENT_METHOD_CC,
                'title'      => $this->language->get('text_title'),
                'sort_order' => $this->config->get('payfort_fort_sort_order'),
                'terms'      => ''
            );
        }

        return $method_data;
    }

}

?>