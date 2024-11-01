<?php
/**
 * This file is used to markup the my points template.
 *
 * This template can be overridden by copying it to yourtheme/xt-woo-points-rewards/myaccount/points-legend.php.
 *
 * HOWEVER, on occasion we will need to update template files and you (the theme developer).
 * will need to copy the new files to your theme to maintain compatibility. We try to do this.
 * as little as possible, but it does happen. When this occurs the version of the template file will.
 * be bumped and the readme will list any important changes.
 *
 * @var $hide_title
 * @var $points_label
 *
 * @see         https://docs.xplodedthemes.com/article/127-template-structure
 * @author 		XplodedThemes
 * @package     XT_Woo_Points_Rewards/Templates
 * @version     1.0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
?>

<?php if(!empty($earning_descriptions)): ?>
    <div class="xt_woopr-account-section xt_woopr-points-earning">
        <?php if(!$hide_title): ?>
        <h3><?php printf( esc_html__( 'How to earn %s?', 'xt-woo-points-rewards' ), $points_label  ); ?></h3>
        <?php endif;?>
        <table class="shop_table my_account_xt_points_rewards my_account_orders">
            <tbody>
            <?php foreach ( $earning_descriptions as $description) : ?>
                <tr class="points-event">
                    <td class="points-rewards-event-description">
                        <?php echo wp_kses_post($description); ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>