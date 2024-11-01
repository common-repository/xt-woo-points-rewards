<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://xplodedthemes.com
 * @since      1.0.0
 *
 * @package    XT_Woo_Points_Rewards
 * @subpackage XT_Woo_Points_Rewards_Product_Admin
 */
/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    XT_Woo_Points_Rewards
 * @subpackage XT_Woo_Points_Rewards_Product_Admin
 * @author     XplodedThemes 
 */
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * Product Admin class
 *
 * Load / saves product admin settings
 *
 * @since 1.0
 */
class XT_Woo_Points_Rewards_Admin_Product {
    /**
     * Core class reference.
     *
     * @since    1.0.0
     * @access   private
     * @var      XT_Woo_Points_Rewards    core    Core Class
     */
    protected $core;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    XT_Woo_Points_Rewards    $core    Plugin core class.
     */
    public function __construct( $core ) {
        $this->core = $core;
        /** Simple Product hooks */
        // add 'Points Earned' field to simple product general tab
        add_action( 'woocommerce_product_options_general_product_data', array($this, 'render_simple_product_fields') );
        /** Variable Product hooks */
        // add 'Points Earned' to variable products under the 'Variations' tab after the shipping class dropdown
        add_action(
            'woocommerce_product_after_variable_attributes',
            array($this, 'render_variable_product_fields'),
            15,
            3
        );
        /** Product category hooks */
        // add 'Points Earned' field to the add product category page
        add_action( 'product_cat_add_form_fields', array($this, 'render_product_category_fields') );
        // add 'Points Earned' field to view/edit product category
        add_action( 'product_cat_edit_form_fields', array($this, 'render_edit_product_category_fields') );
    }

    /** Simple Product methods ******************************************************/
    /**
     * Render simple product points earned / maximum discount fields
     *
     * @since 1.0
     * @version 1.0.0
     */
    public function render_simple_product_fields() {
        $product = wc_get_product();
        if ( $product->get_type() !== 'simple' ) {
            return false;
        }
        $custom_attributes = ( !$this->core->access_manager()->can_use_premium_code__premium_only() ? array(
            'readonly' => true,
        ) : array() );
        echo '<div class="options_group">';
        echo xtfw_heading( $this->core->plugin_menu_name(), 'h4', array('xtfw-title-separator', 'show_if_simple') );
        $this->core->admin_messages()->premium_required( esc_html__( 'points related settings below', 'xt-woo-points-rewards' ), array('show_if_simple') );
        // points earned
        woocommerce_wp_text_input( array(
            'id'                => '_xt_woopr_points_earned',
            'wrapper_class'     => 'show_if_simple show_if_booking show_if_accommodation-booking',
            'class'             => 'short',
            'label'             => esc_html__( 'Points Earned', 'xt-woo-points-rewards' ),
            'description'       => esc_html__( 'This can be either a fixed number of points earned for purchasing this product, or a percentage which assigns points based on the price. For example, if you want to award points equal to double the normal rate, enter 200%.  This setting modifies the global Points Conversion Rate and overrides any category value.  Use 0 to assign no points for this product, and empty to use the global/category settings.', 'xt-woo-points-rewards' ),
            'desc_tip'          => true,
            'type'              => 'text',
            'custom_attributes' => $custom_attributes,
        ) );
        // maximum discount allowed on product
        woocommerce_wp_text_input( array(
            'id'                => '_xt_woopr_points_max_discount',
            'class'             => 'short',
            'wrapper_class'     => 'show_if_simple show_if_booking show_if_accommodation-booking',
            'label'             => esc_html__( 'Maximum Points Discount', 'xt-woo-points-rewards' ),
            'description'       => esc_html__( 'Enter either a fixed maximum discount amount or percentage which restricts the amount of points that can be redeemed for a discount based on the product price. For example, if you want to restrict the discount on this product to a maximum of 50%, enter 50%, or enter 5 to restrict the maximum discount to $5.  This setting overrides the global/category defaults, use 0 to disable point discounts for this product, and blank to use the global/category default.', 'xt-woo-points-rewards' ),
            'desc_tip'          => true,
            'type'              => 'text',
            'custom_attributes' => $custom_attributes,
        ) );
        if ( class_exists( 'WC_Subscriptions' ) ) {
            // Subscription renewal integration
            woocommerce_wp_text_input( array(
                'id'                => '_xt_woopr_points_renewal_points',
                'class'             => 'short',
                'wrapper_class'     => 'show_if_simple show_if_subscription',
                'label'             => esc_html__( 'Change Renewal Points', 'xt-woo-points-rewards' ),
                'description'       => esc_html__( 'For Subscription renewals with a different point value than the signup, enter either a fixed maximum discount amount or percentage which restricts the amount of points that can be redeemed for a discount based on the product price. For example, if you want to restrict the discount on this product to a maximum of 50%, enter 50%, or enter 5 to restrict the maximum discount to $5.  This setting overrides the global/category defaults, use 0 to disable point discounts for this product, and blank to use the global/category default.', 'xt-woo-points-rewards' ),
                'desc_tip'          => true,
                'type'              => 'text',
                'custom_attributes' => $custom_attributes,
            ) );
        }
        do_action( $this->core->plugin_short_prefix( 'after_product_fields' ) );
        echo '</div>';
    }

    /**
     * Save the simple product points earned / maximum discount fields
     *
     * @since 1.0
     */
    public function save_simple_product_fields( $post_id ) {
        if ( isset( $_POST['_xt_woopr_points_earned'] ) && '' !== $_POST['_xt_woopr_points_earned'] ) {
            update_post_meta( $post_id, '_xt_woopr_points_earned', sanitize_text_field( stripslashes( $_POST['_xt_woopr_points_earned'] ) ) );
        } else {
            delete_post_meta( $post_id, '_xt_woopr_points_earned' );
        }
        if ( isset( $_POST['_xt_woopr_points_max_discount'] ) && '' !== $_POST['_xt_woopr_points_max_discount'] ) {
            update_post_meta( $post_id, '_xt_woopr_points_max_discount', sanitize_text_field( stripslashes( $_POST['_xt_woopr_points_max_discount'] ) ) );
        } else {
            delete_post_meta( $post_id, '_xt_woopr_points_max_discount' );
        }
        if ( isset( $_POST['_xt_woopr_points_renewal_points'] ) && '' !== $_POST['_xt_woopr_points_renewal_points'] ) {
            update_post_meta( $post_id, '_xt_woopr_points_renewal_points', sanitize_text_field( stripslashes( $_POST['_xt_woopr_points_renewal_points'] ) ) );
        } else {
            delete_post_meta( $post_id, '_xt_woopr_points_renewal_points' );
        }
        if ( isset( $_POST['_xt_woopr_points_include_bundled_product_points'] ) ) {
            update_post_meta( $post_id, '_xt_woopr_points_include_bundled_product_points', sanitize_text_field( stripslashes( $_POST['_xt_woopr_points_include_bundled_product_points'] ) ) );
        } else {
            delete_post_meta( $post_id, '_xt_woopr_points_include_bundled_product_points' );
        }
    }

    /** Variable Product methods ******************************************************/
    /**
     * Add points earned / maximum discount to variable products under the 'Variations' tab after the shipping class dropdown
     *
     * @since 1.0
     */
    public function render_variable_product_fields( $loop, $variation_data, $variation ) {
        $points_earned = get_post_meta( $variation->ID, '_xt_woopr_points_earned', true );
        $max_discount = get_post_meta( $variation->ID, '_xt_woopr_points_max_discount', true );
        $renewal_points = get_post_meta( $variation->ID, '_xt_woopr_points_renewal_points', true );
        $points_earned_description = esc_html__( 'This can either be a fixed number of points earned for purchasing this variation, or a percentage which assigns points based on the price. For example, if you want to award points equal to double the the normal rate, enter 200%.  This setting modifies the global Points Conversion Rate and overrides any category value.  Use 0 to assign no points for this variation, and empty to use the global/category settings.', 'xt-woo-points-rewards' );
        $max_discount_description = esc_html__( 'Enter either a fixed maximum discount amount or percentage which restricts the amount of points that can be redeemed for a discount based on the product price. For example, if you want to restrict the discount on this product to a maximum of 50%, enter 50%, or enter 5 to restrict the maximum discount to $5.  This setting overrides the global/category defaults, use 0 to disable point discounts for this product, and blank to use the global/category default.', 'xt-woo-points-rewards' );
        $renewal_points_description = esc_html__( 'For Subscription renewals with a different point value than the signup, enter either a fixed maximum discount amount or percentage which restricts the amount of points that can be redeemed for a discount based on the product price. For example, if you want to restrict the discount on this product to a maximum of 50%, enter 50%, or enter 5 to restrict the maximum discount to $5.  This setting overrides the global/category defaults, use 0 to disable point discounts for this product, and blank to use the global/category default.', 'xt-woo-points-rewards' );
        $custom_attributes = ( !$this->core->access_manager()->can_use_premium_code__premium_only() ? 'readonly=readonly' : '' );
        ?>

        <div class="options_group">

            <?php 
        echo xtfw_heading( esc_html__( 'Rewards', 'xt-woo-points-rewards' ), 'h4', array('xtfw-title-separator') );
        $this->core->admin_messages()->premium_required( esc_html__( 'points related settings below', 'xt-woo-points-rewards' ) );
        ?>

            <p class="form-row form-row-first">
                <label>
                    <?php 
        esc_html_e( 'Points Earned', 'xt-woo-points-rewards' );
        ?>
                    <?php 
        echo wc_help_tip( esc_attr( $points_earned_description ) );
        ?>
                </label>
                <input type="text" <?php 
        echo esc_attr( $custom_attributes );
        ?> size="5" name="variable_points_earned[<?php 
        echo esc_attr( $loop );
        ?>]" value="<?php 
        echo esc_attr( $points_earned );
        ?>" step="any" min="0" placeholder="<?php 
        esc_html_e( 'Variation Points Earned', 'xt-woo-points-rewards' );
        ?>" />
            </p>

            <p class="form-row form-row-last">
                <label>
                    <?php 
        esc_html_e( 'Maximum Points Discount', 'xt-woo-points-rewards' );
        ?>
                    <?php 
        echo wc_help_tip( esc_attr( $max_discount_description ) );
        ?>
                </label>
                <input type="text" <?php 
        echo esc_attr( $custom_attributes );
        ?> size="5" name="variable_max_point_discount[<?php 
        echo esc_attr( $loop );
        ?>]" value="<?php 
        echo esc_attr( $max_discount );
        ?>" placeholder="<?php 
        esc_html_e( 'Variation Max Points Discount', 'xt-woo-points-rewards' );
        ?>" />
            </p>

            <?php 
        if ( class_exists( 'WC_Subscriptions' ) ) {
            ?>
                <p class="form-row form-row-full">
                    <label><?php 
            esc_html_e( 'Change Renewal Points', 'xt-woo-points-rewards' );
            ?>
                        <?php 
            echo wc_help_tip( esc_attr( $renewal_points_description ) );
            ?>
                    </label>
                    <input type="text" <?php 
            echo esc_attr( $custom_attributes );
            ?> size="5" name="variable_renewal_points[<?php 
            echo esc_attr( $loop );
            ?>]" value="<?php 
            echo esc_attr( $renewal_points );
            ?>" placeholder="<?php 
            esc_html_e( 'Variation Change Rewards Points', 'xt-woo-points-rewards' );
            ?>" />
                </p>
            <?php 
        }
        ?>

        </div>
        
		<?php 
        do_action( 'xt_woopr_after_variable_product_fields', $loop, $variation_data );
    }

    /**
     * Save the variable product points earned / maximum discount fields
     *
     * @since 1.0
     */
    public function save_variable_product_fields( $variation_id ) {
        // find the index for the given variation ID and save the associated points earned
        $index = array_search( $variation_id, $_POST['variable_post_id'] );
        if ( false !== $index ) {
            // points earned
            if ( isset( $_POST['variable_points_earned'] ) && '' !== $_POST['variable_points_earned'][$index] ) {
                update_post_meta( $variation_id, '_xt_woopr_points_earned', sanitize_text_field( stripslashes( $_POST['variable_points_earned'][$index] ) ) );
            } else {
                delete_post_meta( $variation_id, '_xt_woopr_points_earned' );
            }
            // maximum points discount
            if ( isset( $_POST['variable_max_point_discount'] ) && '' !== $_POST['variable_max_point_discount'][$index] ) {
                update_post_meta( $variation_id, '_xt_woopr_points_max_discount', sanitize_text_field( stripslashes( $_POST['variable_max_point_discount'][$index] ) ) );
            } else {
                delete_post_meta( $variation_id, '_xt_woopr_points_max_discount' );
            }
            // change points for renewal
            if ( isset( $_POST['variable_renewal_points'] ) && '' !== $_POST['variable_renewal_points'][$index] ) {
                update_post_meta( $variation_id, '_xt_woopr_points_renewal_points', sanitize_text_field( stripslashes( $_POST['variable_renewal_points'][$index] ) ) );
            } else {
                delete_post_meta( $variation_id, '_xt_woopr_points_renewal_points' );
            }
        }
    }

    /**
     * Renders the 'Points Earned' bulk edit action on the product admin Variations tab.
     * There is core JS code that automatically handles these bulk edits.
     *
     * @since 1.0
     */
    public function add_variable_product_bulk_edit_points_action() {
        echo '<option value="variable_points_earned">' . esc_html__( 'Points Earned', 'xt-woo-points-rewards' ) . '</option>';
        add_action( 'admin_print_footer_scripts', array($this, 'add_admin_bulk_action_script') );
    }

    public function add_admin_bulk_action_script() {
        ?>
		<script type="text/javascript">
			jQuery( document ).ready( function( $ ) {
				$( 'body' ).on( 'variable_points_earned', function( bulk_edit ) {
					var value = window.prompt( "<?php 
        echo esc_js( esc_html__( 'Enter a value', 'xt-woo-points-rewards' ) );
        ?>" );

					if ( value != null ) {
						$( ':input[name^="variable_points_earned"]' ).val( value ).change();
					}
				});
			});
		</script>
	<?php 
    }

    /** Product Bulk Edit methods ******************************************************/
    /**
     * Add a 'Points Earned' bulk edit field, this is displayed on the Products list page
     * when one or more products is selected, and the Edit Bulk Action is applied
     *
     * @since 1.0
     */
    public function add_points_field_bulk_edit() {
        ?>
			<div class="inline-edit-group">
				<label class="alignleft">
					<span class="title"><?php 
        esc_html_e( 'Points Earned', 'xt-woo-points-rewards' );
        ?></span>
						<span class="input-text-wrap">
							<select class="change_points_earned change_to" name="change_points_earned">
								<?php 
        $options = array(
            ''  => esc_html__( '— No Change —', 'xt-woo-points-rewards' ),
            '1' => esc_html__( 'Change to:', 'xt-woo-points-rewards' ),
            '2' => esc_html__( 'Increase by (fixed amount or %):', 'xt-woo-points-rewards' ),
            '3' => esc_html__( 'Decrease by (fixed amount or %):', 'xt-woo-points-rewards' ),
        );
        foreach ( $options as $key => $value ) {
            echo '<option value="' . esc_attr( $key ) . '">' . esc_html( $value ) . '</option>';
        }
        ?>
							</select>
						</span>
				</label>
				<label class="alignright">
					<input type="text" name="_xt_woopr_points_earned" class="text points_earned" placeholder="<?php 
        esc_html_e( 'Enter Points Earned', 'xt-woo-points-rewards' );
        ?>" value="" />
				</label>
			</div>
		<?php 
    }

    /**
     * Save the 'Points Earned' bulk edit field
     *
     * @since 1.0
     */
    public function save_points_field_bulk_edit( $product ) {
        if ( !empty( $_REQUEST['change_points_earned'] ) ) {
            $option_selected = absint( $_REQUEST['change_points_earned'] );
            $requested_points_earned_change = sanitize_text_field( stripslashes( $_REQUEST['_xt_woopr_points_earned'] ) );
            $current_points_earned = get_post_meta( $product->get_id(), '_xt_woopr_points_earned', true );
            $new_points_earned = $current_points_earned;
            switch ( $option_selected ) {
                // change 'Points Earned' to fixed amount
                case 1:
                    $new_points_earned = $requested_points_earned_change;
                    break;
                // increase 'Points Earned' by fixed amount/percentage
                case 2:
                    if ( false !== strpos( $requested_points_earned_change, '%' ) ) {
                        $percent = str_replace( '%', '', $requested_points_earned_change ) / 100;
                        $new_points_earned = $current_points_earned + $current_points_earned * $percent;
                    } else {
                        $new_points_earned = $current_points_earned + $requested_points_earned_change;
                    }
                    break;
                // decrease 'Points Earned' by fixed amount/percentage
                case 3:
                    if ( false !== strpos( $requested_points_earned_change, '%' ) ) {
                        $percent = str_replace( '%', '', $requested_points_earned_change ) / 100;
                        $new_points_earned = $current_points_earned - $current_points_earned * $percent;
                    } else {
                        $new_points_earned = $current_points_earned - $requested_points_earned_change;
                    }
                    break;
            }
            // update to new Points Earned if different than current Points Earned
            if ( is_numeric( $new_points_earned ) && $new_points_earned != $current_points_earned ) {
                update_post_meta( $product->get_id(), '_xt_woopr_points_earned', $new_points_earned );
            }
        }
        // End if().
    }

    /** Product Category methods ******************************************************/
    /**
     * Add the points earned / maximum discount fields to the add product category page
     *
     * @since 1.0
     */
    public function render_product_category_fields() {
        $this->get_product_category_fields_html();
    }

    /**
     * Add the points earned / maximum discount fields to the view/edit product category page
     *
     * @since 1.0
     * @param object $term the term object
     */
    public function render_edit_product_category_fields( $term ) {
        // get points earned / maximum points discount from product category meta
        $points_earned = $this->get_term_meta( $term->term_id, '_xt_woopr_points_earned', true );
        $max_discount = $this->get_term_meta( $term->term_id, '_xt_woopr_points_max_discount', true );
        $this->get_product_category_fields_html( $points_earned, $max_discount, true );
    }

    /**
     * Return the points field HTML for the product category page
     *
     * @since 1.0
     * @param string $points points earned for the category, if available
     * @param string $max_discount the maximum points discount for the category, if set
     */
    private function get_product_category_fields_html( $points = '', $max_discount = '', $edit = false ) {
        $points_earned_description = esc_html__( 'This can either be a fixed number of points earned for the purchase of any product that belongs to this category, or a percentage which assigns points based on the price of the product. For example, if you want to award points equal to double the normal rate, enter 200%.  This setting modifies the global Points Conversion Rate, but can be overridden by a product/variation. Use 0 to assign no points for products belonging to this category, and empty to use the global setting.', 'xt-woo-points-rewards' );
        $max_discount_description = esc_html__( 'Enter either a fixed maximum discount amount or percentage which restricts the amount of points that can be redeemed for a discount based on the price of the product in this category. For example, if you want to restrict the discount on this category to a maximum of 50%, enter 50%, or enter $5 to restrict the maximum discount to $5.  This setting overrides the global default, but can be overridden by a product/variation. Use 0 to disable point discounts for this product, and blank to use the global/category default.', 'xt-woo-points-rewards' );
        $renewal_points_description = esc_html__( 'For Subscription renewals with a different point value than the signup, enter either a fixed maximum discount amount or percentage which restricts the amount of points that can be redeemed for a discount based on the product price. For example, if you want to restrict the discount on this product to a maximum of 50%, enter 50%, or enter 5 to restrict the maximum discount to $5.  This setting overrides the global/category defaults, use 0 to disable point discounts for this product, and blank to use the global/category default.', 'xt-woo-points-rewards' );
        $custom_attributes = ( !$this->core->access_manager()->can_use_premium_code__premium_only() ? 'readonly=readonly' : '' );
        if ( $edit ) {
            $this->core->admin_messages()->premium_required_table_row( esc_html__( 'points related settings below', 'xt-woo-points-rewards' ) );
            ?>

			<tr class="form-field term-points-earned-wrap">
				<th scope="row" valign="top"><label><?php 
            esc_html_e( 'Points Earned', 'xt-woo-points-rewards' );
            ?></label></th>
				<td>
					<input type="text" <?php 
            echo esc_attr( $custom_attributes );
            ?> size="5" name="_xt_woopr_points_earned" value="<?php 
            echo esc_attr( $points );
            ?>" step="any" min="0" />
                    <?php 
            echo wc_help_tip( esc_attr( $points_earned_description ) );
            ?>
                </td>
			</tr>
			<tr class="form-field term-max-points-discount-wrap">
				<th scope="row" valign="top"><label><?php 
            esc_html_e( 'Maximum Points Discount', 'xt-woo-points-rewards' );
            ?></label></th>
				<td>
					<input type="text" <?php 
            echo esc_attr( $custom_attributes );
            ?> size="5" name="_xt_woopr_points_max_discount" value="<?php 
            echo esc_attr( $max_discount );
            ?>"  />
                    <?php 
            echo wc_help_tip( esc_attr( $max_discount_description ) );
            ?>
				</td>
			</tr>
            <?php 
            if ( class_exists( 'WC_Subscriptions' ) ) {
                ?>
                <tr class="form-field term-change-renewal-points-wrap">
                    <th scope="row" valign="top">
                        <label><?php 
                esc_html_e( 'Change Renewal Points', 'xt-woo-points-rewards' );
                ?></label></th>
                    <td>
                        <input type="text" <?php 
                echo esc_attr( $custom_attributes );
                ?> size="5" name="_xt_woopr_points_renewal_points" value="<?php 
                echo esc_attr( $max_discount );
                ?>"/>
                        <?php 
                echo wc_help_tip( esc_attr( $renewal_points_description ) );
                ?>
                    </td>
                </tr>
                <?php 
            }
        } else {
            $this->core->admin_messages()->premium_required( esc_html__( 'points related settings below', 'xt-woo-points-rewards' ) );
            ?>

		    <div class="form-field term-points-earned-wrap">
				<label><?php 
            esc_html_e( 'Points Earned', 'xt-woo-points-rewards' );
            ?></label>
                <input type="text" <?php 
            echo esc_attr( $custom_attributes );
            ?> size="5" name="_xt_woopr_points_earned" value="<?php 
            echo esc_attr( $points );
            ?>" step="any" min="0" />
                <?php 
            echo wc_help_tip( esc_attr( $points_earned_description ) );
            ?>
			</div>
			<div class="form-field term-max-points-discount-wrap">
				<label><?php 
            esc_html_e( 'Maximum Points Discount', 'xt-woo-points-rewards' );
            ?></label>
                <input type="text" <?php 
            echo esc_attr( $custom_attributes );
            ?> size="5" name="_xt_woopr_points_max_discount" value="<?php 
            echo esc_attr( $max_discount );
            ?>"  />
                <?php 
            echo wc_help_tip( esc_attr( $max_discount_description ) );
            ?>
			</div>
            <?php 
            if ( class_exists( 'WC_Subscriptions' ) ) {
                ?>
                <div class="form-field term-change-renewal-points-wrap">
                    <label><?php 
                esc_html_e( 'Change Renewal Points', 'xt-woo-points-rewards' );
                ?></label>
                    <input type="text" <?php 
                echo esc_attr( $custom_attributes );
                ?> size="5" name="_xt_woopr_points_renewal_points" value="<?php 
                echo esc_attr( $max_discount );
                ?>"/>
                    <?php 
                echo wc_help_tip( esc_attr( $renewal_points_description ) );
                ?>
                </div>
                <?php 
            }
        }
        // End if().
        do_action(
            'xt_woopr_after_category_fields',
            $points,
            $max_discount,
            $edit
        );
    }

    /**
     * Save the points earned / maximum discount fields
     *
     * @since 1.0
     * @param int $term_id term ID being saved
     */
    public function save_product_category_points_field( $term_id ) {
        // points earned
        if ( isset( $_POST['_xt_woopr_points_earned'] ) && '' !== $_POST['_xt_woopr_points_earned'] ) {
            $this->update_term_meta( $term_id, '_xt_woopr_points_earned', sanitize_text_field( stripslashes( $_POST['_xt_woopr_points_earned'] ) ) );
        } else {
            $this->delete_term_meta( $term_id, '_xt_woopr_points_earned' );
        }
        // max points discount
        if ( isset( $_POST['_xt_woopr_points_max_discount'] ) && '' !== $_POST['_xt_woopr_points_max_discount'] ) {
            $this->update_term_meta( $term_id, '_xt_woopr_points_max_discount', sanitize_text_field( stripslashes( $_POST['_xt_woopr_points_max_discount'] ) ) );
        } else {
            $this->delete_term_meta( $term_id, '_xt_woopr_points_max_discount' );
        }
        // change rewewal points
        if ( isset( $_POST['_xt_woopr_points_renewal_points'] ) && '' !== $_POST['_xt_woopr_points_renewal_points'] ) {
            $this->update_term_meta( $term_id, '_xt_woopr_points_renewal_points', sanitize_text_field( stripslashes( $_POST['_xt_woopr_points_renewal_points'] ) ) );
        } else {
            $this->delete_term_meta( $term_id, '_xt_woopr_points_renewal_points' );
        }
        // Clear all points transients
        $this->clear_all_transients();
    }

    /**
     * Clears all transients that saves variation high/low points.
     *
     * @since 1.0.05
     * @version 1.0.0
     */
    public function clear_all_transients() {
        global $wpdb;
        $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '%xt_woopr_lowest_point_variation_%'" );
        $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '%xt_woopr_highest_point_variation_%'" );
    }

    /**
     * Add a 'Points Earned' column header to the product category list table
     *
     * @since 1.0
     * @param array $columns associative array of column id to title
     * @return array
     */
    public function add_product_category_list_table_points_column_header( $columns ) {
        $new_columns = array();
        foreach ( $columns as $column_key => $column_title ) {
            $new_columns[$column_key] = $column_title;
            // add column header immediately after 'Slug'
            if ( 'slug' == $column_key ) {
                $new_columns['points_earned'] = esc_html__( 'Points Earned', 'xt-woo-points-rewards' );
            }
        }
        return $new_columns;
    }

    /**
     * Add the 'Points Earned' column content to the product category list table
     *
     * @since 1.0
     * @param array $columns column content
     * @param string $column column ID
     * @param int $term_id the product category term ID
     * @return array
     */
    public function add_product_category_list_table_points_column( $columns, $column, $term_id ) {
        $points_earned = $this->get_term_meta( $term_id, '_xt_woopr_points_earned' );
        if ( 'points_earned' == $column ) {
            echo ( '' !== $points_earned ? esc_html( $points_earned ) : '&mdash;' );
        }
        return $columns;
    }

    /**
     *
     * Updates a term meta. Compatibility function for WC 3.6.
     *
     * @since 1.0.019
     * @param int    $term_id    Term ID.
     * @param string $meta_key   Meta key.
     * @param mixed  $meta_value Meta value.
     * @return bool
     */
    private function update_term_meta( $term_id, $meta_key, $meta_value ) {
        return update_term_meta( $term_id, $meta_key, $meta_value );
    }

    /**
     *
     * Deletes a term meta. Compatibility function for WC 3.6.
     *
     * @since 1.0.019
     * @param int    $term_id    Term ID.
     * @param string $meta_key   Meta key.
     * @return bool
     */
    private function delete_term_meta( $term_id, $meta_key ) {
        return delete_term_meta( $term_id, $meta_key );
    }

    /**
     * Gets a term meta. Compatibility function for WC 3.6.
     *
     * @since 1.0.019
     * @param int    $term_id Term ID.
     * @param string $key     Meta key.
     * @param bool   $single  Whether to return a single value. (default: true).
     * @return mixed
     */
    private function get_term_meta( $term_id, $key, $single = true ) {
        return get_term_meta( $term_id, $key, $single );
    }

}

// end \XT_Woo_Points_Rewards_Admin class