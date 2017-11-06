<?php
namespace Agent\Controller;
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
    			$this->ajaxOutput("用户名不能为空");
    		}
    		if (empty($password)) {
    			$this->ajaxOutput("密码不能为空");
    		}
    		if (empty($verify_code)) {
    			$this->ajaxOutput("验证码不能为空");
    		}
    		// 验证码验证
    		$verify = new \Think\Verify();
    		if ($verify->check($verify_code) === false) {
    			$this->ajaxOutput("验证码错误");
    		}
    		$userInfo = M('admin_user')->where([
    			'user_name' => $user_name,
    			'password' => md5($password),
    		])->find();
    		if (empty($userInfo)) {
    			$this->ajaxOutput("用户名或密码错误");
    		}
            if ($userInfo['status'] == 0) {
                $this->ajaxOutput('您的帐号已被禁止登录，请联系客服');
            }
    		session('ag_name', $userInfo['user_name']);
            M('admin_login_log')->add([
                'user_id'=> $userInfo['user_id'],
                'user_name'=> $userInfo['user_name'],
                'ip'=> get_client_ip(),
                'add_time' => date('Y-m-d H:i:s')
            ]);
    		$this->ajaxOutput("登录成功", 1, U('Index/index'));
    	} else {
    		$this->display();
    	}
    }

    /**
	 * 退出登录
	*/
    public function logout() {
    	session('ag_name', null);
    	$this->redirect('Login/login');
    }

    /**
	 * 获取验证码
    */
    public function verifyCode() {
        $Verify = new \Think\Verify();
        $Verify->codeSet = '0123456789';
        $Verify->length   = 4;
        $Verify->fontttf   = '4.ttf';//字体
        $Verify->useCurve   = false;//是否画混淆曲线
        $Verify->useNoise = true;//是否添加杂点
        $Verify->entry();
    }

    // 输出
    protected function ajaxOutput($msg, $code=0,  $url='', $data=[]) {
        $result = [
            'code' => $code,
            'msg'  => $msg,
            'url'  => $url,
            'data' => $data,
        ];
        exit(json_encode($result));
    }
}