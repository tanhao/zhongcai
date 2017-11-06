<?php
namespace Adminbak\Controller;
use Adminbak\Controller\BaseController;
class UserController extends BaseController {
    public function index() {

    }

    public function bank() {
    	if (IS_POST) {
    		$real_name = I('post.real_name');
    		$account_number = I('post.account_number');
    		$bank_name = I('post.bank_name');
    		$branch_bank = I('post.branch_bank');
    		$pay_password = I('post.pay_password');
    		$re_pay_password = I('post.re_pay_password');
    		if (empty($real_name)) {
    			$this->error('开户名不能为空');
    		}
    		if (empty($account_number)) {
    			$this->error('银行账户不能为空');
    		}
            if (!preg_match('/^62\d{17}$/', $account_number)) {
                $this->error('银行账户格式不对');
            }
    		if (empty($bank_name)) {
    			$this->error('开户行不能为空');
    		}
    		if (empty($branch_bank)) {
    			$this->error('开户支行不能为空');
    		}
    		$adminInfo = M('admin_user')->where(['user_name'=> session('admin_name')])->find();
    		if (empty($adminInfo['pay_password']) && empty($pay_password)) {
    			$this->error('第一次录入银行卡信息，资金密码不能为空');
    		}
    		if ($pay_password != $re_pay_password) {
    			$this->error('两次输入的资金密码不一致');
    		}
    		if (!empty($pay_password) && !preg_match('/[a-zA-Z0-9]{6,15}/', $pay_password)) {
    			$this->error('资金密码只能输入6-15位英文字母或数字');
    		}
    		$saveData = [
    			'real_name' => $real_name,
    			'account_number' => $account_number,
    			'bank_name' => $bank_name,
    			'branch_bank' => $branch_bank,
    		];
    		if (!empty($pay_password)) {
    			$saveData['pay_password'] = md5($pay_password);
    		}
    		M('admin_user')->where(['user_id'=> $adminInfo['user_id']])->save($saveData);
            $this->redirect('Draw/index');
    	} else {
    		$adminInfo = M('admin_user')->where(['user_name'=> session('admin_name')])->find();
	    	$this->assign('adminInfo', $adminInfo);
	    	$this->display();
    	}
    }

    public function log() {
        $count = M('admin_login_log')->where(['user_name'=> session('admin_name'), 'type'=>1])->count();
        $PageObject = new \Think\Page($count,15);
        $list = M('admin_login_log')->where(['user_name'=> session('admin_name'), 'type'=>1])->order('id desc')->limit($PageObject->firstRow.','.$PageObject->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page_show', $PageObject->show());
        $this->display();
    }

    public function message() {
        $count = M('notice')->count();
        $PageObject = new \Think\Page($count,15);
        $list = M('notice')->order('id desc')->limit($PageObject->firstRow.','.$PageObject->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page_show', $PageObject->show());
        $this->display();
    }

    public function password() {
        if (IS_POST) {
            $password = I('post.password', '', 'trim');
            if (empty($password)) {
                $this->error('密码不能为空！');
            }
            M('admin_user')->where(['user_name'=> session('admin_name')])->save([
                'password' => md5($password),
            ]);
            $this->success('保存成功', U('Index/index'));
        } else {
            // $level = M('admin_user')->where(['user_name'=> session('admin_name')])->getField('level');
            // $level_name = '';
            // switch ($level) {
            //     case '1':
            //         $level_name = '总代理';
            //         break;
            //     case '2':
            //         $level_name = '分公司';
            //         break;
            //     case '3':
            //         $level_name = '股东';
            //         break;
            //     case '4':
            //         $level_name = '总代理';
            //         break;
            //     case '5':
            //         $level_name = '代理';
            //         break;
            // }
            // $this->assign('level_name', $level_name);
            $this->display();
        }
    }
}