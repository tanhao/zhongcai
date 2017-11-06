<?php
namespace Mmbak\Controller;
use Think\Controller;
class BaseController extends Controller {
    public function _initialize() {
    	$mm_name = session('mm_name');
    	if (empty($mm_name)) {
    		$this->redirect('Login/login');
    	}
    	$this->assign('mm_name', $mm_name);
    	$this->assign('year', date('Y'));
    	$this->assign('monthday', date('m月d日'));
    	$this->assign('controller_name', CONTROLLER_NAME);

        $privilege = ['Agent','Draw','Index','Lottery','Mm','Report','Stat','System','User','Log','Account','Stop','Message','Actionlog'];
        // if (session('is_super') == 0) {
        //     $level = M('mm_user')->where(['user_name'=>$mm_name])->getField('level');
        //     if (!in_array(CONTROLLER_NAME, $this->privilege[$level])) {
        //         $this->error('抱歉，您的帐号权限无法打开该页面，请联系高级管理员');
        //     }
        //     $privilege = $this->privilege[$level];
        // }
        $this->assign('privilege', $privilege);
        // 添加管理员操作日志
        D('MmActionLog')->addActionLog();
    }

    // 1.超管：最高权限，能进行后台的所有功能操作；
    // 2.运维：可操作功能  充值管理页面、提现管理页面、站内消息页面、历史开奖页面；
    // 3.客服：可操作功能  充值管理页面、提现管理页面
    private $privilege = [
        1 => [
            'Agent','Draw','Index','Lottery','Mm','Report','Stat','System','User','Log','Account','Account','Stop'
        ],
        2 => [
            'Draw','Index','Lottery','System','Log','Account','Stop'
        ],
        3 => [
            'Draw','Index','Log','Account','Stop'
        ],
    ];
}