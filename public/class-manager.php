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
 * # Manager Class
 *
 * ## Points Increase/Reduce Algorithm
 *
 * For simplicity we reduce points from the oldest records while increasing
 * points in a brand new record.  This means that some minor gaming of the
 * system could be possible if points expiration were supported: for instance a
 * customer with soon-to-expire points could place a new order to earn points,
 * then cancel it to cycle out their old points, thus artifically increasing the
 * life of their points.  Trying to account for this would add a significant
 * amount of complexity (maybe) and in any event expiring points aren't supported
 * for version 1.0 and so this algorithm could be revisited when/if that feature
 * is implemented.
 *
 * ## Potential User Points Table Cleanup
 *
 * Technically, as soon as a user_points record reaches a balance of zero it
 * can be removed from this table as it no longer has any real value.  The log
 * record will still exist, and granted it will point to a missing record, but
 * this would help the user_points table from growing at the nearly the same
 * speed as the log table.
 *
 * ## User Points Query Implementation
 *
 * For better or for worse I decided to make use of the very limited
 * WP_User_Query class to pull the user points records.  This allowed me on the
 * one hand to write a very efficient query to pull only the current page of
 * user records, rather than pulling all user records and sorting in memory.
 * The drawbacks: I had to do my best to ensure that all customers have the
 * xt_woopr_user_points meta (initializing to 0), and we're quite limited to the
 * amount of searching possible (currently search is disabled, though it seems
 * like it would be nice search over billing email or user display name or
 * something).
 *
 * @since 1.0
 */
class XT_Woo_Points_Rewards_Manager {
    /** @var int records the number of users found during the get_user_points() method */
    public static $found_users;

    public static $disable_emails = false;

    public static function get_event_types( $settingDescriptions = false ) {
        if ( $settingDescriptions ) {
            $event_types = array(
                'admin-reset'      => __( 'Points reset by "admin"', 'xt-woo-points-rewards' ),
                'admin-adjustment' => __( 'Points adjusted by "admin"', 'xt-woo-points-rewards' ),
                'order-placed'     => __( 'Points earned for purchase', 'xt-woo-points-rewards' ),
                'order-cancelled'  => __( 'Points adjusted for cancelled order', 'xt-woo-points-rewards' ),
                'order-refunded'   => __( 'Points adjusted for an order refund', 'xt-woo-points-rewards' ),
                'order-redeem'     => __( 'Points redeemed towards purchase', 'xt-woo-points-rewards' ),
                'expire'           => __( 'Points expired', 'xt-woo-points-rewards' ),
            );
        } else {
            $event_types = array(
                'admin-reset'      => __( '%s reset by "admin"', 'xt-woo-points-rewards' ),
                'admin-adjustment' => __( '%s adjusted by "admin"', 'xt-woo-points-rewards' ),
                'order-placed'     => __( '%s earned for purchase', 'xt-woo-points-rewards' ),
                'order-cancelled'  => __( '%s adjusted for cancelled order', 'xt-woo-points-rewards' ),
                'order-refunded'   => __( '%s adjusted for an order refund', 'xt-woo-points-rewards' ),
                'order-redeem'     => __( '%s redeemed towards purchase', 'xt-woo-points-rewards' ),
                'expire'           => __( '%s expired', 'xt-woo-points-rewards' ),
            );
        }
        return $event_types;
    }

    /**
     * Returns the description to display for the given event type
     *
     * @param string $event_type the event type
     * @param object $event optional event log object
     * @return string the description for $event_type
     * @since 1.0
     */
    public static function event_type_description( $event_type, $event = null ) {
        $event_description = '';
        $points_label = xt_woo_points_rewards()->get_points_label( ( $event ? $event->points : null ) );
        $event_types = self::get_event_types();
        if ( !isset( $event_types[$event_type] ) ) {
            return '';
        }
        switch ( $event_type ) {
            case 'admin-reset':
            case 'admin-adjustment':
            case 'order-placed':
            case 'order-cancelled':
            case 'order-refunded':
            case 'order-redeem':
            case 'expire':
                $event_description = sprintf( $event_types[$event_type], $points_label );
                break;
        }
        if ( $event ) {
            if ( !empty( $event->order_id ) ) {
                $order = wc_get_order( $event->order_id );
                if ( !empty( $order ) ) {
                    $order_link = ( is_admin() ? $order->get_edit_order_url() : $order->get_view_order_url() );
                    $order_id = sprintf( esc_html__( 'Order %s', 'xt-woo-points-rewards' ), '#' . $event->order_id );
                    $event_description .= ': ' . sprintf( '<a target="_blank" href="%s">%s</a>', $order_link, $order_id );
                }
            }
        }
        // allow other plugins to define their own event types/descriptions
        return apply_filters(
            'xt_woopr_event_description',
            $event_description,
            $event_type,
            $event
        );
    }

    /**
     * Sets the points balance for the user identified by $user_id
     *
     * @param int $user_id user identifier
     * @param int $points_balance the new points balance
     * @param string $event_type the event type slug
     * @return boolean true if successfully updated, false otherwise
     * @since 1.0
     */
    public static function set_points_balance( $user_id, $points_balance, $event_type ) {
        $user = get_userdata( $user_id );
        if ( false === $user ) {
            return false;
        }
        $new_balance = (int) apply_filters(
            'xt_woopr_set_points_balance',
            $points_balance,
            $user_id,
            $event_type
        );
        $current_balance = self::get_users_points( $user_id );
        if ( $new_balance === $current_balance ) {
            // Balance is already correct.
            return false;
        }
        if ( $new_balance > $current_balance || $current_balance === 0 ) {
            // By calling increase_points() we are inserting the new points on a new database row so the expiry date for
            // the new points will be based on today's date.
            $points_change = self::increase_points( $user_id, $new_balance - $current_balance, $event_type );
        } else {
            $points_change = self::decrease_points( $user_id, $current_balance - $new_balance, $event_type );
        }
        if ( $points_change ) {
            do_action( 'xt_woopr_after_set_points_balance', $user_id, $points_balance );
        }
        return $points_change;
    }

    public static function disable_emails( $disable = true ) {
        self::$disable_emails = $disable;
    }

    /**
     * Adds points to the balance for the user identified by $user_id
     *
     * @param int $user_id the user identifier
     * @param int $points the points to add
     * @param string $event_type the type of event responsible
     * @param mixed $data optional arbitrary data to associate with the log for this action
     * @param int $order_id optional order identifier, if this action is associated with a particular order
     * @return boolean true if the points are successfully added to the user's balance
     * @since 1.0
     */
    public static function increase_points(
        $user_id,
        $points,
        $event_type,
        $data = null,
        $order_id = null
    ) {
        global $wpdb;
        // ensure the user exists
        $user = get_userdata( $user_id );
        if ( false === $user ) {
            return false;
        }
        $points = apply_filters(
            'xt_woopr_increase_points',
            $points,
            $user_id,
            $event_type,
            $data,
            $order_id
        );
        $_data = array(
            'user_id'        => $user_id,
            'points'         => $points,
            'points_balance' => $points,
            'date'           => current_time( 'mysql', 1 ),
        );
        $format = array(
            '%d',
            '%d',
            '%d',
            '%s'
        );
        if ( $order_id ) {
            $_data['order_id'] = $order_id;
            $format[] = '%d';
        }
        // create the new user points record
        $success = $wpdb->insert( xt_woo_points_rewards()->user_points_db_tablename, $_data, $format );
        // failed to insert the user points record
        if ( 1 != $success ) {
            return false;
        }
        // required log parameters
        $args = array(
            'user_id'        => $user_id,
            'points'         => $points,
            'event_type'     => $event_type,
            'user_points_id' => $wpdb->insert_id,
        );
        // optional associated order
        if ( $order_id ) {
            $args['order_id'] = $order_id;
        }
        // optional associated data
        if ( $data ) {
            $args['data'] = $data;
        }
        // log the event
        XT_Woo_Points_Rewards_Points_Log::add_log_entry( $args, self::$disable_emails );
        do_action(
            'xt_woopr_after_increase_points',
            $user_id,
            $points,
            $event_type,
            $data,
            $order_id
        );
        // success
        return true;
    }

    /**
     * Reduces the points balance for the user identified by $user_id
     *
     * @param int $user_id the user identifier
     * @param int $points the points to reduce, ie 75
     * @param string $event_type the type of event responsible
     * @param mixed $data optional arbitrary data to associate with the log for this action
     * @param int $order_id optional order identifier, if this action is associated with a particular order
     * @return boolean true if the points are successfully reduced from the user's balance
     * @since 1.0
     */
    public static function decrease_points(
        $user_id,
        $points,
        $event_type,
        $data = null,
        $order_id = null
    ) {
        global $wpdb;
        // ensure the user exists
        $user = get_userdata( $user_id );
        if ( false === $user ) {
            return false;
        }
        $points = apply_filters(
            'xt_woopr_decrease_points',
            $points,
            $user_id,
            $event_type,
            $data,
            $order_id
        );
        // get any existing points records
        $query = "SELECT * FROM " . xt_woo_points_rewards()->user_points_db_tablename . " WHERE user_id = %d and points_balance != 0 ORDER BY date ASC";
        $user_points = $wpdb->get_results( $wpdb->prepare( $query, $user_id ) );
        // no non-zero records, so create a new one
        if ( empty( $user_points ) ) {
            $_data = array(
                'user_id'        => $user_id,
                'points'         => -$points,
                'points_balance' => -$points,
                'date'           => current_time( 'mysql', 1 ),
            );
            $format = array(
                '%d',
                '%d',
                '%d',
                '%s'
            );
            if ( $order_id ) {
                $_data['order_id'] = $order_id;
                $format[] = '%d';
            }
            // create the negative-balance user points record
            $wpdb->insert( xt_woo_points_rewards()->user_points_db_tablename, $_data, $format );
        } elseif ( count( $user_points ) > 0 ) {
            // existing non-zero points records
            $points_difference = -$points;
            // the goal is to get each existing record as close to zero as possible, oldest to newest
            foreach ( $user_points as $index => &$_points ) {
                if ( $_points->points_balance > 0 && $points_difference < 0 ) {
                    $_points->points_balance += $points_difference;
                    if ( $_points->points_balance >= 0 || count( $user_points ) - 1 == $index ) {
                        // used up all of points_difference, or reached the newest user points record which therefore receives the remaining balance
                        $points_difference = 0;
                        break;
                    } else {
                        // still have more points balance to distribute
                        $points_difference = $_points->points_balance;
                        $_points->points_balance = 0;
                    }
                } elseif ( count( $user_points ) - 1 == $index && 0 != $points_difference ) {
                    // if we made it here, assign all remaining points to the final record and we're done
                    $_points->points_balance += $points_difference;
                    $points_difference = 0;
                }
            }
            // update any affected rows
            for ($i = 0; $i <= $index; $i++) {
                $wpdb->update(
                    xt_woo_points_rewards()->user_points_db_tablename,
                    array(
                        'points_balance' => $user_points[$i]->points_balance,
                    ),
                    array(
                        'id' => $user_points[$i]->id,
                    ),
                    array('%d'),
                    array('%d')
                );
            }
        }
        // log the points change
        $args = array(
            'user_id'    => $user_id,
            'points'     => -$points,
            'event_type' => $event_type,
        );
        // optional associated order
        if ( $order_id ) {
            $args['order_id'] = $order_id;
        }
        // optional associated data
        if ( $data ) {
            $args['data'] = $data;
        }
        // log the event
        XT_Woo_Points_Rewards_Points_Log::add_log_entry( $args, self::$disable_emails );
        do_action( 'xt_woopr_after_reduce_points', $user_id, self::get_users_points( $user_id ) );
        // always return true for now
        return true;
    }

    /**
     * Deletes the user points record associated with $user_id, but leaves the
     * points log records intact
     *
     * @param $user_id int the user id to delete all user points records for
     * @since 1.0
     */
    public static function delete_user_points( $user_id ) {
        global $wpdb;
        $wpdb->delete( xt_woo_points_rewards()->user_points_db_tablename, array(
            'user_id' => $user_id,
        ) );
    }

    /**
     * Returns the current points balance for the identified user
     *
     * @param int $user_id the user identifier
     * @return int the point balance for the user
     * @since 1.0
     */
    public static function get_users_points( $user_id = null, $force = false ) {
        $user_id = ( $user_id ? $user_id : get_current_user_id() );
        $cache_key = 'get_users_points' . $user_id;
        if ( $force ) {
            xt_woo_points_rewards()->cache()->delete( $cache_key );
        }
        $points_balance = xt_woo_points_rewards()->cache()->result( $cache_key, function () use($user_id) {
            global $wpdb;
            $points_balance = 0;
            $query = "SELECT * FROM " . xt_woo_points_rewards()->user_points_db_tablename . " WHERE user_id = %d AND points_balance != 0";
            $points = $wpdb->get_results( $wpdb->prepare( $query, $user_id ) );
            // total up the existing points balance
            foreach ( $points as $_points ) {
                $points_balance += $_points->points_balance;
            }
            return ( is_numeric( $points_balance ) ? $points_balance : null );
        } );
        return intval( apply_filters( 'xt_woopr_user_points_balance', $points_balance, $user_id ) );
    }

    public static function get_users_points_value( $user_id = null ) {
        $user_id = ( $user_id ?: get_current_user_id() );
        return self::calculate_points_value( self::get_users_points( $user_id ) );
    }

    /**
     * Returns all user points records
     *
     * @param $args array arguments for the user query
     * @return array of user_points objects with user_id and points_balance fields
     * @since 1.0
     */
    public static function get_all_users_points( $args ) {
        if ( !isset( $args['fields'] ) ) {
            $args['fields'] = 'ID';
        }
        // perform the user query, altering the orderby as needed when ordering by user points
        $wp_user_query = new WP_User_Query($args);
        // record the total result set (for pagination purposes)
        if ( isset( $args['count_total'] ) && $args['count_total'] ) {
            self::$found_users = $wp_user_query->get_total();
        }
        $results = array();
        // build the expected user points records
        foreach ( $wp_user_query->get_results() as $user_id ) {
            $result = new stdClass();
            $result->user_id = $user_id;
            $result->points_balance = self::get_users_points( $user_id );
            $results[] = $result;
        }
        return $results;
    }

    /**
     * Returns the total user points records as found by the most recent call
     * to get_user_points()
     *
     * @return int the total user points records found
     * @since 1.0
     */
    public static function get_found_user_points() {
        return self::$found_users;
    }

    /**
     * Get points ratio info
     *
     * @return object Points Ratio
     * @since 1.0
     */
    public static function get_points_earning_ratio() {
        list( $points, $monetary_value ) = explode( ':', get_option( 'xt_woopr_earn_points_ratio', '' ) );
        $points = ( !empty( $points ) ? $points : 0 );
        $monetary_value = ( !empty( $monetary_value ) ? $monetary_value : 0 );
        if ( empty( $points ) || empty( $monetary_value ) ) {
            return null;
        }
        return (object) array(
            'points'         => $points,
            'monetary_value' => $monetary_value,
        );
    }

    /**
     * Get points conversion info
     *
     * @return object Points Ratio
     * @since 1.0
     */
    public static function get_points_redemption_ratio() {
        list( $points, $monetary_value ) = explode( ':', get_option( 'xt_woopr_redeem_points_ratio', '' ) );
        $points = ( !empty( $points ) ? $points : 0 );
        $monetary_value = ( !empty( $monetary_value ) ? $monetary_value : 0 );
        if ( empty( $points ) || empty( $monetary_value ) ) {
            return null;
        }
        return (object) array(
            'points'         => $points,
            'monetary_value' => $monetary_value,
        );
    }

    /**
     * Calculate the points earned for a purchase based on the given amount. This uses the ratio set in the admin settings
     * (e.g. earn 10 points for every $1 spent).
     *
     * @param string|float $amount The amount to calculate the points earned for.
     *
     * @return int The points earned.
     * @since 1.0
     */
    public static function calculate_points( $amount ) {
        // Ratio string "a:a" to array "[a,a]".
        $ratio = self::get_points_earning_ratio();
        if ( empty( $ratio ) ) {
            return 0;
        }
        if ( !$ratio->points || !$ratio->monetary_value || !$amount ) {
            return 0;
        }
        $amount = apply_filters( 'xt_woopr_filter_amount', $amount );
        return $amount * ($ratio->points / $ratio->monetary_value);
    }

    /**
     * Calculate the value of the points earned for a purchase based on the given amount. This uses the ratio set in the
     * admin settings (e.g. For every 100 points get a $1 discount). The points value is formatted to 2 decimal places.
     *
     * @param int $amount the amount of points to calculate the monetary value for
     * @return float the monetary value of the points
     * @since 1.0
     */
    public static function calculate_points_value( $amount ) {
        $amount = apply_filters( 'xt_woopr_filter_amount', $amount );
        $ratio = self::get_points_redemption_ratio();
        return number_format(
            $amount * ($ratio->monetary_value / $ratio->points),
            2,
            '.',
            ''
        );
    }

    /**
     * Calculate the amount of points required to redeem for a given discount amount. This uses the ratio set in the
     * admin settings (e.g. For every 100 points get a $1 discount). The points value ceil up to the nearest whole
     * integer, so $1.01 discount requires 2 points
     *
     * @param float $discount_amount the discount amount to calculate the amount of points required to redeem
     * @return int
     * @since 1.0
     */
    public static function calculate_points_for_discount( $discount_amount ) {
        $discount_amount = apply_filters( 'xt_woopr_filter_amount', $discount_amount );
        list( $points, $monetary_value ) = explode( ':', get_option( 'xt_woopr_redeem_points_ratio', '' ) );
        $required_points = $discount_amount * ($points / $monetary_value);
        // to prevent any rounding errors we need to round off any fractions
        // ex. 408.000000001 should require 408 points but 408.50 should require 409
        $required_points = floor( $required_points * 100 );
        $required_points = $required_points / 100;
        return ceil( $required_points );
    }

    /**
     * Calculate how much coupons affect points
     *
     * @param int $points That will be modified by coupons.
     * @param array $coupons Array of coupons that can affect the points.
     *
     * @return int $points Points after coupons modification.
     * @since 1.0.016
     */
    public static function calculate_points_modification_from_coupons( $points, $coupons ) {
        $disable_points_earned = false;
        if ( !empty( $coupons ) ) {
            $points_modifier = 0;
            // Get the maximum points modifier if there are multiple coupons applied, each with their own modifier.
            foreach ( $coupons as $coupon_code ) {
                $coupon = new WC_Coupon($coupon_code);
                $coupon_id = $coupon->get_id();
                $points_modifier = get_post_meta( $coupon_id, '_xt_woopr_points_modifier', true );
                if ( !empty( $points_modifier ) ) {
                    // User can use % in the setting field so we need to remove it here.
                    $coupon_points_modifier = str_replace( '%', '', $points_modifier );
                    $coupon_points_modifier = floatval( $coupon_points_modifier );
                    // Find the biggest one.
                    if ( $coupon_points_modifier > $points_modifier ) {
                        $points_modifier = $coupon_points_modifier;
                    }
                    // If it is 0%, then disable points earned for all coupons.
                    if ( 0.0 === $coupon_points_modifier ) {
                        $disable_points_earned = true;
                    }
                }
            }
            if ( $points_modifier > 0 ) {
                $points = $points * ($points_modifier / 100);
            }
        }
        if ( $disable_points_earned ) {
            $points = 0;
        }
        return $points;
    }

    /**
     * Round the points using merchant selected method.
     *
     * @param float $points That will be rounded.
     *
     * @return int $points Points after rounding.
     * @since 1.0.016
     */
    public static function round_the_points( $points ) {
        $rounding_option = get_option( 'xt_woopr_earn_points_rounding' );
        switch ( $rounding_option ) {
            case 'ceil':
                $points_earned = ceil( $points );
                break;
            case 'floor':
                $points_earned = floor( $points );
                break;
            default:
                $points_earned = round( $points );
                break;
        }
        return $points_earned;
    }

}

// end \XT_Woo_Points_Rewards_Manager class