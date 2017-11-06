<?php
namespace Adminbak\Controller;
use Think\Controller;
class BaseController extends Controller {
    public function _initialize() {
    	$admin_name = session('admin_name');
    	if (empty($admin_name)) {
    		$this->redirect('Login/login');
    	}
    	$this->assign('admin_name', $admin_name);
    	$this->assign('year', date('Y'));
    	$this->assign('monthday', date('m月d日'));
    	$this->assign('controller_name', CONTROLLER_NAME);
        // 必须生成安全码
        $adminInfo = M('admin_user')->where(['user_name'=> session('admin_name')])->field('invite_code,status')->find();
        if ($adminInfo['status'] == 0) {
            $this->error('您的帐号已被冻结，请联系客服', U('Login/login'));
        }
        if (CONTROLLER_NAME != 'Index' || ACTION_NAME != 'index') {
            if (empty($adminInfo['invite_code'])) {
                $this->error('请生成安全码');
            }
        }
    }
}