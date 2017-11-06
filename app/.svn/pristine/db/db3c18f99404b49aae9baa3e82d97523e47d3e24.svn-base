<?php
namespace Ag\Controller;
class SettingsController extends BaseController {
	// 我的设置
    public function index(){
    	$this->assign('agent', $this->agUserInfo);
        $this->display();
    }

    // 关于广彩
    public function about() {
    	$this->display();
    }

    // 我的推广
    public function generalize(){
    	$this->assign('agent', $this->agUserInfo);
    	$this->display();
    }

    // 账户中心
    public function account(){
    	$this->assign('agent', $this->agUserInfo);
    	$this->display();
    }

    // 编辑
    public function edit(){
	    if (IS_POST) {
			$nickname = I('post.nickname','','htmlspecialchars,trim');
			$qq = I('post.qq','','trim');
			$phone = I('post.phone','','htmlspecialchars,trim');
            $email = I('post.email','','htmlspecialchars,trim');
			$full_name = I('post.full_name','','htmlspecialchars,trim');
			if (empty($nickname)) {
				$this->ajaxOutput('昵称不能为空');
			}
			$saveData = ['nickname'=> $nickname];
			if (!empty($qq)) {
				if (!preg_match('/^\d{4,15}$/', $qq)) {
					$this->ajaxOutput('联系QQ格式不正确');
				}
				$saveData['qq'] = $qq;
			}
			if (!empty($phone)) {
				if (!isPhone($phone)) {
					$this->ajaxOutput('手机号码格式不对');
				}
				$saveData['phone'] = $phone;
			}
			if (!empty($email)) {
				if (!isEmail($email)) {
					$this->ajaxOutput('邮箱格式不对');
				}
				$saveData['email'] = $email;
			}
            if (!empty($full_name)) {
                $saveData['full_name'] = $full_name;
            }
            M('admin_user')->where(['user_id'=> $this->agUserInfo['user_id']])->save($saveData);
            $this->ajaxOutput("资料保存成功！", 1, U('settings/account'));
	    } else {
	    	$this->assign('agent', $this->agUserInfo);
	    	$this->display();
	    }
    }

    // 安全中心
    public function security(){
    	if (IS_POST) {
    		$type = I('post.type');
    		if ($type == 'editPassword') {
    			// 修改密码
    			$old_password = I('post.old_password','','trim');
    			$password = I('post.password','','trim');
    			$re_password = I('post.re_password','','trim');
    			if (empty($old_password) || empty($password) || empty($re_password)) {
    				$this->ajaxOutput('参数错误');
    			}
    			if ($re_password != $password) {
    				$this->ajaxOutput('两次输入密码不一致');
    			}
    			if (!preg_match('/^[A-Za-z0-9]{6,16}$/', $password)) {
	                $this->ajaxOutput("密码必须是6至16位英文或数字");
	            }
	            if ($this->agUserInfo['password'] != md5($old_password)) {
	            	$this->ajaxOutput("旧登录密码错误");
	            }
	            M('admin_user')->where(['user_id'=> $this->agUserInfo['user_id']])->save(['password' => md5($password)]);
    		} else {
    			// 修改支付密码
    			$old_pay_password = I('post.old_pay_password','','trim');
    			$pay_password = I('post.pay_password','','trim');
    			$re_pay_password = I('post.re_pay_password','','trim');
    			if (empty($pay_password) || empty($re_pay_password)) {
    				$this->ajaxOutput('参数错误');
    			}
    			if ($pay_password != $re_pay_password) {
    				$this->ajaxOutput('两次输入密码不一致');
    			}
    			if (!preg_match('/^[A-Za-z0-9]{6,16}$/', $pay_password)) {
	                $this->ajaxOutput("密码必须是6至16位英文或数字");
	            }
	            if (!empty($this->agUserInfo['pay_password'])) {
	            	if (empty($old_pay_password)) {
	            		$this->ajaxOutput('请输入旧资金密码');
	            	}
	            	if ($this->agUserInfo['pay_password'] != md5($old_pay_password)) {
		            	$this->ajaxOutput("旧登录密码错误");
		            }
	            }
	            M('admin_user')->where(['user_id'=> $this->agUserInfo['user_id']])->save(['pay_password' => md5($pay_password)]);
    		}
    		$this->ajaxOutput("修改成功", 1, U('settings/index'));
    	} else {
    		$this->assign('agent', $this->agUserInfo);
    		$this->display();
    	}
    }

    // 提现
    public function draw(){
    	if (IS_POST) {
			$type = I('post.type');
    		if ($type == 'editDraw') {
    			// 修改提现信息
    			$bank_name = I('post.bank_name','','htmlspecialchars,trim');
    			$branch_bank = I('post.branch_bank','','htmlspecialchars,trim');
    			$real_name = I('post.real_name','','htmlspecialchars,trim');
    			$account_number = I('post.account_number','','htmlspecialchars,trim');
    			if (empty($bank_name) || empty($branch_bank) || empty($real_name) || empty($account_number)) {
    				$this->ajaxOutput('参数错误');
    			}
    			if (!preg_match('/^\d+$/', $account_number)) {
	                $this->ajaxOutput("银行账号必须是数字");
	            }
	            M('admin_user')->where(['user_id'=> $this->agUserInfo['user_id']])->save([
	            	'bank_name' => $bank_name,
	            	'branch_bank' => $branch_bank,
	            	'real_name' => $real_name,
	            	'account_number' => $account_number,
	            ]);
	            $this->ajaxOutput("修改成功", 1, U('settings/security'));
    		} else {
    			// 提现
    			$apply_cash = I('post.apply_cash',0,'floatval');
	    		$pay_password = I('post.pay_password','','trim');
	    		if (empty($apply_cash)) {
	    			$this->ajaxOutput('提现资金不能为空');
	    		}
	            if (empty($pay_password)) {
	                $this->ajaxOutput('资金密码不能为空');
	            }
	            $min_draw = getConfig('min_draw');
	            $max_draw = getConfig('max_draw');
	            if ($apply_cash < $min_draw) {
	                $this->ajaxOutput("提现金额不能少于{$min_draw}");
	            }
	            if ($apply_cash > $max_draw) {
	                $this->ajaxOutput("提现金额不能大于{$max_draw}");
	            }
	            $adminInfo = $this->agUserInfo;
	            if (empty($adminInfo['pay_password'])) {
	            	$this->ajaxOutput('请先设置资金密码');
	            }
	    		if (empty($adminInfo['account_number']) || empty($adminInfo['bank_name']) || empty($adminInfo['real_name']) || empty($adminInfo['branch_bank'])) {
	    			$this->ajaxOutput("请先保存银行卡信息");
	    		}
	    		if (md5($pay_password) != $adminInfo['pay_password']) {
	    			$this->ajaxOutput('资金密码错误');
	    		}
	    		if (bccomp($apply_cash, $adminInfo['balance']) == 1) {
	    			$this->ajaxOutput("余额不足");
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
	    			// 记录流水
	                M('admin_waste_book')->add([
	                    'user_id'=> $adminInfo['user_id'],
	                    'before_balance'=> $adminInfo['balance'],
	                    'after_balance'=> $balance,
	                    'change_balance'=> -$apply_cash,
	                    'type'=> 2,
	                    'add_time'=> time(),
	                ]);
	    		}
	    		$this->ajaxOutput("提款成功", 1, U('Settings/index'));
    		}
	    } else {
	    	$this->assign('agent', $this->agUserInfo);
	    	$this->display();
	    }
    }

    public function report() {
    	$this->display();
    }

    public function profitLog() {
    	$start_date = I('get.start_date');
    	$end_date = I('get.end_date');
    	if (empty($start_date)) {
    		$start_date = date('Y-m-1');
    	}
    	if (empty($end_date)) {
    		$end_date = date('Y-m-d');
    	}
    	$where = [
    		'admin_id'=> $this->agUserInfo['user_id'],
    		'add_time'=> [['egt', strtotime($start_date)],['lt', strtotime($end_date)+86400]],
    		'commission'=> ['gt', 0],
    	];
    	$admin_income = M('admin_income');
    	$count = $admin_income->where($where)->count();
    	$pageInfo = setAppPage($count);
    	$list = $admin_income->where($where)->limit($pageInfo['limit'])->field('commission,add_time')->select();
    	$this->assign('start_date', $start_date);
    	$this->assign('end_date', $end_date);
    	$this->assign('pageInfo', $pageInfo);
    	$this->assign('list', $list);
    	$this->display();
    }

    public function drawLog() {
    	$start_date = I('get.start_date');
    	$end_date = I('get.end_date');
    	if (empty($start_date)) {
    		$start_date = date('Y-m-1');
    	}
    	if (empty($end_date)) {
    		$end_date = date('Y-m-d');
    	}
    	$where = [
    		'user_id'=> $this->agUserInfo['user_id'],
    		'type'=> 2,
    		'add_time'=> [['egt', strtotime($start_date)],['lt', strtotime($end_date)+86400]],
    		'sync'=> ['neq', 2],
    	];
    	$draw_cash = M('draw_cash');
    	$count = $draw_cash->where($where)->count();
    	$pageInfo = setAppPage($count);
    	$list = $draw_cash->where($where)->limit($pageInfo['limit'])->field('apply_cash,add_time')->select();
    	$this->assign('start_date', $start_date);
    	$this->assign('end_date', $end_date);
    	$this->assign('pageInfo', $pageInfo);
    	$this->assign('list', $list);
    	$this->display();
    }
}