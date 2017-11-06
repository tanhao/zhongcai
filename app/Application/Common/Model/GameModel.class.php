<?php
namespace Common\Model;
use Think\Model;
use Lib\Enum\CacheEnum;

class GameModel extends Model {
	// 获取彩票列表 0-全部，1-北京赛车，2-重庆时时彩，3-幸运飞艇
	public function getGameList($lottery_id = 0) {
		$redis = redisCache();
		$gameList = $redis->get(CacheEnum::GAME_LIST);
		if (empty($gameList)) {
			$gameList = $this->where(['status'=>1])->field('game_id,game_name,lottery_id,must_host,zone_count')->select();
			$redis->set(CacheEnum::GAME_LIST, serialize($gameList));
		} else {
			$gameList = unserialize($gameList);
		}
		$result = array();
		foreach ($gameList as $key => $value) {
			if ($lottery_id == 0 || $value['lottery_id'] == $lottery_id) {
				$result[] = $value;
			}
		}
		return $result;
	}

	// 获取游戏信息
	public function getGameInfo($game_id) {
		$redis = redisCache();
		$gameInfo = $redis->get(CacheEnum::GAME_INFO.$game_id);
		if (empty($gameInfo)) {
			$gameInfo = $this->where(['status'=>1,'game_id'=>$game_id])->field('game_id,game_name,lottery_id,must_host,zone_count')->find();
			$redis->set(CacheEnum::GAME_INFO.$game_id, serialize($gameInfo));
		} else {
			$gameInfo = unserialize($gameInfo);
		}
		return !empty($gameInfo) ? $gameInfo : [];
	}
}