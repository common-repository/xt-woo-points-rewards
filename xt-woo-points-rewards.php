<?php
/**
 * XT Points & Rewards for WooCommerce
 *
 * @package     XT_Woo_Points_Rewards
 * @author      XplodedThemes
 * @copyright   2018 XplodedThemes
 * @license     GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name: XT Points & Rewards for WooCommerce
 * Plugin URI:  https://xplodedthemes.com/products/woo-points-rewards/
 * Description: A WooCommerce extension that lets you reward your customers for purchases and other actions with points that can be redeemed for discounts
 * Version:     1.7.5
 * WC requires at least: 3.0.0
 * WC tested up to: 9.1
 * Author:      XplodedThemes
 * Author URI:  https://xplodedthemes.com
 * Text Domain: xt-woo-points-rewards
 * Domain Path: /languages/
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 *
     */
 
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

global $xt_woopr_plugin;

$market = '##XT_MARKET##';
$market = (strpos($market, 'XT_MARKET') !== false) ? 'freemius' : $market;
$market = (defined('XT_MARKET')) ? XT_MARKET : $market;

$xt_woopr_plugin = array(
    'version'       => '1.7.5',
    'name'          => esc_html__('Points & Rewards for WooCommerce', 'xt-woo-points-rewards'),
    'menu_name'     => esc_html__('Points & Rewards', 'xt-woo-points-rewards'),
    'url'           => 'https://xplodedthemes.com/products/woo-points-rewards/',
    'slug'          => 'xt-woo-points-rewards',
	'prefix'       => 'xt_woo_points_rewards',
	'short_prefix' => 'xt_woopr',
    'market'        => $market,
    'markets'       => array(
	    'freemius' => array(
		    'id'            => 4927,
		    'key'           => 'pk_4bce78499b03ee1b41a2df4021b87',
		    'url'           => 'https://xplodedthemes.com/products/woo-points-rewards/',
		    'premium_slug'  => 'xt-woo-points-rewards-pro',
		    'freemium_slug' => 'xt-woo-points-rewards',
	    ),
        'envato' => array(
            'id' => 1,
            'url' => 'https://codecanyon.net/item/woocommerce-points-and-rewards/0',
            'premium_slug'  => 'xt-woo-points-rewards-pro'
        )
    ),
	'dependencies' => array(
		array(
			'name'  => 'WooCommerce',
            'class' => 'WooCommerce',
            'slug'  => 'woocommerce'
		)
	),
    'conflicts' => array(
        array(
            'name'  => 'WooCommerce Points And Rewards',
            'path'  => 'woocommerce-points-and-rewards/woocommerce-points-and-rewards.php',
        ),
        array(
            'name'  => 'YITH WooCommerce Points and Rewards',
            'path'   => 'yith-woocommerce-points-and-rewards/init.php'
        ),
        array(
            'name'  => 'YITH WooCommerce Points and Rewards Premium',
            'path'   => 'yith-woocommerce-points-and-rewards-premium/init.php'
        )
    ),
    'file'          => __FILE__
);

if ( function_exists( 'xt_woo_points_rewards' ) ) {

	xt_woo_points_rewards()->access_manager()->set_basename( false, __FILE__ );

} else {

	/**
	 * Require XT Framework
	 *
	 * @since    1.0.0
	 */
	require_once plugin_dir_path( __FILE__ ) . 'xt-framework/start.php';

	/**
	 * Require main plugin file
	 *
	 * @since    1.0.0
	 */
	require_once plugin_dir_path( __FILE__ ) . 'class-core.php';

    /**
     * Begins execution of the plugin.
     *
     * Since everything within the plugin is registered via hooks,
     * then kicking off the plugin from this point in the file does
     * not affect the page life cycle.
     *
     * @since    1.0.0
     */
    function xt_woo_points_rewards() {

        global $xt_woopr_plugin;

        return XT_Woo_Points_Rewards::instance($xt_woopr_plugin);
    }

    // Run Plugin.
    xt_woo_points_rewards();

}