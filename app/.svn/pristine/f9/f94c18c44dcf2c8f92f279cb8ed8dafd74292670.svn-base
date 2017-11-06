<?php
namespace Common\Model;
use Think\Model;
use Lib\Enum\CacheEnum;

class LotteryModel extends Model {
	// 获取彩票列表
	public function getLotteryList() {
		$redis = redisCache();
		$lotteryList = $redis->get(CacheEnum::LOTTERY_LIST);
		if (empty($lotteryList)) {
			$lotteryList = $this->select();
			foreach ($lotteryList as $key => $value) {
				$lotteryList[$key]['condition'] = json_decode($value['condition'], true);
			}
			$redis->set(CacheEnum::LOTTERY_LIST, serialize($lotteryList));
		} else {
			$lotteryList = unserialize($lotteryList);
		}
		return $lotteryList;
	}

	// 获取彩票信息
	public function getLotteryInfo($lottery_id = 1) {
		$lotteryList = $this->getLotteryList();
		foreach ($lotteryList as $key => $value) {
			if ($value['lottery_id'] == $lottery_id) {
				return $value;
			}
		}
		return array();
	}

	/**
	 * @desc 一种彩票的倒计时和状态
	 * @param $lotteryInfo 彩票信息
	 * @return array
	 *     - status 当前状态：1-合计时状态，2-封盘状态，3-休市状态
	 *     - countdown 倒计时
	 */
	public function getCountdown($lotteryInfo) {
		// 当前状态：1-下注状态，2-封盘等待开奖状态，3-封盘等待下局开始状态，4-休市状态
		$now_time = date('H:i');
		$status = redisCache()->get(CacheEnum::BAN_BET.$lotteryInfo['lottery_id']) ? 2 : 1;
		if ($status != 2) {
			if ($lotteryInfo['start_time'] < $lotteryInfo['end_time']) {
				if ($now_time < $lotteryInfo['start_time'] || $now_time >= $lotteryInfo['end_time']) $status = 4;
			} else {
				if ($now_time < $lotteryInfo['start_time'] && $now_time >= $lotteryInfo['end_time']) $status = 4;
			}
		}
		// 计算倒计时
		$countdown = 0;
		$fp_countdown = 40;
		$start_countdown = 0;
		if ($status != 4) {
			foreach ($lotteryInfo['condition'] as $k => $v) {
				$temp = false;
				if ($v['start'] < $v['end']) {
					if ($now_time >= $v['start'] && $now_time < $v['end']) $temp = true;
				} else {
					if ($now_time >= $v['start'] || $now_time < $v['end']) $temp = true;
				}
				if ($temp) {
					list($h0, $i0) = explode(':', $v['start']);
					list($h, $i, $s) = explode(':', date('H:i:s'));
					$time0 = $h0 * 3600 + $i0 * 60;
					$time = $h * 3600 + $i * 60 + $s;
					$time = $time < $time0 ? $time + 24 * 3600 : $time;
					$dt = $time - $time0;
					if ($status == 2) {
						// 封盘
						$fp_countdown = $dt - floor($dt / ($v['interval'] * 60)) * $v['interval'] * 60;
						$fp_countdown = $fp_countdown < 40 ? 40 - $fp_countdown : 0;
					} elseif ($status == 1) {
						// 下注倒计时
						$countdown = ceil($dt / ($v['interval'] * 60)) * $v['interval'] * 60 - $dt;
						$countdown = $countdown == 0 ? $v['interval'] * 60 : $countdown;
						// 上次开奖到现在的时间差
						$temp = $dt - floor($dt / ($v['interval'] * 60)) * $v['interval'] * 60;
						// 配置等待开始时间
						$waite_time = 140;
						// 配置封盘时间
						$fp_time = 40;
						if ($temp < $fp_time && $now_time != $lotteryInfo['start_time']) {
							$fp_countdown = $fp_time - $temp;
							$countdown = 0;
							$status = 2;
						} elseif ($temp < $waite_time) {
							$start_countdown = $waite_time - $temp;
							$countdown = $countdown - $start_countdown;
							$status = 3;
						}
					}
					break;
				}
			}
		}
		return [
			'status'=> $status, 
			'countdown'=> $countdown, 
			'fp_countdown'=> $fp_countdown, 
			'start_countdown'=> $start_countdown,
			'start_time'=> $lotteryInfo['start_time'],
			'end_time'=> $lotteryInfo['end_time'],
			'lottery_id'=> $lotteryInfo['lottery_id'],
		];
	}

	/**
	 * @desc 获取彩票状态
	 * @param $lottery_id 彩票ID
	 * @return bool
	 */
	public function getLotteryStatus($lottery_id) {
		$lotteryInfo = $this->getLotteryInfo($lottery_id);
    	$ret = $this->getCountdown($lotteryInfo);
    	return $ret['status'];
	}

	/**
	 * @desc 获取彩票当天结果
	 * @param $lottery_id 彩票ID
	 * @return bool
	 */
	public function getLotteryTodayResult($lottery_id) {
		$result = M('lottery_issue')->where(['lottery_id'=> $lottery_id, 'add_time'=>['egt',strtotime(date('Y-m-d'))]])->field('issue,lottery_number,date_time')->order('id desc')->select();
    	foreach ($result as $key => $value) {
    		$lottery_number = implode(',', str_split($value['lottery_number']));
    		if ($lottery_id != 2) {
    			$lottery_number = str_replace('0', '10', $lottery_number);
    		}
    		$result[$key]['lottery_number'] = $lottery_number;
    	}
    	return $result;
	}
}