<?php
/*
Plugin Name: WooCommerce Payment Gateway for PayGate Korea(페이게이트)
Plugin URI: http://blog.sunnysidesoft.com/paygate-plugin
Description: Extends WooCommerce with an PayGate(Korean payment gateway company) gateway. Woocommerce에서 사용가능한 페이게이트 지불 게이트웨이 모듈입니다.
Version: 1.0
Author: YoungJae Kwon, admin@sunnysidesoft.com
Author URI: http://sunnysidesoft.com/
 
	Copyright: © 2012-2013 SunnysideSoft.
	License: GNU General Public License v3.0
	License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/


add_action('wp_print_styles', 'woocommerce_gateway_paygate_style');
add_action('wp_print_scripts', 'woocommerce_gateway_paygate_script');

/*
* Enqueue css file
*/
function woocommerce_gateway_paygate_style() {
    
	wp_register_style('woocommerce_fancybox_styles', plugins_url('css/fancybox.css', __FILE__));
	wp_enqueue_style( 'woocommerce_fancybox_styles' );
	
	wp_register_style('sunnysidesoft_paygate_css', plugins_url('paygate-style.css', __FILE__));
    wp_enqueue_style( 'sunnysidesoft_paygate_css');
}

/*
* Enqueue css file
*/
function woocommerce_gateway_paygate_script() {

	global $post;

	if( is_checkout() )
	{
		$suffix 				= defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		// replace checkout.js from woocommerce to plugin's checkout.js
		wp_dequeue_script('wc-checkout');
		
		wp_enqueue_script( 'wc-checkout-mod',  plugins_url('checkout'. $suffix .'.js', __FILE__), array( 'jquery' ) ); 
		
		wp_enqueue_script( 'paygate-checkout', 'https://api.paygate.net/ajax/common/OpenPayAPI.js', array(), false, false );
		// for [IE7] support, add charset='utf-8'
/* 		echo "<script type='text/javascript' src='https://api.paygate.net/ajax/common/OpenPayAPI.js' charset='utf-8'> </script>"; */
		
		wp_register_script( 'fancybox', plugins_url('/js/fancybox.js', __FILE__) );
	    wp_enqueue_script( 'fancybox'); 
	
	    
		wp_register_script('sunnysidesoft_paygate_js', plugins_url('paygate.js', __FILE__), array( 'fancybox' ));
	    wp_enqueue_script( 'sunnysidesoft_paygate_js');
    
	}
	
}



add_action('woocommerce_after_checkout_form', 'woocommerce_gateway_paygate_print_creditcard_code', 0);
	
function woocommerce_gateway_paygate_print_creditcard_code() {
	?>
	<div style="display:none;">
		<div id="PGIOscreen"></div>  <!-- YoungJae: PayGate-->
	</div>
	
	<!-- YoungJae: PayGate, anchor for fancybox -->
	<a id="btn_paygate_purchase" href="#PGIOscreen"></a>

<?php
}	
	

add_action('plugins_loaded', 'woocommerce_gateway_paygate_init', 0);

/*
* initialize PayGate plugin for woocommerce
*/ 
function woocommerce_gateway_paygate_init() {
 
	if ( !class_exists( 'WC_Payment_Gateway' ) ) return;
 
	/**
 	 * MOD: Localisation
	 */
	load_plugin_textdomain('wc-gateway-paygate', false, dirname( plugin_basename( __FILE__ ) ) . '/languages');
    
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
			$this->id				= 'paygatekorea';
			$this->icon 			= apply_filters('woocommerce_paygatekorea_icon', '');
			$this->has_fields 		= true;
			$this->method_title     = __( 'PayGate', 'sunnysidesoft' );
			// Load the form fields.
			$this->init_form_fields();
	
			// Load the settings.
			$this->init_settings();
	
			// Define user set variables
			$this->title 			= $this->settings['title'];
			$this->description      = $this->settings['description'];
			$this->account_name     = $this->settings['account_name'];
			
			$this->mid              = $this->settings['mid'];
			$this->goodname         = $this->settings['goodname'];
			
			// Actions
			add_action('woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options'));
	    	add_action('woocommerce_thankyou_paygate', array(&$this, 'thankyou_page'));
	
	    	// Customer Emails
	    	add_action('woocommerce_email_before_order_table', array(&$this, 'email_instructions'), 10, 2);
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
					<input type="text" name="mid" value="<?php echo $this->mid;?>">
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
								'title' => __( 'Title', 'sunnysidesoft' ),
								'type' => 'text',
								'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
								'default' => __( '페이게이트', 'sunnysidesoft' )
							),
				'description' => array(
								'title' => __( '고객이 보게될 결제 페이지 메시지', 'sunnysidesoft' ),
								'type' => 'textarea',
								'description' => __( 'PayGate를 통해 신용카드로 결제합니다.', 'sunnysidesoft' ),
								
								'default' => __('Make your payment directly into our bank account. Please use your Order ID as the payment reference. Your order wont be shipped until the funds have cleared in our account.', 'sunnysidesoft')
							),
				'mid' => array(
								'title' => __( 'Merchant ID(mid) for PayGate', 'sunnysidesoft' ),
								'type' => 'text',
								'description' => __( '페이게이트에서 발급받으신 상점ID를 입력하세요', 'sunnysidesoft' ),
								'default' => 'paygateus'
							),
				'goodname' => array(
								'title' => __( '결제 상품명(goodname필드)', 'sunnysidesoft' ),
								'type' => 'text',
								'description' => __( '결제시 출력되는 상품명입니다.', 'sunnysidesoft' ),
								'default' => ''
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
	    	<h3><?php _e('PayGate Korea', 'sunnysidesoft'); ?></h3>
	    	<p><?php _e('페이게이트 플러그인 Plugin for PayGate(Korean payment gateway company)', 'sunnysidesoft'); ?></p>
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

			if ( $description = $this->get_description() )
	        	echo wpautop( wptexturize( $description ) );
	
			?><h2><?php _e('Our Details', 'sunnysidesoft') ?></h2><ul class="order_details paygate_details"><?php
	
			$fields = apply_filters('woocommerce_paygate_fields', array(
				'account_name' 	=> __('Account Name', 'sunnysidesoft')

			));
			
			foreach ($fields as $key=>$value) :
			    if(!empty($this->$key)) :
			    	echo '<li class="'.$key.'">'.$value.': <strong>'.wptexturize($this->$key).'</strong></li>';
			    endif;
			endforeach;
	
			?></ul><?php
	    }
	
	
	    /**
	     * Add content to the WC emails.
	     *
	     * @access public
	     * @param WC_Order $order
	     * @param bool $sent_to_admin
	     * @return void
	     */
	    function email_instructions( $order, $sent_to_admin ) {
	
	    	if ( $sent_to_admin ) return;
	
	    	if ( $order->payment_method !== 'paygate') return;
	
			if ( $description = $this->get_description() )
	        	echo wpautop( wptexturize( $description ) );
	
			?><h2><?php _e('Our Details', 'sunnysidesoft') ?></h2><ul class="order_details paygate_details"><?php
	
			$fields = apply_filters('woocommerce_paygate_fields', array(
				'account_name' 	=> __('Account Name', 'sunnysidesoft')
			));
	
			foreach ($fields as $key=>$value) :
			    if(!empty($this->$key)) :
			    	echo '<li class="'.$key.'">'.$value.': <strong>'.wptexturize($this->$key).'</strong></li>';
			    endif;
			endforeach;
	
			?></ul><?php
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
	

	    	$order = new WC_Order( $order_id );
	    	//$order->payment_complete();

				
			// Mark as processing
			$order->update_status('processing', __('카드결제완료', 'sunnysidesoft'));
	
			// Reduce stock levels
			$order->reduce_order_stock();
	
			// Remove cart
			$woocommerce->cart->empty_cart();
	
			// Empty awaiting payment session
			unset($_SESSION['order_awaiting_payment']);

	
			// Return thankyou redirect
			return array(
				'result' 	=> 'success',
				'redirect'	=> add_query_arg('key', $order->order_key, add_query_arg('order', $order->id, get_permalink(woocommerce_get_page_id('thanks'))))
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