<?php

/**
 * XT Points and Rewards
 *
 * @package     WC-Points-Rewards/List-Table
 * @author      XplodedThemes
 * @copyright   Copyright (c) 2019, XplodedThemes
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
if ( !class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}
/**
 * Points Log List Table class
 *
 * Extends WP_List_Table to display points history and related information
 *
 * @since 1.0
 * @extends \WP_List_Table
 */
class XT_Woo_Points_Rewards_Points_Log_List_Table extends WP_List_Table {
    /**
     * Core class reference.
     *
     * @since    1.0.0
     * @access   private
     * @var      XT_Woo_Points_Rewards    core    Core Class
     */
    protected $core;

    /**
     * Setup list table
     *
     * @see WP_List_Table::__construct()
     * @since 1.0
     * @return \XT_Woo_Points_Rewards_Points_Log_List_Table
     */
    public function __construct() {
        $this->core = xt_woo_points_rewards();
        parent::__construct( array(
            'singular' => $this->core->get_singlular_points_label(),
            'plural'   => $this->core->get_plural_points_label(),
            'ajax'     => false,
            'screen'   => 'woocommerce_page_xt_woopr_points_log',
        ) );
    }

    /**
     * Returns the column slugs and titles
     *
     * @see WP_List_Table::get_columns()
     * @since 1.0
     * @return array of column slug => title
     */
    public function get_columns() {
        $columns = array(
            'customer' => esc_html__( 'Customer', 'xt-woo-points-rewards' ),
            'points'   => $this->core->get_plural_points_label(),
            'event'    => esc_html__( 'Event', 'xt-woo-points-rewards' ),
            'date'     => esc_html__( 'Date', 'xt-woo-points-rewards' ),
        );
        return $columns;
    }

    /**
     * Returns the sortable columns and initial direction
     *
     * @see WP_List_Table::get_sortable_columns()
     * @since 1.0
     * @return array of sortable column slug => array( orderby, boolean )
     *         where true indicates the initial sort is descending
     */
    public function get_sortable_columns() {
        return array(
            'points' => array('points', false),
            'date'   => array('date', false),
        );
    }

    /**
     * Get column content, this is called once per column, per row item ($order)
     * returns the content to be rendered within that cell.
     *
     * @see WP_List_Table::single_row_columns()
     * @since 1.0
     * @param object $log_entry one row (item) in the table
     * @param string $column_name the column slug
     * @return string the column content
     */
    public function column_default( $log_entry, $column_name ) {
        return XT_Woo_Points_Rewards_Points_Log::table_entry_markup( $log_entry, $column_name );
    }

    /**
     * Gets the current orderby, defaulting to 'date' if none is selected
     */
    private function get_current_orderby() {
        return ( isset( $_GET['orderby'] ) ? sanitize_text_field( $_GET['orderby'] ) : 'date' );
    }

    /**
     * Gets the current orderby, defaulting to 'DESC' if none is selected
     */
    private function get_current_order() {
        return ( isset( $_GET['order'] ) ? sanitize_text_field( $_GET['order'] ) : 'DESC' );
    }

    /**
     * Prepare the list of points history items for display
     *
     * @see WP_List_Table::prepare_items()
     * @since 1.0
     */
    public function prepare_items() {
        $per_page = $this->get_items_per_page( 'xt_woopr_points_log_per_page' );
        // main query args
        $args = array(
            'orderby'         => array(
                'field' => $this->get_current_orderby(),
                'order' => $this->get_current_order(),
            ),
            'per_page'        => $per_page,
            'paged'           => $this->get_pagenum(),
            'calc_found_rows' => true,
        );
        // Filter: points event log by customer, event type or event date
        $args = $this->add_filter_args( $args );
        // items as array
        $this->items = XT_Woo_Points_Rewards_Points_Log::get_points_log_entries( $args );
        // total number of items for pagination purposes
        $found_items = XT_Woo_Points_Rewards_Points_Log::$found_rows;
        $this->set_pagination_args( array(
            'total_items' => $found_items,
            'per_page'    => $per_page,
            'total_pages' => ceil( $found_items / $per_page ),
        ) );
    }

    /**
     * Adds in any query arguments based on the current filters
     *
     * @since 1.0
     * @param array $args associative array of WP_Query arguments used to query and populate the list table
     * @return array associative array of WP_Query arguments used to query and populate the list table
     */
    private function add_filter_args( $args ) {
        global $wpdb;
        // filter by customer user
        if ( isset( $_GET['_customer_user'] ) && $_GET['_customer_user'] > 0 ) {
            $args['user'] = sanitize_text_field( $_GET['_customer_user'] );
        }
        // filter by event type
        if ( isset( $_GET['_event_type'] ) && $_GET['_event_type'] ) {
            $args['event_type'] = sanitize_text_field( $_GET['_event_type'] );
        }
        // filter by event log date
        if ( isset( $_GET['date'] ) && $_GET['date'] ) {
            $date = sanitize_text_field( $_GET['date'] );
            $year = substr( $date, 0, 4 );
            $month = ltrim( substr( $date, 4, 2 ), '0' );
            $args['where'][] = $wpdb->prepare( 'YEAR( date ) = %s AND MONTH( date ) = %s', $year, $month );
        }
        return $args;
    }

    /**
     * The text to display when there are no point log entries
     *
     * @see WP_List_Table::no_items()
     * @since 1.0
     */
    public function no_items() {
        if ( isset( $_REQUEST['s'] ) ) {
            ?>
			<p><?php 
            esc_html_e( 'No log entries found', 'xt-woo-points-rewards' );
            ?></p>
		<?php 
        } else {
            ?>
			<p><?php 
            esc_html_e( 'Point log entries will appear here for you to view and manage.', 'xt-woo-points-rewards' );
            ?></p>
		<?php 
        }
    }

    /**
     * Extra controls to be displayed before pagination, which
     * includes our Filters: Customers, Event Types, Event Dates
     *
     * @see WP_List_Table::extra_tablenav();
     * @since 1.0
     * @param string $which the placement, one of 'top' or 'bottom'
     */
    public function extra_tablenav( $which ) {
        if ( 'top' == $which ) {
            echo '<div class="alignleft actions">';
            // Customers, products
            $user_string = '';
            $customer_id = '';
            if ( !empty( $_GET['_customer_user'] ) ) {
                $customer_id = absint( $_GET['_customer_user'] );
                // For multisite we only want to display members of the current site.
                if ( is_multisite() && !is_user_member_of_blog( $customer_id ) ) {
                    $user_string = esc_html__( 'Invalid customer', 'woocommerce-points-and-rewards' );
                } else {
                    $user = get_user_by( 'id', $customer_id );
                    $user_string = esc_html( $user->display_name ) . ' (#' . absint( $user->ID ) . ' &ndash; ' . esc_html( $user->user_email );
                }
            }
            ?>
	
			<select id="customer_user" class="wc-customer-search" name="_customer_user" data-placeholder="<?php 
            esc_attr_e( 'Show All Customers', 'xt-woo-points-rewards' );
            ?>">
				<?php 
            if ( !empty( $customer_id ) ) {
                echo '<option value="' . esc_attr( $customer_id ) . '">' . wp_kses_post( $user_string ) . '</option>';
            }
            ?>
			</select>


            <?php 
            submit_button(
                esc_html__( 'Filter', 'xt-woo-points-rewards' ),
                'button',
                false,
                false,
                array(
                    'id' => 'post-query-submit',
                )
            );
            echo '</div>';
        }
    }

    /**
     * Display a monthly dropdown for filtering items by availability date
     *
     * @since 1.0
     */
    private function render_dates_dropdown() {
        global $wpdb, $wp_locale;
        // Performance: we could always pull out the database order-by and sort in code to get rid of a 'filesort' from the query
        $months = $wpdb->get_results( "\n\t\t\tSELECT DISTINCT YEAR( date ) AS year, MONTH( date ) AS month\n\t\t\tFROM " . $this->core->user_points_log_db_tablename . "\n\t\t\tORDER BY date DESC\n\t\t" );
        $month_count = count( $months );
        if ( !$month_count || 1 == $month_count && 0 == $months[0]->month ) {
            return;
        }
        $date = ( isset( $_GET['date'] ) ? (int) $_GET['date'] : 0 );
        ?>
		<select id="dropdown_dates" name='date' class="wc-enhanced-select" style="width:200px">
			<option<?php 
        selected( $date, 0 );
        ?> value='0'><?php 
        esc_html_e( 'Show all Event Dates', 'xt-woo-points-rewards' );
        ?></option>
			<?php 
        foreach ( $months as $arc_row ) {
            if ( 0 == $arc_row->year ) {
                continue;
            }
            $month = zeroise( $arc_row->month, 2 );
            $year = $arc_row->year;
            printf(
                "<option %s value='%s'>%s</option>\n",
                selected( $date, $year . $month, false ),
                esc_attr( $arc_row->year . $month ),
                sprintf( esc_html__( '%1$s %2$d', 'xt-woo-points-rewards' ), $wp_locale->get_month( $month ), $year )
            );
        }
        ?>
		</select>
		<?php 
    }

}
