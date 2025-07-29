# Amazon Payment Services Extension for OpenCart
<a href="https://paymentservices.amazon.com/" target="_blank">Amazon Payment Services</a> extension offers seamless payments for OpenCart platform merchants.  If you don't have an APS account click [here](https://paymentservices.amazon.com/) to sign up for Amazon Payment Services account.


## Getting Started
We know that payment processing is critical to your business. With this extension we aim to increase your payment processing capabilities. Do you have a business-critical questions? View our quick reference [documentation](https://paymentservices.amazon.com/docs/EN/index.html) for key insights covering payment acceptance, integration, and reporting.


## Configuration and User Guide
You can download the archive [file](/oc3_apsopencart_2.3.0.ocmod.zip) of the extension and easily install it via OpenCart admin screen (ocmod).
OpenCart Extension user guide is included in the repository [here](https://github.com/payfort/opencart3-payfort/wiki) 
   

## Payment Options

* Integration Types
   * Redirection
   * Merchant Page
   * Hosted Merchant Page
   * Installments
   * Embedded Hosted Installments

* Payment methods
   * Mastercard
   * VISA
   * American Express
   * VISA Checkout
   * valU
   * mada
   * Meeza
   * KNET
   * NAPS
   * Apple Pay
 
# Installation
##  Admin Panel
- Login to [Admin Panel] of Opencart website 
- Navigate to Extensions-> Installer 
- Click on “Upload” and choose the extension zip file 
- Under Install History section click on “Install” 
- Follow the configuration steps mentioned in Step 3 
## SFTP 
- Connect via SFTP and navigate to [your site root folder] 
- Copy Opencart APS extension folder under root folder 
- Navigate to Extensions-> Extensions 
- Under “Choose the extension type” choose Payments 
- Under Payments section find a payment methods names as “Amazon Payment Services” 
- Click on Install icon 
- Follow the configuration steps mentioned in Step 3 
## Configuration 

Follow the below instruction to access configuration page of APS Opencart extension:  

- Navigate to Extensions -> Extensions 
- Under “Choose the extension type” choose Payments 
- Under Payments section find a payment methods names as “Amazon Payment Services” 
- Click on edit icon 


| Extension Version | Release Notes |
| :---: | :--- |
| 2.5.0 |   * Use parametrized queries  |
| 2.4.0 |   * New payment option: Tabby  |
| 2.3.0 |   * valU changes: downpayment, ToU and Cashback amounts are included in checkout page  |
| 2.2.2 |   * Certificate upload hardening | 
| 2.2.1 |   * Curl hardening, url validation and follow redirect limitation | 
| 2.2.0 |   * Installments are embedded in Debit/Credit Card payment option | 
| 2.1.0 |   * ApplePay is activated in Product and Cart pages | 
| 2.0.0 |   * Integrated payment options: MasterCard, Visa, AMEX, mada, Meeza, KNET, NAPS, Visa Checkout, ApplePay, valU <br/> * Tokenization is enabled for Debit/Credit Cards and Installments <br/> * Recurring is available via Subscription products <br/> * Partial/Full Refund, Single/Multiple Capture and Void events are manage in Opencart order management screen | 



## API Documentation
This extension has been implemented by using following [API library](https://paymentservices-reference.payfort.com/docs/api/build/index.html)


## Further Questions
Have any questions? Just get in [touch](https://paymentservices.amazon.com/get-in-touch)

## License
Released under the [MIT License](/LICENSE).
