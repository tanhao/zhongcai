<?php
namespace Api\Controller;
use Lib\Enum\CodeEnum;
use Lib\Enum\CacheEnum;

class UserController extends BaseController {

    /**
     * @desc 注册
     * @param user_name 手机或者邮箱
     * @param verify_code  验证码
     * @param password  密码
     * @param repassword  确认密码
     * @param invite_code  邀请码
     * @param type  phone-手机号注册 email-邮箱注册 name-用户名注册
     * @param client_id 客户端ID
     * @param token     用户TOKEN
     * @return int
     */
    public function register() {
        $user_name = I('get.user_name', '', 'htmlspecialchars,trim');
        $verify_code = I('get.verify_code', '', 'htmlspecialchars,trim');
        $password = I('get.password', '', 'htmlspecialchars,trim');
        $repassword = I('get.repassword', '', 'htmlspecialchars,trim');
        $invite_code = I('get.invite_code', '', 'htmlspecialchars,trim');
        $type = I('get.type', '', 'trim');
        if (empty($user_name) || (empty($verify_code) && in_array($type, ['phone', 'email'])) || empty($password) || empty($repassword) || empty($invite_code) || !in_array($type, ['phone', 'email', 'name'])) {
            $this->ajaxReturn(output(CodeEnum::PARAM_ERROR));
        }
        if ($type == "phone" && !isPhone($user_name)) {
            $this->ajaxReturn(output(CodeEnum::IS_NOT_PHONE));
        }
        if ($type == "email" && !isEmail($user_name)) {
            $this->ajaxReturn(output(CodeEnum::IS_NOT_EMIAL));
        }
        if ($password != $repassword) {
            $this->ajaxReturn(output(CodeEnum::PASSWORD_DIFFERENT));
        }
        if (!preg_match('/^[\da-zA-Z]{6,18}$/', $password)) {
            $this->ajaxReturn(output(CodeEnum::PASSWORD_FORMAT_ERROR));
        }
        // 验证邀请码
        $count = M('admin_user')->where(['invite_code'=> $invite_code])->count();
        if (!$count) {
            $this->ajaxReturn(output(CodeEnum::INVITE_CODE_NOT_EXIST));
        }
        // 验证验证码
        if ($type != 'name') {
            $verify_code_cache = redisCache()->get(CacheEnum::VERIFY_CODE . $user_name);
            if (empty($verify_code_cache) || $verify_code_cache != $verify_code) {
                $this->ajaxReturn(output(CodeEnum::VERIFY_CODE_ERROR));
            }
        } 
        // 验证账号是否已被注册
        if (M('user')->where(['user_name'=> $user_name])->count()) {
            $this->ajaxReturn(output(CodeEnum::USERNAME_IS_REGISTERED));
        }
        $user_id = M('user')->add([
            'user_name'=> $user_name,
            'nickname'=> "",
            'phone'=> $type == "phone" ? $user_name : "",
            'email'=> $type == "email" ? $user_name : "",
            'password'=> md5($password),
            'pay_password'=> "",
            'balance'=> 0,
            'status'=> 1,
            'invite_code'=> $invite_code,
            'add_time'=> time(),
        ]);
        if (empty($user_id)) {
            $this->ajaxReturn(output(CodeEnum::REGISTER_FAILED));
        }
        $ret = M('user_token')->where(["token"=> C('TOKEN')])->save([
            'user_id'  => $user_id,
            'is_temp'  => 0,
        ]);
        $this->ajaxReturn(output(CodeEnum::SUCCESS));
    }

    /**
     * @desc 登录
     * @param user_name 手机或者邮箱或者用户名
     * @param password  密码
     * @param client_id 客户端ID
     * @param token     用户TOKEN
     * @return int
     */
    public function login() {
        $user_name = I('get.user_name', '', 'trim');
        $password = I('get.password', '', 'trim');
        $client_id = I('get.client_id');
        $token = I('get.token');
        if (empty($user_name) || empty($password)) {
            $this->ajaxReturn(output(CodeEnum::PARAM_ERROR));
        }
        // 判断用户是否存在，帐号密码是否正确
        $userInfo = M('user')->where(['user_name'=> $user_name])->field('user_id,user_name,password,status')->find();
        if (empty($userInfo) || $userInfo['password'] != md5($password)) {
            $this->ajaxReturn(output(CodeEnum::USERNAME_OR_PASSWORD_ERROR));
        }
        // 判断用户是否冻结
        // if ($userInfo['status'] == 0) {
        //     $this->ajaxReturn(output(CodeEnum::FREEZE_USER));
        // }
        // 删除临时用户
        M('user_token')->where(['token'=> $token])->delete();
        // 您的账号已在其他设备登录
        $tokenInfo = M('user_token')->where(['user_id'=> $userInfo['user_id'], 'is_temp'=> 0])->find();
        if (!empty($tokenInfo)) {
            if ($tokenInfo['online'] && $tokenInfo['client_id'] != $client_id) {
                sendToClient($tokenInfo['client_id'], CodeEnum::REMOTE_LOGIN, $data);
            }
            M('user_token')->where(['user_id'=> $userInfo['user_id'], 'is_temp'=> 0])->delete();
        }
        // 登录
        M('user_token')->add([
            'user_id'  => $userInfo['user_id'],
            'is_temp'  => 0,
            'token'    => $token,
            'client_id'=> $client_id,
            'online'  => 1,
            'add_time'   => time(),
        ]);
        // 登录日志
        M('login_log')->add([
            'user_id'  => $userInfo['user_id'],
            'ip' => get_client_ip(),
            'add_time' => time(),
        ]);
        // 用户日志
        $content = empty($userInfo['phone']) ? "帐号登录" : "手机登录";
        $this->addUserLog('登录', "{$pay_name}充值{$recharge_cash}");
        $this->ajaxReturn(output(CodeEnum::SUCCESS));
    }

    /**
     * @desc 发送验证码到手机或邮箱
     * @param user_name 手机或者邮箱
     * @param type  phone-手机号注册 email-邮箱注册 all-后端来匹配
     * @param client_id 客户端ID
     * @param token     用户TOKEN
     * @return int
     */
    public function getVerifyCode() {
        $user_name = I('get.user_name', '', 'trim');
        $type = I('get.type', '', 'trim');
        if (empty($user_name) || !in_array($type, ['phone', 'email', 'all'])) {
            $this->ajaxReturn(output(CodeEnum::PARAM_ERROR));
        }
        // 验证手机或邮箱格式
        if ($type == "phone") {
            if (!isPhone($user_name)) $this->ajaxReturn(output(CodeEnum::IS_NOT_PHONE));
        } elseif ($type == "email") {
            if (!isEmail($user_name)) $this->ajaxReturn(output(CodeEnum::IS_NOT_EMIAL));
        } else {
            if (!isEmail($user_name) && !isPhone($user_name)) $this->ajaxReturn(output(CodeEnum::USER_NAME_ERROR));
        }
        $verify_code = rand(100000, 999999);
        $project_name = C('PROJECT_NAME');
        $subject = "{$project_name}验证码";
        $body = "尊贵的客户，您的验证码为{$verify_code}，请尽快验证。温馨提示：请妥善保管，不要随意泄露给他人。【{$project_name}】";
        if (isPhone($user_name)) {
            // 发验证码短信到手机
            $ret = sendSms($user_name, array('code'=> $verify_code));
            if (!$ret) {
                $this->ajaxReturn(output(CodeEnum::SENDSMS_FAILED));
            }
        } else {
            // 发验证码邮件到邮箱
            $ret = sendMail($user_name, $subject, $body);
            if (!$ret) {
                $this->ajaxReturn(output(CodeEnum::SENDMAIL_FAILED));
            }
        }
        // 记录验证码到缓存
        redisCache()->set(CacheEnum::VERIFY_CODE . $user_name, $verify_code, 60 * 10);
        $this->ajaxReturn(output(CodeEnum::SUCCESS));
    }

    /**
     * @desc 忘记密码（手机号/邮箱）
     * @param user_name 手机或者邮箱
     * @param verify_code 验证码
     * @param password 密码
     * @param repassword 确认密码
     * @param client_id 客户端ID
     * @param token     用户TOKEN
     * @return int
     */
    public function forgetPassword() {
        $user_name = I('get.user_name', '', 'trim');
        $verify_code = I('get.verify_code', '', 'trim');
        $password = I('get.password', '', 'trim');
        $repassword = I('get.repassword', '', 'trim');
        if (empty($user_name) || empty($verify_code) || empty($password) || empty($repassword)) {
            $this->ajaxReturn(output(CodeEnum::PARAM_ERROR));
        }
        if ($password != $repassword) {
            $this->ajaxReturn(output(CodeEnum::PASSWORD_DIFFERENT));
        }
        if (!preg_match('/^[\da-zA-Z]{8,18}$/', $password)) {
            $this->ajaxReturn(output(CodeEnum::PASSWORD_FORMAT_ERROR));
        }
        // 验证用户是否存在
        $userInfo = M('user')->where(['user_name'=> $user_name])->field('user_id')->find();
        if (empty($userInfo)) {
            $this->ajaxReturn(output(CodeEnum::USER_NAME_ERROR));
        }
        // 验证验证码
        $verify_code_cache = redisCache()->get(CacheEnum::VERIFY_CODE . $user_name);
        if (empty($verify_code_cache) || $verify_code_cache != $verify_code) {
            $this->ajaxReturn(output(CodeEnum::VERIFY_CODE_ERROR));
        }
        M('user')->where(['user_name'=> $user_name])->save(['password' => md5($password)]);
        $this->ajaxReturn(output(CodeEnum::SUCCESS));
    }

    /**
     * @desc 忘记密码（用户名）
     * @param user_name 手机名
     * @param account_number 银行主账号
     * @param pay_password 资金密码
     * @param password 新密码
     * @param repassword 确认密码
     * @param client_id 客户端ID
     * @param token     用户TOKEN
     * @return int
     */
    public function forgetPasswordByBank() {
        $user_name = I('get.user_name', '', 'trim');
        $account_number = I('get.account_number', '', 'trim');
        $pay_password = I('get.pay_password', '', 'trim');
        $password = I('get.password', '', 'trim');
        $repassword = I('get.repassword', '', 'trim');
        if (empty($user_name) || empty($account_number) || empty($pay_password) || empty($password) || empty($repassword)) {
            $this->ajaxReturn(output(CodeEnum::PARAM_ERROR));
        }
        if ($password != $repassword) {
            $this->ajaxReturn(output(CodeEnum::PASSWORD_DIFFERENT));
        }
        if (!preg_match('/^[\da-zA-Z]{8,18}$/', $password)) {
            $this->ajaxReturn(output(CodeEnum::PASSWORD_FORMAT_ERROR));
        }
        // 验证用户是否存在
        $userInfo = M('user')->where(['user_name'=> $user_name])->find();
        if (empty($userInfo)) {
            $this->ajaxReturn(output(CodeEnum::USER_NAME_ERROR));
        }
        // 验证资金密码
        if (empty($userInfo['pay_password'])) {
            $this->ajaxReturn(output(CodeEnum::PAY_PASSWORD_ISNOT_SET));
        }
        if ($userInfo['pay_password'] != md5($pay_password)) {
            $this->ajaxReturn(output(CodeEnum::PAY_PASSWORD_ERROR));
        }
        // 验证银行卡
        $bankInfo = M('bank_card')->where(['user_id'=> $userInfo['user_id'], 'is_delete'=> 0, 'is_default'=>1])->find();
        if (empty($bankInfo)) {
            $this->ajaxReturn(output(CodeEnum::BANK_CARD_ISNOT_SET));
        }
        if ($bankInfo['account_number'] != $account_number) {
            $this->ajaxReturn(output(CodeEnum::ACCOUNT_NUMBER_ERROR));
        }
        M('user')->where(['user_name'=> $user_name])->save(['password' => md5($password)]);
        $this->ajaxReturn(output(CodeEnum::SUCCESS));
    }

    /**
     * @desc 修改昵称
     * @param nickname  昵称
     * @param client_id 客户端ID
     * @param token     用户TOKEN
     * @return int
     */
    public function editNickname() {
        $nickname = I('get.nickname');
        if (empty($nickname)) {
            $this->ajaxReturn(output(CodeEnum::PARAM_ERROR));
        }
        if (!preg_match("/^[a-zA-Z0-9\x{4e00}-\x{9fa5}]+$/u", $nickname)) {
            $this->ajaxReturn(output(CodeEnum::NICKNAME_FORMAT_ERROR));
        }
        // 判断昵称是否已经有人在使用
        if (M('user')->where(['nickname'=> $nickname, 'user_id'=>['neq', C('USER_ID')]])->count()) {
            $this->ajaxReturn(output(CodeEnum::NICKNAME_EXIST));
        }
        M('user')->where(['user_id'=> C('USER_ID')])->save(['nickname'=> $nickname]);
        $this->addUserLog('修改昵称', "修改昵称：{$nickname}");
        $this->ajaxReturn(output(CodeEnum::SUCCESS));
    }

    /**
     * @desc 退出登录
     * @param client_id 客户端ID
     * @param token     用户TOKEN
     * @return int
     */
    public function logout() {
        $temp_user_id = getTempUserId();
        $ret = M('user_token')->where(['token'=> C('TOKEN')])->save([
            'user_id'=> $temp_user_id, 
            'is_temp'=> 1, 
        ]);
        $this->addUserLog('退出登录', "退出登录");
        $this->ajaxReturn(output(CodeEnum::SUCCESS));
    }

    /**
     * @desc 银行卡列表
     * @param client_id 客户端ID
     * @param token     用户TOKEN
     * @return int
     */
    public function getBankList() {
        $bankList = M('bank_card')->where(['user_id'=> C('USER_ID'), 'is_delete'=> 0])->order('bank_id asc')->select();
        $this->ajaxReturn(output(CodeEnum::SUCCESS, ['bankList'=> $bankList]));
    }

    /**
     * @desc 获取银行卡信息
     * @param bank_id 银行卡ID
     * @param client_id 客户端ID
     * @param token     用户TOKEN
     * @return int
     */
    public function getBankInfo() {
        $bank_id = I('get.bank_id', '', 'intval');
        if (empty($bank_id)) {
            $this->ajaxReturn(output(CodeEnum::PARAM_ERROR));
        }
        $bankInfo = M('bank_card')->where(['bank_id'=> $bank_id, 'is_delete'=> 0, 'user_id'=> C('USER_ID')])->field('bank_id,account_number,bank_name,real_name,branch_bank,is_default')->find();
        if (empty($bankInfo)) {
            $this->ajaxReturn(output(CodeEnum::BANK_NOT_EXIST));
        }
        $this->ajaxReturn(output(CodeEnum::SUCCESS, $bankInfo));
    }

    /**
     * @desc 添加银行卡
     * @param account_number 银行卡号
     * @param bank_name 银行名称
     * @param real_name 真实姓名
     * @param branch_bank 开户支行
     * @param client_id 客户端ID
     * @param token     用户TOKEN
     * @return int
     */
    public function addBankCard() {
        $account_number = I('get.account_number','', 'trim');
        $bank_name = I('get.bank_name','', 'trim');
        $real_name = I('get.real_name','', 'trim');
        $branch_bank = I('get.branch_bank','', 'trim');
        if (empty($account_number) || !is_numeric($account_number) || empty($bank_name) || empty($real_name) || empty($branch_bank)) {
            $this->ajaxReturn(output(CodeEnum::PARAM_ERROR));
        }
        $bank_card = M('bank_card');
        // 不能添加相同卡号的银行卡
        if ($bank_card->where(['account_number'=> $account_number, 'is_delete'=> 0, 'user_id'=> C('USER_ID')])->count()) {
            $this->ajaxReturn(output(CodeEnum::NOT_ADD_SAME_BLANK));
        }
        // 添加的卡号真名要与主卡一致
        $_real_name = $bank_card->where(['user_id'=> C('USER_ID'), 'is_default'=> 1, 'is_delete'=> 0])->getField('real_name');
        if (!empty($_real_name) && $_real_name != $real_name) {
            $this->ajaxReturn(output(CodeEnum::ONLY_ADD_SAME_USER_CARD));
        }
        M('bank_card')->add([
            'user_id'=> C('USER_ID'),
            'account_number'=> $account_number,
            'bank_name'=> $bank_name,
            'real_name'=> $real_name,
            'branch_bank'=> $branch_bank,
            'is_default'=> empty($_real_name) ? 1 : 0,
            'is_delete'=> 0,
            'add_time'=> time(),
        ]);
        $this->addUserLog('添加银行卡', "添加银行卡");
        $this->ajaxReturn(output(CodeEnum::SUCCESS));
    }

    /**
     * @desc 编辑银行卡
     * @param bank_id 银行卡ID
     * @param account_number 银行卡号
     * @param bank_name 银行名称
     * @param branch_bank 开户支行
     * @param client_id 客户端ID
     * @param token     用户TOKEN
     * @return int
     */
    public function editBankCard() {
        $bank_id = I('get.bank_id', '', 'intval');
        $account_number = I('get.account_number','', 'trim');
        $bank_name = I('get.bank_name','', 'trim');
        $branch_bank = I('get.branch_bank','', 'trim');
        if (empty($bank_id) || empty($account_number) || !is_numeric($account_number) || empty($bank_name) || empty($branch_bank)) {
            $this->ajaxReturn(output(CodeEnum::PARAM_ERROR));
        }
        $bank_card = M('bank_card');
        // 判断银行卡是否存在
        $bankInfo = $bank_card->where(['bank_id'=> $bank_id, 'is_delete'=> 0, 'user_id'=> C('USER_ID')])->find();
        if (empty($bankInfo)) {
            $this->ajaxReturn(output(CodeEnum::BANK_NOT_EXIST));
        }
        // 不能编辑主号
        if ($bankInfo['is_default'] == 1) {
            $this->ajaxReturn(output(CodeEnum::DEFAULT_CARD_CANNOT_EDIT));
        }
        // 判断编辑的卡号是否有同名
        if ($bank_card->where(['account_number'=> $account_number, 'is_delete'=> 0, 'user_id'=> C('USER_ID'), 'bank_id'=>['neq', $bank_id]])->count()) {
            $this->ajaxReturn(output(CodeEnum::ACCOUNT_NUMBER_EXIST));
        }
        $bank_card->where(['bank_id'=> $bank_id])->save([
            'account_number'=> $account_number,
            'bank_name'=> $bank_name,
            'branch_bank'=> $branch_bank,
        ]);
        $this->addUserLog('编辑银行卡', "编辑银行卡");
        $this->ajaxReturn(output(CodeEnum::SUCCESS));
    }

    /**
     * @desc 删除银行卡
     * @param bank_id 银行卡ID
     * @param client_id 客户端ID
     * @param token     用户TOKEN
     * @return int
     */
    public function delBankCard() {
        $bank_id = I('get.bank_id', '', 'intval');
        $bank_card = M('bank_card');
        if (empty($bank_id)) {
            $this->ajaxReturn(output(CodeEnum::PARAM_ERROR));
        }
        $bankInfo = $bank_card->where(['user_id'=> C('USER_ID'), 'bank_id'=> $bank_id, 'is_delete'=> 0])->find();
        if (empty($bankInfo)) {
            $this->ajaxReturn(output(CodeEnum::BANK_NOT_EXIST));
        }
        if ($bankInfo['is_default'] == 1) {
            $this->ajaxReturn(output(CodeEnum::DEFAULT_CARD_CANNOT_DELETE));
        }
        $bank_card->where(['bank_id'=> $bank_id])->save(['is_delete'=> 1]);
        $this->addUserLog('删除银行卡', "删除银行卡：{$bankInfo['account_number']}");
        $this->ajaxReturn(output(CodeEnum::SUCCESS));
    }

    /**
     * @desc 提现详情
     * @param client_id 客户端ID
     * @param token     用户TOKEN
     * @return int
     */
    public function applyCashInfo() {
        $userInfo = M('user')->where(['user_id'=> C('USER_ID')])->field('pay_password,balance')->find();
        if (empty($userInfo)) {
            $this->ajaxReturn(output(CodeEnum::USER_NOT_EXIST));
        }
        // 判断有没设置提款密码
        if (empty($userInfo['pay_password'])) {
            $this->ajaxReturn(output(CodeEnum::PLEASE_SET_PAY_PASSWORD));
        }
        // 获取今日提款次数
        $count = M('draw_cash')->where(['user_id'=> C('USER_ID'), 'type'=>1, 'add_time'=>['egt', strtotime(date('Y-m-d'))]])->count();
        $free_draw_times = getConfig('free_draw_times');
        $free_times = $count >= $free_draw_times ? 0 : $free_draw_times - $count;
        $draw_fee = getConfig('draw_fee');
        $draw_fee = $draw_fee*100;
        $tips = "注意：每天可免费提现{$free_draw_times}次，超过{$free_draw_times}次则收取{$draw_fee}%的手续费";
        $result = [
            'balance'=> $userInfo['balance'],
            'free_times' => $free_times,
            'min_draw' => getConfig('min_draw'),
            'max_draw' => getConfig('max_draw'),
            'tips' => $tips,
        ];
        $this->ajaxReturn(output(CodeEnum::SUCCESS, $result));
    }

    /**
     * @desc  提交提款申请
     * @param apply_cash 提现金额
     * @param pay_password 提款密码
     * @param bank_id 银行卡ID
     * @param client_id 客户端ID
     * @param token     用户TOKEN
     * @return int
     */
    public function applyCashCommit() {
        $apply_cash = I('get.apply_cash','', 'floatval');
        $pay_password = I('get.pay_password');
        $bank_id = I('get.bank_id', '', 'intval');
        $client_id = I('get.client_id');
        if (empty($apply_cash) || $apply_cash <= 0 || empty($pay_password) || empty($bank_id)) {
            $this->ajaxReturn(output(CodeEnum::PARAM_ERROR));
        }
        // 没设置提款密码
        if (empty($this->userInfo['pay_password'])) {
            $this->ajaxReturn(output(CodeEnum::PLEASE_SET_PAY_PASSWORD));
        }
        // 提款密码错误
        if (md5($pay_password) != $this->userInfo['pay_password']) {
            $this->ajaxReturn(output(CodeEnum::PAY_PASSWORD_ERROR));
        }
        // 余额不足
        if ($this->userInfo['balance'] < $apply_cash) {
            $this->ajaxReturn(output(CodeEnum::BALANCE_IS_NOT_ENOUGH));
        }
        // 验证银行卡ID
        $bankInfo = M('bank_card')->where(['user_id'=> C('USER_ID'), 'bank_id'=> $bank_id, 'is_delete'=> 0])->find();
        if (empty($bankInfo)) {
            $this->ajaxReturn(output(CodeEnum::BANK_NOT_EXIST));
        }
        // 提现金额范围
        $min_draw = getConfig('min_draw');
        $max_draw = getConfig('max_draw');
        if ($apply_cash < $min_draw || $apply_cash > $max_draw) {
            $this->ajaxReturn(output(CodeEnum::DRAW_BALANCE_ERROR,[],[$min_draw,$max_draw]));
        }
        // 充值金额、流水
        $rechargeBalance = M('recharge')->where(['user_id'=> C('USER_ID'), 'sync'=> 1])->sum('real_cash');
        $waterBalance = M('user_water')->where(['user_id'=> C('USER_ID')])->sum('balance');
        $water_times = getConfig('water_times');
        $minWaterBalance = $rechargeBalance * $water_times;
        if ($minWaterBalance > $waterBalance) {
            $this->ajaxReturn(output(CodeEnum::WATER_NOT_ENOUGH,[],[$water_times]));
        }

        $draw_cash = M('draw_cash');
        // 获取今日提款次数
        $count = $draw_cash->where(['user_id'=> C('USER_ID'), 'type'=>1, 'add_time'=>['egt', strtotime(date('Y-m-d'))]])->count();
        $free_draw_times = getConfig('free_draw_times');
        $draw_fee = getConfig('draw_fee');
        $real_cash = $count >= $free_draw_times ? bcmul($apply_cash, (1-$draw_fee), 2) : $apply_cash;
        // 开户事务
        M()->startTrans();
        $ret1 = $draw_cash->add([
            'user_id'=> C('USER_ID'),
            'user_name'=> $this->userInfo['user_name'],
            'type'=> 1,
            'apply_cash'=> $apply_cash,
            'real_cash'=> $real_cash,
            'account_number'=> $bankInfo['account_number'],
            'bank_name'=> $bankInfo['bank_name'],
            'real_name'=> $bankInfo['real_name'],
            'branch_bank'=> $bankInfo['branch_bank'],
            'sync'=> 0,
            'add_time'=> time(),
        ]);
        $left_balance = bcsub($this->userInfo['balance'], $apply_cash, 2);
        $ret2 = M('user')->where(['user_id'=> C('USER_ID')])->save(['balance'=> $left_balance]);
        // 流水LOG
        $ret3 = M('user_waste_book')->add([
            'user_id'=> C('USER_ID'),
            'before_balance'=> $this->userInfo['balance'],
            'after_balance'=> $left_balance,
            'change_balance'=> -$apply_cash,
            'type'=> 2,
            'add_time'=> time(),
        ]);
        if ($ret1 && $ret2 && $ret3) {
            M()->commit();
            // 推送用户余额给用户
            sendToClient($client_id, CodeEnum::LEFT_BALANCE, ['balance'=> $left_balance]);
        } else {
            M()->rollback();
            $this->ajaxReturn(output(CodeEnum::APPLY_CASH_FAILED));
        }
        $this->addUserLog('提现', "申请提现{$apply_cash}");
        $this->ajaxReturn(output(CodeEnum::SUCCESS));
    }

    /**
     * @desc 修改提款密码
     * @param old_pay_password      旧资金密码
     * @param new_pay_password      新资金密码
     * @param re_pay_password       确认资金密码
     * @param client_id 客户端ID
     * @param token     用户TOKEN
     * @return int
     */
    public function editPayPassword() {
        $old_pay_password = I('get.old_pay_password', '', 'trim');
        $new_pay_password = I('get.new_pay_password', '', 'trim');
        $re_pay_password = I('get.re_pay_password', '', 'trim');
        if (empty($old_pay_password) || empty($new_pay_password) || empty($re_pay_password) || strlen($new_pay_password) < 6 || strlen($new_pay_password) > 15) {
            $this->ajaxReturn(output(CodeEnum::PARAM_ERROR));
        }
        if ($new_pay_password != $re_pay_password) {
            $this->ajaxReturn(output(CodeEnum::PAY_PASSWORD_DIFFERENT));
        }
        if ($this->userInfo['pay_password'] != md5($old_pay_password)) {
            $this->ajaxReturn(output(CodeEnum::PAY_PASSWORD_ERROR));
        }
        M('user')->where(['user_id'=> C('USER_ID')])->save(['pay_password'=> md5($new_pay_password)]);
        $this->addUserLog('修改资金密码', "修改资金密码");
        $this->ajaxReturn(output(CodeEnum::SUCCESS));
    }

    /**
     * @desc 设置提款密码
     * @param pay_password      资金密码
     * @param re_pay_password   确认资金密码
     * @param client_id 客户端ID
     * @param token     用户TOKEN
     * @return int
     */
    public function setPayPassword() {
        $pay_password = I('get.pay_password', '', 'trim');
        $re_pay_password = I('get.re_pay_password', '', 'trim');
        if (empty($pay_password) || empty($re_pay_password) || strlen($pay_password) < 6 || strlen($pay_password) > 15) {
            $this->ajaxReturn(output(CodeEnum::PARAM_ERROR));
        }
        if ($pay_password != $re_pay_password) {
            $this->ajaxReturn(output(CodeEnum::PAY_PASSWORD_DIFFERENT));
        }
        if (!empty($this->userInfo['pay_password'])) {
            $this->ajaxReturn(output(CodeEnum::PAY_PASSWORD_IS_SET));
        }
        M('user')->where(['user_id'=> C('USER_ID')])->save(['pay_password'=> md5($pay_password)]);
        $this->addUserLog('修改资金密码', "修改资金密码");
        $this->ajaxReturn(output(CodeEnum::SUCCESS));
    }

    /**
     * @desc  银联充值（线下）
     * @param recharge_cash     充值资金
     * @param account_number    银行卡号
     * @param bank_name         银行名称
     * @param real_name         真实姓名
     * @param client_id         客户端ID
     * @param token             用户TOKEN
     * @return int
     */
    public function recharge() {
        $recharge_cash = I('get.recharge_cash', '', 'floatval');
        $account_number = I('get.account_number');
        $bank_name = I('get.bank_name');
        $real_name = I('get.real_name');
        if (empty($recharge_cash) || empty($account_number) || empty($bank_name) || empty($real_name)) {
            $this->ajaxReturn(output(CodeEnum::PARAM_ERROR));
        }
        // 充值金额范围
        $min_recharge = getConfig('min_recharge');
        $max_recharge = getConfig('max_recharge');
        if ($recharge_cash < $min_recharge || $recharge_cash > $max_recharge) {
            $this->ajaxReturn(output(CodeEnum::RECHARGE_BALANCE_ERROR,[],[$min_recharge,$max_recharge]));
        }
        $bank_id = redisCache()->get(CacheEnum::RECHARGE_BANK_ID . $this->userInfo['user_id']);
        $random_code = redisCache()->get(CacheEnum::RECHARGE_RANDOM . $this->userInfo['user_id']);
        $random_code = isset($random_code) ? (string)$random_code : '';
        if (empty($bank_id)) {
            $bank_id = M('set_bank_card')->where(['is_default'=>1,'is_delete'=>0])->getField('id');
        }
        M('Recharge')->add([
            'user_id' => $this->userInfo['user_id'],
            'bank_id' => $bank_id,
            'user_name' => $this->userInfo['user_name'],
            'recharge_cash' => $recharge_cash,
            'account_number' => $account_number,
            'bank_name' => $bank_name,
            'real_name' => $real_name,
            'message' => $random_code,
            'type' => 1,
            'sync' => 0,
            'add_time' => time()
        ]);
        $this->addUserLog('充值', "线下充值{$recharge_cash}");
        $this->ajaxReturn(output(CodeEnum::SUCCESS));
    }

    /**
     * @desc 获取充值信息
     * @param client_id         客户端ID
     * @param token             用户TOKEN
     * @return int
     */
    public function getRechargeInfo() {
        $bankList = M('set_bank_card')->where(['is_default'=>1,'is_delete'=>0])->select();
        $key =rand(0, count($bankList)-1);
        $bankInfo = $bankList[$key];
        $random_code = strtolower(getRandChar(4));
        redisCache()->set(CacheEnum::RECHARGE_BANK_ID . $this->userInfo['user_id'], $bankInfo['id']);
        redisCache()->set(CacheEnum::RECHARGE_RANDOM . $this->userInfo['user_id'], $random_code);
        // 获取上一次充值银行卡信息
        $rechargeInfo = M('recharge')->where(['user_id'=>$this->userInfo['user_id'],'type'=>1])->order('id desc')->find();
        $my_bank_name = !empty($rechargeInfo) ? $rechargeInfo['bank_name'] : '';
        $my_real_name = !empty($rechargeInfo) ? $rechargeInfo['real_name'] : '';
        $my_account_number = !empty($rechargeInfo) ? $rechargeInfo['account_number'] : '';
        return $this->ajaxReturn(output(CodeEnum::SUCCESS, [
            'bank_name' => $bankInfo['bank_name'],
            'real_name' => $bankInfo['real_name'],
            'account_number' => $bankInfo['account_number'],
            'branch_bank' => $bankInfo['branch_bank'],
            'my_bank_name' => $my_bank_name,
            'my_real_name' => $my_real_name,
            'my_account_number' => $my_account_number,
            'tip' => "请在转账时留言：{$random_code}",
        ]));
    }

    /**
     * @desc 获取用户信息
     * @param date      日期，格式：Y-m-d，默认今天
     * @param client_id 客户端ID
     * @param token     用户TOKEN
     * @return int
     */
    public function getUserInfo() {
        $date = I('get.date');
        if (empty($date) || !preg_match('/^\d{4}\-\d{1,2}\-\d{1,2}$/', $date)) {
            $date = date('Y-m-d');
        }
        $id = C('IS_TEMP') ? "-1" : $this->userInfo['user_id'];
        $user_name = C('IS_TEMP') ? C('USER_ID') : $this->userInfo['user_name'];
        $nickname = C('IS_TEMP') ? getTempNickname(C('USER_ID')) : $this->userInfo['nickname'];
        $balance = C('IS_TEMP') ? "0.00" : $this->userInfo['balance'];
        $result = [
            'id'=> $id,
            'user_name'=> $user_name,
            'nickname'=> $nickname,
            'balance'=> $balance,
            'change_balance'=> "0.00",
            'date' => $date,
            'list' => []
        ];
        $time = strtotime($date);
        if (C('IS_TEMP') == 0) {
            $betLogList = M('bet_log')->where([
                'user_id' => C('USER_ID'),
                'add_time' => [['egt', $time], ['lt', $time + 86400]]
            ])->order('add_time desc')->select();
            $list = [];
            foreach ($betLogList as $key => $value) {
                // 游戏信息
                $siteInfo = D('Site')->getSiteInfo($value['room_id']);
                $gameInfo = D('Game')->getGameInfo($siteInfo['game_id']);
                // 场所信息
                // 彩票信息
                $lotteryInfo = D('Lottery')->getLotteryInfo($gameInfo['lottery_id']);
                // 期
                $lottery_number = M('lottery_issue')->where(['lottery_id'=> $gameInfo['lottery_id'], 'issue'=> $value['issue']])->getField('lottery_number');
                // 下注明细
                $bet_detail = json_decode($value['bet_detail'], true);
                $result['list'][] = [
                    'title' => $lotteryInfo['lottery_name'].'-'.$value['issue'].'期-'.$gameInfo['game_name'].'-'.$siteInfo['site_name'],
                    'lottery_number' => $lottery_number,
                    'bet_detail' => $bet_detail,
                    'bet_balance' => $value['bet_balance'],
                    'profit_balance' => $value['profit_balance'],
                    'add_time' => date('Y-m-d H:i:s', $value['add_time']),
                ];
                $result['change_balance'] = bcadd($result['change_balance'], $value['profit_balance'], 2);
            }
        }
        $this->ajaxReturn(output(CodeEnum::SUCCESS, $result));
    }

    /**
     * @desc 获取充值信息
     * @param client_id         客户端ID
     * @param token             用户TOKEN
     * @return int
     */
    public function getCSContact () {
        $cs_qq = getConfig('cs_qq');
        $cs_wx = getConfig('cs_wx');
        return $this->ajaxReturn(output(CodeEnum::SUCCESS, [
            'qq' => $cs_qq,
            'wx' => $cs_wx,
            'text' => "QQ:{$cs_qq} 微信:{$cs_wx}",
        ]));
    }

    /**
     * @desc 获取系统信息
     * @param client_id         客户端ID
     * @param token             用户TOKEN
     * @param page              页数
     * @return int
     */
    public function getSystemMessage() {
        $client_id = I('get.client_id');
        $system_message = M('system_message');
        $where = ['user_id'=> $this->userInfo['user_id']];
        $count = $system_message->where($where)->count();
        $pageInfo = setAppPage($count, 10);
        $list = $system_message->where($where)->limit($pageInfo['limit'])->field('id,content,read,add_time')->order('id desc')->select();
        $ids = [];
        foreach ($list as $key => &$value) {
            $value['add_time'] = date('Y-m-d H:i', $value['add_time']);
            if ($value['read'] == 0) {
                $ids[] = $value['id'];
            }
        }
        if (!empty($ids)) {
            $system_message->where(['id'=> ['in', $ids]])->save(['read'=> 1]);
            // 推送系统通知
            $unreadCount = $system_message->where(['user_id'=> $this->userInfo['user_id'], 'read'=>0])->count();
            sendToClient($client_id, CodeEnum::SYSTEM_MESSAGE, ['unreadCount'=> $unreadCount]);
        }
        return $this->ajaxReturn(output(CodeEnum::SUCCESS, [
            'prev' => $pageInfo['prev'],
            'next' => $pageInfo['next'],
            'list' => $list,
        ]));
    }
    
    /**
     * @desc 获取充微信，与随机码
     * @param client_id     客户端ID
     * @param token         用户TOKEN
     * @return int
     */
    public function getHandleRechargeInfo() {
		$code=time()%10000;
		$code=$code<10000?$code*10:$code;
        $this->ajaxReturn(output(CodeEnum::SUCCESS, [
            'cz_wx' => getConfig('cz_wx'),
			'cz_zfb' => getConfig('cz_zfb'),
            'random_code' => $code,
        ]));
    }
}
