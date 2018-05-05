<?php
namespace Ag\Controller;
class AgentController extends BaseController {
    private $rateEnum = [2,1.8,1.6,1.4,1.35,1.3,1.25,1.2,1.15,1.1,1,0.95,0.9,0.85,0.8,0.75,0.7,0.65,0.6,0.55,0.5,0.45,0.4,0.35,0.3,0.25,0.2,0.15,0.1,0.05];
    public function agentList() {
        $admin_user = M('admin_user');
        // 查询条件
        $where = ['pid'=> $this->agUserInfo['user_id']];
        $count = $admin_user->where($where)->count();
        $pageInfo = setAppPage($count);
        $list = $admin_user->where($where)->limit($pageInfo['limit'])->field("user_id,user_name,rate,add_time")->select();
        $draw_cash = M('draw_cash');
        foreach ($list as $key => $value) {
            $apply_cash = $draw_cash->where(['user_id'=> $value['user_id'], 'type'=> 2, 'sync'=> ['neq',2]])->sum('apply_cash');
            $list[$key]['apply_cash'] = !empty($apply_cash) ? $apply_cash : "0.00";
            $list[$key]['rate'] = $value['rate']/100;
        }
        $canadd = $this->agUserInfo['rate'] <= $this->rateEnum[count($this->rateEnum)-1] ? 0 : 1;
        $this->assign('canadd', $canadd);
        $this->assign('list', $list);
    	$this->assign('pageInfo', $pageInfo);
    	$this->display();
    }

    public function agentInfo () {
        $user_id = I('user_id',0,'intval');
        $admin_user = M('admin_user');
        $adminInfo = $admin_user->where(['user_id'=> $user_id,'pid'=> $this->agUserInfo['user_id']])->find();
        if (empty($adminInfo)) {
            $this->error("直属代理不存在");
        }
        $apply_cash = M('draw_cash')->where(['user_id'=> $user_id, 'type'=> 2, 'sync'=> ['neq',2]])->sum('apply_cash');
        $adminInfo['apply_cash'] = !empty($apply_cash) ? $apply_cash : "0.00";
        $adminInfo['user_count'] = M('user')->where(['invite_code'=>$adminInfo['invite_code']])->count();
        $this->assign('adminInfo', $adminInfo);
        $this->display();
    }

    public function addAgent() {
        if (IS_POST) {
            $user_name = I('post.user_name');
            $nickname = I('post.nickname');
            $password = I('post.password');
            $rate = I('post.rate', 0, 'floatval');
            if (empty($user_name) || empty($password) || empty($rate) || empty($nickname)) {
                $this->ajaxOutput("参数不完整");
            }
            if (!preg_match('/^[A-Za-z0-9]{6,15}$/', $user_name)) {
                $this->ajaxOutput("账号必须是6-15位英文字母或数字");
            }
            if (!preg_match('/^[A-Za-z0-9]{6,15}$/', $password)) {
                $this->ajaxOutput("密码必须是6-15位英文字母或数字");
            }
            if (!in_array($rate, $this->rateEnum)) {
                $this->ajaxOutput("返点不合法");
            }
            if ($this->agUserInfo['rate'] <= $rate) {
                $this->ajaxOutput("返点不能高于当前帐号");
            }
            if ($this->agUserInfo['pid'] != 0 && $this->agUserInfo['agent_count'] <= 0) {
                $this->ajaxOutput("没有代理名额");
            }
            $admin_user = M('admin_user');
            if ($admin_user->where(['user_name'=> $user_name])->count()) {
                $this->ajaxOutput("该代理帐号已存在，请重新输入");
            }
            if ($admin_user->where(['nickname'=> $nickname])->count()) {
                $this->ajaxOutput("该帐号昵称已存在，请重新输入");
            }
            // 添加代理
            $invite_code = getInviteCode();
            $admin_user->add([
                'user_name'=> $user_name,
                'nickname'=> $nickname,
                'password'=> md5($password),
                'pid'=> $this->agUserInfo['user_id'],
                'rate'=> $rate,
                'invite_code'=> $invite_code,
                'add_time'=> time(),
            ]);
            // 减少代理名额
            if ($this->agUserInfo['pid'] != 0) {
                $admin_user->where(['user_id'=> $this->agUserInfo['user_id']])->setDec('agent_count',1);
            }
            $this->ajaxOutput("添加成功",1,U('Agent/agentList'));
        } else {
            $rateList = [];
            foreach ($this->rateEnum as $value) {
                if ($value < $this->agUserInfo['rate']) {
                    $rateList[] = bcadd($value, 0, 2);
                }
            }
            $this->assign('rateList', $rateList);
            $this->display();
        }
    }

    public function editAgent() {
        if (IS_POST) {
            $user_id = I('post.user_id', 0, 'intval');
            $password = I('post.password','','trim');
            $re_password = I('post.re_password','','trim');
            $agent_count = I('post.agent_count', 0,'intval');
            if ($user_id < 1 || $agent_count < 0) {
                $this->ajaxOutput('参数错误');
            }
            $saveData = [];
            if (!empty($password)) {
                if (!preg_match('/^[A-Za-z0-9]{6,16}$/', $password)) {
                    $this->ajaxOutput('请输入6至16位英文或数字作为密码');
                }
                if ($password != $re_password) {
                    $this->ajaxOutput('两个密码不一致');
                }
                $saveData['password'] = md5($password);
            }
            $admin_user = M('admin_user');
            $adminInfo = $admin_user->where(['user_id'=> $user_id, 'pid'=> $this->agUserInfo['user_id']])->find();
            if (empty($adminInfo)) {
                $this->ajaxOutput('代理不存在');
            }
            $dc = $agent_count - $adminInfo['agent_count'];
            if ($dc !=0 ) {
                if ($this->agUserInfo['agent_count'] < $dc && $this->agUserInfo['pid'] != 0) {
                    $this->ajaxOutput('代理名额不够');
                }
                $saveData['agent_count'] = $agent_count;
                // 改变自身代理名额
                if ($this->agUserInfo['pid'] != 0) {
                    $admin_user->where(['user_id'=> $this->agUserInfo['user_id']])->setDec('agent_count', $dc);
                }
            }  
            $admin_user->where(['user_id'=> $user_id])->save($saveData);
            $this->ajaxOutput('修改成功', 1, U('Agent/agentInfo', ['user_id'=>$user_id]));
        }
    }
}
