<?php
namespace Mmbak\Controller;
use Mmbak\Controller\BaseController;
class MmController extends BaseController {
    public function index() {
        $mm_user = M('mm_user');
        $where = session('is_super') == 0 ? ['level'=>['neq', 1]] : [];
        $count = $mm_user->where($where)->count();
        $PageObject = new \Think\Page($count,15);
        // 客服列表
        $list = $mm_user->where($where)->order('user_id asc')->limit($PageObject->firstRow.','.$PageObject->listRows)->field("user_id,user_name,password,level")->select();
        foreach ($list as $key => $value) {
            switch ($value['level']) {
                case '1':
                    $level_name = '超管';break;
                case '2':
                    $level_name = '运维';break;
                case '3':
                    $level_name = '客服';break;
            }
            $list[$key]['level_name'] = $level_name;
        }
        // 可以添加的等级
        $levelList = [];
        if (session('is_super') == 1) {
            $levelList[] = ['level'=>1,'level_name'=>'超管','selected'=>0];
        }
        $levelList[] = ['level'=>2,'level_name'=>'运维','selected'=>0];
        $levelList[] = ['level'=>3,'level_name'=>'客服','selected'=>0];
        $levelList[0]['selected'] = 1;

        $this->assign('list', $list);
        $this->assign('levelList', $levelList);
        $this->assign('page_show', $PageObject->show());
        $this->display();
    }

    public function editPassword() {
        $user_name = I('post.user_name', '', 'trim');
        $password = I('post.password', '', 'trim');
        if (empty($user_name) || empty($password)) {
            exit(json_encode([
                'code' => 1,
                'msg' => "参数错误",
                'data' => [],
            ]));
        }
        $userInfo = M('mm_user')->where(['user_name'=> $user_name])->find();
        if (empty($userInfo)) {
            exit(json_encode([
                'code' => 1,
                'msg' => "用户不存在",
                'data' => [],
            ]));
        }
        if ($userInfo['level'] == 1 && session('is_super') == 0) {
            exit(json_encode([
                'code' => 1,
                'msg' => "权限不够",
                'data' => [],
            ]));
        }
        M('mm_user')->where(['user_name'=> $user_name])->save(['password' => $password]);
        // 成功返回
        exit(json_encode([
            'code' => 0,
            'msg' => "success!",
            'data' => [],
        ]));
    }

    public function addMmUser() {
        $level = I('post.level', 0, 'intval');
        $user_name = I('post.user_name','','htmlspecialchars,trim');
        $password = I('post.password','','htmlspecialchars,trim');
        if (!in_array($level, [1,2,3]) || empty($user_name) || empty($password)) {
            $this->error('参数错误', U('Mm/index'));
        }
        if (session('is_super') == 0 && $level == 1) {
            $this->error('超管不能新增超管用户', U('Mm/index'));
        }
        // 获取总公司帐号
        $total_account = M('admin_user')->where('pid=0')->getField('user_name');
        if ($total_account == $user_name || M('mm_user')->where(['user_name'=> $user_name])->count()) {
            $this->error('该帐号已被占用，请重新输入', U('Mm/index'));
        }
        M('mm_user')->add([
            'user_name'=> $user_name,
            'password'=> $password,
            'level'=> $level,
            'add_time'=> time(),
        ]);
        $this->success('新增成功', U('Mm/index'));
    }
}