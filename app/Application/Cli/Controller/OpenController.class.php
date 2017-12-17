<?php
namespace Cli\Controller;
use Think\Controller;
use Lib\Enum\CacheEnum;
use Lib\Enum\CodeEnum;

class OpenController extends Controller {

    private $lottery_id = 0;
    private $nowTime = 0;
    private $issue = '';
    private $action_name = '';
    private $finished = 1;
    private $opencode = [];

    /**
    北京赛车
	2,7,12,17,22,27,32,37,42,47,52,57 9-23 * * * /usr/local/php/bin/php /var/www/app/index.php cli/open/index/id/1
    时时彩
    50 9 * * * /usr/local/php/bin/php /var/www/app/index.php cli/open/index/id/2
    0,10,20,30,40,50 10-21 * * * /usr/local/php/bin/php /var/www/app/index.php cli/open/index/id/2
    0,5,10,15,20,25,30,35,40,45,50,55 22-23,0-1 * * * /usr/local/php/bin/php /var/www/app/index.php cli/open/index/id/2
    幸运飞艇
    4,9,14,19,24,29,34,39,44,49,54,59 13-23,0-3 * * * /usr/local/php/bin/php /var/www/app/index.php cli/open/index/id/3
    4 4 * * * /usr/local/php/bin/php /var/www/app/index.php cli/open/index/id/3
    */
    public function index() {
        if (!IS_CLI) {
            exit;
        }
        set_time_limit(0);
        $redis = redisCache();
        $this->lottery_id = I('get.id', 0, 'intval');
        $this->nowTime = strtotime(date('Y-m-d H:i'));
        // 封盘
        $redis->set(CacheEnum::BAN_BET.$this->lottery_id, 1);
        $lotteryInfo = D('Lottery')->getLotteryInfo($this->lottery_id);
        // 开市
        if (date("H:i", $this->nowTime) == $lotteryInfo['start_time']) {
            $redis->delete(CacheEnum::BAN_BET.$this->lottery_id);
            $getLotteryInfo = D('Lottery')->getLotteryInfo($this->lottery_id);
            $countdownInfo = D('Lottery')->getCountdown($getLotteryInfo);
            sendToAll(CodeEnum::COUNTDOWN_INFO, $countdownInfo);
            exit;
        }

        sleep(40);//封盘40S倒计时后开始抓开奖结果
        while (1) {
            sleep(2);
            $success = $this->getOpenData($lotteryInfo['api_url']);
            if ($success) {
                // 至少留5s抓奖动画时间
                $sleep_time = 45 + $this->nowTime - time();
                if ($sleep_time > 0) {
                    sleep($sleep_time);
                }
                $lottery_number = str_replace(',','',str_replace('10','0',implode(',',$this->opencode)));
                M('lottery_issue')->add([
                    'lottery_name'=> $lotteryInfo['lottery_name'],
                    'lottery_id'=> $this->lottery_id,
                    'issue'=> $this->issue,
                    'lottery_number'=> $lottery_number,
                    'date_time'=> date("Y-m-d H:i:s", $this->nowTime),
                    'action_name'=> $this->action_name,
                    'finished'=> $this->finished,
                    'add_time'=> time(),
                ]);
                $this->executeResult();
                break;
            }
        }
        // 封盘结束
        $redis->delete(CacheEnum::BAN_BET.$this->lottery_id);
        // 推送倒计时
        $getLotteryInfo = D('Lottery')->getLotteryInfo($this->lottery_id);
        $countdownInfo = D('Lottery')->getCountdown($getLotteryInfo);
        sendToAll(CodeEnum::COUNTDOWN_INFO, $countdownInfo);
        // 推送开奖号码
        if (!empty($this->opencode)) {
            sendToAll(CodeEnum::PUSH_LOTTERY_NUMBER, [
                'lottery_id' => $this->lottery_id,
                'issue' => $this->issue,
                'lottery_number' => implode(',', $this->opencode),
            ]);
        }
    }
    
    private function getJsonPost($apiPath,$postData = false ){
    	$ch = curl_init();// 设置浏览器的特定header
    	if(isset($postData) && !empty($postData)){
    		curl_setopt($ch, CURLOPT_POST, 1);
    		curl_setopt($ch, CURLOPT_POSTFIELDS, $postData );// post的变量
    	}
    	curl_setopt($ch, CURLOPT_URL, $apiPath);
    	if(isset($headerInfo) && !empty($headerInfo) ){
    		curl_setopt($ch, CURLOPT_HTTPHEADER , $headerInfo) ;  // 在HTTP请求头中"Referer: "的内容。
    	}else{
    		curl_setopt($ch, CURLOPT_REFERER,"https://www.baidu.com");
    		curl_setopt($ch, CURLOPT_ENCODING, "gzip, deflate, sdch");
    		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; WOW64; rv:41.0) Gecko/20100101 Firefox/41.0');
    	}
    	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    	curl_setopt($ch, CURLOPT_TIMEOUT,30);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//302redirect
    	$html = curl_exec($ch);
    	curl_close($ch);
    	if(isset($html) && trim($html) != "" ){
    		return  $html;
    	}else{
    		return  "{}";
    	}
    }

    private function getOpenData($url) {
        $result = [];
        // 120s抓不到数据就开相同号码8888888888
        if (time() - $this->nowTime > 110) {
            $lottery_name = '';
            switch ($this->lottery_id) {
                case '1': $lottery_name = "北京赛车";break;
                case '2': $lottery_name = "时时彩";break;
                case '3': $lottery_name = "幸运飞艇";break;
            }
	        file_put_contents(APP_PATH.'Runtime/lottery.txt',  "[ ".date('Y-m-d H:i:s')." ]{$lottery_name}开奖异常！{$this->nowTime}== ".time()."\n", FILE_APPEND);
            $issue = getIssue($this->lottery_id);
            $result = [
                'expect'=> $issue,
                'opencode'=> $this->lottery_id == 2 ? '8,8,8,8,8' : '8,8,8,8,8,8,8,8,8,8',
            ];
            $this->finished = 0;
        }
        // 人工开奖
        if (empty($result)) {
            $result = M('manual_lottery_result')->where([
                'lottery_id'=> $this->lottery_id,
                'opentimestamp'=> ['gt', $this->nowTime]
            ])->find();
            if (!empty($result)) {
                $this->action_name = $result['action_name'];//人工开奖客服
            }
        }
        if(true || empty($result)) {
            // 购买API开奖
            $result = json_decode(api_curl($url), true);
        	//$result = json_decode($this->getJsonPost($url,null), true);
            $jsondata = $result ? json_encode($result) : 'NOT FIND ……… NULL DATA ; '. $url ; 
            $result = isset($result['data'][0]) ? $result['data'][0] : [];
            $result = !empty($result) && $result['opentimestamp'] > $this->nowTime ? $result : [];
            file_put_contents(APP_PATH.'Runtime/lottery.txt',  "[ ".date('Y-m-d H:i:s')." ]{$this->lottery_id}采集成功！{$this->nowTime}------ ".$result['opentimestamp'].$jsondata."\n", FILE_APPEND);
            if(empty($result)) {
                // 自己去网站抓的开奖
                $result = M('push_lottery_result')->where(['lottery_id'=>$this->lottery_id])->order('id desc')->find();
                $result = !empty($result) && strtotime($result['open_time']) >= $this->nowTime ? $result : [];
            }
        }
        if (empty($result)) {
            return false;
        }
        $this->issue = $result['expect'];
        $this->opencode = explode(',', $result['opencode']);
        foreach ($this->opencode as $key => $value) {
            $this->opencode[$key] = (int)$value;
        }
        return true;
    }

    private function executeResult() {
        // 获取游戏列表
        $gameList = D('Game')->getGameList($this->lottery_id);
        foreach ($gameList as $game_key => $gameInfo) {
            $siteList = D('Site')->getSiteList($gameInfo['game_id']);
            // 牌九
            $zoneDetail = [];
            if ($gameInfo['game_id'] == 1 || $gameInfo['game_id'] == 7) {
                for ($zone = 1; $zone <= $gameInfo['zone_count']; $zone++) {
                    $opencode_key = ($zone-1) * 2;
                    $number1 = $this->opencode[$opencode_key];
                    $number2 = $this->opencode[$opencode_key + 1];
                    $zoneDetail[] = [
                        'zone' => $zone,
                        'point' => ($number1 + $number2) % 10,
                        'max_number' => max([$number1, $number2]),
                    ];
                }
                $zoneDetail = sortZone($zoneDetail);
                // 遍历房间
                foreach ($siteList as $site_key => $siteInfo) {
                    // 房间ID
                    $room_id = $siteInfo['site_id'];
                    $this->bigWinSmall($zoneDetail, $room_id);
                }
            // 牛牛
            } elseif ($gameInfo['game_id'] == 2 || $gameInfo['game_id'] == 8) {
                for ($zone = 1; $zone <= $gameInfo['zone_count']; $zone++) {
                    $opencode_key = ($zone-1) * 5;
                    $number1 = $this->opencode[$opencode_key];
                    $number2 = $this->opencode[$opencode_key + 1];
                    $number3 = $this->opencode[$opencode_key + 2];
                    $number4 = $this->opencode[$opencode_key + 3];
                    $number5 = $this->opencode[$opencode_key + 4];
                    $arr = [$number1, $number2, $number3, $number4, $number5];
                    $zoneDetail[] = [
                        'zone' => $zone,
                        'point' => getCowPoint($arr),
                        'max_number' => max([$number1, $number2, $number3, $number4, $number5]),
                    ];
                }
                $zoneDetail = sortZone($zoneDetail);
                // 遍历房间
                foreach ($siteList as $site_key => $siteInfo) {
                    // 房间ID
                    $room_id = $siteInfo['site_id'];
                    $this->bigWinSmall($zoneDetail, $room_id);
                }
            //  三公
            } elseif ($gameInfo['game_id'] == 3 || $gameInfo['game_id'] == 9) {
                for ($zone = 1; $zone <= $gameInfo['zone_count']; $zone++) {
                    $opencode_key = ($zone-1) * 2;
                    $number1 = $this->opencode[$opencode_key];
                    $number2 = $this->opencode[$opencode_key + 1];
                    $number3 = isset($this->opencode[$opencode_key + 2]) ? $this->opencode[$opencode_key + 2] : $this->opencode[0];
                    $zoneDetail[] = [
                        'zone' => $zone,
                        'point' => ($number1 + $number2 + $number3) % 10,
                        'max_number' => max([$number1, $number2, $number3]),
                    ];
                }
                $zoneDetail = sortZone($zoneDetail);
                // 遍历房间
                foreach ($siteList as $site_key => $siteInfo) {
                    // 房间ID
                    $room_id = $siteInfo['site_id'];
                    $this->bigWinSmall($zoneDetail, $room_id);
                }
            // 龙虎
            } elseif ($gameInfo['game_id'] == 4 || $gameInfo['game_id'] == 10) {
                // 单双
                for ($i = 0; $i < count($this->opencode); $i++) {
                    $zoneDetail[] = [
                        'zone' => $i * 2 + 1,
                        'point' => $this->opencode[$i] % 2 == 0 ? 0 : 1,
                        'max_number' => 0,
                        'rank' => 0,
                    ];
                    $zoneDetail[] = [
                        'zone' => $i * 2 + 2,
                        'point' => $this->opencode[$i] % 2 == 0 ? 1 : 0,
                        'max_number' => 0,
                        'rank' => 0,
                    ];
                }
                // 大小
                for ($i = 0; $i < count($this->opencode); $i++) {
                    $zoneDetail[] = [
                        'zone' => $i * 2 + 21,
                        'point' => $this->opencode[$i] > 5 ? 1 : 0,
                        'max_number' => 0,
                        'rank' => 0,
                    ];
                    $zoneDetail[] = [
                        'zone' => $i * 2 + 22,
                        'point' => $this->opencode[$i] > 5 ? 0 : 1,
                        'max_number' => 0,
                        'rank' => 0,
                    ];
                }
                // 龙虎斗
                for ($i = 0; $i < count($this->opencode) / 2; $i++) {
                    $zoneDetail[] = [
                        'zone' => $i * 2 + 41,
                        'point' => $this->opencode[$i] > $this->opencode[9-$i] ? 1 : 0,
                        'max_number' => 0,
                        'rank' => 0,
                    ];
                    $zoneDetail[] = [
                        'zone' => $i * 2 + 42,
                        'point' => $this->opencode[$i] > $this->opencode[9-$i] ? 0 : 1,
                        'max_number' => 0,
                        'rank' => 0,
                    ];
                }
                foreach ($siteList as $site_key => $siteInfo) {
                    // 房间ID
                    $room_id = $siteInfo['site_id'];
                    $this->dragonTiger($zoneDetail, $room_id, $userZoneBet, $zoneBet, $roomBet);
                }
            // 单张
            } elseif ($gameInfo['game_id'] == 5) {
                for ($zone = 1; $zone <= $gameInfo['zone_count']; $zone++) {
                    $zoneDetail[] = [
                        'zone' => $zone,
                        'point' => $this->opencode[$zone - 1],
                        'max_number' => $this->opencode[$zone - 1],
                    ];
                }
                $zoneDetail = sortZone($zoneDetail);
                // 遍历房间
                foreach ($siteList as $site_key => $siteInfo) {
                    // 房间ID
                    $room_id = $siteInfo['site_id'];
                    $this->bigWinSmall($zoneDetail, $room_id);
                }
            // 龙虎
            } elseif ($gameInfo['game_id'] == 6) {
                $sum = 0;
                for ($i = 0; $i < count($this->opencode); $i++) {
                    $sum += $this->opencode[$i];
                }
                // 单双
                $zoneDetail[] = [
                    'zone' => 1,
                    'point' => $sum % 2 == 1 ? 1 : 0,
                    'max_number' => 0,
                    'rank' => 0,
                ];
                $zoneDetail[] = [
                    'zone' => 2,
                    'point' => $sum % 2 == 0 ? 1 : 0,
                    'max_number' => 0,
                    'rank' => 0,
                ];
                // 大小
                $zoneDetail[] = [
                    'zone' => 3,
                    'point' => $sum >= 23 ? 1 : 0,
                    'max_number' => 0,
                    'rank' => 0,
                ];
                $zoneDetail[] = [
                    'zone' => 4,
                    'point' => $sum < 23 ? 1 : 0,
                    'max_number' => 0,
                    'rank' => 0,
                ];
                // 龙虎
                $is_long = $this->opencode[0] > $this->opencode[4] ? 1 : ($this->opencode[0] < $this->opencode[4] ? 0 : 2);
                $is_hu = $this->opencode[0] < $this->opencode[4] ? 1 : ($this->opencode[0] > $this->opencode[4] ? 0 : 2);
                $zoneDetail[] = [
                    'zone' => 5,
                    'point' => $is_long,
                    'max_number' => 0,
                    'rank' => 0,
                ];
                $zoneDetail[] = [
                    'zone' => 6,
                    'point' => $is_hu,
                    'max_number' => 0,
                    'rank' => 0,
                ];

                foreach ($siteList as $site_key => $siteInfo) {
                    // 房间ID
                    $room_id = $siteInfo['site_id'];
                    $this->dragonTiger($zoneDetail, $room_id, $userZoneBet, $zoneBet, $roomBet);
                }
            }
            M('open_result_log')->add([
                'game_id'=> $gameInfo['game_id'],
                'issue'=> $this->issue,
                'zone_detail'=>json_encode(sortArray($zoneDetail,'zone','asc')),
                'add_time'=> $this->nowTime,
            ]);
        }
    }

    // 大食小算法
    private function bigWinSmall($zoneDetailSort, $room_id) {
        $ret = $this->getRoomBetDetail($room_id);
        $userZoneBet = $ret['userZoneBet']; //用户区域下注
        $zoneBet = $ret['zoneBet']; //区域下注
        $totalBet = $ret['totalBet']; //总下注

        $redis = redisCache();
        $rate = getConfig('rate');//回佣率
        $tempArr = [];
        foreach ($zoneDetailSort as $k => $v) {
            $tempArr[$v['zone']] = $v;
        }
        $zoneDetailSort = $tempArr;
        M('Host')->where(['room_id'=> $room_id, 'is_delete'=> 1])->delete();// 删除下庄的记录
        $hostInfo = M('Host')->where(['room_id'=> $room_id, 'status'=> ['gt', 0]])->find();
        if (!empty($hostInfo)) {
            // 有庄家情况
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
                    $balance = isset($zoneBet[$value['zone']]) ? $zoneBet[$value['zone']] : 0;
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
                $hostInfo['commission'] = C('PATTERN')==1 ? bcmul($win_balance+$lost_balance, $rate/2, 2) : bcmul($win_balance, $rate, 2);// 代理佣金
                $hostInfo['deduction'] = bcmul($win_balance, $rate, 2);// 玩家扣除
                // cal_balance
                foreach ($zoneDetailSort as $key => $value) {
                    if ($value['zone'] == $hostInfo['host_zone']) {
                        $zoneDetailSort[$key]['cal_balance'] = bcsub(bcadd($hostInfo['host_balance'], $win_balance, 2), $lost_balance, 2);
                    } else {
                        if ($hostInfo['rank'] > $value['rank']) {
                            $zoneDetailSort[$key]['cal_balance'] = bcmul($value['balance'], 2, 2);
                        } elseif ($hostInfo['rank'] < $value['rank']) {
                            $zoneDetailSort[$key]['cal_balance'] = 0;
                        } else {
                            $zoneDetailSort[$key]['cal_balance'] = $value['balance'];
                        }
                    }
                }
            }
        } else {
            // 没有庄家的情况
            $half_balance = $totalBet/2;
            $temp_total = 0;
            $bindArr = [];
            foreach ($zoneDetailSort as $key => $value) {
                $bindArr[$value['rank']]['zones'][] = $value['zone'];
            }
            // 获取一半金额在的位置$rank
            $rank = 0;
            foreach ($bindArr as $key => $value) {
                foreach ($value['zones'] as $k => $v) {
                    if (isset($zoneBet[$v])) {
                        $temp_total += $zoneBet[$v];
                    }
                }
                if ($temp_total >= $half_balance) {
                    $rank = $key;
                    break;
                }
            }
            // balance、cal_balance
            $temp_total = 0;
            foreach ($bindArr as $key => $value) {
                if ($key < $rank) {
                    foreach ($value['zones'] as $k => $v) {
                        $zoneDetailSort[$v]['balance'] = isset($zoneBet[$v]) ? $zoneBet[$v] : 0;
                        $zoneDetailSort[$v]['cal_balance'] = bcmul($zoneDetailSort[$v]['balance'], 2, 2);
                        $temp_total = bcadd($temp_total, $zoneDetailSort[$v]['cal_balance'], 2);
                    }
                } elseif ($key == $rank) {
                    $cal_balance = bcsub($totalBet, $temp_total, 2);
                    $zone_total = 0;
                    foreach ($value['zones'] as $k => $v) {
                        $zoneDetailSort[$v]['balance'] = isset($zoneBet[$v]) ? $zoneBet[$v] : 0;
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
                        $zoneDetailSort[$v]['balance'] = isset($zoneBet[$v]) ? $zoneBet[$v] : 0;
                        $zoneDetailSort[$v]['cal_balance'] = 0;
                    }
                }
            }
        }
        // 用户在房间下注情况
        if (!empty($hostInfo)) {
            // 计算庄家收入
            $final_balance = bcsub($zoneDetailSort[$hostInfo['host_zone']]['cal_balance'], $hostInfo['deduction']);
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
                'bet_detail' => json_encode([['zone'=> $hostInfo['host_zone'],'balance'=> sprintf('%.2f',$zoneDetailSort[$hostInfo['host_zone']]['balance']),'win_balance'=>$hostInfo['profit_balance'], 'commission'=>$hostInfo['commission']]]),
                'finished' => $this->finished,
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
        foreach ($userZoneBet as $user_id => $userBetList) {
            // 计算闲家的情况
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
            foreach ($userBetList as $zone => $value) {
                $cal_balance = bcdiv(bcmul($value, $zoneDetailSort[$zone]['cal_balance'], 2), $zoneDetailSort[$zone]['balance'], 2);
                $final_balance = $cal_balance <= $value ? $cal_balance : bcadd($value, ($cal_balance - $value) * (1-$rate), 2);
                $commission = C('PATTERN')==1 ? bcmul(abs(bcsub($cal_balance, $value, 2)), $rate/2) : bcsub($cal_balance, $final_balance, 2);//佣金
                $tempInfo['bet_balance'] = bcadd($tempInfo['bet_balance'] , $value, 2);
                $tempInfo['profit_balance'] = bcadd($tempInfo['profit_balance'] , bcsub($final_balance, $value, 2), 2);
                $tempInfo['final_balance'] = bcadd($tempInfo['final_balance'] , $final_balance, 2);
                $tempInfo['commission'] = bcadd($tempInfo['commission'], $commission, 2);
                $tempInfo['bet_detail'][] = [
                    'zone'=> $zone,
                    'balance'=> sprintf('%.2f',$value),
                    'win_balance'=> bcsub($final_balance, $value, 2),
                    'commission'=> $commission,
                ];
            }
            $betLog[] = $tempInfo;
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
                'finished' => $this->finished,
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
                'is_catch' => $this->finished,
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

    // 龙虎算法
    private function dragonTiger($zoneDetail, $room_id, $userZoneBet, $zoneBet, $roomBet) {
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
                $balance = isset($zoneBet[$value['zone']]['balance']) ? $zoneBet[$value['zone']]['balance'] : 0;
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
                'finished' => $this->finished,
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
            foreach ($userZoneBet as $user_id => $userBetList) {
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
                    'finished' => $this->finished,
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

    private function getRoomBetDetail($room_id) {
        // 获取下注列表
        $redis = redisCache();
        $betDetail = $redis->lrange(CacheEnum::BET_DETAIL.$room_id, 0, -1);
        $redis->delete(CacheEnum::BET_DETAIL.$room_id);
        $userZoneBet = [];
        $zoneBet = [];
        $totalBet = 0;
        foreach ($betDetail as $key => $value) {
            $value = json_decode($value, true);
            // 用户区域下注
            if (!isset($userZoneBet[$value['user_id']][$value['zone']])) {
                $userZoneBet[$value['user_id']][$value['zone']] = $value['balance'];
            } else {
                $userZoneBet[$value['user_id']][$value['zone']] += $value['balance'];
            }
            // 区域下注
            if (!isset($zoneBet[$value['zone']])) {
                $zoneBet[$value['zone']] = $value['balance'];
            } else {
                $zoneBet[$value['zone']] += $value['balance'];
            }
            // 总下注
            $totalBet += $value['balance'];
        }
        return [
            'userZoneBet'=> $userZoneBet,
            'zoneBet'=> $zoneBet,
            'totalBet'=> $totalBet,
        ];
    }
}
