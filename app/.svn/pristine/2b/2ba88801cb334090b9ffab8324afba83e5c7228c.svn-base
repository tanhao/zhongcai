<?php
namespace Mmbak\Controller;
use Mmbak\Controller\BaseController;
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

    public function openLottery() {
        $lottery_id = I('post.lottery_id', 0, 'intval');
        $lottery_number = I('post.lottery_number');
        $issue = I('post.issue');
        // 验证参数格式是否正确
        if (!in_array($lottery_id, [1,2,3]) || empty($lottery_number) || empty($issue) || !preg_match('/^\d+$/', $lottery_number) || !preg_match('/^\d+$/', $issue)) {
            $this->error('参数错误');
        }
        // 验证当前时间是否可以开奖
        $lotteryInfo = D('Lottery')->getLotteryInfo($lottery_id);
        $countdownInfo = D('Lottery')->getCountdown($lotteryInfo);
        if ($countdownInfo['status'] != 2 || $countdownInfo['fp_countdown'] != 0) {
            $this->error('非法开奖时间');
        }
        // 验证期数是否正确
        $now_issue = $this->getIssue($lottery_id);
        if ($now_issue != $issue) {
            $this->error("期数与当前将开奖的期数[{$now_issue}]不一致");
        }
        // 赛车/飞艇
        if ($lottery_id == 1 || $lottery_id == 3) {
            // 验证开奖结果是否合法
            $ret1 = str_split($lottery_number);
            $ret2 = array_unique($ret1);
            if (strlen($lottery_number) != 10 || count($ret1) != count($ret2)) {
                $lottery_name = $lottery_id == 1 ? '北京赛车' : '幸运飞艇';
                // $this->error("{$lottery_name}开奖结果只能是0~9不重复的10个数字");
            }
            $opencode = str_replace('0', '10', implode(',', str_split($lottery_number)));
        } else {
            // 时时彩
            if (strlen($lottery_number) != 5) {
                $this->error('重庆时时彩开奖结果只能是5个数字');
            }
            $opencode = implode(',', str_split($lottery_number));
        }
        $manual_lottery_result = M('manual_lottery_result');
        if ($manual_lottery_result->where(['lottery_id' => $lottery_id, 'expect'=> $issue])->count()) {
            $this->error('本期已被开奖，请勿重复操作');
        }
        M('manual_lottery_result')->add([
            'lottery_id' => $lottery_id,
            'expect' => $issue,
            'opencode' => $opencode,
            'action_name' => session('mm_name'),
            'opentimestamp' => time(),
        ]);
        $this->error('人工开奖成功', U('Lottery/index'));
    }

    private function getIssue($lottery_id) {
        $time = time();
        // 赛车
        if ($lottery_id == 1) {
            $fix_time_1 = '2017-07-23 09:07:00';
            $fix_issue_1 = 630226;
            $issue1 = $fix_issue_1 + floor(($time - strtotime($fix_time_1))/86400)*179;
            $issue2 = date('H:i:s') > '09:07:00' ? floor(($time - strtotime(date('Y-m-d ').'09:07:00'))/300) : 178;
            return $issue1+$issue2;
        } 
        // 时时彩
        if ($lottery_id == 2) {
            $issue1 = date('Ymd', $time);
            if (date('H:i:s') < '00:05:00') {
                $issue1 = date('Ymd', $time - 86400);
                $issue2 = 120;
            } elseif (date('H:i:s') < '02:00:00') {
                $issue2 = str_pad(floor(($time - strtotime(date('Y-m-d')))/300), 3, '0', STR_PAD_LEFT);
            } elseif (date('H:i:s') < '10:00:00') {
                $issue2 = '023';
            } elseif (date('H:i:s') < '22:05:00') {
                $issue2 = 24 + floor(($time - strtotime(date('Y-m-d ').'10:00:00'))/600);
                $issue2 = str_pad($issue2, 3, '0', STR_PAD_LEFT);
            } else {
                $issue2 = 96 + floor(($time - strtotime(date('Y-m-d ').'22:00:00'))/300);
            }
            return $issue1.$issue2;
        }
        // 飞艇
        if ($lottery_id == 3) {
            $issue1 = date('Ymd', $time);
            if (date('H:i:s') < '04:04:00') {
                $issue1 = date('Ymd', $time - 86400);
                $issue2 = 131 + floor(($time - strtotime(date('Y-m-d ', $time - 86400).'23:59:00'))/300);
            } elseif (date('H:i:s') < '13:09:00') {
                $issue1 = date('Ymd', $time - 86400);
                $issue2 = 180;
            } else {
                $issue2 = str_pad(floor(($time - strtotime(date('Y-m-d ').'13:04:00'))/300), 3, '0', STR_PAD_LEFT);
            }
            return $issue1.$issue2;
        }
    }
}