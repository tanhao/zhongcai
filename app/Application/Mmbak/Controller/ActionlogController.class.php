<?php
namespace Mmbak\Controller;
use Mmbak\Controller\BaseController;
class ActionlogController extends BaseController {
    public function index() {
    	$name = I('get.name');
    	$action_name = I('get.action_name');
    	$where = [];
    	if (!empty($name)) {
    		$where['name'] = $name;
    	}
    	if (!empty($action_name)) {
    		$where['action_name'] = $action_name;
    	}
        $count = M('mm_action_log')->where($where)->count();
        $PageObject = new \Think\Page($count,15);
        $list = M('mm_action_log')->where($where)->order('id desc')->limit($PageObject->firstRow.','.$PageObject->listRows)->select();
        foreach ($list as $key => $value) {
        	if (!empty($value['get_param'])) {
        		$get_param = json_decode($value['get_param'], true);
        		$get_str = [];
        		foreach ($get_param as $k => $v) {
        			$get_str[] = $k.':'.$v;
        		}
        		$list[$key]['get_param'] = implode('<br>', $get_str);
        	}
        	if (!empty($value['post_param'])) {
        		$post_param = json_decode($value['post_param'], true);
        		$post_str = [];
        		foreach ($post_param as $k => $v) {
        			$post_str[] = $k.':'.$v;
        		}
        		$list[$key]['post_param'] = implode('<br>', $post_str);
        	}
        }
        $this->assign('name', $name);
        $this->assign('action_name', $action_name);
        $this->assign('list', $list);
        $this->assign('page_show', $PageObject->show());
        $this->display();
    }
}