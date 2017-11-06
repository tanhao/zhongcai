<?php
namespace Api\Controller;
use Lib\Enum\CodeEnum;
use Lib\Enum\CacheEnum;
use Lib\GatewayClient\Gateway;

class IndexController extends BaseController {

	 /**
     * @desc 首页
     * @param client_id 客户端ID
     * @param token     用户TOKEN
     * @return 
     */
	public function index() {
		$result = array();
		$result['nickname'] = C('IS_TEMP') ? getTempNickname(C('USER_ID')) : $this->userInfo['nickname'];
		$result['balance'] = C('IS_TEMP') ? "0.00" : $this->userInfo['balance'];
		$result['register_count'] = M('user')->count();
		$lotteryModel = D('Lottery');
		$lotteryList = $lotteryModel->getLotteryList();
		foreach ($lotteryList as $key => $value) {
			// 最新开奖期数
			$issueInfo = M('lottery_issue')->where(['lottery_id' => $value['lottery_id']])->field('issue,lottery_number')->order('id desc')->find();
			// 最新开奖号码
			if ($value['lottery_id'] == 2) {
				$lottery_number = implode(',', str_split($issueInfo['lottery_number']));
			} else {
				$lottery_number = str_replace('0', '10', implode(',', str_split($issueInfo['lottery_number'])));
			}
			// 获取当前状态和倒计时：1-倒计时状态，2-封盘状态，3-休市状态
			$ret = $lotteryModel->getCountdown($value);
			$result['lotteryList'][] = [
				'lottery_id' => $value['lottery_id'],
				'lottery_name' => $value['lottery_name'],
				'start_time' => $value['start_time'],
				'end_time' => $value['end_time'],
				'issue' => $issueInfo['issue'],
				'lottery_number' => $lottery_number,
				'status' => $ret['status'],
				'countdown' => $ret['countdown'] + $ret['start_countdown'],
			];
		}
		// 推送站内消息
		$notice = M('notice')->where(['status'=>1])->getField('content');
		$client_id = I('get.client_id');
		sendToClient($client_id, CodeEnum::PUSH_NOTICE, ['notice'=> $notice]);
		// 推送系统通知
        $unreadCount = M('system_message')->where(['user_id'=> C('USER_ID'), 'read'=>0])->count();
        sendToClient($client_id, CodeEnum::SYSTEM_MESSAGE, ['unreadCount'=> $unreadCount]);
		// 用户日志
		$this->addUserLog('进入大厅', '进入大厅');
		$this->ajaxReturn(output(CodeEnum::SUCCESS, $result));
	}

	/**
     * @desc 房间列表
     * @param lottery_id 彩票ID
     * @param client_id 客户端ID
     * @param token     用户TOKEN
     * @return 
     */
	public function roomList() {
		$result = array();
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
		// 用户日志
		$lotteryInfo = $lotteryModel->getLotteryInfo($lottery_id);
		$this->addUserLog('房间列表', "进入{$lotteryInfo['lottery_name']}房间列表");
		$this->ajaxReturn(output(CodeEnum::SUCCESS, $result));
	}

	/**
     * @desc 公告
     * @param lottery_id 彩票ID
     * @param client_id 客户端ID
     * @param token     用户TOKEN
     * @return 
     */
	public function getAnnouncementUrl() {
		$announcement_url = getConfig('announcement_url');
		$result = ['announcement_url'=> $announcement_url];
		$this->ajaxReturn(output(CodeEnum::SUCCESS, $result));
	}
}