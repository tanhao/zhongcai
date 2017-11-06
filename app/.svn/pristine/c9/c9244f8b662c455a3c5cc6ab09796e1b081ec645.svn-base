<?php
namespace Cs\Model;
use Think\Model;

class AdminUserModel extends Model {
	public function getAllUpAgent($user_id) {
		$result = $this->getParentAgent($user_id);
		krsort($result);
		if (empty($result)) {
			return '';
		}
		$temp = [];
		foreach ($result as $key => $value) {
			$temp[] = $value['user_name'];
		}
		return implode('->', $temp);
	}

	public function getParentAgent($user_id, $result=[]) {
		if ($user_id != 0) {
			$userInfo = $this->where(['user_id'=>$user_id])->field('user_name,pid')->find();
			$result[] = ['user_id'=> $user_id, 'user_name'=> $userInfo['user_name']];
			$result = $this->getParentAgent($userInfo['pid'],$result);
		}
		return $result;
	}
}