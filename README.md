# Wordpress Woocommerce용 페이게이트(PayGate) 결제모듈


## 버전 히스토리

### v1.1, 2013-11-14 

버전별 호환성과 결제 안정성이 향상되었으며 여전히 Woocommerce 1.6.x, 2.0.x 버전 모두를 지원합니다.

- IE7+, Chrome, Firefox, Safari 테스트완료
- 모달 다이얼로그 방식의 결제를 버리고 우커머스 표준에 맞지 않게 2단계 결제(체크아웃 - 결제)방식으로 변경.
- Woocommerce 내부의 checkout.js 을 바꿔치기하는 부분 제거(업데이트 호환성 향상)
- 신용카드 결제시 고객 영수증에 표시되는 상품 이름을 상품이름 기반으로 동적으로 생성. ex) 상품명: "사과 1Kg 외 3건"

- 기존 결제 에러페이지를 따로 두던것을 삭제하고, 결제 페이지 상단에 추가 메시지 형태로 결제에러 표시.


### v1.0, 2013-10-15

- 우커머스 2.0, 1.6을 모두 지원
- 보안성 향상을 위해 거래금액 인증기능 기능을 추가
- 관리자 옵션을 추가


## 특징

* 국내에서 Non-Active X 결제가 가능한것으로 유명한 [페이게이트](http://www.paygate.net) 전용 결제모듈이며, 워드프레스용 쇼핑몰 플러그인 우커머스(Woocommerce) 전용으로 개발된 플러그인입니다.

* 버전 호환성: 현재 출시되어있는 메이저 버전 2.0.x, 1.6.x 두가지 모두를 지원합니다. 추후 우커머스의 새로운 버전이 출시되더라도 지속적인 업그레이드를 통해 서포트 될 예정입니다.

* 플러그인을 활성화 하신후 페이게이트에서 발급받으신 상점아이디(mid)만 입력하면 곧바로 결제가 가능할 정도로 손쉬운 설정이 가능합니다.

* 페이게이트에서 결제API에서 지원하는 [결제금액검증 기능](https://km.paygate.net/display/CS/Transaction+Hash+Verification%28SHA-256%29)을 손쉽게 설정가능합니다. 따라서 클라이언트쪽에서 자바스크립트를 이용하여 금액 위조 등의 악의적 요청을 하더라도 서버측에서 이중으로 점검 후 주문 승인을 내기때문에 안전한 결제가 가능합니다.

* 플러그인 내부의 CSS(paygate-style.css) 수정을 통해 자유롭게 UI 디자인을 변경 가능합니다.

* 자세한 설치방법은 아래 메뉴얼을 참고해 주세요.

## 라이센스

GNU General Public License v3.0 을 따릅니다.

## 개발자에게 기부하기(Donation)
Developed by [SunnysideSoft](http://www.sunnysidesoft.com)

[Donate](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=NTFKG9Q6EJ9RJ)

## 문의 사항
admin@sunnysidesoft.com으로 이메일 문의 주세요.

## 설치 메뉴얼

1. 결제모듈 플러그인 설치
 	- 다운로드 받은 플러그인 파일을 ftp를 통해 플러그인 디렉토리에 업로드하거나, 워드프레스 플러그인 페이지를 통해 업로드하여 설치한 후 활성화(activate) 한다.

2. 우커머스 일반 설정(Woocommerce General Setting)
	- 워드프레스 관리자페이지의 Woocommerce - 설정(Settings) - 일반(General) 메뉴로 이동한다.
 	- 통화(Currency)를 Korean Won 으로 변경한다.
 	
3. 결제 모듈 설정(Payment Gateway Setting)
	 - 워드프레스 관리자페이지의 Woocommerce - 설정(Settings) - 지불게이트웨이(Payment Gateways) 메뉴로 이동 후 PayGate를 클릭하여 상세설정페이지로 이동한다.
	 - 'Enable PayGate'설정을 체크하고 결제 페이지에 출력될 메시지들을 원하는 대로 입력한다.
 	 - 페이게이트에서 발급받은 mid를 입력한다.
 	 - 거래 금액 검증기능을 사용하려면 페이게이트 관리자 페이지에서 PayGate API Authentication hash 서비스를 신청 후 API key를 발급받은 후([발급 방법](https://km.paygate.net/display/CS/Transaction+Hash+Verification%28SHA-256%29)), 해당 키를 입력한다.
 	 
 	 - 처음 설치시에는 테스트 거래를 하면서 동작여부 점검이 필요하므로 로그 활성화 기능을 켜놓는 것을 추천한다. 주문 금액, 주문 번호, 시간, 요청 파라메터 등이 다음 경로 wp-content/plugins/woocommerce/logs/paygate_transactions에 존재하는 로그파일에 저장된다.
 	 

3. 결제 테스트
	- 페이게이트의 경우 실거래를 통해서만 테스트가능하기 때문에 실제 카드로 테스트 결제가 잘되는지 확인 후 페이게이트 관리자페이지에서 결제를 취소하는 방식으로 진행해야 한다.
 	 