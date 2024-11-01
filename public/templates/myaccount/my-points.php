<?php
/**
 * This file is used to markup the my points template.
 *
 * This template can be overridden by copying it to yourtheme/xt-woo-points-rewards/myaccount/my-points.php.
 *
 * @var $hide_title
 * @var $points_label
 * @var $points_balance
 *
 * HOWEVER, on occasion we will need to update template files and you (the theme developer).
 * will need to copy the new files to your theme to maintain compatibility. We try to do this.
 * as little as possible, but it does happen. When this occurs the version of the template file will.
 * be bumped and the readme will list any important changes.
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

<div class="xt_woopr-account-section xt_woopr-points">
    <?php if(!$hide_title): ?>
    <h3><?php printf( esc_html__( 'My %s', 'xt-woo-points-rewards' ), $points_label  ); ?></h3>
    <?php endif; ?>
    <p><?php printf( esc_html__( "You have %s%d%s %s", 'xt-woo-points-rewards' ), '<strong>', $points_balance, '</strong>', $points_label ); ?></p>
</div>
