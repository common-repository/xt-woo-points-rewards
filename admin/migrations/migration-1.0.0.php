<?php
class XT_Woo_Points_Rewards_Migration_1_0_0 {

    public static function migrate() {

        self::install();
    }

    private static function install() {

        global $wpdb;

        $wpdb->hide_errors();

        $core = xt_woo_points_rewards();

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        require_once( $core->plugin_path(). 'admin/class-admin.php' );

        // try and avoid timeouts as best we can
        @set_time_limit( 0 );

        // initial install, add the xt_woopr_balance user meta (of 0) to all users
        $offset           = (int) get_option( 'xt_woopr_install_offset', 0 );
        $records_per_page = 500;
        do {
            // grab a set of user ids
            $user_ids = get_users( array( 'fields' => 'ID', 'offset' => $offset, 'number' => $records_per_page ) );

            // iterate through the results and set the meta
            if ( is_array( $user_ids ) ) {
                foreach ( $user_ids as $user_id ) {

                    $xt_woopr_balance = get_user_meta( $user_id, 'xt_woopr_balance', true );

                    if ( '' === $xt_woopr_balance ) {
                        // need to create an empty balance for this customer
                        update_user_meta( $user_id, 'xt_woopr_balance', 0 );
                    }
                }
            }

            // increment offset
            $offset += $records_per_page;
            // and keep track of how far we made it in case we hit a script timeout
            update_option( 'xt_woopr_install_offset', $offset );

        } while ( count( $user_ids ) == $records_per_page );  // while full set of results returned  (meaning there may be more results still to retrieve)

        // install default settings, terms, etc
        foreach ( $core->settings()->get_all_setting_tab_fields() as $setting ) {
            if ( isset( $setting['default'] ) ) {
                add_option( $setting['id'], $setting['default'] );
            }
        }

        // it's important that this table be indexed-up as it can grow quite large
        $sql =
            "CREATE TABLE {$core->user_points_log_db_tablename} (
		  id bigint(20) NOT NULL AUTO_INCREMENT,
		  user_id bigint(20) NOT NULL,
		  points bigint(20) NOT NULL,
		  type varchar(255) DEFAULT NULL,
		  user_points_id bigint(20) DEFAULT NULL,
		  order_id bigint(20) DEFAULT NULL,
		  admin_user_id bigint(20) DEFAULT NULL,
		  data longtext DEFAULT NULL,
		  date datetime NOT NULL,
		  KEY idx_xt_woopr_user_points_log_date (date),
		  KEY idx_xt_woopr_user_points_log_type (type),
		  KEY idx_xt_woopr_user_points_log_points (points),
		  PRIMARY KEY  (id)
		) " . self::get_db_collation();
        dbDelta( $sql );

        $sql =
            "CREATE TABLE {$core->user_points_db_tablename} (
		  id bigint(20) NOT NULL AUTO_INCREMENT,
		  user_id bigint(20) NOT NULL,
		  points bigint(20) NOT NULL,
		  points_balance bigint(20) NOT NULL,
		  order_id bigint(20) DEFAULT NULL,
		  date datetime NOT NULL,
		  KEY idx_xt_woopr_user_points_user_id_points_balance (user_id,points_balance),
		  KEY `idx_xt_woopr_user_points_date_points_balance` (`date`,`points_balance`),
		  PRIMARY KEY  (id)
		) " . self::get_db_collation();
        dbDelta( $sql );

    }


    /**
     * Returns the WordPress DB collation clause used when creating tables
     *
     * @since 1.0
     * @return string db collation clause
     */
    private static function get_db_collation() {
        global $wpdb;

        $collate = '';
        if ( $wpdb->has_cap( 'collation' ) ) {
            if ( ! empty( $wpdb->charset ) ) {
                $collate .= "DEFAULT CHARACTER SET {$wpdb->charset}";
            }
            if ( ! empty( $wpdb->collate ) ) {
                $collate .= " COLLATE {$wpdb->collate}";
            }
        }

        return $collate;
    }
}

XT_Woo_Points_Rewards_Migration_1_0_0::migrate();