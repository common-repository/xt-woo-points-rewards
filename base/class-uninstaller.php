<?php

/**
 * Fired during plugin uninstall
 *
 * @link       http://xplodedthemes.com
 * @since      1.0.0
 *
 * @package    XT_Woo_Points_Rewards
 * @subpackage XT_Woo_Points_Rewards/includes
 */

/**
 * Fired during plugin uninstall.
 *
 * This class defines all code necessary to run during the plugin's uninstall.
 *
 * @since      1.0.0
 * @package    XT_Woo_Points_Rewards
 * @subpackage XT_Woo_Points_Rewards/includes
 * @author     XplodedThemes 
 */
class XT_Woo_Points_Rewards_Uninstaller {

    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public static function uninstall() {

        do_action('xt_woopr_uninstall');
    }

}

XT_Woo_Points_Rewards_Uninstaller::uninstall();