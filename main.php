<?php
/*
Plugin Name: 페이게이트(PayGate) 우커머스 결제연동 플러그인
Plugin URI: http://sunnysidesoft.com/woocommerce-paygate
Description: Extends WooCommerce with an PayGate(Korean payment gateway company) gateway. Woocommerce에서 사용가능한 페이게이트 지불 게이트웨이 모듈입니다. 
Version: 1.0
Author: SunnysideSoft, admin@sunnysidesoft.com
Author URI: http://sunnysidesoft.com/
 
	Copyright: © 2012-2013 SunnysideSoft.
	License: GNU General Public License v3.0
	License URI: http://www.gnu.org/licenses/gpl-3.0.html
	
*/

add_action('plugins_loaded', 'ss_gateway_paygate_init', 0);

add_action('wp_print_styles', 'ss_gateway_paygate_style');
add_action('wp_print_scripts', 'ss_gateway_paygate_script');

define( SS_PAYGATE_PLUGIN_DIR,  plugin_dir_url ( __FILE__ ) ); 

/*
* Enqueue css file
*/
function ss_gateway_paygate_style() {
    
	wp_register_style('ss_paygate_css', SS_PAYGATE_PLUGIN_DIR.'paygate-style.css');
    wp_enqueue_style( 'ss_paygate_css');
}

/*
* Enqueue js file
*/
function ss_gateway_paygate_script() {

	global $post, $woocommerce;

	if( !function_exists('is_checkout') )
		return;
		
	if( is_checkout() )
	{
		wp_enqueue_script( 'paygate-checkout', 'https://api.paygate.net/ajax/common/OpenPayAPI.js', array(), false, false );

		wp_register_script('ss_paygate_js', SS_PAYGATE_PLUGIN_DIR.'paygate.js');
	    wp_enqueue_script( 'ss_paygate_js');    
	}
	// thankyou 페이지에서 verifyNum +100을 전송하기 위해 스크립트 필요.
	else if( is_page( woocommerce_get_page_id( 'thanks' ) ) ) {
		wp_enqueue_script( 'paygate-checkout', 'https://api.paygate.net/ajax/common/OpenPayAPI.js', array(), false, false );
	}
	
}


function ss_gateway_paygate_init() {
	if ( !class_exists( 'WC_Payment_Gateway' ) ) return;
	
	require_once('class-wc-gateway-paygate.php');
	
	require_once('class-wc-gateway-paygate-card.php');
	require_once('class-wc-gateway-paygate-mobile.php');
	require_once('class-wc-gateway-paygate-bank.php');		
 	/**
 	* Add the Paygate Gateways to WooCommerce
 	**/
	function woocommerce_add_gateway_paygate($methods) {
		$methods[] = 'WC_Gateway_PayGate_card';
		$methods[] = 'WC_Gateway_PayGate_mobile';
		$methods[] = 'WC_Gateway_PayGate_bank';
		return $methods;
	}
	add_filter('woocommerce_payment_gateways', 'woocommerce_add_gateway_paygate' );
} 

