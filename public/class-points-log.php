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
 * Points Log Class
 *
 * Access class for the Points Log
 *
 * @since 1.0
 */
class XT_Woo_Points_Rewards_Points_Log {
    /** @var int count of rows found from the query function */
    public static $found_rows = 0;

    /**
     * Adds an entry to the points log table
     *
     * @param array $args the log entry arguments:
     * + `user_id`        - int required customer identifier
     * + `points`         - int required the points change, ie 10, -75, etc
     * + `event_type`     - string required the event type slug
     * + `user_points_id` - int optional user_points identifier, if this log entry is associated with a user points record
     * + `order_id`       - int optional order identifier, if this log entry is associated with an order
     * + `data`           - mixed optional data to associate with this event
     * + `timestamp`      - string optional event timestamp in mysql format.  Defaults to the current time.
     * @return boolean true if the record is created, false otherwise
     * @since 1.0
     */
    public static function add_log_entry( $args, $disable_emails = false ) {
        global $wpdb;
        // required data column/value
        $data = array(
            'user_id' => $args['user_id'],
            'points'  => $args['points'],
            'type'    => $args['event_type'],
            'date'    => ( isset( $args['timestamp'] ) && $args['timestamp'] ? $args['timestamp'] : current_time( 'mysql', 1 ) ),
        );
        // required data format
        $format = array(
            '%d',
            '%d',
            '%s',
            '%s'
        );
        // optional parameter: associated user points record
        if ( isset( $args['user_points_id'] ) && $args['user_points_id'] ) {
            $data['user_points_id'] = $args['user_points_id'];
            $format[] = '%d';
        }
        // optional parameter: associated order record
        if ( isset( $args['order_id'] ) && $args['order_id'] ) {
            $data['order_id'] = $args['order_id'];
            $format[] = '%d';
        }
        // optional parameter: associated arbitrary data
        if ( isset( $args['data'] ) && $args['data'] ) {
            $data['data'] = serialize( $args['data'] );
            $format[] = '%s';
        }
        // automatically associate this log entry with an admin user if in the admin
        if ( is_admin() && !defined( 'DOING_AJAX' ) ) {
            $admin_user = wp_get_current_user();
            $data['admin_user_id'] = $admin_user->ID;
            $format[] = '%d';
        }
        // create the record
        $inserted = $wpdb->insert( self::core()->user_points_log_db_tablename, $data, $format );
        return $inserted;
    }

    public static function send_email( $log_entry ) {
        $event_types = XT_Woo_Points_Rewards_Manager::get_event_types();
        if ( isset( $event_types[$log_entry->type] ) ) {
            $enabled = self::core()->settings()->get_option_bool( $log_entry->type . '-email' );
            if ( !$enabled ) {
                return false;
            }
            $user = get_user_by( 'id', $log_entry->user_id );
            if ( !is_object( $user ) || !$user->exists() ) {
                return false;
            }
            $points_label = self::core()->get_points_label( $log_entry->points );
            $total_points = XT_Woo_Points_Rewards_Manager::get_users_points( $log_entry->user_id, true );
            $points = self::table_entry_markup( $log_entry, 'points' );
            $action = self::table_entry_markup( $log_entry, 'event' );
            $date = self::table_entry_markup( $log_entry, 'date' );
            $subject = apply_filters(
                'xt_woopr_email_subject',
                '[' . get_bloginfo( 'name' ) . ']: ' . sprintf( __( '%s Update', 'xt-woo-points-rewards' ), $points_label ),
                $log_entry,
                $user
            );
            $heading = apply_filters(
                'xt_woopr_email_heading',
                sprintf( __( '%s Update', 'xt-woo-points-rewards' ), $points_label ),
                $log_entry,
                $user
            );
            // load the template
            $message = self::core()->get_template( 'emails/points-update', array(
                'user'         => $user,
                'log_entry'    => $log_entry,
                'points'       => $points,
                'action'       => $action,
                'date'         => $date,
                'total_points' => $total_points,
                'points_label' => $points_label,
            ), true );
            return XT_Framework_Woocommerce::send_email(
                $user->user_email,
                $subject,
                $heading,
                $message
            );
        }
        return false;
    }

    /**
     * Gets point log entries based on $args
     *
     * @param array $args the query arguments
     * @return array of log entry objects
     * @since 1.0
     */
    public static function get_points_log_entries( $args ) {
        global $wpdb;
        // special handling for searching by user
        if ( !empty( $args['user'] ) ) {
            $args['where'][] = $wpdb->prepare( self::core()->user_points_log_db_tablename . ".user_id = %s", $args['user'] );
        }
        // special handling for searching by event type
        if ( !empty( $args['event_type'] ) ) {
            $args['where'][] = $wpdb->prepare( self::core()->user_points_log_db_tablename . ".type = %s", $args['event_type'] );
        }
        $entries = array();
        foreach ( self::query( $args ) as $log_entry ) {
            $log_entry = self::transform_log_entry( $log_entry );
            $entries[] = $log_entry;
        }
        return $entries;
    }

    public static function transform_log_entry( $log_entry ) {
        if ( is_array( $log_entry ) ) {
            $log_entry = (object) $log_entry;
        }
        // maybe unserialize the arbitrary data object
        $log_entry->data = ( !empty( $log_entry->data ) ? maybe_unserialize( $log_entry->data ) : null );
        // Format the event date as "15 minutes ago" if the event took place in the last 24 hours, otherwise just show the date (timestamp on mouse hover)
        $timestamp = strtotime( $log_entry->date );
        $t_time = date_i18n( 'Y/m/d g:i:s A', $timestamp );
        $time_diff = current_time( 'timestamp', true ) - $timestamp;
        if ( $time_diff > 0 && $time_diff < 24 * 60 * 60 ) {
            $h_time = sprintf( __( '%s ago', 'xt-woo-points-rewards' ), human_time_diff( $timestamp, current_time( 'timestamp', true ) ) );
        } else {
            $h_time = date_i18n( wc_date_format(), $timestamp );
        }
        $log_entry->date_display_human = $h_time;
        $log_entry->date_display = $t_time;
        // retrieve the description
        $log_entry->description = XT_Woo_Points_Rewards_Manager::event_type_description( $log_entry->type, $log_entry );
        return $log_entry;
    }

    /**
     * Returns all event types and their counts
     *
     * @return array of event types
     * @since 1.0
     */
    public static function get_event_types() {
        global $wpdb;
        $query = "SELECT type, COUNT(*) as count FROM " . self::core()->user_points_log_db_tablename . " GROUP BY type ORDER BY type";
        $results = $wpdb->get_results( $query );
        // make a "human-readable" name of the event type slug
        if ( is_array( $results ) ) {
            foreach ( $results as &$row ) {
                $row->name = ucwords( str_replace( '-', ' ', $row->type ) );
            }
        }
        return ( $results ?: array() );
    }

    /**
     * Query for point log entries based on $args
     *
     * @param array $args the query arguments
     * @return array of log entry objects
     * @since 1.0
     */
    public static function query( $args ) {
        global $wpdb;
        // calculate found rows? (costly, but needed for pagination)
        $found_rows = '';
        if ( isset( $args['calc_found_rows'] ) && $args['calc_found_rows'] ) {
            $found_rows = 'SQL_CALC_FOUND_ROWS';
        }
        // distinct results?
        $distinct = '';
        if ( isset( $args['distinct'] ) && $args['distinct'] ) {
            $distinct = 'DISTINCT';
        }
        // returned fields
        $fields = self::core()->user_points_log_db_tablename . ".*";
        if ( !empty( $args['fields'] ) && is_array( $args['fields'] ) ) {
            $fields .= ', ' . implode( ', ', $args['fields'] );
        }
        // joins
        $join = '';
        if ( !empty( $args['join'] ) && is_array( $args['join'] ) ) {
            $join = implode( ' ', $args['join'] );
        }
        // where clauses
        $where = '';
        if ( !empty( $args['where'] ) ) {
            $where = 'AND ' . implode( ' AND ', $args['where'] );
        }
        // group by
        $groupby = '';
        if ( !empty( $args['groupby'] ) && is_array( $args['groupby'] ) ) {
            $groupby = implode( ', ', $args['groupby'] );
        }
        // order by
        $orderby = '';
        if ( !empty( $args['orderby'] ) ) {
            // convert "really simple" format of simply a string
            if ( is_string( $args['orderby'] ) ) {
                $args['orderby'] = array(array(
                    'field' => $args['orderby'],
                ));
            }
            // check if the 'simple' format is being used with a single column to order by
            list( $key ) = array_keys( $args['orderby'] );
            if ( is_string( $key ) ) {
                $args['orderby'] = array($args['orderby']);
            }
            foreach ( $args['orderby'] as $_orderby ) {
                if ( isset( $_orderby['field'] ) ) {
                    $orderby .= ( empty( $orderby ) ? $_orderby['field'] : ', ' . $_orderby['field'] );
                    if ( isset( $_orderby['order'] ) ) {
                        $orderby .= ' ' . $_orderby['order'];
                    }
                }
            }
            if ( $orderby ) {
                $orderby = 'ORDER BY ' . $orderby;
            }
        }
        // query limits
        $limits = '';
        // allow a page and per_page to be provided, or simply per_page to limit to that number of results (defaults 'paged' to '1')
        if ( !empty( $args['per_page'] ) && empty( $args['paged'] ) ) {
            $args['paged'] = 1;
        }
        if ( !empty( $args['per_page'] ) ) {
            $limits = ' LIMIT ' . ($args['paged'] - 1) * $args['per_page'] . ', ' . $args['per_page'];
        }
        // build the query
        $query = "SELECT {$found_rows} {$distinct} {$fields} FROM " . self::core()->user_points_log_db_tablename . " {$join} WHERE 1=1 {$where} {$groupby} {$orderby} {$limits}";
        $results = $wpdb->get_results( $query );
        // record the found rows
        if ( isset( $args['calc_found_rows'] ) && $args['calc_found_rows'] ) {
            self::$found_rows = $wpdb->get_var( 'SELECT FOUND_ROWS()' );
        }
        return ( $results ?: array() );
    }

    public static function table_entry_markup( $log_entry, $column_name ) {
        switch ( $column_name ) {
            case 'customer':
                $customer_email = null;
                if ( $log_entry->user_id ) {
                    $user = get_user_by( 'id', $log_entry->user_id );
                    $customer_email = ( is_object( $user ) && $user->exists() ? $user->user_email : false );
                }
                if ( $customer_email ) {
                    $column_content = sprintf( '<a href="%s">%s</a>', get_edit_user_link( $log_entry->user_id ), $customer_email );
                } else {
                    $column_content = sprintf( '<a href="%s">%s</a>', get_edit_user_link( $log_entry->user_id ), ( $user ? $user->user_login : esc_html__( 'Unknown', 'xt-woo-points-rewards' ) ) );
                }
                break;
            case 'points':
                // add a '+' sign when needed
                $color = ( $log_entry->points > 0 ? '#298c29' : '#de1e1e' );
                $column_content = '<strong style="color:' . esc_attr( $color ) . '">' . (( $log_entry->points > 0 ? '+' : '' )) . $log_entry->points . '</strong>';
                break;
            case 'event':
                $column_content = $log_entry->description;
                break;
            case 'date':
                $column_content = '<span title="' . esc_attr( $log_entry->date_display ) . '">' . esc_html( $log_entry->date_display_human ) . '</span>';
                break;
            default:
                $column_content = '';
                break;
        }
        return $column_content;
    }

    public static function core() {
        return xt_woo_points_rewards();
    }

}
