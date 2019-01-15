<?php

require_once DIR_APPLICATION . '/controller/payment/payfort_fort_installments.php';

class ControllerExtensionPaymentPayfortFortInstallments extends ControllerPaymentPayfortFortInstallments {
    public function __construct($registry)
    {
        parent::__construct($registry);
    }
}

?>