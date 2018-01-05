<?php
namespace Cs\Controller;
use Lib\Enum\CodeEnum;
class UserController extends BaseController {
    private $rateArr = [2,1,0.8,0.6,0.4,0.35,0.3,0.25,0.2,0.15,0.1,0.05];
    // 代理列表
    public function agent() {
        $user_name = I('get.user_name');
        $rate = I('get.rate',-1,'floatval');
    	$pid = I('get.pid',0,'intval');
        $admin_user = D('admin_user');
        // 查询条件
        $where =$where2 =  [];
        if (!empty($user_name)) {
             $where2['user_name'] = array("eq",$user_name);
    		 $where2['user_id'] = array("eq",$user_name);
    		 $where2['_logic'] = 'OR';
    		$where['_complex'] = $where2 ; //联合查询
        }
        if ($rate != -1) {
            if ($rate == -2) {
                $where['rate'] = ['lt', 0.2];
            } else {
                $where['rate'] = $rate;
            }
        }
        if ($pid > 0) {
            $where['pid'] = $pid;
        }
        $count = $admin_user->where($where)->count();
        $pageInfo = setPage($count);
        $list = $admin_user->where($where)->limit($pageInfo['limit'])->field("user_id,user_name,nickname,rate,balance,status,invite_code,pid")->order('user_id desc')->select();
        $admin_login_log = M('admin_login_log');
        $user = M('user');
        foreach ($list as $key => $value) {
            // 最后登录
            $login_log = $admin_login_log->where(['user_id'=>$value['user_id'],'type'=>1])->order('id desc')->field('ip,add_time')->find();
            $list[$key]['login_ip'] = !empty($login_log) ? $login_log['ip'] : '';
            $list[$key]['add_time'] = !empty($login_log) ? $login_log['add_time'] : '';
            // 直属代理
            $list[$key]['agent_count'] = $admin_user->where(['pid'=>$value['user_id']])->count();
            // 直属会员
            $list[$key]['user_count'] = $user->where(['invite_code'=>$value['invite_code']])->count();
            // 上级
            $list[$key]['admin_name'] = $admin_user->where(['user_id'=>$value['pid']])->getField('user_name');
            $list[$key]['up_agent'] = $admin_user->getAllUpAgent($value['pid']);
        }
        $parent = [];
        if ($pid > 0) {
            $parent = D('AdminUser')->getParentAgent($pid);
            krsort($parent);
        }
        $this->assign('parent', $parent);
        $this->assign('user_name', $user_name);
        $this->assign('pid', $pid);
        $this->assign('rate', $rate);
        $this->assign('list', $list);
        $this->assign('pageInfo', $pageInfo);
        $this->display();
    }

    // 冻结代理
    public function freezeAgent() {
        if (IS_POST) {
            $user_id = I('post.user_id');
            $admin_user = M('admin_user');
            $adminInfo = $admin_user->where(['user_id'=>['in',$user_id]])->find();
            if (empty($adminInfo)) {
                $this->ajaxOutput('代理不存在');
            }
            $admin_user->where(['user_id'=>['in',$user_id]])->save(['status'=>0]);
            $this->addAtionLog("冻结代理账号{$adminInfo['user_name']}");
            $this->ajaxOutput("冻结成功", 1, U('User/agent'));
        }
    }

    // 解冻代理
    public function unfreezeAgent() {
        if (IS_POST) {
            $user_id = I('post.user_id');
            $admin_user = M('admin_user');
            $adminInfo = $admin_user->where(['user_id'=>['in',$user_id]])->find();
            if (empty($adminInfo)) {
                $this->ajaxOutput('代理不存在');
            }
            $admin_user->where(['user_id'=>['in',$user_id]])->save(['status'=>1]);
            $this->addAtionLog("解冻代理账号{$adminInfo['user_name']}");
            $this->ajaxOutput("解冻成功", 1, U('User/agent'));
        }
    }

    /**
     * 添加代理
     */
    public function addAgent() {
        if (IS_POST) {
            $user_name = I('post.user_name', '', 'htmlspecialchars,trim');
            $higher = I('post.higher', '', 'htmlspecialchars,trim');
            $nickname = I('post.nickname', '', 'htmlspecialchars,trim');
            $rate = I('post.rate', 0, 'floatval');
            $password = I('post.password', '', 'htmlspecialchars,trim');
            $repassword = I('post.repassword', '', 'htmlspecialchars,trim');
            $pay_password = I('post.pay_password', '', 'htmlspecialchars,trim');
            if (empty($user_name) || empty($higher) || empty($nickname) ||  !in_array($rate, $this->rateArr) || empty($password) || empty($pay_password)) {
                $this->ajaxOutput('参数错误');
            }
            if (strlen($password) < 4 || strlen($password) > 16) {
                $this->ajaxOutput('登录密码请输入4-16位字符');
            }
            if (strlen($pay_password) < 4 || strlen($pay_password) > 16) {
                $this->ajaxOutput('资金密码请输入4-16位字符');
            }
            if ($password != $repassword) {
                $this->ajaxOutput('登录密码和确认密码不一致');
            }
            $admin_user = M('admin_user');
            $higherInfo = $admin_user->where(['user_name'=>$higher])->field('user_id,rate,agent_count,pid')->find();
            if (empty($higherInfo)) {
                $this->ajaxOutput('上级账号不存在');
            }
            if ($higherInfo['rate'] <= $rate) {
                $this->ajaxOutput('设置返点必须小于上级账号的返点');
            }
            if ($higherInfo['pid'] != 0 && $higherInfo['agent_count'] <= 0) {
                $this->ajaxOutput("该上级代理已经没有代理名额");
            }
            $adminInfo = $admin_user->where(['user_name'=>$user_name])->find();
            if (!empty($adminInfo)) {
                $this->ajaxOutput('账号已存在');
            }
            if ($admin_user->where(['nickname'=>$nickname])->count()) {
                $this->ajaxOutput('账号别名已存在');
            }
            $invite_code = getInviteCode();
            $admin_user->add([
                'user_name'=> $user_name,
                'nickname'=> $nickname,
                'rate'=> $rate,
                'pid'=> $higherInfo['user_id'],
                'invite_code'=> $invite_code,
                'password'=> md5($password),
                'pay_password'=> md5($pay_password),
                'add_time'=>time(),
            ]);
            // 减少代理名额
            if ($higherInfo['pid'] != 0) {
                $admin_user->where(['user_id'=> $higherInfo['user_id']])->setDec('agent_count',1);
            }
            $this->addAtionLog("添加代理账号{$user_name}");
            $this->ajaxOutput("新增成功", 1, U('User/agent'));
        } else {
            $this->display();
        }
    }

    /**
     * 编辑代理
     */
    public function editAgent() {
        if (IS_POST) {
            $user_id = I('post.user_id', 0, 'intval');
            $nickname = I('post.nickname', '', 'htmlspecialchars,trim');
            $rate = I('post.rate', 0, 'floatval');
            $agent_count = I('post.agent_count', 0, 'intval');
            $password = I('post.password', '', 'htmlspecialchars,trim');
            $repassword = I('post.repassword', '', 'htmlspecialchars,trim');
            $pay_password = I('post.pay_password', '', 'htmlspecialchars,trim');
            if ($user_id < 1 || empty($nickname)) {
                $this->ajaxOutput('参数错误');
            }
            if ($password != $repassword) {
                $this->ajaxOutput('登录密码和确认密码不一致');
            }
            $admin_user = M('admin_user');
            $adminInfo = $admin_user->where(['user_id'=>$user_id])->find();
            if (empty($adminInfo)) {
                $this->ajaxOutput('代理不存在');
            }
            // 更新数据
            $updateData = ['nickname'=> $nickname];
            if ($adminInfo['pid'] != 0) {
                if (!in_array($rate, $this->rateArr) || $agent_count < 0) {
                    $this->ajaxOutput('参数错误');
                }
                $higherInfo = $admin_user->where(['user_id'=>$adminInfo['pid']])->field('user_id,rate,agent_count,pid')->find();
                if ($higherInfo['rate'] <= $rate) {
                    $this->ajaxOutput('设置返点必须小于上级的返点');
                }
                $min_rate = $admin_user->where(['pid'=>$user_id])->min('rate');
                $min_rate = !empty($min_rate) ? $min_rate : 0;
                if ($min_rate >= $rate) {
                    $this->ajaxOutput('设置返点必须大于下级的返点');
                }
                $updateData['rate'] = $rate;
                $dc = $agent_count - $adminInfo['agent_count'];
                if ($dc != 0) {
                    if ($higherInfo['agent_count'] < $dc && $higherInfo['pid'] != 0) {
                        $this->ajaxOutput('代理名额不够');
                    }
                    $updateData['agent_count'] = $agent_count;
                    // 改变上级代理名额
                    if ($higherInfo['pid'] != 0) {
                        $admin_user->where(['user_id'=> $higherInfo['user_id']])->setDec('agent_count', $dc);
                    }
                }    
            }
            if (!empty($password)) {
                if (strlen($password) < 4 || strlen($password) > 16) {
                    $this->ajaxOutput('登录密码请输入4-16位字符');
                }
                $updateData['password'] = md5($password);
            }
            if (!empty($pay_password)) {
                if (strlen($pay_password) < 4 || strlen($pay_password) > 16) {
                    $this->ajaxOutput('资金密码请输入4-16位字符');
                }
                $updateData['pay_password'] = md5($pay_password);
            }
            if ($admin_user->where(['nickname'=>$nickname,'user_id'=>['neq',$user_id]])->count()) {
                $this->ajaxOutput('别名已存在');
            }
            $admin_user->where(['user_id'=>$user_id])->save($updateData);
            $this->addAtionLog("修改代理账号{$adminInfo['user_name']}");
            $this->ajaxOutput("修改成功", 1, U('User/agent'));
        } else {
            $user_id = I('get.user_id');
            $admin_user = M('admin_user');
            $adminInfo = $admin_user->where(['user_id'=>$user_id])->find();
            if (empty($adminInfo)) {
                $this->error('代理不存在');
            }
            $adminInfo['higher'] = $admin_user->where(['user_id'=>$adminInfo['pid']])->getField('user_name');
            $adminInfo['up_name'] = D('AdminUser')->getAllUpAgent($user_id);
            $this->assign('adminInfo', $adminInfo);
            $this->display();
        }
    }

    // 收益记录
    public function adminWasteBook() {
        $type = I('get.type', 0, 'intval');
        $user_id = I('get.user_id', 0, 'intval');
        $admin_waste_book = M('admin_waste_book');
        $where = ['user_id'=>$user_id];
        if ($type > 0) {
            $where['type'] = $type;
        }
        $count = $admin_waste_book->where($where)->count();
        $pageInfo = setPage($count);
        $list = $admin_waste_book->where($where)->limit($pageInfo['limit'])->order('id desc')->select();
        foreach ($list as $key => $value) {
            $type_name = '';
            switch ($value['type']) {
                case '1': $type_name = '佣金';break;
                case '2': $type_name = '提现';break;
                case '3': $type_name = '提现失败';break;
                case '4': $type_name = '退款';break;
            }
            $list[$key]['type_name'] = $type_name;
        }
        $this->assign('user_id', $user_id);
        $this->assign('type', $type);
        $this->assign('list', $list);
        $this->assign('pageInfo', $pageInfo);
        $this->display();
    }

    // 流水排名
    public function agentRank() {
        $type = I('get.type');
        $start_time = 0;
        $end_time = 0;
        // 条件
        $where = [];
        $clock = 6;
        switch ($type) {
            case 'thisweek':
                $thisweek = strtotime('this monday') + $clock*3600;
                $start_time = time() > $thisweek ? $thisweek : $thisweek - 3600*24*7;
                $end_time = time();
                break;                   
            case 'lastweek':
                $thisweek = strtotime('this monday') + $clock*3600;
                $start_time = time() > $thisweek ? $thisweek - 3600*24*7 : $thisweek - 3600*24*14;
                $end_time = time() > $thisweek ? $thisweek : $thisweek - 3600*24*7;
                break;
            case 'thismonth':
                $thismonth = strtotime(date('Y-m-01')) + $clock*3600;
                $start_time = $thismonth;
                $end_time = time();
                break;
            case 'lastmonth':
                $thismonth = strtotime(date('Y-m-01')) + $clock*3600;
                $lastmonth = strtotime(date('Y-m-01', strtotime('-1 month'))) + $clock*3600;
                $start_time = $lastmonth;
                $end_time = $thismonth;
                break;
            case 'thisseason':
                $month = floor((date('n'))/3)*3+1;
                $start_time = strtotime(date('Y').'-'.$month.'-01') + $clock*3600;
                $end_time = time();
                break;
            case 'lastseason':
                $month = floor((date('n'))/3)*3+1;
                if ($month == 1) {
                    $start_time = strtotime((date('Y')-1).'-10-01') + $clock*3600;
                } else {
                    $start_time = strtotime(date('Y').'-'.($month-3).'-01') + $clock*3600;
                }
                $end_time = strtotime(date('Y').'-'.$month.'-01') + $clock*3600;
                break;
            default: 
                $thisweek = strtotime('this monday') + $clock*3600;
                $start_time = time() > $thisweek ? $thisweek : $thisweek - 3600*24*7;
                $end_time = time();
                $type = 'thisweek';
                break;
        }
        $show_time = date('Y-m-d H:i',$start_time).' ~ '.date('Y-m-d H:i',$end_time);
        $where['add_time'][] = ['egt', $start_time];
        $where['add_time'][] = ['lt', $end_time];
        
        // 列表
        $user_water = M('user_water');
        $admin_user = M('admin_user');
        $admin_income = M('admin_income');
        $count = $user_water->where($where)->group('admin_id')->getField('id', true);
        $count = !empty($count) ? count($count) : 0;
        $pageInfo = setPage($count);
        $list = $user_water->where($where)->group('admin_id')->limit($pageInfo['limit'])->field('admin_id,sum(balance) as balance')->order('balance desc')->select();
        $db_prefix = C('DB_PREFIX');
        foreach ($list as $key => $value) {
            $adminInfo = $admin_user->where(['user_id'=> $value['admin_id']])->field('user_name,nickname,invite_code')->find();
            $list[$key]['user_name'] = $adminInfo['user_name'];
            $list[$key]['nickname'] = $adminInfo['nickname'];
            // 直属玩家佣金总数
            $list[$key]['income'] = $admin_income->join("as i inner join {$db_prefix}user as u on i.user_id=u.user_id")->where(['i.admin_id'=>$value['admin_id'], 'u.invite_code'=>$adminInfo['invite_code']])->sum('commission');
            $list[$key]['income'] = !empty($list[$key]['income']) ? $list[$key]['income'] : "0.00";
            // 直属玩家会员盈亏
            $list[$key]['profit'] = $admin_income->join("as i inner join {$db_prefix}user as u on i.user_id=u.user_id")->where(['i.admin_id'=>$value['admin_id'], 'u.invite_code'=>$adminInfo['invite_code']])->sum('profit_balance');
            $list[$key]['profit'] = !empty($list[$key]['profit']) ? $list[$key]['profit'] : "0.00";
        }

        $this->assign('type', $type);
        $this->assign('show_time', $show_time);
        $this->assign('list', $list);
        $this->assign('pageInfo', $pageInfo);
        $this->display();
    }

    // 用户列表
    public function user() {
        $user_name = I('get.user_name');
        $where = [];
        //array(array('gt',3),array('lt',10), 'or');
        if (!empty($user_name)){
        		 $where['user_name'] = array("eq",$user_name);
        		 $where['user_id'] = array("eq",$user_name);
        		 $where['_logic'] = 'OR';
        }
        $user = M('user');
        //where("id=%d and username='%s' and xx='%f'",array($id,$username,$xx))
        $count = $user->where($where)->count();
        $pageInfo = setPage($count);
        $list = $user->where($where)->limit($pageInfo['limit'])->order('user_id desc')->select();
        $login_log = M('login_log');
        $admin_user = M('admin_user');
        $user_token = M('user_token');
        foreach ($list as $key => $value) {
            // 最后登录
            $list[$key]['login_ip'] = $login_log->where(['user_id'=>$value['user_id']])->order('id desc')->getField('ip');
            // 直属代理
            $list[$key]['admin_name'] = $admin_user->where(['invite_code'=>$value['invite_code']])->getField('user_name');
            // 活动时间
            $list[$key]['token_time'] = $user_token->where(['user_id'=>$value['user_id'],'is_temp'=>0])->getField('add_time');
        }
        $this->assign('user_name', $user_name);
        $this->assign('list', $list);
        $this->assign('pageInfo', $pageInfo);
        $this->display();
    }

    // 冻结用户
    public function freezeUser() {
        if (IS_POST) {
            $user_id = I('post.user_id');
            $user = M('user');
            $userInfo = $user->where(['user_id'=>['in',$user_id]])->find();
            if (empty($userInfo)) {
                $this->ajaxOutput('用户不存在');
            }
            $user->where(['user_id'=>['in',$user_id]])->save(['status'=>0]);
            $this->addAtionLog("冻结会员账号{$userInfo['user_name']}");
            $this->ajaxOutput("冻结成功", 1, U('User/user'));
        }
    }

    // 解冻用户
    public function unfreezeUser() {
        if (IS_POST) {
            $user_id = I('post.user_id');
            $user = M('user');
            $userInfo = $user->where(['user_id'=>['in',$user_id]])->find();
            if (empty($userInfo)) {
                $this->ajaxOutput('用户不存在');
            }
            $user->where(['user_id'=>['in',$user_id]])->save(['status'=>1]);
            $this->addAtionLog("解冻会员账号{$userInfo['user_name']}");
            $this->ajaxOutput("解冻成功", 1, U('User/user'));
        }
    }

    /**
     * 添加用户
     */
    public function addUser() {
        if (IS_POST) {
            $user_name = I('post.user_name', '', 'htmlspecialchars,trim');
            $admin_name = I('post.admin_name', '', 'htmlspecialchars,trim');
            $nickname = I('post.nickname', '', 'htmlspecialchars,trim');
            $password = I('post.password', '', 'htmlspecialchars,trim');
            $repassword = I('post.repassword', '', 'htmlspecialchars,trim');
            $pay_password = I('post.pay_password', '', 'htmlspecialchars,trim');
            if (empty($user_name) || empty($admin_name) || empty($nickname) || empty($password) || empty($pay_password)) {
                $this->ajaxOutput('参数错误');
            }
            if (strlen($password) < 4 || strlen($password) > 16) {
                $this->ajaxOutput('登录密码请输入4-16位字符');
            }
            if (strlen($pay_password) < 4 || strlen($pay_password) > 16) {
                $this->ajaxOutput('资金密码请输入4-16位字符');
            }
            if ($password != $repassword) {
                $this->ajaxOutput('登录密码和确认密码不一致');
            }
            $invite_code = M('admin_user')->where(['user_name'=>$admin_name,'status'=>1])->getField('invite_code');
            if (empty($invite_code)) {
                $this->ajaxOutput('代理账号不存在');
            }
            $user = M('user');
            if ($user->where(['user_name'=> $user_name])->count()) {
                $this->ajaxOutput('该账号已被注册');
            }
            if ($user->where(['nickname'=> $nickname])->count()) {
                $this->ajaxOutput('该昵称已被注册');
            }
            M('user')->add([
                'user_name'=> $user_name,
                'nickname'=> $nickname,
                'password'=> md5($password),
                'pay_password'=> md5($pay_password),
                'invite_code'=> $invite_code,
                'add_time'=> time(),
            ]);
            $this->addAtionLog("添加会员账号{$user_name}");
            $this->ajaxOutput("新增成功", 1, U('User/user'));
        } else {
            $this->display();
        }
    }

    /**
     * 编辑用户
     */
    public function editUser() {
        if (IS_POST) {
            $user_id = I('post.user_id', 0, 'intval');
            $nickname = I('post.nickname', '', 'htmlspecialchars,trim');
            $password = I('post.password', '', 'htmlspecialchars,trim');
            $repassword = I('post.repassword', '', 'htmlspecialchars,trim');
            $pay_password = I('post.pay_password', '', 'htmlspecialchars,trim');
            if ($user_id < 1 || empty($nickname)) {
                $this->ajaxOutput('参数错误');
            }
            if ($password != $repassword) {
                $this->ajaxOutput('登录密码和确认密码不一致');
            }
            $user = M('user');
            $userInfo = $user->where(['user_id'=>$user_id])->find();
            if (empty($userInfo)) {
                $this->ajaxOutput('用户不存在');
            }
            if ($user->where(['user_id'=>['neq',$user_id],'nickname'=>$nickname])->count()) {
                $this->ajaxOutput('昵称已存在');
            }
            // 更新数据
            $updateData = ['nickname'=> $nickname];
            if (!empty($password)) {
                if (strlen($password) < 4 || strlen($password) > 16) {
                    $this->ajaxOutput('登录密码请输入4-16位字符');
                }
                $updateData['password'] = md5($password);
            }
            if (!empty($pay_password)) {
                if (strlen($pay_password) < 4 || strlen($pay_password) > 16) {
                    $this->ajaxOutput('资金密码请输入4-16位字符');
                }
                $updateData['pay_password'] = md5($pay_password);
            }
            $user->where(['user_id'=>$user_id])->save($updateData);
            $this->addAtionLog("修改会员账号{$userInfo['user_name']}");
            $this->ajaxOutput("修改成功", 1, U('User/user'));
        } else {
            $user_id = I('get.user_id');
            $user = M('user');
            $userInfo = $user->where(['user_id'=>$user_id])->find();
            if (empty($userInfo)) {
                $this->error('用户不存在');
            }
            $this->assign('userInfo', $userInfo);
            $this->display();
        }
    }

    // 账变记录
    public function userWasteBook() {
        $user_id = I('get.user_id', 0, 'intval');
        $user_waste_book = M('user_waste_book');
        $where = ['user_id'=>$user_id];
        $count = $user_waste_book->where($where)->count();
        $pageInfo = setPage($count);
        $list = $user_waste_book->where($where)->order('id desc')->limit($pageInfo['limit'])->select();
        foreach ($list as $key => $value) {
            $type_name = '';
            switch ($value['type']) {
                case '1': $type_name = '下注';break;
                case '2': $type_name = '提现';break;
                case '3': $type_name = '提现失败';break;
                case '4': $type_name = '充值';break;
                case '5': $type_name = '取消下注';break;
                case '6': $type_name = '上庄';break;
                case '7': $type_name = '下庄';break;
                case '8': $type_name = '退款';break;
                case '9': $type_name = '回利';break;
            }
            $list[$key]['type_name'] = $type_name;
        }
        $this->assign('user_id', $user_id);
        $this->assign('list', $list);
        $this->assign('pageInfo', $pageInfo);
        $this->display();
    }

    // 用户日志
    public function userLog() {
        $user_id = I('get.user_id', 0, 'intval');
        $user_name = I('get.user_name');
        $user_log = M('user_log');
        $where = [];
        if ($user_id > 0) {
            $where['user_id'] = $user_id;
        }
        if (!empty($user_name)) {
            $where['user_name'] = $user_name;
        }
        $count = $user_log->where($where)->count();
        $pageInfo = setPage($count);
        $list = $user_log->where($where)->order('id desc')->limit($pageInfo['limit'])->select();
        $this->assign('user_id', $user_id);
        $this->assign('user_name', $user_name);
        $this->assign('list', $list);
        $this->assign('pageInfo', $pageInfo);
        $this->display();
    }
    /**
     * 对用户进行加钱操作
     */
    public function userJiaMinsMoney(){
    	$user_id = I('get.user_id', 0, 'intval');
    	$ispost = false ;
    	if(!isset($user_id) || empty($user_id)){
    		$ispost = true ;
    		$user_id = I('post.user_id', 0, 'intval');
    	}

    	$userObj = M('user');
    	$userBcard = M('bank_card');
    	$user_money = 0 ;
    	if(isset($user_id) && $user_id > 0){
    		$uinfo = $userObj->where(['user_id'=>$user_id])->find();
    		if(!$uinfo || empty($uinfo['user_id'])) {
    			if($ispost){
    				$this->ajaxOutput("非法操作", 0, U('User/user'));
    			}else{
    				exit("非法操作");
    			}
    		}
    		$user_money = isset($uinfo['balance']) ? floatval($uinfo['balance']): 0.0;
    		//获取用户的银行卡信息
    		$userBand = M('bank_card')->where(['user_id'=>$user_id])->find();
    		if(!$userBand || empty($userBand['bank_id'])){ //没有银行卡
    			$userBand = array('bank_name'=> '中国银行','real_name'=> @$uinfo['user_name'],"account_number"=> '88888888') ; //默认一个
    			$lstId = M('bank_card')->add($userBand); //添加默认卡信息
    			if(isset($lstId) && intval($lstId)>0){ $userBand["bank_id"] = $lstId ; }
    			/* if($ispost){
    				$this->ajaxOutput("很抱歉，当前用户没有绑定银行卡信息，操作失败", 0, U('User/user'));
    			}else{
    				exit("很抱歉，当前用户没有绑定银行卡信息，操作失败。");
    			} */
    		}
    		if(IS_POST){  //操作内容保存
    			$handType = I('post.handerType','IN','trim');
    			$handDing = 1 ; // I('post.isDing','1','intval');
    			$user_amonut_money = I('post.user_amount',0.0,'trim');
    			$user_sn_out = I('post.user_out_sn','','htmlspecialchars,trim');
    			if(!isset($user_amonut_money) || floatval($user_amonut_money) <= 0){
    				$this->ajaxOutput("交易金额必须大于0，操作失败", 0, U('User/user'));
    			}
    			$admin_Name = (isset($this->csUserInfo) && isset($this->csUserInfo["user_name"]) ) ?  $this->csUserInfo['user_name'] : 'cs-admin';
    			$commonData = array(
						"user_id" => $user_id,"user_name"=> @$uinfo['user_name'],"type"=> 1,'sync'=>0,"add_time"=>time(),
						"account_number"=> isset($userBand['account_number'])	? trim($userBand['account_number']):'' ,
						"bank_name"=> isset($userBand['bank_name'])	? trim($userBand['bank_name']): '' ,
						"real_name"=> isset($userBand['real_name'])	? trim($userBand['real_name']): '' ,
    			);

    			$handName = "后台充值";
    			$res_num = 0 ;
    			// 开户事务
    			M()->startTrans();
    			if($handType && $handType == 'OUT'){
    					$lastBlance = bcsub($user_money,$user_amonut_money, 2);
    					if( ! $user_money || $lastBlance< 0){
		    				$this->ajaxOutput("用户余额不足 ，操作失败", 0, U('User/user'));
		    			}
    					$handName = "后台扣款";

    					$commonData["type"] = 1 ; 		 //代理(=2)还是用户(=1)
    					$commonData["branch_bank"] = isset($userBand['branch_bank'])	? trim($userBand['branch_bank']): '-' ;
    					$commonData["apply_cash"] =  floatval($user_amonut_money);  	//提现金额
    					$commonData["real_cash"]  =  floatval($user_amonut_money);
    					$commonData["sync"] = 1 ; 		 //同意提款/ /sync=0  申请中，1-同意提款，2-取消，3-有问题 
    					$commonData["cs_name"]  = $admin_Name;
    					$commonData["sync_time"]  = time();  //同步时间

    					$res_num = M('draw_cash')->add($commonData);				 //添加记录信息
    					//添加资金记录
    					$recodeInfo = array("user_id"=> $user_id,"before_balance"=>$user_money,"after_balance" => $lastBlance ,"change_balance"=> - floatval($user_amonut_money),"type"=> 2,"add_time"=> time() );
    					$userEditData = array("balance"=> $lastBlance);
    					$ret_unum  = M('user')->where(['user_id'=> $user_id])->save($userEditData);
    					$ret_rcos  = M('user_waste_book')->add($recodeInfo);
    					if(!($res_num && $ret_rcos  && $ret_unum )){
    							 M()->rollback();
               					 $this->ajaxOutput('扣款操失败');
    					}
    			}else{
    				$lastBlance = bcadd($user_money,$user_amonut_money, 2);
    				$recodeInfo = array(
    						//"user_id" => $user_id,"user_name"=> @$uinfo['user_name'],"type"=> 1,'sync'=>0,
    						"recharge_cash"=> floatval($user_amonut_money),
    						"order_sn"=>$user_sn_out,  //外部订单号
    						"bank_id"=> isset($userBand['bank_id'])	? trim($userBand['bank_id']): 0 ,
    						//"account_number"=> isset($userBand['account_number'])	? trim($userBand['account_number']):'' ,
    						"message"=> '后台充值',
    						//"add_time"=>time()
    				);
    				$recodeInfo = $commonData ? array_merge($commonData , $recodeInfo)  : $commonData ;
    				if(isset($handDing) && $handDing === 1){ //要立即生效
    					$recodeInfo["sync"]=1;
    					$recodeInfo["real_cash"]=floatval($user_amonut_money);
    					$recodeInfo["mm_name"]=$admin_Name;
    					$recodeInfo["pay_time"]=time();
    					$res_num =  M('recharge')->add($recodeInfo); //添加记录信息
    					//添加资金记录
    					$recodeInfo = array("user_id"=> $user_id,"before_balance"=>$user_money,"after_balance" => $lastBlance ,"change_balance"=> floatval($user_amonut_money),"type"=> 4,"add_time"=> time() );
    					$userEditData = array("balance"=> $lastBlance);
    					$ret_unum  = M('user')->where(['user_id'=> $user_id])->save($userEditData);
    					$ret_rcos  = M('user_waste_book')->add($recodeInfo);
    					if(!($res_num && $ret_rcos  && $ret_unum )){
    						M()->rollback();
    						$this->ajaxOutput('加款操失败');
    					}
    				}else{
    					$res_num =  M('recharge')->add($recodeInfo); //添加记录信息
    				}
    			}
    			if(isset($res_num) && intval($res_num) >0){
    				M()->commit();
    				$this->addAtionLog("后台操作会员{$uinfo['user_name']}  {$handName}；金额：￥{$user_amonut_money}");
    				$this->ajaxOutput("操作成功", 1, U('User/user'));
    			}else{
    				 $this->ajaxOutput("操作失败");
    			}
    		}else{
    			$this->assign('user_id', $user_id);
    			$this->assign('userInfo', $uinfo);
    			$this->assign('user_blance_money', $user_money);
    			$this->display();
    		}
    	}else{
    		exit("非法操作,暂无用户信息");
    	}
    }
}
