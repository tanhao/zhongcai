<?php
namespace Cs\Controller;
use Lib\Enum\CodeEnum;
use Lib\Enum\CacheEnum;
class GameController extends BaseController {
	// 房间列表
    public function room(){
        $lottery_id = I('get.lottery_id', 0, 'intval');
        $site = M('site');
        $game = M('game');
        $where = ['g.status'=>1];
        if ($lottery_id > 0) {
            $where['g.lottery_id'] = $lottery_id;
        }
        $db_prefix = C('DB_PREFIX');
        $count = $site->join("as s left join {$db_prefix}game as g on s.game_id=g.game_id")->where($where)->count();
        $pageInfo = setPage($count);
        $list = $site->join("as s left join {$db_prefix}game as g on s.game_id=g.game_id")->where($where)->limit($pageInfo['limit'])->select();
        foreach ($list as $key => $value) {
            switch ($value['lottery_id']) {
                case '1': $list[$key]['lottery_name'] = "北京赛车";break;
                case '2': $list[$key]['lottery_name'] = "重庆时时彩";break;
                case '3': $list[$key]['lottery_name'] = "幸运飞艇";break;
            }
            switch ($value['site_type']) {
                case '1': $list[$key]['type_name'] = "底注10";break;
                case '2': $list[$key]['type_name'] = "底注100";break;
                case '3': $list[$key]['type_name'] = "体验场";break;
            }
            $list[$key]['online_count'] = D('Site')->getRoomCount($value['site_id']);
        }
        $this->assign('lottery_id', $lottery_id);
        $this->assign('list', $list);
        $this->assign('pageInfo', $pageInfo);
    	$this->display();
    }

    // 查看在线会员
    public function onlineUser() {
        $room_id = I('get.room_id', 0, 'intval');
        $where = [
            't.room_id' => $room_id,
            't.online' => 1,
        ];
        $user_token = M('user_token');
        $db_prefix = C('DB_PREFIX');
        $count = $user_token->join("as t left join {$db_prefix}user as u on u.user_id=t.user_id")->where($where)->count();
        $pageInfo = setPage($count);
        $list = $user_token->join("as t left join {$db_prefix}user as u on u.user_id=t.user_id")->where($where)->limit($pageInfo['limit'])->field('t.user_id,u.nickname,u.user_name')->select();
        foreach ($list as $key => $value) {
            if (!isset($value['nickname']) || !isset($value['user_name'])) {
                $list[$key]['user_id'] = substr($value['user_id'], -6);
                $list[$key]['user_name'] = $value['user_id'];
                $list[$key]['nickname'] = getTempNickname($value['user_id']);
            }
        }

        $this->assign('room_id', $room_id);
        $this->assign('list', $list);
        $this->assign('pageInfo', $pageInfo);
        $this->display();
    }
    // 编辑房间
    public function editRoom() {
        if (IS_POST) {
            $site_id = I('post.site_id', 0, 'intval');
            $max_member = I('post.max_member', 0, 'intval');
            $less_host_banlance = I('post.less_host_banlance', 0, 'intval');
            $max_host_banlance = I('post.max_host_banlance', 0, 'intval');
            $max_bet_banlance = I('post.max_bet_banlance', 0, 'intval');
            $site_name = I('post.site_name', '', 'htmlspecialchars,trim');
            if ($site_id < 1 || $max_member < 1 || $less_host_banlance < 1 || $max_host_banlance < 1 || $max_bet_banlance < 1 || empty($site_name)) {
                $this->ajaxOutput('参数错误');
            }
            $site = M('site');
            $siteInfo = $site->where(['site_id'=> $site_id])->find();
            if (empty($siteInfo)) {
                $this->ajaxOutput('房间不存在');
            }
            $site->where(['site_id'=> $site_id])->save([
                'max_member'=> $max_member,
                'less_host_banlance'=> $less_host_banlance,
                'max_host_banlance'=> $max_host_banlance,
                'max_bet_banlance'=> $max_bet_banlance,
                'site_name'=> $site_name,
            ]);
            // 清缓存
            $redis = redisCache();
            $redis->delete(CacheEnum::SITE_LIST . $siteInfo['game_id']);
            $redis->delete(CacheEnum::SITE_INFO . $site_id);
            $this->addAtionLog("编辑房间（id：{$site_id}）配置信息");
            $this->ajaxOutput('编辑成功', 1, U('Game/room'));
        } else {
            $site_id = I('get.site_id', 0, 'intval');
            $site = M('site');
            $siteInfo = $site->where(['site_id'=> $site_id])->find();
            if (empty($siteInfo)) {
                $this->error('房间不存在');
            }
            $gameInfo = M('game')->where(['game_id'=>$siteInfo['game_id']])->field('game_name,lottery_id')->find();
            $siteInfo['game_name'] = $gameInfo['game_name'];
            switch ($gameInfo['lottery_id']) {
                case '1': $siteInfo['lottery_name'] = "北京赛车";break;
                case '2': $siteInfo['lottery_name'] = "重庆时时彩";break;
                case '3': $siteInfo['lottery_name'] = "幸运飞艇";break;
            }
            switch ($siteInfo['site_type']) {
                case '1': $siteInfo['type_name'] = "底注10";break;
                case '2': $siteInfo['type_name'] = "底注100";break;
                case '3': $siteInfo['type_name'] = "体验场";break;
            }
            $this->assign('siteInfo', $siteInfo);
            $this->display();
        }
    }

    public function pk10(){
        $lottery_id = 1;
        $this->lottery($lottery_id);
    }

    public function ssc(){
        $lottery_id = 2;
        $this->lottery($lottery_id);
    }

    public function xyft(){
        $lottery_id = 3;
        $this->lottery($lottery_id);
    }

    // 彩奖列表
    private function lottery($lottery_id){
        $lottery_issue = M('lottery_issue');
        $where = ['lottery_id'=> $lottery_id];
        $count = $lottery_issue->where($where)->count();
        $pageInfo = setPage($count);
        $list = $lottery_issue->where($where)->order('id desc')->limit($pageInfo['limit'])->select();
        $bet_log = M('bet_log');
        $rate = getConfig('rate');
        foreach ($list as $key => $value) {
            $list[$key]['lottery_number'] = str_replace('0', '10', implode('-', str_split($value['lottery_number'])));
            $bet_balance = $bet_log->where(['lottery_id'=> $lottery_id,'issue'=>$value['issue'],'is_host'=>0])->sum('bet_balance');
            $list[$key]['bet_balance'] = !empty($bet_balance) ? $bet_balance : "0.00";
            $commission = $bet_log->where(['lottery_id'=> $lottery_id,'issue'=>$value['issue']])->sum('commission');
            $list[$key]['commission'] = !empty($commission) ? $commission : "0.00";
            $list[$key]['trade_balance'] = bcdiv($commission * 2, $rate, 2);
        }
        $action = $lottery_id==1 ? 'pk10' : ($lottery_id==2 ? 'ssc' : 'xyft');
        $this->assign('url', U('Game/'.$action));
        $this->assign('lottery_id', $lottery_id);
        $this->assign('list', $list);
        $this->assign('pageInfo', $pageInfo);
        $this->display('lottery');
    }

    // 查看下注
    public function betLog() {
        $id = I('get.id', 0, 'intval');
        $room_id = I('get.room_id', 0, 'intval');
        $game_id = I('get.game_id', 0, 'intval');
        $zone = I('get.zone', 0, 'intval');
        $user_name = I('get.user_name');

        $lottery_issue = M('lottery_issue');
        $site = D('Site');
        $game = D('Game');
        $issueInfo = $lottery_issue->where(['id'=> $id])->field('lottery_id,issue')->find();
        if (empty($issueInfo)) {
            $this->error('该期不存在');
        }
        $lottery_id = $issueInfo['lottery_id'];
        $lottery_name = '';
        switch ($issueInfo['lottery_id']) {
            case '1': $lottery_name = "北京赛车";break;
            case '2': $lottery_name = "重庆时时彩";break;
            case '3': $lottery_name = "幸运飞艇";break;
        }
        $where = ['lottery_id'=> $issueInfo['lottery_id'], 'issue'=> $issueInfo['issue']];
        $user = M('user');
        if (!empty($user_name)) {
            $user_id = $user->where(['user_name'=> $user_name])->getField('user_id');
            $where['user_id'] = $user_id;
        }
        if ($game_id > 0) {
            $gameInfo = $game->getGameInfo($game_id);
            $room_ids = $site->where(['game_id'=> $game_id])->getField('site_id', true);
            $where['room_id'] = ['in', $room_ids];
            if ($room_id > 0 && in_array($room_id, $room_ids)) {
                $where['room_id'] = $room_id;
                if ($zone > $gameInfo['zone_count']) {
                    $zone = 0;
                }
            } else {
                $room_id = 0;
            }
        } else {
            $room_id = 0;
        }
        if ($room_id == 0 || $game_id == 0) {
            $zone = 0;
        }
        
        $bet_log = M('bet_log');
        $betList = $bet_log->where($where)->select();
        foreach ($betList as $key => $value) {
            $bet_detail = json_decode($value['bet_detail'], true);
            foreach ($bet_detail as $k => $v) {
                if ($zone == 0 || $v['zone'] == $zone) {
                    $list[] = [
                        'lottery_name'=> $lottery_name,
                        'user_id'=> $value['user_id'],
                        'room_id'=> $value['room_id'],
                        'zone'=> $v['zone'],
                        'balance'=> $v['balance'],
                        'win_balance'=> $v['win_balance'],
                        'add_time'=> $value['add_time'],
                    ];
                }
            }
        }
        $count = count($list);
        $pageInfo = setPage($count);
        list($offset, $length) = explode(',', $pageInfo['limit']);
        $list = array_slice($list, $offset, $length);
        foreach ($list as $key => $value) {
            $list[$key]['user_name'] = $user->where(['user_id'=> $value['user_id']])->getField('user_name');
            $siteInfo = $site->getSiteInfo($value['room_id']);
            $list[$key]['site_name'] = $siteInfo['site_name'];
            $gameInfo = $game->getGameInfo($siteInfo['game_id']);
            $list[$key]['game_name'] = $gameInfo['game_name'];
        }
        // 联动选择表单
        $game_form = '<option value="0">全部玩法</option>';
        $gameList = $game->getGameList($lottery_id);
        foreach ($gameList as $key => $value) {
            $selected = $value['game_id'] == $game_id ? "selected" : '';
            $game_form .= "<option value='{$value['game_id']}' {$selected}>{$value['game_name']}</option>";
        }
        $site_form = '<option value="0">全部房间</option>';
        if ($game_id > 0) {
            $siteList = $site->getSiteList($game_id);
            foreach ($siteList as $key => $value) {
                $selected = $value['site_id'] == $room_id ? "selected" : '';
                $site_form .= "<option value='{$value['site_id']}' {$selected}>{$value['site_name']}</option>";
            }
        }
        $zone_form = '<option value="0">区号</option>';
        if ($room_id > 0) {
            $gameInfo = $game->getGameInfo($game_id);
            for ($i = 1; $i <= $gameInfo['zone_count']; $i++) {
                $selected = $i == $zone ? "selected" : '';
                $zone_form .= "<option value='{$i}' {$selected}>{$i}</option>";
            }
        }
        $this->assign('id', $id);
        $this->assign('game_id', $game_id);
        $this->assign('zone', $zone);
        $this->assign('room_id', $room_id);
        $this->assign('user_name', $user_name);
        $this->assign('game_form', $game_form);
        $this->assign('site_form', $site_form);
        $this->assign('zone_form', $zone_form);
        $this->assign('list', $list);
        $this->assign('pageInfo', $pageInfo);
        $this->display();
    }

    // 实时注单
    public function nowBetDetail() {
        // 搜索参数
        $lottery_id = I('get.lottery_id', 0, 'intval');
        $game_id = I('get.game_id', 0, 'intval');
        $room_id = I('get.room_id', 0, 'intval');
        $zone = I('get.zone', 0, 'intval');
        $user_name = I('get.user_name');
        // 类型
        $lottery_id = in_array($lottery_id, [0,1,2,3]) ? $lottery_id : 0;
        $lotteryNameList = [0=>'全部类型',1=>'赛车',2=>'时时彩',3=>'飞艇'];
        $lotteryList = [];
        for ($i=0; $i<=3; $i++) {
            $lotteryList[] = [
                'lottery_id'=> $i,
                'lottery_name'=> $lotteryNameList[$i],
                'selected'=> ($i == $lottery_id ? 'selected' : ''),
            ];
        }
        // 玩法
        $gameList = [];
        $game = D('Game');
        $gameList = [['game_id'=>0,'game_name'=>'全部玩法','selected'=>($game_id == 0 ? 'selected' : '')]];
        if ($lottery_id == 0) {
            $game_id = 0;
        } else {
            $gameTempList = $game->getGameList($lottery_id);
            foreach ($gameTempList as $key => $value) {
                $gameList[] = [
                    'game_id'=> $value['game_id'],
                    'game_name'=> $value['game_name'],
                    'selected'=> ($game_id == $value['game_id'] ? 'selected' : '')
                ];
            }
        }
        // 房间
        $siteList = [];
        $site = D('Site');
        $siteList = [['site_id'=>0,'site_name'=>'全部房间','selected'=>($room_id == 0 ? 'selected' : '')]];
        if ($game_id == 0) {
            $room_id = 0;
        } else {
            $siteTempList = $site->getSiteList($game_id);
            foreach ($siteTempList as $key => $value) {
                $siteList[] = [
                    'site_id'=> $value['site_id'],
                    'site_name'=> $value['site_name'],
                    'selected'=> ($room_id == $value['site_id'] ? 'selected' : '')
                ];
            }
        }
        // 区间
        $zoneList = [];
        $zoneList = [['zone'=>0,'zone_name'=>'区号','selected'=>($zone == 0 ? 'selected' : '')]];
        if ($game_id == 0) {
            $zone = 0;
        } else {
            $gameInfo = $game->getGameInfo($game_id);
            for ($i = 1; $i <= $gameInfo['zone_count']; $i++) {
                $zoneList[] = [
                    'zone'=> $i,
                    'zone_name'=> $i,
                    'selected'=> ($i == $zone ? 'selected' : '')
                ];
            }
        }
        // 帐号搜索
        $user_id = 0;
        $user = M('user');
        if (!empty($user_name)) {
            $user_id = $user->where(['user_name'=> $user_name])->getField('user_id');
            $user_id = !empty($user_id) ? $user_id : -1;
        }
        
        $list = $this->gatherUserBetBalance($lottery_id, $game_id, $room_id, $zone, $user_id);
        $count = count($list);
        $pageInfo = setPage($count);
        list($offset, $length) = explode(',', $pageInfo['limit']);
        $list = array_slice($list, $offset, $length);
        foreach ($list as $key => $value) {
            $list[$key]['user_name'] = $user->where(['user_id'=> $value['user_id']])->getField('user_name');
            $siteInfo = $site->getSiteInfo($value['room_id']);
            $list[$key]['site_name'] = $siteInfo['site_name'];
            $gameInfo = $game->getGameInfo($siteInfo['game_id']);
            $list[$key]['game_name'] = $gameInfo['game_name'];
            $list[$key]['lottery_name'] = $lotteryNameList[$gameInfo['lottery_id']];
        }

        $this->assign('lottery_id', $lottery_id);
        $this->assign('game_id', $game_id);
        $this->assign('zone', $zone);
        $this->assign('room_id', $room_id);
        $this->assign('user_name', $user_name);

        $this->assign('lotteryList', $lotteryList);
        $this->assign('gameList', $gameList);
        $this->assign('siteList', $siteList);
        $this->assign('zoneList', $zoneList);

        $this->assign('list', $list);
        $this->assign('pageInfo', $pageInfo);
        $this->display();
    }

    private function gatherUserBetBalance($lottery_id, $game_id, $room_id, $zone, $user_id) {
        // 获取房间ID集合
        $roomList = [];
        if ($room_id > 0) {
            $roomList = [$room_id];
        }
        if ($game_id > 0 && empty($roomList)) {
            $siteList = D('Site')->getSiteList($game_id);
            foreach ($siteList as $key => $value) $roomList[] = $value['site_id'];
        }
        if (empty($roomList)) {
            $gameList = D('Game')->getGameList($lottery_id);
            foreach ($gameList as $key => $value) {
                $siteList = D('Site')->getSiteList($value['game_id']);
                foreach ($siteList as $key => $value) $roomList[] = $value['site_id'];
            }
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
                if ($user_id == 0 || $_user_id == $user_id) {
                    foreach ($value as $_zone => $v) {
                        if ($zone == 0 || $_zone == $zone) {
                            $v['room_id'] = $room_id;
                            $result[] = $v;
                        }
                    }
                }
            }
        }
        return $result;
    }

    // 实时注单
    public function reportForm() {
        // 接收参数
        $lottery_id = I('get.lottery_id', 0, 'intval');
        $game_id = I('get.game_id', 0, 'intval');
        $room_id = I('get.room_id', 0, 'intval');
        $user_name = I('get.user_name');
        $admin_name = I('get.admin_name');
        $start_time = I('get.start_time');
        $end_time = I('get.end_time');
        $finished = I('get.finished', 1, 'intval');
        // 日期
        if (empty($start_time) && empty($end_time)) {
            $start_time = date('Y-m'.'-1');
            $end_time = date('Y-m-d');
        }
        // 彩种
        $lottery_id = in_array($lottery_id, [0,1,2,3]) ? $lottery_id : 0;
        $lotteryNameList = [0=>'全部彩种',1=>'赛车',2=>'时时彩',3=>'飞艇'];
        $lotteryList = [];
        for ($i=0; $i<=3; $i++) {
            $lotteryList[] = [
                'lottery_id'=> $i,
                'lottery_name'=> $lotteryNameList[$i],
                'selected'=> ($i == $lottery_id ? 'selected' : ''),
            ];
        }
        // 玩法
        $gameList = [];
        $game = D('Game');
        $gameList = [['game_id'=>0,'game_name'=>'所有','selected'=>($game_id == 0 ? 'selected' : '')]];
        if ($lottery_id == 0) {
            $game_id = 0;
        } else {
            $gameTempList = $game->getGameList($lottery_id);
            foreach ($gameTempList as $key => $value) {
                $gameList[] = [
                    'game_id'=> $value['game_id'],
                    'game_name'=> $value['game_name'],
                    'selected'=> ($game_id == $value['game_id'] ? 'selected' : '')
                ];
            }
        }
        // 房间
        $siteList = [];
        $site = D('Site');
        $siteList = [['site_id'=>0,'site_name'=>'全部房间','selected'=>($room_id == 0 ? 'selected' : '')]];
        if ($game_id == 0) {
            $room_id = 0;
        } else {
            $siteTempList = $site->getSiteList($game_id);
            foreach ($siteTempList as $key => $value) {
                $siteList[] = [
                    'site_id'=> $value['site_id'],
                    'site_name'=> $value['site_name'],
                    'selected'=> ($room_id == $value['site_id'] ? 'selected' : '')
                ];
            }
        }

        $this->assign('lottery_id', $lottery_id);
        $this->assign('game_id', $game_id);
        $this->assign('room_id', $room_id);
        $this->assign('user_name', $user_name);
        $this->assign('admin_name', $admin_name);
        $this->assign('start_time', $start_time);
        $this->assign('end_time', $end_time);
        $this->assign('finished', $finished);

        $this->assign('lotteryList', $lotteryList);
        $this->assign('gameList', $gameList);
        $this->assign('siteList', $siteList);

        $this->display();
    }

    // 实时注单
    public function reportSheet() {
        // 接收参数
        $lottery_id = I('get.lottery_id', 0, 'intval');
        $game_id = I('get.game_id', 0, 'intval');
        $room_id = I('get.room_id', 0, 'intval');
        $user_name = I('get.user_name');
        $admin_name = I('get.admin_name');
        $start_time = I('get.start_time');
        $end_time = I('get.end_time');
        $finished = I('get.finished');

        $where = ['is_host'=> 0];
        // 是否结算
        $finished = in_array($finished, [0,1]) ? $finished : 1;
        $where['finished'] = $finished;
        // 时间条件
        if (!empty($start_time)) {
            $where['add_time'][] = ['egt', strtotime($start_time) + 21600];
        }
        if (!empty($end_time)) {
            $where['add_time'][] = ['lt', strtotime($end_time) + 86400 + 21600];
        }
        // 用户名
        $user = M('user');
        if (!empty($user_name)) {
            $user_id = $user->where(['user_name'=>$user_name])->getField('user_id');
            $user_id = !empty($user_id) ? $user_id : 0;
            $where['user_id'][] = ['eq', $user_id];
        }
        // 代理
        $admin_user = M('admin_user');
        if (!empty($admin_name)) {
            $invite_code = $admin_user->where(['user_name'=>$admin_name])->getField('invite_code');
            if (!empty($invite_code)) {
                $user_ids = $user->where(['invite_code'=>$invite_code])->getField('user_id',true);
                if (!empty($user_ids)) {
                    $where['user_id'][] = ['in', $user_ids];
                } else {
                    $where['user_id'] = 0;
                }
            } else {
                $where['user_id'] = 0;
            }
        }

        // 彩种
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
        // 查询语句
        $bet_log = M('bet_log');
        $count = $bet_log->where($where)->count();
        $pageInfo = setPage($count);
        $list = $bet_log->where($where)->limit($pageInfo['limit'])->order('id desc')->select();
        $lotteryEnum = [1=>'赛车',2=>'时时彩',3=>'飞艇'];
        foreach ($list as $key => $value) {
            $userInfo = $user->where(['user_id'=> $value['user_id']])->field('user_name,nickname')->find();
            $list[$key]['user_name'] = $userInfo['user_name'];
            $list[$key]['nickname'] = $userInfo['nickname'];
            $list[$key]['lottery_name'] = $lotteryEnum[$value['lottery_id']];
            $siteInfo = $site->getSiteInfo($value['room_id']);
            $list[$key]['site_name'] = $siteInfo['site_name'];
            $gameInfo = $game->getGameInfo($siteInfo['game_id']);
            $list[$key]['game_name'] = $gameInfo['game_name'];
        }

        $this->assign('lottery_id', $lottery_id);
        $this->assign('game_id', $game_id);
        $this->assign('room_id', $room_id);
        $this->assign('user_name', $user_name);
        $this->assign('admin_name', $admin_name);
        $this->assign('start_time', $start_time);
        $this->assign('end_time', $end_time);
        $this->assign('finished', $finished);
        $this->assign('list', $list);
        $this->assign('pageInfo', $pageInfo);
        $this->display();
    }
}