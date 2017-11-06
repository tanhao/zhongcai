<?php
namespace Cli\Controller;
use Think\Controller;
use Lib\Enum\CacheEnum;

class PublicController extends Controller {

	private $admin_user;

	// 0 6 * * * /usr/local/php/bin/php /var/www/app/index.php Cli/Public/index
	public function index() {
		$userToken = M('user_token');
		$time = time() - 86400;
		$userToken->where(['is_temp'=> 1, 'add_time' => ['lt', $time]])->delete();
	}

	// */1 * * * * /usr/local/php/bin/php /var/www/app/index.php cli/public/userWater
	public function userWater() {
		$redis = redisCache();
		$list = $redis->lrange(CacheEnum::USER_WATER, 0, -1);
		$user = M('user');
		$admin_user = M('admin_user');
		$user_water = M('user_water');
		foreach ($list as $key => $value) {
			$value = json_decode($value, true);
			$invite_code = $user->where(['user_id'=> $value['user_id']])->getField('invite_code');
			$admin_id = $admin_user->where(['invite_code'=> $invite_code])->getField('user_id');
            $user_water->add([
                'user_id'=> $value['user_id'],
                'admin_id'=> $admin_id,
                'balance'=> $value['balance'],
                'add_time'=> $value['add_time'],
            ]);
		}
		$redis->delete(CacheEnum::USER_WATER);
	}
}