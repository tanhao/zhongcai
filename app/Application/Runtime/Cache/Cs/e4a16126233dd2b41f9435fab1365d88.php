<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html>

	<head>
		<meta charset="utf-8">
		<title>注单</title>
		<meta name="renderer" content="webkit">
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
		<link rel="stylesheet" href="/Static/Public/Cs/frame/layui/css/layui.css" media="all">
		<link rel="stylesheet" href="/Static/Public/Cs/frame/mystyle.css" />
		<!-- 注意：如果你直接复制所有代码到本地，上述css路径需要改成你本地的 -->
	</head>

	<body>
		<div class="my-btn-box">
			<form class="layui-form" action="<?php echo U('Game/nowBetDetail');?>" method="get">
				<span class="fl sl">
					 <div class="layui-form-item">
					    <div class="layui-input-inline" style="width: 100px;">
					      <select name="lottery_id">
					        <?php if(is_array($lotteryList)): $i = 0; $__LIST__ = $lotteryList;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$data): $mod = ($i % 2 );++$i;?><option value="<?php echo ($data['lottery_id']); ?>" <?php echo ($data['selected']); ?>><?php echo ($data['lottery_name']); ?></option><?php endforeach; endif; else: echo "" ;endif; ?>
					      </select>
					    </div>
					    <div class="layui-input-inline" style="width: 100px;">
					      <select name="game_id">
					      	<?php if(is_array($gameList)): $i = 0; $__LIST__ = $gameList;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$data): $mod = ($i % 2 );++$i;?><option value="<?php echo ($data['game_id']); ?>" <?php echo ($data['selected']); ?>><?php echo ($data['game_name']); ?></option><?php endforeach; endif; else: echo "" ;endif; ?>
					      </select>
					    </div>
					    <div class="layui-input-inline" style="width: 100px;">
					      <select name="room_id">
					        <?php if(is_array($siteList)): $i = 0; $__LIST__ = $siteList;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$data): $mod = ($i % 2 );++$i;?><option value="<?php echo ($data['site_id']); ?>" <?php echo ($data['selected']); ?>><?php echo ($data['site_name']); ?></option><?php endforeach; endif; else: echo "" ;endif; ?>
					      </select>
					    </div>
					    <div class="layui-input-inline" style="width: 100px;">
					      <select name="zone">
					        <?php if(is_array($zoneList)): $i = 0; $__LIST__ = $zoneList;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$data): $mod = ($i % 2 );++$i;?><option value="<?php echo ($data['zone']); ?>" <?php echo ($data['selected']); ?>><?php echo ($data['zone_name']); ?></option><?php endforeach; endif; else: echo "" ;endif; ?>
					      </select>
					    </div>
					  </div>
				</span>
				<span class="fr">
	     		   <span class="layui-form-label">搜索条件：</span>
					<div class="layui-input-inline">
						<input type="text" autocomplete="off" name="user_name" placeholder="请输入账号" class="layui-input" value="<?php echo ($user_name); ?>">
					</div>
					<button class="layui-btn mgl-20">查询</button>
				</span>
    		</form>
		</div>
		<div class="layui-form">
			<table class="layui-table"> 
				<!-- <colgroup>
					<col width="50">
					<col width="100">
					<col width="100">
					<col width="100">
					<col width="100">
					<col width="100">
					<col width="100">
					<col width="100">
				</colgroup> -->
				<thead>
					<tr>
						<th>会员账号</th>
						<th>彩种类型</th>
						<th>房间</th>
						<th>玩法</th>
						<th>区号</th>
						<th>金额</th>
						<th>下注时间</th>
					</tr>
				</thead>
				<tbody>
					<?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$data): $mod = ($i % 2 );++$i;?><tr>
						<td><?php echo ($data['user_name']); ?></td>
						<td><?php echo ($data['lottery_name']); ?></td>
						<td><?php echo ($data['site_name']); ?></td>
						<td><?php echo ($data['game_name']); ?></td>
						<td><?php echo ($data['zone']); ?></td>
						<td><?php echo ($data['balance']); ?></td>
						<td><?php echo (date("Y-m-d H:i:s",$data['add_time'])); ?></td>
					</tr><?php endforeach; endif; else: echo "" ;endif; ?>
				</tbody>
			</table>
		</div>
		<div id="demo1"></div>
		<script src="/Static/Public/Cs/frame/layui/layui.js" charset="utf-8"></script>
		<!-- 注意：如果你直接复制所有代码到本地，上述js路径需要改成你本地的 -->
		<script>
			layui.use(['form','laypage', 'layer','laydate'], function() {
				var $ = layui.jquery,laypage = layui.laypage;
				// 分页
				laypage({
    				cont: 'demo1',
    				curr: <?php echo ($pageInfo['page']); ?>,
    				pages: <?php echo ($pageInfo['page_count']); ?>, //总页数
    				groups: 5, //连续显示分页数
    				jump: function(obj, first){
				    	var page = obj.curr;
				    	if (!first) {
				    		location.href = "<?php echo U('Game/nowBetDetail');?>?lottery_id=<?php echo ($lottery_id); ?>&game_id=<?php echo ($game_id); ?>&zone=<?php echo ($zone); ?>&room_id=<?php echo ($room_id); ?>&user_name=<?php echo ($user_name); ?>&page="+page;
				    	}
				    }
  				});

				$('.sl dd').click(function(){
					$('form').submit();
				});

			});
		</script>

	</body>

</html>