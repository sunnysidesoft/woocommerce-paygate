<?php

class WC_Gateway_PayGateKorea extends WC_Payment_Gateway {
		public static $version = '1.1';
		
	    public function __construct() {
		    global $woocommerce;

	    	// 공통 변수 초기화
	    	// common variable init
			$this->icon 			= '';
			$this->has_fields 		= true;
			$this->log_filename = 'paygate_transactions';

	    }
	    
	    // This should called after 'init_settings()'
	    function load_settings() {
			if( $this->get_woocommerce_major_version() >= 2) {

				// Woocommerce v2.0.x style:
				$this->title 			= $this->get_option('title');
				$this->description      = $this->get_option('description');
				$this->thankyou_extra_message      = $this->get_option('thankyou_extra_message');
	
				$this->mid              = $this->get_option('mid');
				$this->is_api_auth_hash_enabled         = $this->get_option('is_api_auth_hash_enabled');
				$this->api_auth_hash         = $this->get_option('api_auth_hash');		
							
				$this->is_log_enabled         = $this->get_option('is_log_enabled');	
				add_action('woocommerce_update_options_payment_gateways_'.$this->id, array($this, 'process_admin_options'));
			}	
			else {
				// Woocommerce v1.6.x style:
				$this->title 			= $this->settings['title'];
				$this->description      = $this->settings['description'];
				$this->thankyou_extra_message      = $this->settings['thankyou_extra_message'];	
			
				$this->mid              = $this->settings['mid'];
				$this->is_api_auth_hash_enabled         = $this->settings['is_api_auth_hash_enabled'];			
				$this->api_auth_hash         = $this->settings['api_auth_hash'];
							
				$this->is_log_enabled         = $this->settings['is_log_enabled'];

				// Woocommerce에 한국 원단위 추가. 1.6버전에는 원화단위가 없음.
				// Woocommerce 1.6 doesn't have Korean WON support, so add from here.
				add_filter( 'woocommerce_currencies', array(&$this,'add_KRW_currency') );
				add_filter( 'woocommerce_currency_symbol', array(&$this,'add_KRW_currency_symbol'), 10, 2);

				// 1.6.x uses different 'woocommerce_update_options_payment_gateways' action
				add_action('woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options'));
			}
			

			// For processing PayGate payment Form.
			// WC_Payment_Gateways are initialized after $woocommerce->init_checkout() which is called at 'get_header' hook.
			// Therefore this should be called after that.
			add_action('get_header',  array( &$this, 'process_payment_action' ), 20 );
	
	    	add_action('woocommerce_receipt_'.$this->id, array(&$this, 'display_paygate_payment_form') );
	    	add_action('woocommerce_thankyou_'.$this->id, array(&$this, 'thankyou_page'));

	    }
	
		function add_KRW_currency( $currencies ) {
				$currencies['Korean'] = 'Korean Won(₩)';
				return $currencies;
		}

		function add_KRW_currency_symbol( $currency_symbol, $currency ) {
			switch( $currency ) {
				case 'Korean': $currency_symbol = '₩'; break;
			}
			return $currency_symbol;
		}
	
		function get_woocommerce_major_version() {
			global $woocommerce;
			
			$verion_code = explode('.', $woocommerce->version );
		
			if( isset($verion_code) && count($verion_code) > 0 )
				return $verion_code[0];
			else {
				return '1';
			}
		}


		function payment_fields() {
		
			 if ($this->description) : ?>
			 	<p><?php echo $this->description; ?></p><?php
			 endif;
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
								'label' => __( '활성화', 'sunnysidesoft' ),
								'default' => 'yes'
							),						
				'mid' => array(
								'title' => __( '페이게이트에서 발급받으신 상점ID(mid)를 입력하세요', 'sunnysidesoft' ),
								'type' => 'text',
								'description' => __( '일반적으로 등록하신 페이게이트 계정 ID와 동일합니다.', 'sunnysidesoft' ),
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
			</table>
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
					  // verifyNum+100 로직 실행(woocommerce DB에 주문 결제처리 완료되었다는 의미로 페이게이트쪽에 전송하는 값)
					  // Let Paygate know that payment result went through to Woocommerce DB successfully by sending verifyNum+100 
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
	    	$order = new WC_Order( $order_id );
	    	
	    	
	    	return array(
				'result' 	=> 'success',
				'redirect'	=> add_query_arg('order', $order->id, add_query_arg( 'key', $order->order_key, get_permalink(woocommerce_get_page_id('pay'))))
			);
		}
		
		/**
	     * 실제 결제에 필요한 페이게이트 <form> 출력
	     * Print out required <form> for Paygate 
	     *
	     * @access public
	     * @param int $order_id
	     * @return void
	     */
		function display_paygate_payment_form( $order_id ) {
	        global $woocommerce;
	        
	        $order = new WC_Order( $order_id );			$current_user = wp_get_current_user();
	        
	        // 결제시 표기될 상품명을 생성
	        // Create goodname based on the items in the order
	        $items = $order->get_items();
			$item_count = sizeof( $items );
			
			$goodname_max_length = 60;
			if ( $item_count == 0 ) {
				$goodname = '';	
			}
	        else if ( $item_count == 1 ) {
	        	$item = reset($items);
		        $goodname = $item['name'];
		        if( strlen($goodname) > $goodname_max_length ) {
			        $goodname = mb_substr($goodname, 0, $goodname_max_length - 3, 'UTF-8');
		        	$goodname = $goodname.'...';
		        }
		        	
	        }
	        else  {
	        	$item = reset($items);
	        	$goodname = $item['name'];
		        if( strlen($goodname) > $goodname_max_length ) {
			        $goodname = mb_substr($goodname, 0, $goodname_max_length-8, 'UTF-8');
			        $goodname = $goodname.'...';
		        }
				$goodname = sprintf( __( '%s 외 %s건' , 'sunnysidesoft'), $goodname, $item_count-1 );
	        }
	        
	        
	        // 실시간 계좌이체 경우 상품명에 특수문자(!,@,#,$,%,^,&,*등)가 포함되어 있을 경우 결제오류가 발생합니다. 상품명에 특수문자는 사용하지 말아 주시기 바랍니다.
	        // Remove all special characters((!,@,#,$,%,^,&,*,etc.) to prevent payment errors.
	        $goodname = preg_replace ('[<>#&%@\'=,`~/"_|!\?\*\$\^\(\)\[\]\{\}\\\\\+\-\:\;\.]', "",  $goodname);	
	        $action_url = add_query_arg('order', $order->id, add_query_arg( 'key', $order->order_key, get_permalink(woocommerce_get_page_id('pay')) ) );
	        
	        $receipttoname = $order->billing_first_name.' '.$order->billing_last_name;
			?>
			<div id="paygate_wrapper">
				<div id="PGIOscreen" ></div>
				<form method="post" name ="PGIOForm" id="PGIOForm" action="<?php echo $action_url;?>">
					<?php $woocommerce->nonce_field('process_payment_action'); ?>
					<fieldset>
						<input type="hidden" name="mid" value="<?php echo $this->mid;?>">
						<input type="hidden" name="charset" value="UTF-8">
						<input type="hidden" name="KR">

						<input type="hidden" name="paymethod" value="<?php echo $this->paygate_payment_method;?>">
						<input type="hidden" name="unitprice" value="<?php echo (int)$order->get_total();?>">
						<input type="hidden" name="goodcurrency" value="WON">
						
						<input type="hidden" name="goodname" value="<?php echo $goodname; ?>">
						<input type="hidden" name="receipttoname" value="<?php echo $receipttoname; //$current_user->user_firstname; ?>">
						<input type="hidden" name="receipttoemail" value="<?php echo $order->billing_email;//$current_user->user_email; ?>">

						<input type="hidden" name="ResultScreen"></textarea>
						<input type="hidden" name="replycode" value="">
						<input type="hidden" name="replyMsg" value="">
						<input type="hidden" name="hashresult" value="">
						<input type="hidden" name="mb_serial_no" value="<?php echo $order_id;?>">
						<input type="hidden" name="tid" value="">
						<?php $this->display_extra_inputs($order); ?>
					</fieldset>
					<input type="hidden" name="paygate_submit" />
				 </form>
				 <div id="paygate_submit_btn_wrapper">
	             	<a class="button" href="#" id="paygate_submit_btn"><?php _e( '재시도', 'sunnysidesoft' ); ?></a>
				 </div>
			</div>	 
			<?php
	        
	    }
	    
	    /**
	     * 페이게이트 결제가 정상 완료된 시점에서 호출되며 거래금액 검증 및 지불 완료처리를 한다.
	     * Apply payment amount verification logic based on HASH API provided by Paygate Korea.
	     *
	     * @access public
	     * @param int $order_id
	     * @return void
	     */
		function process_payment_action() {
		
			if(!isset( $_POST['paygate_submit'] ))
				return;
				
			global $woocommerce;
			
			while(1) {
					
				if( !isset( $_GET['order'] ) || !isset( $_GET['key'] ) ) {
					$woocommerce->add_error( __('잘못된 접근','sunnysidesoft') );
					break;
				}
				
				$order_id  =  absint( $_GET['order'] );
				$order_key =  $_GET['key'];
				
				// make sure it is valid request
				$woocommerce->verify_nonce( 'process_payment_action' );
			
		    	$logger = $woocommerce->logger();
		    	$is_log_enabled = $this->is_log_enabled == 'yes' ? true : false;
		    	$is_api_auth_hash_enabled = $this->is_api_auth_hash_enabled == 'yes' ? true : false;
	
				if($is_log_enabled) $logger->add($this->log_filename,'----------------------------------------------------');	    				
				if($is_log_enabled) $logger->add($this->log_filename, 'POST Params: tid='.$_POST['tid'].', hashresult='.$_POST['hashresult'].', replycode='.$_POST['replycode'].', mb_serial_no='.$_POST['mb_serial_no'].', unitprice='.$_POST['unitprice'].', goodcurrency='.$_POST['goodcurrency']);
	
				if ( $is_api_auth_hash_enabled ) {
					if($is_log_enabled) $logger->add($this->log_filename,'API Auth Hash check is currently enabled.');

					// hash 결과값 생성에 필요한 POST 입력값들의 존재여부를 체크.
					// make sure all required POST parameters are set. 
					if( !isset($_POST['tid']) || !isset($_POST['hashresult']) || !isset($_POST['replycode']) || !isset($_POST['mb_serial_no']) || !isset($_POST['unitprice']) || !isset($_POST['goodcurrency']) ) {
						
						if($is_log_enabled) $logger->add($this->log_filename, 'POST input parameter missing');
						
						$woocommerce->add_error( __('결제 처리에 필요한 입력값들이 제대로 입력되지 않아서 결제오류가 발생하였습니다.', 'sunnysidesoft') );
						break;
					}
					
					//PayGate Reference Doc: https://km.paygate.net/pages/viewpage.action?pageId=5439761
					// WON -> KRW conversion
					if( $_POST['goodcurrency'] == 'WON')
						$goodcurrency = 'KRW';
					else 
						$goodcurrency = $_POST['goodcurrency'];				
					
					// Paygate Hash Verification Logic
					// hash 문자열 형식: replycode + tid + mb_serial_no + unitprice + goodcurrency
					// hash 문자열 예제: 0000 + devbasic_2013-1-7.1340279401 +  1000  + KRW					
					$hash_string = $_POST['replycode'].$_POST['tid'].$_POST['mb_serial_no'].$_POST['unitprice'].$goodcurrency;
					$hashresult_server = hash('sha256', $this->api_auth_hash . $hash_string);
					

					if( $hashresult_server != $_POST['hashresult']) {
						
						if($is_log_enabled) $logger->add($this->log_filename,'hash match error -> server:'.$hashresult_server.' client:'.$_POST['hashresult']);
						
						$woocommerce->add_error(__('결제금액인증에 실패했습니다.', 'sunnysidesoft'));
						break;
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
		    	
				$woocommerce->cart->empty_cart();
				
				// save PayGate tid to post_meta for custom uses
				update_post_meta( $order_id, '_paygate_tid', $_POST['tid'] ); // if insert '_' on post_meta key as a prefix, the value doesn't show up in the custom field meta_box
		


				// cURL이 기본포함되지 않는 웹서버이용자를 위해, 기본적으로 thankyou_page() 함수에서 자바스크립트로 verifyReceived()를 호출해서 페이게이트에 verifyNum+100 전송하게 되어있음.
				// cURL 라이브러리를 이용해서 verifyNum+100 로직 실행(woocommerce DB에 주문 결제처리 완료되었다는 의미로 페이게이트쪽에 전송하는 값)하려면 다음 코드를 주석해제
				// Commented out: For compatibility for the server without cURL, uses javascript in thnakyou_page() instead of cURL request.				
	/*
		        $ch = curl_init(); 
		        curl_setopt($ch, CURLOPT_URL, 'https://service.paygate.net/admin/settle/verifyReceived.jsp?tid='.$_POST['tid'].'&verifyNum=100');
		        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //return the transfer as a string 
		
		        $output = curl_exec($ch); 
		        curl_close($ch);
	*/
				
				if($is_log_enabled) $logger->add($this->log_filename,'Payment completed. Redirecting to thankyou page');
				
				$redirect_url = add_query_arg('tid', $_POST['tid'], add_query_arg('key', $order->order_key, add_query_arg('order', $order->id, get_permalink(woocommerce_get_page_id('thanks')))));
				wp_safe_redirect($redirect_url);
				exit;
			}
			
			// 결제중 에러가 발생했을 경우에만 여기 코드가 실행됩니다.
			// If code reached here, it means payment error.
			$woocommerce->add_error(__('결제중에 오류가 발생하였습니다.', 'sunnysidesoft'));
			wp_safe_redirect( add_query_arg('order', $order->id, add_query_arg( 'key', $order->order_key, get_permalink(woocommerce_get_page_id('pay')))) );
			exit;
			
			
	    }
	
	}
	?>