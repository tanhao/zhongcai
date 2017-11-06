<?php
namespace Adminbak\Controller;
use Adminbak\Controller\BaseController;
class StatController extends BaseController {
	// 新增
    public function increase() {
    	$start_time = I('get.start_time');
    	$end_time = I('get.end_time');
    	$invite_code = M('admin_user')->where(['user_name'=> session('admin_name')])->getField('invite_code');
    	$where = ['invite_code'=> $invite_code];
    	$user = M('User');
    	$min_time = $user->where($where)->min('add_time');
    	$min_time = is_null($min_time) ? strtotime(date('Y-m-d')) : strtotime(date('Y-m-d',$min_time));
    	$max_time = strtotime(date('Y-m-d')) + 86400;
    	if (!empty($start_time) && strtotime($start_time) > $min_time) {
    		$min_time = strtotime($start_time);
    	}
    	if (!empty($end_time) && (strtotime($end_time) + 86400) < $max_time) {
    		$max_time = strtotime($end_time) + 86400;
    	}
    	$count = floor(($max_time - $min_time) / 86400);
    	$PageObject = new \Think\Page($count,15);
    	$list = [];
    	for ($i=0; $i < 15; $i++) { 
            $a = $max_time - ($PageObject->firstRow + $i + 1) * 86400;
            $b = $max_time - ($PageObject->firstRow + $i) * 86400;
    		if ($a < $min_time) {
    			break;
    		}
    		$where['add_time'] = [['egt',$a],['lt', $b]];
    		$number = $user->where($where)->count();
    		$number = $number ? $number : 0;
    		$list[] = [
    			'date' => date('Y-m-d', $a),
    			'number' => $number,
    		];
    	}
    	$this->assign('start_time', $start_time);
    	$this->assign('end_time', $end_time);
    	$this->assign('list', $list);
    	$this->assign('page_show', $PageObject->show());
    	$this->display();
    }

    // 活跃
    public function active() {
    	$start_time = I('get.start_time');
    	$end_time = I('get.end_time');
    	$db_prefix = C('DB_PREFIX');
    	$invite_code = M('admin_user')->where(['user_name'=> session('admin_name')])->getField('invite_code');
    	// 查询条件
    	$where = ['u.invite_code'=> $invite_code];
    	$login_log = M('login_log');
    	$min_time = $login_log
    		->join("as l left join {$db_prefix}user as u on l.user_id=u.user_id")
    		->where($where)
    		->min('l.add_time');
    	$min_time = is_null($min_time) ? strtotime(date('Y-m-d')) : strtotime(date('Y-m-d',$min_time));
    	$max_time = strtotime(date('Y-m-d')) + 86400;
    	if (!empty($start_time) && strtotime($start_time) > $min_time) {
    		$min_time = strtotime($start_time);
    	}
    	if (!empty($end_time) && (strtotime($end_time) + 86400) < $max_time) {
    		$max_time = strtotime($end_time) + 86400;
    	}
    	$count = floor(($max_time - $min_time) / 86400);
    	$PageObject = new \Think\Page($count,15);
    	// 列表
    	$list = [];
    	for ($i=0; $i < 15; $i++) { 
            $a = $max_time - ($PageObject->firstRow + $i + 1) * 86400;
            $b = $max_time - ($PageObject->firstRow + $i) * 86400;
            if ($a < $min_time) {
                break;
            }
    		$where['l.add_time'] = [['egt',$a],['lt', $b]];
    		// 一天内总活跃人数
    		$total_number = $login_log
	    		->join("as l left join {$db_prefix}user as u on l.user_id=u.user_id")
	    		->where($where)
	    		->count("DISTINCT l.user_id");
	    	$total_number = $total_number ? $total_number : 0;
    		// 一天新用户活跃人数
    		$where1 = $where;
    		$where1['u.add_time'] = [['egt',$a],['lt', $b]];
    		$new_number = $login_log
	    		->join("as l left join {$db_prefix}user as u on l.user_id=u.user_id")
	    		->where($where1)
	    		->count("DISTINCT l.user_id");
    		$new_number = $new_number ? $new_number : 0;
    		$old_number = $total_number - $new_number;
    		$list[] = [
    			'date' => date('Y-m-d', $a),
    			'new_number' => $new_number,
    			'old_number' => $old_number,
    			'total_number' => $total_number,
    		];
    	}

    	$this->assign('start_time', $start_time);
    	$this->assign('end_time', $end_time);
    	$this->assign('list', $list);
    	$this->assign('page_show', $PageObject->show());
    	$this->display();
    }

    // 留存
    public function retention() {
    	$start_time = I('get.start_time');
        $end_time = I('get.end_time');
        $db_prefix = C('DB_PREFIX');
        $invite_code = M('admin_user')->where(['user_name'=> session('admin_name')])->getField('invite_code');
        // 查询条件
        $where = ['u.invite_code'=> $invite_code];
        $login_log = M('login_log');
        $min_time = $login_log
            ->join("as l left join {$db_prefix}user as u on l.user_id=u.user_id")
            ->where($where)
            ->min('l.add_time');
        $min_time = is_null($min_time) ? strtotime(date('Y-m-d')) : strtotime(date('Y-m-d',$min_time));
        $max_time = strtotime(date('Y-m-d')) + 86400;
        if (!empty($start_time) && strtotime($start_time) > $min_time) {
            $min_time = strtotime($start_time);
        }
        if (!empty($end_time) && (strtotime($end_time) + 86400) < $max_time) {
            $max_time = strtotime($end_time) + 86400;
        }
        $count = floor(($max_time - $min_time) / 86400);
        $PageObject = new \Think\Page($count,15);
        // 列表
        $list = [];
        for ($i=0; $i < 15; $i++) { 
            $a = $max_time - ($PageObject->firstRow + $i + 1) * 86400;
            $b = $max_time - ($PageObject->firstRow + $i) * 86400;
            if ($a < $min_time) {
                break;
            }
            $where['l.add_time'] = [['egt',$a],['lt', $b]];
            // 获取留存
            $col_2 = $this->getRetention($a, $invite_code, 2);
            $col_3 = $this->getRetention($a, $invite_code, 3);
            $col_4 = $this->getRetention($a, $invite_code, 4);
            $col_5 = $this->getRetention($a, $invite_code, 5);
            $col_6 = $this->getRetention($a, $invite_code, 6);
            $col_7 = $this->getRetention($a, $invite_code, 7);
            $col_14 = $this->getRetention($a, $invite_code, 14);
            $col_30 = $this->getRetention($a, $invite_code, 30);
            $list[] = [
                'date' => date('Y-m-d', $a),
                'col_2' => $col_2,
                'col_3' => $col_3,
                'col_4' => $col_4,
                'col_5' => $col_5,
                'col_6' => $col_6,
                'col_7' => $col_7,
                'col_14' => $col_14,
                'col_30' => $col_30,
            ];
        }

        $this->assign('start_time', $start_time);
        $this->assign('end_time', $end_time);
        $this->assign('list', $list);
        $this->assign('page_show', $PageObject->show());
        $this->display();
    }

    // 获取某天的n日留存
    private function getRetention($time, $invite_code, $n) {
        // N日前用户注册数
        $a = $time - ($n-1) * 86400;
        $b = $time - ($n-2) * 86400;
        $user_ids = M('user')->where(['add_time'=> [['egt',$a],['lt', $b]], 'invite_code'=> $invite_code])->getField('user_id', true);
        if (empty($user_ids)) {
            return 0;
        }
        $c = $time;
        $d = $time + 86400;
        $count = M('login_log')->where(['add_time'=> [['egt',$c],['lt', $d]], 'user_id'=> ['in', $user_ids]])->count("DISTINCT user_id");
        $result = bcdiv($count, count($user_ids), 2);
        return $result * 100;
    }
}