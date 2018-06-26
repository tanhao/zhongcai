<?php
namespace Api\Controller;
use Lib\Enum\CodeEnum;
use Lib\Enum\CacheEnum;

class PayController extends BaseController {

	/**
	 *HTTP请求
	 */
	protected function payPostHttp($reqUrl,$postData=array(),$timeout=5,$header=""){
		$defaultHeader = "User-Agent:Mozilla/5.0 (Windows; U; Windows NT 5.1; zh-CN; rv:1.9.2.12) Gecko/20101026 Firefox/3.6.12\r\n";
		$defaultHeader.="Accept:text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8\r\n";
		$defaultHeader.="Accept-language: zh-cn,zh;q=0.5\r\n";
		$defaultHeader.="Accept-Charset: GB2312,utf-8;q=0.7,*;q=0.7\r\n";
		$post_string = http_build_query($postData);
		$header =$defaultHeader."Content-length: ".strlen($post_string);
		$opts = array(
				'http'=>array(
						'protocol_version'=>'1.0',//http协议版本(若不指定php5.2系默认为http1.0)
						'method'=>"POST",//获取方式
						'timeout' => $timeout ,//超时时间
						'header'=> $header,
						'content'=> $post_string)
		);
		$context = stream_context_create($opts);
		return  @file_get_contents($reqUrl,false,$context);
	}
	
	/**	新的支付接口参数：
	 *	uid	商户uid	string(24)	必填。您的商户唯一标识，注册后在设置里获得。一个24位字符串
	 *	price	价格	float	必填。单位：元。精确小数点后2位
	 *	istype	支付渠道	int	必填。10001：支付宝；20001：微信支付
	 *	notify_url	通知回调网址	string(255)	必填。用户支付成功后，我们服务器会主动发送一个post消息到这个网址。由您自定义。不要urlencode。例：http://www.aaa.com/aacallback
	 *	return_url	跳转网址	string(255)	必填。用户支付成功后，我们会让用户浏览器自动跳转到这个网址。由您自定义。不要urlencode。例：http://www.aaa.com/aaareturn
	 *	format	跳转说明	string(255)	web跳转我们的支付页，json(默认)获取json页支付信息，可自定义支付页面，return_url参数无效
	 *	orderid	商户自定义订单号	string(50)	必填。我们会据此判别是同一笔订单还是新订单。我们回调时，会带上这个参数。例：1525921853
	 *	orderuid	商户自定义客户号	string(100)	选填。我们会显示在您后台的订单列表中，方便您看到是哪个用户的付款，方便后台对账。强烈建议填写。可以填用户名，也可以填您数据库中的用户uid。例：xxx, xxx@aaa.com
	 *	goodsname	商品名称	string(100)	选填。您的商品名称，用来显示在后台的订单名称。如未设置，我们会使用后台商品管理中对应的商品名称
	 *	key 秘钥
	 */
	protected function paySecondGet($pay_type,$merchant_order_sn=FALSE,$price=0){
		$pay_url = getConfig("pay_url");
		$getApp_id = getConfig("app_id");
		$notify_url = getConfig('notify_url');
		$return_url = getConfig('return_url');
		$getPayToken = getConfig("app_secret");
		$zftype = ($pay_type == 'wechat') ? 20001 : 10001;
		$httpFront = 'http://'.C('DOMAIN');
		//测试数据
		//if(!$pay_url)
		$pay_url = "https://www.aw5880.cn/pay/action";
		if(!$notify_url) $notify_url = $httpFront.'/api/public/payCallback';
		else $notify_url = $httpFront.$notify_url;
		if(!$return_url) $return_url = $httpFront."/callback.php";
		else $return_url = $httpFront.$return_url;
		
//	$getApp_id = "57752125";
//	$getPayToken = "d334cb030f2935c306ed47454a8c18dd";
		//$merchant_order_sn ='1322342332'.rand(10000, 90000);
	
		//echo $getApp_id.'--'.$getPayToken;	
		//接口数据
		$post = array("uid"=> $getApp_id);
		$post["price"] = $price;
		$post["istype"] = intval($zftype);
		$post["notify_url"] = $notify_url;
		$post["return_url"] = $return_url;
		$post["orderid"] = $merchant_order_sn;
		$post["orderuid"] = $merchant_order_sn;
		$post["goodsname"] = "test";
		$post["key"] = md5($post["goodsname"] . $post["istype"] . $post["notify_url"] . $post["orderid"] . $post["orderuid"] . $post["price"] . $post["return_url"] . $getPayToken . $post["uid"]);
		// md5(goodsname + istype + notify_url + orderid + orderuid + price + return_url + token + uid);
		$post["format"] = "json";
		//print_r($post);
		$resultJson = $this->payPostHttp($pay_url, $post);
		return !empty($resultJson) ? $resultJson : '';
	}
	
    /**
     * @desc 线上充值
     * @param recharge_cash 充值金额
     * @param pay_type      支付方式：wechat-微信，alipay-支付宝
     * @param nonce_str     随机字符串（32位）
     * @param sign          签名
     * @param client_id     客户端ID
     * @param token         用户TOKEN
     * @return int
     */
    public function onlineRecharge() {
        $recharge_cash = I('get.recharge_cash');
        $pay_type = I('get.pay_type');
        $nonce_str = I('get.nonce_str');
        $getCurrentToken = I('get.token');
        // 验证参数
        if (empty($recharge_cash) || !in_array($pay_type, ['wechat','alipay']) || empty($nonce_str)) {
            $this->ajaxReturn(output(CodeEnum::PARAM_ERROR));
        }
        if (!is_numeric($recharge_cash) || $recharge_cash < 0.01) {
            $this->ajaxReturn(output(CodeEnum::RECHARGE_BALANCE_ERROR));
        }
        // 验证签名
        if (!verifySign()) {
            //$this->ajaxReturn(output(CodeEnum::SIGN_ERROR));
        }
        // 判断是否支付线上支付
        if (getConfig('online_pay') == 0) {
            $this->ajaxReturn(output(CodeEnum::NOT_SUPPORT_ONLINE_PAY));
        }
        // api参数
        $recharge_cash = sprintf("%.2f", $recharge_cash);
        // 充值金额范围
        $min_recharge = getConfig('min_recharge');
        $min_recharge=0;
        $max_recharge = getConfig('max_recharge');
        if ($recharge_cash < $min_recharge || $recharge_cash > $max_recharge) {
            $this->ajaxReturn(output(CodeEnum::RECHARGE_BALANCE_ERROR,[],[$min_recharge,$max_recharge]));
        }
        $type = $pay_type == 'wechat' ? 1 : 2;
        $merchant_order_sn = $this->getOrderSn($pay_type);
        // 请求支付接口
        $pay_url = getConfig('pay_url');
        //默认第三方支付的参数配置和签名方案
        $payType = getConfig('online_add_newpay');
        $result = false ;
        if(!$payType){
        	$postData = [
        			"app_id"        => getConfig('app_id'),
        			"method"        => getConfig('pay_method'),
        			"format"        => "json",
        			"sign_method"   => "md5",
        			"nonce"         => $nonce_str,
        			"biz_content"   => json_encode([
        					"type"              => $type,
        					"merchant_order_sn" => $merchant_order_sn,
        					"total_fee"         => $recharge_cash,
        					"store_id"          => getConfig('store_id'),
        			]),
        	];
        	// 获取签名
        	$postData['sign'] = getPaySign($data);
        	$resultJson = http_post_json($pay_url, $postData);
        	$result = $resultJson ? json_decode($resultJson, true) : false;
        }else if(intval($payType) > 0){ //新增的支付方案
        	$resultJson = $this->paySecondGet($pay_type,$merchant_order_sn,$recharge_cash);
        	$resultJson = $resultJson ? json_decode($resultJson, true) : false;

        	//组装新新的格式
        	$result = array("result_code"=> -1,'msg'=> '支付异常',"data"=>false);
        	if(!empty($resultJson) && isset($resultJson['code']) && intval($resultJson['code']) == 200){
        		$result['result_code']=200;
        		$result['data'] = array(
        				'price'=> isset($resultJson['data']["price"]) ? $resultJson['data']["price"]: '',
        				'order_no'=> isset($resultJson['ordno']) ? $resultJson['ordno']: '', //流水号
        				'order_sn'=> isset($resultJson['orderid']) ? $resultJson['orderid']: '',
        				'qr_code'=> isset($resultJson['data']["qrcode"]) ? $resultJson['data']["qrcode"]: '',
        		);
        	}else{
        		$result['result_code']=isset($resultJson['code']) ? $resultJson['code']: '';
        		$result['msg']=isset($resultJson['msg']) ? $resultJson['msg']. ' code='.$resultJson['code']: '';
        	}
        }
        
        //成功
        if (!isset($result['result_code']) || $result['result_code'] != 200) {
            file_put_contents(APP_PATH.'Runtime/onlinePay.txt',  "[ ".date('Y-m-d H:i:s')." ]获取支付链接失败：".print_r($result, 1), FILE_APPEND);
	    $remsg = isset($result["msg"]) ?trim($result["msg"]) : '';
            
            $this->ajaxReturn(output(CodeEnum::GET_PAY_URL_ERROR,false,array($remsg)));
        }
        
        // 插入支付订单
        M('pay_order')->add([
            'order_sn' => $result['data']['order_sn'],
            'merchant_order_sn' => $merchant_order_sn,
            'user_id' => $this->userInfo['user_id'],
            'total_fee' => $recharge_cash,
            'real_pay' => 0,
            'pay_type' => $pay_type,
            'is_pay' => 0,
            'pay_time' => 0,
            'add_time' => time(),
        ]);
        M('recharge')->add([
            'user_id'=> $this->userInfo['user_id'],
            'user_name'=> $this->userInfo['user_name'],
            'order_sn'=> $result['data']['order_sn'],
            'recharge_cash'=> $recharge_cash,
            'real_cash'=> 0,
            'type' => $pay_type == 'wechat' ? 3 : 2,
            'sync' => 0,
            'add_time' => time(),
        ]);
        $qr_code = $result['data']['qr_code'];
        // 微信支付生成二维链接
	/* if ($pay_type == 'wechat') {
            $qr_code = 'http://'.C('DOMAIN').'/api/public/qrcode?url='.urlencode($qr_code);
        }*/
        
        //不能修改数据
        $newSign = strtoupper(md5($qr_code."CHECK[PAY]".$pay_type));
        $realprice =  base64_encode($recharge_cash);
        //支付成功返回数据
        $qrCodeUrl = 'http://'.C('DOMAIN').'/api/public/wechatcode?uuid='.urlencode($qr_code) . "&paytype=".$pay_type."&sign=".$newSign."&tp=".$realprice; 
       
	if ($pay_type == 'wechat') {
		$qr_code = 'http://'.C('DOMAIN').'/api/public/qrcode?url='.urlencode($qr_code);
    }else{
		//$qr_code = !empty($qr_code) ? strtolower($qr_code);
	}


        // 用户日志
        $pay_name = $pay_type == 'wechat' ? "微信" : "支付宝";
        $this->addUserLog('线上充值', "{$pay_name}充值请求：{$recharge_cash}");
     	//   $this->ajaxReturn(output(CodeEnum::SUCCESS, ['qr_code'=> $qr_code, 'pay_type' => $pay_type]));
	$this->ajaxReturn(output(CodeEnum::SUCCESS, ['qr_code'=> $qr_code,"qrc_web"=>$qrCodeUrl, 'pay_type' => $pay_type]));
    }

    // 订单号
    private function getOrderSn($pay_type) {
        $prefix = $pay_type == 'wechat' ? 'W' : 'A';
        $merchant_order_sn = $prefix . date('YmdHis') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        if (M('pay_order')->where(['merchant_order_sn'=> $merchant_order_sn])->count()) {
            return $this->getOrderSn($pay_type);
        }
        return $merchant_order_sn;
    }

    public function getSign() {
        $param = I('get.');
        ksort($param);
        $str = '';
        foreach ($param as $key => $val){
             if(!empty($val) && $key != 'sign'){
                 $str .= $key."=".$val."&";
             }
        }
        $str = rtrim($str, "&");
        $str .= 'guangcai';
        $sign = strtolower(md5($str));
        echo $sign;
    }

    /**
     * @desc 获取充值方式列表
     * @param client_id     客户端ID
     * @param token         用户TOKEN
     * @return int
     */
    public function getPayTypeList() {
        $payTypeList = [];
        if (getConfig('online_pay') == 1) {
            $payTypeList[] = ['pay_type'=> 'alipay', 'pay_name'=>'支付宝充值'];
            $payTypeList[] = ['pay_type'=> 'wechat', 'pay_name'=>'微信充值'];
        }
        $payTypeList[] = ['pay_type'=> 'bank', 'pay_name'=>'线下充值'];
        $this->ajaxReturn(output(CodeEnum::SUCCESS, [
            'minRecharge' => getConfig('min_recharge'),
            'maxRecharge' => getConfig('max_recharge'),
            'payTypeList' => $payTypeList,
        ]));
    }
}
