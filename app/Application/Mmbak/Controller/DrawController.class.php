<?php
namespace Mmbak\Controller;
use Mmbak\Controller\BaseController;
class DrawController extends BaseController {
    public function userList(){
        if (IS_POST) {
            $id = I('post.id',0,'intval');
            $apply_cash = M('apply_cash');
            $info = $apply_cash->where(['id'=> $id])->find();
            if (empty($info)) {
                $this->error('打款信息不存在');
            }
            if ($info['sync'] != 0) {
                $this->error('请勿重复打款');
            }
            $apply_cash->where(['id'=> $id])->save([
                'sync' => 1,
                'mm_name' => session('mm_name'),
            ]);
            $this->success('打款成功', U('Draw/userList'));
        } else {
            $nickname = I('get.nickname');
            $apply_cash = M('apply_cash');
            $where = [];
            if (!empty($nickname)) {
                $where = ['u.nickname'=> $nickname];
            }
            $count = $apply_cash->join("as a inner join zc_user as u on a.user_id=u.user_id")->join("inner join zc_bank_card as b on a.bank_id=b.bank_id and a.user_id=b.user_id")->where($where)->count();
            $PageObject = new \Think\Page($count,15);
            $list = $apply_cash->join("as a inner join zc_user as u on a.user_id=u.user_id")
                ->join("inner join zc_bank_card as b on a.bank_id=b.bank_id and a.user_id=b.user_id")
                ->where($where)
                ->order('a.add_time desc')
                ->limit($PageObject->firstRow.','.$PageObject->listRows)
                ->field("u.user_name,u.nickname,a.real_cash,a.sync,a.add_time,a.id,a.mm_name,b.account_number,b.bank_name,b.real_name,b.branch_bank")
                ->select();
            $this->assign('list', $list);
            $this->assign('page_show', $PageObject->show());
            $this->display();
        }
    }

    public function adminList(){
        if (IS_POST) {
            $id = I('post.id',0,'intval');
            $admin_apply_cash = M('admin_apply_cash');
            $info = $admin_apply_cash->where(['id'=> $id])->find();
            if (empty($info)) {
                $this->error('打款信息不存在');
            }
            if ($info['sync'] != 0) {
                $this->error('请勿重复打款');
            }
            $admin_apply_cash->where(['id'=> $id])->save([
                'sync' => 1,
                'mm_name' => session('mm_name'),
            ]);
            $this->success('打款成功', U('Draw/adminList'));
        } else {
            $admin_apply_cash = M('admin_apply_cash');
            $count = $admin_apply_cash->join("as a inner join zc_admin_user as u on a.user_id=u.user_id")->count();
            $PageObject = new \Think\Page($count,15);
            $list = $admin_apply_cash->join("as a inner join zc_admin_user as u on a.user_id=u.user_id")
                ->order('a.add_time desc')
                ->limit($PageObject->firstRow.','.$PageObject->listRows)
                ->field("u.user_name,u.rate,a.apply_cash,a.real_cash,a.sync,a.add_time,a.id,a.mm_name,a.account_number,a.bank_name,a.real_name,a.branch_bank")
                ->select();
            $this->assign('list', $list);
            $this->assign('page_show', $PageObject->show());
            $this->display();
        }
    }
}