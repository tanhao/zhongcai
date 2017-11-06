<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html>

	<head>
		<meta charset="utf-8">
		<title>提现列表</title>
		<meta name="renderer" content="webkit">
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
		<link rel="stylesheet" href="/Static/Public/Cs/frame/layui/css/layui.css" media="all">
		<link rel="stylesheet" href="/Static/Public/Cs/frame/mystyle.css" />
		<!-- 注意：如果你直接复制所有代码到本地，上述css路径需要改成你本地的 -->
	</head>

	<body>
		<div class="my-btn-box">
			<form class="layui-form" action="<?php echo U('Pay/draw');?>" method="get">
				<span class="fl">
					 <div class="layui-form-item">
					    <div class="layui-input-inline sl" style="width: 100px;">
					      <select name="type">
					        <option value="-1" <?php if($type == -1): ?>selected<?php endif; ?>>全部类型</option>
					        <option value="2" <?php if($type == 2): ?>selected<?php endif; ?>>代理</option>
					        <option value="1" <?php if($type == 1): ?>selected<?php endif; ?>>会员</option>
					      </select>
					    </div>			    
					  </div>
				</span>
				<span class="fl">
					 <div class="layui-form-item">
					    <div class="layui-input-inline sl" style="width: 100px;">
					      <select name="sync">
					        <option value="-1" <?php if($sync == -1): ?>selected<?php endif; ?>>全部状态</option>
					        <option value="1" <?php if($sync == 1): ?>selected<?php endif; ?>>待处理</option>
					        <option value="2" <?php if($sync == 2): ?>selected<?php endif; ?>>已完成</option>
					        <option value="3" <?php if($sync == 3): ?>selected<?php endif; ?>>已取消</option>
					        <option value="4" <?php if($sync == 4): ?>selected<?php endif; ?>>有问题</option>
					      </select>
					    </div>			    
					  </div>
				</span>
				<span class="fr">
	     		   <span class="layui-form-label">搜索条件：</span>
					<div class="layui-input-inline">
						<input type="text" name="user_name" autocomplete="off" placeholder="请输入账号" class="layui-input" value="<?php echo ($user_name); ?>">
					</div>
					<button class="layui-btn mgl-20">查询</button>
				</span>
    		</form>
		</div>
		<div class="layui-form">
			<table class="layui-table"> 
				<thead>
					<tr>
						<th>ID</th>
						<th>账号类型</th>
						<th>用户账号</th>
						<th>提现金额</th>
						<th>实际金额</th>
						<th>用户余额</th>
						<th>申请时间</th>
						<th>状态</th>
						<th>操作人</th>
						<th>操作</th>
					</tr>
				</thead>
				<tbody>
					<?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$data): $mod = ($i % 2 );++$i;?><tr>
						<td><?php echo ($data['id']); ?></td>
						<td><?php echo ($data['typeName']); ?></td>
						<td><?php echo ($data['user_name']); ?></td>
						<td><?php echo ($data['apply_cash']); ?></td>
						<td><?php echo ($data['real_cash']); ?></td>
						<td><?php echo ($data['balance']); ?></td>
						<td><?php echo (date("Y-m-d H:i:s",$data['add_time'])); ?></td>
						<td><?php echo ($data['status']); ?></td>
						<td><?php echo ($data['cs_name']); ?></td>
						<td>
						<a class="layui-btn layui-btn-mini layui-btn-warm act-btn-0" data-id="<?php echo ($data['id']); ?>">查看</a>
						<?php if($data['sync'] == 0): ?><a class="layui-btn layui-btn-mini layui-btn-warm act-btn-1" data-id="<?php echo ($data['id']); ?>">打款</a>
							<a class="layui-btn layui-btn-mini layui-btn-warm act-btn-2" data-id="<?php echo ($data['id']); ?>">取消</a>
							<a class="layui-btn layui-btn-mini layui-btn-warm act-btn-3" data-id="<?php echo ($data['id']); ?>">有问题</a><?php endif; ?>
						</td>
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

				$('.act-btn-0').click(function(){
					var id = $(this).attr('data-id');
  					layer.open({
						type:2,
						title:'提现的银行卡信息',
						shadeClose:true,
						shade:0.8,
						area:['500px','400px'],
						content:"<?php echo U('Pay/checkDraw');?>?id="+id
					});
				});

				// 分页
				laypage({
    				cont: 'demo1',
    				curr: <?php echo ($pageInfo['page']); ?>,
    				pages: <?php echo ($pageInfo['page_count']); ?>, //总页数
    				groups: 5, //连续显示分页数
    				jump: function(obj, first){
				    	var page = obj.curr;
				    	if (!first) {
				    		location.href = "<?php echo U('Pay/draw');?>?type=<?php echo ($type); ?>&sync=<?php echo ($sync); ?>&user_name=<?php echo ($user_name); ?>&page="+page;
				    	}
				    }
  				});
				// 已打款
				$('.act-btn-1').click(function(){
					var id = $(this).attr('data-id');
					var sync = 1;
					$.post("<?php echo U('Pay/changeDrawStatus');?>",{id:id,sync:sync},function(res){
				        if(res.code > 0){
				            layer.msg(res.msg,{time:1800,icon: 1});
				        }else{
				            layer.msg(res.msg,{time:1800,icon: 5});
				        }
				        location.reload();
				    },'json');
				});

				// 取消
				$('.act-btn-2').click(function(){
					var id = $(this).attr('data-id');
					var sync = 2;
					$.post("<?php echo U('Pay/changeDrawStatus');?>",{id:id,sync:sync},function(res){
				        if(res.code > 0){
				            layer.msg(res.msg,{time:1800,icon: 1});
				        }else{
				            layer.msg(res.msg,{time:1800,icon: 5});
				        }
				        location.reload();
				    },'json');
				});

				// 有问题
				$('.act-btn-3').click(function(){
					var id = $(this).attr('data-id');
					var sync = 3;
					$.post("<?php echo U('Pay/changeDrawStatus');?>",{id:id,sync:sync},function(res){
				        if(res.code > 0){
				            layer.msg(res.msg,{time:1800,icon: 1});
				        }else{
				            layer.msg(res.msg,{time:1800,icon: 5});
				        }
				        location.reload();
				    },'json');
				});
				
				$('.sl dd').click(function(){
					var type = $(this).attr('lay-value');
					$('form').submit();
				});

			});
		</script>

	</body>

</html>