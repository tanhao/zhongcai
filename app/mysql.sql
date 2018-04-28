CREATE TABLE `zc_user` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_name` varchar(50) NOT NULL DEFAULT '' COMMENT '用户名',
  `nickname` varchar(50) NOT NULL DEFAULT '' COMMENT '昵称',
  `phone` varchar(20) NOT NULL DEFAULT '' COMMENT '手机号',
  `email` varchar(50) NOT NULL DEFAULT '' COMMENT '电子邮箱',
  `password` varchar(50) NOT NULL DEFAULT '' COMMENT '登录密码',
  `pay_password` varchar(50) NOT NULL DEFAULT '' COMMENT '支付密码',
  `balance` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '元宝',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态：0-冻结 1-正常',
  `invite_code` varchar(10) NOT NULL DEFAULT '' COMMENT '邀请码',
  `add_time` int(11) NOT NULL DEFAULT '0' COMMENT '注册时间',
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `user_name` (`user_name`),
  KEY `invite_code` (`invite_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户表';

CREATE TABLE `zc_user_token` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `token` varchar(50) NOT NULL DEFAULT '' COMMENT '用户TOKEN',
  `client_id` varchar(50) NOT NULL DEFAULT '' COMMENT '客户端ID',
  `user_id` bigint(20) NOT NULL DEFAULT '0' COMMENT '用户ID',
  `is_temp` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否临时用户',
  `online` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否在线',
  `room_id` int(11) NOT NULL DEFAULT '0' COMMENT '房间',
  `add_time` int(11) NOT NULL DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `room_id` (`room_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户TOKEN表';

CREATE TABLE `zc_bank_card` (
  `bank_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户ID',
  `account_number` varchar(50) NOT NULL DEFAULT '' COMMENT '银行卡号',
  `bank_name` varchar(50) NOT NULL DEFAULT '' COMMENT '银行名称',
  `real_name` varchar(50) NOT NULL DEFAULT '' COMMENT '真实姓名',
  `branch_bank` varchar(50) NOT NULL DEFAULT '' COMMENT '开户支行',
  `is_default` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否主卡',
  `is_delete` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否删除',
  `add_time` int(11) NOT NULL DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`bank_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='银行卡表';

CREATE TABLE `zc_lottery` (
  `lottery_id` int(11) NOT NULL AUTO_INCREMENT,
  `lottery_name` varchar(20) NOT NULL DEFAULT '' COMMENT '彩票名称',
  `api_url` varchar(100) NOT NULL DEFAULT '' COMMENT '接口地址',
  `start_time` varchar(20) NOT NULL DEFAULT '' COMMENT '开始时间',
  `end_time` varchar(20) NOT NULL DEFAULT '' COMMENT '结束时间',
  `condition` varchar(255) NOT NULL DEFAULT '' COMMENT '开奖时间详情',
  PRIMARY KEY (`lottery_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='彩票种类表';
INSERT INTO `zc_lottery` (`lottery_id`, `lottery_name`, `api_url`, `start_time`, `end_time`, `condition`) VALUES ('1', '北京赛车', 'http://a.apiplus.net/newly.do?token=30f00e72da3a4cc5&code=bjpk10&rows=1&format=json', '09:02', '23:57', '[{"start":"09:02","end":"23:57","interval":5}]');
INSERT INTO `zc_lottery` (`lottery_id`, `lottery_name`, `api_url`, `start_time`, `end_time`, `condition`) VALUES ('2', '重庆时时彩', 'http://d.apiplus.net/newly.do?token=30f00e72da3a4cc5&code=cqssc&rows=1&format=json', '09:50', '01:55', '[{"start":"09:50","end":"22:00","interval":10},{"start":"22:00","end":"01:55","interval":5}]');
INSERT INTO `zc_lottery` (`lottery_id`, `lottery_name`, `api_url`, `start_time`, `end_time`, `condition`) VALUES ('3', '幸运飞艇', 'http://d.apiplus.net/newly.do?token=30f00e72da3a4cc5&code=mlaft&rows=1&format=json', '13:04', '04:04', '[{"start":"13:04","end":"04:04","interval":5}]');

CREATE TABLE `zc_lottery_issue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lottery_name` varchar(20) NOT NULL DEFAULT '' COMMENT '彩票名称',
  `lottery_id` tinyint(1) NOT NULL DEFAULT '1' COMMENT '彩票类型：1-北京赛车，2-重庆时时彩，3-幸运飞艇',
  `issue` varchar(20) NOT NULL DEFAULT '' COMMENT '期数',
  `lottery_number` varchar(20) NOT NULL DEFAULT '0' COMMENT '开奖号码',
  `date_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '官方开奖时间',
  `action_name` varchar(50) NOT NULL DEFAULT '' COMMENT '人工开奖操作人',
  `finished` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否结算',
  `add_time` int(11) NOT NULL DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='开奖结果记录表';

CREATE TABLE `zc_game` (
  `game_id` int(11) NOT NULL AUTO_INCREMENT,
  `game_name` varchar(20) NOT NULL DEFAULT '' COMMENT '游戏名称',
  `lottery_id` int(11) NOT NULL DEFAULT '0' COMMENT '彩票类型：1-北京赛车，2-重庆时时彩，3-幸运飞艇',
  `must_host` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否必须要有庄',
  `zone_count` tinyint(1) NOT NULL DEFAULT '0' COMMENT '下注区域数量',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态：0-停用，1-启动',
  PRIMARY KEY (`game_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='游戏种类表';
INSERT INTO `zc_game` (`game_id`, `game_name`, `lottery_id`, `must_host`, `zone_count`, `status`) VALUES 
('1', '牌九', '1', '0', '5', '1');
INSERT INTO `zc_game` (`game_id`, `game_name`, `lottery_id`, `must_host`, `zone_count`, `status`) VALUES 
('2', '牛牛', '1', '0', '2', '1');
INSERT INTO `zc_game` (`game_id`, `game_name`, `lottery_id`, `must_host`, `zone_count`, `status`) VALUES 
('3', '三公', '1', '0', '5', '1');
INSERT INTO `zc_game` (`game_id`, `game_name`, `lottery_id`, `must_host`, `zone_count`, `status`) VALUES 
('4', '龙虎', '1', '1', '50', '0');
INSERT INTO `zc_game` (`game_id`, `game_name`, `lottery_id`, `must_host`, `zone_count`, `status`) VALUES 
('5', '单张', '2', '0', '5', '1');
INSERT INTO `zc_game` (`game_id`, `game_name`, `lottery_id`, `must_host`, `zone_count`, `status`) VALUES 
('6', '龙虎', '2', '1', '6', '0');
INSERT INTO `zc_game` (`game_id`, `game_name`, `lottery_id`, `must_host`, `zone_count`, `status`) VALUES 
('7', '牌九', '3', '0', '5', '1');
INSERT INTO `zc_game` (`game_id`, `game_name`, `lottery_id`, `must_host`, `zone_count`, `status`) VALUES 
('8', '牛牛', '3', '0', '2', '1');
INSERT INTO `zc_game` (`game_id`, `game_name`, `lottery_id`, `must_host`, `zone_count`, `status`) VALUES 
('9', '三公', '3', '0', '5', '1');
INSERT INTO `zc_game` (`game_id`, `game_name`, `lottery_id`, `must_host`, `zone_count`, `status`) VALUES 
('10', '龙虎', '3', '1', '50', '0');

CREATE TABLE `zc_site` (
  `site_id` int(11) NOT NULL AUTO_INCREMENT,
  `site_name` varchar(20) NOT NULL DEFAULT '' COMMENT '场馆名称',
  `site_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '场馆类型，1-底注10，2-底注100，3-体验场',
  `game_id` tinyint(1) NOT NULL DEFAULT '1' COMMENT '游戏ID',
  `max_member` int(11) NOT NULL DEFAULT '1' COMMENT '房间最多人数',
  `less_host_banlance` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '最少上庄金额',
  `max_host_banlance` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '最大上庄金额',
  `max_bet_banlance` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '最大下注金额',
  PRIMARY KEY (`site_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='场所表';
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`, `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('新葡京1', '1', '1', '100', '10000', '50000', '10000');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`,  `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('新葡京2', '1', '1', '100', '10000', '50000', '10000');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`,  `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('新葡京3', '1', '1', '100', '10000', '50000', '10000');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`,  `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('新葡京4', '1', '1', '100', '10000', '50000', '10000');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`,  `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('新葡京5', '1', '1', '100', '10000', '50000', '10000');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`,  `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('永利皇宫1', '2', '1', '100', '30000', '500000', '30000');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`,  `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('永利皇宫2', '2', '1', '100', '30000', '500000', '30000');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`,  `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('体验场', '3', '1', '100', '200', '500', '200');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`, `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('新葡京1', '1', '2', '100', '10000', '50000', '10000');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`,  `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('新葡京2', '1', '2', '100', '10000', '50000', '10000');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`,  `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('新葡京3', '1', '2', '100', '10000', '50000', '10000');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`,  `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('新葡京4', '1', '2', '100', '10000', '50000', '10000');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`,  `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('新葡京5', '1', '2', '100', '10000', '50000', '10000');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`,  `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('永利皇宫1', '2', '2', '100', '30000', '500000', '30000');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`,  `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('永利皇宫2', '2', '2', '100', '30000', '500000', '30000');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`,  `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('体验场', '3', '2', '100', '200', '500', '200');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`, `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('新葡京1', '1', '3', '100', '10000', '50000', '10000');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`,  `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('新葡京2', '1', '3', '100', '10000', '50000', '10000');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`,  `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('新葡京3', '1', '3', '100', '10000', '50000', '10000');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`,  `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('新葡京4', '1', '3', '100', '10000', '50000', '10000');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`,  `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('新葡京5', '1', '3', '100', '10000', '50000', '10000');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`,  `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('永利皇宫1', '2', '3', '100', '30000', '500000', '30000');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`,  `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('永利皇宫2', '2', '3', '100', '30000', '500000', '30000');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`,  `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('体验场', '3', '3', '100', '200', '500', '200');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`, `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('新葡京1', '1', '4', '100', '10000', '50000', '10000');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`,  `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('新葡京2', '1', '4', '100', '10000', '50000', '10000');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`,  `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('新葡京3', '1', '4', '100', '10000', '50000', '10000');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`,  `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('新葡京4', '1', '4', '100', '10000', '50000', '10000');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`,  `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('新葡京5', '1', '4', '100', '10000', '50000', '10000');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`,  `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('永利皇宫1', '2', '4', '100', '30000', '500000', '30000');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`,  `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('永利皇宫2', '2', '4', '100', '30000', '500000', '30000');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`,  `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('体验场', '3', '4', '100', '200', '500', '200');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`, `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('新葡京1', '1', '5', '100', '10000', '50000', '10000');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`,  `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('新葡京2', '1', '5', '100', '10000', '50000', '10000');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`,  `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('新葡京3', '1', '5', '100', '10000', '50000', '10000');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`,  `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('新葡京4', '1', '5', '100', '10000', '50000', '10000');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`,  `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('新葡京5', '1', '5', '100', '10000', '50000', '10000');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`,  `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('永利皇宫1', '2', '5', '100', '30000', '500000', '30000');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`,  `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('永利皇宫2', '2', '5', '100', '30000', '500000', '30000');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`,  `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('体验场', '3', '5', '100', '200', '500', '200');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`, `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('新葡京1', '1', '6', '100', '10000', '50000', '10000');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`,  `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('新葡京2', '1', '6', '100', '10000', '50000', '10000');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`,  `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('新葡京3', '1', '6', '100', '10000', '50000', '10000');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`,  `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('新葡京4', '1', '6', '100', '10000', '50000', '10000');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`,  `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('新葡京5', '1', '6', '100', '10000', '50000', '10000');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`,  `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('永利皇宫1', '2', '6', '100', '30000', '500000', '30000');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`,  `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('永利皇宫2', '2', '6', '100', '30000', '500000', '30000');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`,  `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('体验场', '3', '6', '100', '200', '500', '200');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`, `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('新葡京1', '1', '7', '100', '10000', '50000', '10000');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`,  `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('新葡京2', '1', '7', '100', '10000', '50000', '10000');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`,  `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('新葡京3', '1', '7', '100', '10000', '50000', '10000');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`,  `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('新葡京4', '1', '7', '100', '10000', '50000', '10000');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`,  `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('新葡京5', '1', '7', '100', '10000', '50000', '10000');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`,  `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('永利皇宫1', '2', '7', '100', '30000', '500000', '30000');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`,  `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('永利皇宫2', '2', '7', '100', '30000', '500000', '30000');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`,  `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('体验场', '3', '7', '100', '200', '500', '200');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`, `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('新葡京1', '1', '8', '100', '10000', '50000', '10000');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`,  `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('新葡京2', '1', '8', '100', '10000', '50000', '10000');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`,  `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('新葡京3', '1', '8', '100', '10000', '50000', '10000');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`,  `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('新葡京4', '1', '8', '100', '10000', '50000', '10000');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`,  `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('新葡京5', '1', '8', '100', '10000', '50000', '10000');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`,  `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('永利皇宫1', '2', '8', '100', '30000', '500000', '30000');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`,  `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('永利皇宫2', '2', '8', '100', '30000', '500000', '30000');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`,  `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('体验场', '3', '8', '100', '200', '500', '200');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`, `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('新葡京1', '1', '9', '100', '10000', '50000', '10000');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`,  `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('新葡京2', '1', '9', '100', '10000', '50000', '10000');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`,  `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('新葡京3', '1', '9', '100', '10000', '50000', '10000');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`,  `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('新葡京4', '1', '9', '100', '10000', '50000', '10000');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`,  `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('新葡京5', '1', '9', '100', '10000', '50000', '10000');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`,  `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('永利皇宫1', '2', '9', '100', '30000', '500000', '30000');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`,  `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('永利皇宫2', '2', '9', '100', '30000', '500000', '30000');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`,  `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('体验场', '3', '9', '100', '200', '500', '200');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`, `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('新葡京1', '1', '10', '100', '10000', '50000', '10000');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`,  `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('新葡京2', '1', '10', '100', '10000', '50000', '10000');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`,  `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('新葡京3', '1', '10', '100', '10000', '50000', '10000');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`,  `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('新葡京4', '1', '10', '100', '10000', '50000', '10000');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`,  `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('新葡京5', '1', '10', '100', '10000', '50000', '10000');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`,  `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('永利皇宫1', '2', '10', '100', '30000', '500000', '30000');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`,  `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('永利皇宫2', '2', '10', '100', '30000', '500000', '30000');
INSERT INTO `zc_site` (`site_name`, `site_type`, `game_id`, `max_member`,  `less_host_banlance`, `max_host_banlance`, `max_bet_banlance`) VALUES ('体验场', '3', '10', '100', '200', '500', '200');

CREATE TABLE `zc_bet_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户ID',
  `lottery_id` tinyint(1) NOT NULL DEFAULT '1' COMMENT '彩票ID',
  `room_id` int(11) NOT NULL DEFAULT '0' COMMENT '房间ID',
  `issue` varchar(20) NOT NULL DEFAULT '' COMMENT '期数',
  `is_host` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否庄家',
  `bet_balance` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '下注总金额',
  `profit_balance` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '收益',
  `commission` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '佣金',
  `bet_detail` text NOT NULL DEFAULT '' COMMENT '下注情况',
  `sync` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否同步',
  `finished` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否结算',
  `add_time` int(11) NOT NULL DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `sync` (`sync`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='下注记录表';

CREATE TABLE `zc_host` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户ID',
  `room_id` int(11) NOT NULL DEFAULT '0' COMMENT '房间ID',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '庄家状态，0-等待上庄，1-上庄，2-已申请下庄',
  `host_balance` decimal(10,2) DEFAULT '0.00' COMMENT '上庄余额',
  `host_zone` tinyint(1) DEFAULT '0' COMMENT '上庄区域',
  `is_delete` tinyint(1) DEFAULT '0' COMMENT '是否删除',
  `add_time` int(11) NOT NULL DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`id`),
  KEY (`room_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='庄家表';

CREATE TABLE `zc_open_result_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `game_id` int(11) NOT NULL DEFAULT '0' COMMENT '游戏ID',
  `issue` varchar(20) NOT NULL DEFAULT '' COMMENT '期数',
  `zone_detail` text NOT NULL DEFAULT '' COMMENT '区域开奖情况',
  `add_time` int(11) NOT NULL DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`id`),
  KEY (`game_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='开奖情况记录表';

CREATE TABLE `zc_chat_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room_id` int(10) NOT NULL DEFAULT '0' COMMENT '房间ID',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户ID',
  `nickname` varchar(50) NOT NULL DEFAULT '' COMMENT '昵称',
  `content` text NOT NULL DEFAULT '' COMMENT '聊天内容',
  `add_time` int(11) NOT NULL DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`id`),
  KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户聊天记录';

CREATE TABLE `zc_admin_user` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_name` varchar(50) NOT NULL DEFAULT '' COMMENT '用户名',
  `nickname` varchar(50) NOT NULL DEFAULT '' COMMENT '昵称',
  `full_name` varchar(15) NOT NULL DEFAULT '' COMMENT '姓名',
  `qq` varchar(15) NOT NULL DEFAULT '' COMMENT 'QQ',
  `icon` varchar(100) NOT NULL DEFAULT '' COMMENT '头像',
  `phone` varchar(20) NOT NULL DEFAULT '' COMMENT '手机号',
  `email` varchar(50) NOT NULL DEFAULT '' COMMENT '电子邮箱',
  `password` varchar(50) NOT NULL DEFAULT '' COMMENT '登录密码',
  `pay_password` varchar(50) NOT NULL DEFAULT '' COMMENT '支付密码',
  `balance` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '元宝',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态：0-冻结 1-正常',
  `pid` int(11) NOT NULL DEFAULT '0' COMMENT '父ID',
  `agent_count` int(11) NOT NULL DEFAULT '0' COMMENT '还剩的代理数量',
  `rate` decimal(6,2) NOT NULL DEFAULT '0.00' COMMENT '佣金率',
  `invite_code` varchar(10) NOT NULL DEFAULT '' COMMENT '邀请码',
  `account_number` varchar(50) NOT NULL DEFAULT '' COMMENT '银行卡号',
  `bank_name` varchar(50) NOT NULL DEFAULT '' COMMENT '银行名称',
  `real_name` varchar(50) NOT NULL DEFAULT '' COMMENT '真实姓名',
  `branch_bank` varchar(50) NOT NULL DEFAULT '' COMMENT '开户支行',
  `add_time` int(11) NOT NULL DEFAULT '0' COMMENT '注册时间',
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `user_name` (`user_name`),
  KEY `pid` (`pid`),
  KEY `invite_code` (`invite_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='代理用户表';
INSERT INTO `zc_admin_user` (`user_name`, `nickname`, `password`, `pid`, `rate`, `invite_code`) VALUES ('admin', 'admin', 'e10adc3949ba59abbe56e057f20f883e', '0', '2.00', '000000');


CREATE TABLE `zc_admin_income` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bet_id` int(11) NOT NULL DEFAULT '0' COMMENT '下注ID',
  `admin_id` int(11) NOT NULL DEFAULT '0' COMMENT '代理用户ID',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户ID',
  `issue` varchar(20) NOT NULL DEFAULT '' COMMENT '期数',
  `is_host` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否庄家',
  `bet_balance` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '下注总金额',
  `profit_balance` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '收益',
  `win_balance` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '流水',
  `commission` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '佣金',
  `add_time` int(11) NOT NULL DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`id`),
  KEY `admin_id` (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='代理收入表';

CREATE TABLE `zc_admin_login_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户ID',
  `user_name` varchar(50) DEFAULT '' COMMENT '用户名',
  `type` tinyint(1) DEFAULT '1' COMMENT '1：代理，2：客服',
  `ip` varchar(20) DEFAULT '' COMMENT 'IP',
  `add_time` varchar(50) DEFAULT '' COMMENT '时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='登录日志';

CREATE TABLE `zc_login_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户ID',
  `ip` varchar(20) DEFAULT '' COMMENT 'IP',
  `add_time` int(11) NOT NULL DEFAULT '0' COMMENT '时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='登录日志';

CREATE TABLE `zc_request_action_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) NOT NULL DEFAULT '0' COMMENT '用户ID',
  `is_temp` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否临时',
  `controller` varchar(20) NOT NULL DEFAULT '' COMMENT '控制器',
  `action` varchar(50) NOT NULL DEFAULT '' COMMENT '方法',
  `param` text NOT NULL COMMENT 'GET参数',
  `return` text NOT NULL COMMENT '返回值',
  `ip` varchar(20) NOT NULL DEFAULT '' COMMENT 'IP',
  `add_time` varchar(50) NOT NULL DEFAULT '' COMMENT '时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='请求动作日志';

CREATE TABLE `zc_notice` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `content` varchar(255) NOT NULL DEFAULT '' COMMENT '公告内容',
  `user_name` varchar(50) NOT NULL DEFAULT '' COMMENT '员工帐号',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1-启动，0-停用',
  `add_time` int(11) NOT NULL DEFAULT '0' COMMENT '时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='公告表';
INSERT INTO `zc_notice` (`content`, `user_name`, `add_time`) VALUES ('欢迎莅临众彩娱乐，祝您财源广进！', '', '0');

CREATE TABLE `zc_push_lottery_result` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lottery_id` tinyint(1) NOT NULL DEFAULT '1' COMMENT '彩票ID：1-北京赛车，2-重庆时时彩，3-幸运飞艇',
  `expect` varchar(20) NOT NULL DEFAULT '' COMMENT '期数',
  `opencode` varchar(20) NOT NULL DEFAULT '' COMMENT '开奖号码',
  `open_time` varchar(20) NOT NULL DEFAULT '' COMMENT '官方开奖时间',
  `opentimestamp` int(11) NOT NULL DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='第三方推送开奖结果记录';

CREATE TABLE `zc_recharge` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户ID',
  `bank_id` int(11) NOT NULL DEFAULT '0' COMMENT '银行卡ID',
  `user_name` varchar(50) NOT NULL DEFAULT '' COMMENT '用户名',
  `recharge_cash` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '充值金额',
  `real_cash` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '实际到账金额',
  `order_sn` varchar(30) NOT NULL DEFAULT '' COMMENT '支付平台的订单号',
  `account_number` varchar(50) NOT NULL DEFAULT '' COMMENT '银行卡号',
  `bank_name` varchar(50) NOT NULL DEFAULT '' COMMENT '银行名称',
  `real_name` varchar(50) NOT NULL DEFAULT '' COMMENT '真实姓名',
  `message` varchar(10) NOT NULL DEFAULT '' COMMENT '留言',
  `type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '充值方式：1-银联转账，2-支付宝，3-微信',
  `sync` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0-申请中，1-同意提款，2-不同意',
  `mm_name` varchar(50) NOT NULL DEFAULT '' COMMENT '操作人',
  `add_time` int(11) NOT NULL DEFAULT '0' COMMENT '添加时间',
  `pay_time` int(11) NOT NULL DEFAULT '0' COMMENT '到账时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='充值表';

CREATE TABLE `zc_edit_password_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '被修改人的用户ID',
  `is_agent` tinyint(1) NOT NULL DEFAULT '0' COMMENT '被修改人是否是代理',
  `action_name` varchar(50) NOT NULL DEFAULT '' COMMENT '操作人',
  `type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '操作人类型：1-客服，2-代理',
  `add_time` int(11) NOT NULL DEFAULT '0' COMMENT '时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='修改密码日志记录';

CREATE TABLE `zc_pay_order` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_sn` varchar(30) NOT NULL DEFAULT '' COMMENT '支付平台的订单号',
  `merchant_order_sn` varchar(30) NOT NULL DEFAULT '' COMMENT '订单号,确保唯一',
  `user_id` int(11) NOT NULL DEFAULT '0',
  `total_fee` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '提交支付金额',
  `real_pay` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '实际支付金额（回调）',
  `pay_type` varchar(10) NOT NULL DEFAULT '' COMMENT '支付方式：wechat-微信，alipay-支付宝',
  `is_pay` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否支付',
  `pay_time` int(11) NOT NULL DEFAULT '0' COMMENT '支付时间',
  `add_time` int(11) NOT NULL DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`id`),
  KEY (`merchant_order_sn`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='第三方充值支付订单表';

CREATE TABLE `zc_nonce_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nonce_str` varchar(32) NOT NULL DEFAULT '' COMMENT '32位随机字符串',
  `add_time` int(11) NOT NULL DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`id`),
  KEY (`nonce_str`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='签名随机字符串表';

CREATE TABLE `zc_manual_lottery_result` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lottery_id` tinyint(1) NOT NULL DEFAULT '1' COMMENT '彩票ID：1-北京赛车，2-重庆时时彩，3-幸运飞艇',
  `expect` varchar(20) NOT NULL DEFAULT '' COMMENT '期数',
  `opencode` varchar(40) NOT NULL DEFAULT '0' COMMENT '开奖号码',
  `action_name` varchar(50) NOT NULL DEFAULT '' COMMENT '操作人',
  `opentimestamp` int(11) NOT NULL DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='人工开奖表';

CREATE TABLE `zc_set_bank_card` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account_number` varchar(50) NOT NULL DEFAULT '' COMMENT '银行卡号',
  `bank_name` varchar(50) NOT NULL DEFAULT '' COMMENT '银行名称',
  `real_name` varchar(50) NOT NULL DEFAULT '' COMMENT '真实姓名',
  `branch_bank` varchar(50) NOT NULL DEFAULT '' COMMENT '开户支行',
  `is_default` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否使用',
  `is_delete` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否删除',
  `add_time` int(11) NOT NULL DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='网银充值银行表';
INSERT INTO `zc_set_bank_card` (`account_number`, `bank_name`, `real_name`, `branch_bank`, `is_default`) VALUES ('40026332566325412', '工商银行', '林某某', '体育西路支行', 1);

CREATE TABLE `zc_config` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `config_sign` char(50) NOT NULL COMMENT '配置标识',
  `config_name` char(50) NOT NULL COMMENT '配置名称',
  `config_value` varchar(255) NOT NULL COMMENT '配置值',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='配置表';
INSERT INTO `zc_config` (`config_sign`, `config_name`, `config_value`) VALUES ('system_maintenance', '系统维护', '0');
INSERT INTO `zc_config` (`config_sign`, `config_name`, `config_value`) VALUES ('announcement', '维护公告', '当前系统维护，暂时无法登录，请稍后再试');
INSERT INTO `zc_config` (`config_sign`, `config_name`, `config_value`) VALUES ('announcement_url', '公告地址', 'http://baidu.com');
INSERT INTO `zc_config` (`config_sign`, `config_name`, `config_value`) VALUES ('cs_qq', '客服QQ', '121345');
INSERT INTO `zc_config` (`config_sign`, `config_name`, `config_value`) VALUES ('cs_wx', '客服微信', 'wx_888168');
INSERT INTO `zc_config` (`config_sign`, `config_name`, `config_value`) VALUES ('online_pay', '第三方支付通道', '1');
INSERT INTO `zc_config` (`config_sign`, `config_name`, `config_value`) VALUES ('rate', '佣金率', '0.02');
INSERT INTO `zc_config` (`config_sign`, `config_name`, `config_value`) VALUES ('app_id', 'APP_ID', '20170723234205313585');
INSERT INTO `zc_config` (`config_sign`, `config_name`, `config_value`) VALUES ('app_secret', 'APP_SECRET', '75af9c1b2f714783b44bb28d4edb1c5a');
INSERT INTO `zc_config` (`config_sign`, `config_name`, `config_value`) VALUES ('store_id', 'STORE_ID', '202574');
INSERT INTO `zc_config` (`config_sign`, `config_name`, `config_value`) VALUES ('pay_url', 'PAY_URL', 'https://shq-api.51fubei.com/gateway');
INSERT INTO `zc_config` (`config_sign`, `config_name`, `config_value`) VALUES ('pay_method', 'PAY_METHOD', 'openapi.payment.order.scan');
INSERT INTO `zc_config` (`config_sign`, `config_name`, `config_value`) VALUES ('free_draw_times', '免费提现次数', '3');
INSERT INTO `zc_config` (`config_sign`, `config_name`, `config_value`) VALUES ('draw_fee', '手续费', '0.03');
INSERT INTO `zc_config` (`config_sign`, `config_name`, `config_value`) VALUES ('min_recharge', '最低充值', '10');
INSERT INTO `zc_config` (`config_sign`, `config_name`, `config_value`) VALUES ('max_recharge', '最高充值', '5000');
INSERT INTO `zc_config` (`config_sign`, `config_name`, `config_value`) VALUES ('min_draw', '最低提现', '10');
INSERT INTO `zc_config` (`config_sign`, `config_name`, `config_value`) VALUES ('max_draw', '最高提现', '5000');
INSERT INTO `zc_config` (`config_sign`, `config_name`, `config_value`) VALUES ('water_times', '流水倍数', '2');
INSERT INTO `zc_config` (`config_sign`, `config_name`, `config_value`) VALUES ('cz_wx', '充值客户微信', '2');

CREATE TABLE `zc_admin_waste_book` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '代理用户ID',
  `before_balance` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '变化前金额',
  `after_balance` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '变化后金额',
  `change_balance` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '变化金额',
  `type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '类型：1-佣金，2-提现，3-提现失败，4-退款',
  `add_time` int(11) NOT NULL DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='代理流水日志';

CREATE TABLE `zc_user_waste_book` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户ID',
  `before_balance` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '变化前金额',
  `after_balance` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '变化后金额',
  `change_balance` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '变化金额',
  `type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '类型：1-下注，2-提现，3-提现失败，4-充值，5-取消下注，6-上庄，7-下庄，8-退款，9-回利',
  `add_time` int(11) NOT NULL DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='玩家流水日志';

CREATE TABLE `zc_cs_user` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_name` varchar(50) NOT NULL DEFAULT '' COMMENT '员工账号',
  `nickname` varchar(50) NOT NULL DEFAULT '' COMMENT '员工别名',
  `password` varchar(50) NOT NULL DEFAULT '' COMMENT '登录密码',
  `auth` varchar(255) NOT NULL DEFAULT '' COMMENT '权限',
  `is_delete` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否删除',
  `add_time` int(11) NOT NULL DEFAULT '0' COMMENT '时间',
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `user_name` (`user_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='客服表';
INSERT INTO `zc_cs_user` (`user_name`, `nickname`, `password`, `auth`, `is_delete`) VALUES ('admin', 'admin', md5('123456'),'101,102,103,104,105,106,107,201,202,203,301,302,303,304,305,306,401,402','0');

CREATE TABLE `zc_draw_cash` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户ID',
  `user_name` varchar(50) NOT NULL DEFAULT '' COMMENT '账号',
  `type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1-会员，2-代理',
  `apply_cash` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '申请提现金额',
  `real_cash` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '到手金额',
  `account_number` varchar(50) NOT NULL DEFAULT '' COMMENT '银行卡号',
  `bank_name` varchar(50) NOT NULL DEFAULT '' COMMENT '银行名称',
  `real_name` varchar(50) NOT NULL DEFAULT '' COMMENT '真实姓名',
  `branch_bank` varchar(50) NOT NULL DEFAULT '' COMMENT '开户支行',
  `sync` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0-申请中，1-同意提款，2-取消，3-有问题',
  `cs_name` varchar(50) NOT NULL DEFAULT '' COMMENT '操作人',
  `add_time` int(11) NOT NULL DEFAULT '0' COMMENT '添加时间',
  `sync_time` int(11) NOT NULL DEFAULT '0' COMMENT '同步时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='提现表';

CREATE TABLE `zc_user_water` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户ID',
  `admin_id` int(11) NOT NULL DEFAULT '0' COMMENT '代理ID',
  `balance` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '流水金额',
  `add_time` int(11) NOT NULL DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='计算玩家流水表';

CREATE TABLE `zc_user_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户ID',
  `user_name` varchar(50) NOT NULL DEFAULT '' COMMENT '用户名称',
  `title` varchar(50) NOT NULL DEFAULT '' COMMENT '标题',
  `content` varchar(100) NOT NULL DEFAULT '' COMMENT '内容',
  `add_time` int(11) NOT NULL DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `user_name` (`user_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='玩家日志表';

CREATE TABLE `zc_cs_action_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_name` varchar(50) NOT NULL DEFAULT '' COMMENT '操作人',
  `content` varchar(255) NOT NULL DEFAULT '' COMMENT '操作详细',
  `controller` varchar(30) NOT NULL DEFAULT '' COMMENT '控制器',
  `action` varchar(30) NOT NULL DEFAULT '' COMMENT '方法名',
  `param` text NOT NULL DEFAULT '' COMMENT 'POST参数',
  `add_time` int(11) NOT NULL DEFAULT '0' COMMENT '时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='客服操作日志表';

CREATE TABLE `zc_system_message` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户ID',
  `content` varchar(255) NOT NULL DEFAULT '' COMMENT '内容',
  `read` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0-未读，1-已读',
  `add_time` int(11) NOT NULL DEFAULT '0' COMMENT '时间',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='系统消息';
