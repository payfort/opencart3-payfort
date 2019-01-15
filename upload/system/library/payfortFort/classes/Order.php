<?php
class Payfort_Fort_Order
{

    private $registry;
    private $order = array();
    private $pfConfig;

    public function __construct($registry)
    {
        $this->registry = $registry;
        $this->pfConfig = Payfort_Fort_Config::getInstance($registry);
    }

    public function loadOrder($orderId)
    {
        $this->order = $this->getOrderById($orderId);
    }

    public function getSessionOrderId()
    {
        return $this->registry->get('session')->data['order_id'];
    }
    
    public function getOrderId()
    {
        return isset($this->order['order_id']) ? $this->order['order_id'] : 0;
    }

    public function getOrderById($orderId)
    {
        $this->registry->get('load')->model('checkout/order');
        return $this->registry->get('model_checkout_order')->getOrder($orderId);
    }

    public function getLoadedOrder()
    {
        return $this->order;
    }

    public function getEmail()
    {
        return isset($this->order['email']) ? $this->order['email'] : '';
    }

    public function getCustomerName()
    {
        $fullName  = '';
        $firstName = isset($this->order['payment_firstname']) ? $this->order['payment_firstname'] : '';
        $lastName  = isset($this->order['payment_lastname']) ? $this->order['payment_lastname'] : '';

        $fullName = trim($firstName . ' ' . $lastName);
        return $fullName;
    }

    public function getCurrencyCode()
    {
        return isset($this->order['currency_code']) ? $this->order['currency_code'] : '';
    }

    public function getCurrencyValue()
    {
        return isset($this->order['currency_value']) ? $this->order['currency_value'] : 0;
    }

    public function getTotal()
    {
        return isset($this->order['total']) ? $this->order['total'] : 0;
    }

    public function getPaymentMethod() 
    {
        return isset($this->order['payment_code']) ? $this->order['payment_code'] : '';
    }
    
    public function getStatusId()
    {
        return isset($this->order['order_status_id']) ? $this->order['order_status_id'] : 0;
    }
    
    public function updateOrderStatus($orderId, $statusId, $comment)
    {
        $this->registry->get('load')->model('checkout/order');
        $this->registry->get('model_checkout_order')->addOrderHistory($orderId, $statusId, $comment, false);
    }
    
    public function declineOrder() {
        $status = 10;
        if($this->getStatusId() == $status) {
            return true;
        }
        if($this->getOrderId() && $this->pfConfig->orderPlacementIsAll()) {
            $this->updateOrderStatus($this->getOrderId(), $status, 'Payment Failed');
        }
        return true;
    }
    
    public function cancelOrder() {
        $status = 7;
        if($this->getStatusId() == $status) {
            return true;
        }
        if($this->getOrderId() && $this->pfConfig->orderPlacementIsAll()) {
            $this->updateOrderStatus($this->getOrderId(), $status, 'Payment Canceled');
        }
        return true;
    }

    public function successOrder($response_params, $response_mode) {
        $status = $this->pfConfig->getSuccessOrderStatusId();
        if($this->getStatusId() == $status) {
            return true;
        }
        if($this->getOrderId()) {
            $this->registry->get('load')->model('checkout/order');
            $this->registry->get('model_checkout_order')->addOrderHistory($this->getOrderId(), $status, 'Paid: ' . $this->getOrderId(), false);
        }
        return true;
    }

}

?>