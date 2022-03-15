<?php
class ModelExtensionPaymentAmazonPS extends Model {
    private $amazonpspaymentservices;

    public function __construct($registry)
	{
		$this->registry = $registry;
        $this->amazonpspaymentservices = new AmazonPSPaymentServices($registry);
	}

	public function getMethod($address, $total) {
		$this->load->language('extension/payment/amazon_ps');

		$status = $this->amazonpspaymentservices->isCcActive();
		
		$method_data = array();

		if ($status) {
			$method_data = array(
				'code'       => 'amazon_ps',
				'title'		 => $this->get_checkout_payment_title().$this->get_icon(),
			//	'title'      => $this->language->get('text_title'),
				'terms'      => '',
				'sort_order' => $this->config->get('payment_amazon_ps_cc_sort_order')
			);
		}

		return $method_data;
	}

	public function get_icon() {
		$icon_html       = '';
		$image_directory = 'catalog/view/theme/default/image/amazon_ps/';
		$mada_logo       = $image_directory . 'mada-logo.png';
		$visa_logo       = $image_directory . 'visa-logo.png';
		$mastercard_logo = $image_directory . 'mastercard-logo.png';
		$amex_logo       = $image_directory . 'amex-logo.png';
		$meeza_logo      = $image_directory . 'meeza-logo.jpg';
		//Wrap icons
		if ( $this->amazonpspaymentservices->isMadaBranding() ) {
			$icon_html .= '<img style="margin: 5px !important;" src="' . $mada_logo . '" alt="mada" class="payment-icons" />';
		}
		$icon_html .= '<img style="margin: 5px !important;" src="' . $visa_logo . '" alt="visa" class="payment-icons" />';
		$icon_html .= '<img style="margin: 5px !important;" src="' . $mastercard_logo . '" alt="mastercard" class="payment-icons"/>';
		$icon_html .= '<img style="margin: 5px !important;" src="' . $amex_logo . '" alt="amex" class="payment-icons"/>';
		if ( $this->amazonpspaymentservices->isMeezaBranding() ) {
			$icon_html .= '<img style="margin: 5px !important;" src="' . $meeza_logo . '" alt="meeza" class="payment-icons"/>';
		}
		$icon_html .= '';
		return $icon_html;
	}

	public function get_card_inline_icon() {
		$icon_html       = '';
		$image_directory = 'catalog/view/theme/default/image/amazon_ps/';
		$mada_logo       = $image_directory . 'mada-logo.png';
		$visa_logo       = $image_directory . 'visa-logo.png';
		$mastercard_logo = $image_directory . 'mastercard-logo.png';
		$amex_logo       = $image_directory . 'amex-logo.png';
		$meeza_logo      = $image_directory . 'meeza-logo.jpg';
		//Wrap icons
		if ( $this->amazonpspaymentservices->isMadaBranding() ) {
			$icon_html .= '<img src="' . $mada_logo . '" alt="mada" class="card-mada card-icon" />';
		}
		$icon_html .= '<img src="' . $visa_logo . '" alt="visa" class="card-visa card-icon" />';
		$icon_html .= '<img src="' . $mastercard_logo . '" alt="mastercard" class="card-mastercard card-icon"/>';
		$icon_html .= '<img src="' . $amex_logo . '" alt="amex" class="card-amex card-icon"/>';
		if ( $this->amazonpspaymentservices->isMeezaBranding() ) {
			$icon_html .= '<img src="' . $meeza_logo . '" alt="meeza" class="card-meeza card-icon"/>';
		}
		$icon_html .= '';
		return $icon_html;
	}
	/**
	 * Get Checkout Payment Title
	 */
	public function get_checkout_payment_title() {
		if ( $this->amazonpspaymentservices->isMadaBranding() ) {
			return $this->language->get('mada_text_title');
		}
		return $this->language->get('text_title');
	}

	public function updateAmazonPSMetaData($order_id, $meta_key, $meta_value, $mix_value = false) {
		if($mix_value){
			$meta_value = serialize($meta_value);
		}
		$this->db->query("INSERT INTO `" . DB_PREFIX . "amazon_ps_order_meta_data` SET `order_id` = '" . (int)$order_id . "',`meta_key` = '" . $this->db->escape($meta_key) . "',`meta_value` = '" . $this->db->escape($meta_value) . "',  `date_added` = now()");
		return $this->db->getLastId();
	}

    public function saveUpdateValuOrderReferenceId($order_id, $reference_id){
        $qry = $this->db->query("SELECT * FROM `" . DB_PREFIX . "amazon_ps_order_meta_data` WHERE `order_id` = '" . (int)$order_id . "' AND `meta_key` = 'valu_reference_id' LIMIT 1");

        if (!($qry->num_rows)) {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "amazon_ps_order_meta_data` SET `order_id` = '" . (int)$order_id . "',`meta_key` = 'valu_reference_id',`meta_value` = '" . $this->db->escape($reference_id) . "',  `date_added` = now()");
        }else{
            $this->db->query("UPDATE `" . DB_PREFIX . "amazon_ps_order_meta_data` SET `meta_value` = '" . $this->db->escape($reference_id) . "' where `order_id` = '" . (int)$order_id . "' and `meta_key` = 'valu_reference_id'");
        }
    }

    public function find_valu_order_by_reference($reference_id){
        $qry = $this->db->query("SELECT order_id FROM `" . DB_PREFIX . "amazon_ps_order_meta_data` WHERE `meta_value` = '" . $this->db->escape($reference_id ). "' AND `meta_key` = 'valu_reference_id' LIMIT 1");

        if ( $qry->num_rows ) {
            return $qry->row['order_id'];
        } else {
            return false;
        }
    }

	public function getAmazonPSMetaValue($order_id, $meta_key, $mix_value = false) {
		$qry = $this->db->query("SELECT meta_value FROM `" . DB_PREFIX . "amazon_ps_order_meta_data` WHERE `order_id` = '" . (int)$order_id . "' AND `meta_key` = '" . $this->db->escape($meta_key) . "' LIMIT 1");

		if ( $qry->num_rows ) {
			if($mix_value){
				return unserialize($qry->row['meta_value']);
			}
			return $qry->row['meta_value'];
		} else {
			return false;
		}
	}

	public function getAmazonPSMetaData($order_id, $meta_key, $mix_value = false) {
        $qry = $this->db->query("SELECT * FROM `" . DB_PREFIX . "amazon_ps_order_meta_data` WHERE `order_id` = '" . (int)$order_id . "' AND `meta_key` = '" . $this->db->escape($meta_key) . "' ORDER BY `amazon_ps_order_id` DESC");

        $result = [];
        if ( $qry->num_rows ) {
            foreach ($qry->rows as $row) {
                $result[] = $row;
            }
        }
        return $result;
    }

    public function find_valu_reference_by_order($orderId){
        $qry = $this->db->query("SELECT meta_value FROM `" . DB_PREFIX . "amazon_ps_order_meta_data` WHERE `order_id` = '" . $this->db->escape($orderId ). "' AND `meta_key` = 'valu_reference_id' LIMIT 1");

        if ( $qry->num_rows ) {
            return $qry->row['meta_value'];
        } else {
            return false;
        }
    }

	/*
	    * Used by the checkout to state the module
	    * supports recurring recurrings.
	*/
	public function recurringPayments() {
        return true;
   	}

   	public function nextRecurringOrderPayments($order_recurring_id = 0) {
        $payments = array();

        $where_recurring = '';
        if($order_recurring_id){
        	$where_recurring = " AND order_recurring_id = '".$order_recurring_id."'";
        }

        $recurring_sql = "SELECT `or`.* FROM `" . DB_PREFIX . "order_recurring` `or` INNER JOIN `" . DB_PREFIX . "order` o ON (o.order_id = `or`.order_id) WHERE `or`.status='" . AmazonPSConstant::RECURRING_ACTIVE . "' AND payment_code='" . AmazonPSConstant::AMAZON_PS_PAYMENT_METHOD_CC . "'".$where_recurring;

        $this->load->model('checkout/order');
        foreach ($this->db->query($recurring_sql)->rows as $recurring) {
            if (!$this->paymentIsDue($recurring['order_recurring_id'])) {
                continue;
            }

            $price = (float)($recurring['trial'] ? $recurring['trial_price'] : $recurring['recurring_price']);

            $payments[] = array(
                'is_free' => $price == 0,
                'amount'  => $price * $recurring['product_quantity'],
                'order_id' => $recurring['order_id'],
                'order_recurring_id' => $recurring['order_recurring_id']
            );
        }
        return $payments;
    }

	private function paymentIsDue($order_recurring_id) {
        // The recurring profile is active.
        $recurring_info = $this->getRecurring($order_recurring_id);

        if ($recurring_info['trial']) {
            $frequency = $recurring_info['trial_frequency'];
            $cycle = (int)$recurring_info['trial_cycle'];
        } else {
            $frequency = $recurring_info['recurring_frequency'];
            $cycle = (int)$recurring_info['recurring_cycle'];
        }
        // Find date of last payment
        if (!$this->getTotalSuccessfulPayments($order_recurring_id)) {
            $previous_time = strtotime($recurring_info['date_added']);
        } else {
            $previous_time = strtotime($this->getLastSuccessfulRecurringPaymentDate($order_recurring_id));
        }

        switch ($frequency) {
            case 'day' : $time_interval = 24 * 3600; break;
            case 'week' : $time_interval = 7 * 24 * 3600; break;
            case 'semi_month' : $time_interval = 15 * 24 * 3600; break;
            case 'month' : $time_interval = 30 * 24 * 3600; break;
            case 'year' : $time_interval = 365 * 24 * 3600; break;
        }

        $due_date = date('Y-m-d', $previous_time + ($time_interval * $cycle));

        $this_date = date('Y-m-d');
        return $this_date >= $due_date;
    }

    public function getPaymentPendingOrders() {
        $order_status_ids = array(
            AmazonPSConstant::UNPROCESSED_ORDER_STATUS_ID,
            AmazonPSConstant::PENDING_ORDER_STATUS_ID
        );

    	$payment_codes = array(
    		AmazonPSConstant::AMAZON_PS_PAYMENT_METHOD_CC,
    		AmazonPSConstant::AMAZON_PS_PAYMENT_METHOD_NAPS,
    		AmazonPSConstant::AMAZON_PS_PAYMENT_METHOD_KNET,
    		AmazonPSConstant::AMAZON_PS_PAYMENT_METHOD_VALU,
    		AmazonPSConstant::AMAZON_PS_PAYMENT_METHOD_VISA_CHECKOUT,
    		AmazonPSConstant::AMAZON_PS_PAYMENT_METHOD_INSTALLMENTS,
            AmazonPSConstant::AMAZON_PS_PAYMENT_METHOD_APPLE_PAY,
    	);

        $duration_mins = $this->amazonpspaymentservices->getCheckStatusCronDuration();
        $current_datetime = date("Y-m-d H:i:s");
        $order_datetime = date("Y-m-d H:i:s", strtotime("-{$duration_mins} minutes", strtotime($current_datetime)));

    	$payment_codes = implode("','", array_values($payment_codes));
		$sql = "SELECT o.* FROM `" . DB_PREFIX . "order` o JOIN `" . DB_PREFIX . "amazon_ps_order_meta_data` aps_om ON (o.order_id = aps_om.order_id) where aps_om.meta_key='aps_redirected_order' AND o.order_status_id IN(". implode(",", $order_status_ids).") and o.payment_code IN('".$payment_codes."') and o.date_added <'".$order_datetime."'";
		return $this->db->query($sql)->rows;
	}

	public function getRecurring($order_recurring_id) {
        $recurring_sql = "SELECT * FROM `" . DB_PREFIX . "order_recurring` WHERE order_recurring_id='" . (int)$order_recurring_id . "'";

        return $this->db->query($recurring_sql)->row;
    }

    public function getRecurringByOrderId($orderId){
    	$recurring_sql = "SELECT * FROM `" . DB_PREFIX . "order_recurring` WHERE order_id='" .$orderId. "'";
    	return $this->db->query($recurring_sql)->rows;	
    }

    private function getTotalSuccessfulPayments($order_recurring_id) {
        return $this->db->query("SELECT COUNT(*) as total FROM `" . DB_PREFIX . "order_recurring_transaction` WHERE order_recurring_id='" . (int)$order_recurring_id . "' AND type='" . AmazonPSConstant::TRANSACTION_PAYMENT . "'")->row['total'];
    }

    private function getLastSuccessfulRecurringPaymentDate($order_recurring_id) {
        return $this->db->query("SELECT date_added FROM `" . DB_PREFIX . "order_recurring_transaction` WHERE order_recurring_id='" . (int)$order_recurring_id . "' AND type='" . AmazonPSConstant::TRANSACTION_PAYMENT . "' ORDER BY date_added DESC LIMIT 0,1")->row['date_added'];
    }

    public function addRecurringTransaction($order_recurring_id, $reference, $amount, $type) {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "order_recurring_transaction` SET order_recurring_id='" . (int)$order_recurring_id . "', reference='" . $this->db->escape($reference) . "', type='" . (int)$type . "', amount='" . (float)$amount . "', date_added=NOW()");
    }

    public function updateRecurringTransaction($reference, $type){
    	$this->db->query("UPDATE `" . DB_PREFIX . "order_recurring_transaction` SET type='".(int)$type."' WHERE reference='" . $this->db->escape($reference). "'");
    }

    public function updateRecurringTrial($order_recurring_id) {
        $recurring_info = $this->getRecurring($order_recurring_id);

        // If recurring payment is in trial and can expire (trial_duration > 0)
        if ($recurring_info['trial'] && $recurring_info['trial_duration']) {
            $number_of_successful_payments = $this->getTotalSuccessfulPayments($order_recurring_id);

            // If successful payments exceed trial_duration
            if ($number_of_successful_payments >= $recurring_info['trial_duration']) {
                $this->db->query("UPDATE `" . DB_PREFIX . "order_recurring` SET trial='0' WHERE order_recurring_id='" . (int)$order_recurring_id . "'");

                return true;
            }
        }

        return false;
    }

    public function updateRecurringExpired($order_recurring_id) {
        $recurring_info = $this->getRecurring($order_recurring_id);

        if ($recurring_info['trial']) {
            // If trial, we need to check if the trial will end at some point
            $expirable = (bool)$recurring_info['trial_duration'];
        } else {
            // If not trial, we need to check if the recurring will end at some point
            $expirable = (bool)$recurring_info['recurring_duration'];
        }

        // If recurring payment can expire (trial_duration > 0 AND recurring_duration > 0)
        if ($expirable) {
            $number_of_successful_payments = $this->getTotalSuccessfulPayments($order_recurring_id);

            $total_duration = (int)$recurring_info['trial_duration'] + (int)$recurring_info['recurring_duration'];
            
            // If successful payments exceed (trial_duration+recurring_duration)
            if ($number_of_successful_payments >= $total_duration) {
                $this->db->query("UPDATE `" . DB_PREFIX . "order_recurring` SET status='" . AmazonPSConstant::RECURRING_EXPIRED . "' WHERE order_recurring_id='" . (int)$order_recurring_id . "'");

                return true;
            }
        }

        return false;
    }

    public function suspendRecurringProfile($order_recurring_id) {
        $this->db->query("UPDATE `" . DB_PREFIX . "order_recurring` SET status='" . AmazonPSConstant::RECURRING_SUSPENDED . "' WHERE order_recurring_id='" . (int)$order_recurring_id . "'");

        return true;
    }

    public function updatePaymentMethod($order_id, $title){
        $this->db->query("UPDATE `" . DB_PREFIX . "order` SET payment_method='".$this->db->escape($title)."' WHERE order_id='" . (int)$order_id. "'");
    }

}
