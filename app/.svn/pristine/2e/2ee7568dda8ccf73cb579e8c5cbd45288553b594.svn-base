<?php
namespace Mmbak\Controller;
use Mmbak\Controller\BaseController;
class UserController extends BaseController {
    public function index() {
        $nickname = I('get.nickname');
        $user = M('user');
        if (!empty($nickname)) {
            $where['nickname'] = $nickname;
        }
        $count = $user->where($where)->count();
        $PageObject = new \Think\Page($count,15);
        $userList = $user->where($where)->order('user_id asc')->limit($PageObject->firstRow.','.$PageObject->listRows)->field("user_id,nickname,balance,status")->select();
        foreach ($userList as $key => $value) {
            // 收益
            $profit_balance = M('bet_log')->where(['user_id'=> $value['user_id']])->sum('profit_balance');
            $userList[$key]['profit_balance'] = !empty($profit_balance) ? $profit_balance : "0.00";
            // 密码修改操作人
            $action_name = M('edit_password_log')->where(['user_id'=> $value['user_id'], 'is_agent'=>0])->order('id desc')->getField('action_name');
            $userList[$key]['action_name'] = !empty($action_name) ? $action_name : '';
        }
        $this->assign('nickname', $nickname);
        $this->assign('userList', $userList);
        $this->assign('page_show', $PageObject->show());
        $this->display();
    }

    public function editPassword() {
        $user_id = I('post.user_id', 0, 'intval');
        $password = I('post.password', '', 'trim');
        $pay_password = I('post.pay_password', '', 'trim');
        if ($user_id < 1 || empty($password) || empty($pay_password)) {
            exit(json_encode([
                'code' => 1,
                'msg' => "参数错误",
                'data' => [],
            ]));
        }
        $userInfo = M('user')->where(['user_id'=> $user_id])->find();
        if (empty($userInfo)) {
            exit(json_encode([
                'code' => 1,
                'msg' => "用户不存在",
                'data' => [],
            ]));
        }
        M('user')->where(['user_id'=> $userInfo['user_id']])->save(['password' => md5($password), 'pay_password' => md5($pay_password)]);
        // 记录修改日志
        M('edit_password_log')->add([
            'user_id'=> $userInfo['user_id'], 
            'is_agent'=> 0,
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
        M('user')->where(['user_id'=> $user_id])->save([
            'status'=> $status
        ]);
        $msg = $status == 1 ? "解冻成功" : "冻结成功";
        $this->success($msg, U('User/index'));
    }

    public function stopLogin() {
        $user_id = I('get.user_id', 0, 'intval');
        $status = I('get.status', 0, 'intval');
        if (empty($user_id) || !in_array($status, [0,1])) {
            $this->error('参数错误');
        }
        M('user')->where(['user_id'=> $user_id])->save([
            'login_status'=> $status
        ]);
        $msg = $status == 1 ? "允许登录成功" : "禁止登录成功";
        $this->success($msg, U('User/index'));
    }

    public function delete() {
        $user_id = I('get.user_id', 0, 'intval');
        if (empty($user_id)) {
            $this->error('参数错误');
        }
        $user = M('user');
        $admin_user = M('admin_user');
        $userInfo = $user->where(['user_id'=> $user_id])->find();
        if (empty($userInfo)) {
            $this->error('用户不存在');
        }
        if ($userInfo['balance'] > 0) {
            $adminInfo = $admin_user->where(['pid'=>0])->find();
            $balance = bcadd($adminInfo['balance'], $userInfo['balance'], 2);
            $admin_user->where(['pid'=>0])->save(['balance'=> $balance]);
        }
        M('delete_user')->add($userInfo);
        $user->where(['user_id'=> $user_id])->delete();
        $this->success('删号成功', U('User/index'));
    }
}