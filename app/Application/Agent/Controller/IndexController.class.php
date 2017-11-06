<?php
namespace Agent\Controller;
class IndexController extends BaseController {
    public function index(){
    	// 跑马灯
    	$content = M('notice')->where(['status'=>1])->getField('content');
    	// 我的财富
    	$balance = $this->agUserInfo['balance'];
    	// 下属代理
    	$agent_count = M('admin_user')->where(['pid'=>$this->agUserInfo['user_id']])->count();
    	// 下属玩家
    	$user_count = M('user')->where(['invite_code'=>$this->agUserInfo['invite_code']])->count();
    	// 今日下注
    	$user_ids = M('user')->where(['invite_code'=>$this->agUserInfo['invite_code']])->getField('user_id', true);
    	if (!empty($user_ids)) {
    		$bet_balance = M('bet_log')->where(['user_id'=>['in',$user_ids],'is_host'=>0,'add_time'=>['egt',strtotime(date('Y-m-d'))]])->sum('bet_balance');
    		$bet_balance = !empty($bet_balance) ? $bet_balance : '0.00';
    	}
    	// 收益
		$profit_balance = M('admin_income')->where(['admin_id'=>$this->agUserInfo['user_id'],'add_time'=>['egt',strtotime(date('Y-m-d'))]])->sum('commission');
		$profit_balance = !empty($profit_balance) ? $profit_balance : '0.00';
    	
    	$this->assign('content',$content);
    	$this->assign('balance',$balance);
    	$this->assign('agent_count',$agent_count);
    	$this->assign('user_count',$user_count);
    	$this->assign('bet_balance',$bet_balance);
    	$this->assign('profit_balance',$profit_balance);
        $this->display();
    }

    public function generalize(){
    	$this->display();
    }

    public function qrcode() {
        /*
        步骤：
            1.分别创建大小图画布并获取它们的宽高
            2.添加文字水印
            3.执行图片水印处理
            4.输出
            5.销毁画布
         */
        //1.分别创建大小图画布并获取它们的宽高
        $code = $this->agUserInfo['invite_code'];
        $big = imagecreatefromjpeg('./Static/Public/Agent/img/2bg.jpg');
        $bx = imagesx($big);
        $by = imagesy($big);

        $small = imagecreatefrompng('./Static/Public/Agent/img/kdp9.png');
        $sx = imagesx($small);
        $sy = imagesy($small);


        //2.添加水印文字
        $blue = imagecolorallocate($big,100,100,100);
        imagettftext($big,30,0,10,1050,$blue,'./Static/Public/Agent/fonts/2.ttf','扫描二维码下载广彩APP,全新玩法，官方开奖,刺激好玩!');
        $blue = imagecolorallocate($big,100,100,100);
        imagettftext($big,70,0,70,1160,$blue,'./Static/Public/Agent/fonts/2.ttf','注册安全码:'.$code);

        //3.执行图片水印处理
        imagecopymerge($big,$small,$bx-$sx,0,0,0,$sx,$sy,100);

        //4.输出到浏览器
        header('content-type: image/jpeg');
        imagejpeg($big);

        //5.销毁画布
        imagedestroy($big);
        imagedestroy($small);
    }
}