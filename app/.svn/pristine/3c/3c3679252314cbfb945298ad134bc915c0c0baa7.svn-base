<?php
namespace Common\Model;
use Think\Model;
use Lib\Enum\CacheEnum;

class SiteModel extends Model {
	// 获取场所列表
	public function getSiteList($game_id=1) {
		$redis = redisCache();
		$siteList = $redis->get(CacheEnum::SITE_LIST . $game_id);
		if (empty($siteList)) {
			$siteList = $this->where(['game_id'=>$game_id])->select();
			$redis->set(CacheEnum::SITE_LIST . $game_id, serialize($siteList));
		} else {
			$siteList = unserialize($siteList);
		}
		return $siteList;
	}

	// 获取场所信息
	public function getSiteInfo($site_id) {
		$redis = redisCache();
		$siteInfo = $redis->get(CacheEnum::SITE_INFO . $site_id);
		if (empty($siteInfo)) {
			$siteInfo = $this->where(['site_id'=> $site_id])->find();
			$redis->set(CacheEnum::SITE_INFO . $site_id, serialize($siteInfo));
		} else {
			$siteInfo = unserialize($siteInfo);
		}
		return $siteInfo;
	}

	// 获取房间在线人数 游戏Id-场管id
	public function getRoomCount($room_id) {
		$result = M('user_token')->where([
			'online'=> 1, 
			'room_id'=> $room_id,
		])->count();
		return $result ? $result : 0;
	}
}