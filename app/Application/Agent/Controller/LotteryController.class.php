<?php
namespace Agent\Controller;
class LotteryController extends BaseController {
    public function index() {
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
        $count = $lottery_issue->where($where)->count();
        $pageInfo = setAppPage($count);
        $list = $lottery_issue->where($where)->order('id desc')->limit($pageInfo['limit'])->select();
        $user_ids = M('user')->where(['invite_code'=> $this->agUserInfo['invite_code']])->getField('user_id', true);
        $bet_log = M('bet_log');
        foreach ($list as $key => $value) {
            $list[$key]['lottery_number'] = explode(',', str_replace('0', '10', implode(',', str_split($value['lottery_number']))));
            $bet_balance = "0.00";
            if (!empty($user_ids)) {
                $bet_balance = $bet_log->where(['lottery_id'=> $lottery_id, 'issue'=> $value['issue'], 'is_host'=> 0, 'user_id'=>['in', $user_ids]])->sum('bet_balance');
            }
            $list[$key]['bet_balance'] = !empty($bet_balance) ? $bet_balance : "0.00";
        }
        $lottery_name = $lottery_id == 1 ? "北京塞车" : ($lottery_id == 2 ? "重庆时时彩" : "幸运飞艇");
        $this->assign('lottery_name', $lottery_name);
        $this->assign('date', $date);
        $this->assign('lottery_id', $lottery_id);
        $this->assign('list', $list);
        $this->assign('pageInfo', $pageInfo);
    	$this->display();
    }

    public function betDetail() {
    	$id = I('get.id', 0, 'intval');
    	$lottery_issue = M('lottery_issue');
    	$issueInfo = $lottery_issue->where(['id'=>$id])->field('lottery_id,issue,lottery_number,add_time')->find();
    	if (empty($issueInfo)) {
    		$this->error('下注详细不存在');
    	}
    	$user = M('user');
    	$user_ids = M('user')->where(['invite_code'=> $this->agUserInfo['invite_code']])->getField('user_id', true);
    	$list = [];
    	if (!empty($user_ids)) {
    		$bet_log = M('bet_log');
	    	$tempList = $bet_log->where(['lottery_id'=> $issueInfo['lottery_id'], 'issue'=> $issueInfo['issue'], 'is_host'=> 0, 'user_id'=>['in', $user_ids]])->field('room_id,bet_detail,add_time,user_id')->select();
	    	$site = D('Site');
        	$game = D('Game');
            $prate = M('admin_user')->where(['pid'=>0])->getField('rate');
	    	foreach ($tempList as $key => $value) {
	    		$userInfo = $user->where(['user_id'=> $value['user_id']])->field('user_name,nickname')->find();
	    		$zoneList = json_decode($value['bet_detail'], true);
	    		$siteInfo = $site->getSiteInfo($value['room_id']);
	            $gameInfo = $game->getGameInfo($siteInfo['game_id']);
	    		foreach ($zoneList as $k => $v) {
	    			$list[] = [
	    				'zone'=> $v['zone'],
	    				'user_name'=> $this->hideUserName($userInfo['user_name']),
	    				'nickname'=> $userInfo['nickname'],
	    				'site_name'=> $siteInfo['site_name'],
	    				'game_name'=> $gameInfo['game_name'],
	    				'issue'=> $value['issue'],
                        'balance'=> $v['balance'],
                        'win_balance'=> $v['win_balance'],
                        'commission'=> bcdiv(bcmul($v['commission'], $this->agUserInfo['rate'], 4), $prate, 2),
	    				'rate'=> $this->agUserInfo['rate'],
	    				'add_time'=>$value['add_time'],

	    			];
	    		}
	    	}

    	}
        $issueInfo['lottery_name'] = $issueInfo['lottery_id'] == 1 ? "北京塞车" : ($issueInfo['lottery_id'] == 2 ? "时时彩" : "幸运飞艇");
        $issueInfo['lottery_number'] = explode(',', str_replace('0', '10', implode(',', str_split($issueInfo['lottery_number']))));
        $this->assign('issueInfo', $issueInfo);
	    $this->assign('list', $list);
	    $this->display();	
    }
}