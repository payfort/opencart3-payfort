<?php

require_once DIR_APPLICATION . '/controller/payment/payfort_fort_qpay.php';

class ControllerExtensionPaymentPayfortFortQpay extends ControllerPaymentPayfortFortQpay
{

    public function __construct($registry)
    {
        parent::__construct($registry);
    }

}
