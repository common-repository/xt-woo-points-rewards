<?php

/**
 * XT Points and Rewards
 *
 * @package     WC-Points-Rewards/Classes
 * @author      XplodedThemes
 * @copyright   Copyright (c) 2019, XplodedThemes
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * Order class
 *
 * Handle adding points earned upon checkout & deducting points redeemed for discounts
 *
 * @since 1.0
 */
class XT_Woo_Points_Rewards_Order {
    /**
     * Core class reference.
     *
     * @since    1.0.0
     * @access   private
     * @var      XT_Woo_Points_Rewards    $core    Core Class
     */
    private $core;

    /**
     * Add hooks/filters
     * @var      XT_Woo_Points_Rewards    $core    Core Class
     * @since 1.0
     */
    public function __construct( $core ) {
        $this->core = $core;
        if ( !$core->enabled() ) {
            return;
        }
        add_action( 'woocommerce_order_status_processing', array($this, 'maybe_update_points') );
        add_action( 'woocommerce_order_status_completed', array($this, 'maybe_update_points') );
        add_action( 'woocommerce_order_status_on-hold', array($this, 'maybe_update_points') );
        // Compatibility with WooCommerce Pre-Orders.
        add_action( 'woocommerce_order_status_pre-ordered', array($this, 'maybe_update_points') );
        add_action( 'woocommerce_checkout_order_processed', array($this, 'log_redemption_points') );
        // credit points back to the user if their order is cancelled or refunded
        add_action( 'woocommerce_order_status_cancelled', array($this, 'handle_cancelled_refunded_order') );
        add_action( 'woocommerce_order_status_refunded', array($this, 'handle_cancelled_refunded_order') );
        add_action( 'woocommerce_order_status_failed', array($this, 'handle_cancelled_refunded_order') );
        add_action(
            'woocommerce_order_partially_refunded',
            array($this, 'handle_partially_refunded_order'),
            10,
            2
        );
        add_filter( 'wcs_renewal_order_meta_query', array($this, 'exclude_meta_from_renewal_order') );
    }

    /**
     * Check if customer has previous completed orders
     *
     * @since 1.0.00
     * @version 1.0.0
     * @param bool
     */
    public function has_previous_orders( $user_id, $exclude_order_id = null ) {
        $query = array(
            'numberposts' => 1,
            'meta_key'    => '_customer_user',
            'meta_value'  => $user_id,
            'post_type'   => 'shop_order',
            'post_status' => 'wc-completed,wc-processing,wc-on-hold,wc-pending',
        );
        if ( $exclude_order_id ) {
            $query['post__not_in'] = array($exclude_order_id);
        }
        // Get all customer orders
        $customer_orders = get_posts( $query );
        // return "true" when customer has already at least one order (false if not)
        return ( count( $customer_orders ) > 0 ? true : false );
    }

    /**
     * Conditionally updates points.
     *
     * @since 1.0.00
     * @version 1.0.0
     * @param int $order_id
     */
    public function maybe_update_points( $order_id ) {
        $order = wc_get_order( $order_id );
        $this->maybe_deduct_redeemed_points( $order_id );
        $paid = null !== $order->get_date_paid( 'edit' );
        if ( $paid || 'completed' === $order->get_status() ) {
            $this->add_points_earned( $order_id );
        }
    }

    /**
     * Add the points earned for purchase to the customer's account upon successful payment
     *
     * @since 1.0
     * @param object|int $order the WC_Order object or order ID
     */
    public function add_points_earned( $order, $disable_emails = false ) {
        if ( !is_object( $order ) ) {
            $order = wc_get_order( $order );
        }
        $order_id = $order->get_id();
        $order_user_id = $order->get_user_id();
        // bail for guest user
        if ( !$order_user_id ) {
            return;
        }
        // Bail for gifted orders.
        $gift = get_post_meta( $order_id, '_wcgp_given_order', true );
        if ( 'yes' == $gift && apply_filters( 'woocommerce_points_rewards_ignore_gifted_orders', true ) ) {
            return;
        }
        // check if points have already been added for this order
        $points = $this->get_points_earned_for_order( $order_id );
        if ( !empty( $points ) ) {
            return;
        }
        // get points earned
        $points = $this->get_points_earned_for_purchase( $order, true );
        // set order meta, regardless of whether any points were earned, just so we know the process took place
        $this->update_points_earned_for_order( $order_id, $points );
        // bail if no points earned
        if ( !$points ) {
            return;
        }
        // add points
        XT_Woo_Points_Rewards_Manager::disable_emails( $disable_emails );
        XT_Woo_Points_Rewards_Manager::increase_points(
            $order_user_id,
            $points,
            'order-placed',
            null,
            $order_id
        );
        XT_Woo_Points_Rewards_Manager::disable_emails( false );
        // add order note
        /* translators: 1: points 2: points label */
        $order->add_order_note( sprintf( esc_html__( 'Customer earned %1$d %2$s for purchase.', 'xt-woo-points-rewards' ), $points, $this->core->get_points_label( $points ) ) );
        sleep( 1 );
        do_action( 'xt_woopr_after_order_points_added', $order, $points );
    }

    /**
     * Returns the amount of points earned for the purchase, calculated by getting the points earned for each individual
     * product purchase multiplied by the quantity being ordered
     *
     * @param WC_Order $order
     * @param bool $forOrderOnly
     * @return int
     * @since 1.0
     */
    private function get_points_earned_for_purchase( WC_Order $order, $forOrderOnly = false ) {
        $user_id = $order->get_user_id();
        $points_earned = 0;
        $order_total = 0;
        foreach ( $order->get_items() as $item_key => $item ) {
            $product = $item->get_product();
            if ( !is_object( $product ) ) {
                continue;
            }
            // If prices include tax, we include the tax in the points calculation
            if ( 'no' === get_option( 'woocommerce_prices_include_tax' ) ) {
                // Get the un-discounted price paid and adjust our product price
                $item_price = $order->get_item_subtotal( $item );
            } else {
                // Get the un-discounted price paid and adjust our product price
                $item_price = $order->get_item_subtotal( $item, true );
            }
            $order_total += $item_price;
            $product->set_price( $item_price );
            // Calc points earned
            $points_earned += apply_filters(
                'xt_woopr_points_earned_for_order_item',
                XT_Woo_Points_Rewards_Product::get_points_earned_for_product_purchase(
                    $product,
                    $order,
                    'edit',
                    false
                ),
                $product,
                $item_key,
                $item,
                $order
            ) * $item['qty'];
        }
        // Reduce by any discounts.  One minor drawback: if the discount includes a discount on tax and/or shipping
        // It will cost the customer points, but this is a better solution than granting full points for discounted orders.
        $discount = $order->get_total_discount( !wc_prices_include_tax() );
        $points_earned -= min( XT_Woo_Points_Rewards_Manager::calculate_points( $discount ), $points_earned );
        $points_earned = XT_Woo_Points_Rewards_Manager::round_the_points( $points_earned );
        if ( $forOrderOnly ) {
            return $points_earned;
        }
        return apply_filters(
            'xt_woopr_points_earned_for_purchase',
            $points_earned,
            $user_id,
            $order_total
        );
    }

    /**
     * Logs the possible points and amount for redemption.
     * This is needed because some orders will be in pending or on-hold
     * before it gets processed.
     *
     * @since 1.0.01
     * @version 1.0.0
     * @param int $order_id
     */
    public function log_redemption_points( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( !$order ) {
            return;
        }
        $discount_code = XT_Woo_Points_Rewards_Discount::get_discount_code();
        $max_discount = XT_Woo_Points_Rewards_Manager::get_users_points_value( get_current_user_id() );
        $discount_amount = $this->get_discount_from_code( $discount_code, $max_discount );
        $points_redeemed = XT_Woo_Points_Rewards_Manager::calculate_points_for_discount( $discount_amount );
        $order->update_meta_data( '_xt_woopr_points_logged_redemption', array(
            'points'        => $points_redeemed,
            'amount'        => $discount_amount,
            'discount_code' => $discount_code,
        ) );
        $order->save();
    }

    /**
     * Deducts the points redeemed for a discount when the order is processed at checkout. Note that points are deducted
     * immediately upon checkout processing to protect against abuse.
     *
     * @since 1.0
     * @param int $order_id the WC_Order ID
     */
    public function maybe_deduct_redeemed_points( $order_id ) {
        $already_redeemed = $this->get_points_redeemed_for_order( $order_id );
        $logged_redemption = $this->get_logged_redemption_for_order( $order_id );
        // Points has already been redeemed
        if ( !empty( $already_redeemed ) ) {
            return;
        }
        $order = wc_get_order( $order_id );
        $order_user_id = $order->get_user_id();
        // bail for guest user
        if ( !$order_user_id ) {
            return;
        }
        $discount_code = XT_Woo_Points_Rewards_Discount::get_discount_code();
        $order_statuses = apply_filters( 'xt_woopr_redeem_points_order_statuses', array('processing', 'completed') );
        if ( !empty( $logged_redemption ) ) {
            $points_redeemed = $logged_redemption['points'];
            $discount_amount = $logged_redemption['amount'];
            $discount_code = $logged_redemption['discount_code'];
        } else {
            // Get amount of discount
            $max_discount = XT_Woo_Points_Rewards_Manager::get_users_points_value( get_current_user_id() );
            $discount_amount = $this->get_discount_from_code( $discount_code, $max_discount );
            $points_redeemed = XT_Woo_Points_Rewards_Manager::calculate_points_for_discount( $discount_amount );
        }
        // only deduct points if they were redeemed for a discount
        $coupon_codes = $order->get_coupon_codes();
        if ( !in_array( $discount_code, $coupon_codes ) && in_array( $order->get_status(), $order_statuses ) ) {
            return;
        }
        // deduct points
        XT_Woo_Points_Rewards_Manager::decrease_points(
            $order_user_id,
            $points_redeemed,
            'order-redeem',
            array(
                'discount_code'   => $discount_code,
                'discount_amount' => $discount_amount,
            ),
            $order_id
        );
        $this->update_points_redeemed_for_order( $order_id, $points_redeemed );
        // add order note
        /* translators: 1: points earned 2: points label 3: discount amount */
        $order->add_order_note( sprintf(
            esc_html__( '%1$d %2$s redeemed for a %3$s discount.', 'xt-woo-points-rewards' ),
            $points_redeemed,
            $this->core->get_points_label( $points_redeemed ),
            wc_price( $discount_amount )
        ) );
    }

    /**
     * Get the discount amount associated with the given code.
     *
     * @since 1.0.022
     * @param string $discount_code The unique discount code generated for the applied discount.
     * @param float  $max_discount The maximum discount available.
     * @return float The discount amount for the coupon.
     */
    public function get_discount_from_code( $discount_code, $max_discount = null ) {
        $discount_amount = 0;
        if ( isset( WC()->cart->coupon_discount_amounts[$discount_code] ) ) {
            $discount_amount += WC()->cart->coupon_discount_amounts[$discount_code];
        }
        $tax_inclusive = 'inclusive' === get_option( 'xt_woopr_points_tax_application', ( wc_prices_include_tax() ? 'inclusive' : 'exclusive' ) );
        if ( $tax_inclusive && isset( WC()->cart->coupon_discount_tax_amounts[$discount_code] ) ) {
            $discount_amount += WC()->cart->coupon_discount_tax_amounts[$discount_code];
        }
        return ( $max_discount === null ? $discount_amount : min( $discount_amount, $max_discount ) );
    }

    /**
     * Handle an order that is cancelled or refunded by:
     *
     * 1) Removing any points earned for the order
     *
     * 2) Crediting points redeemed for a discount back to the customer's account if the order that they redeemed the points
     * for a discount on is cancelled or refunded
     *
     * @since 1.0
     * @param int $order_id the WC_Order ID
     */
    public function handle_cancelled_refunded_order( $order_id ) {
        $order = wc_get_order( $order_id );
        $order_id = $order->get_id();
        $order_user_id = $order->get_user_id();
        // bail for guest user
        if ( !$order_user_id ) {
            return;
        }
        // handle removing any points earned for the order
        $points_earned = $this->get_points_earned_for_order( $order_id );
        if ( $points_earned > 0 ) {
            // remove points
            XT_Woo_Points_Rewards_Manager::decrease_points(
                $order_user_id,
                $points_earned,
                'order-cancelled',
                null,
                $order_id
            );
            // remove points from order
            $this->delete_points_earned_for_order( $order_id );
            // add order note
            /* translators: 1: points earned 2: points earned label */
            $order->add_order_note( sprintf( esc_html__( '%1$d %2$s removed.', 'xt-woo-points-rewards' ), $points_earned, $this->core->get_points_label( $points_earned ) ) );
        }
        // handle crediting points redeemed for a discount
        $points_redeemed = $this->get_points_redeemed_for_order( $order_id );
        if ( $points_redeemed > 0 ) {
            // credit points
            XT_Woo_Points_Rewards_Manager::increase_points(
                $order_user_id,
                $points_redeemed,
                'order-cancelled',
                null,
                $order_id
            );
            // remove points from order
            $this->delete_points_redeemed_for_order( $order_id );
            // add order note
            /* translators: 1: points redeemed 2: points redeemed label */
            $order->add_order_note( sprintf( esc_html__( '%1$d %2$s credited back to customer.', 'xt-woo-points-rewards' ), $points_redeemed, $this->core->get_points_label( $points_redeemed ) ) );
        }
    }

    /**
     * Handle an order that is cancelled or refunded by:
     *
     * 1) Removing any points earned for the order
     *
     * 2) Crediting points redeemed for a discount back to the customer's account if the order that they redeemed the points
     * for a discount on is cancelled or refunded
     *
     * @since 1.0
     * @param int $order_id  WC_Order ID
     * @param int $refund_id WC_Order_Refund ID
     */
    public function handle_partially_refunded_order( $order_id, $refund_id ) {
        $order = wc_get_order( $order_id );
        $order_user_id = $order->get_user_id();
        // Bail for guest user.
        if ( !$order_user_id ) {
            return;
        }
        // Handle removing any points earned for the order.
        $points_earned = $this->get_points_earned_for_order( $order_id );
        if ( $points_earned > 0 ) {
            $refund = new WC_Order_Refund($refund_id);
            $points_refunded = XT_Woo_Points_Rewards_Manager::calculate_points( $refund->get_amount() );
            $points_refunded = XT_Woo_Points_Rewards_Manager::round_the_points( $points_refunded );
            // Remove points.
            XT_Woo_Points_Rewards_Manager::decrease_points(
                $order_user_id,
                $points_refunded,
                'order-refunded',
                null,
                $order_id
            );
            // Add order note.
            /* translators: 1: points earned 2: points earned label */
            $order->add_order_note( sprintf( esc_html__( '%1$d %2$s removed.', 'xt-woo-points-rewards' ), $points_refunded, $this->core->get_points_label( $points_refunded ) ) );
        }
    }

    /**
     * Returns the number of exact points earned for an order
     * @param  int $order_id
     * @return int
     */
    public function get_points_earned_for_order( $order_id ) {
        $points = 0;
        $points_earned = get_post_meta( $order_id, '_xt_woopr_points_earned', true );
        if ( !empty( $points_earned ) ) {
            $points = $points_earned;
        }
        return $points;
    }

    /**
     * Update the number of points earned for an order
     * @param  int $order_id
     * @return bool
     */
    public function update_points_earned_for_order( $order_id, $points ) {
        return update_post_meta( $order_id, '_xt_woopr_points_earned', $points );
    }

    /**
     * Delete points earned for an order
     * @param  int $order_id
     * @return bool
     */
    public function delete_points_earned_for_order( $order_id ) {
        return delete_post_meta( $order_id, '_xt_woopr_points_earned' );
    }

    /**
     * Returns the number of exact points redeemed for an order
     * @param  int $order_id
     * @return int
     */
    public function get_points_redeemed_for_order( $order_id ) {
        $points = 0;
        $points_redeemed = get_post_meta( $order_id, '_xt_woopr_points_redeemed', true );
        if ( !empty( $points_redeemed ) ) {
            $points = $points_redeemed;
        }
        return $points;
    }

    /**
     * Update the number of points redeemed for an order
     * @param  int $order_id
     * @return bool
     */
    public function update_points_redeemed_for_order( $order_id, $points ) {
        return update_post_meta( $order_id, '_xt_woopr_points_redeemed', $points );
    }

    /**
     * Delete points redeemed for an order
     * @param  int $order_id
     * @return bool
     */
    public function delete_points_redeemed_for_order( $order_id ) {
        return delete_post_meta( $order_id, '_xt_woopr_points_redeemed' );
    }

    /**
     * Returns the number of exact points redeemed for an order
     * @param  int $order_id
     * @return int
     */
    public function get_logged_redemption_for_order( $order_id ) {
        return get_post_meta( $order_id, '_xt_woopr_points_logged_redemption', true );
    }

    /**
     * Delete points redeemed for an order
     * @param  int $order_id
     * @return bool
     */
    public function delete_logged_redemption_for_order( $order_id ) {
        return delete_post_meta( $order_id, '_xt_woopr_points_logged_redemption' );
    }

    /**
     * Exclude any metadata from being passed on to subscription renewal orders.
     *
     * @since 1.6.40
     * @param string $meta_query Query string for copying meta data.
     * @return string The modified query.
     */
    public function exclude_meta_from_renewal_order( $meta_query ) {
        $meta_query .= " AND `meta_key` NOT IN ( '_xt_woopr_points_earned', '_xt_woopr_points_logged_redemption', '_xt_woopr_points_redeemed' )";
        return $meta_query;
    }

}

// end \XT_Woo_Points_Rewards_Order class