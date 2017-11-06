<?php
namespace Ag\Controller;
class IndexController extends BaseController {
    public function index(){
    	// 跑马灯
    	$content = M('notice')->where(['status'=>1])->getField('content');
    	$this->assign('content',$content);
        $this->display();
    }
}