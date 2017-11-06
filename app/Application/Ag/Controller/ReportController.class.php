<?php
namespace Ag\Controller;
class ReportController extends BaseController {
	// 直属玩家报表
    public function players(){
    	$user_name = I('get.user_name','','trim');
    	$user = M('user');
    	$where = ['admin_id'=> $this->agUserInfo['user_id']];
    	// 搜索用户帐号
    	if (!empty($user_name)) {
    		$user_id = $user->where(['_complex'=>['user_name'=>$user_name,'nickname'=>$user_name,'_logic'=>'OR']])->getField('user_id',true);
    		$where['user_id'] = !empty($user_id) ? ['in', $user_id] : 0;
    	}
    	$admin_income = M('admin_income');
    	$count = $admin_income->where($where)->count('DISTINCT user_id');
        $pageInfo = setAppPage($count);
    	// 列表
    	$list = $admin_income->where($where)->group('user_id')
    	    ->field('user_id,count(user_id) as count,sum(bet_balance) as bet_balance,sum(profit_balance) as profit_balance,sum(win_balance) as win_balance,sum(commission) as commission')
    	    ->limit($pageInfo['limit'])->select();
    	$admin_user = M('admin_user');
    	foreach ($list as $key => $value) {
    		$userInfo = $user->where(['user_id'=> $value['user_id']])->field('user_name,nickname,invite_code')->find();
    		$list[$key]['user_name'] = $userInfo['user_name'][0].'******'.substr($userInfo['user_name'], -1);
    		$list[$key]['nickname'] = $userInfo['nickname'];
    		$pid = $admin_user->where(['invite_code'=>$userInfo['invite_code']])->getField('user_id');
    		$list[$key]['rate'] = $this->getRateToAdmin(0, $pid);
    	}
    	// 总收入
    	$total_income = $admin_income->where(['admin_id'=> $this->agUserInfo['user_id']])->sum('commission');
    	$total_income = $total_income ? $total_income : "0.00";
    	$this->assign('user_name', $user_name);
    	$this->assign('list', $list);
    	$this->assign('total_income', $total_income);
    	$this->assign('pageInfo', $pageInfo);
    	$this->display();
    }

    private function getRateToAdmin($rate, $pid) {
    	$admin_user = M('admin_user');
    	$adminInfo = $admin_user->where(['user_id'=> $pid])->field('user_id,pid,rate')->find();
    	if (empty($adminInfo)) {
    		return '0.00';
    	}
    	if ($this->agUserInfo['user_id'] == $adminInfo['user_id']) {
    		return bcsub($adminInfo['rate'], $rate, 2);
    	} else {
    		return $this->getRateToAdmin($adminInfo['rate'], $adminInfo['pid']);
    	}
    }

    public function agent() {
        $user_name = I('get.user_name');
        $admin_user = M('admin_user');
        // 查询条件
        $where = ['pid'=> $this->agUserInfo['user_id']];
        if (!empty($user_name)) {
        	$where['user_name'] = $user_name;
        }
        $count = $admin_user->where($where)->count();
        $pageInfo = setAppPage($count);
        $list = $admin_user->where($where)->order('user_id asc')->limit($pageInfo['limit'])->field("user_id,user_name,rate")->select();
        $admin_income = M('admin_income');
        $total_income = "0.00";
        foreach ($list as $key => $value) {
            $incomeInfo = $admin_income->where(['admin_id'=>$value['user_id']])
	    	    ->field('count(user_id) as count,sum(bet_balance) as bet_balance,sum(profit_balance) as profit_balance,sum(win_balance) as win_balance,sum(commission) as commission')
	    	    ->limit($pageInfo['limit'])->find();
	    	$list[$key]['count'] = !empty($incomeInfo['count']) ? $incomeInfo['count'] : 0;
	    	$list[$key]['bet_balance'] = !empty($incomeInfo['bet_balance']) ? $incomeInfo['bet_balance'] : "0.00";
	    	$list[$key]['profit_balance'] = !empty($incomeInfo['profit_balance']) ? $incomeInfo['profit_balance'] : "0.00";
	    	$list[$key]['win_balance'] = !empty($incomeInfo['win_balance']) ? $incomeInfo['win_balance'] : "0.00";
	    	$list[$key]['commission'] = !empty($incomeInfo['commission']) ? $incomeInfo['commission'] : "0.00";
	    	$list[$key]['myrate'] = $this->getRateToAdmin(0, $value['user_id']);
	    	$list[$key]['mycommission'] = bcdiv(bcmul($list[$key]['win_balance'], $list[$key]['rate'], 4), 100, 2);
	    	$total_income = bcadd($total_income, $list[$key]['mycommission'], 2);
        }

    	$this->assign('total_income', $total_income);
        $this->assign('user_name', $user_name);
        $this->assign('list', $list);
    	$this->assign('pageInfo', $pageInfo);
    	$this->display();
    }
}