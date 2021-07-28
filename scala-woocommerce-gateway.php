<?php
/*
Plugin Name: Scala Woocommerce Gateway
Plugin URI: https://github.com/scala-network/scala-woocommerce
Description: Extends WooCommerce by adding a Scala Gateway
Version: 3.0.5
Tested up to: 5.7.2
Author: mosu-forge, SerHack, Scala Network Project Team
Author URI: https://scalaproject.io
*/
// This code isn't for Dark Net Markets, please report them to Authority!

defined( 'ABSPATH' ) || exit;

// Constants, you can edit these if you fork this repo
define('MONERO_GATEWAY_MAINNET_EXPLORER_URL', 'https://xmrchain.net/');
define('MONERO_GATEWAY_TESTNET_EXPLORER_URL', 'https://testnet.xmrchain.com/');
define('MONERO_GATEWAY_ADDRESS_PREFIX', 0x9b);
define('MONERO_GATEWAY_ADDRESS_PREFIX_INTEGRATED', 0x99cb);
define('MONERO_GATEWAY_ATOMIC_UNITS', 2);
define('MONERO_GATEWAY_ATOMIC_UNIT_THRESHOLD', 10); // Amount under in atomic units payment is valid
define('MONERO_GATEWAY_DIFFICULTY_TARGET', 120);

// Do not edit these constants
define('MONERO_GATEWAY_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MONERO_GATEWAY_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MONERO_GATEWAY_ATOMIC_UNITS_POW', pow(10, MONERO_GATEWAY_ATOMIC_UNITS));
define('MONERO_GATEWAY_ATOMIC_UNITS_SPRINTF', '%.'.MONERO_GATEWAY_ATOMIC_UNITS.'f');

// Include our Gateway Class and register Payment Gateway with WooCommerce
add_action('plugins_loaded', 'scala_init', 1);
function scala_init() {

    // If the class doesn't exist (== WooCommerce isn't installed), return NULL
    if (!class_exists('WC_Payment_Gateway')) return;

    // If we made it this far, then include our Gateway Class
    require_once('include/class-scala-gateway.php');

    // Create a new instance of the gateway so we have static variables set up
    new Scala_Gateway($add_action=false);

    // Include our Admin interface class
    require_once('include/admin/class-scala-admin-interface.php');

    add_filter('woocommerce_payment_gateways', 'scala_gateway');
    function scala_gateway($methods) {
        $methods[] = 'Scala_Gateway';
        return $methods;
    }

    add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'scala_payment');
    function scala_payment($links) {
        $plugin_links = array(
            '<a href="'.admin_url('admin.php?page=scala_gateway_settings').'">'.__('Settings', 'scala_gateway').'</a>'
        );
        return array_merge($plugin_links, $links);
    }

    add_filter('cron_schedules', 'scala_cron_add_one_minute');
    function scala_cron_add_one_minute($schedules) {
        $schedules['one_minute'] = array(
            'interval' => 60,
            'display' => __('Once every minute', 'scala_gateway')
        );
        return $schedules;
    }

    add_action('wp', 'scala_activate_cron');
    function scala_activate_cron() {
        if(!wp_next_scheduled('scala_update_event')) {
            wp_schedule_event(time(), 'one_minute', 'scala_update_event');
        }
    }

    add_action('scala_update_event', 'scala_update_event');
    function scala_update_event() {
        Scala_Gateway::do_update_event();
    }

    add_action('woocommerce_thankyou_'.Scala_Gateway::get_id(), 'scala_order_confirm_page');
    add_action('woocommerce_order_details_after_order_table', 'scala_order_page');
    add_action('woocommerce_email_after_order_table', 'scala_order_email');

    function scala_order_confirm_page($order_id) {
        Scala_Gateway::customer_order_page($order_id);
    }
    function scala_order_page($order) {
        if(!is_wc_endpoint_url('order-received'))
            Scala_Gateway::customer_order_page($order);
    }
    function scala_order_email($order) {
        Scala_Gateway::customer_order_email($order);
    }

    add_action('wc_ajax_scala_gateway_payment_details', 'scala_get_payment_details_ajax');
    function scala_get_payment_details_ajax() {
        Scala_Gateway::get_payment_details_ajax();
    }

    add_filter('woocommerce_currencies', 'scala_add_currency');
    function scala_add_currency($currencies) {
        $currencies['Scala'] = __('Scala', 'scala_gateway');
        return $currencies;
    }

    add_filter('woocommerce_currency_symbol', 'scala_add_currency_symbol', 10, 2);
    function scala_add_currency_symbol($currency_symbol, $currency) {
        switch ($currency) {
        case 'Scala':
            $currency_symbol = 'XLA';
            break;
        }
        return $currency_symbol;
    }

    if(Scala_Gateway::use_scala_price()) {

        // This filter will replace all prices with amount in Scala (live rates)
        add_filter('wc_price', 'scala_live_price_format', 10, 3);
        function scala_live_price_format($price_html, $price_float, $args) {
            $price_float = wc_format_decimal($price_float);
            if(!isset($args['currency']) || !$args['currency']) {
                global $woocommerce;
                $currency = strtoupper(get_woocommerce_currency());
            } else {
                $currency = strtoupper($args['currency']);
            }
            return Scala_Gateway::convert_wc_price($price_float, $currency);
        }

        // These filters will replace the live rate with the exchange rate locked in for the order
        // We must be careful to hit all the hooks for price displays associated with an order,
        // else the exchange rate can change dynamically (which it should for an order)
        add_filter('woocommerce_order_formatted_line_subtotal', 'scala_order_item_price_format', 10, 3);
        function scala_order_item_price_format($price_html, $item, $order) {
            return Scala_Gateway::convert_wc_price_order($price_html, $order);
        }

        add_filter('woocommerce_get_formatted_order_total', 'scala_order_total_price_format', 10, 2);
        function scala_order_total_price_format($price_html, $order) {
            return Scala_Gateway::convert_wc_price_order($price_html, $order);
        }

        add_filter('woocommerce_get_order_item_totals', 'scala_order_totals_price_format', 10, 3);
        function scala_order_totals_price_format($total_rows, $order, $tax_display) {
            foreach($total_rows as &$row) {
                $price_html = $row['value'];
                $row['value'] = Scala_Gateway::convert_wc_price_order($price_html, $order);
            }
            return $total_rows;
        }

    }

    add_action('wp_enqueue_scripts', 'scala_enqueue_scripts');
    function scala_enqueue_scripts() {
        if(Scala_Gateway::use_scala_price())
            wp_dequeue_script('wc-cart-fragments');
        if(Scala_Gateway::use_qr_code())
            wp_enqueue_script('scala-qr-code', MONERO_GATEWAY_PLUGIN_URL.'assets/js/qrcode.min.js');

        wp_enqueue_script('scala-clipboard-js', MONERO_GATEWAY_PLUGIN_URL.'assets/js/clipboard.min.js');
        wp_enqueue_script('scala-gateway', MONERO_GATEWAY_PLUGIN_URL.'assets/js/scala-gateway-order-page.js');
        wp_enqueue_style('scala-gateway', MONERO_GATEWAY_PLUGIN_URL.'assets/css/scala-gateway-order-page.css');
    }

    // [scala-price currency="USD"]
    // currency: BTC, GBP, etc
    // if no none, then default store currency
    function scala_price_func( $atts ) {
        global  $woocommerce;
        $a = shortcode_atts( array(
            'currency' => get_woocommerce_currency()
        ), $atts );

        $currency = strtoupper($a['currency']);
        $rate = Scala_Gateway::get_live_rate($currency);
        if($currency == 'BTC')
            $rate_formatted = sprintf('%.8f', $rate / 1e8);
        else
            $rate_formatted = sprintf('%.5f', $rate / 1e8);

        return "<span class=\"scala-price\">1 XLA = $rate_formatted $currency</span>";
    }
    add_shortcode('scala-price', 'scala_price_func');


    // [scala-accepted-here]
    function scala_accepted_func() {
        return '<img src="'.MONERO_GATEWAY_PLUGIN_URL.'assets/images/scala-accepted-here.png" />';
    }
    add_shortcode('scala-accepted-here', 'scala_accepted_func');

}

register_deactivation_hook(__FILE__, 'scala_deactivate');
function scala_deactivate() {
    $timestamp = wp_next_scheduled('scala_update_event');
    wp_unschedule_event($timestamp, 'scala_update_event');
}

register_activation_hook(__FILE__, 'scala_install');
function scala_install() {
    global $wpdb;
    require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
    $charset_collate = $wpdb->get_charset_collate();

    $table_name = $wpdb->prefix . "scala_gateway_quotes";
    if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
        $sql = "CREATE TABLE $table_name (
               order_id BIGINT(20) UNSIGNED NOT NULL,
               payment_id VARCHAR(95) DEFAULT '' NOT NULL,
               currency VARCHAR(6) DEFAULT '' NOT NULL,
               rate BIGINT UNSIGNED DEFAULT 0 NOT NULL,
               amount BIGINT UNSIGNED DEFAULT 0 NOT NULL,
               paid TINYINT NOT NULL DEFAULT 0,
               confirmed TINYINT NOT NULL DEFAULT 0,
               pending TINYINT NOT NULL DEFAULT 1,
               created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
               PRIMARY KEY (order_id)
               ) $charset_collate;";
        dbDelta($sql);
    }

    $table_name = $wpdb->prefix . "scala_gateway_quotes_txids";
    if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
        $sql = "CREATE TABLE $table_name (
               id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
               payment_id VARCHAR(95) DEFAULT '' NOT NULL,
               txid VARCHAR(64) DEFAULT '' NOT NULL,
               amount BIGINT UNSIGNED DEFAULT 0 NOT NULL,
               height MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
               PRIMARY KEY (id),
               UNIQUE KEY (payment_id, txid, amount)
               ) $charset_collate;";
        dbDelta($sql);
    }

    $table_name = $wpdb->prefix . "scala_gateway_live_rates";
    if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
        $sql = "CREATE TABLE $table_name (
               currency VARCHAR(6) DEFAULT '' NOT NULL,
               rate BIGINT UNSIGNED DEFAULT 0 NOT NULL,
               updated TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
               PRIMARY KEY (currency)
               ) $charset_collate;";
        dbDelta($sql);
    }
}
