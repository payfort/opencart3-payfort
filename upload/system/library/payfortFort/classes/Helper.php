<?php

class Payfort_Fort_Helper
{

    private static $instance;
    private $registry;
    private $pfConfig;

    public function __construct($registry)
    {
        $this->pfConfig = Payfort_Fort_Config::getInstance($registry);
        $this->registry = $registry;
    }

    /**
     * @return Payfort_Fort_Config
     */
    public static function getInstance($registry)
    {
        if (self::$instance === null) {
            self::$instance = new Payfort_Fort_Helper($registry);
        }
        return self::$instance;
    }
    
    public function getBaseCurrency()
    {
        $query = $this->registry->get('db')->query("SELECT DISTINCT * FROM " . DB_PREFIX . "currency WHERE value = '1.00000000'");

        return isset($query->row["code"]) ? $query->row["code"] : '';
    }

    public function getFrontCurrency()
    {
        return $this->registry->get('session')->data['currency'];
    }
    
    public function getFortCurrency($baseCurrencyCode, $currentCurrencyCode)
    {
        $gateway_currency = $this->pfConfig->getGatewayCurrency();
        $currencyCode     = $baseCurrencyCode;
        if ($gateway_currency == 'front') {
            $currencyCode = $currentCurrencyCode;
        }
        return $currencyCode;
    }

    public function getReturnUrl($path)
    {
        return $this->getUrl('payment/payfort_fort/' . $path);
    }

    public function getUrl($path)
    {
        $url = $this->registry->get('url')->link($path, '', 'SSL');
        return $url;
    }

    /**
     * Convert Amount with dicemal points
     * @param decimal $amount
     * @param decimal $currency_value
     * @param string  $currency_code
     * @return decimal
     */
    public function convertFortAmount($amount, $currency_value, $currency_code)
    {
        $gateway_currency = $this->pfConfig->getGatewayCurrency();
        $new_amount       = 0;
        //$decimal_points = $this->currency->getDecimalPlace();
        $decimal_points   = $this->getCurrencyDecimalPoints($currency_code);
        if ($gateway_currency == 'front') {
            $new_amount = round($amount * $currency_value, $decimal_points);
        }
        else {
            $new_amount = round($amount, $decimal_points);
        }
        $new_amount = $new_amount * (pow(10, $decimal_points));
        return "$new_amount";
    }

    /**
     * 
     * @param string $currency
     * @param integer 
     */
    public function getCurrencyDecimalPoints($currency)
    {
        $decimalPoint  = 2;
        $arrCurrencies = array(
            'JOD' => 3,
            'KWD' => 3,
            'OMR' => 3,
            'TND' => 3,
            'BHD' => 3,
            'LYD' => 3,
            'IQD' => 3,
        );
        if (isset($arrCurrencies[$currency])) {
            $decimalPoint = $arrCurrencies[$currency];
        }
        return $decimalPoint;
    }

    /**
     * calculate fort signature
     * @param array $arrData
     * @param sting $signType request or response
     * @return string fort signature
     */
    public function calculateSignature($arrData, $signType = 'request')
    {
        $shaString = '';

        ksort($arrData);
        foreach ($arrData as $k => $v) {
            $shaString .= "$k=$v";
        }

        if ($signType == 'request') {
            $shaString = $this->pfConfig->getRequestShaPhrase() . $shaString . $this->pfConfig->getRequestShaPhrase();
        }
        else {
            $shaString = $this->pfConfig->getResponseShaPhrase() . $shaString . $this->pfConfig->getResponseShaPhrase();
        }
        $signature = hash($this->pfConfig->getHashAlgorithm(), $shaString);

        return $signature;
    }

    /**
     * Log the error on the disk
     */
    public function log($messages, $forceDebug = false)
    {
        $debugMode = $this->pfConfig->isDebugMode();
        if (!$debugMode && !$forceDebug) {
            return;
        }
        $log = new Log($this->pfConfig->getLogFileDir());
        $log->write($messages);
    }

    public function getCustomerIp()
    {
        return $this->registry->get('request')->server['REMOTE_ADDR'];
    }

    public function getGatewayHost()
    {
        if ($this->pfConfig->isSandboxMode()) {
            return $this->getGatewaySandboxHost();
        }
        return $this->getGatewayProdHost();
    }

    public function getGatewayUrl($type = 'redirection')
    {
        $testMode = $this->pfConfig->isSandboxMode();
        if ($type == 'notificationApi') {
            $gatewayUrl = $testMode ?  'https://sbpaymentservices.payfort.com/FortAPI/paymentApi' :  'https://paymentservices.payfort.com/FortAPI/paymentApi';
        }
        else {
            $gatewayUrl = $testMode ? $this->pfConfig->getGatewaySandboxHost() . 'FortAPI/paymentPage' : $this->pfConfig->getGatewayProdHost() . 'FortAPI/paymentPage';
        }

        return $gatewayUrl;
    }

    public function setFlashMsg($message, $status = PAYFORT_FORT_FLASH_MSG_ERROR, $title = '')
    {
        $this->registry->get('session')->data['error'] = $message;
    }
    
    public static function loadJsMessages($messages, $isReturn = true, $category = 'payfort_fort') {
        $result = '';
        foreach($messages as $label => $translation) {
            $result .= "arr_messages['{$category}.{$label}']='" . $translation ."';\n";
        }
        if($isReturn) {
            return $result;
        }
        else{
            echo $result; 
        }
    }

}

?>
