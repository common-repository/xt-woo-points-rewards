<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://xplodedthemes.com
 * @since      1.0.0
 *
 * @package    XT_Woo_Points_Rewards
 * @subpackage XT_Woo_Points_Rewards/admin
 */
/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    XT_Woo_Points_Rewards
 * @subpackage XT_Woo_Points_Rewards/admin
 * @author     XplodedThemes
 */
class XT_Woo_Points_Rewards_Admin {
    /**
     * Core class reference.
     *
     * @since    1.0.0
     * @access   private
     * @var      XT_Woo_Points_Rewards $core Core Class
     */
    protected $core;

    /**
     * Var that holds the product admin class object.
     *
     * @since    1.0.0
     * @access   protected
     * @var      XT_Woo_Points_Rewards_Admin_Product $product
     */
    protected $product;

    /**
     * Var that holds the points actions class object
     *
     * @since    1.0.0
     * @access   protected
     * @var      XT_Woo_Points_Rewards_Actions $actions
     */
    protected $actions;

    /**
     * Var that manages points list table object.
     *
     * @since    1.0.0
     * @access   protected
     * @var      XT_Woo_Points_Rewards_Manage_Points_List_Table $manage_points_list_table
     */
    protected $manage_points_list_table;

    /**
     * Var that manages the points log list table object.
     *
     * @since    1.0.0
     * @access   protected
     * @var      XT_Woo_Points_Rewards_Points_Log_List_Table $points_log_list_table
     */
    protected $points_log_list_table;

    /**
     * Initialize the class and set its properties.
     *
     * @param XT_Woo_Points_Rewards $core Plugin core class.
     * @since    1.0.0
     */
    public function __construct( $core ) {
        $this->core = $core;
        $prefix = $core->plugin_prefix() . '_';
        // plugin admin tabs
        add_filter(
            $prefix . 'admin_tabs',
            array($this, 'add_admin_tabs'),
            1,
            1
        );
        // plugin admin setting tabs
        add_filter(
            $prefix . 'setting_tabs',
            array($this, 'add_setting_tabs'),
            1,
            1
        );
        // warn that points won't be able to be redeemed if coupons are disabled.
        add_action( 'admin_notices', array($this, 'verify_coupons_enabled') );
        // manage points / points log list table settings.
        add_action( 'in_admin_header', array($this, 'load_list_tables') );
        add_filter(
            'set-screen-option',
            array($this, 'set_list_table_options'),
            10,
            3
        );
        add_filter( 'manage_woocommerce_page_xt_woopr_columns', array($this, 'manage_columns') );
        // Add a custom plugin setting field types.
        add_action( $prefix . 'settings_field_conversion_ratio', array($this, 'render_conversion_ratio_field') );
        add_action( $prefix . 'settings_field_singular_plural', array($this, 'render_singular_plural_field') );
        add_action( $prefix . 'settings_field_points_expiry', array($this, 'render_points_expiry') );
        // Filters the save custom field type functions so they get sanitized / saved correctly
        add_filter(
            $prefix . 'settings_sanitize_option_type_conversion_ratio',
            array($this, 'save_conversion_ratio_field'),
            10,
            3
        );
        add_filter(
            $prefix . 'settings_sanitize_option_type_singular_plural',
            array($this, 'save_singular_plural_field'),
            10,
            3
        );
        add_filter(
            $prefix . 'settings_sanitize_option_type_points_expiry',
            array($this, 'save_points_expiry'),
            10,
            3
        );
        // enqueue custom js on settings page
        add_action( $prefix . 'settings_rendered', array($this, 'settings_rendered') );
        /** Order hooks */
        // Add the points earned/redeemed for a discount to the edit order page.
        add_action( 'woocommerce_admin_order_totals_after_shipping', array($this, 'render_points_earned_redeemed_info') );
        /** Coupon hooks */
        // Add coupon points modifier field.
        add_action( 'woocommerce_coupon_options', array($this, 'render_coupon_points_modifier_field') );
        // Tool to clear points.
        add_filter( 'woocommerce_debug_tools', array($this, 'woocommerce_debug_tools') );
        $this->init_backend_dependencies();
    }

    public function init_backend_dependencies() {
        $this->product = new XT_Woo_Points_Rewards_Admin_Product($this->core);
    }

    public function add_admin_tabs( $tabs ) {
        $tabs[] = array(
            'id'          => 'manage',
            'title'       => sprintf( esc_html__( 'Manage %s', 'xt-woo-points-rewards' ), $this->core->get_plural_points_label() ),
            'action_link' => true,
            'content'     => array(
                'type'     => 'function',
                'function' => array($this, 'show_manage_tab'),
            ),
            'callback'    => array($this, 'add_manage_points_screen_options'),
            'order'       => 0,
        );
        $tabs[] = array(
            'id'          => 'log',
            'title'       => sprintf( esc_html__( '%s Log', 'xt-woo-points-rewards' ), $this->core->get_plural_points_label() ),
            'action_link' => true,
            'content'     => array(
                'type'     => 'function',
                'function' => array($this, 'show_log_tab'),
            ),
            'callback'    => array($this, 'add_points_log_screen_options'),
            'order'       => 5,
        );
        $tabs[] = array(
            'id'          => 'shortcodes',
            'title'       => esc_html__( 'Shortcodes', 'xt-woo-points-rewards' ),
            'action_link' => true,
            'content'     => array(
                'type'     => 'function',
                'function' => array($this, 'show_shortcodes_tab'),
            ),
            'order'       => 10,
        );
        return $tabs;
    }

    public function add_setting_tabs( $tabs ) {
        $tabs[] = array(
            'id'          => 'settings',
            'title'       => esc_html__( 'Settings', 'xt-woo-points-rewards' ),
            'action_link' => true,
            'settings'    => array($this, 'get_settings'),
            'order'       => 10,
            'callback'    => array($this, 'setting_tab_callback'),
        );
        return $tabs;
    }

    /**
     * Show the Points & Rewards > Manage tab content
     *
     * @since 1.0
     */
    public function show_manage_tab() {
        $this->enqueue_scripts();
        // setup 'Manage Points' list table and prepare the data.
        $manage_table = $this->get_manage_points_list_table();
        $manage_table->prepare_items();
        ?>
        <form method="post" id="mainform" action="" enctype="multipart/form-data">
            <input type="hidden" name="page" value="<?php 
        echo esc_attr( $this->core->plugin_slug() );
        ?>"/>
            <?php 
        // display the list table.
        $manage_table->render_messages();
        // display the list table.
        $manage_table->display();
        ?>
        </form>
        <?php 
    }

    /**
     * Show the Points & Rewards > Log tab content
     *
     * @since 1.0
     */
    public function show_log_tab() {
        $this->enqueue_scripts();
        // setup 'Points Log' list table and prepare the data.
        $log_table = $this->get_points_log_list_table();
        $log_table->prepare_items();
        ?>
        <form method="get" id="mainform" action="" enctype="multipart/form-data">
            <input type="hidden" name="page" value="<?php 
        echo esc_attr( $this->core->plugin_slug( 'log' ) );
        ?>"/>
            <?php 
        // display the list table.
        $log_table->display();
        ?>
        </form>
        <?php 
    }

    /**
     * Show the Points & Rewards > Log tab content
     *
     * @since 1.0
     */
    public function show_shortcodes_tab() {
        $this->core->admin_messages()->premium_required_big( esc_html__( 'shortcodes.', 'xt-woo-variation-swatches' ) );
        ?>

        <?php 
    }

    /**
     * Return the plugin action links.  This will only be called if the plugin
     * is active.
     *
     * @param array $actions associative array of action names to anchor tags
     * @return array associative array of plugin action links
     * @since 1.0
     */
    public function add_plugin_configure_link( $actions ) {
        // add the link to the front of the actions list
        return array_merge( array(
            'configure' => sprintf( '<a href="%s">%s</a>', $this->core->plugin_admin_url( 'settings' ), esc_html__( 'Configure', 'xt-woo-points-rewards' ) ),
        ), $actions );
    }

    /**
     * Verify that coupons are enabled and render an annoying warning in the
     * admin if they are not
     *
     * @since 1.0
     */
    public function verify_coupons_enabled() {
        $coupons_enabled = ( get_option( 'woocommerce_enable_coupons' ) === 'no' ? false : true );
        if ( !$coupons_enabled ) {
            $message = sprintf( 
                /* translators: %1$s link start, %2$s link end */
                __( 'XT Points and Rewards requires coupons to be %senabled%s in order to function properly and allow customers to redeem points during checkout.', 'xt-woo-points-rewards' ),
                '<a href="' . admin_url( 'admin.php?page=wc-settings' ) . '">',
                '</a>'
             );
            echo '<div class="error"><p>' . wp_kses_post( $message ) . '</p></div>';
        }
    }

    /**
     * Save our list table options
     *
     * @param string $status unknown.
     * @param string $option the option name.
     * @param mixed $value the option value.
     * @return mixed
     * @since 1.0
     */
    public function set_list_table_options( $status, $option, $value ) {
        if ( 'xt_woopr_manage_points_per_page' == $option || 'xt_woopr_points_log_per_page' == $option ) {
            return $value;
        }
        return $status;
    }

    /**
     * Add manage points list table Screen Options
     *
     * @since 1.0
     */
    public function add_manage_points_screen_options() {
        $args = array(
            'label'   => esc_html__( 'Manage Points', 'xt-woo-points-rewards' ),
            'default' => 20,
            'option'  => 'xt_woopr_manage_points_per_page',
        );
        add_screen_option( 'per_page', $args );
    }

    /**
     * Add points log list table Screen Options
     *
     * @since 1.0
     */
    public function add_points_log_screen_options() {
        $args = array(
            'label'   => esc_html__( 'Points Log', 'xt-woo-points-rewards' ),
            'default' => 20,
            'option'  => 'xt_woopr_points_log_per_page',
        );
        add_screen_option( 'per_page', $args );
    }

    /**
     * Loads the list tables so the columns can be hidden/shown from
     * the page Screen Options dropdown (this must be done prior to Screen Options
     * being rendered)
     *
     * @since 1.0
     */
    public function load_list_tables() {
        if ( isset( $_GET['page'] ) && 'xt-woo-points-rewards' == $_GET['page'] ) {
            if ( isset( $_GET['tab'] ) && 'log' == $_GET['tab'] ) {
                $this->get_points_log_list_table();
            } else {
                $this->get_manage_points_list_table();
            }
        }
    }

    /**
     * Returns the list table columns so they can be managed from the screen
     * options pulldown.  Normally this would happen automatically based on the
     * screen id, but since we have two distinct list tables sharing one screen
     * we had to generate unique id's in the two list table constructors, which
     * means that the core manage_{screen_id}_columns filters don't get called,
     * so we hook on the screen-based filter and then call our two custom-screen
     * based filters to get the columns based on the current tab.
     *
     * Unfortunately the settings still seem to be saved to the common screen id
     * so hiding a column in one list table hides a column of the same name in
     * the other
     *
     * @param $columns array array of column definitions
     * @return array of column definitions
     * @since 1.0
     */
    public function manage_columns( $columns ) {
        if ( isset( $_GET['page'] ) && 'xt-woo-points-rewards' == $_GET['page'] ) {
            if ( isset( $_GET['tab'] ) && 'log' == $_GET['tab'] ) {
                $columns = apply_filters( 'manage_woocommerce_page_xt_woopr_points_log_columns', $columns );
            } else {
                $columns = apply_filters( 'manage_woocommerce_page_xt_woopr_manage_points_columns', $columns );
            }
        }
        return $columns;
    }

    /**
     * Gets the manage points list table object
     *
     * @return \XT_Woo_Points_Rewards_Manage_Points_List_Table the points & rewards manage points list table object
     * @since 1.0
     */
    private function get_manage_points_list_table() {
        if ( !is_object( $this->manage_points_list_table ) ) {
            $class_name = apply_filters( 'xt_woopr_manage_points_list_table_class_name', 'XT_Woo_Points_Rewards_Manage_Points_List_Table' );
            $this->manage_points_list_table = new $class_name();
        }
        return $this->manage_points_list_table;
    }

    /**
     * Gets the points log list table object
     *
     * @return \XT_Woo_Points_Rewards_Points_Log_List_Table the points & rewards points log list table object
     * @since 1.0
     */
    private function get_points_log_list_table() {
        if ( !is_object( $this->points_log_list_table ) ) {
            $class_name = apply_filters( 'xt_woopr_points_log_list_table_class_name', 'XT_Woo_Points_Rewards_Points_Log_List_Table' );
            $this->points_log_list_table = new $class_name();
        }
        return $this->points_log_list_table;
    }

    /**
     * Enqueue custom js on settings page
     *
     * @since 1.0
     */
    public function settings_rendered() {
        xtfw_enqueue_js( "\n\n\t\t\tvar apply_points = \$('#xt_woopr_apply_points');\n\t\t\tvar data = apply_points.data('data');\n\t\t\t\n\t\t\t\$('.apply_points_from_field input.xtfw-datepicker').on('change', function() {\n\t\t\t\tvar date_from = \$( this ).val();\n\t\t\t\tdata = \$.extend(data, {date_from: date_from});\n\t\t\t\tapply_points.data('data', data);\n\t\t\t}).change();\n\t\t\t\n\t\t\t\$('.apply_points_to_field input.xtfw-datepicker').on('change', function() {\n\t\t\t\tvar date_to = \$( this ).val();\n\t\t\t\tdata = \$.extend(data, {date_to: date_to});\n\t\t\t\tapply_points.data('data', data);\n\t\t\t}).change();\n\t\t\t\n\t\t" );
    }

    /**
     * Returns settings array for use by render/save/install default settings methods
     *
     * @return array settings
     * @since 1.0
     */
    public function get_settings() {
        $settings = array();
        $settings[] = array(
            'title' => esc_html__( 'General Settings', 'xt-woo-points-rewards' ),
            'type'  => 'title',
            'id'    => 'general_settings_start',
        );
        // Enabled
        $settings[] = array(
            'title'    => esc_html__( 'Enable Plugin', 'xt-woo-points-rewards' ),
            'desc'     => esc_html__( 'Enable the plugin once you\'re ready.', 'xt-woo-points-rewards' ),
            'desc_tip' => esc_html__( 'Enable the plugin once you\'re ready.', 'xt-woo-points-rewards' ),
            'id'       => 'enabled',
            'default'  => '',
            'type'     => 'checkbox',
        );
        // Enabled for user role(s)
        $settings[] = array(
            'title'    => esc_html__( 'Enable for user roles', 'xt-woo-points-rewards' ),
            'desc'     => esc_html__( 'If user roles are selected, the plugin will only be enabled for those roles. Leave empty to enable the plugin for all users.', 'xt-woo-points-rewards' ),
            'desc_tip' => esc_html__( 'If user roles are selected, the plugin will only be enabled for those roles. Leave empty to enable the plugin for all users.', 'xt-woo-points-rewards' ),
            'id'       => 'enabled_for_roles',
            'options'  => $this->core->get_available_user_roles_options(),
            'default'  => array(),
            'type'     => 'multiselect',
        );
        // My points endpoint
        $settings[] = array(
            'title'   => esc_html__( 'My Points Endpoint Slug', 'xt-woo-points-rewards' ),
            'desc'    => esc_html__( 'Set the frontend endpoint slug used for the My Points page. The page will appear under WooCommerce My Account page.', 'xt-woo-points-rewards' ),
            'id'      => 'endpoint',
            'default' => 'my-points',
            'type'    => 'text',
        );
        $settings[] = array(
            'type' => 'sectionend',
            'id'   => 'general_settings_end',
        );
        $settings[] = array(
            'title' => esc_html__( 'Points Settings', 'xt-woo-points-rewards' ),
            'type'  => 'title',
            'id'    => 'points_settings_start',
        );
        // earn points conversion.
        $settings[] = array(
            'title'    => esc_html__( 'Earn Points Conversion Rate', 'xt-woo-points-rewards' ),
            'desc_tip' => esc_html__( 'Set the number of points awarded based on the product price.', 'xt-woo-points-rewards' ),
            'id'       => 'earn_points_ratio',
            'default'  => '2:1',
            'type'     => 'conversion_ratio',
        );
        // earn points conversion.
        $settings[] = array(
            'title'    => esc_html__( 'Earn Points Rounding Mode', 'xt-woo-points-rewards' ),
            'desc_tip' => esc_html__( 'Set how points should be rounded.', 'xt-woo-points-rewards' ),
            'id'       => 'earn_points_rounding',
            'default'  => 'round',
            'options'  => array(
                'round' => 'Round to nearest integer',
                'floor' => 'Always round down',
                'ceil'  => 'Always round up',
            ),
            'type'     => 'select',
        );
        // redeem points conversion.
        $settings[] = array(
            'title'    => esc_html__( 'Redemption Conversion Rate', 'xt-woo-points-rewards' ),
            'desc_tip' => esc_html__( 'Set the value of points redeemed for a discount.', 'xt-woo-points-rewards' ),
            'id'       => 'redeem_points_ratio',
            'default'  => '100:1',
            'type'     => 'conversion_ratio',
        );
        $settings[] = array(
            'type'         => 'image',
            'image'        => $this->core->plugin_url() . 'admin/assets/images/settings/points-settings.png',
            'image_mobile' => $this->core->plugin_url() . 'admin/assets/images/settings/points-settings-mobile.png',
            'link'         => $this->core->plugin_upgrade_url(),
        );
        $settings[] = array(
            'type' => 'sectionend',
            'id'   => 'points_settings_end',
        );
        $settings[] = array(
            'title' => esc_html__( 'Points Messages', 'xt-woo-points-rewards' ),
            'desc'  => sprintf( esc_html__( 'Adjust the message by using %1$s{points}%2$s and %1$s{points_label}%2$s to represent the points earned / available for redemption and the label set for points.', 'xt-woo-points-rewards' ), '<code>', '</code>' ),
            'type'  => 'title',
            'id'    => 'messages_start',
        );
        // single product page message.
        $settings[] = array(
            'title'    => esc_html__( 'Single Product Page Message', 'xt-woo-points-rewards' ),
            'desc_tip' => esc_html__( 'Add an optional message to the single product page below the price. Customize the message using {points}, {points_value} and {points_label}. Limited HTML is allowed. Leave blank to disable.', 'xt-woo-points-rewards' ),
            'desc'     => sprintf( esc_html__( 'Variables: %1$s{points}%2$s %1$s{points_value}%2$s %1$s{points_label}%2$s', 'xt-woo-points-rewards' ), '<code>', '</code>' ),
            'id'       => 'single_product_message',
            'css'      => 'min-height: 100px;',
            'default'  => sprintf( esc_html__( 'Purchase this product now and earn %s!', 'xt-woo-points-rewards' ), '<strong>{points}</strong> {points_label}' ),
            'type'     => 'textarea',
        );
        // variable product page message.
        $settings[] = array(
            'title'    => esc_html__( 'Variable Product Page Message', 'xt-woo-points-rewards' ),
            'desc_tip' => esc_html__( 'Add an optional message to the variable product page below the price. Customize the message using {points}, {points_value} and {points_label}. Limited HTML is allowed. Leave blank to disable.', 'xt-woo-points-rewards' ),
            'desc'     => sprintf( esc_html__( 'Variables: %1$s{points}%2$s %1$s{points_value}%2$s %1$s{points_label}%2$s', 'xt-woo-points-rewards' ), '<code>', '</code>' ),
            'id'       => 'variable_product_message',
            'css'      => 'min-height: 100px;',
            'default'  => sprintf( esc_html__( 'Earn up to %s.', 'xt-woo-points-rewards' ), '<strong>{points}</strong> {points_label}' ),
            'type'     => 'textarea',
        );
        // earn points shop/cart/checkout page message.
        $settings[] = array(
            'title'    => esc_html__( 'Earn Points Message', 'xt-woo-points-rewards' ),
            'desc_tip' => esc_html__( 'Displayed on the shop, cart and checkout pages when points can be earned. Customize the message using {points}, {points_value} and {points_label}. Limited HTML is allowed.', 'xt-woo-points-rewards' ),
            'desc'     => sprintf( esc_html__( 'Variables: %1$s{points}%2$s %1$s{points_value}%2$s %1$s{points_label}%2$s', 'xt-woo-points-rewards' ), '<code>', '</code>' ),
            'id'       => 'earn_points_message',
            'css'      => 'min-height: 100px;',
            'default'  => sprintf( esc_html__( 'Complete your order and earn %s for a discount on a future purchase', 'xt-woo-points-rewards' ), '<strong>{points}</strong> {points_label}' ),
            'type'     => 'textarea',
        );
        // earn points message visibility
        $settings[] = array(
            'title'    => esc_html__( 'Earn Points Message Visibility', 'xt-woo-points-rewards' ),
            'desc_tip' => esc_html__( 'Where would you like this message to appear?', 'xt-woo-points-rewards' ),
            'id'       => 'earn_points_message_pages',
            'options'  => array(
                'shop'     => 'Shop Pages',
                'cart'     => 'Cart Page',
                'checkout' => 'Checkout Page',
            ),
            'default'  => array('shop', 'cart', 'checkout'),
            'type'     => 'multiselect',
        );
        // redeem points shop/cart/checkout page message.
        $settings[] = array(
            'title'    => esc_html__( 'Redeem Points Message', 'xt-woo-points-rewards' ),
            'desc_tip' => esc_html__( 'Displayed on the shop, cart and checkout page when points are available for redemption. Customize the message using {points}, {points_value}, and {points_label}. Limited HTML is allowed.', 'xt-woo-points-rewards' ),
            'desc'     => sprintf( esc_html__( 'Variables: %1$s{points}%2$s %1$s{points_value}%2$s %1$s{points_label}%2$s', 'xt-woo-points-rewards' ), '<code>', '</code>' ),
            'id'       => 'redeem_points_message',
            'css'      => 'min-height: 100px;',
            'default'  => sprintf( esc_html__( 'Use %s for a %s discount on this order!', 'xt-woo-points-rewards' ), '<strong>{points}</strong> {points_label}', '<strong>{points_value}</strong>' ),
            'type'     => 'textarea',
        );
        // earn points message visibility
        $settings[] = array(
            'title'    => esc_html__( 'Redeem Points Message Visibility', 'xt-woo-points-rewards' ),
            'desc_tip' => esc_html__( 'Where would you like this message to appear?', 'xt-woo-points-rewards' ),
            'id'       => 'redeem_points_message_pages',
            'options'  => array(
                'shop'     => 'Shop Pages',
                'cart'     => 'Cart Page',
                'checkout' => 'Checkout Page',
            ),
            'default'  => array('shop', 'cart', 'checkout'),
            'type'     => 'multiselect',
        );
        // earned points thank you / order received page message.
        $settings[] = array(
            'title'    => esc_html__( 'Order Received Message', 'xt-woo-points-rewards' ),
            'desc_tip' => esc_html__( 'Displayed on the thank you / order received & order detail page when points were earned. Customize the message using {points}, {total_points}, {points_label}, and {total_points_label}. Limited HTML is allowed.', 'xt-woo-points-rewards' ),
            'desc'     => sprintf( esc_html__( 'Variables: %1$s{points}%2$s %1$s{total_points}%2$s %1$s{points_label}%2$s %1$s{total_points_label}%2$s', 'xt-woo-points-rewards' ), '<code>', '</code>' ),
            'id'       => 'order_message',
            'css'      => 'min-height: 100px;',
            'default'  => sprintf( esc_html__( 'You have earned %s for this order. You now have a total of %s.', 'xt-woo-points-rewards' ), '<strong>{points}</strong> {points_label}', '<strong>{total_points}</strong> {total_points_label}' ),
            'type'     => 'textarea',
        );
        // New order customer email message / Order details customer email message
        $settings[] = array(
            'title'    => esc_html__( 'New Order / Order details Email Message', 'xt-woo-points-rewards' ),
            'desc_tip' => esc_html__( 'Displayed within the New Order email / Order Details email that is sent to the customer when placing an order. Customize the message using {points}, {total_points}, {points_label}, and {total_points_label}. Limited HTML is allowed.', 'xt-woo-points-rewards' ),
            'desc'     => sprintf( esc_html__( 'Variables: %1$s{points}%2$s %1$s{total_points}%2$s %1$s{points_label}%2$s %1$s{total_points_label}%2$s', 'xt-woo-points-rewards' ), '<code>', '</code>' ),
            'id'       => 'order_customer_email_message',
            'css'      => 'min-height: 100px;',
            'default'  => sprintf( esc_html__( 'You have earned %s for this order. You now have a total of %s.', 'xt-woo-points-rewards' ), '<strong>{points}</strong> {points_label}', '<strong>{total_points}</strong> {total_points_label}' ),
            'type'     => 'textarea',
        );
        // New order admin email message
        $settings[] = array(
            'title'    => esc_html__( 'New Order Admin Email Message', 'xt-woo-points-rewards' ),
            'desc_tip' => esc_html__( 'Displayed within the New Order email that is sent to the admin when a customer places an order. Customize the message using {points}, {total_points}, {points_label}, and {total_points_label}. Limited HTML is allowed.', 'xt-woo-points-rewards' ),
            'desc'     => sprintf( esc_html__( 'Variables: %1$s{points}%2$s %1$s{total_points}%2$s %1$s{points_label}%2$s %1$s{total_points_label}%2$s', 'xt-woo-points-rewards' ), '<code>', '</code>' ),
            'id'       => 'order_admin_email_message',
            'css'      => 'min-height: 100px;',
            'default'  => sprintf( esc_html__( 'The customer earned %s for this order. They now have a total of %s.', 'xt-woo-points-rewards' ), '<strong>{points}</strong> {points_label}', '<strong>{total_points}</strong> {total_points_label}' ),
            'type'     => 'textarea',
        );
        $settings[] = array(
            'type' => 'sectionend',
            'id'   => 'messages_end',
        );
        $settings[] = array(
            'title' => esc_html__( 'Points Earned for Actions', 'xt-woo-points-rewards' ),
            'desc'  => esc_html__( 'Customers can also earn points for actions like creating an account or writing a product review. You can enter the amount of points the customer will earn for each action in this section.', 'xt-woo-points-rewards' ),
            'type'  => 'title',
            'id'    => 'earn_points_for_actions_settings_start',
        );
        $settings[] = array(
            'type'         => 'image',
            'image'        => $this->core->plugin_url() . 'admin/assets/images/settings/actions.png',
            'image_mobile' => $this->core->plugin_url() . 'admin/assets/images/settings/actions-mobile.png',
            'link'         => $this->core->plugin_upgrade_url(),
        );
        $settings[] = array(
            'type' => 'sectionend',
            'id'   => 'earn_points_for_actions_settings_end',
        );
        $settings[] = array(
            'title' => esc_html__( 'Email Notifications', 'xt-woo-points-rewards' ),
            'desc'  => esc_html__( 'Turn ON or OFF customer email notifications for each action that resulted in earning points.', 'xt-woo-points-rewards' ),
            'type'  => 'title',
            'id'    => 'email_notifications_start',
        );
        $settings[] = array(
            'type'         => 'image',
            'image'        => $this->core->plugin_url() . 'admin/assets/images/settings/emails.png',
            'image_mobile' => $this->core->plugin_url() . 'admin/assets/images/settings/emails-mobile.png',
            'link'         => $this->core->plugin_upgrade_url(),
        );
        $settings[] = array(
            'type' => 'sectionend',
            'id'   => 'email_notifications_end',
        );
        $settings[] = array(
            'title'       => esc_html__( 'Points Badges', 'xt-woo-points-rewards' ),
            'desc'        => esc_html__( 'Insert a points badge on your shop products to highlight how many points can a customer earn on purchase.', 'xt-woo-points-rewards' ),
            'type'        => 'title',
            'id'          => 'points_badges_start',
            'has_preview' => ( $this->core->access_manager()->can_use_premium_code__premium_only() ? array(
                'id'         => 'points_badge_preview',
                'type'       => 'preview',
                'callback'   => array($this, 'render_product_points_badge_preview__premium_only'),
                'conditions' => array(array(
                    'id'    => 'points_badge_enabled',
                    'value' => 'yes',
                )),
            ) : null ),
        );
        $settings[] = array(
            'type'         => 'image',
            'image'        => $this->core->plugin_url() . 'admin/assets/images/settings/badge-settings.png',
            'image_mobile' => $this->core->plugin_url() . 'admin/assets/images/settings/badge-settings-mobile.png',
            'link'         => $this->core->plugin_upgrade_url(),
        );
        $settings[] = array(
            'type' => 'sectionend',
            'id'   => 'points_badges_end',
        );
        $settings[] = array(
            'type'  => 'title',
            'title' => esc_html__( 'Admin Tools', 'xt-woo-points-rewards' ),
            'id'    => 'points_actions_start',
        );
        $settings[] = array(
            'title'       => esc_html__( 'Apply Points to Previous Orders', 'xt-woo-points-rewards' ),
            'desc_tip'    => esc_html__( 'This will apply points to all previous orders (paid and completed) and cannot be reversed.', 'xt-woo-points-rewards' ),
            'button_text' => esc_html__( 'Apply Points', 'xt-woo-points-rewards' ),
            'type'        => 'admin_action',
            'id'          => 'apply_points',
            'before'      => array($this, 'render_apply_points_fields'),
            'class'       => 'xt-woopr-apply-points-button',
            'callback'    => array($this, 'handle_apply_points_action'),
        );
        $settings[] = array(
            'title'       => esc_html__( 'Delete all points and clear the logs.', 'xt-woo-points-rewards' ),
            'desc_tip'    => esc_html__( 'This action will remove all customer points. This cannot be undone!', 'xt-woo-points-rewards' ),
            'button_text' => esc_html__( 'Delete all points and clear the logs', 'xt-woo-points-rewards' ),
            'type'        => 'admin_action',
            'id'          => 'delete_all_points',
            'class'       => 'xt-woopr-delete-all-points-button',
            'callback'    => array($this, 'handle_delete_all_points_action'),
        );
        $settings[] = array(
            'title'       => esc_html__( 'Factory Reset', 'xt-woo-points-rewards' ),
            'desc_tip'    => esc_html__( 'This action will remove all customer points, user metas, category metas & post metas ever saved by the plugin. This cannot be undone!', 'xt-woo-points-rewards' ),
            'button_text' => esc_html__( 'Factory Reset', 'xt-woo-points-rewards' ),
            'type'        => 'admin_action',
            'id'          => 'factory_reset',
            'class'       => 'xt-woopr-factory-reset-button',
            'callback'    => array($this, 'handle_factory_reset_action'),
        );
        $settings[] = array(
            'type' => 'sectionend',
            'id'   => 'points_actions_end',
        );
        return $settings;
    }

    /**
     * Setting tab callback function
     *
     * @since    1.0.0
     */
    public function setting_tab_callback() {
        add_action( 'admin_enqueue_scripts', array($this, 'enqueue_settings_assets') );
    }

    /**
     * Register settings assets
     *
     * @since    1.0.0
     */
    public function enqueue_settings_assets() {
        $handle = $this->core->plugin_slug();
        wp_enqueue_style(
            $handle,
            $this->core->plugin_url( 'public/assets/css', 'badges.css' ),
            array(),
            $this->core->plugin_version()
        );
        wp_add_inline_style( $handle, '
            .xtfw-wc-product-image{
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                border: 1px solid #eaeaea;
                padding: 10px;
                margin-bottom: 10px;
                position: relative;
            }
            .xt_woopr-product-image {
                text-align: center;
            }
            .xt_woopr-product-title {
                font-size: 13px;
            }
        ' );
        $this->core->frontend()->enqueue_scripts();
    }

    /**
     * Render the Earn Points/Redeem Points conversion ratio section
     *
     * @param array $field associative array of field parameters
     * @since 1.0
     */
    public function render_conversion_ratio_field( $field ) {
        if ( isset( $field['title'] ) && isset( $field['id'] ) ) {
            $ratio = get_option( $field['id'], $field['default'] );
            list( $points, $monetary_value ) = explode( ':', $ratio );
            $monetary_value = str_replace( '.', wc_get_price_decimal_separator(), $monetary_value );
            ?>
            <tr valign="top">
                <th scope="row" class="titledesc">
                    <?php 
            $this->core->settings()->render_field_label( $field );
            ?>
                </th>
                <td class="forminp forminp-text">
                    <fieldset class="flex-box">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><?php 
            _e( 'Points', 'xt-woo-points-rewards' );
            ?></span>
                            </div>
                            <input name="<?php 
            echo esc_attr( $field['id'] . '_points' );
            ?>" id="<?php 
            echo esc_attr( $field['id'] . '_points' );
            ?>" type="text" class="small-text-input inline-input" value="<?php 
            echo esc_attr( $points );
            ?>"/>
                        </div>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><?php 
            echo get_woocommerce_currency_symbol();
            ?></span>
                            </div>
                            <input name="<?php 
            echo esc_attr( $field['id'] . '_monetary_value' );
            ?>" id="<?php 
            echo esc_attr( $field['id'] . '_monetary_value' );
            ?>" type="text" class="wc_input_price small-text-input inline-input" value="<?php 
            echo esc_attr( $monetary_value );
            ?>"/>
                        </div>
                    </fieldset>
                </td>
            </tr>
        <?php 
        }
    }

    /**
     * Render a singular-plural text field
     *
     * @param array $field associative array of field parameters
     * @since 0.1
     */
    public function render_singular_plural_field( $field ) {
        if ( isset( $field['title'] ) && isset( $field['id'] ) ) {
            $value = get_option( $field['id'], $field['default'] );
            $value = ( !empty( $value ) ? $value : ':' );
            list( $singular, $plural ) = explode( ':', $value );
            ?>
            <tr valign="top">
                <th scope="row" class="titledesc">
                    <?php 
            $this->core->settings()->render_field_label( $field );
            ?>
                </th>
                <td class="forminp forminp-text">
                    <fieldset class="flex-box">
                        <input name="<?php 
            echo esc_attr( $field['id'] . '_singular' );
            ?>" id="<?php 
            echo esc_attr( $field['id'] . '_singular' );
            ?>" type="text" class="small-text-input inline-input" placeholder="<?php 
            echo esc_attr__( 'Singular Label', 'xt-woo-points-rewards' );
            ?>" value="<?php 
            echo esc_attr( $singular );
            ?>"/>
                        <input name="<?php 
            echo esc_attr( $field['id'] . '_plural' );
            ?>" id="<?php 
            echo esc_attr( $field['id'] . '_plural' );
            ?>" type="text" class="small-text-input inline-input" placeholder="<?php 
            echo esc_attr__( 'Plural Label', 'xt-woo-points-rewards' );
            ?>" value="<?php 
            echo esc_attr( $plural );
            ?>"/>
                    </fieldset>
                </td>
            </tr>
        <?php 
        }
    }

    /**
     * Save the points expiry field
     *
     * @param $value
     * @param $option
     * @param $raw_value
     * @return string
     * @since 1.0.02
     */
    public function save_points_expiry( $value, $option, $raw_value ) {
        if ( isset( $_POST[$option['id'] . '_number'] ) && isset( $_POST[$option['id'] . '_period'] ) ) {
            if ( is_numeric( $_POST[$option['id'] . '_number'] ) && in_array( $_POST[$option['id'] . '_period'], array(
                'DAY',
                'WEEK',
                'MONTH',
                'YEAR'
            ) ) ) {
                // Check if expire points since has been set
                if ( isset( $_POST['expire_points_since'] ) && DateTime::createFromFormat( 'Y-m-d', $_POST['expire_points_since'] ) ) {
                    update_option( 'xt_woopr_points_expire_points_since', xtfw_clean( $_POST['expire_points_since'] ) );
                }
                return xtfw_clean( $_POST[$option['id'] . '_number'] ) . ':' . xtfw_clean( $_POST[$option['id'] . '_period'] );
            } else {
                update_option( 'xt_woopr_points_expire_points_since', '' );
                return '';
            }
        }
    }

    /**
     * Save the Earn Points/Redeem Points Conversion Ratio field
     *
     * @param $value
     * @param $option
     * @param $raw_value
     * @return string
     * @since 1.0
     */
    public function save_conversion_ratio_field( $value, $option, $raw_value ) {
        if ( isset( $_POST[$option['id'] . '_points'] ) && !empty( $_POST[$option['id'] . '_monetary_value'] ) ) {
            $points = xtfw_clean( $_POST[$option['id'] . '_points'] );
            $monetary_value = xtfw_clean( $_POST[$option['id'] . '_monetary_value'] );
            $monetary_value = str_replace( wc_get_price_decimal_separator(), '.', $monetary_value );
            // Clear all points transients.
            // Variable product points could be updated https://github.com/woocommerce/woocommerce-points-and-rewards/issues/401.
            $this->clear_all_transients();
            return $points . ':' . $monetary_value;
        }
    }

    /**
     * Clears all transients that saves variation high/low points.
     *
     * @since 1.6.42
     */
    public function clear_all_transients() {
        global $wpdb;
        $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '%xt_woopr_lowest_point_variation_%'" );
        $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '%xt_woopr_highest_point_variation_%'" );
    }

    /**
     * Save the singular-plural text fields
     *
     * @param $value
     * @param $option
     * @param $raw_value
     * @return string
     * @since 0.1
     */
    public function save_singular_plural_field( $value, $option, $raw_value ) {
        if ( !empty( $_POST[$option['id'] . '_singular'] ) && !empty( $_POST[$option['id'] . '_plural'] ) ) {
            return xtfw_clean( $_POST[$option['id'] . '_singular'] ) . ':' . xtfw_clean( $_POST[$option['id'] . '_plural'] );
        }
    }

    /**
     * Render the 'Points Expiry' section
     *
     * @param array $field associative array of field parameters
     * @since 1.0.02
     */
    public function render_points_expiry( $field ) {
        if ( isset( $field['title'] ) && isset( $field['id'] ) ) {
            $expiry = get_option( $field['id'] );
            if ( !$expiry ) {
                $number = '';
                $period = '';
            } else {
                list( $number, $period ) = explode( ':', $expiry );
            }
            $periods = array(
                'DAY'   => 'Day(s)',
                'WEEK'  => 'Week(s)',
                'MONTH' => 'Month(s)',
                'YEAR'  => 'Year(s)',
            );
            $expire_since = get_option( 'xt_woopr_points_expire_points_since', '' );
            ?>
            <tr valign="top">
                <th scope="row" class="titledesc">
                    <?php 
            $this->core->settings()->render_field_label( $field );
            ?>
                </th>
                <td class="forminp forminp-text">
                    <fieldset id="expire_points" class="flex-box">
                        <select name="<?php 
            echo esc_attr( $field['id'] . '_number' );
            ?>" class="xtfw-select inline-input" id="<?php 
            echo esc_attr( $field['id'] );
            ?>_number">
                            <option value=""><?php 
            echo esc_html( 'Never Expires', 'xt-woo-points-rewards' );
            ?></option>
                            <?php 
            for ($num = 1; $num < 100; $num++) {
                $selected = '';
                if ( $num == $number ) {
                    $selected = ' selected';
                }
                ?>
                                <option value="<?php 
                echo esc_attr( $num );
                ?>" <?php 
                echo esc_attr( $selected );
                ?>><?php 
                echo esc_html( $num );
                ?></option>
                            <?php 
            }
            ?>
                        </select>
                        <select name="<?php 
            echo esc_attr( $field['id'] . '_period' );
            ?>" class="xtfw-select inline-input"  id="<?php 
            echo esc_attr( $field['id'] );
            ?>_period">
                            <option value=""><?php 
            echo esc_html( 'Select Period', 'xt-woo-points-rewards' );
            ?></option>
                            <?php 
            foreach ( $periods as $period_id => $period_text ) {
                $selected = '';
                if ( $period_id == $period ) {
                    $selected = ' selected';
                }
                ?>
                                <option value="<?php 
                echo esc_attr( $period_id );
                ?>" <?php 
                echo esc_attr( $selected );
                ?>><?php 
                _e( $period_text, 'xt-woo-points-rewards' );
                ?></option>
                            <?php 
            }
            ?>
                        </select>
                    </fieldset>
                    <fieldset>
                        <p class="form-field expire-points-since">
                            <label for="expire_points_since"><?php 
            printf(
                esc_html__( '%sOnly apply to points earned since%s - %sOptional%s', 'xt-woo-points-rewards' ),
                '<strong>',
                '</strong>',
                '<em>',
                '</em>'
            );
            ?></label>
                            <br>
                            <input type="text" class="xtfw-datepicker inline-input" name="expire_points_since"
                                   id="expire_points_since" value="<?php 
            echo esc_attr( $expire_since );
            ?>"
                                   placeholder="<?php 
            echo _x( 'YYYY-MM-DD', 'placeholder', 'xt-woo-points-rewards' );
            ?>"
                                   pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])"
                            />
                            <div class="description"><?php 
            _e( 'Leave blank to apply to all points', 'xt-woo-points-rewards' );
            ?></div>
                        </p>
                    </fieldset>

                </td>
            </tr>
        <?php 
        }
    }

    /**
     * Render the 'Apply Points to all previous orders' fields
     *
     * @since 1.0
     */
    public function render_apply_points_fields() {
        ?>

        <fieldset class="flex-box">
            <div class="form-field apply_points_from_field">
                <label for="apply_points_from">
                    <strong><?php 
        echo esc_html__( 'From', 'xt-woo-points-rewards' );
        ?>:</strong>
                </label>
                <input type="text" class="xtfw-datepicker inline-input" name="apply_points_from"
                       id="apply_points_from" value=""
                       placeholder="<?php 
        echo _x( 'YYYY-MM-DD', 'placeholder', 'xt-woo-points-rewards' );
        ?>"
                       pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])"
                />
            </div>
            <div class="form-field apply_points_to_field">
                <label for="apply_points_to">
                    <strong><?php 
        echo esc_html__( 'To', 'xt-woo-points-rewards' );
        ?>:</strong>
                </label>
                <input type="text" class="xtfw-datepicker inline-input" name="apply_points_to"
                       id="apply_points_to" value=""
                       placeholder="<?php 
        echo _x( 'YYYY-MM-DD', 'placeholder', 'xt-woo-points-rewards' );
        ?>"
                       pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])"
                />
            </div>
        </fieldset>
        <p class="description"><?php 
        echo esc_html__( 'Leave blank to apply to all orders', 'xt-woo-points-rewards' );
        ?></p>
        <br>
        <?php 
    }

    /**
     * Handles apply points action.
     * Apply points to previous orders, useful when the plugin
     * is first installed
     *
     * @since 1.0
     */
    public function handle_apply_points_action() {
        // try and avoid timeouts as best we can
        @set_time_limit( 0 );
        $date_from = ( empty( $_REQUEST['date_from'] ) ? null : sanitize_text_field( urldecode( $_REQUEST['date_from'] ) ) );
        $date_to = ( empty( $_REQUEST['date_to'] ) ? null : sanitize_text_field( urldecode( $_REQUEST['date_to'] ) ) );
        $date_from = strtotime( $date_from );
        $date_to = strtotime( $date_to );
        // perform the action in manageable chunks
        $success_count = 0;
        $offset = 0;
        $per_page = 500;
        do {
            $args = array(
                'post_status'    => array('wc-processing', 'wc-completed'),
                'post_type'      => 'shop_order',
                'fields'         => 'ids',
                'offset'         => $offset,
                'posts_per_page' => $per_page,
                'author__not_in' => array('0'),
                'meta_query'     => array(array(
                    'key'     => '_xt_woopr_points_earned',
                    'compare' => 'NOT EXISTS',
                ), array(
                    'key'     => '_customer_user',
                    'compare' => '!=',
                    'value'   => '0',
                )),
            );
            // if date has been chosen, query orders only after that date
            $after = null;
            $before = null;
            if ( $date_from ) {
                $after = array(
                    'year'  => date( 'Y', $date_from ),
                    'month' => date( 'n', $date_from ),
                    'day'   => date( 'j', $date_from ),
                );
            }
            if ( $date_to ) {
                $before = array(
                    'year'  => date( 'Y', $date_to ),
                    'month' => date( 'n', $date_to ),
                    'day'   => date( 'j', $date_to ),
                );
            }
            if ( $after || $before ) {
                $date_query = array();
                if ( $after ) {
                    $date_query['after'] = $after;
                }
                if ( $before ) {
                    $date_query['before'] = $before;
                }
                $date_query['inclusive'] = true;
                $args['date_query'][] = $date_query;
            }
            // grab a set of order ids for existing orders with no earned points set
            $order_ids = get_posts( $args );
            // some sort of database error
            if ( is_wp_error( $order_ids ) ) {
                $this->core->plugin_notices()->add_error_message( esc_html__( 'Database error while applying user points.', 'xt-woo-points-rewards' ) );
                return;
            }
            // otherwise go through the results and set the order numbers
            if ( is_array( $order_ids ) ) {
                foreach ( $order_ids as $order_id ) {
                    $order = wc_get_order( $order_id );
                    if ( !$order ) {
                        continue;
                    }
                    $paid = null !== $order->get_date_paid( 'edit' );
                    // only add points to processing or completed orders
                    if ( $paid || 'completed' === $order->get_status() ) {
                        $this->core->frontend()->order->add_points_earned( $order, true );
                        $success_count++;
                    }
                }
            }
            // increment offset
            $offset += $per_page;
        } while ( count( $order_ids ) == $per_page );
        // while full set of results returned  (meaning there may be more results still to retrieve)
        // success message
        $this->core->plugin_notices()->add_success_message( sprintf( _n(
            '%d order updated.',
            '%s orders updated.',
            $success_count,
            'xt-woo-points-rewards'
        ), $success_count ) );
    }

    /**
     * Handles Clear all points and the logs
     *
     * @since 1.0
     */
    public function handle_delete_all_points_action( $showMessage = true ) {
        global $wpdb;
        $wpdb->query( "TRUNCATE " . $this->core->user_points_log_db_tablename );
        $wpdb->query( "TRUNCATE " . $this->core->user_points_db_tablename );
        do_action( $this->core->plugin_prefix( 'delete_all_points_action' ) );
        $message = esc_html__( 'All points and logs have been successfully deleted!', 'xt-woo-points-rewards' );
        if ( $showMessage ) {
            $this->core->plugin_notices()->add_success_message( $message );
        }
        return $message;
    }

    /**
     * Handles Factory Reset
     *
     * @since 1.0
     */
    public function handle_factory_reset_action( $includingSettings = false ) {
        global $wpdb;
        // try and avoid timeouts as best we can
        @set_time_limit( 0 );
        $this->handle_delete_all_points_action( false );
        $key_search = '%xt_woopr_%';
        $queries = array();
        $queries[] = $wpdb->prepare( "DELETE FROM `{$wpdb->usermeta}` WHERE `meta_key` LIKE (%s) AND `meta_key` NOT LIKE (%s)", $key_search, 'xt_woopr_billing_birth_date' );
        $queries[] = $wpdb->prepare( "DELETE FROM `{$wpdb->termmeta}` WHERE `meta_key` LIKE (%s)", $key_search );
        $queries[] = $wpdb->prepare( "DELETE FROM `{$wpdb->postmeta}` WHERE `meta_key` LIKE (%s)", $key_search );
        if ( $includingSettings ) {
            $queries[] = $wpdb->prepare( "DELETE FROM `{$wpdb->options}` WHERE `option_name` LIKE (%s)", $key_search );
        }
        foreach ( $queries as $query ) {
            $wpdb->query( $query );
        }
        $message = esc_html__( 'Factory Reset Succeeded!', 'xt-woo-points-rewards' );
        // success message
        $this->core->plugin_notices()->add_success_message( $message );
        return $message;
    }

    /**
     * Render the points earned / redeemed on the Edit Order totals section
     *
     * @param int $order_id the WC_Order ID
     * @since 1.0
     */
    public function render_points_earned_redeemed_info( $order_id ) {
        $points_earned = $this->core->frontend()->order->get_points_earned_for_order( $order_id );
        $points_redeemed = $this->core->frontend()->order->get_points_redeemed_for_order( $order_id );
        ?>
        <h4><?php 
        _e( 'Points', 'xt-woo-points-rewards' );
        ?></h4>
        <ul class="totals">
            <li class="left">
                <label><?php 
        _e( 'Earned:', 'xt-woo-points-rewards' );
        ?></label>
                <input type="number" disabled="disabled" id="_xt_woopr_points_earned" name="_xt_woopr_points_earned"
                       placeholder="<?php 
        _e( 'None', 'xt-woo-points-rewards' );
        ?>"
                       value="<?php 
        if ( !empty( $points_earned ) ) {
            echo esc_attr( $points_earned );
        }
        ?>" class="first"/>
            </li>
            <li class="right">
                <label><?php 
        _e( 'Redeemed:', 'xt-woo-points-rewards' );
        ?></label>
                <input type="number" disabled="disabled" id="_xt_woopr_points_redeemed" name="_xt_woopr_points_redeemed"
                       placeholder="<?php 
        _e( 'None', 'xt-woo-points-rewards' );
        ?>"
                       value="<?php 
        if ( !empty( $points_redeemed ) ) {
            echo esc_attr( $points_redeemed );
        }
        ?>" class="first"/>
            </li>
        </ul>
        <div class="clear"></div>
        <?php 
    }

    /**
     * Render the points modifier field on the create/edit coupon page
     *
     * @since 1.0
     */
    public function render_coupon_points_modifier_field() {
        $custom_attributes = ( !$this->core->access_manager()->can_use_premium_code__premium_only() ? array(
            'readonly' => true,
        ) : array() );
        $this->core->admin_messages()->premium_required( esc_html__( 'points related settings below', 'xt-woo-points-rewards' ) );
        // Unique URL
        woocommerce_wp_text_input( array(
            'id'                => '_xt_woopr_points_modifier',
            'label'             => esc_html__( 'Points Modifier', 'xt-woo-points-rewards' ),
            'description'       => esc_html__( 'Enter a percentage which modifies how points are earned when this coupon is applied. For example, enter 200% to double the amount of points typically earned when the coupon is applied.', 'xt-woo-points-rewards' ),
            'desc_tip'          => true,
            'custom_attributes' => $custom_attributes,
        ) );
    }

    /**
     * Go through variations and store the max and min points.
     *
     * @param object $product In < WC3.0 this is the variation ID.
     * @param array $children In WC3.0+ this is not passed.
     * @since 1.0.00
     * @version 1.0.0
     */
    public function variable_product_sync( $product, $children = array() ) {
        $variation_id = $product->get_id();
        $children = $product->get_children();
        $max_points_earned = '';
        $min_points_earned = '';
        $variable_points = array();
        foreach ( $children as $child ) {
            $earned = get_post_meta( $child, '_xt_woopr_points_earned', true );
            if ( $earned !== '' ) {
                $variable_points[] = $earned;
            }
        }
        if ( count( $variable_points ) > 0 ) {
            $max_points_earned = max( $variable_points );
            $min_points_earned = min( $variable_points );
        }
        update_post_meta( $variation_id, '_xt_woopr_max_points_earned', $max_points_earned );
        update_post_meta( $variation_id, '_xt_woopr_min_points_earned', $min_points_earned );
    }

    /**
     * Reset tools
     * @param array $tools
     * @return array
     */
    public function woocommerce_debug_tools( $tools ) {
        $tools['xt_woopr_delete_points'] = array(
            'name'     => esc_html__( 'XT Points and Rewards: Reset Points', 'xt-woo-points-rewards' ),
            'button'   => esc_html__( 'Delete all points and clear the logs', 'xt-woo-points-rewards' ),
            'desc'     => sprintf( esc_html__( '%1$sNote:%2$s This action will remove all customer points. This cannot be undone!', 'xt-woo-points-rewards' ), '<strong>', '</strong>' ),
            'callback' => array($this, 'handle_delete_all_points_action'),
        );
        $tools['xt_woopr_factory_reset'] = array(
            'name'     => esc_html__( 'XT Points and Rewards: Factory Reset', 'xt-woo-points-rewards' ),
            'button'   => esc_html__( 'Factory Reset', 'xt-woo-points-rewards' ),
            'desc'     => sprintf( esc_html__( '%1$sNote:%2$s This action will remove all customer points, user metas, category metas & post metas ever saved by the plugin. This cannot be undone!', 'xt-woo-points-rewards' ), '<strong>', '</strong>' ),
            'callback' => array($this, 'handle_factory_reset_action'),
        );
        return $tools;
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        $suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min' );
        wp_enqueue_style( 'xt-jquery-select2' );
        wp_enqueue_script(
            'wc-enhanced-select',
            WC()->plugin_url() . '/assets/js/admin/wc-enhanced-select' . $suffix . '.js',
            array('jquery', 'selectWoo'),
            WC()->version
        );
    }

}
