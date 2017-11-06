<?php
namespace Mmbak\Controller;
use Mmbak\Controller\BaseController;
use Lib\Enum\CacheEnum;
use Lib\Enum\CodeEnum;
class SystemController extends BaseController {
    public function index() {
        if (IS_POST) {
            $system_maintenance = I('post.system_maintenance', 0, 'intval');
            $online_pay = I('post.online_pay', 0, 'intval');
            $announcement = I('post.announcement', '', 'htmlspecialchars,trim');
            $announcement_url = I('post.announcement_url', '', 'htmlspecialchars,trim');
            $cs_qq = I('post.cs_qq', '', 'htmlspecialchars,trim');
            $cs_wx = I('post.cs_wx', '', 'htmlspecialchars,trim');
            $rate = I('post.rate', '', 'htmlspecialchars,floatval');
            $app_id = I('post.app_id', '', 'htmlspecialchars,trim');
            $app_secret = I('post.app_secret', '', 'htmlspecialchars,trim');
            $store_id = I('post.store_id', '', 'htmlspecialchars,trim');
            $free_draw_times = I('post.free_draw_times', 0, 'intval');
            if (!in_array($system_maintenance, [0,1]) || !in_array($online_pay, [0,1]) || empty($announcement) || empty($announcement_url) || empty($cs_qq) || empty($cs_wx) || $rate >= 1 || $rate <= 0 || empty($app_id) || empty($app_secret) || empty($store_id) || $free_draw_times < 1) {
                $this->error('参数错误');
            }
            M('config')->where(['config_sign'=>'system_maintenance'])->save(['config_value'=> $system_maintenance]);
            M('config')->where(['config_sign'=>'online_pay'])->save(['config_value'=> $online_pay]);
            M('config')->where(['config_sign'=>'announcement'])->save(['config_value'=> $announcement]);
            M('config')->where(['config_sign'=>'announcement_url'])->save(['config_value'=> $announcement_url]);
            M('config')->where(['config_sign'=>'cs_qq'])->save(['config_value'=> $cs_qq]);
            M('config')->where(['config_sign'=>'cs_wx'])->save(['config_value'=> $cs_wx]);
            M('config')->where(['config_sign'=>'rate'])->save(['config_value'=> $rate]);
            M('config')->where(['config_sign'=>'app_id'])->save(['config_value'=> $app_id]);
            M('config')->where(['config_sign'=>'app_secret'])->save(['config_value'=> $app_secret]);
            M('config')->where(['config_sign'=>'store_id'])->save(['config_value'=> $store_id]);
            M('config')->where(['config_sign'=>'free_draw_times'])->save(['config_value'=> $free_draw_times]);
            $redis = redisCache();
            $redis->delete(CacheEnum::CONFIG);
            $systemInfo = M('config')->select();
            $config[] = [];
            foreach ($systemInfo as $key => $value) {
                $config[$value['config_sign']] = $value['config_value'];
            }
            $redis->set(CacheEnum::CONFIG, json_encode($config));
            if ($system_maintenance == 1) {
                sendToAll(CodeEnum::SERVER_ERROR,[
                    'announcement'=>$announcement,
                ]);
            }
            $this->success('保存设置成功', U('System/index'));
        } else {
            $list = M('config')->select();
            // $list = [];
            // foreach ($systemInfo as $key => $value) {
            //     $list[$value['config_sign']] = $value['config_value'];
            // }
            $this->assign('list', $list);
            $this->display();
        }
    }
}