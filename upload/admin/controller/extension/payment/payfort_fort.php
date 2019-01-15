<?php

require_once DIR_APPLICATION . '/controller/payment/payfort_fort.php';

class ControllerExtensionPaymentPayfortFort extends ControllerPaymentPayfortFort {
    public function __construct($registry)
    {
        parent::__construct($registry);
    }
}

?>