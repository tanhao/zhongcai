<?php
namespace Agent\Controller;
use Lib\Enum\CodeEnum;
use Lib\Enum\CacheEnum;
class BetController extends BaseController {

    // 实时注单
    public function nowBetDetail() {
        // 搜索参数
        $user = M('user');
        $user_id_arr = $user->where(['invite_code'=> $this->agUserInfo['invite_code']])->getField('user_id', true);
        $list = $this->gatherUserBetBalance($user_id_arr);
        $site = D('Site');
        $game = D('Game');
        $lotteryNameList = [1=>'赛车',2=>'时时彩',3=>'飞艇'];
        $issue1 = M('lottery_issue')->where(['lottery_id' => 1])->order('id desc')->getField('issue');
        $issue2 = M('lottery_issue')->where(['lottery_id' => 2])->order('id desc')->getField('issue');
        $issue3 = M('lottery_issue')->where(['lottery_id' => 3])->order('id desc')->getField('issue');
        $issueList = [1=>$issue1+1, 2=>$issue2+1, 3=>$issue3+1];
        $total = "0.00";
        $tempGame =[];
        $tempSite =[];
        $tempUser =[];
        foreach ($list as $key => $value) {
            if (isset($tempUser[$value['user_id']])) {
                $userInfo = $tempUser[$value['user_id']];
            } else {
                $userInfo = $user->where(['user_id'=> $value['user_id']])->field('user_name,nickname')->find();
                $tempUser[$value['user_id']] = $userInfo;
            }
            $list[$key]['user_name'] = $this->hideUserName($userInfo['user_name']);
            $list[$key]['nickname'] = $userInfo['nickname'];
            if (isset($tempSite[$value['room_id']])) {
                $siteInfo = $tempSite[$value['room_id']];
            } else {
                $siteInfo = $site->getSiteInfo($value['room_id']);
                $tempSite[$value['room_id']] = $siteInfo;
            }
            $list[$key]['site_name'] = $siteInfo['site_name'];
            if (isset($tempGame[$siteInfo['game_id']])) {
                $gameInfo = $tempGame[$siteInfo['game_id']];
            } else {
                $gameInfo = $game->getGameInfo($siteInfo['game_id']);
                $tempGame[$siteInfo['game_id']] = $gameInfo;
            }
            $list[$key]['game_name'] = $gameInfo['game_name'];
            $list[$key]['lottery_name'] = $lotteryNameList[$gameInfo['lottery_id']];
            $list[$key]['issue'] = $issueList[$gameInfo['lottery_id']];
            $total = bcadd($total, $value['balance'], 2);
        }

        $this->assign('game_id', $game_id);
        $this->assign('total', $total);
        $this->assign('list', $list);
        $this->display();
    }

    private function gatherUserBetBalance ($user_id_arr) {
        if (empty($user_id_arr)) return [];
        // 获取房间ID集合
        $roomList = [];
        $gameList = D('Game')->getGameList();
        foreach ($gameList as $key => $value) {
            $siteList = D('Site')->getSiteList($value['game_id']);
            foreach ($siteList as $key => $value) $roomList[] = $value['site_id'];
        }
        $redis = redisCache();
        // 结果
        $result = [];
        foreach ($roomList as $room_id) {
            $list = $redis->lrange(CacheEnum::BET_DETAIL.$room_id, 0, -1);
            if (empty($list)) continue;
            $userZoneBalance = [];
            foreach ($list as $key => $value) {
                $value = json_decode($value, true);
                if (!isset($userZoneBalance[$value['user_id']][$value['zone']])) {
                    $userZoneBalance[$value['user_id']][$value['zone']] = $value;
                } else {
                    $userZoneBalance[$value['user_id']][$value['zone']]['balance'] += $value['balance'];
                }
            }
            
            foreach ($userZoneBalance as $_user_id => $value) {
                if (in_array($_user_id, $user_id_arr)) {
                    foreach ($value as $_zone => $v) {
                        $v['room_id'] = $room_id;
                        $result[] = $v;
                    }
                }
            }
        }
        return $result;
    }

    // 历史下注
    public function historyBetForm() {
        // 查询条件
        $lotteryNameList = [1=>'北京赛车',2=>'时时彩',3=>'幸运飞艇'];
        $game_select = [];
        $game_select = [[
            'value'=>0,
            'text'=>'全部彩种',
            'children'=> [[
                'value'=>0,
                'text'=>'全部玩法',
                'children'=>[[
                    'value'=>0,
                    'text'=>'全部房间',
                ]]]]
        ]];
        $site = D('Site');
        $game = D('Game');
        for ($i=1; $i <= 3; $i++) { 
            $lotteryInfo = ['value'=> $i,'text'=> $lotteryNameList[$i]];
            $gameList = $game->getGameList($i);
            $lottery_child = [['value'=>0,'text'=>'全部玩法','children'=>[['value'=>0,'text'=>'全部房间']]]];
            foreach ($gameList as $key => $value) {
                
                $temp = ['value'=>$value['game_id'],'text'=>$value['game_name']];
                $siteList = $site->getSiteList($value['game_id']);
                $game_child = [['value'=>0,'text'=>'全部房间']];
                foreach ($siteList as $k => $v) {
                    $game_child[] = ['value'=>$v['site_id'],'text'=>$v['site_name']];
                }
                $temp['children'] = $game_child;
                $lottery_child[] = $temp;
            }
            $lotteryInfo['children'] = $lottery_child;
            $game_select[] = $lotteryInfo;
        }
        // 期数
        $qishu = [[
            'value'=>0,
            'text'=>'全部',
        ]];
        $lottery_issue = M('lottery_issue');
        for ($i=1; $i <= 3; $i++) {
            $lotteryInfo = ['value'=> $i,'text'=> $lotteryNameList[$i]];
            $issueList = $lottery_issue->where(['lottery_id'=>$i])->order('id desc')->limit(10)->field('id,issue')->select();
            foreach ($issueList as $key => $value) {
                $temp = ['value'=>$value['id'],'text'=>$lotteryNameList[$i].$value['issue'].'期'];
                $qishu[] = $temp;
            }
        }
        // print_r($qishu);exit;
        $this->assign('start_time',date('Y-m-01'));
        $this->assign('end_time',date('Y-m-d'));
        $this->assign('game_select', json_encode($game_select));
        $this->assign('qishu', json_encode($qishu));
        $this->display();
    }

    public function historyBetSheet() {
        $lottery_id = I('get.lottery_id', 0, 'intval');
        $game_id = I('get.game_id', 0, 'intval');
        $room_id = I('get.room_id', 0, 'intval');
        $issue_id = I('get.issue_id', 0, 'intval');
        $start_time = I('get.start_time');
        $end_time = I('get.end_time');
        $user_name = I('get.user_name','','trim');
        $nickname = I('get.nickname','','trim');

        $where = ['is_host'=>0];
        $temp = true;
        if ($issue_id > 0) {
            $lotteryInfo = M('lottery_issue')->where(['id'=> $issue_id])->field('lottery_id,issue')->find();
            if (!empty($lotteryInfo)) {
                $where['issue'] = $lotteryInfo['issue'];
                $where['lottery_id'] = $lotteryInfo['lottery_id'];
                $temp = false;
            }
        }
        if ($temp) {
            // 彩种
            if ($lottery_id > 0) {
                $site = D('Site');
                $game = D('Game');
                $lottery_id = in_array($lottery_id, [0,1,2,3]) ? $lottery_id : 0;
                if ($lottery_id > 0) {
                    $where['lottery_id'] = $lottery_id;
                    // 游戏类型
                    $game_ids = [];
                    if ($game_id == 0) {
                        $gameTempList = $game->getGameList($lottery_id);
                        foreach ($gameTempList as $key => $value) {
                            $game_ids[] = $value['game_id'];
                        }
                    } else {
                        $game_ids[] = $game_id;
                    }
                    // 房间条件
                    $room_ids = [];
                    if ($room_id == 0) {
                        if (!empty($game_ids)) {
                            $room_ids = $site->where(['game_id'=>['in', $game_ids]])->getField('site_id', true);
                            if (!empty($room_ids)) {
                                $where['room_id'] = ['in', $room_ids];
                            }
                        }
                    } else {
                        $where['room_id'] = $room_id;
                    }
                }
            }
            // 日期
            if (!empty($start_time)) {
                $where['add_time'][] = ['egt', strtotime($start_time)];
            }
            if (!empty($end_time)) {
                $where['add_time'][] = ['lt', strtotime($end_time)+86400];
            }
        }
        $user = M('user');
        // 用户名和昵称搜索
        $user_ids = [];
        if (!empty($user_name)) {
            $user_id = $user->where(['user_name'=>$user_name,'invite_code'=> $this->agUserInfo['invite_code']])->getField('user_id');
            $user_ids[] = !empty($user_id) ? $user_id : 0;
        }
        if (!empty($nickname)) {
            $user_id = $user->where(['nickname'=>$nickname,'invite_code'=> $this->agUserInfo['invite_code']])->getField('user_id');
            $user_ids[] = !empty($user_id) ? $user_id : 0;
        }
        if (!empty($user_ids)) {
            $where['user_id'] = ['in', $user_ids];
        } else {
            $user_id_arr = $user->where(['invite_code'=> $this->agUserInfo['invite_code']])->getField('user_id', true);
            if (!empty($user_id_arr)) {
                $where['user_id'] = ['in', $user_id_arr];
            } else {
                $where['user_id'] = 0;
            }
        }
        // 查询
        $bet_log = M('bet_log');
        $count = $bet_log->where($where)->count();
        $pageInfo = setAppPage($count);
        $betLogList = $bet_log->where($where)->order('id desc')->field('user_id,room_id,lottery_id,issue,bet_detail,add_time,finished')->limit($pageInfo['limit'])->select();
        $site = D('Site');
        $game = D('Game');
        $lotteryNameList = [1=>'赛车',2=>'时时彩',3=>'飞艇'];
        $tempGame =[];
        $tempSite =[];
        $tempUser =[];
        $list = [];
        foreach ($betLogList as $key => $value) {
            if (isset($tempUser[$value['user_id']])) {
                $userInfo = $tempUser[$value['user_id']];
            } else {
                $userInfo = $user->where(['user_id'=> $value['user_id']])->field('user_name,nickname')->find();
                $tempUser[$value['user_id']] = $userInfo;
            }
            if (isset($tempSite[$value['room_id']])) {
                $siteInfo = $tempSite[$value['room_id']];
            } else {
                $siteInfo = $site->getSiteInfo($value['room_id']);
                $tempSite[$value['room_id']] = $siteInfo;
            }
            if (isset($tempGame[$siteInfo['game_id']])) {
                $gameInfo = $tempGame[$siteInfo['game_id']];
            } else {
                $gameInfo = $game->getGameInfo($siteInfo['game_id']);
                $tempGame[$siteInfo['game_id']] = $gameInfo;
            }
            $bet_detail = json_decode($value['bet_detail'], true);
            foreach ($bet_detail as $k => $v) {
                $list[] = [
                    'user_name'=> substr($userInfo['user_name'], 0,2).'****'.substr($userInfo['user_name'], -1),
                    'nickname'=> $userInfo['nickname'],
                    'title'=> $lotteryNameList[$gameInfo['lottery_id']].' '.$value['issue'].'期 '.$siteInfo['site_name'].'-'.$gameInfo['game_name'],
                    'zone'=> $v['zone'],
                    'balance'=> $v['balance'],
                    'rate'=> $this->agUserInfo['rate'],
                    'win_balance'=> $v['win_balance'],
                    'commission'=> $v['commission'],
                    'finished'=> $value['finished'],
                    'add_time'=> $value['add_time'],
                ];
            }
        }
        $this->assign('list', $list);
        $this->assign('pageInfo', $pageInfo);
        $this->display();
    }
}