<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html>

	<head>
		<meta charset="utf-8">
		<title>房间列表</title>
		<meta name="renderer" content="webkit">
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
		<link rel="stylesheet" href="/Static/Public/Cs/frame/layui/css/layui.css" media="all">
		<link rel="stylesheet" href="/Static/Public/Cs/frame/mystyle.css" />
	</head>

	<body>
		<div class="my-btn-box">
			<form class="layui-form" action="<?php echo U('Game/room');?>" method="get">
			<span class="fl">
				<span class="layui-form-label">彩票类型：</span>
			    <div class="layui-input-inline sl" style="width: 120px;">
			      <select name="lottery_id">
			      	<option value="0" <?php if($lottery_id == 0): ?>selected<?php endif; ?>>全部类型</option>
			      	<option value="1" <?php if($lottery_id == 1): ?>selected<?php endif; ?>>北京赛车</option>
			        <option value="2" <?php if($lottery_id == 2): ?>selected<?php endif; ?>>重庆时时彩</option>
			        <option value="3" <?php if($lottery_id == 3): ?>selected<?php endif; ?>>幸运飞艇</option>
			    </select>
			    </div>
    		</span>
    		<!-- <span class="fl">
        		<a class="layui-btn btn-add btn-default" id="btn-add-room">新增房间</a>
    		</span> -->
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
					<col>
				</colgroup> -->
				<thead>
					<tr>
						<th>ID</th>
						<th>彩票类型</th>
						<th>游戏类型</th>
						<th>底注</th>
						<th>房间名称</th>
						<th>人数限制</th>
						<th>最小上庄金额</th>
						<th>最大上庄金额</th>
						<th>最大下注金额</th>
						<th>在线人数</th>
						<th>操作</th>
					</tr>
				</thead>
				<tbody>
					<?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$data): $mod = ($i % 2 );++$i;?><tr>
						<td><?php echo ($data['site_id']); ?></td>
						<td><?php echo ($data['lottery_name']); ?></td>
						<td><?php echo ($data['game_name']); ?></td>
						<td><?php echo ($data['type_name']); ?></td>
						<td><?php echo ($data['site_name']); ?></td>
						<td><?php echo ($data['max_member']); ?></td>
						<td><?php echo ($data['less_host_banlance']); ?></td>
						<td><?php echo ($data['max_host_banlance']); ?></td>
						<td><?php echo ($data['max_bet_banlance']); ?></td>
						<td>
							<?php echo ($data['online_count']); ?> 
							<?php if($data['online_count'] > 0): ?><a class="layui-btn layui-btn-mini layui-btn-warm look-online-user" data-id="<?php echo ($data['site_id']); ?>">查看</a><?php endif; ?>
						</td>
						<td>
						<a class="layui-btn layui-btn-mini layui-btn-warm view-edit" data-id="<?php echo ($data['site_id']); ?>">编辑</a>
						<!-- <a class="layui-btn layui-btn-mini layui-btn-warm view-agent">删除</a> -->
						</td>
					</tr><?php endforeach; endif; else: echo "" ;endif; ?>				
				</tbody>
			</table>
		</div>
		<div id="demo1"></div>
		<script src="/Static/Public/Cs/frame/layui/layui.js" charset="utf-8"></script>
		<!-- 注意：如果你直接复制所有代码到本地，上述js路径需要改成你本地的 -->
		<script>
			layui.use(['form','laypage','layer','laydate','element'], function() {
				var $ = layui.jquery,form = layui.form(),laypage = layui.laypage,layer = layui.layer;

				// 分页
				laypage({
    				cont: 'demo1',
    				curr: <?php echo ($pageInfo['page']); ?>,
    				pages: <?php echo ($pageInfo['page_count']); ?>, //总页数
    				groups: 5, //连续显示分页数
    				jump: function(obj, first){
				    	var page = obj.curr;
				    	if (!first) {
				    		location.href = "<?php echo U('Game/room');?>?lottery_id=<?php echo ($lottery_id); ?>&page="+page;
				    	}
				    }
  				});

  				// 查看在线会员
  				$('.look-online-user').on('click',function(){
  					var room_id = $(this).attr('data-id');
  					layer.open({
						type:2,
						title:'在线会员',
						shadeClose:true,
						shade:0.8,
						area:['600px','550px'],
						content:"<?php echo U('Game/onlineUser');?>?room_id="+room_id
					});
  				});

  				// 编辑
  				$('.view-edit').on('click',function(){
  					var site_id = $(this).attr('data-id');
  					layer.open({
						type:2,
						title:'编辑房间',
						shadeClose:true,
						shade:0.8,
						area:['400px','535px'],
						content:"<?php echo U('Game/editRoom');?>?site_id="+site_id
					});
  				});

				$('.sl dd').click(function(){
					$('form').submit();
				});
			});
		</script>
	</body>
</html>