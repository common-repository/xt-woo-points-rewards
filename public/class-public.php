<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://xplodedthemes.com
 * @since      1.0.0
 *
 * @package    XT_Woo_Points_Rewards
 * @subpackage XT_Woo_Points_Rewards/public
 */
/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    XT_Woo_Points_Rewards
 * @subpackage XT_Woo_Points_Rewards/public
 * @author     XplodedThemes 
 */
class XT_Woo_Points_Rewards_Public {
    /**
     * Core class reference.
     *
     * @since    1.0.0
     * @access   private
     * @var      XT_Woo_Points_Rewards    $core    Core Class
     */
    private $core;

    /**
     * Var that holds the product class object.
     *
     * @since    1.0.0
     * @access   public
     * @var      XT_Woo_Points_Rewards_Product $product
     */
    public $product;

    /**
     * Var that holds the cart class object.
     *
     * @since    1.0.0
     * @access   public
     * @var      XT_Woo_Points_Rewards_Cart_Checkout $cart
     */
    public $cart;

    /**
     * Var that holds the order class object.
     *
     * @since    1.0.0
     * @access   public
     * @var      XT_Woo_Points_Rewards_Order $order
     */
    public $order;

    /**
     * Var that holds the discount class object.
     *
     * @since    1.0.0
     * @access   public
     * @var      XT_Woo_Points_Rewards_Discount $discount
     */
    public $discount;

    /**
     * Var that holds the actions class object.
     *
     * @since    1.0.0
     * @access   public
     * @var      XT_Woo_Points_Rewards_Actions $actions
     */
    public $actions;

    /** @var string the endpoint page to use for frontend */
    public $endpoint;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    XT_Woo_Points_Rewards    $core    Plugin core class
     */
    public function __construct( $core ) {
        $this->core = $core;
        if ( $core->enabled() ) {
            // set my points endpoint
            $this->endpoint = get_option( 'xt_woopr_endpoint', 'my-points' );
            add_action( 'wp_enqueue_scripts', array($this, 'enqueue_styles') );
            add_action( 'wp_enqueue_scripts', array($this, 'enqueue_scripts') );
            // display points on a separate tab on user's account page
            add_action( 'init', array($this, 'add_endpoints') );
            add_action( 'xt_woopr_activate', array($this, 'schedule_expired_points_event') );
            add_action( 'xt_woopr_deactivate', array($this, 'unschedule_expired_points_event') );
            add_action( 'woocommerce_account_menu_items', array($this, 'add_menu_items') );
            add_action( 'woocommerce_account_' . $this->endpoint . '_endpoint', array($this, 'render_my_points_page') );
            // remove the user points record on user delete
            add_action( 'delete_user', array($this, 'delete_user_points') );
        }
        $this->init_frontend_dependencies();
    }

    public function init_frontend_dependencies() {
        $this->cart = new XT_Woo_Points_Rewards_Cart_Checkout($this->core);
        $this->order = new XT_Woo_Points_Rewards_Order($this->core);
        $this->product = new XT_Woo_Points_Rewards_Product($this->core);
        $this->discount = new XT_Woo_Points_Rewards_Discount($this->core);
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        $handle = $this->core->plugin_slug();
        wp_enqueue_style(
            $handle,
            $this->core->plugin_url( 'public/assets/css', 'frontend.css' ),
            array(),
            $this->core->plugin_version(),
            'all'
        );
        $this->core->settings()->generate_frontend_settings_css_output( $handle );
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script( 'jquery' );
        wp_register_script(
            $this->core->plugin_slug(),
            $this->core->plugin_url( 'public/assets/js', 'frontend' . XTFW_SCRIPT_SUFFIX . '.js' ),
            array('jquery'),
            $this->core->plugin_version(),
            false
        );
        $vars = apply_filters( $this->core->plugin_prefix( 'localize_script_vars' ), array(
            'can_use_premium_code' => $this->core->access_manager()->can_use_premium_code__premium_only(),
            'partial_redemption'   => $this->core->settings()->get_option_bool( 'partial_redemption_enabled' ),
            'lang'                 => array(
                'partial_redemption_prompt' => esc_html__( 'How many points would you like to apply?', 'xt-woo-points-rewards' ),
            ),
        ) );
        wp_localize_script( $this->core->plugin_slug(), 'XT_WOOPR', $vars );
        wp_enqueue_script( $this->core->plugin_slug() );
    }

    /**
     * Register new endpoint to use inside My Account page.
     *
     * @since 1.0.0
     *
     * @see https://developer.wordpress.org/reference/functions/add_rewrite_endpoint/
     */
    public function add_endpoints() {
        add_rewrite_endpoint( $this->endpoint, EP_ROOT | EP_PAGES );
    }

    /**
     * Get my points endpoint url with page number
     *
     * @since 1.0.0
     *
     */
    public function endpoint_url( $page = 1, $from_shortcode = false ) {
        if ( !$from_shortcode ) {
            return wc_get_endpoint_url( $this->endpoint, $page );
        } else {
            return get_the_permalink() . $page;
        }
    }

    /**
     * Insert the new endpoint into the My Account menu.
     *
     * @since 1.0.0
     *
     * @param array $menu_items
     * @return array
     */
    public function add_menu_items( $menu_items ) {
        // Remove logout menu item.
        $logout = $menu_items['customer-logout'];
        unset($menu_items['customer-logout']);
        // Insert Points & Rewards.
        $menu_items[$this->endpoint] = sprintf( esc_html__( 'My %s', 'xt-woo-points-rewards' ), $this->core->get_points_label( 2, true ) );
        // Insert back logout item.
        $menu_items['customer-logout'] = $logout;
        return $menu_items;
    }

    /**
     * Get earning descriptions
     *
     * @since 1.0.0
     *
     * @param array $earning_descritpions
     * @return array
     */
    function earning_descriptions() {
        $earning_descriptions = array();
        $points_ratio = XT_Woo_Points_Rewards_Manager::get_points_earning_ratio();
        if ( !empty( $points_ratio ) ) {
            $points_ratio_label = $this->core->get_points_label( $points_ratio->points, true );
            $monetary_value = apply_filters( 'xt_woopr_filter_monetary_value', $points_ratio->monetary_value );
            $default_earning_description = sprintf(
                esc_html__( 'Earn %s%s %s%s for every %s%s%s spent', 'xt-woo-points-rewards' ),
                '<strong>',
                $points_ratio->points,
                $points_ratio_label,
                '</strong>',
                '<strong>',
                wc_price( $monetary_value ),
                '</strong>'
            );
            $earning_descriptions[] = $default_earning_description;
        }
        return apply_filters( $this->core->plugin_prefix() . '_action_earning_descriptions', $earning_descriptions );
    }

    /**
     * Template function to render the template
     *
     * @since 1.0
     */
    function render_my_points_page( $current_page, $from_shortcode = false ) {
        $this->render_my_points( false, false );
        $this->render_my_points_log(
            $current_page,
            $from_shortcode,
            false,
            false
        );
        $this->render_points_legend( false, false );
    }

    /**
     * Template function to render my points template
     *
     * @since 1.0
     */
    function render_my_points( $hide_title = false, $return = true ) {
        $points_balance = XT_Woo_Points_Rewards_Manager::get_users_points();
        $points_label = $this->core->get_points_label( $points_balance, true );
        // load the template
        return $this->core->get_template( 'myaccount/my-points', array(
            'hide_title'     => $hide_title,
            'points_balance' => $points_balance,
            'points_label'   => $points_label,
        ), $return );
    }

    /**
     * Template function to render my points log template
     *
     * @since 1.0
     */
    function render_my_points_log(
        $current_page,
        $from_shortcode = false,
        $hide_title = false,
        $return = true
    ) {
        $points_label = $this->core->get_plural_points_label( true );
        $count = apply_filters( 'xt_woopr_my_account_points_events', 5, get_current_user_id() );
        $current_page = ( empty( $current_page ) ? 1 : absint( $current_page ) );
        // get a set of points events, ordered newest to oldest
        $args = array(
            'calc_found_rows' => true,
            'orderby'         => array(
                'field' => 'date',
                'order' => 'DESC',
            ),
            'per_page'        => $count,
            'paged'           => $current_page,
            'user'            => get_current_user_id(),
        );
        $events = XT_Woo_Points_Rewards_Points_Log::get_points_log_entries( $args );
        $total_rows = XT_Woo_Points_Rewards_Points_Log::$found_rows;
        // load the template
        return $this->core->get_template( 'myaccount/my-points-log', array(
            'hide_title'     => $hide_title,
            'points_label'   => $points_label,
            'events'         => $events,
            'total_rows'     => $total_rows,
            'current_page'   => $current_page,
            'count'          => $count,
            'from_shortcode' => $from_shortcode,
        ), $return );
    }

    /**
     * Template function to render points legend template
     *
     * @since 1.0
     */
    function render_points_legend( $hide_title = false, $return = true ) {
        $points_label = $this->core->get_singlular_points_label( true );
        $earning_descriptions = $this->earning_descriptions();
        // load the template
        return $this->core->get_template( 'myaccount/points-legend', array(
            'hide_title'           => $hide_title,
            'points_label'         => $points_label,
            'earning_descriptions' => $earning_descriptions,
        ), $return );
    }

    /**
     * Deletes the user points for the deleted user identified by $user_id
     *
     * @param int $user_id the identifier of the user being deleted
     * @since 1.0
     */
    public function delete_user_points( $user_id ) {
        XT_Woo_Points_Rewards_Manager::delete_user_points( $user_id );
    }

    /**
     * Get message html
     *
     * @param mixed $message Message string or array of 2 strings for left / right columns
     * @param string $type type of message
     * @since 1.1.0
     */
    public function get_message_html( $message, $type = 'info' ) {
        return $this->core->plugin_frontend_notices()->get_frontend_message_output( $type, $message, array($this->core->plugin_short_prefix( 'points_message' )) );
    }

    public function schedule_expired_points_event() {
        if ( !wp_next_scheduled( 'xt_woopr_expire_points_daily' ) ) {
            wp_schedule_event( time(), 'daily', 'xt_woopr_expire_points_daily' );
        }
    }

    function unschedule_expired_points_event() {
        wp_clear_scheduled_hook( 'xt_woopr_expire_points_daily' );
    }

}
