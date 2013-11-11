<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WC_Gateway_PayGate_bank extends WC_Gateway_PayGate {
        
    function __construct(){
		parent::__construct();    
        
        $this->id					= 'paygatekorea_bank';
        $this->paygate_payment_method = '4';
        $this->method_title			= __('Paygate(실시간계좌이체)', 'sunnysidesoft');
        $this->method_description   = 'paygatekorea_bank';
        
        // 아래 세 함수는 id, method_title 등이 모두 설정된 후에 호출되어야함
        $this->init_form_fields();
		$this->init_settings();			
        $this->load_settings(); 
	}
	

    function init_form_fields() {
        parent::init_form_fields();
                
        $this->form_fields = array_merge( $this->form_fields, array(
		        'title' => array(
								'title' => __( '결제수단 명칭', 'sunnysidesoft' ),
								'type' => 'text',
								'description' => __( '결제 수단 선택 화면에서 출력될 결제 수단의 이름을 입력하세요.', 'sunnysidesoft' ),
								'default' => __( '실시간계좌이체', 'sunnysidesoft' )
							),
				'description' => array(
								'title' => __( '결제 수단 선택시 추가 설명 입력', 'sunnysidesoft' ),
								'type' => 'textarea',
								'description' => '',
								'default' => __('실시간계좌이체를 이용하여 결제합니다(인터넷 익스플로러만 가능).', 'sunnysidesoft')
							),
				'thankyou_extra_message' => array(
								'title' => __( '주문 완료 화면 추가 메시지', 'sunnysidesoft' ),
								'type' => 'textarea',
								'description' => __( '결제후 주문 완료화면에서 추가적으로 보여줄 메시지를 입력하세요.', 'sunnysidesoft' ),
								'default' => __('실시간 계좌이체가 안전하게 처리되었습니다.', 'sunnysidesoft')
							)
            ));
    }
        

    function display_extra_inputs( $order ) {
    ?>
     <input type="hidden" name="socialnumber" value="" />
  	<?php
    }
}
