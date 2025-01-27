<?php
/**
 * XT Points and Rewards
 *
 * @package     WC-Points-Rewards/Classes
 * @author      XplodedThemes
 * @copyright   Copyright (c) 2019, XplodedThemes
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Discount class
 *
 * Handles generating the coupon code and data that allows the user to redeem their points for a discount
 *
 * @since 1.0.00
 */
class XT_Woo_Points_Rewards_Discount {

	/**
	 * Core class reference.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      XT_Woo_Points_Rewards    $core    Core Class
	 */
	private $core;

	/**
	 * Add coupon-related filters to help generate the custom coupon
	 *
	 * @param XT_Woo_Points_Rewards $core
	 * @since 1.0.0
	 */
	public function __construct( $core ) {

		$this->core = $core;

		if(!$core->enabled()) {
			return $this;
		}

		$this->hooks( 'add' );
	}

	/**
	 * Add or remove callbacks to/from the hooks.
	 *
	 * @since 1.0.05
	 * @version 1.0.0
	 *
	 * @param string $verb What operation to perform (either 'add' or 'remove').
	 */
	protected function hooks( $verb ) {
		$filters = array(
			array( 'woocommerce_get_shop_coupon_data', array( $this, 'get_discount_data' ), 10, 2 ),
			array( 'woocommerce_coupon_message', array( $this, 'get_discount_applied_message' ), 10, 3 ),
            array( 'woocommerce_cart_totals_coupon_label', array($this, 'coupon_label'), 10, 2)
		);

		$func = 'add' === $verb ? 'add_filter' : 'remove_filter';
		foreach ( $filters as $filter ) {
			call_user_func_array( $func, $filter );
		}
	}

	/**
	 * Generate the coupon data required for the discount
	 *
	 * @deprecated 1.6.0
	 * @since 1.0
	 * @param array $data the coupon data
	 * @param string $code the coupon code
	 * @return array the custom coupon data
	 */
	public function get_discount_data( $data, $code ) {
		if ( strtolower( $code ) != $this->get_discount_code() ) {
			return $data;
		}

        // Only filter for frontend cart calculations.
        if ( ! WC()->cart || ! WC()->session ) {
            return $data;
        }

        // Discount calculation is much simpler now. We just add a fixed_cart coupon
        // to the cart with the amount determined by available points.
        $amount = $this->core->frontend()->cart->get_discount_for_redeeming_points(true, null, false, $code );

		$data = array(
			'id'                         => true,
			'type'                       => 'fixed_cart',
			'amount'                     => $amount,
			'coupon_amount'              => $amount, // 2.2
			'individual_use'             => false,
			'usage_limit'                => '',
			'usage_count'                => '',
			'expiry_date'                => '',
			'apply_before_tax'           => true,
			'free_shipping'              => false,
			'product_categories'         => array(),
			'exclude_product_categories' => array(),
			'exclude_sale_items'         => false,
			'minimum_amount'             => '',
			'maximum_amount'             => '',
			'customer_email'             => '',
		);

		return $data;
	}

	/**
	 * Change the "Coupon applied successfully" message to "Discount Applied Successfully"
	 *
	 * @since 1.0.00
	 * @param string $message the message text
	 * @param string $message_code the message code
	 * @param object $coupon the WC_Coupon instance
	 * @return string the modified messages
	 */
	public function get_discount_applied_message( $message, $message_code, $coupon ) {
		if ( WC_Coupon::WC_COUPON_SUCCESS === $message_code && $coupon->get_code() === $this->get_discount_code() ) {
			return __( 'Discount Applied Successfully', 'xt-woo-points-rewards' );
		} else {
			return $message;
		}
	}

	public function coupon_label($label, $coupon) {

	    if(strpos($coupon, $this->discount_code_prefix()) !== false) {
            return $this->core->settings()->get_option('points_coupon_label', esc_html__('Points Redemption', 'xt-woo-points-rewards'));
        }

        return $label;
    }
	
	/**
	 * Get discount code prefix
	 *
	 * @since 1.0.00
	 */
	public static function discount_code_prefix() {
		
		return apply_filters('discount_code_prefix', 'points_redemption_');
	}

	/**
	 * Generates a unique discount code tied to the current user ID and timestamp
	 *
	 * @since 1.0.00
	 */
	public static function generate_discount_code() {
		// set the discount code to the current user ID + the current time in YYYY_MM_DD_H_M format
	
		$discount_code = sprintf( '%s%s_%s', self::discount_code_prefix(), get_current_user_id(), date( 'Y_m_d_h_i', current_time( 'timestamp' ) ) );

		WC()->session->set( 'xt_woopr_discount_code', $discount_code );

		return $discount_code;
	}

	/**
	 * Returns the unique discount code generated for the applied discount if set
	 *
	 * @since 1.0.00
	 */
	public static function get_discount_code() {
		if ( WC()->session !== null ) {
			return WC()->session->get( 'xt_woopr_discount_code' );
		}
	}
}
