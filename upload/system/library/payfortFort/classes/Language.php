<?php

class Payfort_Fort_Language
{
    
    public static function __($registry, $input, $args = array(), $domain = 'extension/payment/payfort_fort')
    {        
        $registry->get('language')->load($domain);
        return $registry->get('language')->get($input);
    }

    public static function getCurrentLanguageCode($registry) 
    {
        return $registry->get('language')->get('code');
    }
}

?>