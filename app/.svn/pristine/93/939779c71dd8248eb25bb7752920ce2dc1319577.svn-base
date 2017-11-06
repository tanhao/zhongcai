<?php
namespace Mmbak\Controller;
use Mmbak\Controller\BaseController;
use Lib\Enum\CacheEnum;
class StopController extends BaseController {
    public function server(){
        $model = M('stop_server');
        $count = $model->count();
        $PageObject = new \Think\Page($count,15);
        $list = $model->order('id desc')->limit($PageObject->firstRow.','.$PageObject->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page_show', $PageObject->show());
        $this->display();
    }

    public function stopServer(){
        $status = I('status', 0, 'intval');
        $stop_server = M('stop_server');
        $info = $stop_server->where(['status'=>0])->find();
        
        if ($status == 0) {
            if (!empty($info)) {
                $this->error('现在已经是停服状态，请勿重复操作');
            }
            $stop_server->add([
                'status'=> 0,
                'start_user'=> session('mm_name'),
                'start_time'=> time(),
                'end_user'=> '',
                'end_time'=> 0,
            ]);
            $msg = '停服成功';
        } else {
            if (empty($info)) {
                $this->error('已经结束停服，请勿重复操作');
            }
            $stop_server->where(['id'=>$info['id']])->save([
                'status'=> 1,
                'end_user'=> session('mm_name'),
                'end_time'=> time(),
            ]);
            $msg = '结束停服成功';
        }
        $redis = redisCache();
        $stop_value = $redis->delete(CacheEnum::STOP_SERVER);
        $this->success($msg, U('Stop/server'));
    }

    public function pay(){
        $where = ['type'=> 2];
        $model = M('stop_server');
        $count = $model->where($where)->count();
        $PageObject = new \Think\Page($count,15);
        $list = $model->where($where)->order('id desc')->limit($PageObject->firstRow.','.$PageObject->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page_show', $PageObject->show());
        $this->display();
    }

    public function stopPay(){
        $status = I('status', 0, 'intval');
        $stop_server = M('stop_server');
        $info = $stop_server->where(['type'=> 2, 'status'=>0])->find();
        if ($status == 0) {
            if (!empty($info)) {
                $this->error('现在已经是关闭支付状态，请勿重复操作');
            }
            $stop_server->add([
                'status'=> 0,
                'start_user'=> session('mm_name'),
                'start_time'=> time(),
                'end_user'=> '',
                'end_time'=> 0,
                'type'=> 2,
            ]);
            $this->success('停服成功', U('Stop/server'));
        } else {
            if (empty($info)) {
                $this->error('已经结束停服，请勿重复操作');
            }
            $stop_server->where(['id'=>$info['id']])->save([
                'status'=> 1,
                'end_user'=> session('mm_name'),
                'end_time'=> time(),
            ]);
            $this->success('结束停服成功', U('Stop/server'));
        }
    }
}