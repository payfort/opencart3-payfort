<?php

require_once DIR_SYSTEM . '/library/payfortFort/init.php';

class ModelExtensionPaymentPayfortFortInstallments extends Model {

    public function __construct($registry) {
        parent::__construct($registry);
    }

    public function getMethod($address, $total) {
        $this->language->load('extension/payment/payfort_fort');
        $enabled = $this->config->get('payment_payfort_fort_installments');
        $status = true;
        if (!$enabled) {
            $status = false;
        }
        $method_data = array();
        if ($status) {
            $method_data = array(
                'code' => PAYFORT_FORT_PAYMENT_METHOD_INSTALLMENTS,
                'title' => $this->language->get('text_installments'),
                'sort_order' => $this->config->get('payfort_fort_installments_sort_order'),
                'terms' => ''
            );
        }
        return $method_data;
    }

}

?>