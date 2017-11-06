<?php
namespace Adminbak\Controller;
use Adminbak\Controller\BaseController;
class DrawController extends BaseController {
    public function index() {
    	if (IS_POST) {
    		$apply_cash = I('post.apply_cash');
    		$pay_password = I('post.pay_password');
    		if (empty($apply_cash)) {
    			$this->error('提现资金不能为空');
    		}
            if (empty($pay_password)) {
                $this->error('资金密码不能为空');
            }
            if ($apply_cash < 10 || !preg_match('/^\d+$/', $apply_cash)) {
                $this->error('请输入大于10的整数');
            }
    		$adminInfo = M('admin_user')->where(['user_name'=> session('admin_name')])->find();
    		if (empty($adminInfo['pay_password']) || empty($adminInfo['account_number']) || empty($adminInfo['bank_name']) || empty($adminInfo['real_name']) || empty($adminInfo['branch_bank'])) {
    			$this->error("请先保存银行卡信息", U('User/bank'));
    		}
    		if (md5($pay_password) != $adminInfo['pay_password']) {
    			$this->error('资金密码错误');
    		}
    		if (bccomp($apply_cash, $adminInfo['balance']) == 1) {
    			$this->error("余额不足");
    		}
            // 获取今日提款次数
            $count = M('draw_cash')->where(['user_id'=> $adminInfo['user_id'], 'type'=>2, 'add_time'=>['egt', strtotime(date('Y-m-d'))]])->count();
            $free_draw_times = getConfig('free_draw_times');
            $draw_fee = getConfig('draw_fee');
            $real_cash = $count >= $free_draw_times ? bcmul($apply_cash, (1-$draw_fee), 2) : $apply_cash;
    		$addData = [
                'user_id' => $adminInfo['user_id'],
    			'user_name' => $adminInfo['user_name'],
                'type'=> 2,
                'apply_cash' => $apply_cash,
    			'real_cash' => $real_cash,
    			'account_number' => $adminInfo['account_number'],
    			'bank_name' => $adminInfo['bank_name'],
    			'real_name' => $adminInfo['real_name'],
    			'branch_bank' => $adminInfo['branch_bank'],
    			'sync' => 0,
    			'add_time' => time(),
    		];
    		if (M('draw_cash')->add($addData)) {
    			$balance = bcsub($adminInfo['balance'], $apply_cash, 2);
    			M('admin_user')->where(['user_id'=> $adminInfo['user_id']])->save(['balance'=> $balance]);
                M('admin_waste_book')->add([
                    'user_id'=> $adminInfo['user_id'],
                    'before_balance'=> $adminInfo['balance'],
                    'after_balance'=> $balance,
                    'change_balance'=> -$apply_cash,
                    'type'=> 2,
                    'add_time'=> time(),
                ]);
    		}
    		$this->success("申请提款成功", U('Draw/index'));
    	} else {
    		$adminInfo = M('admin_user')->where(['user_name'=> session('admin_name')])->find();
    		if (empty($adminInfo['pay_password'])) {
                $this->redirect('User/bank');
    		}
    		$draw_balance = M('draw_cash')
    			->where(['user_id'=> $adminInfo['user_id'],'type'=>2, 'sync'=>['neq',2]])
    			->sum('apply_cash');
    		$draw_balance = $draw_balance ? $draw_balance : "0.00";
    		$all_income = bcadd($draw_balance, $adminInfo['balance'], 2);
		    $this->assign('draw_balance', $draw_balance);
		    $this->assign('all_income', $all_income);
		    $this->assign('adminInfo', $adminInfo);
	    	$this->display();
    	}
    }
}