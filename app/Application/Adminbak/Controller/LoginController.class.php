<?php
namespace Adminbak\Controller;
use Think\Controller;
class LoginController extends Controller {
	/**
	 * 登录
	*/
    public function login() {
    	if (IS_POST) {
    		$user_name = I('post.user_name');
    		$password = I('post.password');
    		$verify_code = I('post.verify_code');
    		if (empty($user_name)) {
    			$this->error("用户名不能为空", U('Login/login'));
    		}
    		if (empty($password)) {
    			$this->error("密码不能为空", U('Login/login'));
    		}
    		if (empty($verify_code)) {
    			$this->error("验证码不能为空", U('Login/login'));
    		}
    		// 验证码验证
    		$verify = new \Think\Verify();
    		if ($verify->check($verify_code) === false) {
    			$this->error("验证码错误", U('Login/login'));
    		}
    		$userInfo = M('admin_user')->where([
    			'user_name' => $user_name,
    			'password' => md5($password),
    		])->find();
    		if (empty($userInfo)) {
    			$this->error("用户名或密码错误", U('Login/login'));
    		}
            if ($userInfo['status'] == 0) {
                $this->error('您的帐号已被禁止登录，请联系客服', U('Login/login'));
            }
    		session('admin_name', $userInfo['user_name']);
            M('admin_login_log')->add([
                'user_id'=> $userInfo['user_id'],
                'user_name'=> $userInfo['user_name'],
                'ip'=> get_client_ip(),
                'add_time' => date('Y-m-d H:i:s')
            ]);
    		$this->success("登录成功", U('Index/index'));
    	} else {
    		$this->display();
    	}
    }

    /**
	 * 退出登录
	*/
    public function logout() {
    	session('admin_name', null);
    	$this->success("退出成功", U('Login/login'));
    }

    /**
	 * 获取验证码
    */
    public function verifyCode() {
    	$Verify = new \Think\Verify();
    	$Verify->fontSize = 16;
		$Verify->length   = 4;
		$Verify->useNoise = false;
		$Verify->entry();
    }
}