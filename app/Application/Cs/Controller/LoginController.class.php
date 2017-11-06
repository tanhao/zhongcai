<?php
namespace Cs\Controller;
use Think\Controller;
class LoginController extends Controller {
    /**
	 * 登录
	*/
    public function login() {
    	if (IS_POST) {
    		$user_name = I('post.username');
    		$password = I('post.passwd');
    		$verify_code = I('post.captcha_code');
    		if (empty($user_name)) {
    			$this->ajaxOutput('用户名不能为空');
    		}
    		if (empty($password)) {
                $this->ajaxOutput('密码不能为空');
    		}
    		if (empty($verify_code)) {
                $this->ajaxOutput('验证码不能为空');
    		}
    		// 验证码验证
    		$verify = new \Think\Verify();
    		if ($verify->check($verify_code) === false) {
                $this->ajaxOutput('验证码错误');
    		}
    		$userInfo = M('cs_user')->where([
                'user_name' => $user_name,
                'password'  => md5($password),
    			'is_delete' => 0,
    		])->find();
            if (empty($userInfo)) {
                $this->ajaxOutput('用户名或密码错误');
            }
    		M('admin_login_log')->add([
                'user_id'   => $userInfo['user_id'],
                'user_name' => $userInfo['user_name'],
                'type'      => 2,
                'ip'        => get_client_ip(),
                'add_time'  => date('Y-m-d H:i:s')
            ]);
            session('cs_name', $userInfo['user_name']);
            $this->ajaxOutput('登录成功', 1, U('Index/index'));
    	} else {
    		$this->display();
    	}
    }

    /**
	 * 退出登录
	*/
    public function logout() {
        session('cs_name', null);
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


    private function ajaxOutput($msg, $code=0,  $url='', $data=[]) {
        $result = [
            'code' => $code,
            'msg'  => $msg,
            'url'  => $url,
            'data' => $data,
        ];
        exit(json_encode($result));
    }
}