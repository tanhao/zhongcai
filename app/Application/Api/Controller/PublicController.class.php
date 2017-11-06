<?php
namespace Api\Controller;
use Think\Controller;
use Lib\Enum\CodeEnum;
use Lib\Enum\CacheEnum;

class PublicController extends Controller {

	/**
	 * @desc 获取TOKEN
	 * @param string $client_id
	 * @return int
	 */
    public function getToken() {
        $client_id = I('get.client_id');
        if (empty($client_id)) {
    		$this->ajaxReturn(output(CodeEnum::PARAM_ERROR));
    	}
    	$token = getToken($client_id);
        C('TOKEN', $token);
        $this->ajaxReturn(output(CodeEnum::SUCCESS));
    }

    /**
     * @desc 线上支付回调
     * @param  string $data             {"merchant_order_sn":"201706061496753590676","total_fee":99}
     * @param  int    $result_code      200
     * @param  string $result_message   成功
     * @param  string $sign             9F4E2E841C71B2D3F24DB6C5C3711890
     * @return echo 'success';
     */
    public function payCallback() {
        $post = $_POST;
        if (!isset($post['result_code']) || $post['result_code'] != 200) {
            file_put_contents('./Application/Runtime/onlinePay.txt',  "[ ".date('Y-m-d H:i:s')." ]支付回调失败：".print_r($post, 1), FILE_APPEND);
            exit;
        }
        // 验证签名
        $sign = $post['sign'];
        if ($sign != getPaySign($post)) {
            file_put_contents('./Application/Runtime/onlinePay.txt',  "[ ".date('Y-m-d H:i:s')." ]支付回调验证签名失败：".print_r($post, 1), FILE_APPEND);
            exit;
        }
        $data = json_decode($post['data'], true);
        // 商户的订单号
        $merchant_order_sn = $data['merchant_order_sn'];
        // 订单金额(元)
        $total_fee = $data['total_fee'];
        // 实例模型
        $pay_order = M('pay_order');
        $user = M('user');
        $mysql = M();
        // 判断订单是否存在
        $orderInfo = $pay_order->where(['merchant_order_sn'=> $merchant_order_sn, 'is_pay'=> 0])->find();
        if (empty($orderInfo)) {
            file_put_contents(APP_PATH.'Runtime/onlinePay.txt',  "[ ".date('Y-m-d H:i:s')." ]支付回调订单不存在：".print_r($post, 1), FILE_APPEND);
            exit;
        }
        $mysql->startTrans();
        // 更新订单状态
        $ret_1 = $pay_order->where(['merchant_order_sn'=> $merchant_order_sn])->save([
            'real_pay' => $total_fee,
            'is_pay'   => 1,
            'pay_time' => time(),
        ]);
        // 更新用户余额
        $userInfo = $user->where(['user_id'=> $orderInfo['user_id']])->field('user_name,balance')->find();
        $balance = bcadd($userInfo['balance'], $total_fee, 2);
        $ret_2 = $user->where(['user_id'=> $orderInfo['user_id']])->save(['balance' => $balance]);
        // 添加充值记录
        $ret_3 = M('recharge')->where(['order_sn'=> $orderInfo['order_sn']])->save([
            'real_cash'=> $total_fee,
            'sync' => 1,
            'pay_time' => time(),
        ]);
        // 流水LOG
        $ret_4 = M('user_waste_book')->add([
            'user_id'=> $orderInfo['user_id'],
            'before_balance'=> $userInfo['balance'],
            'after_balance'=> $balance,
            'change_balance'=> $total_fee,
            'type'=> 4,
            'add_time'=> time(),
        ]);
        if (!$ret_1 || !$ret_2 || !$ret_3 || !$ret_4) {
            $mysql->rollback();
            exit;
        }
        // 用户日志
        $pay_name = $orderInfo['pay_type'] == 'wechat' ? '微信' : '支付宝';
        M('user_log')->add([
            'user_id'=> $orderInfo['user_id'],
            'user_name'=> $userInfo['user_name'],
            'title'=> '线上充值',
            'content'=> "{$pay_name}充值成功：{$total_fee}",
            'add_time'=> time(),
        ]);
        $mysql->commit();
        $client_id = M('user_token')->where(['user_id'=> $orderInfo['user_id'], 'is_temp'=> 0, 'online'=> 1])->getField('client_id');
        if (!empty($client_id)) {
            // 推送用户余额给用户
            sendToClient($client_id, CodeEnum::LEFT_BALANCE, ['balance'=> $balance]);
        }
        echo 'success';
        exit;
    }

    /**
     * @desc 生成二维码
     * @param  string $url    生成二维码的链接
     * @return url;
     */
    public function qrcode () {
        vendor("phpqrcode.phpqrcode");
        $url = isset($_GET['url']) ? (string)$_GET['url'] : '';
        // 纠错级别：L、M、Q、H
        $level = 'L';
        // 点的大小：1到10,用于手机端4就可以了
        $size = 10;
        // 下面注释了把二维码图片保存到本地的代码,如果要保存图片,用$fileName替换第二个参数false
        //$path = "images/";
        // 生成的文件名
        //$fileName = $path.$size.'.png';
        \QRcode::png($url, false, $level, $size);
    }

    /**
     * @desc 推送开奖结果
     * @param  int $lottery_id
     * @param  string $opencode
     * @param  string $issue
     * @param  string $open_time
     * @param  string $secret_key = 2b27690b4907bad30a67a59a383368cb
     * @return int
     */
    public function pushLotteryResult() {
        $lottery_id = I('post.lottery_id', 0 , 'intval');
        $opencode = I('post.opencode');
        $issue = I('post.issue');
        $open_time = I('post.open_time');
        $secret_key = I('post.secret_key');
        if (empty($lottery_id) || empty($opencode) || !in_array($lottery_id, [1,2,3]) || $secret_key != C('SECRET_KEY') || empty($open_time)) {
            $this->ajaxReturn(output(CodeEnum::PARAM_ERROR));
        }
        if (M('push_lottery_result')->where(['lottery_id'=>$lottery_id,'expect'=>$issue,'opencode'=>$opencode])->count()){
            $this->ajaxReturn(output(CodeEnum::REPEAT_PUSH));
        }
        M('push_lottery_result')->add([
            'lottery_id' => $lottery_id,
            'expect' => $issue,
            'opencode' => $opencode,
            'open_time' => date('Y-m-d H:i:s', strtotime($open_time)),
            'opentimestamp' => time(),
        ]);
        $this->ajaxReturn(output(CodeEnum::SUCCESS));
    }

    /**
     * @desc 推送未处理的线下充值
     * @param  string $secret_key = 2b27690b4907bad30a67a59a383368cb
     * @return int
     */
    public function pushRecharge() {
        $secret_key = I('get.secret_key');
        if ($secret_key != C('SECRET_KEY')) {
            $this->ajaxReturn(output(CodeEnum::PARAM_ERROR));
        }
        $list = M('recharge')->where(['type'=>1,'sync'=>0])->field('id,user_id,user_name,recharge_cash,account_number,bank_name,real_name,add_time')->select();
        $this->ajaxReturn(output(CodeEnum::SUCCESS,$list));
    }

    /**
     * @desc 推送未处理的提现
     * @param  string $secret_key = 2b27690b4907bad30a67a59a383368cb
     * @return int
     */
    public function pushDraw() {
        $secret_key = I('get.secret_key');
        if ($secret_key != C('SECRET_KEY')) {
            $this->ajaxReturn(output(CodeEnum::PARAM_ERROR));
        }
        $list = M('draw_cash')->where(['sync'=>0])->field('id,user_id,user_name,apply_cash,real_cash,account_number,bank_name,branch_bank,real_name,add_time')->select();
        $this->ajaxReturn(output(CodeEnum::SUCCESS,$list));
    }
}