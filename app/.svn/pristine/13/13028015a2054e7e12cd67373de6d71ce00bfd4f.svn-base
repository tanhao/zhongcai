<?php
namespace Adminbak\Controller;
use Adminbak\Controller\BaseController;
class AgentController extends BaseController {
    private $rateArr1 = [2,1,0.8,0.6,0.4];
    private $rateArr2 = [0.4,0.35,0.3,0.25,0.2,0.15,0.1,0.05];

    public function index() {
        $user_name = I('get.user_name');
        $admin_user = M('admin_user');
        $adminInfo = $admin_user->where(['user_name'=> session('admin_name')])->find();
        // 查询条件
        $where = ['pid'=> $adminInfo['user_id']];
        if (!empty($user_name)) $where['user_name'] = $user_name;
        $count = $admin_user->where($where)->count();
        $PageObject = new \Think\Page($count,15);
        $adminList = $admin_user->where($where)->order('user_id asc')->limit($PageObject->firstRow.','.$PageObject->listRows)->field("user_id,user_name,rate")->select();
        $draw_cash = M('draw_cash');
        $admin_income = M('admin_income');
        foreach ($adminList as $key => $value) {
            // 提款金额
            $apply_cash = $draw_cash->where(['type'=>2,'user_id'=> $value['user_id'], 'sync'=> ['neq',2]])->sum('apply_cash');
            $adminList[$key]['apply_cash'] = !empty($apply_cash) ? $apply_cash : "0.00";
            // 总收入
            $income = $admin_income->where(['admin_id'=> $value['user_id']])->sum('commission');
            $adminList[$key]['income'] = !empty($income) ? $income : "0.00";
        }
        $rateList = [];
        if ($adminInfo['rate'] > 0.4) {
            foreach ($this->rateArr1 as $value) {
                if ($adminInfo['rate'] > $value) {
                    $rateList[] = $value;
                }
            }
        } else {
            foreach ($this->rateArr2 as $value) {
                if ($adminInfo['rate'] > $value) {
                    $rateList[] = $value;
                }
            }
        }
        $this->assign('user_name', $user_name);
        $this->assign('rateList', $rateList);
        $this->assign('adminList', $adminList);
    	$this->assign('page_show', $PageObject->show());
    	$this->display();
    }

    public function userList() {
        $user_name = I('get.user_name');
        $admin_user = M('admin_user');
        $user = M('user');
        $pid = $admin_user->where(['user_name'=> session('admin_name')])->getField('user_id');
        $adminInfo = $admin_user->where(['user_name'=> $user_name, 'pid'=> $pid])->find();
        if (empty($adminInfo)) {
            $this->error("直属代理不存在");
        }
        // 查询条件
        $where = ['invite_code'=> $adminInfo['invite_code']];
        $count = $user->where($where)->count();
        $PageObject = new \Think\Page($count,15);
        $userList = $user->where($where)->limit($PageObject->firstRow.','.$PageObject->listRows)->field("user_id,user_name,nickname")->select();
        $apply_cash_model = M('apply_cash');
        $bet_log = M('bet_log');
        foreach ($userList as $key => $value) {
            // 提款金额
            $apply_cash = $apply_cash_model->where(['user_id'=> $value['user_id'], 'sync'=> 1])->sum('apply_cash');
            $userList[$key]['apply_cash'] = !empty($apply_cash) ? $apply_cash : "0.00";
            // 总收入
            $income = $bet_log->where(['user_id'=> $value['user_id']])->sum('profit_balance');
            $userList[$key]['income'] = !empty($income) ? $income : "0.00";
            // 帐号名称加*号
            $userList[$key]['nickname'] = mb_substr($value['nickname'], 0, 1, 'utf-8') . "******" . mb_substr($value['nickname'], -1, 1, 'utf-8');
        }
        $this->assign('user_name', $user_name);
        $this->assign('rate', $adminInfo['rate']);
        $this->assign('userList', $userList);
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
        $pid = M('admin_user')->where(['user_name'=> session('admin_name')])->getField('user_id');
        $adminInfo = M('admin_user')->where(['user_name'=> $user_name, 'pid'=> $pid])->find();
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
            'action_name'=> session("admin_name"),
            'type'=> 2,
            'add_time'=> time(),
        ]);
        // 成功返回
        exit(json_encode([
            'code' => 0,
            'msg' => "success!",
            'data' => [],
        ]));
    }

    public function addAdmin() {
        $user_name = I('post.user_name');
        $password = I('post.password');
        $repassword = I('post.repassword');
        $rate = I('post.rate');
        if (empty($user_name) || empty($password) || empty($repassword) || empty($rate)) {
            $this->error("参数不完整");
        }
        if (!preg_match('/^[A-Za-z0-9]{6,15}$/', $user_name)) {
            $this->error("账号必须是6-15位英文字母或数字");
        }
        if (!preg_match('/^[A-Za-z0-9]{6,15}$/', $password)) {
            $this->error("密码必须是6-15位英文字母或数字");
        }
        if ($password != $repassword) {
            $this->error("两次输入的密码不一致");
        }
        $admin_user = M('admin_user');
        $adminInfo = M('admin_user')->where(['user_name'=> session('admin_name')])->find();
        $rate = sprintf('%.2f', $rate);
        if ($adminInfo['rate'] < $rate) {
            $this->error("添加的代理回佣比率不能高于当前帐号");
        }
        if ($adminInfo['rate'] > 0.4 && !in_array((float)$rate, $this->rateArr1)) {
            $this->error("添加的代理回佣比率不合法");
        }
        if ($adminInfo['rate'] <= 0.4 && !in_array((float)$rate, $this->rateArr2)) {
            $this->error("添加的代理回佣比率不合法");
        }
        if ($admin_user->where(['user_name'=> $user_name])->count()) {
            $this->error("该代理帐号已存在，请重新输入");
        }
        $admin_user->add([
            'user_name'=> $user_name,
            'password'=> md5($password),
            'pid'=> $adminInfo['user_id'],
            'rate'=> $rate,
            'add_time'=> time(),
        ]);
        $this->success("添加成功", U('Agent/index'));
    }
}