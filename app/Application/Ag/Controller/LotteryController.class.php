<?php
namespace Ag\Controller;
class LotteryController extends BaseController {
    public function index() {
    	$this->display();
    }

    public function ajaxGetLottery() {
		$lottery_id = I('get.lottery_id', 1, 'intval');
    	$date = I('get.date');
        $lottery_id = in_array($lottery_id, [1,2,3]) ? $lottery_id : 1;
    	$lottery_issue = M('lottery_issue');
        $where = ['lottery_id'=> $lottery_id];
        if (empty($date)) {
            $date = date('Y-m-d');
        }
        $where['add_time'] = [
        	['egt', strtotime($date)],
        	['lt', strtotime($date)+86400],
        ];
        $list = $lottery_issue->where($where)->order('id desc')->select();
        $lottery_name = $lottery_id == 1 ? "北京塞车" : ($lottery_id == 2 ? "时时彩" : "幸运飞艇");
        $str = '';
        foreach ($list as $key => $value) {
        	$number = str_replace('0', '10', implode('-', str_split($value['lottery_number'])));
        	$str .= '<li class="mui-table-view-cell">';
        	$str .= "<a class='mui-navigate-right' href='".U('Lottery/betDetail',['id'=>$value['id']])."'>";
        	$str .= "<h4>{$lottery_name} - {$value['issue']} (官方)</h4>";
        	$str .= "<p class='mui-ellipsis'>{$number} <span class='mui-pull-right'>".date('Y-m-d H:i:s',$value['add_time'])."</span></p></a></li>";
        }
        echo $str;
        exit;
    }

    public function betDetail() {
    	$id = I('get.id', 0, 'intval');
    	$lottery_issue = M('lottery_issue');
    	$issueInfo = $lottery_issue->where(['id'=>$id])->field('lottery_id,issue')->find();
    	if (empty($issueInfo)) {
    		$this->error('下注详细不存在');
    	}
    	$user = M('user');
    	$user_ids = M('user')->where(['invite_code'=> $this->agUserInfo['invite_code']])->getField('user_id', true);
    	$lottery_name = $issueInfo['lottery_id'] == 1 ? "北京塞车" : ($issueInfo['lottery_id'] == 2 ? "时时彩" : "幸运飞艇");
    	$list = [];
    	if (!empty($user_ids)) {
    		$bet_log = M('bet_log');
	    	$tempList = $bet_log->where(['lottery_id'=> $issueInfo['lottery_id'], 'issue'=> $issueInfo['issue'], 'is_host'=> 0, 'user_id'=>['in', $user_ids]])->field('room_id,bet_detail,add_time,user_id')->select();
	    	$site = D('Site');
        	$game = D('Game');
	    	foreach ($tempList as $key => $value) {
	    		$userInfo = $user->where(['user_id'=> $value['user_id']])->field('user_name,nickname')->find();
	    		$zoneList = json_decode($value['bet_detail'], true);
	    		$siteInfo = $site->getSiteInfo($value['room_id']);
	            $gameInfo = $game->getGameInfo($siteInfo['game_id']);
	    		foreach ($zoneList as $k => $v) {
	    			$list[] = [
	    				'zone'=> $v['zone'],
	    				'user_name'=> $userInfo['user_name'],
	    				'nickname'=> $userInfo['nickname'],
	    				'lottery_name'=> $lottery_name,
	    				'site_name'=> $siteInfo['site_name'],
	    				'game_name'=> $gameInfo['game_name'],
	    				'issue'=> $value['issue'],
	    				'balance'=> $v['balance'],
	    				'add_time'=>$value['add_time'],

	    			];
	    		}
	    	}

    	}
	    $this->assign('list', $list);
	    $this->display();	
    }
}