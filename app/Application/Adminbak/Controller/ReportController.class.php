<?php
namespace Adminbak\Controller;
use Adminbak\Controller\BaseController;
class ReportController extends BaseController {
    public function index() {
    	$type = I('get.type');
    	$start_time = I('get.start_time');
    	$end_time = I('get.end_time');
    	$user_name = I('get.user_name');
    	$user = M('user');
    	$admin_user = M('admin_user');

    	$admin_id = $admin_user->where(['user_name'=> session('admin_name')])->getField('user_id');
    	$where = ['admin_id'=> $admin_id];
		// 按时间搜索
		if (!empty($start_time)) {
			$where['add_time'][] = ['egt', strtotime($start_time)];
		}
		if (!empty($end_time)) {
			$where['add_time'][] = ['lt', strtotime($end_time) + 86400];
		}
    	switch ($type) {
			case 'all':
				break;    			
			case 'today':
				$where['add_time'][] = ['egt', strtotime(date('Y-m-d'))];
				break;    			
			case 'yesterday':
				$where['add_time'][] = ['egt', strtotime('yesterday')];
				$where['add_time'][] = ['lt', strtotime(date('Y-m-d'))];
				break;    			
			case 'lastweek':
                $thisweek = strtotime('this monday');
                if (time() > $thisweek) {
                    $where['add_time'][] = ['egt', $thisweek - 3600*24*7];
                    $where['add_time'][] = ['lt', $thisweek];
                } else {
                    $where['add_time'][] = ['egt', $thisweek - 3600*24*14];
                    $where['add_time'][] = ['lt', $thisweek - 3600*24*7];
                }
				break;
			case 'thisweek':
                $thisweek = strtotime('this monday');
                if (time() > $thisweek) {
                    $where['add_time'][] = ['egt', $thisweek];
                } else {
                    $where['add_time'][] = ['egt', $thisweek - 3600*24*7];
                }
				break;
			case 'thisissue':
				$issueArr = M('lottery_issue')->group('lottery_id')->field('max(issue) as issue')->select();
				$issues = [];
				foreach ($issueArr as $key => $value) $issues[] = $value['issue'];
				$where['issue'] = ['in', $issues];
                break;
			default: 
				$type = 'all';
		}
    	// 搜索用户帐号
    	if (!empty($user_name)) {
    		$user_id = $user->where(['user_name'=>$user_name])->getField('user_id');
    		$user_id = $user_id ? $user_id : 0;
    		$where['user_id'] =  $user_id;
    	}
    	$admin_income = M('admin_income');
    	$count = $admin_income->where($where)->count('DISTINCT user_id');
        $PageObject = new \Think\Page($count,15);
    	// 列表
    	$list = $admin_income->where($where)->group('user_id')
    	    ->field('user_id,count(user_id) as count,sum(bet_balance) as bet_balance,sum(profit_balance) as profit_balance,sum(win_balance) as win_balance,sum(commission) as commission')
    	    ->limit($PageObject->firstRow.','.$PageObject->listRows)->select();
    	foreach ($list as $key => $value) {
    		$userInfo = $user->where(['user_id'=> $value['user_id']])->field('user_name,nickname,invite_code')->find();
    		$list[$key]['user_name'] = $userInfo['user_name'];
    		$list[$key]['nickname'] = $userInfo['nickname'];
    		$list[$key]['rate'] = $this->getRateToAdmin($admin_id, $userInfo['invite_code']);
    	}
    	// 总收入
    	$total_income = $admin_income->where(['admin_id'=> $admin_id])->sum('commission');
    	$total_income = $total_income ? $total_income : "0.00";
    	$this->assign('type', $type);
    	$this->assign('user_name', $user_name);
    	$this->assign('start_time', $start_time);
    	$this->assign('end_time', $end_time);
    	$this->assign('list', $list);
    	$this->assign('total_income', $total_income);
    	$this->assign('page_show', $PageObject->show());
    	$this->display();
    }

    private function getRateToAdmin($admin_id, $invite_code) {
    	$admin_user = M('admin_user');
    	// 5
    	$adminInfo = $admin_user->where(['invite_code'=> $invite_code])->field('user_id,pid,rate')->find();
    	if (empty($adminInfo)) {
    		return "0.00";
    	}
    	if ($adminInfo['user_id'] == $admin_id) {
    		return $adminInfo['rate'];
    	}
    	$rate = $adminInfo['rate'];
    	// 4
    	$adminInfo = $admin_user->where(['user_id'=> $adminInfo['pid']])->field('user_id,pid,rate')->find();
    	if (empty($adminInfo)) {
    		return "0.00";
    	}
    	if ($adminInfo['user_id'] == $admin_id) {
    		return bcsub($adminInfo['rate'], $rate, 2);
    	}
    	$rate = $adminInfo['rate'];
    	// 3
    	$adminInfo = $admin_user->where(['user_id'=> $adminInfo['pid']])->field('user_id,pid,rate')->find();
    	if (empty($adminInfo)) {
    		return "0.00";
    	}
    	if ($adminInfo['user_id'] == $admin_id) {
    		return bcsub($adminInfo['rate'], $rate, 2);
    	}
    	$rate = $adminInfo['rate'];
    	// 2
    	$adminInfo = $admin_user->where(['user_id'=> $adminInfo['pid']])->field('user_id,pid,rate')->find();
    	if (empty($adminInfo)) {
    		return "0.00";
    	}
    	if ($adminInfo['user_id'] == $admin_id) {
    		return bcsub($adminInfo['rate'], $rate, 2);
    	}
    	// 1
    	$adminInfo = $admin_user->where(['user_id'=> $adminInfo['pid']])->field('user_id,pid,rate')->find();
    	if (empty($adminInfo) || $adminInfo['user_id'] != $admin_id) {
    		return "0.00";
    	}
    	return bcsub($adminInfo['rate'], $rate, 2);
    }
}