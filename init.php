<?php
/*
 * Plugin Name: Online Payments for WooCommerce
 * Plugin URI: https://wenqy.com
 * Description:给Woocommerce系统添加银联在线支付功能。
 * Version: 1.0.0
 * Author: wenqy 
 * Author URI:http://www.wenqy.com
 * Text Domain: Online Payments for WooCommerce
 */
if (! defined ( 'ABSPATH' ))
	exit (); // Exit if accessed directly

if (! defined ( 'WENQY_OL_ONLINEPAY' )) {define ( 'WENQY_OL_ONLINEPAY', 'WENQY_OL_ONLINEPAY' );} else {return;}
define('WENQY_OL_ONLINE_VERSION','1.0.0');
define('WENQY_ONLINE_ID','wc_wenqy_online_payment_gateway');
define('WENQY_ONLINE_DIR',rtrim(plugin_dir_path(__FILE__),'/'));
define('WENQY_ONLINE_URL',rtrim(plugin_dir_url(__FILE__),'/'));
load_plugin_textdomain( 'onlinepay', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/'  );
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wenqy_online_payment_gateway_plugin_edit_link' );
add_action( 'init', 'wenqy_online_payment_geteway_init' );

if(!function_exists('wenqy_online_payment_geteway_init')){
    function wenqy_online_payment_geteway_init() {
        if( !class_exists('WC_Payment_Gateway') )  return;
        require_once WENQY_ONLINE_DIR .'/class-wenqy-online-wc-payment-gateway.php';   
        $api = new WC_WENQY_ONLINE_Payment_Gateway();
        
       // $api->check_wechatpay_response();
       // 添加银联支付
       add_filter('woocommerce_payment_gateways',array($api,'woocommerce_onlinepay_add_gateway' ),10,1);
       add_action( 'wp_ajax_WENQY_ONLINE_PAYMENT_GET_ORDER', array($api, "get_order_status" ) );
       add_action( 'wp_ajax_nopriv_WENQY_ONLINE_PAYMENT_GET_ORDER', array($api, "get_order_status") );
       // 支付页面
       add_action( 'woocommerce_receipt_'.$api->id, array($api, 'receipt_page'));
       // 保存设置
       add_action( 'woocommerce_update_options_payment_gateways_' . $api->id, array ($api,'process_admin_options') ); // WC >= 2.0
       add_action( 'woocommerce_update_options_payment_gateways', array ($api,'process_admin_options') );

       add_action( 'woocommerce_thankyou', array( $api, 'thankyou_page' ) );
       // add_action( 'woocommerce_receipt_alipay_wap', array( $api, 'receipt_page' ) );

       // Payment listener/API hook
       add_action( 'woocommerce_api_' . $api->id, array( $api, 'check_onlinepay_response' ) );

       // Display Alipay Trade No. in the backend.
       //add_action( 'woocommerce_admin_order_data_after_billing_address',array( $api, 'wc_onlinepay_display_order_meta_for_admin' ) );
       //add_action( 'wp_enqueue_scripts', array ($api,'wp_enqueue_scripts') );
    }
}

function wenqy_online_payment_gateway_plugin_edit_link( $links ){
    return array_merge(
        array(
            'settings' => '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section='.WENQY_ONLINE_ID) . '">'.__( 'Settings', 'onlinepay' ).'</a>'
        ),
        $links
    );
}

 /**
 * Display Trade No. in the backend.
 * 
 * @access public
 * @param mixed $order
 * @return void
 */
function wc_onlinepay_display_order_meta_for_admin( $order ){
    $trade_no = get_post_meta( $order->id, 'Online Trade No.', true );
    if( !empty($trade_no ) ){
        echo '<p><strong>' . __( 'Online Trade No.:', 'onlinepay') . '</strong><br />' .$trade_no. '</p>';
    }
}
?>