<?php
namespace Mmbak\Model;
use Think\Model;

class MmActionLogModel extends Model {
	// 添加管理员操作日志
	public function addActionLog() {
		$post_param = I('post.');
		$post_param = !empty($post_param) ? json_encode($post_param, JSON_UNESCAPED_UNICODE) : '';
		$get_param = I('get.');
		unset($get_param['p']);
		$get_param = !empty($get_param) ? json_encode($get_param, JSON_UNESCAPED_UNICODE) : '';
		$nameConfig = $this->getNameConfig();
		if (isset($nameConfig[CONTROLLER_NAME.'-'.ACTION_NAME])) {
			$this->add([
				'name'=> $nameConfig[CONTROLLER_NAME.'-'.ACTION_NAME],
	            'action_name'=> session('mm_name'),
	            'controller'=> CONTROLLER_NAME,
	            'action'=> ACTION_NAME,
	            'get_param'=> $get_param,
	            'post_param'=> $post_param,
	            'add_time'=> time(),
			]);
		}
	}

	private function getNameConfig() {
		$result = [
			'Account-useBank'=> '使用银行卡',
			'Account-addBank'=> '添加银行卡',
			'Agent-editPassword'=> '修改代理密码',
			'Agent-freeze'=> '修改代理冻结状态',
			'Agent-stopLogin'=> '修改代理禁止登录状态',
			'Agent-delete'=> '删除代理',
			'Draw-userList'=> IS_POST ? '给提现玩家转帐' : null,
			'Draw-adminList'=> IS_POST ? '给提现代理转帐' : null,
			'Index-recharge'=> '给充值玩家充余额',
			'Lottery-openLottery'=> '人工开奖',
			'Message-index'=> IS_POST ? '添加公告' : null,
			'Mm-editPassword'=> '修改员工帐号密码',
			'Mm-addMmUser'=> '添加员工帐号',
			'System-index'=> '修改系统设置',
			'User-editPassword'=> '修改玩家密码',
			'User-freeze'=> '修改玩家冻结状态',
			'User-stopLogin'=> '修改玩家禁止登录状态',
			'User-delete'=> '删除玩家',
		];
		return $result;
	}

}