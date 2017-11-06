<?php
namespace Adminbak\Controller;
use Adminbak\Controller\BaseController;
class PlayersController extends BaseController {
    public function index() {
    	$nickname = I('get.nickname');
    	$admin_user = M('admin_user');
    	$user = M('user');
        $invite_code = $admin_user->where(['user_name'=> session('admin_name')])->getField('invite_code');
        $where = ['invite_code'=> $invite_code];
        if (!empty($nickname)) {
        	$where['nickname'] = $nickname;
        }
        $count = $user->where($where)->count();
        $PageObject = new \Think\Page($count,15);
        $userList = $user->where($where)->order('user_id asc')->limit($PageObject->firstRow.','.$PageObject->listRows)->field("user_id,nickname,balance")->select();
        $this->assign('nickname', $nickname);
        $this->assign('userList', $userList);
    	$this->assign('page_show', $PageObject->show());
    	$this->display();
    }
}