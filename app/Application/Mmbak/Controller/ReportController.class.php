<?php
namespace Mmbak\Controller;
use Mmbak\Controller\BaseController;
class ReportController extends BaseController {
    public function index() {
    	$type = I('get.type');
    	$start_time = I('get.start_time');
    	$end_time = I('get.end_time');

        $admin_user = M('admin_user');
    	$admin_income = M('admin_income');
        $admin_id = M('admin_user')->where(['pid'=> 0])->getField('user_id');
    	$where = ['admin_id'=> $admin_id];
        $type_name = '';
        // 总收入
        $total_income = $admin_income->where(['admin_id'=> $admin_id])->sum('commission');
		// 按时间搜索
		if (!empty($start_time)) {
			$where['add_time'][] = ['egt', strtotime($start_time)];
		}
		if (!empty($end_time)) {
			$where['add_time'][] = ['lt', strtotime($end_time) + 86400];
		}
    	switch ($type) {  			
			case 'today':
				$where['add_time'][] = ['egt', strtotime(date('Y-m-d'))];
                $type_name = "今天";
				break;    			
			case 'yesterday':
				$where['add_time'][] = ['egt', strtotime('yesterday')];
				$where['add_time'][] = ['lt', strtotime(date('Y-m-d'))];
                $type_name = "昨天";
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
                $type_name = "上周";
				break;
			case 'thisweek':
                $thisweek = strtotime('this monday');
                if (time() > $thisweek) {
                    $where['add_time'][] = ['egt', $thisweek];
                } else {
                    $where['add_time'][] = ['egt', $thisweek - 3600*24*7];
                }
                $type_name = "本周";
				break;
			case 'thisissue':
				$issueArr = M('lottery_issue')->group('lottery_id')->field('max(issue) as issue')->select();
				$issues = [];
				foreach ($issueArr as $key => $value) $issues[] = $value['issue'];
				$where['issue'] = ['in', $issues];
                $type_name = "本期";
                break;
            case 'all':
			default: 
				$type = 'all';
                $type_name = "全部";
		}
        // 时间段收入
        $type_income = $admin_income->where($where)->sum('commission');
        $type_income = $type_income ? $type_income : "0.00";
        $total_income = $total_income ? $total_income : "0.00";

        // 时间名称
        if (!empty($start_time) && !empty($end_time)) {
            $type_name = date('Y.m.d', strtotime($start_time)).'-'.date('Y.m.d', strtotime($end_time));
        }

    	$this->assign('type', $type);
    	$this->assign('start_time', $start_time);
    	$this->assign('end_time', $end_time);
        $this->assign('total_income', $total_income);
        $this->assign('type_income', $type_income);
    	$this->assign('type_name', $type_name);
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