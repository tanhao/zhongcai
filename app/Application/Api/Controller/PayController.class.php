<?php
namespace Api\Controller;
use Lib\Enum\CodeEnum;
use Lib\Enum\CacheEnum;

class PayController extends BaseController {

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
        // 验证参数
        if (empty($recharge_cash) || !in_array($pay_type, ['wechat','alipay']) || empty($nonce_str)) {
            $this->ajaxReturn(output(CodeEnum::PARAM_ERROR));
        }
        if (!is_numeric($recharge_cash) || $recharge_cash < 0.01) {
            $this->ajaxReturn(output(CodeEnum::RECHARGE_BALANCE_ERROR));
        }
        // 验证签名
        if (!verifySign()) {
            $this->ajaxReturn(output(CodeEnum::SIGN_ERROR));
        }
        // 判断是否支付线上支付
        if (getConfig('online_pay') == 0) {
            $this->ajaxReturn(output(CodeEnum::NOT_SUPPORT_ONLINE_PAY));
        }
        // api参数
        $recharge_cash = sprintf("%.2f", $recharge_cash);
        // 充值金额范围
        $min_recharge = getConfig('min_recharge');
        $max_recharge = getConfig('max_recharge');
        if ($recharge_cash < $min_recharge || $recharge_cash > $max_recharge) {
            $this->ajaxReturn(output(CodeEnum::RECHARGE_BALANCE_ERROR,[],[$min_recharge,$max_recharge]));
        }
        $type = $pay_type == 'wechat' ? 1 : 2;
        $merchant_order_sn = $this->getOrderSn($pay_type);
        $data = [
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
        $data['sign'] = getPaySign($data);
        // 请求支付接口
        $pay_url = getConfig('pay_url');
        $result = http_post_json($pay_url, $data);
        $result = json_decode($result, true);
        if (!isset($result['result_code']) || $result['result_code'] != 200) {
            file_put_contents(APP_PATH.'Runtime/onlinePay.txt',  "[ ".date('Y-m-d H:i:s')." ]获取支付链接失败：".print_r($result, 1), FILE_APPEND);
            $this->ajaxReturn(output(CodeEnum::GET_PAY_URL_ERROR));
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
        if ($pay_type == 'wechat') {
            $qr_code = 'http://'.C('DOMAIN').'/api/public/qrcode?url='.urlencode($qr_code);
        }
        // 用户日志
        $pay_name = $pay_type == 'wechat' ? "微信" : "支付宝";
        $this->addUserLog('线上充值', "{$pay_name}充值请求：{$recharge_cash}");
        $this->ajaxReturn(output(CodeEnum::SUCCESS, ['qr_code'=> $qr_code, 'pay_type' => $pay_type]));
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