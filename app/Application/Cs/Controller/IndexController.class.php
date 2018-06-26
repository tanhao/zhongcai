<?php
namespace Cs\Controller;
class IndexController extends BaseController {
    public function index(){
    	$user_auth = explode(',', $this->csUserInfo['auth']);
    	$list = [];
    	foreach ($this->module as $key => $value) {
    		$temp_arr = [];
            foreach ($value['list'] as $k => $v) {
            	if (in_array($v, $user_auth)) {
            		$temp_arr[] = [
		                'id'	=> $v,
		                'name'	=> $this->auth[$v]['name'],
		                'url'	=> U(str_replace('-', '/', $this->auth[$v]['list'][0])),
	                ];
            	}
            }
            if (!empty($temp_arr)) {
            	$list[] = [
            		'name'=> $value['name'],
            		'list'=> $temp_arr,
            	];
            }
        }
        $rCount = M('recharge')->where("type=1 and sync=0 and (mm_name='' or mm_name='{$this->csUserInfo['user_name']}')")->count();
        $dCount = M('draw_cash')->where(['sync'=>0])->count();
        $this->assign('rCount', $rCount);
        $this->assign('dCount', $dCount);
    	$this->assign('list', $list);
        $this->display();
    }

    public function home() {
        // 代理人数
        $agent_count = M('admin_user')->count();
        $agent_count = !empty($agent_count) ? $agent_count : 0;
        // 会员人数
        $user_count = M('user')->count();
        $user_count = !empty($user_count) ? $user_count : 0;
        // 在线人数
        $online_count = M('user_token')->where(['online'=>1,'is_temp'=>0])->count();
        $online_count = !empty($online_count) ? $online_count : 0;
        // 充值金额
        $recharge_balance = M('recharge')->where(['sync'=>1])->sum('real_cash');
        $recharge_balance = !empty($recharge_balance) ? $recharge_balance : "0.00";
        // 代理提现
        $agent_draw_balance = M('draw_cash')->where(['type'=>2,'sync'=>1])->sum('real_cash');
        $agent_draw_balance = !empty($agent_draw_balance) ? $agent_draw_balance : "0.00";
        // 会员提现
        $user_draw_balance = M('draw_cash')->where(['type'=>1,'sync'=>1])->sum('real_cash');
        $user_draw_balance = !empty($user_draw_balance) ? $user_draw_balance : "0.00";
        // 平台总佣金
        $total_commission = M('admin_income')->sum('commission');
        $total_commission = !empty($total_commission) ? $total_commission : "0.00";
		
        // 公司总收入
        $user_id = M('admin_user')->where(['pid'=>0])->getField('user_id');
        $my_commission = M('admin_income')->where(['admin_id'=>$user_id])->sum('commission');
        $my_commission = !empty($my_commission) ? $my_commission : "0.00";

        $this->assign('agent_count', $agent_count);
        $this->assign('user_count', $user_count);
        $this->assign('online_count', $online_count);
        $this->assign('recharge_balance', $recharge_balance);
        $this->assign('agent_draw_balance', $agent_draw_balance);
        $this->assign('user_draw_balance', $user_draw_balance);
        $this->assign('total_commission', $total_commission);
        $this->assign('my_commission', $my_commission);
        $this->display();
    }

    public function payWaiting() {
        if (IS_POST) {
            $rCount = M('recharge')->where("type=1 and sync=0 and (mm_name='' or mm_name='{$this->csUserInfo['user_name']}')")->count();
            $dCount = M('draw_cash')->where(['sync'=>0])->count();
            $this->ajaxOutput('success', 1, '', [
                'rCount'=> $rCount,
                'dCount'=> $dCount,
            ]);
        }
    }
}