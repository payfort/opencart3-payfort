<?php
class ModelExtensionPaymentAmazonPSTokens extends Model {
    private $amazonpspaymentservices;
    
    public function __construct($registry)
    {
        $this->registry = $registry;
        $this->amazonpspaymentservices = new AmazonPSPaymentServices($registry);
    }

    public function saveApsTokens($order, $response_params, $response_mode ) {
        // if tokenization is not enabled then return
        if($this->amazonpspaymentservices->isEnabledTokenization() == false){
            $this->amazonpspaymentservices->log('Tokenization not enabled:');
            return;
        }

        //check response with get Method and card detail contain * only
        if ( isset( $response_params['expiry_date'] ) ) {
            if(!preg_match('#[^*]#',$response_params['expiry_date'])){
                // return if all character are *
                $this->amazonpspaymentservices->log('Token expiry_date not valid');
                return;
            }
        }
        $this->amazonpspaymentservices->log('tokens: '.$order['customer_id']);
        if(isset($order['customer_id']) && $order['customer_id'] > 0){
            $token_row_id = $this->insertOrUpdateGetId($response_params['token_name'], $order['customer_id']);
            if(intval($token_row_id) > 0 ) {
                if ( isset( $response_params['payment_option'] ) ) {
                    $this->updatePaymentMeta( $token_row_id, 'card_type', strtolower( $response_params['payment_option'] ) );
                }
                if ( isset( $response_params['card_holder_name'] ) ) {
                    $this->updatePaymentMeta( $token_row_id, 'card_holder_name', $response_params['card_holder_name'] );
                }
                if ( isset( $response_params['card_number'] ) ) {
                    $last4 = substr( $response_params['card_number'], -4 );
                    $this->updatePaymentMeta( $token_row_id, 'last4', $last4 );
                    $this->updatePaymentMeta( $token_row_id, 'masking_card', $response_params['card_number'] );
                }
                if ( isset( $response_params['expiry_date'] ) ) {
                    $short_year  = substr( $response_params['expiry_date'], 0, 2 );
                    $short_month = substr( $response_params['expiry_date'], 2, 2 );
                    $date_format = \DateTime::createFromFormat( 'y', $short_year );
                    $full_year   = $date_format->format( 'Y' );
                    $this->updatePaymentMeta( $token_row_id, 'expiry_month', $short_month );
                    $this->updatePaymentMeta( $token_row_id, 'expiry_year', $full_year );
                }
            }
        }
    }

    public function insertOrUpdateGetId( $token, $customer_id ) {
        $sql = "SELECT * FROM `" . DB_PREFIX . "amazon_ps_tokens` WHERE token = ?";
        $result = $this->db->query($sql, [$this->db->escape($token)]);
        if ($result->num_rows > 0) {
          return (int) $result->row['ID'];
        } else {
            $insert = array(
                'token' => $this->db->escape($token)
            );
            $insertData = array();
            foreach ($insert as $key => $value) {
                if(is_string($value)) {
                    $insertData[] = "`" . $key . "`='" . $value . "'";
                } else {
                    $insertData[] = "`" . $key . "`=" . $value;
                }
            }
            $insertData[]  = "`created_at` = NOW()";
            $insertData[]  = "`updated_at` = NOW()";
            $this->db->query("INSERT INTO `" . DB_PREFIX . "amazon_ps_tokens` SET customer_id='".$customer_id."', " . implode(',', $insertData));
            return $this->db->getLastId();
        }
    }

    public function updatePaymentMeta( $token_id, $meta_key, $meta_value ) {
        $sql = "SELECT * FROM `" . DB_PREFIX . "amazon_ps_token_meta_data` WHERE token_id = ? and meta_key='" . $this->db->escape($meta_key) . "'";
        $result = $this->db->query($sql, $this->db->escapse($token_id));
        if ($result->num_rows > 0) {
            $insert = array(
                'token_id' => $this->db->escape($token_id),
                'meta_key' => $this->db->escape($meta_key),
                'meta_value' => $this->db->escape($meta_value)
            );
            $insertData = array();
            foreach ($insert as $key => $value) {
                if(is_string($value)) {
                    $insertData[] = "`" . $key . "`='" . $value . "'";
                } else {
                    $insertData[] = "`" . $key . "`=" . $value;
                }
            }
            $insertData[]  = "`updated_at` = NOW()";
            $this->db->query("UPDATE `" . DB_PREFIX . "amazon_ps_token_meta_data` SET " . implode(',', $insertData) . " where ID = " . $result->row['ID']);
        } else {
            $insert = array(
                'token_id' => $this->db->escape($token_id),
                'meta_key' => $this->db->escape($meta_key),
                'meta_value' => $this->db->escape($meta_value)
            );
            $insertData = array();
            foreach ($insert as $key => $value) {
                if(is_string($value)) {
                    $insertData[] = "`" . $key . "`='" . $value . "'";
                } else {
                    $insertData[] = "`" . $key . "`=" . $value;
                }
            }
            $insertData[]  = "`created_at` = NOW()";
            $insertData[]  = "`updated_at` = NOW()";
            $this->db->query("INSERT INTO `" . DB_PREFIX . "amazon_ps_token_meta_data` SET " . implode(',', $insertData));
        }
    }

    public function getTokens() {
        $tokens = array();
        $customer_id = $this->customer->getId();
        $this->amazonpspaymentservices->log('getTokens For customer_id: '.$this->customer->getId());
        if($customer_id){
            $token_tbl      = DB_PREFIX . 'amazon_ps_tokens';
            $token_meta_tbl = DB_PREFIX . 'amazon_ps_token_meta_data';
            $query_sql      = "SELECT * FROM " . $token_tbl . " as OT INNER JOIN " . $token_meta_tbl . " as OTM on OT.ID = OTM.token_id where OT.customer_id='".$customer_id."'";
            $token_result   = $this->db->query($query_sql);
            
            if( $token_result->num_rows > 0 ) {
                $token_data = array();
                foreach( $token_result->rows as $row ) {
                    $token_data[$row['token']]['token']                    = $row['token'];
                    $token_data[$row['token']]['extras'][$row['meta_key']] = $row['meta_value'];
                }
                foreach( $token_data as $token_row ) {
                    $tokens[] = $token_row;
                }
            }
        }
        return $tokens;
    }

    public function getTokenCardType($token){
        $token_tbl      = DB_PREFIX . 'amazon_ps_tokens';
        $token_meta_tbl = DB_PREFIX . 'amazon_ps_token_meta_data';
        $query_sql      = "SELECT OTM.meta_value FROM " . $token_tbl . " as OT INNER JOIN " . $token_meta_tbl . " as OTM on OT.ID = OTM.token_id where OT.token ='". $this->db->escape($token) ."' and OTM.meta_key = 'card_type' LIMIT 1";
        $result = $this->db->query($query_sql);
        if($result->num_rows){
            return $result->row['meta_value'];
        }
        return '';
    }

    public function verifyTokenCustomer($token, $customer_id){
        $sql = "SELECT * FROM `" . DB_PREFIX . "amazon_ps_tokens` WHERE token='" . $this->db->escape($token) . "' and customer_id = '".$this->db->escape($customer_id)."'";
        $result = $this->db->query($sql);
        if ($result->num_rows > 0) {
          return (int) $result->row['ID'];
        }
        return false;
    }

    public function deleteToken($token, $customer_id){
        $token_id = $this->verifyTokenCustomer($token, $customer_id);
        if($token_id){
            $sql = "DELETE FROM `" . DB_PREFIX . "amazon_ps_tokens` WHERE token='" . $this->db->escape($token) . "' and customer_id = '".$this->db->escape($customer_id)."'";
            $result = $this->db->query($sql);

            $sql = "DELETE FROM `" . DB_PREFIX . "amazon_ps_token_meta_data` WHERE token_id='" . $this->db->escape($token) . "'";
            $result = $this->db->query($sql);
        }
    }
}
