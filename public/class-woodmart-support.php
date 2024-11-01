<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Woodmart Theme Integration
// https://woodmart.xtemos.com/


class XT_WOOPR_Woodmart {

    public static function init() {

        if (defined('WOODMART_THEME_DIR')) {
            add_filter('xt_woopr_shop_render_messages_hook_name', array(__CLASS__, 'shop_render_messages_hook_name'));
        }
    }

    public static function shop_render_messages_hook_name() {

        return 'woodmart_before_shop_page';
    }

}

add_action('after_setup_theme', 'XT_WOOPR_Woodmart::init');