﻿服务器：
服务：nginx,php,mysql,redis,websecket

接口文档：
https://www.showdoc.cc/home/item/show/item_id/1617831 访问密码：123123123

京彩代理后台
http://jc7888.com/88/After/Bet/historyDetail/p/2 bbb666/zxc23168

PK10 ：    09:02~23:57，09:07开第一期，5分钟开一期，23：57最后一期，共179期
时时彩 ：  09:50~22:00，10:00开第一期，10分钟开一期，22：00最后一期；共73期
		   22:00~01:55，22:05开第一期，5分钟开一期，01：55最后一期；共47期
幸运飞艇 : 13:04~04:04，13:09开第一期，5分钟开一期，4：04开最后一期，共180期

MYSQL远程登录
0. CREATE USER 'root'@'%' IDENTIFIED BY 'zhongcai123$'; 
1. 进入mysql，GRANT ALL PRIVILEGES ON *.* TO 'root'@'%' IDENTIFIED BY 'zhongcai123$' WITH GRANT OPTION;
2. FLUSH privileges; 更新

// websocket配置，端口:8282
php start.php start -d  // 启动
php start.php reload    // 重启

// 指令
free -m 查内存
df -lh 查磁盘
du -sh ./* 查目录下文件大小

#定时任务
2,7,12,17,22,27,32,37,42,47,52,57 9-23 * * * /usr/local/php/bin/php /var/www/app/index.php cli/open/index/id/1

50 9 * * * /usr/local/php/bin/php /var/www/app/index.php cli/open/index/id/2
0,10,20,30,40,50 10-21 * * * /usr/local/php/bin/php /var/www/app/index.php cli/open/index/id/2
0,5,10,15,20,25,30,35,40,45,50,55 22-23,0-1 * * * /usr/local/php/bin/php /var/www/app/index.php cli/open/index/id/2

4,9,14,19,24,29,34,39,44,49,54,59 13-23,0-3 * * * /usr/local/php/bin/php /var/www/app/index.php cli/open/index/id/3
4 4 * * * /usr/local/php/bin/php /var/www/app/index.php cli/open/index/id/3

*/1 * * * * /usr/local/php/bin/php /var/www/app/index.php cli/syncIncome/index
0 6 * * * /usr/local/php/bin/php /var/www/app/index.php cli/public/index
*/1 * * * * /usr/local/php/bin/php /var/www/app/index.php cli/public/userWater