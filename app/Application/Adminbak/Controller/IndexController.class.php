<?php
namespace Adminbak\Controller;
use Adminbak\Controller\BaseController;
class IndexController extends BaseController {
    public function index(){
    	$create_code = I('get.create_code');
    	$admin_user = M('admin_user');
    	$adminInfo = $admin_user->where(['user_name'=> session('admin_name')])->find();
    	$invite_code =  $adminInfo['invite_code'];
    	if (!empty($create_code) && empty($invite_code)) {
    		$invite_code = $this->getInviteCode();
    		$admin_user->where(['user_name'=> session('admin_name')])->save([
    			'invite_code'=> $invite_code,
    		]);
    	}
    	$this->assign('invite_code', $invite_code);
    	$this->display();
    }

    private function getInviteCode() {
        $invite_code = rand(0, 9) . rand(10000, 99999);
        if (M('admin_user')->where(['invite_code'=> $invite_code])->count()) {
            return $this->getInviteCode();
        }
        return $invite_code;
    }
}