<?php

/**
 * Fired during plugin activation
 *
 * @link       http://xplodedthemes.com
 * @since      1.0.0
 *
 * @package    XT_Woo_Points_Rewards
 * @subpackage XT_Woo_Points_Rewards/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    XT_Woo_Points_Rewards
 * @subpackage XT_Woo_Points_Rewards/includes
 * @author     XplodedThemes 
 */
class XT_Woo_Points_Rewards_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {

		flush_rewrite_rules();

        do_action('xt_woopr_activate');
	}

}

XT_Woo_Points_Rewards_Activator::activate();