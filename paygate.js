// variable for check if transaction is successful
// this will be used in 'checkout.js'
var g_transaction_success = false;

function getPGIOresult() {
    verifyReceived(getPGIOElement("tid"),'callbacksuccess','callbackfail');
    		
    //var replycode = document.PGIOForm.elements['replycode'].value;
    //var replyMsg = document.PGIOForm.elements['replyMsg'].value;
    //displayStatus(getPGIOElement('ResultScreen'));

}
    
function callbacksuccess() {
	var replycode = getPGIOElement('replycode');
	var hashresult  = getPGIOElement('hashresult');
/* 	alert (hashresult); */

	if (replycode == '0000') {
 		alert("결제 성공: 확인버튼을 누르신 후 잠시 기다려주세요."); 
		g_transaction_success = true;
		jQuery('#place_order').trigger('click');
		
	} else {
		alert("결제가 실패했습니다. 다시 시도해 주세요.\n" +"에러코드:" + replycode + "\n에러메시지:"+ getPGIOElement('replyMsg'));
	}
}
 
function callbackfail() {
	alert("결제시스템에 오류가 발생하여 결제에 실패했습니다. 다시 시도해 주세요.\n" +"에러코드:" + replycode + "\n에러메시지:"+ getPGIOElement('replyMsg'));
}


jQuery(document).ready(function($) {
	
	//주의: 이쪽 부분은 order-review 부분에 의해 ajax로 로딩되서 여기서 셋팅해봐야 다시 초기화 된다


});