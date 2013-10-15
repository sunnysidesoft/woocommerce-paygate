
function getPGIOresult() {
    verifyReceived(getPGIOElement("tid"),'callbacksuccess','callbackfail');
}
    
function callbacksuccess() {
	var replycode = getPGIOElement('replycode');
	if (replycode == '0000') {
	
		//결제성공후 getPGIOresult()를 호출하고 상점쪽에 결제정보가 저장되는중에 alert 호출 등 사용자의 요청을 기다리는 소스는 삽입하지 마십시오.
		//고객이 alert('결제성공')이라는 메세지만 보고 브라우저를 종료시에 결제정보가 상점측에 정상적으로 전달될 수 없습니


		jQuery('form[name=PGIOForm]').submit();
		
	} else {
		alert("결제가 실패했습니다. 다시 시도해 주세요.\n" +"에러코드:" + replycode + "\n에러메시지:"+ getPGIOElement('replyMsg'));
	}
}
 
function callbackfail() {
	alert("결제시스템에 오류가 발생하여 결제에 실패했습니다. 다시 시도해 주세요.\n" +"에러코드:" + replycode + "\n에러메시지:"+ getPGIOElement('replyMsg'));
}