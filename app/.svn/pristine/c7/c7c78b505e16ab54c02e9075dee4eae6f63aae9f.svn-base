<?php
namespace Api\Controller;
use Think\Controller;
use Lib\Enum\CodeEnum;
use Lib\Enum\CacheEnum;

class BaseController extends Controller {
    // 用户信息
    protected $userInfo = null;

    public function _initialize() {
    	$token = I('get.token');
    	$client_id = I('get.client_id');
    	if (empty($token) || empty($client_id)) {
    		$this->ajaxReturn(output(CodeEnum::PARAM_ERROR));
    	}
        // 判断是否停服
        if (getConfig('system_maintenance') == '1') {
            $this->ajaxReturn(output(CodeEnum::SERVER_IS_STOP, [
                'announcement'=> getConfig('announcement'),
            ]));
        }
    	// 验证token是否可用
    	$tokenInfo = M('user_token')->where(['token'=> $token])->find();
    	if (empty($tokenInfo)) {
    		$this->ajaxReturn(output(CodeEnum::TOKEN_INVALID));
    	}
        C('TOKEN', $token);
        C('USER_ID', $tokenInfo['user_id']);
        C('IS_TEMP', $tokenInfo['is_temp']);
        if ($tokenInfo['is_temp'] == 0) {
            $userInfo = M('user')->where(['user_id'=> $tokenInfo['user_id']])->find();
            // 冻结用户不能访问API
            if ($userInfo['status'] == 0 && in_array(CONTROLLER_NAME.'-'.ACTION_NAME, $this->freezeApi)) {
                $this->ajaxReturn(output(CodeEnum::FREEZE_USER));
            }
            $this->userInfo = $userInfo;
            // 登录或者注册时TOKEN已经有对应的用户必须改为临时用户
            if (in_array(ACTION_NAME, ['login', 'register'])) {
                $temp_user_id = getTempUserId();
                M('user_token')->where(['token'=> $token])->save([
                    'user_id'=> $temp_user_id, 
                    'is_temp'=> 1, 
                ]);
            }
        }
        // 必须登录的API
        if (in_array(CONTROLLER_NAME.'-'.ACTION_NAME, $this->needLogin) && C('IS_TEMP') == 1) {
            $this->ajaxReturn(output(CodeEnum::PLEASE_LOGIN));
        }
    	// 更新当前room_id
        $updateData = [
            'client_id' => I('get.client_id'),
            'online'=> 1,
            'add_time'=> time(),
        ];
        if (CONTROLLER_NAME != "Room" && !in_array(CONTROLLER_NAME.'-'.ACTION_NAME, $this->notNeedoutRoom)) {
            $updateData['room_id'] = 0;
        }
        M('user_token')->where(['token'=> $token])->save($updateData);
    }

    // 需要登录的接口
    private $needLogin = [
        'User-editNickname',
        'User-addBankCard',
        'User-getBankList',
        'User-applyCashInfo',
        'User-applyCashCommit',
        'User-editPayPassword',
        'User-setPayPassword',
        'User-recharge',
        'User-delBankCard',
        'User-getRechargeInfo',
        'User-editBankCard',
        'User-getSystemMessage',
        'Pay-onLineRecharge',
        'Pay-getPayTypeList',
    ];

    // 不需要退出房间的接口
    private $notNeedoutRoom = [
        'User-editNickname',
        'User-getRechargeInfo',
        'User-recharge',
        'User-applyCashCommit',
        'User-getUserInfo',
        'Index-getAnnouncementUrl',
        'Pay-getPayTypeList',
        'Pay-onlineRecharge',
    ];

    // 冻结用户不允许访问的接口
    private $freezeApi = [
        'User-applyCashCommit',
        'Room-beHost',
        'Room-onBet',
        'User-getBankList',
        'Pay-getPayTypeList',
    ];

    protected function addUserLog($title, $content) {
        if (C('IS_TEMP') == 0) {
            M('user_log')->add([
                'user_id'=> $this->userInfo['user_id'],
                'user_name'=> $this->userInfo['user_name'],
                'title'=> $title,
                'content'=> $content,
                'add_time'=> time(),
            ]);
        }  
    }
}