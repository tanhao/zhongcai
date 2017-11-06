<?php
namespace Adminbak\Controller;
use Adminbak\Controller\BaseController;
class LotteryController extends BaseController {
    public function index() {
    	$lottery_id = I('get.lottery_id', 1, 'intval');
        $lottery_id = in_array($lottery_id, [1,2,3]) ? $lottery_id : 1;
    	$lottery_issue = M('lottery_issue');
        $where = ['lottery_id'=> $lottery_id];
        $count = $lottery_issue->where($where)->count();
        $PageObject = new \Think\Page($count,15);
        $issueList = $lottery_issue->where($where)->order('id desc')->limit($PageObject->firstRow.','.$PageObject->listRows)->select();
        $this->assign('lottery_id', $lottery_id);
        $this->assign('issueList', $issueList);
    	$this->assign('page_show', $PageObject->show());
    	$this->display();
    }

    public function detail() {
        $id = I('get.id', 0, 'intval');
        $lotteryInfo = M('lottery_issue')->where(['id'=> $id])->find();
        if (empty($lotteryInfo)) {
            $this->error('非法操作');
        }
        $game_id = I('get.game_id', 0, 'intval');
        // 游戏列表
        $gameList = D('Game')->getGameList($lotteryInfo['lottery_id']);
        $gameList = addSelectKey($gameList, $game_id, 'game_id');
        $gameInfo = array();
        foreach ($gameList as $key => $value) {
            $name = $lotteryInfo['lottery_name'] . '(' . $value['game_name'] . ')';
            $gameList[$key]['name'] = $name;
            if ($value['game_id'] == $game_id) {
                $gameInfo = $value;
                $gameInfo['name'] = $name;
            }
        }
        // 区域下注金额
        $betList = M('bet_log')->where(['issue'=> $lotteryInfo['issue'], 'room_id'=> ['like',$game_id.'-%']])->select();
        $zoneList = [];
        for ($i=1; $i <= $gameInfo['zone_count']; $i++) {
            $zoneList[$i] = "0.00";
        }
        foreach ($betList as $key => $value) {
            $bet_detail = json_decode($value['bet_detail'], true);
            foreach ($bet_detail as $k => $v) {
                $zoneList[$v['zone']] = bcadd($zoneList[$v['zone']], $v['balance'], 2);
            }
        }
        // 显示格子列表
        $grid[] = ['name'=> '期数', 'value'=> $lotteryInfo['issue']];
        // 牛牛
        if ($gameInfo['zone_count'] == 2) {
            foreach ($zoneList as $zone => $balance) {
                $name = $zone == 1 ? '庄' : '闲';
                $grid[] = ['name'=> $name, 'value'=> $balance];
            }
        } elseif ($gameInfo['zone_count'] == 5) {
            // 三公/牌九
            foreach ($zoneList as $zone => $balance) {
                $name = "区域{$zone}/点数";
                $grid[] = ['name'=> $name, 'value'=> $balance];
            }
        }
        $this->assign('lotteryInfo', $lotteryInfo);
        $this->assign('gameInfo', $gameInfo);
        $this->assign('gameList', $gameList);
        $this->assign('grid', $grid);
        $this->display();
    }
}