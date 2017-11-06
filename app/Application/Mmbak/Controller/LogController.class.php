<?php
namespace Mmbak\Controller;
use Mmbak\Controller\BaseController;
class LogController extends BaseController {
    public function log() {
        $count = M('admin_login_log')->where(['user_name'=> session('mm_name'), 'type'=>2])->count();
        $PageObject = new \Think\Page($count,15);
        $list = M('admin_login_log')->where(['user_name'=> session('mm_name'), 'type'=>2])->order('id desc')->limit($PageObject->firstRow.','.$PageObject->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page_show', $PageObject->show());
        $this->display();
    }
}