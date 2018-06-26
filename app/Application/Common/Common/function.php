<?php
use Lib\Enum\CodeEnum;
use Lib\Enum\CacheEnum;
use Lib\GatewayClient\Gateway;

/**
 * @desc 是否手机号码
 * @param string $number
 * @return int
 */
function isPhone($number) {
    return preg_match('/^1[356789]\d{9}$/', $number);
}

/**
 * @desc 是否邮箱
 * @param string $email
 * @return int
 */
function isEmail($email) {
    return preg_match('/^[a-zA-Z0-9_-]+@[a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)+$/', $email);
}

/**
 * @desc 是否身份证
 * @param string $number
 * @return int
 */
function isIdCard($number) {
    $isEighteen = preg_match("/^[1-9]\d{5}[1-9]\d{3}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}([0-9]|X)$/", $number);
    if ($isEighteen) {
        return 1;
    }
    return preg_match("/^[1-9]\d{7}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}$/", $number);
}

/**
 * @desc 获取随机字符串
 * @param int $length 随机数长度
 * @return string
 */
function getRandChar($length = 30){
    $str = '';
    $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
    for($i = 0; $i < $length; $i++){
        $str.= $strPol[rand(0, (strlen($strPol) - 1))];//rand($min,$max)生成介于min和max两个数之间的一个随机整数
    }
    return $str;
}

/**
 * 验证APP签名方法
 * @param $post_data
 * @param string $sign_key
 * @return string
 */
function checkSign($post_data, $sign_key = "zhongcai"){
    if(!is_array($post_data) || empty($post_data) || empty($sign_key)){
        return false;
    }
    //数组key顺序排序
    ksort($post_data);
    //串联post数据
    $post_string = '';
    foreach($post_data as $key => $value){
        if (isset($value) && $key != 'sign'){
            $post_string .=$key .'=' .$value .'&';
        }
    }
    $post_string .= 'key=' . $sign_key;
    return strtolower(md5($post_string));
}

/**
 * @desc 发邮件
 * @param string $email    收件人邮箱
 * @param string $subject  邮件主题
 * @param string $body     邮件内容
 * @return bool
 */
function sendMail($email, $subject, $body) {
    // // 25端口不支持
    // Vendor('smtpMail.smtp');
    // $smtpserver = "smtp.163.com";//使用163邮箱服务器
    // $smtpserverport = 25;//163邮箱服务器端口 
    // $smtpusermail = C("MAIL_USER");//你的163服务器邮箱账号
    // $smtpemailto = $email;//收件人邮箱
    // $smtpuser = substr($smtpusermail, 0, -8);//你的邮箱账号(去掉@163.com)
    // $smtppass = C("MAIL_PASSWORD"); //你的邮箱密码
    // $mailsubject = $subject;
    // $mailbody = $body;
    // $mailtype = "HTML";//邮件格式（HTML/TXT）,TXT为文本邮件 
    // $smtp = new smtp($smtpserver,$smtpserverport,true,$smtpuser,$smtppass);
    // $smtp->debug = true;
    // $smtp->sendmail($smtpemailto, $smtpusermail, $mailsubject, $mailbody, $mailtype); 
    Vendor('PHPMailer.PHPMailerAutoload');
    $mail = new PHPMailer();
    $mail->isSMTP();
    $mail->SMTPAuth=true;
    $mail->Host = 'smtp.163.com';
    $mail->SMTPSecure = 'ssl';
    $mail->Port = 465;
    $mail->Helo = $subject;
    $mail->Hostname = '163.com';
    $mail->CharSet = 'UTF-8';
    $mail->FromName = C("PROJECT_NAME");
    $mail->Username = C("MAIL_USER");
    $mail->Password = C("MAIL_PASSWORD");
    $mail->From = C("MAIL_USER");
    $mail->isHTML(true); 
    $mail->addAddress($email);
    $mail->Subject = $subject;
    $mail->Body = $body;
    return $mail->send();
}

/**
 * @desc 发短信
 * @param string $phoneNumbers     手机号
 * @param string $templateCode     模板Code
 * @param array $templateParam     模板内容参数
 * @return bool
 */
function sendSms($phoneNumbers, $templateParam=null, $templateCode=null) {
    Vendor('phpSMS.SmsDemo');
    if (empty($templateCode)) {
        $templateCode = C('SMS_TEMPLATE_CODE');
    }
    $demo = new SmsDemo(
        C('SMS_ACCESS_KEY_ID'),
        C('SMS_ACCESS_KEY_SECRET')
    );
    $response = $demo->sendSms(
        C('SMS_SIGNATURE'), // 短信签名
        $templateCode, // 短信模板编号
        $phoneNumbers, // 短信接收者
        $templateParam  // 短信模板中字段的值
    );
    if ($response->Code != 'OK') {
        file_put_contents(APP_PATH.'Runtime/sms.txt',  "[ ".date('Y-m-d H:i:s')." ]发送短信失败({$phoneNumbers})：".print_r($response, 1), FILE_APPEND);
        return false;
    }
    return true;
}

/**
 * @desc 获取一个redis对象
 * @return object
 */
function redisCache() {
    $redis = new \Redis();
    $redis->connect(C('REDIS_ADDRESS'), C('REDIS_PORT'));
    $redis->auth(C('REDIS_PASSWORD'));
    $redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_NONE);
    return $redis;
}

/**
 * @desc 获取接口输出数据
 * @return array
 */
function output($code, $data = array(), $replace = array()) {
    $message = isset(CodeEnum::$codeText[$code]) ? CodeEnum::$codeText[$code] : '';
    foreach ($replace as $key => $value) {
        $message = preg_replace('/\?\?/', $value, $message, 1);
    }
    $result = array(
        'code'      => $code,
        'token'     => C('TOKEN'),
        'message'   => $message,
        'data'      => $data,
    );
    // 记录用户请求动作LOG
    $param = IS_POST ? json_encode(I('post.'), JSON_UNESCAPED_UNICODE) : json_encode(I('get.'), JSON_UNESCAPED_UNICODE);
    if (I('get.client_id') != 1 &&　APP_DEBUG) {
        M('request_action_log')->add([
            'user_id'=> C('USER_ID') ? C('USER_ID') : 0,
            'is_temp'=> C('IS_TEMP') ? C('IS_TEMP') : 0,
            'controller'=> CONTROLLER_NAME,
            'action'=> ACTION_NAME,
            'param'=> $param,
            'return'=> json_encode($result, JSON_UNESCAPED_UNICODE),
            'ip'=> get_client_ip(),
            'add_time'=> date('Y-m-d H:i:s'),
        ]);
    }
    return $result;
}

/**
 * @desc 获取长链接输出数据
 * @return object
 */
function outputSocket($code, $data = array()) {
    $result = array(
        'code'      => $code,
        'message'   => isset(CodeEnum::$socketCodeText[$code]) ? CodeEnum::$socketCodeText[$code] : '',
        'data'      => $data,
    );
    return json_encode($result, JSON_UNESCAPED_UNICODE);
}

/**
 * @desc 获取Token
 * @param string $client_id     客户端ID
 * @return object
 */
function getToken($client_id) {
    $result = md5(time() . getRandChar(10));
    if (M('user_token')->where(['token'=> $result])->count()) {
        return getToken($client_id);
    } else {
        $temp_user_id = getTempUserId();
        M('user_token')->add([
            'user_id'  => $temp_user_id,
            'is_temp'  => 1,
            'token'    => $result,
            'client_id'=> $client_id,
            'online'   => 1,
            'room_id'  => 0,
            'add_time' => time(),
        ]);
        return $result; 
    }
}

/**
 * @desc 获取临时用户ID
 * @return object
 */
function getTempUserId() {
    $result = time() . rand(1111, 9999);
    $count = M('user_token')->where(['user_id'=> $result])->count();
    return $count ? getTempUserId() : $result;
}

/**
 * @desc 获取临时昵称
 * @return object
 */
function getTempNickname($temp_user_id) {
    $str = substr(md5($temp_user_id), 20, 5);
    $str = strtoupper($str);
    $str = '*' . $str;
    return $str; 
}

/**
 * @desc 获取随机邀请码
 * @return object
 */
function getInviteCode() {
    $invite_code = rand(0, 9) . rand(10000, 99999);
    if (M('admin_user')->where(['invite_code'=> $invite_code])->count()) {
        return getInviteCode();
    }
    return $invite_code;
}

/**
 * curl 请求方法
 *
 * @param string $url 请求地址
 * @param array $data post方式数据
 * @param string $method 请求方式
 * @return bool 结果
 */
function api_curl($url, $data=array(), $method='GET')
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1);
    if('POST' == $method)
    {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        //设置HTTP头信息
        curl_setopt($ch,CURLOPT_HTTPHEADER,array("X-HTTP-Method-Override: $method"));
    }
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

/**
 * PHP发送Json对象数据
 * @param $url 请求url
 * @param $params 发送的参数数组
 * @return array
 */
function http_post_json($url,$params = []) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POST, true);
    if (!empty($params)) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
    }
    $header = array("content-type: application/json");
    curl_setopt($ch, CURLOPT_HTTPHEADER,$header);
    $reponse = curl_exec($ch);
    curl_close($ch);
    return $reponse;

}

/**
 * @desc 获取网站内容
 * @return object
 */
function curl_file_get_contents($url){ 
    $cookie_file = APP_PATH."Runtime/Temp/cookie.txt"; 
    $ch = curl_init(); 
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file); 
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); 
    curl_setopt($ch, CURLOPT_TIMEOUT,5);
    $ret = curl_exec($ch); 
    curl_close($ch); 
    return $ret; 
}

/**
 * @desc 斗牛算法
 * @param $arr count = 5
 * @return int 10-牛牛，0-无牛
 */
function getCowPoint($arr){
    foreach ($arr as $av) {
        foreach ($arr as $bv) {
            foreach ($arr as $cv) {
                if ($av < $bv && $bv < $cv) {
                    if(($av + $bv + $cv) % 10 == 0){
                        $s = 0;
                        foreach ($arr as $dv) {
                            if (!in_array($dv, [$av, $bv, $cv])) {
                                $s += $dv;
                            }
                        }
                        $s = $s % 10;
                        return $s == 0 ? 10 : $s;
                    }
                }
            }
        }
    }
    return 0;
}

/**
 * @desc 区域排名
 * @param $zoneDetail [zone point  max_number]
 * @return [zone point  max_number rank]
 */
function sortZone($zoneDetail) {
    $sort1 = [];
    $sort2 = [];
    foreach ($zoneDetail as $key => $value) {
        $sort1[] = $value['point'];
        $sort2[] = $value['max_number'];
    }
    array_multisort($sort1, SORT_DESC, $sort2, SORT_DESC, $zoneDetail);
    $rank = 1;
    foreach ($zoneDetail as $key => $value) {
        $zoneDetail[$key]['rank'] = $rank;
        // 同点数并且最大数相同排序号相同
        if (isset($zoneDetail[$key-1])) {
            if ($value['point'] == $zoneDetail[$key-1]['point'] && $value['max_number'] == $zoneDetail[$key-1]['max_number']) {
                $rank--;
                $zoneDetail[$key]['rank'] = $rank;
            }
        }
        $rank++;
    }
    return $zoneDetail;
}

/**
 * @desc 数组排序
 * @param $arr    排序数组
 * @param $field  排序字段
 * @param $sort   asc-正序，desc-倒序
 * @return [zone point  max_number rank]
 */
function sortArray($arr, $field, $sort = 'asc') {
    $temp = [];
    foreach ($arr as $key => $value) {
        $temp[] = $value[$field];
    }
    $sort = $sort == 'asc' ? SORT_ASC : SORT_DESC;
    array_multisort($temp, $sort, $arr);
    return $arr;
}

function addSelectKey($list, &$key_value, $field_name) {
    if (empty($list)) {
        return [];
    }
    $temp = 0;
    foreach ($list as $key => $value) {
        $selected = 0;
        if ($value[$field_name] == $key_value) {
            $selected = 1;
            $temp = 1;
        }
        $list[$key]['selected'] = $selected;
    }
    if ($temp == 0) {
        $list[0]['selected'] = 1;
        $key_value = $list[0][$field_name];
    }
    return $list;
}

/**
 * @desc 推送信息到客户端
 * @return bool
 */
function sendToClient($client_id, $code, $data) {
    $json_data = outputSocket($code, $data);
    try {
        Gateway::sendToClient($client_id, $json_data);
    } catch(\Exception $e) {
         // \Think\Log::record($e->getMessage());
    }
}

/**
 * @desc 推送信息到客户端
 * @return bool
 */
function sendToAll($code, $data, $clientArr = []) {
    $json_data = outputSocket($code, $data);
    try {
        if (!empty($clientArr)) {
            Gateway::sendToAll($json_data, $clientArr);
        } else {
            Gateway::sendToAll($json_data);
        }
    } catch(\Exception $e) {
         // \Think\Log::record($e->getMessage());
    }
}

/**
 * @desc 验证签名
 * @return bool
 */
function verifySign() {
    $param = I('get.');
    if (!isset($param['nonce_str']) || !isset($param['sign']) || strlen($param['nonce_str']) != 32) {
        return false;
    }
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
    if ($sign != $param['sign']) {
        return false;
    }
    if (M('nonce_log')->where(['nonce_str'=> $param['nonce_str']])->count()) {
        return false;
    }
    M('nonce_log')->add(['nonce_str'=> $param['nonce_str']]);
    return true;
}

/**
 * @desc 获取第三方支付签名
 * @return bool
 */
function getPaySign($data) {
    ksort($data);
    $str = "";
    foreach ($data as $key=>$val){
         if(!empty($val) && $key != 'sign'){
             $str .= $key."=".$val."&";
         }
    }
    $str = rtrim($str,"&");
    $str .= getConfig('app_secret');
    return strtoupper(md5($str));
}

/**
 * @desc 获取第三方支付签名
 * @param config_sign
 * @return bool
 */
function getConfig($config_sign) {
    $redis = redisCache();
    $config = $redis->get(CacheEnum::CONFIG);
    if (empty($config)) {
        $systemInfo = M('config')->select();
        $config[] = [];
        foreach ($systemInfo as $key => $value) {
            $config[$value['config_sign']] = $value['config_value'];
        }
        $redis->set(CacheEnum::CONFIG, json_encode($config));
    } else {
        $config = json_decode($config, true);
    }
    if (!isset($config[$config_sign])) {
        $systemInfo = M('config')->select();
        $config = [];
        foreach ($systemInfo as $key => $value) {
            $config[$value['config_sign']] = $value['config_value'];
        }
        $redis->set(CacheEnum::CONFIG, json_encode($config));
    }
    $returnVal = isset($config[$config_sign]) ? $config[$config_sign] : ''; 
    
    //是否启用第三方配置多选功；
    $payType = isset($config['online_add_newpay']) ? intval($config['online_add_newpay']) : 1 ;//默认为原来的    
    $allowKeys = array('app_id','app_secret','store_id','pay_url','pay_method',"notify_url","return_url");
    $newPays = !empty($config['pay_set_data']) ? unserialize($config['pay_set_data']) : false;//支付参数使用最新数据
    if(isset($config['pay_set_data']) && !empty($config['pay_set_data']) && !empty($newPays) && $payType > 1){ //新的第三方支付参数如果不为空的时
    	//取支付参数列表值；[app_id app_secret store_id pay_url pay_method]
    	if(isset($newPays[$config_sign]) && !empty($newPays[$config_sign]) && in_array($config_sign, $allowKeys)) {
    		$returnVal = trim($newPays[$config_sign]);
    	}
    }
    return $returnVal;
}

/**
 * @desc 管理后台分页
 * @param config_sign
 * @return bool
 */
 function setPage($count, $page_size=15, $field="page") {
    $page = I('get.'.$field, 1, 'intval');
    $page_count = ceil($count/$page_size);
    if ($page > $page_count) $page = $page_count;
    if ($page < 1) $page = 1;
    $offset = $page_size * ($page - 1);
    $limit = $offset.','.$page_size;
    return [
        'page'=> $page,
        'page_count'=> $page_count,
        'limit'=> $limit,
    ];
 }

/**
 * @desc 手机端分页
 * @param config_sign
 * @return bool
 */
function setAppPage($count, $page_size=10, $field="page") {
    $page = I('get.'.$field, 1, 'intval');
    $page_count = ceil($count/$page_size);
    if ($page > $page_count) $page = $page_count;
    if ($page < 1) $page = 1;
    $offset = $page_size * ($page - 1);
    $limit = $offset.','.$page_size;
    $prev = $page - 1;
    $next = $page + 1;
    $next = $next > $page_count ? 0 : $next;
    return [
        'prev' => $prev,
        'next' => $next,
        'limit'=> $limit,
    ];
}

/**
 * @desc 获取当前开奖期数
 * @param lottery_id
 * @return bool
 */
function getIssue($lottery_id) {
    $time = time();
    // 赛车
    if ($lottery_id == 1) {
        $fix_time_1 = '2017-07-23 09:07:00';
        $fix_issue_1 = 630226;
        $issue1 = $fix_issue_1 + floor(($time - strtotime($fix_time_1))/86400)*179;
        $issue2 = date('H:i:s') > '09:07:00' ? floor(($time - strtotime(date('Y-m-d ').'09:07:00'))/300) : 178;
        return $issue1+$issue2;
    } 
    // 时时彩
    if ($lottery_id == 2) {
        $issue1 = date('Ymd', $time);
        if (date('H:i:s') < '00:05:00') {
            $issue1 = date('Ymd', $time - 86400);
            $issue2 = 120;
        } elseif (date('H:i:s') < '02:00:00') {
            $issue2 = str_pad(floor(($time - strtotime(date('Y-m-d')))/300), 3, '0', STR_PAD_LEFT);
        } elseif (date('H:i:s') < '10:00:00') {
            $issue2 = '023';
        } elseif (date('H:i:s') < '22:05:00') {
            $issue2 = 24 + floor(($time - strtotime(date('Y-m-d ').'10:00:00'))/600);
            $issue2 = str_pad($issue2, 3, '0', STR_PAD_LEFT);
        } else {
            $issue2 = 96 + floor(($time - strtotime(date('Y-m-d ').'22:00:00'))/300);
        }
        return $issue1.$issue2;
    }
    // 飞艇
    if ($lottery_id == 3) {
        $issue1 = date('Ymd', $time);
        if (date('H:i:s') < '04:04:00') {
            $issue1 = date('Ymd', $time - 86400);
            $issue2 = 131 + floor(($time - strtotime(date('Y-m-d ', $time - 86400).'23:59:00'))/300);
        } elseif (date('H:i:s') < '13:09:00') {
            $issue1 = date('Ymd', $time - 86400);
            $issue2 = 180;
        } else {
            $issue2 = str_pad(floor(($time - strtotime(date('Y-m-d ').'13:04:00'))/300), 3, '0', STR_PAD_LEFT);
        }
        return $issue1.$issue2;
    }
}
