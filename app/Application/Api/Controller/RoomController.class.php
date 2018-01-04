<?php
namespace Api\Controller;
use Lib\Enum\CodeEnum;
use Lib\Enum\CacheEnum;

class RoomController extends BaseController {

	 /**
     * @desc 进入房间
     * @param room_id 房间ID
     * @param client_id 客户端ID
     * @param token     用户TOKEN
     * @return 
     */
	public function index() {
		$room_id = I('get.room_id', 0, 'intval');
		$client_id = I('get.client_id');
		if ($room_id < 1) {
			$this->ajaxReturn(output(CodeEnum::PARAM_ERROR));
		}
		$gameModel = D('Game');
    	$siteModel = D('Site');
    	$lotteryModel = D('Lottery');
    	// 获取 $game_id, $lottery_id
		$siteInfo = $siteModel->getSiteInfo($room_id);
		$gameInfo = $gameModel->getGameInfo($siteInfo['game_id']);
		if (empty($gameInfo) || empty($siteInfo)) {
			$this->ajaxReturn(output(CodeEnum::PARAM_ERROR));
		}
		$lottery_id = $gameInfo['lottery_id'];
		// 房间人数不能超过100人
		if ($siteModel->getRoomCount($room_id) >= $siteInfo['max_member']) {
			$this->ajaxReturn(output(CodeEnum::ROOM_OVER_100));
		}
		// 获取彩票状态和倒计时
		$lotteryInfo = $lotteryModel->getLotteryInfo($lottery_id);
    	$countdownInfo = $lotteryModel->getCountdown($lotteryInfo);
    	if ($countdownInfo['status'] == 4) {
    		$this->ajaxReturn(output(CodeEnum::LOTTERY_STOP));
    	}
    	// 最新开奖期数
		$issueInfo = M('lottery_issue')->where(['lottery_id' => $lottery_id])->field('issue,lottery_number')->order('id desc')->find();
		// 标题
		$title = $lotteryInfo['lottery_name'].'-'.$issueInfo['issue'].'期-'.$gameInfo['game_name'].'-'.$siteInfo['site_name'];
		// 最新开奖期数
		$lottery_number = $lottery_id != 2 ? str_replace('0', '10', implode(',', str_split($issueInfo['lottery_number']))) : implode(',', str_split($issueInfo['lottery_number']));
		// 下注币列表
		$coinList = [];
		switch ($siteInfo['site_type']) {
			case '1':
				$coinList = [10, 50, 100, 500, 1000];
				break;
			case '2':
				$coinList = [100, 500, 1000, 5000, 10000];
				break;
			case '3':
				$coinList = [1, 2, 3, 4, 5];
				break;
		}
    	// 获取房间下注情况
    	$zoneDetail = [];
    	for ($i = 1; $i <= $gameInfo['zone_count']; $i++) {
    		$zoneDetail[$i] = ['zone'=> $i, 'all_balance'=> 0, 'single_balance'=> 0, 'coinDetail'=>[]];
    	}
    	// 取消下注按钮
    	$cancelInfo = ['cancel_button'=> 0, 'cancel_countdown'=> 0];
    	$if_cenceled = 0;
    	$bet_first_time = 0;
    	// 获取下注详细
    	$redis = redisCache();
    	$betDetail = $redis->lrange(CacheEnum::BET_DETAIL.$room_id, 0, -1);
    	if (!empty($betDetail)) {
    		foreach ($betDetail as $key => $value) {
    			$betInfo = json_decode($value, true);
				$zoneDetail[$betInfo['zone']]['all_balance'] += $betInfo['balance'];
				$zoneDetail[$betInfo['zone']]['coinDetail'][] = $betInfo['balance'];
				if ($betInfo['user_id'] == C('USER_ID')) {
					$zoneDetail[$betInfo['zone']]['single_balance'] += $betInfo['balance'];
					//  取消下注按钮
					if ($bet_first_time == 0) {
						$bet_first_time = $betInfo['add_time']; 
					} else {
						$bet_first_time = $bet_first_time > $betInfo['add_time'] ? $betInfo['add_time'] : $bet_first_time;
					}
					if ($betInfo['balance'] < 0) {
						$if_cenceled = 1;
					}
				}
    		}
    		$temp_time = time() - $bet_first_time;
    		if ($temp_time < 30 && $countdownInfo['status'] == 1 && $if_cenceled == 0) {
    			$cancelInfo['cancel_button'] = 1;
    			$cancelInfo['cancel_countdown']  = 30 - $temp_time > $countdownInfo['countdown'] ? $countdownInfo['countdown'] : 30 - $temp_time;
    		}
    	}
    	$zoneDetail = array_values($zoneDetail);
    	foreach ($zoneDetail as $key => $value) {
    		$zoneDetail[$key]['coinDetail'] = $this->deleteCoin($value['coinDetail']);
    	}
    	// 庄家信息 host_button:1-我要上庄，2-我要下庄，
    	$hostInfo = D('Host')->getHostInfo($room_id);
    	foreach ($hostInfo['waitHostList'] as $key => $value) {
    		if ($value['user_id'] == C('USER_ID')) {
    			$hostInfo['host_button'] = 2;break;
    		}
    	}
    	// 上庄至少金额
    	$less_host_banlance = D('Host')->getLessHostBalance($room_id);
    	// 上庄最大金额
    	$max_host_banlance = D('Host')->getMaxHostBalance($room_id);
    	/**========================================================*/
    	// 推送倒计时
    	// sendToClient($client_id, CodeEnum::COUNTDOWN_INFO, $countdownInfo);
    	// 推送下注信息
    	sendToClient($client_id, CodeEnum::BET_DETAIL, ['zoneDetail'=> $zoneDetail]);
    	// 推送庄家信息
    	sendToClient($client_id, CodeEnum::HOST_INFO, $hostInfo);
        // 推送用户余额给用户
        $balance = C('IS_TEMP') ? "0.00" : $this->userInfo['balance'];
        sendToClient($client_id, CodeEnum::LEFT_BALANCE, ['balance'=> $balance]);
        /**=================================================================*/
    	// 更新房间信息
    	M('user_token')->where(['token'=> C('TOKEN')])->save([
            'client_id' => $client_id,
            'online'=> 1, 
            'room_id'=> $room_id,
        ]);
        $this->addUserLog('进入房间', "进入{$title}");
		$this->ajaxReturn(output(CodeEnum::SUCCESS, [
			'nickname' => C('IS_TEMP') ? getTempNickname(C('USER_ID')) : $this->userInfo['nickname'],
			'lottery_id'=> $lottery_id,
			'room_id'=> $room_id,
			'game_id'=> $siteInfo['game_id'],
			'title'=> $title,
			'lottery_number'=> $lottery_number,
			'must_host' => $gameInfo['must_host'],
			'less_host_banlance' => $less_host_banlance,
			'max_host_banlance' => $max_host_banlance,
			'cancelInfo' => $cancelInfo,
			'countdownInfo' => $countdownInfo,
			'coinList'=> $coinList,
		]));
	}

	/**
     * @desc 上庄
     * @param host_balance  上庄金额
     * @param token     用户TOKEN
     * @return 
     */
	public function beHost() {
		$host_balance = I('get.host_balance', 0, 'intval');
		if ($host_balance < 1) {
			$this->ajaxReturn(output(CodeEnum::PARAM_ERROR));
		}
		// 获取房间ID
		$userTokenModel = M('user_token');
		$room_id = $userTokenModel->where([
			'online'=> 1, 
			'user_id'=> C('USER_ID'), 
		])->getField('room_id');
		if (empty($room_id)) {
			$this->ajaxReturn(output(CodeEnum::PLEASE_COME_IN_ROOM));
		}
		// 至少上庄金额
		$less_host_banlance = D('Host')->getLessHostBalance($room_id);
		if (C('IS_TEMP') || $this->userInfo['balance'] < $less_host_banlance || $host_balance < $less_host_banlance) {
			$this->ajaxReturn(output(CodeEnum::BALANCE_IS_NOT_ENOUGH_HOST, [], [$less_host_banlance]));
		}
		// 最大上庄金额
		$max_host_banlance = D('Host')->getMaxHostBalance($room_id);
		if (C('IS_TEMP') || $host_balance > $max_host_banlance) {
			$this->ajaxReturn(output(CodeEnum::BALANCE_IS_OVER_HOST, [], [$max_host_banlance]));
		}
		// 上庄金额不能大于自己余额
		if ($host_balance > $this->userInfo['balance']) {
			$this->ajaxReturn(output(CodeEnum::BALANCE_IS_NOT_ENOUGH));
		}
		$hostModel = M('host');
		$hostInfo = $hostModel->where(['user_id'=> C('USER_ID'),'room_id'=> $room_id])->field('is_delete')->find();
		if (!empty($hostInfo)) {
			// 判断自己是否已经上庄
			if ($hostInfo['is_delete'] == 0) {
				$this->ajaxReturn(output(CodeEnum::YOU_ARE_HOST));
			}
			// 判断是否当局下过庄
			if ($hostInfo['is_delete'] == 1) {
				$this->ajaxReturn(output(CodeEnum::ONLY_ONE_TIME_ON_HOST));
			}
		}
		// 随机区域
		$siteInfo = D('Site')->getSiteInfo($room_id);
        $gameInfo = D('Game')->getGameInfo($siteInfo['game_id']);
		$host_zone = 0;
		if ($gameInfo['must_host'] == 0) {
			$host_zone = rand(1, $gameInfo['zone_count']);
		}
		// 上庄
		$hostModel->add([
			'user_id'=> C('USER_ID'),
			'room_id'=> $room_id,
			'status'=> 0,
			'host_balance'=> $host_balance,
			'host_zone'=> $host_zone,
			'is_delete'=> 0,
			'add_time'=> time(),
		]);
		// 推送用户余额给用户
		$balance = bcsub($this->userInfo['balance'], $host_balance, 2);
		$ret = M('user')->where(['user_id'=> C('USER_ID')])->save(['balance'=> $balance]);
        $client_id = $userTokenModel->where(['user_id'=> C('USER_ID')])->getField('client_id');
        sendToClient($client_id, CodeEnum::LEFT_BALANCE, ['balance'=> $balance]);
        // 流水LOG
        M('user_waste_book')->add([
			'user_id'=> C('USER_ID'),
			'before_balance'=> $this->userInfo['balance'],
			'after_balance'=> $balance,
			'change_balance'=> -$host_balance,
			'type'=> 6,
			'add_time'=> time(),
		]);
        // 推送庄家信息给房间全部用户
        $this->pushHostInfo($room_id);
        $this->addUserLog('上庄', "在{$room_id}房间用{$host_balance}元宝上庄");
        // 成功返回
		$this->ajaxReturn(output(CodeEnum::SUCCESS));
	}

	/**
     * @desc 下庄
     * @param token     用户TOKEN
     * @return 
     */
	public function downHost() {
		// 获取房间ID
		$userTokenModel = M('user_token');
		$room_id = $userTokenModel->where([
			'online'=> 1, 
			'user_id'=> C('USER_ID'), 
		])->getField('room_id');
		if (empty($room_id)) {
			$this->ajaxReturn(output(CodeEnum::PLEASE_COME_IN_ROOM));
		}
		$hostModel = D('Host');
		$hostInfo = $hostModel->where(['user_id'=> C('USER_ID'), 'room_id'=> $room_id, 'is_delete'=> 0])->find();
		// 用户还没上庄
		if (empty($hostInfo)) {
			// 推送庄家信息给房间全部用户
        	$this->pushHostInfo($room_id);
			$this->ajaxReturn(output(CodeEnum::YOU_ARE_NOT_HOST));
		}
		// 下局自动下庄
		if ($hostInfo['status'] == 2) {
			$this->ajaxReturn(output(CodeEnum::NEXT_ISSUE_AUTO_DOWN_HOST));
		}
		// 用户能不能在本局下庄
		if ($hostInfo['status'] == 1 && $this->isBetInRoom($room_id)) {
			$hostModel->where(['user_id'=> C('USER_ID'), 'room_id'=> $room_id])->save(['status'=> 2]);
			$this->ajaxReturn(output(CodeEnum::NEXT_ISSUE_AUTO_DOWN_HOST));
		}
		// 本局下庄
		$hostModel->where(['user_id'=> C('USER_ID'), 'room_id'=> $room_id])->save(['is_delete'=> 1]);
        $balance = bcadd($this->userInfo['balance'], $hostInfo['host_balance'], 2);
        M('user')->where(['user_id'=> C('USER_ID')])->save(['balance'=> $balance]);
        // 推送用户余额给用户
        $client_id = M('user_token')->where(['user_id'=> C('USER_ID')])->getField('client_id');
        sendToClient($client_id, CodeEnum::LEFT_BALANCE, ['balance'=> $balance]);
        // 流水LOG
        M('user_waste_book')->add([
			'user_id'=> C('USER_ID'),
			'before_balance'=> $this->userInfo['balance'],
			'after_balance'=> $balance,
			'change_balance'=> $hostInfo['host_balance'],
			'type'=> 7,
			'add_time'=> time(),
		]);
        // 推送庄家信息给房间全部用户
        $this->pushHostInfo($room_id);
        $this->addUserLog('下庄', "在{$room_id}房间下庄");
        // 成功返回
		$this->ajaxReturn(output(CodeEnum::SUCCESS));
	}

	// 推送庄家信息给房间全部用户
	private function pushHostInfo($room_id) {
		// 获取房间在线用户列表
        $userList = M('user_token')->where([
        	'online'=> 1, 
			'room_id'=> $room_id
		])->field('client_id,user_id')->select();
		// host_button:1-我要上庄，2-我要下庄，
    	$hostInfo = D('Host')->getHostInfo($room_id);
    	foreach ($userList as $key => $value) {
    		$tempHostInfo = $hostInfo;
    		foreach ($hostInfo['waitHostList'] as $k => $v) {
	    		if ($value['user_id'] == $v['user_id']) {
	    			$tempHostInfo['host_button'] = 2;
	    			break;
	    		}
	    	}
	    	sendToClient($value['client_id'], CodeEnum::HOST_INFO, $tempHostInfo);
    	}
	}

	// 判断房间是否已有用户下注
	private function isBetInRoom($room_id) {
		$redis = redisCache();
		$result = false;
    	$betDetail = $redis->lrange(CacheEnum::BET_DETAIL.$room_id, 0, -1);
    	if (!empty($betDetail)) {
    		$bet_total = 0;
    		foreach ($betDetail as $key => $value) {
    			$betInfo = json_decode($value, true);
    			$bet_total += $betInfo['balance'];
    		}
    	}
    	$result = $bet_total > 0 ? true : false;
    	return $result;
	}

	/**
     * @desc 下注
     * @param bet_balance  下注金额
     * @param zone     下注区域
     * @param token    用户TOKEN
     * @return 
     */
	public function onBet() {
		$bet_balance = I('get.bet_balance', 0, 'intval');
		$zone = I('get.zone', 0, 'intval');
		if ($bet_balance < 1 || $zone < 1) {
			$this->ajaxReturn(output(CodeEnum::PARAM_ERROR));
		}
		// 判断用户余额够不够
		if (C('IS_TEMP') || $bet_balance > $this->userInfo['balance']) {
			$this->ajaxReturn(output(CodeEnum::BALANCE_IS_NOT_ENOUGH));
		}
		// 获取房间ID
		$userTokenModel = M('user_token');
		$room_id = $userTokenModel->where([
			'online'=> 1, 
			'user_id'=> C('USER_ID'), 
		])->getField('room_id');
		if (empty($room_id)) {
			$this->ajaxReturn(output(CodeEnum::PLEASE_COME_IN_ROOM));
		}
		// 获取游戏信息
		$siteInfo = D('Site')->getSiteInfo($room_id);
		$gameInfo = D('Game')->getGameInfo($siteInfo['game_id']);
		// 获取庄家信息
		$hostInfo = M('host')->where(['room_id'=>$room_id,'status'=>['gt',0]])->find();
		// 庄家不能下注
		if (!empty($hostInfo) && $hostInfo['user_id'] == C('USER_ID')) {
			$this->ajaxReturn(output(CodeEnum::HOST_CAN_NOT_BET));
		}
		if ($gameInfo['must_host'] == 1 && empty($hostInfo)) {
			$this->ajaxReturn(output(CodeEnum::MUST_HAS_HOST));
		}
		// 判断下注区域是否合法
		if ($zone > $gameInfo['zone_count'] || (!empty($hostInfo) && $zone == $hostInfo['host_zone'])) {
			$this->ajaxReturn(output(CodeEnum::ZONE_IS_NOT_EXIST));
		}
		// 判断下注金额是否合法
		$coinList = [];
		switch ($siteInfo['site_type']) {
			case '1':
				$coinList = [10, 50, 100, 500, 1000];
				break;
			case '2':
				$coinList = [100, 500, 1000, 5000, 10000];
				break;
			case '3':
				$coinList = [1, 2, 3, 4, 5];
				break;
		}
		if (!in_array($bet_balance, $coinList)) {
			$this->ajaxReturn(output(CodeEnum::BET_BALANCE_IS_NOT_EXIST));
		}
		// 判断是否是下注状态
		$lotteryInfo = D('Lottery')->getLotteryInfo($gameInfo['lottery_id']);
		$countdownInfo = D('Lottery')->getCountdown($lotteryInfo);
		if ($countdownInfo['status'] != 1) {
			$this->ajaxReturn(output(CodeEnum::STOP_TO_BET));
		}
    	// 取消下注按钮
    	$redis = redisCache();
    	$cancelInfo = ['cancel_button'=> 0, 'cancel_countdown'=> 0];
    	$bet_first_time = 0;
    	$if_cenceled = 0;
    	$cancel_time = 30;
    	$bet_balance_total = 0;
    	$room_balance_total = 0;
    	$betDetail = $redis->lrange(CacheEnum::BET_DETAIL.$room_id, 0, -1);
    	if (!empty($betDetail)) {
    		foreach ($betDetail as $key => $value) {
    			$betInfo = json_decode($value, true);
    			$room_balance_total = bcadd($room_balance_total, $betInfo['balance'], 2);
    			if ($betInfo['user_id'] == C('USER_ID')) {
    				$bet_balance_total = bcadd($bet_balance_total, $betInfo['balance'], 2);
					if ($bet_first_time == 0) {
						$bet_first_time = $betInfo['add_time'];
					} else {
						$bet_first_time = $bet_first_time > $betInfo['add_time'] ? $betInfo['add_time'] : $bet_first_time;
					}
					if ($betInfo['balance'] < 0) {
						$if_cenceled = 1;
					}
    			}
    		}
    		$temp_time = $cancel_time + $bet_first_time - time();
    		if ($temp_time > 0 && $if_cenceled == 0) {
    			$cancelInfo['cancel_button'] = 1;
    			$cancelInfo['cancel_countdown']  = $temp_time > $countdownInfo['countdown'] ? $countdownInfo['countdown'] : $temp_time;
    		}
    	}
    	if ($bet_first_time == 0) {
    		$cancelInfo['cancel_button'] = 1;
    		$cancelInfo['cancel_countdown'] = $cancel_time > $countdownInfo['countdown'] ? $countdownInfo['countdown'] : $cancel_time;
    	}
    	// 判断是否达到房间最大下注金额
    	if ($bet_balance_total + $bet_balance > $siteInfo['max_bet_banlance']) {
    		$this->ajaxReturn(output(CodeEnum::OVER_MAX_BET_BALANCE,[],[(int)$siteInfo['max_bet_banlance']]));
    	}
    	// 有庄家下注金额不能大于庄家金额
    	if (!empty($hostInfo) && $room_balance_total + $bet_balance > $hostInfo['host_balance']) {
    		$this->ajaxReturn(output(CodeEnum::OVER_BOST_BALANCE));
    	}
		// 下注
    	$redis->rpush(CacheEnum::BET_DETAIL.$room_id, json_encode([
            'user_id'=> C('USER_ID'),
            'zone'=> $zone,
            'balance'=> $bet_balance,
            'add_time' => time()
        ]));
    	// 推送用户余额给用户
		$balance = bcsub($this->userInfo['balance'], $bet_balance, 2);
		M('user')->where(['user_id'=> C('USER_ID')])->save(['balance'=> $balance]);
        $client_id = $userTokenModel->where(['user_id'=> C('USER_ID')])->getField('client_id');
        sendToClient($client_id, CodeEnum::LEFT_BALANCE, ['balance'=> $balance]);
        // 推送下注变化
        $userList = M('user_token')->where([
        	'online'=> 1, 
			'room_id'=> $room_id
		])->field('client_id,user_id')->select();
    	foreach ($userList as $key => $value) {
	        sendToClient($value['client_id'], CodeEnum::BET_CHANGE, [
    			'is_me' => $value['user_id'] == C('USER_ID') ? 1 : 0,
    			'coinList' => [['zone'=> $zone, 'balance'=> $bet_balance]],
	        ]);
    	}
    	// 流水LOG
        M('user_waste_book')->add([
			'user_id'=> C('USER_ID'),
			'before_balance'=> $this->userInfo['balance'],
			'after_balance'=> $balance,
			'change_balance'=> -$bet_balance,
			'type'=> 1,
			'add_time'=> time(),
		]);
		$this->addUserLog('下注', "在{$room_id}房间{$zone}区下注{$bet_balance}");
        $this->ajaxReturn(output(CodeEnum::SUCCESS, $cancelInfo));
	}

	/**
     * @desc  取消下注
     * @param token    用户TOKEN
     * @return 
     */
	public function cancelBet() {
		// 临时用户不能取消
		if (C('IS_TEMP')) {
			$this->ajaxReturn(output(CodeEnum::MUST_BET_CAN_CANCEL));
		}
		// 获取房间ID
		$userTokenModel = M('user_token');
		$room_id = $userTokenModel->where([
			'online'=> 1, 
			'user_id'=> C('USER_ID'), 
		])->getField('room_id');
		if (empty($room_id)) {
			$this->ajaxReturn(output(CodeEnum::PLEASE_COME_IN_ROOM));
		}
		// 获取游戏信息
		$siteInfo = D('Site')->getSiteInfo($room_id);
		$gameInfo = D('Game')->getGameInfo($siteInfo['game_id']);
		// 判断是否是下注状态
		$lotteryInfo = D('Lottery')->getLotteryInfo($gameInfo['lottery_id']);
		$countdownInfo = D('Lottery')->getCountdown($lotteryInfo);
		if ($countdownInfo['status'] != 1) {
			$this->ajaxReturn(output(CodeEnum::STOP_BET_CAN_NOT_CANCEL));
		}
		// 获取下注详细
    	$zoneDetail = [];
    	$is_cancel = 0;//是否可以取消
    	$has_cancel = 0;//是否已取消过
    	$all_balance = 0;//用户下注总金额，用于退还给用户
    	$redis = redisCache();
    	$betDetail = $redis->lrange(CacheEnum::BET_DETAIL.$room_id, 0, -1);
    	if (!empty($betDetail)) {
    		$bet_first_time = 0;
    		foreach ($betDetail as $key => $value) {
    			$betInfo = json_decode($value, true);
    			if ($betInfo['user_id'] == C('USER_ID')) {
					$all_balance += $betInfo['balance'];
					$zoneDetail[] = $betInfo;
					if ($betInfo['balance'] < 0) {
						$has_cancel = 1;
					}
					if ($bet_first_time == 0) {
						$bet_first_time = $betInfo['add_time']; 
					} else {
						$bet_first_time = $bet_first_time > $betInfo['add_time'] ? $betInfo['add_time'] : $bet_first_time;
					}
    			}
    		}
    		$temp_time = time() - $bet_first_time;
    		if ($temp_time < 30) {
    			$is_cancel = 1;
    		}
    	}
    	// 必须有下注才可以取消下注
    	if ($all_balance == 0) {
    		$this->ajaxReturn(output(CodeEnum::MUST_BET_CAN_CANCEL));
    	}
    	// 一局只能取消一次下注
    	if ($has_cancel == 1) {
    		$this->ajaxReturn(output(CodeEnum::ONLY_CAN_CANCEL_ONE_TIME));
    	}
    	// 只能在下注30秒内才能取消下注
    	if ($is_cancel == 0) {
    		$this->ajaxReturn(output(CodeEnum::OVER_TIME_TO_CANCEL_BET));
    	}
    	$coinList = [];
    	// 取消下注
    	foreach ($zoneDetail as $key => $value) {
    		$redis->rpush(CacheEnum::BET_DETAIL.$room_id, json_encode([
	            'user_id'=> C('USER_ID'),
	            'zone'=> $value['zone'],
	            'balance'=> -$value['balance'],
	            'add_time' => time()
	        ]));
	        $coinList[] = ['zone'=> $value['zone'], 'balance'=> -$value['balance']];
    	}
    	// 推送用户余额给用户
		$balance = bcadd($this->userInfo['balance'], $all_balance, 2);
		M('user')->where(['user_id'=> C('USER_ID')])->save(['balance'=> $balance]);
        $client_id = $userTokenModel->where(['user_id'=> C('USER_ID')])->getField('client_id');
        sendToClient($client_id, CodeEnum::LEFT_BALANCE, ['balance'=> $balance]);
        // 推送下注变化
        $userList = M('user_token')->where([
        	'online'=> 1, 
			'room_id'=> $room_id
		])->field('client_id,user_id')->select();
    	foreach ($userList as $key => $value) {
	        sendToClient($value['client_id'], CodeEnum::BET_CHANGE, [
    			'is_me' => $value['user_id'] == C('USER_ID') ? 1 : 0,
    			'coinList' => $coinList,
	        ]);
    	} 
    	// 流水LOG
        M('user_waste_book')->add([
			'user_id'=> C('USER_ID'),
			'before_balance'=> $this->userInfo['balance'],
			'after_balance'=> $balance,
			'change_balance'=> $all_balance,
			'type'=> 5,
			'add_time'=> time(),
		]);
		$this->addUserLog('取消下注', "在{$room_id}房间取消下注");
        $this->ajaxReturn(output(CodeEnum::SUCCESS, ['cancel_button'=> 0, 'cancel_countdown'=> 0]));
	}

	// 去掉取消下注的筹码
    private function deleteCoin($arr) {
        $tempArr = [];
        foreach ($arr as $key => $value) {
            if ($value < 0) {
                $tempArr[] = $value;
                unset($arr[$key]);
            }
        }
        foreach ($tempArr as $key => $value) {
            foreach ($arr as $k => $v) {
                if (-$value == $v) {
                    unset($arr[$k]);
                    break;
                }
            }
        }
        return array_values($arr);
    }

	/**
     * @desc 获取房间用户列表
     * @param page_start 显示页，默认:1
     * @param page_size  每页显示条数，默认:28
     * @param token     用户TOKEN
     * @return 
     */
	public function getRoomUsers() {
		$page_start = I('get.page_start', 1, 'intval');
		$page_size = I('get.page_size', 28, 'intval');
		if ($page_start < 1 || $page_size < 1) {
			$this->ajaxReturn(output(CodeEnum::PARAM_ERROR));
		}
		$userTokenModel = M('user_token');
		$room_id = $userTokenModel->where([
			'online'=> 1, 
			'user_id'=> C('USER_ID'), 
		])->getField('room_id');
		if (empty($room_id)) {
			$this->ajaxReturn(output(CodeEnum::PLEASE_COME_IN_ROOM));
		}
		// 页数
		$online_count = $userTokenModel->join(" as t left join zc_user as u on t.user_id = u.user_id")->where([
			'online'=> 1, 
			'room_id'=> $room_id,
		])->field('t.user_id,u.nickname')->count();
		$page_count = ceil($online_count / $page_size);
		// 列表
		$offset = $page_size * ($page_start - 1);
		$roomUsers = $userTokenModel->join(" as t left join zc_user as u on t.user_id = u.user_id")->where([
			'online'=> 1, 
			'room_id'=> $room_id,
		])->field('t.user_id,u.nickname')->limit("{$offset},{$page_size}")->select();
		foreach ($roomUsers as $key => $value) {
			if (empty($value['nickname'])) {
				$roomUsers[$key]['nickname'] = getTempNickname($value['user_id']);
			}
			// 上编号
			$roomUsers[$key]['num'] = ++$offset;
		}
		$this->ajaxReturn(output(CodeEnum::SUCCESS, [
			'page_start' => $page_start,
			'page_size' => $page_size,
			'page_count' => $page_count,
			'online_count' => $online_count,
			'roomUsers' => $roomUsers,
		]));
	}

	/**
     * @desc 获取房间帮助
     * @param token     用户TOKEN
     * @return 
     */
	public function getRoomHelp() {
		//  获取 $room_id
		$room_id = M('user_token')->where([
			'online'=> 1, 
			'user_id'=> C('USER_ID'), 
		])->getField('room_id');
		if (empty($room_id)) {
			$this->ajaxReturn(output(CodeEnum::PLEASE_COME_IN_ROOM));
		}
		$siteInfo = D('Site')->getSiteInfo($room_id);
		$game_id = $siteInfo['game_id'];
		$gameInfo = D('Game')->getGameInfo($game_id);
		if (empty($gameInfo)) {
			$this->ajaxReturn(output(CodeEnum::PARAM_ERROR));
		}
		// 开奖结果
		$openResult = [];
		if (!in_array($game_id, [4, 6, 10])) {
			$openResult = M('open_result_log')->where(['game_id'=> $game_id, 'add_time'=>['egt',strtotime(date('Y-m-d'))]])->field('issue,add_time,zone_detail')->order('id desc')->select();
			foreach ($openResult as $key => $value) {
				$openResult[$key]['add_time'] = date('Y-m-d H:i:s', $value['add_time']);
				$openResult[$key]['zone_detail'] = json_decode($value['zone_detail'], true);
			}
		}
		// 官方结果
		$officialResult = D('Lottery')->getLotteryTodayResult($gameInfo['lottery_id']);
		// 游戏规则
		$gameRule = $gameInfo['rule'];

		$this->ajaxReturn(output(CodeEnum::SUCCESS, [
			'openResult' => $openResult,
			'officialResult' => $officialResult,
			'gameRule' => $gameRule,
		]));
	}

	/**
     * @desc 聊天
     * @param content   聊天内容
     * @param token     用户TOKEN
     * @return 
     */
	public function chat() {
		$content = I('get.content');
		if (empty($content)) {
			$this->ajaxReturn(output(CodeEnum::PARAM_ERROR));
		}
		// 至少聊天金额
		if (C('IS_TEMP') || $this->userInfo['balance'] < 20) {
			$this->ajaxReturn(output(CodeEnum::LESS_THAN_20_CANNOT_CHAT));
		}
		//  获取 $room_id
		$room_id = M('user_token')->where([
			'online'=> 1, 
			'user_id'=> C('USER_ID'), 
		])->getField('room_id');
		if (empty($room_id)) {
			$this->ajaxReturn(output(CodeEnum::PLEASE_COME_IN_ROOM));
		}
		// 入库
		M('chat_log')->add([
			'room_id' => $room_id,
			'nickname' => $this->userInfo['nickname'],
			'user_id' => C('USER_ID'),
			'content' => $content,
			'add_time' => time(),
		]);
		// 获取房间在线用户列表
        $userList = M('user_token')->where([
        	'online'=> 1, 
			'room_id'=> $room_id
		])->field('client_id,user_id')->select();
		// 实时推送聊天内容给用户
		$result = [
		    'nickname' => $this->userInfo['nickname'],
		    'is_me'    => 1,// 是否自己发表聊天
		    'content'  => $content,
		    'add_time' => date('H:i:s')
		];
		$clientArr = [];
		foreach ($userList as $key => $value) {
			if ($value['user_id'] == C('USER_ID')) {
				sendToClient($value['client_id'], CodeEnum::ROOM_CHAT, $result);
	    	} else {
	    		$clientArr[] = $value['client_id'];
	    	}
		}
		if (!empty($clientArr)) {
			$result['is_me'] = 0;
			sendToAll(CodeEnum::ROOM_CHAT, $result, $clientArr);
		}
		$this->addUserLog('聊天', "在{$room_id}房间发表聊天");
        $this->ajaxReturn(output(CodeEnum::SUCCESS));
	}

	/**
     * @desc 获取某一区域的下注详情
     * @param zone     下注区域
     * @param token    用户TOKEN
     * @return 
     */
	public function getOneZoneBetDetail() {
		$zone = I('get.zone', 0, 'intval');
		if ($zone < 1) {
			$this->ajaxReturn(output(CodeEnum::PARAM_ERROR));
		}
		// 获取房间ID
		$userTokenModel = M('user_token');
		$room_id = $userTokenModel->where([
			'online'=> 1, 
			'user_id'=> C('USER_ID'), 
		])->getField('room_id');
		if (empty($room_id)) {
			$this->ajaxReturn(output(CodeEnum::PLEASE_COME_IN_ROOM));
		}
		// 获取游戏信息
		$siteInfo = D('Site')->getSiteInfo($room_id);
		$gameInfo = D('Game')->getGameInfo($siteInfo['game_id']);
		// 判断下注区域是否合法
		if ($zone > $gameInfo['zone_count']) {
			$this->ajaxReturn(output(CodeEnum::ZONE_IS_NOT_EXIST));
		}
		// 获取下注详细
    	$redis = redisCache();
    	$betDetail = $redis->lrange(CacheEnum::BET_DETAIL.$room_id, 0, -1);
    	$list = [];
    	if (!empty($betDetail)) {
    		foreach ($betDetail as $key => $value) {
    			$betInfo = json_decode($value, true);
    			if ($betInfo['zone'] == $zone) {
    				if (isset($list[$betInfo['user_id']])) {
    					$list[$betInfo['user_id']] += $betInfo['balance'];
    				} else {
    					$list[$betInfo['user_id']] = $betInfo['balance'];
    				}
    			}
    		}
    	}
    	$user = M('User');
    	$result = [];
    	$sort = [];
    	foreach ($list as $user_id => $balance) {
    		if ($balance > 0) {
    			$nickname = $user->where(['user_id'=>$user_id])->getField('nickname');
    			$result[] = [
    				'user_id'  => $user_id,
    				'nickname' => $nickname,
    				'balance'  => $balance,
    			];
    			$sort[] = $balance;
    		}
    	}
    	// 排序
    	array_multisort($sort, SORT_DESC, $result);
    	$this->ajaxReturn(output(CodeEnum::SUCCESS, $result));
	}
	
	/**
     * @desc 获取用户信息
     * @param date      日期，格式：Y-m-d，默认今天
     * @param client_id 客户端ID
     * @param token     用户TOKEN
     * @return int
     */
    public function getUserInfo() {
        $date = I('get.date');
        if (empty($date) || !preg_match('/^\d{4}\-\d{1,2}\-\d{1,2}$/', $date)) {
            $date = date('Y-m-d');
        }
	$id = C('IS_TEMP') ? "-1" : $this->userInfo['user_id'];
        $user_name = C('IS_TEMP') ? C('USER_ID') : $this->userInfo['user_name'];
        $nickname = C('IS_TEMP') ? getTempNickname(C('USER_ID')) : $this->userInfo['nickname'];
        $balance = C('IS_TEMP') ? "0.00" : $this->userInfo['balance'];
        $result = [
	    'id'=> $id,
            'user_name'=> $user_name,
            'nickname'=> $nickname,
            'balance'=> $balance,
            'change_balance'=> "0.00",
            'date' => $date,
            'list' => []
        ];
        $time = strtotime($date);
        if (C('IS_TEMP') == 0) {
            $betLogList = M('bet_log')->where([
                'user_id' => C('USER_ID'),
                'add_time' => [['egt', $time], ['lt', $time + 86400]]
            ])->order('add_time desc')->select();
            $list = [];
            foreach ($betLogList as $key => $value) {
                // 游戏信息
                $siteInfo = D('Site')->getSiteInfo($value['room_id']);
                $gameInfo = D('Game')->getGameInfo($siteInfo['game_id']);
                // 场所信息
                // 彩票信息
                $lotteryInfo = D('Lottery')->getLotteryInfo($gameInfo['lottery_id']);
                // 期
                $lottery_number = M('lottery_issue')->where(['lottery_id'=> $gameInfo['lottery_id'], 'issue'=> $value['issue']])->getField('lottery_number');
                // 下注明细
                $bet_detail = json_decode($value['bet_detail'], true);
                $result['list'][] = [
                    'title' => $lotteryInfo['lottery_name'].'-'.$value['issue'].'期-'.$gameInfo['game_name'].'-'.$siteInfo['site_name'],
                    'lottery_number' => $lottery_number,
                    'bet_detail' => $bet_detail,
                    'bet_balance' => $value['bet_balance'],
                    'profit_balance' => $value['profit_balance'],
                    'add_time' => date('Y-m-d H:i:s', $value['add_time']),
                ];
                $result['change_balance'] = bcadd($result['change_balance'], $value['profit_balance'], 2);
            }
        }
        $this->ajaxReturn(output(CodeEnum::SUCCESS, $result));
    }

    /**
     * @desc 房间列表（用于房间切换时请求）
     * @param lottery_id 彩票ID
     * @param client_id 客户端ID
     * @param token     用户TOKEN
     * @return 
     */
	public function switchRoomList() {
		$result = array();
		$result['id'] = C('IS_TEMP') ? "-1" : $this->userInfo['user_id'];
		$result['nickname'] = C('IS_TEMP') ? getTempNickname(C('USER_ID')) : $this->userInfo['nickname'];
		$result['balance'] = C('IS_TEMP') ? "0.00" : $this->userInfo['balance'];
		$lottery_id = I('get.lottery_id', '', 'intval');
		if (empty($lottery_id) || !in_array($lottery_id, [1,2,3])) {
    		$this->ajaxReturn(output(CodeEnum::PARAM_ERROR));
    	}
    	$gameModel = D('Game');
    	$siteModel = D('Site');
    	$lotteryModel = D('Lottery');
    	// 判断彩票是否在休市状态
    	$lottery_status = $lotteryModel->getLotteryStatus($lottery_id);
    	if ($lottery_status == 4) {
    		$this->ajaxReturn(output(CodeEnum::LOTTERY_STOP));
    	}
    	// 游戏列表
		$gameList = $gameModel->getGameList($lottery_id);
		// 场馆列表
		foreach ($gameList as $key => $value) {
			$levelList = [
				['level_name' => '底注 10', 'site_type'=> 1],
				['level_name' => '底注 100', 'site_type'=> 2],
				['level_name' => '体验场', 'site_type'=> 3],
			];
			// 场馆列表
			$siteList = $siteModel->getSiteList($value['game_id']);
			foreach ($levelList as $level_key => $level_value) {
				foreach ($siteList as $site_key => $site_value) {
					if ($site_value['site_type'] == $level_value['site_type']) {
						$site_value['game_name'] = $value['game_name'];
						// 房间ID
						$site_value['room_id'] = $site_value['site_id'];
						$site_value['game_id'] = $value['game_id'];
						// 房间在线人数
						$site_value['online_count'] = $siteModel->getRoomCount($site_value['site_id']);
						$levelList[$level_key]['roomList'][] = $site_value;
					}
				}
			}
			$gameList[$key]['levelList'] = $levelList;
		}
		$result['gameList'] = $gameList;
		$this->ajaxReturn(output(CodeEnum::SUCCESS, $result));
	}

	/**
     * @desc 获取可下注区域
     * @param token     用户TOKEN
     * @return 
     */
	public function getBetZone() {
		//  获取 $room_id
		$room_id = M('user_token')->where([
			'online'=> 1, 
			'user_id'=> C('USER_ID'), 
		])->getField('room_id');
		if (empty($room_id)) {
			$this->ajaxReturn(output(CodeEnum::PLEASE_COME_IN_ROOM));
		}
		// 获取游戏ID
    	$siteModel = D('Site');
		$gameModel = D('Game');
		$siteInfo = $siteModel->getSiteInfo($room_id);
		$gameInfo = $gameModel->getGameInfo($siteInfo['game_id']);

		// 获取庄家信息
		$hostInfo = M('host')->where(['room_id'=>$room_id,'status'=>['gt',0]])->find();
		$list = [];
		for ($i=1; $i <= $gameInfo['zone_count']; $i++) { 
			if (isset($hostInfo) && $hostInfo['host_zone'] == $i) {
				continue;
			}
			$list[] = $i;
		}
        $this->ajaxReturn(output(CodeEnum::SUCCESS,$list));
	}

	/**
     * @desc 获取下注详细
     * @param token     用户TOKEN
     * @return 
     */
	public function getBetDetail() {
		//  获取 $room_id
		$room_id = M('user_token')->where([
			'online'=> 1, 
			'user_id'=> C('USER_ID'), 
		])->getField('room_id');
		if (empty($room_id)) {
			$this->ajaxReturn(output(CodeEnum::PLEASE_COME_IN_ROOM));
		}
		// 获取下注详细
    	$list = [];
    	$redis = redisCache();
    	$betDetail = $redis->lrange(CacheEnum::BET_DETAIL.$room_id, 0, -1);
    	if (!empty($betDetail)) {
    		foreach ($betDetail as $key => $value) {
    			$betInfo = json_decode($value, true);
    			if ($betInfo['user_id'] == C('USER_ID')) {
    				if (isset($list[$betInfo['zone']])) {
    					$list[$betInfo['zone']]['balance'] += $betInfo['balance'];
    				} else {
    					$list[$betInfo['zone']] = [
    						'zone'=> $betInfo['zone'],
    						'balance'=> $betInfo['balance'],
    					];
    				}
    			}
    		}
    	}
    	ksort($list);
    	$list = array_values($list);
    	$this->ajaxReturn(output(CodeEnum::SUCCESS,$list));
	}

	/**
     * @desc 获取下注状态和倒计时
     * @param lottery_id     
     * @return 
     */
	public function getCountdown() {
		$lottery_id = I('get.lottery_id', 1, 'intval');
		$lottery_id = in_array($lottery_id, [1,2,3]) ? $lottery_id : 1;
		// 获取彩票状态和倒计时
		$lotteryModel = D('Lottery');
		$lotteryInfo = $lotteryModel->getLotteryInfo($lottery_id);
    	$result = $lotteryModel->getCountdown($lotteryInfo);
    	$this->ajaxReturn(output(CodeEnum::SUCCESS,$result));
	}
}
