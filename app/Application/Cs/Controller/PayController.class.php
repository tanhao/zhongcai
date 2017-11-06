<?php
namespace Cs\Controller;
use Lib\Enum\CodeEnum;
class PayController extends BaseController {
	// 充值管理
    public function recharge(){
        $type = I('get.type', -1, 'intval');
        $sync = I('get.sync', -1, 'intval');
        $user_name = I('get.user_name');
        $where = [];
        if (!empty($user_name)) {
            $where['r.user_name'] = $user_name;
        }
        if ($type != -1) {
        	$where['r.type'] = $type;
        }
        if ($sync != -1) {
        	$where['r.sync'] = $sync;
        }
        $recharge = M('recharge');
        $count = M('recharge')->alias('r')->where($where)->count();
        $pageInfo = setPage($count);
        $db_prefix = C('DB_PREFIX');
        $list = $recharge->join("as r left join {$db_prefix}user as u on r.user_id=u.user_id")->where($where)->field("u.balance,r.*")->order('r.id desc')->limit($pageInfo['limit'])->select();
        foreach ($list as $key => $value) {
        	$typeName = $value['type'] == 1 ? "线下" : ($value['type'] == 2 ? "支付宝" : "微信");
        	$list[$key]['typeName'] = $typeName;
        	$wayName = $value['type'] == 1 ? $value['bank_name'] : ($value['type'] == 2 ? "支付宝" : "微信");
        	$list[$key]['wayName'] = $wayName;
        	$account = $value['type'] == 1 ? $value['account_number']."({$value['real_name']})" : "订单号:{$value['order_sn']}";
        	$list[$key]['account'] = $account;
            $list[$key]['show_button'] = $value['type'] == 1 && $value['sync'] == 0 && (empty($value['mm_name']) || $value['mm_name'] == session('cs_name')) ? true : false;
        }
        $this->assign('type', $type);
        $this->assign('sync', $sync);
        $this->assign('user_name', $user_name);
        $this->assign('list', $list);
        $this->assign('pageInfo', $pageInfo);
    	$this->display();
    }

    public function bankInfo() {
        $bank_id = I('get.bank_id',0,'intval');
        $bankInfo = M('set_bank_card')->where(['id'=> $bank_id])->find();
        $this->assign('bankInfo', $bankInfo);
        $this->display();
    }

    // 锁定充值用户
    public function lockOperateUser() {
        if (IS_POST) {
            $id = I('post.id', 0, 'intval');
            if ($id < 1) {
                $this->ajaxOutput('参数错误');
            }
            $recharge = M('recharge');
            $user = M('user');
            $rechargeInfo = $recharge->where(['id'=> $id])->find();
            if (empty($rechargeInfo)) {
                $this->ajaxOutput('充值信息不存在');
            }
            if ($rechargeInfo['type'] != 1) {
                $this->ajaxOutput('只有线下充值才能手动充值');
            }
            if ($rechargeInfo['sync'] != 0) {
                $this->ajaxOutput('该充值信息已同步');
            }
            if (!empty($rechargeInfo['mm_name']) && $rechargeInfo['mm_name'] != session('cs_name')) {
                $this->ajaxOutput($rechargeInfo['mm_name'].'已经在操作');
            }
            $recharge->where(['id'=>$id])->save(['mm_name'=> session('cs_name')]);
            $this->ajaxOutput("锁定成功", 1);
        }
    }

    // 充值
    public function syncRecharge() {
        if (IS_POST) {
            $real_cash = I('post.real_cash', 0, 'floatval');
            $id = I('post.id', 0, 'intval');
            if ($real_cash < 0 || $id < 1) {
                $this->ajaxOutput('参数错误');
            }
            $recharge = M('recharge');
            $user = M('user');
            $rechargeInfo = $recharge->where(['id'=> $id])->find();
            if (empty($rechargeInfo)) {
                $this->ajaxOutput('充值信息不存在');
            }
            if ($rechargeInfo['type'] != 1) {
                $this->ajaxOutput('只有线下充值才能手动充值');
            }
            if ($rechargeInfo['sync'] != 0) {
                $this->ajaxOutput('该充值信息已同步');
            }
            // 开户事务
            M()->startTrans();
            $ret1 = $recharge->where(['id'=>$id])->save([
                'real_cash'=> $real_cash,
                'sync'=> 1,
                'mm_name'=> session('cs_name'),
                'pay_time'=> time()
            ]);
            $userInfo = $user->where(['user_id'=> $rechargeInfo['user_id']])->field('user_name,balance')->find();
            $before_balance = $userInfo['balance'];
            $after_balance = bcadd($before_balance, $real_cash, 2);
            $ret2 = M('user')->where(['user_id'=> $rechargeInfo['user_id']])->save(['balance'=> $after_balance]);
            // 流水LOG
            $ret3 = M('user_waste_book')->add([
                'user_id'=> $rechargeInfo['user_id'],
                'before_balance'=> $before_balance,
                'after_balance'=> $after_balance,
                'change_balance'=> $real_cash,
                'type'=> 4,
                'add_time'=> time(),
            ]);
            // 记录系统通知信息
            $content = "尊敬的客户：您的充值{$real_cash}元已到账。";
            $ret4 = M('system_message')->add(['user_id'=> $rechargeInfo['user_id'], 'content'=> $content, 'read'=> 0, 'add_time'=> time()]);
            if ($ret1 && $ret2 && $ret3 && $ret4) {
                M()->commit();
                $client_id = M('user_token')->where(['user_id'=> $rechargeInfo['user_id']])->getField('client_id');
                // 推送用户余额给用户
                sendToClient($client_id, CodeEnum::LEFT_BALANCE, ['balance'=> $after_balance]);
                // 推送系统通知
                $unreadCount = M('system_message')->where(['user_id'=> $rechargeInfo['user_id'], 'read'=>0])->count();
                sendToClient($client_id, CodeEnum::SYSTEM_MESSAGE, ['unreadCount'=> $unreadCount]);
            } else {
                M()->rollback();
                $this->ajaxOutput('充值失败');
            }
            $this->addAtionLog("确认会员:{$userInfo['user_name']} 线下充值:{$real_cash}");
            $this->ajaxOutput("充值成功", 1, U('Pay/recharge'));
        }
    }

    // 提现管理
    public function draw(){
        $type = I('get.type', -1, 'intval');
        $sync = I('get.sync', -1, 'intval');
        $user_name = I('get.user_name');
        $where = [];
        if (!empty($user_name)) {
            $where['user_name'] = $user_name;
        }
        if ($type != -1) {
        	$where['type'] = $type;
        }
        if ($sync != -1) {
        	$where['sync'] = $sync;
        }
        $draw_cash = M('draw_cash');
        $admin_user = M('admin_user');
        $user = M('user');
        $count = $draw_cash->where($where)->count();
        $pageInfo = setPage($count);
        $list = $draw_cash->where($where)->order('id desc')->limit($pageInfo['limit'])->select();
        foreach ($list as $key => $value) {
        	if ($value['type'] == 1) {
        		$list[$key]['balance'] = $user->where(['user_id'=>$value['user_id']])->getField('balance');
        	} else {
        		$list[$key]['balance'] = $admin_user->where(['user_id'=>$value['user_id']])->getField('balance');
        	}
        	$typeName = $value['type'] == 1 ? "会员" : "代理";
        	$list[$key]['typeName'] = $typeName;
        	$status = "待处理";
        	switch ($value['sync']) {
        		case '1':
        			$status = "已完成";
        			break;
        		case '2':
        			$status = "已取消";
        			break;
        		case '3':
        			$status = "<font color='red'>有问题</font>";
        			break;
        	}
        	$list[$key]['status'] = $status;
        }
        $this->assign('type', $type);
        $this->assign('sync', $sync);
        $this->assign('user_name', $user_name);
        $this->assign('list', $list);
        $this->assign('pageInfo', $pageInfo);
    	$this->display();
    }

    public function changeDrawStatus() {
    	if (IS_POST) {
    		$id = I('post.id', 0, 'intval');
    		$sync = I('post.sync', 0, 'intval');
    		if ($id < 1 || $sync < 1 || $sync > 3) {
    			$this->ajaxOutput('参数错误');
    		}
	        $draw_cash = M('draw_cash');
	        $drawInfo = $draw_cash->where(['id'=>$id])->find();
	        if (empty($drawInfo)) {
	            $this->ajaxOutput('提现信息不存在');
	        }
	        if ($drawInfo['sync'] != 0) {
	        	$this->ajaxOutput('该提现已处理');
	        }
	        $draw_cash->where(['id'=>$id])->save([
	        	'sync'=> $sync,
	        	'cs_name'=> session('cs_name'),
	        	'sync_time'=> time(),
	        ]);
	        // 如果是取消要返回金额给用户
	        if ($sync == 2) {
	        	// 用户
	        	if ($drawInfo['type'] == 1) {
	        		$before_balance = M('user')->where(['user_id'=> $drawInfo['user_id']])->getField('balance');
	        		$balance = bcadd($before_balance, $drawInfo['apply_cash'], 2);
	        		M('user')->where(['user_id'=> $drawInfo['user_id']])->save(['balance'=> $balance]);
	        		// 流水LOG
			        M('user_waste_book')->add([
			            'user_id'=> $drawInfo['user_id'],
			            'before_balance'=> $before_balance,
			            'after_balance'=> $balance,
			            'change_balance'=> $drawInfo['apply_cash'],
			            'type'=> 3,
			            'add_time'=> time(),
			        ]);
			        $client_id = M('user_token')->where(['user_id'=> $drawInfo['user_id']])->getField('client_id');
			        // 推送用户余额给用户
            		sendToClient($client_id, CodeEnum::LEFT_BALANCE, ['balance'=> $balance]);
	        	} else {
	        		//代理
	        		$before_balance = M('admin_user')->where(['user_id'=> $drawInfo['user_id']])->getField('balance');
	        		$balance = bcadd($before_balance, $drawInfo['apply_cash'], 2);
	        		M('admin_user')->where(['user_id'=> $drawInfo['user_id']])->save(['balance'=> $balance]);
	        		// 流水LOG
			        M('admin_waste_book')->add([
			            'user_id'=> $drawInfo['user_id'],
			            'before_balance'=> $before_balance,
			            'after_balance'=> $balance,
			            'change_balance'=> $drawInfo['apply_cash'],
			            'type'=> 3,
			            'add_time'=> time(),
			        ]);
	        	}
	        }
            
            if ($drawInfo['type'] == 1) {
                $typeName = '会员';
                $user_name = M('user')->where(['user_id'=> $drawInfo['user_id']])->getField('user_name');
                // 记录系统通知信息
                $content = '';
                switch ($sync) {
                    case '1': $content = "尊敬的客户：您的提现{$drawInfo['apply_cash']}元已处理。";break;
                    case '2': $content = "尊敬的客户：您的提现{$drawInfo['apply_cash']}元已被取消。";break;
                    case '3': $content = "尊敬的客户：您的提现{$drawInfo['apply_cash']}元有异常。";break;
                }
                M('system_message')->add(['user_id'=> $drawInfo['user_id'], 'content'=> $content, 'read'=> 0, 'add_time'=> time()]);
                // 推送系统通知
                $unreadCount = M('system_message')->where(['user_id'=> $drawInfo['user_id'], 'read'=>0])->count();
                $client_id = M('user_token')->where(['user_id'=> $drawInfo['user_id']])->getField('client_id');
                sendToClient($client_id, CodeEnum::SYSTEM_MESSAGE, ['unreadCount'=> $unreadCount]);
            } else {
                $typeName = '代理';
                $user_name = M('admin_user')->where(['user_id'=> $drawInfo['user_id']])->getField('user_name');
            }
            $logMsg = '';
            switch ($sync) {
                case '1': $logMsg = "确认{$typeName}:{$user_name} 提现:{$drawInfo['real_cash']}";break;
                case '2': $logMsg = "取消{$typeName}{$user_name}提现:{$drawInfo['real_cash']}";break;
                case '3': $logMsg = "{$typeName}{$user_name}提现有问题:{$drawInfo['real_cash']}";break;
            }
            $this->addAtionLog($logMsg);
	        $this->ajaxOutput("操作成功", 1, U('Pay/draw'));
    	}
    }

    public function checkDraw() {
    	$id = I('get.id', 0, 'intval');
        $draw_cash = M('draw_cash');
        $drawInfo = $draw_cash->where(['id'=>$id])->find();
        if (empty($drawInfo)) {
            $this->error('提现信息不存在');
        }
        $status = "待处理";
        switch ($drawInfo['sync']) {
            case '1':
                $status = "已完成";
                break;
            case '2':
                $status = "已取消";
                break;
            case '3':
                $status = "有问题";
                break;
        }
        $drawInfo['status'] = $status;
        $this->assign('drawInfo', $drawInfo);
        $this->display();
    }
}