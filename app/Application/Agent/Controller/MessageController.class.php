<?php
namespace Agent\Controller;
class MessageController extends BaseController {
    public function index(){
    	$notice = M('notice');
        $list = $notice->select();
        $this->assign('list', $list);
        $this->display();
    }
}