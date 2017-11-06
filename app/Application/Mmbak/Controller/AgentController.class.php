<?php
namespace Mmbak\Controller;
use Mmbak\Controller\BaseController;
class AgentController extends BaseController {
    public function index() {
        $user_name = I('get.user_name');
        $pid = I('get.pid');
        $admin_user = M('admin_user');
        // 查询条件
        $where = array();
        if (!empty($user_name)) $where['user_name'] = $user_name;
        $agent_name = '';
        if (!empty($pid)) {
            $where['pid'] = $pid;
            $agent_name = $admin_user->where(['user_id'=> $pid])->getField('user_name');
        }
        $agent_name = !empty($agent_name) ? $agent_name : '';
        $count = $admin_user->where($where)->count();
        $PageObject = new \Think\Page($count,15);
        $adminList = $admin_user->where($where)->order('user_id asc')->limit($PageObject->firstRow.','.$PageObject->listRows)->field("user_id,user_name,rate,pid,balance,status")->select();
        $admin_apply_cash = M('admin_apply_cash');
        $admin_income = M('admin_income');
        foreach ($adminList as $key => $value) {
            // 提款金额
            $apply_cash = $admin_apply_cash->where(['user_id'=> $value['user_id'], 'sync'=> 1])->sum('apply_cash');
            $adminList[$key]['apply_cash'] = !empty($apply_cash) ? $apply_cash : "0.00";
            // 最后修改密码操作人
            $action_name = M('edit_password_log')->where(['user_id'=> $value['user_id'], 'is_agent'=>1])->order('id desc')->getField('action_name');
            $adminList[$key]['action_name'] = !empty($action_name) ? $action_name : '';
        }
        $this->assign('agent_name', $agent_name);
        $this->assign('user_name', $user_name);
        $this->assign('adminList', $adminList);
        $this->assign('page_show', $PageObject->show());
        $this->display();
    }

    public function editPassword() {
        $user_name = I('post.user_name');
        $password = I('post.password');
        if (empty($user_name) || empty($password)) {
            exit(json_encode([
                'code' => 1,
                'msg' => "参数错误",
                'data' => [],
            ]));
        }
        if (!preg_match('/^[A-Za-z0-9]{6,15}$/', $password)) {
            exit(json_encode([
                'code' => 1,
                'msg' => "密码必须是6-15位英文字母或数字",
                'data' => [],
            ]));
        }
        $adminInfo = M('admin_user')->where(['user_name'=> $user_name, 'pid'=>['neq', 0]])->find();
        if (empty($adminInfo)) {
            exit(json_encode([
                'code' => 1,
                'msg' => "代理不存在",
                'data' => [],
            ]));
        }
        M('admin_user')->where(['user_id'=> $adminInfo['user_id']])->save(['password' => md5($password)]);
        // 记录修改日志
        M('edit_password_log')->add([
            'user_id'=> $adminInfo['user_id'], 
            'is_agent'=> 1,
            'action_name'=> session("mm_name"),
            'type'=> 1,
            'add_time'=> time(),
        ]);
        // 成功返回
        exit(json_encode([
            'code' => 0,
            'msg' => "success!",
            'data' => session("mm_name"),
        ]));
    }

    public function freeze() {
        $user_id = I('get.user_id', 0, 'intval');
        $status = I('get.status', 0, 'intval');
        if (empty($user_id) || !in_array($status, [0,1])) {
            $this->error('参数错误');
        }
        M('admin_user')->where(['user_id'=> $user_id, 'pid'=>['neq', 0]])->save([
            'status'=> $status
        ]);
        $msg = $status == 1 ? "解冻成功" : "冻结成功";
        $this->success($msg, U('Agent/index'));
    }

    public function stopLogin() {
        $user_id = I('get.user_id', 0, 'intval');
        $status = I('get.status', 0, 'intval');
        if (empty($user_id) || !in_array($status, [0,1])) {
            $this->error('参数错误');
        }
        M('admin_user')->where(['user_id'=> $user_id, 'pid'=>['neq', 0]])->save([
            'login_status'=> $status
        ]);
        $msg = $status == 1 ? "允许登录成功" : "禁止登录成功";
        $this->success($msg, U('Agent/index'));
    }

    public function delete() {
        $user_id = I('get.user_id', 0, 'intval');
        if (empty($user_id)) {
            $this->error('参数错误');
        }
        $user = M('user');
        $admin_user = M('admin_user');
        $adminInfo = $admin_user->where(['user_id'=> $user_id, 'pid'=>['neq', 0]])->field('balance,invite_code')->find();
        if (empty($adminInfo)) {
            $this->error('用户不存在');
        }
        if ($adminInfo['balance'] > 0) {
            $this->error('该代理账户上还有余额，删除失败');
        }
        $balance = $user->where(['invite_code'=> $adminInfo['invite_code']])->sum('balance');
        if (!empty($balance) && $balance > 0) {
            $this->error('该代理的下级玩家账户上还有余额，删除失败');
        }
        $adminIds = $this->getAdminIds([$user_id]);
        if (!empty($adminIds)) {
            $balance = $admin_user->where(['user_id'=> ['in', $adminIds]])->sum('balance');
            if (!empty($balance) && $balance > 0) {
                $this->error('该代理的下属代理账户上还有余额，删除失败');
            }
        }
        $inviteCodes = $admin_user->where(['user_id'=> ['in', $adminIds]])->getField('invite_code', true);
        $balance = $user->where(['invite_code'=> ['in', $inviteCodes]])->sum('balance');
        if (!empty($balance) && $balance > 0) {
            $this->error('该代理的下属代理的下级玩家账户上还有余额，删除失败');
        }
        $admin_user->where(['user_id'=> ['in', $adminIds]])->delete();
        $user->where(['invite_code'=> ['in', $inviteCodes]])->delete();
        $this->success('删号成功', U('Agent/index'));
    }

    private function getAdminIds($arr) {
        $ids = M('admin_user')->where(['pid'=>['in', $arr]])->getField('user_id', true);
        if (!empty($ids)) {
            $ids = $this->getAdminIds($ids);
            $arr = array_merge($arr, $ids);
        }
        return $arr;
    }

}