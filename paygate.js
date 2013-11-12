function getPGIOresult() {
    verifyReceived(getPGIOElement("tid"),'callbacksuccess','callbackfail');
}
    
function callbacksuccess() {
	var replycode = getPGIOElement('replycode');
	if (replycode == '0000') {
		jQuery('form[name=PGIOForm]').submit();
		
	} else {
		alert("결제가 실패했습니다. 다시 시도해 주세요.\n" +"에러코드:" + replycode + "\n에러메시지:"+ getPGIOElement('replyMsg'));
		jQuery('#paygate_submit_btn').css('visibility','visible');				
	}
}
 
function callbackfail() {
	alert("결제시스템에 오류가 발생하여 결제에 실패했습니다. 다시 시도해 주세요.\n" +"에러코드:" + replycode + "\n에러메시지:"+ getPGIOElement('replyMsg'));
}

jQuery(document).ready(function($) {

	// block user input until paygate module is fully loaded
	jQuery('#content').block({message: null, overlayCSS: {background: '#fff url(' + woocommerce_params.ajax_loader_url + ') no-repeat center', opacity: 0.6}});
	
	jQuery('#paygate_submit_btn').on('click', function() {
		jQuery('form[name=PGIOForm]').submit();
	});
	
	jQuery('form[name=PGIOForm]').submit(function() {

		var replycode = getPGIOElement('replycode');
		if( replycode != '0000') {

			// 기존 결제 실패시 혹은 초기결제시 페이게이트 결제코드 진행
			jQuery('input[name=tid], input[name=replycode], input[name=replyMsg]').val(''); //reset tid, replycode, replyMsg fields are required on retry.
			jQuery('#paygate_submit_btn').css('visibility','hidden');
			doTransaction(document.PGIOForm);
										
			return false;
		}
		else {
			//페이게이트 결제 성공시 폼전송 진행
			//Proceed form sending after the successful payment.
			return true;
		}
			
	});
	
	// start transaction
	jQuery('form[name=PGIOForm]').submit();
});

jQuery(window).load(function($) {
	jQuery('#content').unblock();
});