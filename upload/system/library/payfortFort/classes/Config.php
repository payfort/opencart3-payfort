<?php

define('PAYFORT_FORT_INTEGRATION_TYPE_REDIRECTION', 'redirection');
define('PAYFORT_FORT_INTEGRATION_TYPE_MERCAHNT_PAGE', 'merchantPage');
define('PAYFORT_FORT_INTEGRATION_TYPE_MERCAHNT_PAGE2', 'merchantPage2');
define('PAYFORT_FORT_PAYMENT_METHOD_CC', 'payfort_fort');
define('PAYFORT_FORT_PAYMENT_METHOD_NAPS', 'payfort_fort_qpay');
define('PAYFORT_FORT_PAYMENT_METHOD_SADAD', 'payfort_fort_sadad');
define('PAYFORT_FORT_PAYMENT_METHOD_INSTALLMENTS', 'payfort_fort_installments');
define('PAYFORT_FORT_FLASH_MSG_ERROR', 'E');
define('PAYFORT_FORT_FLASH_MSG_SUCCESS', 'S');
define('PAYFORT_FORT_FLASH_MSG_INFO', 'I');
define('PAYFORT_FORT_FLASH_MSG_WARNING', 'W');

class Payfort_Fort_Config
{

    private static $instance;
    private $registry;
    private $language;
    private $merchantIdentifier;
    private $accessCode;
    private $command;
    private $hashAlgorithm;
    private $requestShaPhrase;
    private $responseShaPhrase;
    private $sandboxMode;
    private $gatewayCurrency;
    private $debugMode;
    private $hostUrl;
    private $successOrderStatusId;
    private $orderPlacement;
    private $status;
    private $ccStatus;
    private $ccIntegrationType;
    private $sadadStatus;
    private $napsStatus;
    private $gatewayProdHost;
    private $gatewaySandboxHost;
    private $logFileDir;
    // installments
    private $installmentsIntegrationType;
    private $installmentsStatus;

    public function __construct($registry)
    {
        $this->registry = $registry;

        $this->gatewayProdHost    = 'https://checkout.payfort.com/';
        $this->gatewaySandboxHost = 'https://sbcheckout.payfort.com/';
        $this->logFileDir         = 'payfort_fort.log';

        $this->language                          = $this->_getShoppingCartConfig('entry_language');
        $this->merchantIdentifier                = $this->_getShoppingCartConfig('entry_merchant_identifier');
        $this->accessCode                        = $this->_getShoppingCartConfig('entry_access_code');
        $this->command                           = $this->_getShoppingCartConfig('entry_command');
        $this->hashAlgorithm                     = $this->_getShoppingCartConfig('entry_hash_algorithm');
        $this->requestShaPhrase                  = $this->_getShoppingCartConfig('entry_request_sha_phrase');
        $this->responseShaPhrase                 = $this->_getShoppingCartConfig('entry_response_sha_phrase');
        $this->sandboxMode                       = $this->_getShoppingCartConfig('entry_sandbox_mode');
        $this->gatewayCurrency                   = $this->_getShoppingCartConfig('entry_gateway_currency');
        $this->debugMode                         = $this->_getShoppingCartConfig('debug');
        $this->successOrderStatusId              = $this->_getShoppingCartConfig('order_status_id');
        $this->orderPlacement                    = $this->_getShoppingCartConfig('order_placement');
        $this->status                            = $this->_getShoppingCartConfig('status');
        $this->ccStatus                          = $this->_getShoppingCartConfig('credit_card');
        $this->ccIntegrationType                 = $this->_getShoppingCartConfig('cc_integration_type');
        $this->sadadStatus                       = $this->_getShoppingCartConfig('sadad');
        $this->napsStatus                        = $this->_getShoppingCartConfig('naps');
        // installments
        $this->installmentsIntegrationType       = $this->_getShoppingCartConfig('installments_integration_type');
        $this->installmentsStatus                = $this->_getShoppingCartConfig('installments');
        
    }

    /**
     * @return Payfort_Fort_Config
     */
    public static function getInstance($registry)
    {
        if (self::$instance === null) {
            self::$instance = new Payfort_Fort_Config($registry);
        }
        return self::$instance;
    }

    private function _getShoppingCartConfig($key)
    {
        return $this->registry->get('config')->get('payment_payfort_fort_' . $key);
    }

    public function getLanguage()
    {
        $langCode = $this->language;
        if ($this->language == 'store') {
            $langCode = Payfort_Fort_Language::getCurrentLanguageCode($this->registry);
        }
        if ($langCode != 'ar') {
            $langCode = 'en';
        }
        return $langCode;
    }

    public function getMerchantIdentifier()
    {
        return $this->merchantIdentifier;
    }

    public function getAccessCode()
    {
        return $this->accessCode;
    }

    public function getCommand()
    {
        return $this->command;
    }

    public function getHashAlgorithm()
    {
        return $this->hashAlgorithm;
    }

    public function getRequestShaPhrase()
    {
        return $this->requestShaPhrase;
    }

    public function getResponseShaPhrase()
    {
        return $this->responseShaPhrase;
    }

    public function getSandboxMode()
    {
        return $this->sandboxMode;
    }

    public function isSandboxMode()
    {
        if ($this->sandboxMode) {
            return true;
        }
        return false;
    }

    public function getGatewayCurrency()
    {
        return $this->gatewayCurrency;
    }

    public function getDebugMode()
    {
        return $this->debugMode;
    }

    public function isDebugMode()
    {
        if ($this->debugMode) {
            return true;
        }
        return false;
    }

    public function getHostUrl()
    {
        return $this->hostUrl;
    }

    public function getSuccessOrderStatusId()
    {
        return $this->successOrderStatusId;
    }

    public function getStatus()
    {
        return $this->Status;
    }

    public function isActive()
    {
        if ($this->active) {
            return true;
        }
        return false;
    }

    public function getOrderPlacement()
    {
        return $this->orderPlacement;
    }

    public function orderPlacementIsAll()
    {
        if (empty($this->orderPlacement) || $this->orderPlacement == 'all') {
            return true;
        }
        return false;
    }

    public function orderPlacementIsOnSuccess()
    {
        if ($this->orderPlacement == 'success') {
            return true;
        }
        return false;
    }

    public function getCcStatus()
    {
        return $this->ccStatus;
    }

    public function isCcActive()
    {
        if ($this->ccStatus) {
            return true;
        }
        return false;
    }

    public function getCcIntegrationType()
    {
        return $this->ccIntegrationType;
    }

    public function isCcMerchantPage()
    {
        if ($this->ccIntegrationType == PAYFORT_FORT_INTEGRATION_TYPE_MERCAHNT_PAGE) {
            return true;
        }
        return false;
    }

    public function isCcMerchantPage2()
    {
        if ($this->ccIntegrationType == PAYFORT_FORT_INTEGRATION_TYPE_MERCAHNT_PAGE2) {
            return true;
        }
        return false;
    }

    public function getSadadStatus()
    {
        return $this->sadadStatus;
    }

    public function isSadadActive()
    {
        if ($this->sadadStatus) {
            return true;
        }
        return false;
    }

    public function getNapsStatus()
    {
        return $this->napsStatus;
    }

    public function isNapsActive()
    {
        if ($this->napsStatus) {
            return true;
        }
        return false;
    }

    public function getGatewayProdHost()
    {
        return $this->gatewayProdHost;
    }

    public function getGatewaySandboxHost()
    {
        return $this->gatewaySandboxHost;
    }

    public function getLogFileDir()
    {
        return $this->logFileDir;
    }
    
    public function getInstallmentsIntegrationType(){
        return $this->installmentsIntegrationType;
    }
    
    public function getInstallmentsStatus(){
        return $this->installmentsStatus;
    }

    public function isInstallmentsMerchantPage()
    {
        if ($this->installmentsIntegrationType == PAYFORT_FORT_INTEGRATION_TYPE_MERCAHNT_PAGE) {
            return true;
        }
        return false;
    }

}

?>
