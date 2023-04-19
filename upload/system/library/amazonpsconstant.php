<?php

class AmazonPSConstant {
    private $session;
    private $url;
    private $config;
    private $log;
    private $customer;
    private $currency;
    private $registry;

    //version
    const AMAZON_PS_VERSION                         = '2.2.2';
    //Payment methods
    const AMAZON_PS_PAYMENT_METHOD_CC               = 'amazon_ps';
    const AMAZON_PS_PAYMENT_METHOD_NAPS             = 'amazon_ps_naps';
    const AMAZON_PS_PAYMENT_METHOD_KNET             = 'amazon_ps_knet';
    const AMAZON_PS_PAYMENT_METHOD_VALU             = 'amazon_ps_valu';
    const AMAZON_PS_PAYMENT_METHOD_VISA_CHECKOUT    = 'amazon_ps_visa_checkout';
    const AMAZON_PS_PAYMENT_METHOD_INSTALLMENTS     = 'amazon_ps_installments';
    const AMAZON_PS_PAYMENT_METHOD_APPLE_PAY        = 'amazon_ps_apple_pay';

    // Integration Types Values
    const AMAZON_PS_INTEGRATION_TYPE_REDIRECTION       = 'redirection';
    const AMAZON_PS_INTEGRATION_TYPE_STANDARD_CHECKOUT = 'standard_checkout';
    const AMAZON_PS_INTEGRATION_TYPE_HOSTED_CHECKOUT   = 'hosted_checkout';
    const AMAZON_PS_INTEGRATION_TYPE_EMBEDDED_HOSTED_CHECKOUT   = 'embedded_hosted_checkout';

    const AMAZON_PS_RETRY_PAYMENT_OPTIONS    = array(
        'VISA',
        'MASTERCARD',
        'AMEX',
        'MADA',
        'MEEZA',
    );
    const AMAZON_PS_RETRY_DIGITAL_WALLETS    = array(
        'VISA_CHECKOUT',
        'APPLE_PAY',
    );

    //Payment response coder
    const AMAZON_PS_PAYMENT_SUCCESS_RESPONSE_CODE               = '14000';
    const AMAZON_PS_TOKENIZATION_SUCCESS_RESPONSE_CODE          = '18000';
    const AMAZON_PS_SAFE_TOKENIZATION_SUCCESS_RESPONSE_CODE     = '18062';
    const AMAZON_PS_UPDATE_TOKENIZATION_SUCCESS_RESPONSE_CODE   = '18063';
    const AMAZON_PS_PAYMENT_CANCEL_RESPONSE_CODE                = '00072';
    const AMAZON_PS_MERCHANT_SUCCESS_RESPONSE_CODE              = '20064';
    const AMAZON_PS_GET_INSTALLMENT_SUCCESS_RESPONSE_CODE       = '62000';
    const AMAZON_PS_VALU_CUSTOMER_VERIFY_SUCCESS_RESPONSE_CODE  = '90000';
    const AMAZON_PS_VALU_CUSTOMER_VERIFY_FAILED_RESPONSE_CODE   = '00160';
    const AMAZON_PS_VALU_OTP_GENERATE_SUCCESS_RESPONSE_CODE     = '88000';
    const AMAZON_PS_VALU_OTP_VERIFY_SUCCESS_RESPONSE_CODE       = '92182';
    const AMAZON_PS_REFUND_SUCCESS_RESPONSE_CODE                = '06000';
    const AMAZON_PS_PAYMENT_AUTHORIZATION_SUCCESS_RESPONSE_CODE = '02000';
    const AMAZON_PS_TOKEN_SUCCESS_RESPONSE_CODE                 = '52062';
    const AMAZON_PS_TOKEN_SUCCESS_STATUS_CODE                   = '52';
    const AMAZON_PS_CAPTURE_SUCCESS_RESPONSE_CODE               = '04000';
    const AMAZON_PS_AUTHORIZATION_VOIDED_SUCCESS_RESPONSE_CODE  = '08000';
    const AMAZON_PS_CHECK_STATUS_SUCCESS_RESPONSE_CODE          = '12000';
    const AMAZON_PS_CHECK_STATUS_ORDER_NOT_FOUND_RESPONSE_CODE  = '12036';
    const AMAZON_PS_PAYMENT_TOKEN_UPDATE_RESPONSE_CODE          = '58000';
    const AMAZON_PS_ONHOLD_RESPONSE_CODES                       = array(
        '15777',
        '15778',
        '15779',
        '15780',
        '15781',
        '00006',
        '01006',
        '02006',
        '03006',
        '04006',
        '05006',
        '06006',
        '07006',
        '08006',
        '09006',
        '11006',
        '13006',
        '17006',
    );
    const AMAZON_PS_FAILED_RESPONSE_CODES                       = array(
        '13666',
        '00072',
    );

    //flash messages constant
    const AMAZON_PS_FLASH_MSG_ERROR          ='E';
    const AMAZON_PS_FLASH_MSG_SUCCESS        ='S';
    const AMAZON_PS_FLASH_MSG_INFO           ='I';
    const AMAZON_PS_FLASH_MSG_WARNING        ='W';

    // API Command
    const AMAZON_PS_COMMAND_GET_INSTALLMENT_PLANS = 'GET_INSTALLMENTS_PLANS';
    const AMAZON_PS_COMMAND_TOKENIZATION          = 'TOKENIZATION';
    const AMAZON_PS_COMMAND_STANDALONE            = 'STANDALONE';
    const AMAZON_PS_COMMAND_PURCHASE              = 'PURCHASE';
    const AMAZON_PS_COMMAND_AUTHORIZATION         = 'AUTHORIZATION';
    const AMAZON_PS_COMMAND_VISA_CHECKOUT_WALLET  = 'VISA_CHECKOUT';
    const AMAZON_PS_COMMAND_REFUND                = 'REFUND';
    const AMAZON_PS_COMMAND_RECURRING             = 'RECURRING';
    const AMAZON_PS_COMMAND_CAPTURE               = 'CAPTURE';
    const AMAZON_PS_COMMAND_VOID_AUTHORIZATION    = 'VOID_AUTHORIZATION';
    const AMAZON_PS_COMMAND_ECOMMERCE             = 'ECOMMERCE';
    const AMAZON_PS_COMMAND_CHECK_STATUS          = 'CHECK_STATUS';

    // Generic Constants
    const AMAZON_PS_VALU_EG_COUNTRY_CODE = '+20';

    //API urls
    const GATEWAY_PRODUCTION_URL                  = 'https://checkout.payfort.com/FortAPI/paymentPage';
    const GATEWAY_SANDBOX_URL                     = 'https://sbcheckout.payfort.com/FortAPI/paymentPage';

    const GATEWAY_PRODUCTION_NOTIFICATION_API_URL = 'https://paymentservices.payfort.com/FortAPI/paymentApi/';
    const GATEWAY_SANDBOX_NOTIFICATION_API_URL    = 'https://sbpaymentservices.payfort.com/FortAPI/paymentApi/';

    const VISA_CHECKOUT_BUTTON_PRODUCTION         = "https://assets.secure.checkout.visa.com/wallet-services-web/xo/button.png";
    const VISA_CHECKOUT_BUTTON_SANDBOX            = "https://sandbox-assets.secure.checkout.visa.com/wallet-services-web/xo/button.png";

    const VISA_CHECKOUT_JS_PRODUCTION             = 'https://assets.secure.checkout.visa.com/checkout-widget/resources/js/integration/v1/sdk.js';
    const VISA_CHECKOUT_JS_SANDBOX                = 'https://sandbox-assets.secure.checkout.visa.com/checkout-widget/resources/js/integration/v1/sdk.js';

    // order recurring status
    const RECURRING_ACTIVE = 1;
    const RECURRING_INACTIVE = 2;
    const RECURRING_CANCELLED = 3;
    const RECURRING_SUSPENDED = 4;
    const RECURRING_EXPIRED = 5;
    const RECURRING_PENDING = 6;

    // order recurring transaction status
    const TRANSACTION_DATE_ADDED = 0;
    const TRANSACTION_PAYMENT = 1;
    const TRANSACTION_OUTSTANDING_PAYMENT = 2;
    const TRANSACTION_SKIPPED = 3;
    const TRANSACTION_FAILED = 4;
    const TRANSACTION_CANCELLED = 5;
    const TRANSACTION_SUSPENDED = 6;
    const TRANSACTION_SUSPENDED_FAILED = 7;
    const TRANSACTION_OUTSTANDING_FAILED = 8;
    const TRANSACTION_EXPIRED = 9;

    // order status id
    const UNPROCESSED_ORDER_STATUS_ID = 0;
    const PENDING_ORDER_STATUS_ID     = 1;//onhold
    const PROCESSING_ORDER_STATUS_ID  = 2;
    const SHIPPED_ORDER_STATUS_ID     = 3;
    const COMPLETE_ORDER_STATUS_ID    = 5;
    const CANCEL_ORDER_STATUS_ID      = 7;
    const FAILED_ORDER_STATUS_ID      = 10;
    const REFUNDED_ORDER_STATUS_ID    = 11;
    const PROCESSED_ORDER_STATUS_ID   = 15;
    const VOIDED_ORDER_STATUS_ID      = 16;

    //Bins
    const MADA_BINS = '440647|440795|446404|457865|968208|457997|474491|636120|417633|468540|468541|468542|468543|968201|446393|409201|458456|484783|462220|455708|410621|455036|486094|486095|486096|504300|440533|489318|489319|445564|968211|410685|406996|432328|428671|428672|428673|968206|446672|543357|434107|407197|407395|412565|431361|604906|521076|529415|535825|543085|524130|554180|549760|968209|524514|529741|537767|535989|536023|513213|520058|558563|588982|589005|531095|530906|532013|968204|422817|422818|422819|428331|483010|483011|483012|589206|968207|419593|439954|530060|531196|420132|421141|588845|403024|968205|406136|42689700';
    const MEEZA_BINS = '507803[0-6][0-9]|507808[3-9][0-9]|507809[0-9][0-9]|507810[0-2][0-9]';
      
    public function __construct($registry) {
        $this->session = $registry->get('session');
        $this->url = $registry->get('url');
        $this->config = $registry->get('config');
        $this->log = $registry->get('log');
        $this->customer = $registry->get('customer');
        $this->currency = $registry->get('currency');
        $this->registry = $registry;
    }    
}
