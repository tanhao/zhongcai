<?php
namespace Cs\Controller;
use Think\Controller;
class BaseController extends Controller {

    public function _initialize() {
        // $this->redirect('Login/login');exit;
        $cs_name = session('cs_name');
        if (empty($cs_name)) {
            if (IS_AJAX) {
                $this->ajaxOutput('请登录', 0, U('Login/login'));
            } else {
                echo "<script>window.parent.location.href='".U('Login/login')."'</script>";exit;
            }
        }
        $this->assign('cs_name', $cs_name);
        $csUserInfo = M('cs_user')->where(['user_name'=> $cs_name, 'is_delete'=>0])->find();
        if (empty($csUserInfo)) {
            if (IS_AJAX) {
                $this->ajaxOutput('帐号不存在', 0, U('Login/login'));
            } else {
                $this->redirect('Login/login');
            }
        }
        $this->csUserInfo = $csUserInfo;
        if (!$this->checkAuth()) {
            if (IS_AJAX) {
                $this->ajaxOutput('权限不够，请联系相关管理员');
            } else {
                echo '权限不够，请联系相关管理员';exit;
            }
        }
    }

    // 管理员信息
    protected $csUserInfo = [];

    // 权限列表
    protected $auth = [
        // 管理设置
        101 => ['name'=>'系统设置','list'=>['Manage-system']],
        102 => ['name'=>'充值提现','list'=>['Manage-cash']],
        103 => ['name'=>'系统账号','list'=>['Manage-user','Manage-addUser','Manage-editUser','Manage-delUser','Manage-user']],
        104 => ['name'=>'公告管理','list'=>['Manage-notice','Manage-changeNoticeStatus','Manage-addNotice','Manage-editNotice','Manage-delNotice']],
        105 => ['name'=>'第三方支付','list'=>['Manage-threePay']],
        106 => ['name'=>'银行卡管理','list'=>['Manage-bank','Manage-changeBankStatus','Manage-addBank','Manage-editBank','Manage-delBank']],
        107 => ['name'=>'操作日志','list'=>['Manage-actLog']],
        // 用户管理
        201 => ['name'=>'代理列表','list'=>['User-agent','User-freezeAgent','User-unfreezeAgent','User-addAgent','User-editAgent','User-adminWasteBook','User-agentRank']],
        202 => ['name'=>'会员列表','list'=>['User-user','User-freezeUser','User-unfreezeUser','User-addUser','User-editUser','User-userWasteBook','User-userJiaMinsMoney']], //添加用户加款操作
        203 => ['name'=>'登录日志','list'=>['User-userLog']],
        // 游戏管理
        301 => ['name'=>'房间管理','list'=>['Game-room','Game-onlineUser','Game-editRoom']],
        302 => ['name'=>'赛车开奖','list'=>['Game-pk10','Game-betLog']],
        303 => ['name'=>'时时彩开奖','list'=>['Game-ssc','Game-betLog']],
        304 => ['name'=>'快艇开奖','list'=>['Game-xyft','Game-betLog']],
        305 => ['name'=>'实时注单','list'=>['Game-nowBetDetail']],
        306 => ['name'=>'报表查询','list'=>['Game-reportForm','Game-reportSheet']],
        // 充值提现
        401 => ['name'=>'充值管理','list'=>['Pay-recharge','Pay-syncRecharge','Pay-bankInfo','Pay-lockOperateUser']],
        402 => ['name'=>'提现管理','list'=>['Pay-draw','Pay-changeDrawStatus','Pay-checkDraw']],
    ];

    // 模块列表
    protected $module = [
        ['name'=>'管理设置','controller'=>'Manage','list'=>[101,102,103,104,105,106,107]],
        ['name'=>'用户管理','controller'=>'User','list'=>[201,202,203]],
        ['name'=>'游戏管理','controller'=>'Game','list'=>[301,302,303,304,305,306]],
        ['name'=>'充值提现','controller'=>'Pay','list'=>[401,402]],
    ];

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

    // 判断访问权限
    private function checkAuth() {
        $action = CONTROLLER_NAME.'-'.ACTION_NAME;
        if (CONTROLLER_NAME == 'Index') {
            return true;
        }
        if (empty($this->csUserInfo['auth'])) {
            return false;
        }
        $auth_id = 0;
        foreach ($this->auth as $key => $value) {
            if (in_array($action, $value['list'])) {
                $auth_id = $key;
                break;
            }
        }
        if ($auth_id == 0) {
            return false;
        }
        return in_array($auth_id, explode(',', $this->csUserInfo['auth']));
    }

    // 添加管理员日志
    protected function addAtionLog($content) {
        if (IS_POST) {
            $param = json_encode($_POST, JSON_UNESCAPED_UNICODE);
            M('cs_action_log')->add([
                'user_name'=> $this->csUserInfo['user_name'],
                'controller'=> CONTROLLER_NAME,
                'action'=> ACTION_NAME,
                'param'=> $param,
                'content'=> $content,
                'add_time'=> time(),
            ]);
        }  
    }
}
