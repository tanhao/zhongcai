<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html>

	<head>
		<meta charset="utf-8">
		<title>layui</title>
		<meta name="renderer" content="webkit">
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
		<link rel="stylesheet" href="/Static/Public/Cs/frame/layui/css/layui.css" media="all">
		<link rel="stylesheet" href="/Static/Public/Cs/frame/mystyle.css" />
	</head>

	<body>
		<div class="layui-form">
			<table class="layui-table"> 
				<!-- <colgroup>
					<col width="50">
					<col width="100">
					<col width="80">
					<col width="250">
					<col width="150">
					<col width="100">
					<col width="100">
					<col width="160">
					<col>
				</colgroup> -->
				<thead>
					<tr>
						<th>ID</th>
						<th>日期</th>
						<th>期数</th>
						<th>开奖号码</th>
						<th>下注金额</th>
						<th>交易金额</th>
						<th>抽佣金额</th>
						<th>开奖时间</th>
						<!-- <th>操作</th> -->
					</tr>
				</thead>
				<tbody>
					<?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$data): $mod = ($i % 2 );++$i;?><tr>
						<td><?php echo ($data['id']); ?></td>
						<td><?php echo (date("Y-m-d",$data['add_time'])); ?></td>
						<td><?php echo ($data['issue']); ?></td>
						<td><?php echo ($data['lottery_number']); ?></td>
						<td><?php echo ($data['bet_balance']); ?> 
						<?php if($data['bet_balance'] > 0): ?><a class="layui-btn layui-btn-mini layui-btn-warm view-bet" data-id="<?php echo ($data['id']); ?>">查看</a><?php endif; ?>
						</td>
						<td><?php echo ($data['trade_balance']); ?></td>
						<td><?php echo ($data['commission']); ?></td>
						<td><?php echo (date("Y-m-d H:i:s",$data['add_time'])); ?></td>
						<!-- <td><a class="layui-btn layui-btn-mini layui-btn-warm view-agent">开奖</a><a class="layui-btn layui-btn-mini layui-btn-warm view-agent">退款</a></td> -->
					</tr><?php endforeach; endif; else: echo "" ;endif; ?>
				</tbody>
			</table>
		</div>
		<div id="demo1"></div>
		<script src="/Static/Public/Cs/frame/layui/layui.js" charset="utf-8"></script>
		<!-- 注意：如果你直接复制所有代码到本地，上述js路径需要改成你本地的 -->
		<script>
			layui.use(['form','laypage', 'layer','laydate'], function() {
				var $ = layui.jquery,
					form = layui.form(),laypage = layui.laypage,layer = layui.layer,laydate = layui.laydate;
				// 分页
				laypage({
    				cont: 'demo1',
    				curr: <?php echo ($pageInfo['page']); ?>,
    				pages: <?php echo ($pageInfo['page_count']); ?>, //总页数
    				groups: 5, //连续显示分页数
    				jump: function(obj, first){
				    	var page = obj.curr;
				    	if (!first) {
				    		location.href = "<?php echo ($url); ?>?lottery_id=<?php echo ($lottery_id); ?>&page="+page;
				    	}
				    }
  				});

  				// 查看下注
  				$('.view-bet').on('click',function(){
  					var id = $(this).attr('data-id');
  					layer.open({
						type:2,
						title:'在线会员',
						shadeClose:true,
						shade:0.8,
						area:['80%','80%'],
						content:"<?php echo U('Game/betLog');?>?id="+id
					});
  				});

			});
		</script>
	</body>
</html>