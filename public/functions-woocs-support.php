<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Woo Currency Switcher Integration
// https://currency-switcher.com/

if (class_exists('WOOCS')) {

    function xt_woopr_currency_switcher_filter_amount($amount, $multiply = false) {

        global $WOOCS;

        if ($WOOCS->is_multiple_allowed) {
            $currrent = $WOOCS->current_currency;
            if ($currrent != $WOOCS->default_currency) {
                $currencies = $WOOCS->get_currencies();
                $rate = $currencies[$currrent]['rate'];
                if(!$multiply) {
                    $amount = $amount / $rate;
                }else{
                    $amount = $amount * $rate;
                }
            }
        }

        return $amount;
    }

    add_filter('xt_woopr_filter_amount', 'xt_woopr_currency_switcher_filter_amount', 10, 1);

    function xt_woopr_currency_switcher_monetary_value($monetary_value) {

        return xt_woopr_currency_switcher_filter_amount($monetary_value, true);
    }

    add_filter('xt_woopr_filter_monetary_value', 'xt_woopr_currency_switcher_monetary_value', 10, 1);
}