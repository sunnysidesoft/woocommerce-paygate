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

add_action('plugins_loaded', 'woocommerce_gateway_paygate_init', 0);
add_action('woocommerce_after_checkout_form', 'woocommerce_gateway_paygate_print_creditcard_code', 0);

add_action('wp_print_styles', 'woocommerce_gateway_paygate_style');
add_action('wp_print_scripts', 'woocommerce_gateway_paygate_script');


function get_woocommerce_major_version() {
	global $woocommerce;
	
	$verion_code = explode('.', $woocommerce->version );

	if( isset($verion_code) && count($verion_code) > 0 )
		return $verion_code[0];
	else {
		return '1';
	}
}

/*
* Enqueue css file
*/
function woocommerce_gateway_paygate_style() {
    
	wp_register_style('sunnysidesoft_paygate_css', plugins_url('paygate-style.css', __FILE__));
    wp_enqueue_style( 'sunnysidesoft_paygate_css');
}

/*
* Enqueue js file
*/
function woocommerce_gateway_paygate_script() {

	global $post, $woocommerce;

	if( !function_exists('is_checkout') )
		return;
		
	if( is_checkout() )
	{
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		
		// replace checkout.js from woocommerce to plugin's checkout.js
		wp_dequeue_script('wc-checkout');



		if( get_woocommerce_major_version() >= 2) {
			// woocommerce v1.6의 경우 prettyPhoto 라이브러리가 포함되어 있음
			wp_enqueue_script( 'prettyPhoto', $woocommerce->plugin_url() . '/assets/js/prettyPhoto/jquery.prettyPhoto' . $suffix . '.js', array( 'jquery' ), '3.1.5', true );
			wp_enqueue_script( 'prettyPhoto-init', $woocommerce->plugin_url() . '/assets/js/prettyPhoto/jquery.prettyPhoto.init' . $suffix . '.js', array( 'jquery' ), $woocommerce->version, true );
			wp_enqueue_style( 'woocommerce_prettyPhoto_css', $woocommerce->plugin_url() . '/assets/css/prettyPhoto.css' );

			wp_enqueue_script( 'wc-checkout-mod',  plugins_url('checkout-v2.x'. $suffix .'.js', __FILE__), array( 'jquery' ) );
		}
		else {
			// woocommerce v1.6의 경우 fancybox 라이브러리가 포함되어 있음
			wp_register_script( 'fancybox', plugins_url('/js/fancybox.js', __FILE__) );
			wp_enqueue_script( 'fancybox'); 
			wp_register_style('woocommerce_fancybox_styles', plugins_url('css/fancybox.css', __FILE__));
			wp_enqueue_style( 'woocommerce_fancybox_styles' );
			
			wp_enqueue_script( 'wc-checkout-mod',  plugins_url('checkout-v1.x'. $suffix .'.js', __FILE__), array( 'jquery' ) );			
		}

  			
		
		
		wp_enqueue_script( 'paygate-checkout', 'https://api.paygate.net/ajax/common/OpenPayAPI.js', array(), false, false );

		wp_register_script('sunnysidesoft_paygate_js', plugins_url('paygate.js', __FILE__));
	    wp_enqueue_script( 'sunnysidesoft_paygate_js');
    
	}
	// thankyou 페이지에서 verifyNum +100을 전송하기 위해 스크립트 필요.
	else if( is_page( woocommerce_get_page_id( 'thanks' ) ) ) {
		wp_enqueue_script( 'paygate-checkout', 'https://api.paygate.net/ajax/common/OpenPayAPI.js', array(), false, false );
	}
	
}



function woocommerce_gateway_paygate_print_creditcard_code() {
	?>
	<div id="PGIOscreenWrapper" style="display:none;">
		<div id="PGIOscreen"></div>
	</div>
	<?php  if( get_woocommerce_major_version() >= 2 ) : ?>
	<a id="btn_paygate_purchase" rel="prettyPhoto" href="#PGIOscreenWrapper"></a>
	<?php else : ?>
	<a id="btn_paygate_purchase" href="#PGIOscreen"></a>
	<?php endif; ?>

<?php
}	

function add_my_currency( $currencies ) {
	$currencies['Korean'] = 'Korean won(₩)';
	return $currencies;
}

function add_my_currency_symbol( $currency_symbol, $currency ) {
	switch( $currency ) {
		case 'Korean': $currency_symbol = '₩'; break;
	}
	return $currency_symbol;
}

function woocommerce_gateway_paygate_init() {
	if ( !class_exists( 'WC_Payment_Gateway' ) ) return;
 
	/**
 	 * Gateway class
 	 */
	class WC_Gateway_PayGate extends WC_Payment_Gateway {
		 /**
	     * Constructor for the gateway.
	     *
	     * @access public
	     * @return void
	     */
	    public function __construct() {
			$this->id				= 'paygatekorea'; // 주의: $order->payment_method에 저장되는 unique값이 이것.
			$this->icon 			= apply_filters('woocommerce_paygatekorea_icon', '');
			$this->has_fields 		= true;
			$this->method_title     = __( 'PayGate', 'sunnysidesoft' ); // 단순 제목타이틀
			// Load the form fields.
			$this->init_form_fields();
	
			// Load the settings.
			$this->init_settings();
	
			
			global $woocommerce;

			if( get_woocommerce_major_version() >= 2) {
				// Define user set variables for
				// Woocommerce v2.0.x style:
				$this->title 			= $this->get_option('title');
				$this->description      = $this->get_option('description');
				$this->thankyou_extra_message      = $this->get_option('thankyou_extra_message');
				$this->account_name     = $this->get_option('account_name');
				
				$this->mid              = $this->get_option('mid');
				$this->goodname         = $this->get_option('goodname');
				$this->is_api_auth_hash_enabled         = $this->get_option('is_api_auth_hash_enabled');
				$this->api_auth_hash         = $this->get_option('api_auth_hash');
				$this->error_page_url         = $this->get_option('error_page_url');			
							
				$this->is_log_enabled         = $this->get_option('is_log_enabled');	
				add_action('woocommerce_update_options_payment_gateways_'.$this->id, array($this, 'process_admin_options'));
			}	
			 else {
			 
				// Woocommerce에 한국 원단위 추가. 1.6버전에는 원화단위가 없음.
				add_filter( 'woocommerce_currencies', 'add_my_currency' );
				add_filter( 'woocommerce_currency_symbol', 'add_my_currency_symbol', 10, 2);

	 			// Define user set variables
				// Woocommerce v1.6.x style
				$this->title 			= $this->settings['title'];
				$this->description      = $this->settings['description'];
				$this->thankyou_extra_message      = $this->settings['thankyou_extra_message'];			
				$this->account_name     = $this->settings['account_name'];
				
				$this->mid              = $this->settings['mid'];
				$this->goodname         = $this->settings['goodname'];
				$this->is_api_auth_hash_enabled         = $this->settings['is_api_auth_hash_enabled'];			
				$this->api_auth_hash         = $this->settings['api_auth_hash'];
				$this->error_page_url         = $this->settings['error_page_url'];			
							
				$this->is_log_enabled         = $this->settings['is_log_enabled'];
				
				add_action('woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options'));
			 }
					
			// Actions
	    	add_action('woocommerce_thankyou_'.$this->id, array(&$this, 'thankyou_page'));
	    	
	    	$this->log_filename = 'paygate_transactions';
	    }
	
	
		/**
		* Payment form on checkout page
		*/
		function payment_fields() {
			global $woocommerce;
			
			global $current_user;
			get_currentuserinfo();
			
			?>
			<?php if ($this->description) : ?><p><?php echo $this->description; ?></p><?php endif; ?>
			<fieldset>
				<div class="payment_input_hidden">
					<input type="hidden" name="mid" value="<?php echo $this->mid;?>">
					<input type="hidden" name="charset" value="UTF-8">
					<select name="langcode">
					   <option value=""></option>
					   <option value="KR" selected="selected">KR</option>		   
					   <option value="US">US</option>
					   <option value="JP">JP</option>
					   <option value="CN">CN</option>
					</select>
					<select name="paymethod">
						<option value=""></option>
						<option value="card" selected="selected">card</option>	
						<option value="104">basic_usd</option>
						<option value="100">BASIC</option>
						<option value="101">BASIC_AUTH</option>
						<option value="102">ISP</option>
						<option value="103">VISA3D</option>
					</select>
					<input name="unitprice" value="<?php global $woocommerce; echo (int)$woocommerce->cart->total;?>" size="7">
					<select name="goodcurrency">
						<option value=""></option>
						<option value="WON" selected="selected">won</option>
						<option value="USD">US dollars</option>
					</select>
					<input name="goodname" value="<?php echo $this->goodname; ?>">
					<input name="receipttoname" value="<?php echo $current_user->user_firstname; ?>">
					<input name="receipttoemail" value="<?php echo $current_user->user_email; ?>">
				</div>
				<div class="payment_input_result">
					<textarea name="ResultScreen" cols="60" rows="2"></textarea>
					<input name="replycode" size="6" value="">
					<input name="replyMsg" size="20" value="">
					<input name="hashresult" type="hidden" value="">
					<input name="mb_serial_no" type="hidden" value="0">
					<input name="tid" type="hidden" value="">
				</div>
			</fieldset>
			
			<?php
		}
	    /**
	     * Initialise Gateway Settings Form Fields
	     *
	     * @access public
	     * @return void
	     */
	    function init_form_fields() {
	
	    	$this->form_fields = array(
				'enabled' => array(
								'title' => __( 'Enable/Disable', 'woocommerce' ),
								'type' => 'checkbox',
								'label' => __( 'Enable PayGate', 'sunnysidesoft' ),
								'default' => 'yes'
							),
				'title' => array(
								'title' => __( '결제수단 명칭', 'sunnysidesoft' ),
								'type' => 'text',
								'description' => __( '결제 수단 선택 화면에서 출력될 결제 수단의 이름을 입력하세요.', 'sunnysidesoft' ),
								'default' => __( '신용카드', 'sunnysidesoft' )
							),
				'description' => array(
								'title' => __( '결제 수단 선택시 추가 설명 입력', 'sunnysidesoft' ),
								'type' => 'textarea',
								'description' => '',
								
								'default' => __('페이게이트를 통해 신용카드로 안전하게 결제합니다.', 'sunnysidesoft')
							),
				'thankyou_extra_message' => array(
								'title' => __( '주문 완료 화면 추가 메시지', 'sunnysidesoft' ),
								'type' => 'textarea',
								'description' => '결제후 주문 완료화면에서 추가적으로 보여줄 메시지를 입력하세요.',
								
								'default' => __('신용카드 결제가 안전하게 처리되었습니다.', 'sunnysidesoft')
							),							
				'mid' => array(
								'title' => __( '페이게이트에서 발급받으신 상점ID(mid)를 입력하세요', 'sunnysidesoft' ),
								'type' => 'text',
								'description' => __( '일반적으로 등록하신 페이게이트 계정 ID와 동일합니다.', 'sunnysidesoft' ),
								'default' => ''
							),
				'goodname' => array(
								'title' => __( '결제 상품명(goodname필드)', 'sunnysidesoft' ),
								'type' => 'text',
								'description' => __( '결제화면 및 전표에 출력되는 상품명입니다.', 'sunnysidesoft' ),
								'default' => ''
							),
				'is_api_auth_hash_enabled' => array(
								'title' => __( 'PayGate API Authentication hash를 사용', 'sunnysidesoft' ),
								'type' => 'checkbox',
								'description' => __( '페이게이트의 거래금액 검증기능을 사용하시겠습니까? <a href="https://km.paygate.net/display/CS/Transaction+Hash+Verification%28SHA-256%29">설명</a>', 'sunnysidesoft' ),
								'default' => ''
							),
				'api_auth_hash' => array(
								'title' => __( 'PayGate API Authentication hash key', 'sunnysidesoft' ),
								'type' => 'text',
								'description' => __( '페이게이트의 거래금액 검증을 위해 페이게이트 관리자 페이지에서 발급받으신 hash key(salt)을 입력하세요. <a href="https://km.paygate.net/display/CS/Transaction+Hash+Verification%28SHA-256%29">설명</a>', 'sunnysidesoft' ),
								'default' => ''
							),
				'error_page_url' => array(
								'title' => __( '에러 페이지 URL', 'sunnysidesoft' ),
								'type' => 'text',
								'description' => __( '거래금액 검증 실패시 이동할 에러페이지 주소를 입력하세요. 관리자모드에서 \'페이지\'를 생성하신 후 해당 페이지의 주소를 넣어주시면 됩니다. ex)http://www.sunnysidesoft.com/payment_error', 'sunnysidesoft' ),
								'default' => 'http://www.sunnysidesoft.com/payment_error'
							),							
				
				'is_log_enabled' => array(
								'title' => __( '로그 활성화', 'sunnysidesoft' ),
								'type' => 'checkbox',
								'description' => __( 'Woocommerce의 Log 함수를 이용하여 페이게이트 트랜잭션들에 대한 정보를 기록하시겠습니까?(디버그용, 로그파일 경로: wp-content/plugins/woocommerce/logs/paygate_transactions)', 'sunnysidesoft' ),
								'default' => 'no'
							)				

				);
				
				
	    }
	
	
		/**
		 * Admin Panel Options
		 * - Options for bits like 'title' and availability on a country-by-country basis
		 *
		 * @access public
		 * @return void
		 */
		public function admin_options() {
	    	?>
	    	<h3><?php _e('페이게이트(PayGate) 지불 게이트웨이 설정', 'sunnysidesoft'); ?></h3>
	    	<p><?php _e('Developed by <a href="http://www.sunnysidesoft.com">SunnysideSoft</a>, Contact: admin@sunnysidesoft.com', 'sunnysidesoft'); ?></p>
	    	<table class="form-table">
	    	<?php
	    		// Generate the HTML For the settings form.
	    		$this->generate_settings_html();
	    	?>
			</table><!--/.form-table-->
	    	<?php
	    }
	
	
	    /**
	     * Output for the order received page.
	     *
	     * @access public
	     * @return void
	     */
	    function thankyou_page() {
			?>
			
			<p><?=$this->thankyou_extra_message; ?></p>
			
			<form name="PGIOForm"></form>
			<script language="javascript">
				
				jQuery(document).ready(function(){
					  //verifyNum+100 로직 실행(woocommerce DB에 주문 결제처리 완료되었다는 의미로 페이게이트쪽에 전송하는 값)
					  setPGIOElement('apilog','100');
					  setPGIOElement('tid','<?=$_GET['tid'];?>');
					  verifyReceived();
				});
			</script>
			<?php
	    }
	
	
	    /**
	     * Process the payment and return the result
	     *
	     * @access public
	     * @param int $order_id
	     * @return array
	     */
	    function process_payment( $order_id ) {
	    	global $woocommerce;
	    	
	    	$logger = $woocommerce->logger();
	    	$is_log_enabled = $this->is_log_enabled == 'yes' ? true : false;
	    	$is_api_auth_hash_enabled = $this->is_api_auth_hash_enabled == 'yes' ? true : false;

			if($is_log_enabled) $logger->add($this->log_filename,'----------------------------------------------------');	    				
			if($is_log_enabled) $logger->add($this->log_filename, 'POST Params: tid='.$_POST['tid'].', hashresult='.$_POST['hashresult'].', replycode='.$_POST['replycode'].', mb_serial_no='.$_POST['mb_serial_no'].', unitprice='.$_POST['unitprice'].', goodcurrency='.$_POST['goodcurrency']);

			
			if ( $is_api_auth_hash_enabled ) {
				if($is_log_enabled) $logger->add($this->log_filename,'API Auth Hash check is currently enabled.');
				// hash 결과값 생성에 필요한 POST 입력값들의 존재여부를 체크. 실패시 결제 취소
				if( !isset($_POST['tid']) || !isset($_POST['hashresult']) || !isset($_POST['replycode']) || !isset($_POST['mb_serial_no']) || !isset($_POST['unitprice']) || !isset($_POST['goodcurrency']) ) {
					
					if($is_log_enabled) $logger->add($this->log_filename, 'POST input parameter missing');
					
//					wp_die( __('결제 처리에 필요한 입력값들이 제대로 입력되지 않아서 결제오류가 발생하였습니다.', 'sunnysidesoft') );

					return array(
						'result' 	=> 'success', // 실제로는 실패지만, woocommerce코드쪽에 실패처리에 대한 코드가 없으므로 성공 처리후, 에러 페이지로 redirect.
						'redirect'	=> $this->error_page_url
					);
				}
				
				//PayGate Reference Doc: https://km.paygate.net/pages/viewpage.action?pageId=5439761
				// WON -> KRW 변환
				if( $_POST['goodcurrency'] == 'WON')
					$goodcurrency = 'KRW';
				else 
					$goodcurrency = $_POST['goodcurrency'];				
				
				
				// mb_serial_no: 워드프레스의 woocommerce 주문번호를 입력하면 되지만 woocommerce의 경우 주문완료되기 전까지 주문번호를 알 수 없는 시스템이므로 임의의 값(고정값)을넣어 보낸다.
				// mb_serial_no의 경우 필수 필드가 아니다보니 단순히 hash check를 위해 고정 값을 사용해도 문제가 없다.
				// hash 문자열 형식: replycode + tid + mb_serial_no + unitprice + goodcurrency
				// hash 문자열 예제: 0000 + devbasic_2013-1-7.1340279401 +  1000  + KRW
				
				$hash_string = $_POST['replycode'].$_POST['tid'].$_POST['mb_serial_no'].$_POST['unitprice'].$goodcurrency;
				$hashresult_server = hash('sha256', $this->api_auth_hash . $hash_string);
				
				// hash값 일치하지 않을경우 결제 취소
				if( $hashresult_server != $_POST['hashresult']) {
					
					if($is_log_enabled) $logger->add($this->log_filename,'hash match error -> server:'.$hashresult_server.' client:'.$_POST['hashresult']);
					
					return array(
						'result' 	=> 'success', // 실제로는 실패지만, woocommerce코드쪽에 실패처리에 대한 코드가 없으므로 성공 처리후, 에러 페이지로 redirect.
						'redirect'	=> $this->error_page_url
					);
				}
				else {
					if($is_log_enabled) $logger->add($this->log_filename,'API Auth Hash check succeeded');
				}
			}
			else {
				if($is_log_enabled) $logger->add($this->log_filename,'API Auth Hash check is currently disabled.');
			}

			if($is_log_enabled) $logger->add($this->log_filename,'Processing order #'.$order_id.'/'.$_POST['unitprice'].$goodcurrency);
			
	    	$order = new WC_Order( $order_id );

	    	$order->payment_complete();
			
			// save PayGate tid to post_meta
			update_post_meta( $order_id, '_paygate_tid', $_POST['tid'] ); // if insert '_' on post_meta key as a prefix, the value doesn't show up in the custom field meta_box
			
			// Remove cart
			$woocommerce->cart->empty_cart();
	
			// cURL이 기본포함되지 않는 웹서버이용자를 위해, 기본적으로 thankyou_page() 함수에서 자바스크립트로 verifyReceived()를 호출해서 페이게이트에 verifyNum+100 전송하게 되어있음.
			// cURL 라이브러리를 이용해서 verifyNum+100 로직 실행(woocommerce DB에 주문 결제처리 완료되었다는 의미로 페이게이트쪽에 전송하는 값)하려면 다음 코드를 주석해제
/*
	        $ch = curl_init(); 
	        curl_setopt($ch, CURLOPT_URL, 'https://service.paygate.net/admin/settle/verifyReceived.jsp?tid='.$_POST['tid'].'&verifyNum=100');
	        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //return the transfer as a string 
	
	        $output = curl_exec($ch); 
	        curl_close($ch);
*/
			
			if($is_log_enabled) $logger->add($this->log_filename,'Payment completed. Redirecting to thankyou page');
			
			// Return thankyou redirect
			return array(
				'result' 	=> 'success',
				'redirect'	=> add_query_arg('tid', $_POST['tid'], add_query_arg('key', $order->order_key, add_query_arg('order', $order->id, get_permalink(woocommerce_get_page_id('thanks')))))
			);

	    }
	
	}
	
	/**
 	* Add the Gateway to WooCommerce
 	**/
	function woocommerce_add_gateway_paygate($methods) {
		$methods[] = 'WC_Gateway_PayGate';
		return $methods;
	}
	
	add_filter('woocommerce_payment_gateways', 'woocommerce_add_gateway_paygate' );
} 