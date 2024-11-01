<?php
/**
 * This file is used to markup the my points template.
 *
 * This template can be overridden by copying it to yourtheme/xt-woo-points-rewards/myaccount/my-points-log.php.
 *
 * @var $hide_title
 * @var $points_label
 * @var $events
 * @var $current_page
 * @var $count
 * @var $total_rows
 * @var $from_shortcode
 *
 * HOWEVER, on occasion we will need to update template files and you (the theme developer).
 * will need to copy the new files to your theme to maintain compatibility. We try to do this.
 * as little as possible, but it does happen. When this occurs the version of the template file will.
 * be bumped and the readme will list any important changes.
 *
 * @see         https://docs.xplodedthemes.com/article/127-template-structure
 * @author 		XplodedThemes
 * @package     XT_Woo_Points_Rewards/Templates
 * @version     1.0.2
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
?>

<?php if ( $events ) : ?>

    <div class="xt_woopr-account-section xt_woopr-points-history">
        <?php if(!$hide_title): ?>
        <h3><?php printf( esc_html__( 'My %s history', 'xt-woo-points-rewards' ), $points_label  ); ?></h3>
        <?php endif; ?>
        <table class="shop_table my_account_xt_points_rewards my_account_orders">
            <thead>
            <tr>
                <th class="points-rewards-event-description"><span class="nobr"><?php esc_html_e( 'Event', 'xt-woo-points-rewards' ); ?></span></th>
                <th class="points-rewards-event-date"><span class="nobr"><?php esc_html_e( 'Date', 'xt-woo-points-rewards' ); ?></span></th>
                <th class="points-rewards-event-points"><span class="nobr"><?php echo esc_html( $points_label ); ?></span></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ( $events as $event ) : ?>
                <tr class="points-event">
                    <td class="points-rewards-event-description">
                        <?php echo wp_kses_post($event->description); ?>
                    </td>
                    <td class="points-rewards-event-date">
                        <?php echo '<span title="' . esc_attr( $event->date_display ) . '">' . esc_html( $event->date_display_human ) . '</span>'; ?>
                    </td>
                    <td class="points-rewards-event-points">
                        <?php echo ( $event->points > 0 ? '+' : '' ) . $event->points; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <div class="woocommerce-pagination woocommerce-pagination--without-numbers woocommerce-Pagination">
            <?php if ( $current_page != 1 ) : ?>
                <a class="woocommerce-button woocommerce-button--previous woocommerce-Button woocommerce-Button--previous button" href="<?php echo esc_url( xt_woo_points_rewards()->frontend()->endpoint_url( $current_page - 1, $from_shortcode ) ); ?>"><?php esc_html_e( 'Previous', 'xt-woo-points-rewards' ); ?></a>
            <?php endif; ?>

            <?php if ( $current_page * $count < $total_rows ) : ?>
                <a class="woocommerce-button woocommerce-button--next woocommerce-Button woocommerce-Button--next button" href="<?php echo esc_url( xt_woo_points_rewards()->frontend()->endpoint_url( $current_page + 1, $from_shortcode ) ); ?>"><?php esc_html_e( 'Next', 'xt-woo-points-rewards' ); ?></a>
            <?php endif; ?>
        </div>
    </div>

<?php endif; ?>