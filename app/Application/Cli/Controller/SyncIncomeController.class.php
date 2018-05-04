<?php
namespace Cli\Controller;
use Think\Controller;

class SyncIncomeController extends Controller {

	private $admin_user;
	private $rate;

	// */1 * * * * /usr/local/php/bin/php /var/www/app/index.php Cli/SyncIncome/index
	public function index() {
		$this->admin_user = M('admin_user');
		$this->rate = getConfig('rate');//回佣率
		$betLog = M('bet_log');
		$adminIncome = M('admin_income');
		$adminUser = M('admin_user');
		$adminWasteBook = M('admin_waste_book');
		$list = $betLog->where(['sync'=> 0])->field('id,user_id,issue,is_host,bet_balance,profit_balance,commission,add_time')->select();
		foreach ($list as $key => $value) {
			$win_balance = bcdiv($value['commission'], $this->rate, 2);  //抽水总钱；
			/*
			if (C('PATTERN') == 1) {
				$win_balance = bcmul($win_balance, 2, 2);
			}
			*/
			
			//查询当前用户所有代理用户，取用户的基础数据来算起			
			$adminList = $this->getAdminList($value['user_id'], $value['commission']);
			//print_r($adminList);exit ;
			foreach ($adminList as $k => $v) {
				$adminIncome->add([
					'admin_id' => $v['user_id'],
					'bet_id' => $value['id'],
					'user_id' => $value['user_id'],
					'issue' => $value['issue'],
					'is_host' => $value['is_host'],
					'bet_balance' => $value['bet_balance'],
					'profit_balance' => $value['profit_balance'],
					'win_balance' => $win_balance,
					'commission' => $v['commission'],
					'add_time' => time()
				]);
				$before_balance = $adminUser->where(['user_id'=>$v['user_id']])->getField('balance');
				$after_balance = bcadd($before_balance, $v['commission'], 2);
				$adminUser->where(['user_id'=>$v['user_id']])->save(['balance'=> $after_balance]);
				// 插入流水表
				$adminWasteBook->add([
					'user_id'=> $v['user_id'],
					'before_balance'=> $before_balance,
					'after_balance'=> $after_balance,
					'change_balance'=> $v['commission'],
					'type'=> 1,
					'add_time'=> $value['add_time'],
				]);
			}
			// 同步下注表状态
			$betLog->where(['id'=> $value['id']])->save(['sync'=> 1]);
		}
	}

	// 根据用户ID获取全部的代理ID和佣金率
	private function getAdminList($user_id, $all_commission) {
		// 找出user对应的所有的代理
		$invite_code = M('user')->where(['user_id'=> $user_id])->getField('invite_code');  //找出用户的上一级直接代理；
		
		//第一级代理		
		$upDailiInfo = $this->admin_user->where(['invite_code'=> $invite_code])->field('user_id,pid,rate')->find();
		$uuid = isset($upDailiInfo["user_id"]) ? intval($upDailiInfo["user_id"]) : 0 ; //用户ID
		$upid = isset($upDailiInfo["pid"]) ? intval($upDailiInfo["pid"]) : 0 ; //上级代理
		$adminList = false ;		
		if(!empty($upDailiInfo) && $uuid >0) {
			$targetArr = array($uuid => $upDailiInfo);
			//取所有的上级代理
			$adminList = $this->getAdminRateList($targetArr,$upid);
		}
		if(!$adminList) {			
			return false ;
		}
		
		$sizeNum = sizeof($adminList) ;
		$win_UserMoney  = bcdiv($all_commission, $this->rate, 2);  //抽水总钱；
		//var_dump($all_commission . '  ==='.$win_balance );
		
		// 分钱
		$tempSum = 0;
		$last_rate = 0;
		$tempIndex = 0 ;
		foreach ($adminList as $key => $value) {
			$userComs = 0; //其它代理用点结算
			if($tempIndex < $sizeNum - 1 ){
					//从上一级开始算起；
					$thisRate = isset($value['rate'] ) ? bcsub($value['rate'] , $last_rate,3 ) : 0 ;
					$userRate = ($thisRate) ?  bcdiv($thisRate  , 100, 5 ) : 0;			
					$userComs =  bcmul( $win_UserMoney  ,$userRate, 2 ) ;  //用户抽水点
					//$commission = bcdiv(bcmul(bcdiv($all_commission, $this->rate, 2), $value['rate'], 2), 100, 2);
					$tempSum = bcadd($tempSum, $userComs, 2);
					//var_dump($userComs . '  ==='.$userRate );
			}else{
					$userComs = bcsub($all_commission, $tempSum, 2);  //最后一个是公司入账
			}
			$last_rate = $value['rate'];
			$adminList[$key]['commission'] = $userComs;  //$commission  用户抽水金额
			$tempIndex ++ ;
		} 		
		//print_r($adminList);exit ;
		return $adminList;
	}

	// 递归获取代理列表
	private function getAdminRateList(& $adminList, $pid) {		
		if(isset($pid) && intval($pid) >0){  //有效用户
			$adminInfo = $this->admin_user->where(['user_id'=> $pid])->field('user_id,pid,rate')->find();
			$upid = isset($adminInfo["pid"]) ? intval($adminInfo["pid"]) : 0 ; //上级代理
			if (!empty($adminInfo) && isset($upid)) {
				
				//添加新的代理进去；
				$adminList[$pid] = $adminInfo;				
				if(isset($upid) && intval($upid) >0){  //有效用户
					$this->getAdminRateList($adminList,$upid);
				} 
			}			
		} 
		return $adminList;
	}
}
