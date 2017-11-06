<?php
namespace Adminbak\Controller;
use Adminbak\Controller\BaseController;
class BetController extends BaseController {
    public function index() {
    	$game_id = I('get.game_id', 0, 'intval');
    	$game_id = $game_id > 0 && $game_id <=10 ? $game_id : 1;
    	$start_time = I('get.start_time');
    	$end_time = I('get.end_time');
    	$user_name = I('get.user_name');
    	$where = [];
    	if (!empty($start_time) || !empty($end_time)) {
    		// 按时间搜索
    		if (!empty($start_time)) {
    			$where['b.add_time'][] = ['egt', strtotime($start_time)];
    		}
    		if (!empty($end_time)) {
    			$where['b.add_time'][] = ['lt', strtotime($end_time) + 86400];
    		}
    	}
    	if ($game_id > 0) {
    		$where['b.room_id'] = ['like', $game_id . '-%'];
    	}
    	if (!empty($user_name)) {
    		$where['u.user_name'] = $user_name;
    	}
    	$invite_code = M('admin_user')->where(['user_name'=> session('admin_name')])->getField('invite_code');
    	$where['u.invite_code'] = $invite_code;
    	// 分页
    	$count = M('bet_log')->join("as b left join zc_user as u on b.user_id = u.user_id")->where($where)->count();
        $PageObject = new \Think\Page($count,15);
    	// 查询列表
    	$list = M('bet_log')
	    	->join("as b left join zc_user as u on b.user_id = u.user_id")
	    	->field('b.user_id,b.room_id,b.issue,b.is_host,b.bet_balance,b.profit_balance,b.commission,b.add_time,u.user_name,u.nickname')
	    	->where($where)
            ->order('b.id desc')
	    	->limit($PageObject->firstRow.','.$PageObject->listRows)
	    	->select();
	    $lotteryArr = [1=>'北京赛车',2=>'时时彩',3=>'幸运飞艇'];
	    foreach ($list as $key => $value) {
	    	list($_game_id, $site_id) = explode('-', $value['room_id']);
	    	$gameInfo = D('Game')->getGameInfo($_game_id);
	    	$list[$key]['name'] = $lotteryArr[$gameInfo['lottery_id']] . '(' . $gameInfo['game_name'] . ')';
	    	$siteInfo = D('Site')->getSiteInfo($site_id);
	    	$list[$key]['site_name'] = $siteInfo['site_name'];
	    }
    	$this->assign('game_id', $game_id);
    	$this->assign('start_time', $start_time);
    	$this->assign('end_time', $end_time);
    	$this->assign('user_name', $user_name);
    	$this->assign('list', $list);
    	$this->assign('page_show', $PageObject->show());
    	$this->display();
    }
}