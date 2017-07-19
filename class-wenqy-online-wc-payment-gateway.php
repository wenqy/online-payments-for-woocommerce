<?php
if (! defined ( 'ABSPATH' ))
	exit (); // Exit if accessed directly
/**
* 银联支付网关类
* 负责产生设置选项、定义相关功能
*/
class WC_WENQY_ONLINE_Payment_Gateway extends WC_Payment_Gateway {
	
	/* 构造函数 */
	public function __construct() {
		//支持退款
		array_push($this->supports,'refunds');

		// 网关的唯一标识，是WooCommerce区分不同支付方式的重要依据
		$this->id = WENQY_ONLINE_ID; 
		// 网关的icon图标，应该是icon的图片url，会在结账页面显示
		$this->icon = WENQY_ONLINE_URL. '/images/union.png'; 
		// 如果设置为true，网关支付需要的字段会直接显示在结账页面
		$this->has_fields = false; 
		$this->method_title = '银联支付'; // checkout option title
	    $this->method_description='银联支付，请访问<a href="http://www.wenqy.com" target="_blank">http://www.wenqy.com</a> ';
	    // 加载表单字段
		$this->init_form_fields ();
		// 加载设置
		$this->init_settings ();
		// 支持退款
		//$this->supports           = array(
 		//	'subscriptions',
 		//	'products',
 		//	'subscription_cancellation',
 		//	'subscription_reactivation',
 		//	'subscription_suspension',
 		//	'subscription_amount_changes',
 		//	'subscription_payment_method_change',
 		//	'subscription_date_changes',
 		//	'default_credit_card_form',
 		//	'refunds',
 		//	'pre-orders'
 		//);
		$this->title = $this->get_option ( 'title' );
		$this->description = $this->get_option ( 'description' );
		$this->frontUrl = get_permalink( woocommerce_get_page_id( 'myaccount' ) ); // 前台通知
		// 后台通知
		$this->notify_url             = WC()->api_request_url( 'WC_WENQY_ONLINE_Payment_Gateway' ); 
		// 定义参数
		// 签名证书路径
		define('SDK_SIGN_CERT_PATH',rtrim(plugin_dir_path(__FILE__),'/').'/certs/'.$this->get_option('onlinepay_signName'));
		// 验签证书路径
		define('SDK_VERIFY_CERT_DIR',rtrim(plugin_dir_path(__FILE__),'/').'/certs/');
		// 签名证书密码
		define('SDK_SIGN_CERT_PWD',$this->get_option('onlinepay_certPwd'));
		// 前台请求地址
		define('SDK_FRONT_TRANS_URL', $this->get_option('onlinepay_transUrl').'/gateway/api/frontTransReq.do');
		// 后台请求地址
		define('SDK_BACK_TRANS_URL', $this->get_option('onlinepay_transUrl').'/gateway/api/backTransReq.do');
		
		$lib = WENQY_ONLINE_DIR.'/sdk';
		
		include_once ($lib . '/log.class.php');
		include_once ($lib . '/SDKConfig.php');
		include_once ($lib . '/common.php');
		include_once ($lib . '/cert_util.php');
		include_once ($lib . '/acp_service.php');

	}
	// 定义我们要在后台显示的选项字段
	function init_form_fields() {
	    $this->form_fields = array (
	    	// 是否开启
	        'enabled' => array (
	            'title' => __ ( 'Enable/Disable', 'onlinepay' ),
	            'type' => 'checkbox',
	            'label' => __ ( 'Enable OnLinePay Payment', 'onlinepay' ),
	            'default' => 'no'
	        ),
	        // 标题
	        'title' => array (
	            'title' => __ ( 'Title', 'onlinepay' ),
	            'type' => 'text',
	            'description' => __ ( 'This controls the title which the user sees during checkout.', 'onlinepay' ),
	            'default' => __ ( 'OnLinePay', 'onlinepay' ),
	            'css' => 'width:400px'
	        ),
	        // 描述
	        'description' => array (
	            'title' => __ ( 'Description', 'onlinepay' ),
	            'type' => 'textarea',
	            'description' => __ ( 'This controls the description which the user sees during checkout.', 'onlinepay' ),
	            'default' => __ ( "Pay OnLinePay, many kinds of card supports", 'onlinepay' ),
	            //'desc_tip' => true ,
	            'css' => 'width:400px'
	        ),
	        // 商户代码
	        'onlinepay_merId' => array (
	            'title' => __ ( 'Application ID', 'onlinepay' ),
	            'type' => 'text',
	            'description' => __ ( 'Please enter the Application ID,If you don\'t have one, <a href="https://open.unionpay.com" target="_blank">click here</a> to get.', 'onlinepay' ),
	            'default' => '777290058110097',
	            'css' => 'width:400px'
	        ),
	        // 私钥证书名
	        'onlinepay_signName' => array (
	            'title' => __ ( 'acp sign name', 'onlinepay' ),
	            'type' => 'text',
	            'description' => __ ( 'Please enter the acp sign name,If you don\'t have one, <a href="https://open.unionpay.com" target="_blank">click here</a> to get.', 'onlinepay' ),
	            'default' => 'acp_test_sign.pfx',
	            'css' => 'width:400px'
	        ),
	        // 签名证书密码
	        'onlinepay_certPwd' => array (
	            'title' => __ ( 'sign cert pwd', 'onlinepay' ),
	            'type' => 'text',
	            'description' => __ ( 'Please enter the sign cert pwd,If you don\'t have one, <a href="https://open.unionpay.com" target="_blank">click here</a> to get.', 'onlinepay' ),
	            'default' => '000000',
	            'css' => 'width:400px'
	        ),
	        // 支付网关地址
	        'onlinepay_transUrl' => array (
	            'title' => __ ( 'union trans url', 'onlinepay' ),
	            'type' => 'text',
	            'description' => __ ( 'Please enter the union trans url on prod:https://gateway.95516.com,default value is test environment.', 'onlinepay' ),
	            'default' => 'https://101.231.204.80:5000',
	            'css' => 'width:400px'
	        ),
	        // 汇率
	        'exchange_rate'=> array (
	            'title' => __ ( 'Exchange Rate', 'onlinepay' ),
	            'type' => 'text',
	            'default'=>1,
	            'description' =>  __ ( "Please set current currency against Chinese Yuan exchange rate, eg if your currency is US Dollar, then you should enter 6.77", 'onlinepay' ),
	            'css' => 'width:80px;',
	            'desc_tip' => true
	        )
	    );
	
	}

	// 获取订单状态
	public function get_order_status() {
		$order_id = isset($_POST ['orderId'])?$_POST ['orderId']:'';
		$order = new WC_Order ( $order_id );
		$isPaid = ! $order->needs_payment ();
	
		echo array (
		    'status' =>$isPaid? 'paid':'unpaid',
		    'url' => $this->get_return_url ( $order )
		);
		
		exit;
	}

	// 接受付款和处理订单
	public function process_payment($order_id) {
		//global $woocommerce;

	    $order = new WC_Order ( $order_id );

	    // Mark as on-hold (we're awaiting the cheque)
	    //$order->update_status('on-hold', __('Awaiting cheque payment', 'woothemes'));
	    // Remove cart
	    //$woocommerce->cart->empty_cart();	  
	    // Empty awaiting payment session
	    //unset($_SESSION['order_awaiting_payment']);
	  
	    // Return thankyou redirect

	    return array (
	        'result' => 'success',
	        'redirect' => $order->get_checkout_payment_url ( true )
	    );
	}

	 /**
     * Return page of Alipay, show Alipay Trade No. 
     *
     * @access public
     * @param mixed Sync Notification
     * @return void
     */
    function thankyou_page( $order_id ) {
        if (isset ( $_POST ['signature'] )) {
				
			echo com\unionpay\acp\sdk\AcpService::validate ( $_POST ) ? '验签成功' : '验签失败';
			$orderId = $_POST ['orderId']; //其他字段也可用类似方式获取
			var_dump($orderId);
			var_dump($order_id);
			$respCode = $_POST ['respCode']; //判断respCode=00或A6即可认为交易成功

		} else {
			echo '签名为空';
		}
    }
	 /**
     * Check for Onlinepay IPN Response
     *
     * @access public
     * @return void
     */
    function check_onlinepay_response() {
    	global $woocommerce;
     	$_POST = stripslashes_deep( $_POST );

        @ob_clean();

        if ( isset ( $_POST ['signature'] ) && com\unionpay\acp\sdk\AcpService::validate ( $_POST )) {
        	$respCode = $_POST ['respCode'];
        	$orderId = $_POST ['orderId'];
        	if ($respCode == "00" || $respCode == "A6") { // 成功
        		$subOrderId = substr($orderId, 14);
        		$order = new WC_Order ( $subOrderId );
        		// Mark as on-hold (we're awaiting the cheque)
	    		//$order->update_status('on-hold', __('Awaiting cheque payment', 'woothemes'));
			    // Remove cart
			    $woocommerce->cart->empty_cart();	  
			    // Empty awaiting payment session
			    unset($_SESSION['order_awaiting_payment']);
			    if($order->needs_payment()){
			    	  // 用于退款
			          $order->payment_complete ($orderId);
			          update_post_meta( $subOrderId, 'wenqy_query_id', $_POST['queryId'] );
			    }
        	} else {
        		wp_die( 'Invalid Order ID' );
        	}
        }


    }
	// 银联支付网关加入到WooCommerce
	public  function woocommerce_onlinepay_add_gateway( $methods ) {
	    // $methods[] = $this;
	    $methods[] = 'WC_WENQY_ONLINE_Payment_Gateway';
	    return $methods;
	}

	// 退款处理
	public function process_refund( $order_id, $amount = null, $reason = ''){		
		$order = new WC_Order ($order_id );

		if(!$order){
			return new WP_Error( 'invalid_order','错误的订单' );
		}
	
		// $trade_no =$order->get_transaction_id();
		$trade_no = get_post_meta( $order->id, 'wenqy_query_id', true );
		if (empty ( $trade_no )) {
			return new WP_Error( 'invalid_order', '未找到银联支付交易号或订单未支付' );
		}
		$total = $order->get_total ();
		//$amount = $amount;
        $preTotal = $total;
        $preAmount = $amount;
        
		$exchange_rate = floatval($this->get_option('exchange_rate'));
		if($exchange_rate<=0){
			$exchange_rate=1;
		}
			
		$total = round ( $total * $exchange_rate, 2 );
		$amount = round ( $amount * $exchange_rate, 2 );
      
        $total = ( int ) ( $total  * 100);
		$amount = ( int ) ($amount * 100);
        
		if($amount<=0||$amount>$total){
			return new WP_Error( 'invalid_order',__('Invalid refused amount!' ,'onlinepay') );
		}

		// 校验时间
        $date = new DateTime ();
		$date->setTimezone ( new DateTimeZone ( 'Asia/Shanghai' ) );
		$startTime = $date->format ( 'YmdHis' );
		// 商户号
		$merId = $this->get_option('onlinepay_merId');
		// 订单号
		$newOrderId = date (( "YmdHis" ).$order->id );
		try {
			$params = array(
					//以下信息非特殊情况不需要改动
					'version' => '5.0.0',		      //版本号
					'encoding' => 'utf-8',		      //编码方式
					'signMethod' => '01',		      //签名方法
					'txnType' => '04',		          //交易类型
					'txnSubType' => '00',		      //交易子类
					'bizType' => '000201',		      //业务类型
					'accessType' => '0',		      //接入类型
					'channelType' => '07',		      //渠道类型
					//'backUrl' => com\unionpay\acp\sdk\SDK_BACK_NOTIFY_URL, //后台通知地址
					'backUrl' => urldecode($this->notify_url),
					//TODO 以下信息需要填写
					'orderId' => $newOrderId,	    //商户订单号，8-32位数字字母，不能含“-”或“_”，可以自行定制规则，重新产生，不同于原消费，此处默认取demo演示页面传递的参数
					'merId' => $merId,	        //商户代码，请改成自己的测试商户号，此处默认取demo演示页面传递的参数
					'origQryId' => $trade_no, //原消费的queryId，可以从查询接口或者通知接口中获取，此处默认取demo演示页面传递的参数
					'txnTime' => $startTime,	    //订单发送时间，格式为YYYYMMDDhhmmss，重新产生，不同于原消费，此处默认取demo演示页面传递的参数
					'txnAmt' => $amount,       //交易金额，退货总金额需要小于等于原消费
			// 		'reqReserved' =>'透传信息',            //请求方保留域，透传字段，查询、通知、对账文件中均会原样出现，如有需要请启用并修改自己希望透传的数据
				);

			com\unionpay\acp\sdk\AcpService::sign ( $params ); // 签名
			//$url = com\unionpay\acp\sdk\SDK_BACK_TRANS_URL;
			$result_arr = com\unionpay\acp\sdk\AcpService::post ( $params, SDK_BACK_TRANS_URL);
			
			if(count($result_arr)<=0) { //没收到200应答的情况
				throw new Exception("status failure" );
			}


			if (!com\unionpay\acp\sdk\AcpService::validate ($result_arr) ){
				throw new Exception ("sign failure" );
			}
			
			if ($result_arr["respCode"] == "00"){
			    //交易已受理，等待接收后台通知更新订单状态，如果通知长时间未收到也可发起交易状态查询
			    //TODO
			    return true;
			} else if ($result_arr["respCode"] == "03"
			 	    || $result_arr["respCode"] == "04"
			 	    || $result_arr["respCode"] == "05" ){
			    //后续需发起交易状态查询交易确定交易状态
			    //TODO
			    return new WP_Error ("timeout Please wait" );
			} else {
			    //其他应答码做以失败处理
			     //TODO
			     return new WP_Error("failure" );
			}
			
	
		} catch ( Exception $e ) {
			return new WP_Error( 'invalid_order',$e->getMessage ());
		}
		
		return true;
	}

	/**
	 * 订单处理付款页面
	 * @param WC_Order $order
	 */
	function receipt_page($order_id) {
	    $order = new WC_Order($order_id);
	    if(!$order||!$order->needs_payment()){
	        wp_redirect($this->get_return_url($order));
	        exit;
	    }
	    // 汇率
	    $exchange_rate = floatval($this->get_option('exchange_rate'));
		if($exchange_rate<=0){
		    $exchange_rate=1;
		}
		// 付款金额,单位：分
		$total = $order->get_total();
		$total = round ($total * $exchange_rate, 2 );
        $totalFee = ( int ) ($total * 100);
        // 校验时间
        $date = new DateTime ();
		$date->setTimezone ( new DateTimeZone ( 'Asia/Shanghai' ) );
		$startTime = $date->format ( 'YmdHis' );
		// 商户号
		$merId = $this->get_option('onlinepay_merId');
		// 订单号
		$orderId = date (( "YmdHis" ).$order->id );
		// 交易参数
	    $params = array(
			//以下信息非特殊情况不需要改动
			'version' => '5.0.0',                 //版本号
			'encoding' => 'utf-8',				  //编码方式
			'txnType' => '01',				      //交易类型
			'txnSubType' => '01',				  //交易子类
			'bizType' => '000201',				  //业务类型
			//'frontUrl' =>  com\unionpay\acp\sdk\SDK_FRONT_NOTIFY_URL,  //前台通知地址
			//'backUrl' => com\unionpay\acp\sdk\SDK_BACK_NOTIFY_URL,	  //后台通知地址
			'frontUrl' => urldecode($this->frontUrl),
			'backUrl' => urldecode($this->notify_url),	  //后台通知地址
			'signMethod' => '01',	              //签名方法
			'channelType' => '08',	              //渠道类型，07-PC，08-手机
			'accessType' => '0',		          //接入类型
			'currencyCode' => '156',	          //交易币种，境内商户固定156
			
			//TODO 以下信息需要填写
			'merId' => $merId,		//商户代码，请改自己的测试商户号，此处默认取demo演示页面传递的参数
			'orderId' => $orderId,	//商户订单号，8-32位数字字母，不能含“-”或“_”，此处默认取demo演示页面传递的参数，可以自行定制规则
			'txnTime' => $startTime,	//订单发送时间，格式为YYYYMMDDhhmmss，取北京时间，此处默认取demo演示页面传递的参数
			'txnAmt' => $totalFee,	//交易金额，单位分，此处默认取demo演示页面传递的参数
	// 		'reqReserved' =>'透传信息',        //请求方保留域，透传字段，查询、通知、对账文件中均会原样出现，如有需要请启用并修改自己希望透传的数据

			//TODO 其他特殊用法请查看 special_use_purchase.php
		);

		com\unionpay\acp\sdk\AcpService::sign ( $params );
		//$uri = com\unionpay\acp\sdk\SDK_FRONT_TRANS_URL;
		$html_form = com\unionpay\acp\sdk\AcpService::createAutoFormHtml( $params, SDK_FRONT_TRANS_URL );
		echo $html_form;
	}
}

?>