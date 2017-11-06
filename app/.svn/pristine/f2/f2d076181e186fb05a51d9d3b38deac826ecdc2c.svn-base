<?php
namespace Mmbak\Controller;
use Mmbak\Controller\BaseController;
use Lib\Enum\CodeEnum;
class MessageController extends BaseController {
    public function index() {
        if (IS_POST) {
            $content = I('post.content');
            $content = trim($content);
            if (empty($content)) {
                $this->error('公告内容不能为空');
            }
            M('notice')->add([
                'content'=> $content,
                'user_name'=> session('mm_name'),
                'add_time'=> time(),
            ]);
            sendToAll(CodeEnum::PUSH_NOTICE, ['notice'=> $content]);
            $this->success('添加公告成功', U('Message/index'));
        } else {
            $count = M('notice')->count();
            $PageObject = new \Think\Page($count,15);
            $list = M('notice')->order('id desc')->limit($PageObject->firstRow.','.$PageObject->listRows)->select();
            $this->assign('list', $list);
            $this->assign('page_show', $PageObject->show());
            $this->display();
        }
    }
}