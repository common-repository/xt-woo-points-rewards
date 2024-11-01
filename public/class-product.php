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
 * Product class
 *
 * Handle messages for the single product page, and calculations for how many points are earned for a product purchase,
 * along with the discount available for a specific product
 *
 * @since 1.0
 */
class XT_Woo_Points_Rewards_Product {
    /**
     * Core class reference.
     *
     * @since    1.0.0
     * @access   private
     * @var      XT_Woo_Points_Rewards    $core    Core Class
     */
    private $core;

    /**
     * Add product-related hooks / filters
     *
     * @param XT_Woo_Points_Rewards $core
     * @since 1.0
     */
    public function __construct( $core ) {
        $this->core = $core;
        if ( !$core->enabled() ) {
            return $this;
        }
        // add single product message immediately after product excerpt
        add_action( 'woocommerce_before_add_to_cart_form', array($this, 'render_product_message'), 15 );
        // add variation message before the price is displayed
        add_action( 'woocommerce_before_single_variation', array($this, 'add_variation_message_to_product_summary') );
        // add variation message before the price is displayed on variation found
        add_filter(
            'woocommerce_available_variation',
            array($this, 'render_available_variation_message'),
            999,
            3
        );
        // delete transients
        add_action( 'woocommerce_delete_product_transients', array($this, 'delete_transients') );
        add_filter( 'woocommerce_show_variation_price', '__return_true' );
    }

    /**
     * Add "Earn X Points when you purchase" message to the single product page for simple products
     *
     * @since 1.0
     */
    public function render_product_message() {
        global $product;
        if ( !is_product() && method_exists( $product, 'get_available_variations' ) ) {
            return;
        }
        if ( !$product->is_purchasable() ) {
            return;
        }
        $message = get_option( 'xt_woopr_single_product_message' );
        $points_earned = self::get_points_earned_for_product_purchase( $product );
        // bail if none available
        if ( !$message || !$points_earned ) {
            $message = '';
        } else {
            // Check to see if Dynamic Pricing is installed
            if ( class_exists( 'WC_Dynamic_Pricing' ) ) {
                // check to see if there are pricing rules for this product, if so, use the 'earn up to X points' message
                if ( get_post_meta( $product->get_id(), '_pricing_rules', true ) ) {
                    $message = $this->create_variation_message_to_product_summary( $points_earned );
                }
            }
            // replace message variables
            $message = $this->replace_message_variables( $message, $product );
        }
        echo apply_filters( 'xt_woopr_single_product_message', $message, $this );
    }

    /**
     * Add a message about the points to the product summary
     *
     * @since 1.0.06
     */
    public function add_variation_message_to_product_summary() {
        global $product;
        // make sure the product has variations (otherwise it's probably a simple product)
        if ( $product && get_queried_object_id() === $product->get_id() && method_exists( $product, 'get_available_variations' ) ) {
            // get variations
            $variations = $product->get_available_variations();
            // find the variation with the most points
            $points = $this->get_highest_points_variation( $variations, $product->get_id() );
            $message = '';
            // if we have a points value let's create a message; other wise don't print anything
            if ( $points ) {
                $message = $this->create_variation_message_to_product_summary( $points );
            }
            echo wp_kses_post( $message );
        }
    }

    /**
     * Get the variation with the highest points and return the points value
     *
     * @since 1.0.6
     */
    public function get_highest_points_variation( $variations, $product_id ) {
        // get transient name
        $transient_name = $this->transient_highest_point_variation( $product_id );
        // see if we already have this data saved
        $points = get_transient( $transient_name );
        // if we don't have anything saved we'll have to figure it out
        if ( false === $points ) {
            // find the variation with the most points
            $highest = array(
                'key'    => 0,
                'points' => 0,
            );
            foreach ( $variations as $key => $variation ) {
                // get points
                $points = self::get_points_earned_for_product_purchase( $variation['variation_id'] );
                // if this is the highest points value save it
                if ( $points > $highest['points'] ) {
                    $highest = array(
                        'key'    => $key,
                        'points' => $points,
                    );
                }
            }
            $points = $highest['points'];
            // save this for future use
            set_transient( $transient_name, $points, YEAR_IN_SECONDS );
        }
        return $points;
    }

    /**
     * Create the "Earn up to X" message
     *
     * @since 1.0.6
     */
    public function create_variation_message_to_product_summary( $points ) {
        $message = get_option( 'xt_woopr_variable_product_message', '' );
        if ( !empty( $message ) ) {
            $points_value = XT_Woo_Points_Rewards_Manager::calculate_points_value( $points );
            // replace placeholders inside settings values
            $message = str_replace( '{points}', number_format_i18n( $points ), $message );
            $message = str_replace( '{points_value}', wc_price( $points_value ), $message );
            $message = str_replace( '{points_label}', $this->core->get_points_label( $points ), $message );
            return $this->core->frontend()->get_message_html( $message );
        }
        return '';
    }

    /**
     * Add "Earn X Points when you purchase" message to the single product page
     * for variable products
     *
     * @param array      $data
     * @param WC_Product $product
     * @param WC_Product $variation
     *
     * @return array
     */
    public function render_available_variation_message( $data, $product, $variation ) {
        if ( !$product->is_purchasable() ) {
            return $data;
        }
        $message = get_option( 'xt_woopr_single_product_message' );
        $points_earned = self::get_points_earned_for_product_purchase( $variation );
        // bail if none available
        if ( !$message || !$points_earned ) {
            return $data;
        }
        // replace message variables
        $message = $this->replace_message_variables( $message, $variation );
        $points_earned_text = $this->get_product_earned_points_text( $variation, $points_earned );
        $data['variation_description'] = $message . ' ' . $data['variation_description'];
        $data['points_earned'] = $points_earned;
        $data['points_earned_text'] = $points_earned_text;
        return $data;
    }

    /**
     * Replace product page message variables :
     *
     * {points} - the points earned for purchasing the product
     * {points_value} - the monetary value of the points earned
     * {points_label} - the label used for points
     *
     * @since 1.0
     * @param string $message the message set in the admin settings
     * @param object $product the product
     * @return string the message with variables replaced
     */
    private function replace_message_variables( $message, $product ) {
        $product_is_variable = $product && method_exists( $product, 'get_variation_price' );
        // the min/max points earned for variable products can't be determined reliably, so the 'earn X points...' message
        // is not shown until a variation is selected, unless the prices for the variations are all the same
        // in which case, treat it like a simple product and show the message
        if ( $product_is_variable && $product->get_variation_price( 'min' ) != $product->get_variation_price( 'max' ) ) {
            return '';
        }
        $points_earned = self::get_points_earned_for_product_purchase( $product );
        $points_value = XT_Woo_Points_Rewards_Manager::calculate_points_value( $points_earned );
        // points earned
        $message = str_replace( '{points}', number_format_i18n( $points_earned ), $message );
        // points value
        $message = str_replace( '{points_value}', wc_price( $points_value ), $message );
        // points label
        $message = str_replace( '{points_label}', $this->core->get_points_label( $points_earned ), $message );
        return $this->core->frontend()->get_message_html( $message );
    }

    /**
     * Return the points earned when purchasing a product. If points are set at both the product and category level,
     * the product points are used. If points are not set at the product or category level, the points are calculated
     * using the default points per currency and the price of the product
     *
     * @since 1.0
     * @param object $product the product to get the points earned for
     * @return int the points earned
     */
    public static function get_points_earned_for_product_purchase(
        $product,
        $order = null,
        $context = 'view',
        $round = true
    ) {
        $points = 0;
        // if we don't have a product object let's try to make one (hopefully they gave us the ID)
        if ( !is_object( $product ) ) {
            $product = wc_get_product( $product );
        }
        // otherwise, show the default points set for the price of the product
        if ( !isset( $points ) || !is_numeric( $points ) ) {
            $points = XT_Woo_Points_Rewards_Manager::calculate_points( $product->get_price( $context ) );
        }
        if ( $round ) {
            $points = XT_Woo_Points_Rewards_Manager::round_the_points( $points );
        }
        return $points;
    }

    /**
     * Return the points earned at the product level if set. If a percentage multiplier is set (e.g. 200%), the points are
     * calculated based on the price of the product then multiplied by the percentage
     *
     * @since 1.0
     * @param object $product the product to get the points earned for
     * @return int the points earned
     */
    public static function get_product_points( $product, $order = null ) {
        $points = false;
        return $points;
    }

    /**
     * Return the points earned at the category level if set. If a percentage multiplier is set (e.g. 200%), the points are
     * calculated based on the price of the product then multiplied by the percentage
     *
     * @since 1.0
     * @param object $product the product to get the points earned for
     * @return int the points earned
     */
    public static function get_category_points( $product, $order = null ) {
        $category_points = null;
        return $category_points;
    }

    /**
     * Calculate the points earned when a product or category is set to a percentage. This modifies the default points
     * earned based on the global "Earn Points Conversion Rate" setting and products price by the given $percentage.
     * e.g. a 200% multiplier will change 5 points to 10.
     *
     * @since 1.0
     * @param string $percentage the percentage to multiply the default points earned by
     * @param object $product the product to get the points earned for
     * @return int the points earned after adjusting for the multiplier
     */
    private static function calculate_points_multiplier( $percentage, $product ) {
        $percentage = str_replace( '%', '', $percentage ) / 100;
        return $percentage * XT_Woo_Points_Rewards_Manager::calculate_points( $product->get_price() );
    }

    /**
     * Return the maximum discount available for redeeming points. If a max discount is set at both the product and
     * category level, the product max discount is used. A global max discount can be set which is used as a fallback if
     * no other max discounts are set
     *
     * @since 1.0
     * @param object $product the product to get the maximum discount for
     * @return float|string the maximum discount or an empty string which means a maximum discount is not set for the given product
     */
    public static function get_maximum_points_discount_for_product( $product ) {
        if ( !is_object( $product ) ) {
            $product = wc_get_product( $product );
        }
        // otherwise, there is no maximum discount set
        return '';
    }

    /**
     * Return the maximum point discount at the product level if set. If a percentage multiplier is set (e.g. 35%),
     * the maximum discount is equal to the product's price times the percentage
     *
     * @since 1.0
     * @param object $product the product to get the maximum discount for
     * @return float|string the maximum discount
     */
    private static function get_product_max_discount( $product ) {
        $max_discount = null;
        return $max_discount;
    }

    /**
     * Return the maximum points discount at the category level if set. If a percentage multiplier is set (e.g. 35%),
     * the maximum discount is equal to the product's price times the percentage
     *
     * @since 1.0
     * @param object $product the product to get the maximum discount for
     * @return float|string the maximum discount
     */
    private static function get_category_max_discount( $product ) {
        $category_max_discount = null;
        return $category_max_discount;
    }

    /**
     * Calculate the maximum points discount when it's set to a percentage by multiplying the percentage times the product's
     * price
     *
     * @since 1.0
     * @param string $percentage the percentage to multiply the price by
     * @param object $product the product to get the maximum discount for
     * @return float the maximum discount after adjusting for the percentage
     */
    private static function calculate_discount_modifier( $percentage, $product ) {
        $percentage = str_replace( '%', '', $percentage ) / 100;
        return $percentage * $product->get_price();
    }

    /**
     * Get product earned points text
     *
     * @param $product
     * @param $points
     *
     * @return mixed|void
     */
    public function get_product_earned_points_text( $product, $points ) {
        $points_label = $this->core->get_points_label( $points );
        if ( is_object( $product ) && method_exists( $product, 'get_variation_price' ) ) {
            $points_text_template = esc_html__( 'Earn up to %d %s!', 'xt-woo-points-rewards' );
        } else {
            $points_text_template = esc_html__( 'Earn %d %s!', 'xt-woo-points-rewards' );
        }
        $points_text = sprintf( $points_text_template, $points, $points_label );
        return apply_filters(
            'xt_woopr_points_badge_text',
            $points_text,
            $points,
            $points_label,
            $product
        );
    }

    /**
     * Get highest point variation transient name
     *
     * @since 1.0.06
     */
    public function transient_highest_point_variation( $product_id ) {
        return 'xt_woopr_highest_point_variation_' . $product_id;
    }

    /**
     * Get lowest point variation transient name
     *
     * @since 1.0.01
     */
    public function transient_lowest_point_variation( $product_id ) {
        return 'xt_woopr_lowest_point_variation_' . $product_id;
    }

    /**
     * Delete transients
     *
     * @since 1.0.06
     */
    public function delete_transients( $product_id ) {
        delete_transient( $this->transient_highest_point_variation( $product_id ) );
        delete_transient( $this->transient_lowest_point_variation( $product_id ) );
    }

    /**
     * Check if order is renewal in case Subscriptions is enabled.
     *
     * @param WC_Order $order
     *
     * @return bool
     */
    protected static function is_order_renewal( $order ) {
        if ( !$order instanceof WC_Order || !function_exists( 'wcs_order_contains_resubscribe' ) || !function_exists( 'wcs_order_contains_renewal' ) ) {
            return false;
        }
        if ( !wcs_order_contains_resubscribe( $order ) && !wcs_order_contains_renewal( $order ) ) {
            return false;
        }
        return true;
    }

}

// end \XT_Woo_Points_Rewards_Product class