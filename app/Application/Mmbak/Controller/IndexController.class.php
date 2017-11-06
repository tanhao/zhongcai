<?php
namespace Mmbak\Controller;
use Mmbak\Controller\BaseController;
class IndexController extends BaseController {
    public function index(){
        $nickname = I('get.nickname');
        $recharge = M('recharge');
        $where = !empty($nickname) ? ['u.nickname'=> $nickname] : [];
        $count = $recharge->join("as r left join zc_user as u on r.user_id=u.user_id")->where($where)->count();
        $PageObject = new \Think\Page($count,15);
        $list = $recharge->join("as r left join zc_user as u on r.user_id=u.user_id")
            ->order('r.add_time desc')
            ->limit($PageObject->firstRow.','.$PageObject->listRows)
            ->field("u.user_name,u.nickname,r.recharge_cash,r.real_cash,r.account_number,r.bank_name,r.real_name,r.add_time,r.mm_name,r.type,r.sync,r.id,r.order_sn")
            ->where($where)
            ->select();
        $this->assign('nickname', $nickname);
        $this->assign('list', $list);
        $this->assign('page_show', $PageObject->show());
    	$this->display();
    }

    public function recharge() {
        $real_cash = I('post.real_cash', 0 , 'floatval');
        $id = I('post.id', 0, 'intval');
        if ($real_cash <= 0 || $id < 1) {
            $this->error('参数错误');
        }
        $real_cash = sprintf('%.2f', $real_cash);
        $recharge = M('recharge');
        $user = M('user');
        // 获取用户充值信息
        $rechargeInfo = $recharge->where(['id'=> $id,'sync'=> 0,'type'=>1])->find();
        if (empty($rechargeInfo)) {
            $this->error('用户的充值信息不存在');
        }
        // 获取用户余额
        $user_balance = $user->where(['user_id'=> $rechargeInfo['user_id']])->getField('balance');
        if (empty($user_balance)) {
            $this->error('充值用户不存在');
        }
        // 开户事务
        M()->startTrans();
        // 更新充值记录
        $ret_1 = $recharge->where(['id'=> $id])->save([
            'real_cash'=> $real_cash,
            'sync'     => 1,
            'mm_name'  => session('mm_name'),
        ]);
        // 更新用户余额
        $balance = bcadd($user_balance, $real_cash, 2);
        $ret_2 = $user->where(['user_id'=>$rechargeInfo['user_id']])->save(['balance'=> $balance]);
        // 流水LOG
        $ret_3 = M('user_waste_book')->add([
            'user_id'=> $rechargeInfo['user_id'],
            'before_balance'=> $user_balance,
            'after_balance'=> $balance,
            'change_balance'=> $real_cash,
            'type'=> 4,
            'add_time'=> time(),
        ]);
        if ($ret_1 && $ret_2 && $ret_3) {
            M()->commit();
            // 推送余额给用户
            $client_id = M('user_token')->where(['user_id'=> $rechargeInfo['user_id'], 'is_temp'=> 0, 'online'=> 1])->getField('client_id');
            if (!empty($client_id)) {
                sendToClient($client_id, \Lib\Enum\CodeEnum::LEFT_BALANCE, ['balance'=> $balance]);
            }
            $this->success('充值成功', U('Index/index'));
        } else {
            M()->rollback();
            $this->error('充值失败');
        } 
    }
}