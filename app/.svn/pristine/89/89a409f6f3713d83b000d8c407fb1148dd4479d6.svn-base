<?php
namespace Lib\Game;
use Lib\Enum\CacheEnum;
use Lib\Enum\CodeEnum;

class Calculator { 
	private $lottery_id = 1;
    private $nowTime = 0;
    private $issue = '';
    private $opencode = [];

    public function __construct($lottery_id, $nowTime, $issue, $opencode) {
    	$this->lottery_id = $lottery_id;
    	$this->nowTime = $nowTime;
    	$this->issue = $issue;
    	$this->opencode = $opencode;
    }

    // 非龙虎的计算方式
    public function calculateFiveZone($zoneDetailSort, $room_id, $userZoneBet, $zoneBet, $roomBet) {
        $redis = redisCache();
        $rate = getConfig('rate');//回佣率
        $tempArr = [];
        foreach ($zoneDetailSort as $k => $v) {
            $tempArr[$v['zone']] = $v;
        }
        $zoneDetailSort = $tempArr;
    	$hostInfo = M('Host')->where(['room_id'=> $room_id, 'status'=> ['gt', 0]])->find();
    	if (!empty($hostInfo)) {
    		foreach ($zoneDetailSort as $key => $value) {
    			if ($value['zone'] == $hostInfo['host_zone']) {
    				$hostInfo['rank'] = $value['rank'];
    				break;
    			}
    		}
    		if (isset($hostInfo['rank'])) {
    			$win_balance = 0;
    			$lost_balance = 0;
    			foreach ($zoneDetailSort as $key => $value) {
    				$balance = isset($zoneBet[$room_id][$value['zone']]['balance']) ? $zoneBet[$room_id][$value['zone']]['balance'] : 0;
    				if ($value['zone'] == $hostInfo['host_zone']) {
    					$zoneDetailSort[$key]['balance'] = $hostInfo['host_balance'];
    				} else {
    					$zoneDetailSort[$key]['balance'] = $balance;
    					if ($hostInfo['rank'] < $value['rank']) {
    						$win_balance += $balance;
    					} elseif ($hostInfo['rank'] > $value['rank']) {
    						$lost_balance += $balance;
    					}
    				}
    			}
    			// cal_balance
    			$hostInfo['commission'] = bcmul($win_balance, $rate, 2);
    			$host_balance = bcsub(bcadd($hostInfo['host_balance'], $win_balance, 2), $hostInfo['commission'], 2);
				foreach ($zoneDetailSort as $key => $value) {
					if ($value['zone'] == $hostInfo['host_zone']) {
						$zoneDetailSort[$key]['cal_balance'] = $host_balance >= $lost_balance ? bcsub($host_balance, $lost_balance, 2) : 0;
					} else {
						if ($hostInfo['rank'] > $value['rank']) {
    						if ($host_balance >= $lost_balance) {
    							$zoneDetailSort[$key]['cal_balance'] = bcmul($value['balance'], 2, 2);
    						} else {
    							$zoneDetailSort[$key]['cal_balance'] = bcadd($value['balance'], bcdiv(bcmul($host_balance, $value['balance'], 2), $lost_balance, 2), 2);
    						}
    					} elseif ($hostInfo['rank'] < $value['rank']) {
							$zoneDetailSort[$key]['cal_balance'] = 0;
    					} else {
    						$zoneDetailSort[$key]['cal_balance'] = $value['balance'];
    					}
					}
    			}
    		}
    	} else {
    		// 非庄家模式
    		$half_balance = isset($roomBet[$room_id]['balance']) ? $roomBet[$room_id]['balance'] / 2 : 0;
	        $temp_total = 0;
            $bankArr = [];
            foreach ($zoneDetailSort as $key => $value) {
                $bankArr[$value['rank']]['zones'][] = $value['zone'];
            }
	        // 获取一半金额在的位置$rank
            $rank = 0;
            foreach ($bankArr as $key => $value) {
                foreach ($value['zones'] as $k => $v) {
                    if (isset($zoneBet[$room_id][$v]['balance'])) {
                        $temp_total += $zoneBet[$room_id][$v]['balance'];
                    }
                }
                if ($temp_total >= $half_balance) {
                    $rank = $key;
                    break;
                }
            }
            // balance、cal_balance
            $temp_total = 0;
            foreach ($bankArr as $key => $value) {
                if ($key < $rank) {
                    foreach ($value['zones'] as $k => $v) {
                        $zoneDetailSort[$v]['balance'] = isset($zoneBet[$room_id][$v]['balance']) ? $zoneBet[$room_id][$v]['balance'] : 0;
                        $zoneDetailSort[$v]['cal_balance'] = bcmul($zoneDetailSort[$v]['balance'], 2, 2);
                        $temp_total = bcadd($temp_total, $zoneDetailSort[$v]['cal_balance'], 2);
                    }
                } elseif ($key == $rank) {
                    $cal_balance = isset($roomBet[$room_id]['balance']) ? bcsub($roomBet[$room_id]['balance'], $temp_total, 2) : 0;
                    $zone_total = 0;
                    foreach ($value['zones'] as $k => $v) {
                        $zoneDetailSort[$v]['balance'] = isset($zoneBet[$room_id][$v]['balance']) ? $zoneBet[$room_id][$v]['balance'] : 0;
                        $zone_total = bcadd($zone_total, $zoneDetailSort[$v]['balance'], 2);
                    }
                    $cal_balance_total = 0;
                    foreach ($value['zones'] as $k => $v) {
                        if ($k == count($value['zones']) - 1) {
                            $zoneDetailSort[$v]['cal_balance'] = bcsub($cal_balance, $cal_balance_total, 2);
                        } else {
                            $zoneDetailSort[$v]['cal_balance'] = bcdiv(bcmul($cal_balance, $zoneDetailSort[$v]['balance'], 2), $zone_total, 2);
                            $cal_balance_total = bcadd($cal_balance_total, $zoneDetailSort[$v]['cal_balance'], 2);
                        }
                    }
                } else {
                    foreach ($value['zones'] as $k => $v) {
                        $zoneDetailSort[$v]['balance'] = isset($zoneBet[$room_id][$v]['balance']) ? $zoneBet[$room_id][$v]['balance'] : 0;
                        $zoneDetailSort[$v]['cal_balance'] = 0;
                    }
                }
            }
    	}
        // 用户在房间下注情况
        if (!empty($hostInfo)) {
        	// 庄家情况
        	$final_balance = $zoneDetailSort[$hostInfo['host_zone']]['cal_balance'];
        	$hostInfo['profit_balance'] = bcsub($final_balance, $zoneDetailSort[$hostInfo['host_zone']]['balance'], 2);
        	$tempInfo = [
                'user_id' => $hostInfo['user_id'],
                'lottery_id' => $this->lottery_id,
                'room_id' => $hostInfo['room_id'],
                'issue' => $this->issue,
                'is_host' => 1,
                'bet_balance' => $hostInfo['host_balance'],
                'profit_balance' => $hostInfo['profit_balance'],
                'commission' => $hostInfo['commission'],
                'bet_detail' => json_encode([['zone'=> $hostInfo['host_zone'],'balance'=> sprintf('%.2f',$zoneDetailSort[$hostInfo['host_zone']]['balance'])]]),
                'add_time' => $this->nowTime,
            ];
            M('bet_log')->add($tempInfo);
            // 计算流水
            $redis->rpush(CacheEnum::USER_WATER, json_encode([
                'user_id'=> $hostInfo['user_id'],
                'balance'=> abs($hostInfo['profit_balance']),
                'add_time'=> time(),
            ]));
            // 判断用户是否要下庄
            $off_host = false;
            // 用户已申请我要下庄
            if ($hostInfo['status'] == 2) {
            	$off_host = true;
            }
            if (!$off_host) {
            	// 用户离开房间默认下庄
            	$is_in_room = M('user_token')->where(['user_id'=> $hostInfo['user_id'],'room_id'=>$hostInfo['room_id'],'online'=>1])->count();
	            if (!$is_in_room) {
	            	$off_host = true;
	            }
            }
            if (!$off_host) {
                // 被别人下庄钱多挤下庄
                $max_other_host_balance = M('host')->where(['room_id'=> $hostInfo['room_id'], 'status'=>0])->max('host_balance');
                if ($max_other_host_balance > $hostInfo['host_balance']) {
                    $off_host = true;
                }
            }
            if (!$off_host) {
            	// 上庄金额不够用余额补上，还不够就被迫下庄
            	$less_host_balance = D('Host')->getLessHostBalance($hostInfo['room_id']);
            	$temp_balance = bcsub($less_host_balance, $final_balance, 2);
            	if ($temp_balance > 0) {
            		$before_balance = M('user')->where(['user_id'=> $hostInfo['user_id']])->getField('balance');
            		if ($before_balance >= $temp_balance) {
            			$final_balance = $less_host_balance;
            			$balance = bcsub($before_balance, $temp_balance, 2);
            			M('user')->where(['user_id'=> $hostInfo['user_id']])->save(['balance'=> $balance]);
            			$client_id = M('user_token')->where(['user_id'=> $hostInfo['user_id']])->getField('client_id');
		                // 推送用户余额给用户
                        sendToClient($client_id, CodeEnum::LEFT_BALANCE, ['balance'=> $balance]);
                        // 流水LOG
                        M('user_waste_book')->add([
                            'user_id'=> $hostInfo['user_id'],
                            'before_balance'=> $before_balance,
                            'after_balance'=> $balance,
                            'change_balance'=> -$temp_balance,
                            'type'=> 6,
                            'add_time'=> time(),
                        ]);
            		} else {
            			$off_host = true;
            		}
            	}
            }
            if ($off_host) {
            	M('Host')->where(['room_id'=> $room_id, 'status'=> ['gt', 0]])->delete();
            	D('Host')->changeHost($room_id);
            	$before_balance = M('user')->where(['user_id'=> $hostInfo['user_id']])->getField('balance');
                $balance = bcadd($before_balance, $final_balance, 2);
                M('user')->where(['user_id'=> $hostInfo['user_id']])->save(['balance'=> $balance]);
                $client_id = M('user_token')->where(['user_id'=> $hostInfo['user_id']])->getField('client_id');
                // 推送用户余额给用户
                sendToClient($client_id, CodeEnum::LEFT_BALANCE, ['balance'=> $balance]);
                // 流水LOG
                M('user_waste_book')->add([
                    'user_id'=> $hostInfo['user_id'],
                    'before_balance'=> $before_balance,
                    'after_balance'=> $balance,
                    'change_balance'=> $final_balance,
                    'type'=> 7,
                    'add_time'=> time(),
                ]);
            } else {
            	D('Host')->continueHost($room_id, $final_balance);
            }
        } else {
        	D('Host')->changeHost($room_id);
        }
        $betLog = [];
        if (isset($userZoneBet[$room_id])) {
            foreach ($userZoneBet[$room_id] as $user_id => $userBetList) {
                // 闲家情况
                ksort($userBetList);
                $tempInfo = [
                    'user_id'=> $user_id, 
                    'lottery_id' => $this->lottery_id,
                    'room_id'=> $room_id, 
                    'is_host'=> 0, 
                    'bet_balance' => 0,
                    'profit_balance'=> 0,
                    'final_balance'=> 0,
                    'commission'=> 0,
                    'bet_detail' => [],
                ];
                foreach ($userBetList as $zone => $userBetInfo) {
                    $cal_balance = bcdiv(bcmul($userBetInfo['balance'], $zoneDetailSort[$zone]['cal_balance'], 2), $zoneDetailSort[$zone]['balance'], 2);
                    $final_balance = $cal_balance <= $userBetInfo['balance'] ? $cal_balance : bcadd($userBetInfo['balance'], ($cal_balance - $userBetInfo['balance']) * (1-$rate), 2);
                    $tempInfo['bet_balance'] = bcadd($tempInfo['bet_balance'] , $userBetInfo['balance'], 2);
                    $tempInfo['profit_balance'] = bcadd($tempInfo['profit_balance'] , bcsub($final_balance, $userBetInfo['balance'], 2), 2);
                    $tempInfo['final_balance'] = bcadd($tempInfo['final_balance'] , $final_balance, 2);
                    $tempInfo['commission'] = bcadd($tempInfo['commission'], bcsub($cal_balance, $final_balance, 2), 2);
                    $tempInfo['bet_detail'][] = [
                        'zone'=> $zone,
                        'balance'=> sprintf('%.2f',$userBetInfo['balance']),
                        'win_balance'=> bcsub($final_balance, $userBetInfo['balance'], 2),
                    ];
                }
                $betLog[] = $tempInfo;
            }
        }
        $betLog = sortArray($betLog, 'profit_balance', 'desc');
        $rankingList = [];
        // 插入下注明细记录表
        $betLog1 = [];
        foreach ($betLog as $key => $value) {
        	$betLog1[$value['user_id']] = $value;
            M('bet_log')->add([
                'user_id' => $value['user_id'],
                'lottery_id' => $value['lottery_id'],
                'room_id' => $value['room_id'],
                'issue' => $this->issue,
                'is_host' => $value['is_host'],
                'bet_balance' => $value['bet_balance'],
                'profit_balance' => $value['profit_balance'],
                'commission' => $value['commission'],
                'bet_detail' => json_encode($value['bet_detail']),
                'add_time' => $this->nowTime,
            ]);
            $before_balance = M('user')->where(['user_id'=> $value['user_id']])->getField('balance');
            $balance = bcadd($before_balance, $value['final_balance'], 2);
            if ($value['final_balance'] > 0) {
                M('user')->where(['user_id'=> $value['user_id']])->save(['balance'=> $balance]);
                $client_id = M('user_token')->where(['user_id'=> $value['user_id']])->getField('client_id');
                // 推送用户余额给用户
                sendToClient($client_id, CodeEnum::LEFT_BALANCE, ['balance'=> $balance]);
            }
            // 流水LOG
            M('user_waste_book')->add([
                'user_id'=> $value['user_id'],
                'before_balance'=> $before_balance,
                'after_balance'=> $balance,
                'change_balance'=> $value['final_balance'],
                'type'=> 9,
                'add_time'=> time(),
            ]);
            // 计算流水
            $redis->rpush(CacheEnum::USER_WATER, json_encode([
                'user_id'=> $value['user_id'],
                'balance'=> bcadd($value['bet_balance'], abs($value['profit_balance']), 2),
                'add_time'=> time(),
            ]));
            if (count($rankingList) <= 10) {
            	$nickname = M('user')->where(['user_id'=> $value['user_id']])->getField('nickname');
            	$rankingList[] = [
	            	'user_id' => $value['user_id'],
	            	'nickname' => $nickname,
	            	'user_id' => $value['user_id'],
	            	'profit_balance' => $value['profit_balance'],
	            ];
            }
        }
        $betLog = $betLog1;
        if (!empty($hostInfo)) {
            $betLog[$hostInfo['user_id']]['profit_balance'] = $hostInfo['profit_balance'];
        }
        // 获取房间在线用户列表
        $userList = M('user_token')->where([
        	'online'=> 1,
			'room_id'=> $room_id
		])->field('client_id,user_id')->select();
		// 庄家信息
    	$hostInfo2 = D('Host')->getHostInfo($room_id);
        // 标题
        $lotteryInfo = D('Lottery')->getLotteryInfo($this->lottery_id);
        $siteInfo = D('Site')->getSiteInfo($room_id);
        $gameInfo = D('Game')->getGameInfo($siteInfo['game_id']);
        $title = $lotteryInfo['lottery_name'].'-'.$this->issue.'期-'.$gameInfo['game_name'].'-'.$siteInfo['site_name'];
        $zoneDetailSort = sortArray($zoneDetailSort, 'zone', 'asc');
        // 飞金币
        $host_zone = isset($hostInfo['host_zone']) ? $hostInfo['host_zone'] : 0;
        $flyBalance = $this->getFlyBalance($zoneDetailSort, $host_zone);
		// 推送信息给用户
		foreach ($userList as $key => $value) {
			// 庄家信息 host_button:0-不显示，1-我要上庄，2-我要下庄，
	    	foreach ($hostInfo2['waitHostList'] as $k => $v) {
	    		if ($value['user_id'] == $v['user_id']) {
	    			$hostInfo2['host_button'] = 2;break;
	    		}
	    	}
			$result = [
		        'lottery_number'=> implode(',', $this->opencode),
                'issue'=> $this->issue,
		        'title'=> $title,
		        'host_profit_balance'=> isset($hostInfo['profit_balance']) ? $hostInfo['profit_balance'] : "0.00",
		        'profit_balance'=> isset($betLog[$value['user_id']]) ? $betLog[$value['user_id']]['profit_balance'] : "0.00",
		        'zoneOpenDetail'=> $zoneDetailSort,
		        'rankingList'=> $rankingList,
                'hostInfo' => $hostInfo2,
		        'flyBalance' => $flyBalance,
		    ]; 
            sendToClient($value['client_id'], CodeEnum::OPEN_RESULT, $result);
		}
    }

    // 飞金币算法
    private function getFlyBalance($arr, $host_zone=0) {
        $result = [];
        $count = count($arr);
        // 无庄的情况
        if ($host_zone == 0) {
            $sort = [];
            foreach ($arr as $key => $value) {
                $sort[] = $value['rank'];
            }
            array_multisort($sort, SORT_ASC, $arr);
            for ($i=0; $i < $count; $i++) {
                if ($arr[$i]['balance'] <= 0) {
                    continue;
                }
                for ($j=$count-1; $j >= 0 ; $j--) {
                    if ($arr[$j]['balance'] <= 0) {
                        continue;
                    }
                    if ($arr[$i]['rank'] >= $arr[$j]['rank'] || $arr[$i]['balance'] <= 0) {
                        break;
                    }
                    $tempSub = bcsub($arr[$i]['cal_balance'], $arr[$i]['balance'], 2);
                    $fly_balance = $tempSub > $arr[$j]['balance'] ? $arr[$j]['balance'] : $tempSub;
                    $result[] = [
                        'begin_zone' => $arr[$j]['zone'],
                        'end_zone' => $arr[$i]['zone'],
                        'fly_balance' => $fly_balance,
                    ];
                    $arr[$i]['balance'] = bcadd($arr[$i]['balance'], $fly_balance, 2);
                    $arr[$j]['balance'] = bcsub($arr[$j]['balance'], $fly_balance, 2);
                    if ($arr[$i]['balance'] == $arr[$i]['cal_balance']) {
                        $result[] = [
                            'begin_zone' => $arr[$i]['zone'],
                            'end_zone' => -1,
                            'fly_balance' => $arr[$i]['cal_balance'],
                        ];
                        $arr[$i]['balance'] = 0;
                    }
                    
                }
            }
            // 剩下的飞回用户组
            for ($i=0; $i < $count; $i++) {
                if ($arr[$i]['balance'] > 0) {
                    $result[] = [
                        'begin_zone' => $arr[$i]['zone'],
                        'end_zone' => -1,
                        'fly_balance' => $arr[$i]['balance'],
                    ];
                }
            }
        } else {
            // 有庄的情况
            $hostInfo = [];
            for ($i=0; $i < $count; $i++) {
                if ($arr[$i]['zone'] == $host_zone) {
                    $hostInfo = $arr[$i];
                    break;
                }
            }
            for ($i=0; $i < $count; $i++) {
                if ($arr[$i]['zone'] != $host_zone && $arr[$i]['balance'] > 0) {
                    $result[] = [
                        'begin_zone' => $arr[$i]['zone'],
                        'end_zone' => $hostInfo['rank'] < $arr[$i]['rank'] ? -2 : -1,
                        'fly_balance' => $arr[$i]['balance'],
                    ];
                }
            }
        }
        return $result;
    }

    // 龙虎的计算方式
    public function calculateDragonTiger($zoneDetail, $room_id, $userZoneBet, $zoneBet, $roomBet) {
    	$rankingList = [];
    	$betLog = [];
    	$hostInfo = M('Host')->where(['room_id'=> $room_id, 'status'=> ['gt', 0]])->find();
    	if (empty($hostInfo)) {
    		D('Host')->changeHost($room_id);
    	} else {
			$win_balance = 0;
			$lost_balance = 0;
			// balance
			foreach ($zoneDetail as $key => $value) {
				$balance = isset($zoneBet[$room_id][$value['zone']]['balance']) ? $zoneBet[$room_id][$value['zone']]['balance'] : 0;
				$zoneDetail[$key]['balance'] = $balance;
				if ($value['point'] == 0) {
					$win_balance += $balance;
				} elseif ($value['point'] == 1) {
					$lost_balance += $balance;
				}
			}
			// cal_balance
			$hostInfo['commission'] = bcmul($win_balance, $rate, 2);
			$host_balance = bcsub(bcadd($hostInfo['host_balance'], $win_balance, 2), $hostInfo['commission'], 2);
			$hostInfo['cal_balance'] = $host_balance >= $lost_balance ? bcsub($host_balance, $lost_balance, 2) : 0;
			$hostInfo['profit_balance'] = bcsub($hostInfo['cal_balance'], $hostInfo['host_balance'], 2);
			foreach ($zoneDetail as $key => $value) {
				if ($value['point'] == 1) {
					if ($host_balance >= $lost_balance) {
						$zoneDetail[$key]['cal_balance'] = bcmul($value['balance'], 2, 2);
					} else {
						$zoneDetail[$key]['cal_balance'] = bcadd($value['balance'], bcdiv(bcmul($host_balance, $value['balance'], 2), $lost_balance, 2), 2);
					}
				} elseif ($value['point'] == 0) {
					$zoneDetail[$key]['cal_balance'] = 0;
				} else {
                    $zoneDetail[$key]['cal_balance'] = $value['balance'];
                }
			}
	        // 区域做为KEY
	        $tempArr = [];
	        foreach ($zoneDetail as $k => $v) $tempArr[$v['zone']] = $v;
	        $zoneDetail = $tempArr;

        	// 庄家情况
        	$tempInfo = [
                'user_id' => $hostInfo['user_id'],
                'lottery_id' => $this->lottery_id,
                'room_id' => $hostInfo['room_id'],
                'issue' => $this->issue,
                'is_host' => 1,
                'bet_balance' => $hostInfo['host_balance'],
                'profit_balance' => $hostInfo['profit_balance'],
                'commission' => $hostInfo['commission'],
                'bet_detail' => json_encode([['zone'=> $hostInfo['host_zone'],'balance'=> sprintf('%.2f',$hostInfo['host_balance'])]]),
                'add_time' => $this->nowTime,
            ];
        	M('bet_log')->add($tempInfo);
            // 判断用户是否要下庄
            $off_host = false;
            // 用户已申请我要下庄
            if ($hostInfo['status'] == 2) {
            	$off_host = true;
            }
            if (!$off_host) {
            	// 用户离开房间默认下庄
            	$is_in_room = M('user_token')->where(['user_id'=> $hostInfo['user_id'],'room_id'=>$hostInfo['room_id'],'online'=>1])->count();
	            if (!$is_in_room) {
	            	$off_host = true;
	            }
            }
            if (!$off_host) {
            	// 上庄金额不够用余额补上，还不够就被迫下庄
            	$less_host_balance = D('Host')->getLessHostBalance($hostInfo['room_id']);
            	$temp_balance = bcsub($less_host_balance, $hostInfo['cal_balance'], 2);
            	if ($temp_balance > 0) {
            		$balance = M('user')->where(['user_id'=> $hostInfo['user_id']])->getField('balance');
            		if ($balance >= $temp_balance) {
            			$hostInfo['cal_balance'] = $less_host_balance;
            			$balance = bcsub($balance, $temp_balance, 2);
            			M('user')->where(['user_id'=> $hostInfo['user_id']])->save(['balance'=> $balance]);
            			$client_id = M('user_token')->where(['user_id'=> $hostInfo['user_id']])->getField('client_id');
		                // 推送用户余额给用户
                        sendToClient($client_id, CodeEnum::LEFT_BALANCE, ['balance'=> $balance]);
            		} else {
            			$off_host = true;
            		}
            	}
            }
            if ($off_host) {
            	M('Host')->where(['room_id'=> $room_id, 'status'=> ['gt', 0]])->delete();
            	D('Host')->changeHost($room_id);
            	$balance = M('user')->where(['user_id'=> $hostInfo['user_id']])->getField('balance');
                $balance = bcadd($balance, $hostInfo['cal_balance'], 2);
                M('user')->where(['user_id'=> $hostInfo['user_id']])->save(['balance'=> $balance]);
                $client_id = M('user_token')->where(['user_id'=> $hostInfo['user_id']])->getField('client_id');
                // 推送用户余额给用户
                sendToClient($client_id, CodeEnum::LEFT_BALANCE, ['balance'=> $balance]);
            } else {
            	D('Host')->continueHost($room_id, $hostInfo['cal_balance']);
            }
            // 闲家情况
	        foreach ($userZoneBet[$room_id] as $user_id => $userBetList) {
	            ksort($userBetList);
	            $tempInfo = [
                    'user_id'=> $user_id, 
	                'lottery_id' => $this->lottery_id,
	                'room_id'=> $room_id, 
	                'is_host'=> 0, 
	                'bet_balance' => 0,
	                'profit_balance'=> 0,
	                'final_balance'=> 0,
	                'commission'=> 0,
	                'bet_detail' => [],
	            ];
	            foreach ($userBetList as $zone => $userBetInfo) {
	                $cal_balance = bcdiv(bcmul($userBetInfo['balance'], $zoneDetail[$zone]['cal_balance'], 2), $zoneDetail[$zone]['balance'], 2);
	                $final_balance = $cal_balance <= $userBetInfo['balance'] ? $cal_balance : bcadd($userBetInfo['balance'], ($cal_balance - $userBetInfo['balance']) * 0.98, 2);
	                $tempInfo['bet_balance'] = bcadd($tempInfo['bet_balance'] , $userBetInfo['balance'], 2);
	                $tempInfo['profit_balance'] = bcadd($tempInfo['profit_balance'] , bcsub($final_balance, $userBetInfo['balance'], 2), 2);
	                $tempInfo['final_balance'] = bcadd($tempInfo['final_balance'] , $final_balance, 2);
	                $tempInfo['commission'] = bcadd($tempInfo['commission'], bcsub($cal_balance, $final_balance, 2), 2);
	                $tempInfo['bet_detail'][] = [
	                    'zone'=> $zone,
	                    'balance'=> sprintf('%.2f',$userBetInfo['balance']),
	                ];
	            }
	            $betLog[] = $tempInfo;
	        }
	        $betLog = sortArray($betLog, 'profit_balance', 'desc');
	        $betLog1 = [];
	        // 插入下注明细记录表
	        foreach ($betLog as $key => $value) {
	        	$betLog1[$value['user_id']] = $value;
	            M('bet_log')->add([
	                'user_id' => $value['user_id'],
                    'lottery_id' => $value['lottery_id'],
	                'room_id' => $value['room_id'],
	                'issue' => $this->issue,
	                'is_host' => $value['is_host'],
	                'bet_balance' => $value['bet_balance'],
	                'profit_balance' => $value['profit_balance'],
	                'commission' => $value['commission'],
	                'bet_detail' => json_encode($value['bet_detail']),
	                'add_time' => $this->nowTime,
	            ]);
	            if ($value['final_balance'] > 0) {
	                $balance = M('user')->where(['user_id'=> $value['user_id']])->getField('balance');
	                $balance = bcadd($balance, $value['final_balance'], 2);
	                M('user')->where(['user_id'=> $value['user_id']])->save(['balance'=> $balance]);
	                $client_id = M('user_token')->where(['user_id'=> $value['user_id']])->getField('client_id');
	                // 推送用户余额给用户
                    sendToClient($client_id, CodeEnum::LEFT_BALANCE, ['balance'=> $balance]);
	            }
	            // 收益排行榜
	            if (count($rankingList) <= 10) {
	            	$nickname = M('user')->where(['user_id'=> $value['user_id']])->getField('nickname');
	            	$rankingList[] = [
		            	'user_id' => $value['user_id'],
		            	'nickname' => $nickname,
		            	'user_id' => $value['user_id'],
		            	'profit_balance' => $value['profit_balance'],
		            ];
	            }
	        }
	        $betLog = $betLog1;
        }
        // 获取房间在线用户列表
        $userList = M('user_token')->where([
        	'online'=> 1, 
			'room_id'=> $room_id
		])->field('client_id,user_id')->select();
		// 庄家信息
    	$hostInfo2 = D('Host')->getHostInfo($room_id);
        // 标题
        $lotteryInfo = D('Lottery')->getLotteryInfo($this->lottery_id);
        $siteInfo = D('Site')->getSiteInfo($room_id);
        $gameInfo = D('Game')->getGameInfo($siteInfo['game_id']);
        $title = $lotteryInfo['lottery_name'].'-'.$this->issue.'期-'.$gameInfo['game_name'].'-'.$siteInfo['site_name'];
		// 推送信息给用户
		foreach ($userList as $key => $value) {
			// 庄家信息 host_button:0-不显示，1-我要上庄，2-我要下庄，
	    	foreach ($hostInfo2['waitHostList'] as $k => $v) {
	    		if ($value['user_id'] == $v['user_id']) {
	    			$hostInfo2['host_button'] = 2;break;
	    		}
	    	}
			$result = [
		        'lottery_number'=> implode(',', $this->opencode),
		        'issue'=> $this->issue,
		        'host_profit_balance'=> isset($hostInfo['profit_balance']) ? $hostInfo['profit_balance'] : "0.00",
		        'profit_balance'=> isset($betLog[$value['user_id']]) ? $betLog[$value['user_id']]['profit_balance'] : "0.00",
		        'zoneOpenDetail'=> sortArray($zoneDetail, 'zone', 'asc'),
		        'rankingList'=> $rankingList,
		        'hostInfo' => $hostInfo2,
		    ]; 
            sendToClient($value['client_id'], CodeEnum::OPEN_RESULT, $result);
		}
    }
}