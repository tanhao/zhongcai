<?php
namespace Mmbak\Controller;
use Mmbak\Controller\BaseController;
class AccountController extends BaseController {
    public function index() {
        $set_bank_card = M('set_bank_card');
        $count = $set_bank_card->count();
        $PageObject = new \Think\Page($count,15);
        // 客服列表
        $list = $set_bank_card->order('id desc')->limit($PageObject->firstRow.','.$PageObject->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page_show', $PageObject->show());
        $this->display();
    }

    public function useBank() {
        $id = I('get.id', 0, 'intval');
        $set_bank_card = M('set_bank_card');
        $bankInfo = $set_bank_card->where(['id'=>$id])->find();
        if (empty($bankInfo)) {
            $this->error('银行卡不存在');
        }
        $set_bank_card->where(['is_default'=>1])->save(['is_default'=>0]);
        $set_bank_card->where(['id'=>$id])->save(['is_default'=>1]);
        $this->success('使用成功', U('Account/index'));
    }

    public function addBank() {
        $account_number = I('post.account_number','','htmlspecialchars,trim');
        $bank_name = I('post.bank_name','','htmlspecialchars,trim');
        $real_name = I('post.real_name','','htmlspecialchars,trim');
        $branch_bank = I('post.branch_bank','','htmlspecialchars,trim');
        if (empty($account_number) || empty($bank_name) || empty($real_name) || empty($branch_bank)) {
            $this->error('参数错误', U('Account/index'));
        }
        M('set_bank_card')->add([
            'account_number'=> $account_number,
            'bank_name'=> $bank_name,
            'real_name'=> $real_name,
            'branch_bank'=> $branch_bank,
            'add_time'=> time(),
        ]);
        $this->success('新增成功', U('Account/index'));
    }
}