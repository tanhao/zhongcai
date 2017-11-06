<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html>

	<head>
		<meta charset="utf-8">
		<title>公告管理</title>
		<meta name="renderer" content="webkit">
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
		<link rel="stylesheet" href="/Static/Public/Cs/frame/layui/css/layui.css" media="all">
		<link rel="stylesheet" href="/Static/Public/Cs/frame/mystyle.css" />
		<!-- 注意：如果你直接复制所有代码到本地，上述css路径需要改成你本地的 -->
	</head>

	<body>
		<div class="my-btn-box">
			<span class="fl">
        		<a class="layui-btn layui-btn-danger radius btn-delect" id="btn-delete-all">批量删除</a>
        		<a href="#" class="layui-btn btn-add btn-default" id="btn-add-bank">添加</a>
    	</span>
		</div>
		<div class="layui-form">
			<table class="layui-table"> 
				<!-- <colgroup>
					<col width="50">
					<col width="50">
					<col width="150">
					<col width="300">
					<col width="150">
					<col width="200">
					<col>
				</colgroup> -->
				<thead>
					<tr>
						<th><input type="checkbox" lay-skin="primary" lay-filter="allChoose"></th>
						<th>ID</th>
						<th>发布人</th>
						<th>公告内容</th>
						<th>发布时间</th>
						<th>更新时间</th>
						<th>状态</th>
						<th>操作</th>
					</tr>
				</thead>
				<tbody>
					<?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$data): $mod = ($i % 2 );++$i;?><tr>
						<td><input name="id" type="checkbox" lay-skin="primary" value="<?php echo ($data['id']); ?>"></td>
						<td><?php echo ($data['id']); ?></td>
						<td><?php echo ($data['user_name']); ?></td>
						<td><?php echo ($data['content']); ?></td>
						<td><?php echo (date("Y-m-d H:i:s",$data['add_time'])); ?></td>
						<td><?php echo (date("Y-m-d H:i:s",$data['update_time'])); ?></td>
						<td>
							<?php if($data['status'] == 1): ?>使用中<?php else: ?>
								<a href="#" class="layui-btn layui-btn-mini layui-btn layui-btn-danger change-status" data-id="<?php echo ($data['id']); ?>">启用</a><?php endif; ?>
						</td>
						<td>
							<a href="#" class="layui-btn layui-btn-mini layui-btn-warm btn-edit-bank" data-url="<?php echo U('Manage/editNotice',array('id'=>$data['id']));?>">修改</a>
							<a href="#" class="layui-btn layui-btn-mini layui-btn-warm btn-del-bank" data-id="<?php echo ($data['id']); ?>">删除</a>
						</td>
					</tr><?php endforeach; endif; else: echo "" ;endif; ?>
				</tbody>
			</table>
		</div>

		<script src="/Static/Public/Cs/frame/layui/layui.js" charset="utf-8"></script>
		<!-- 注意：如果你直接复制所有代码到本地，上述js路径需要改成你本地的 -->
		<script>
			layui.use(['form','layer'], function() {
				var $ = layui.jquery,form = layui.form(),layer = layui.layer;

				// 添加
				$('#btn-add-bank').on('click',function(){
  					layer.open({
						type:2,
						title:'添加公告',
						shadeClose:true,
						shade:0.8,
						area:['50%','30%'],
						content:"<?php echo U('Manage/addNotice');?>"
					});
  				});

				// 修改
				$('.btn-edit-bank').on('click',function(){
					var data_url = $(this).attr('data-url');
  					layer.open({
						type:2,
						title:'修改公告',
						shadeClose:true,
						shade:0.8,
						area:['50%','30%'],
						content:data_url
					});
  				});

				// 启动、信用
  				$('.change-status').click(function(){
  					var id = $(this).attr('data-id');
  					$.post("<?php echo U('Manage/changeNoticeStatus');?>",{id:id},function(res){
  						if(res.code > 0){
				            layer.msg(res.msg,{time:1800,icon: 1},function(){
				                location.href = res.url;
				            });
				        }else{
				            layer.msg(res.msg,{time:1800,icon: 5});
				        }
  					},'json');
  				});

  				// 删除
  				$('.btn-del-bank').click(function(){
  					var id = $(this).attr('data-id');
  					$.post("<?php echo U('Manage/delNotice');?>",{id:id},function(res){
  						if(res.code > 0){
				            layer.msg(res.msg,{time:1800,icon: 1},function(){
				                location.href = res.url;
				            });
				        }else{
				            layer.msg(res.msg,{time:1800,icon: 5});
				        }
  					},'json');
  				});

  				// 批量删除
  				$('#btn-delete-all').click(function(){
			        var ids = $("input:checkbox[name=id]:checked").map(function(index,elem) {
			            return $(elem).val();
			        }).get().join(',');
			        if (ids) {
			        	$.post("<?php echo U('Manage/delNotice');?>",{id:ids},function(res){
	  						if(res.code > 0){
					            layer.msg(res.msg,{time:1800,icon: 1},function(){
					                location.href = res.url;
					            });
					        }else{
					            layer.msg(res.msg,{time:1800,icon: 5});
					        }
	  					},'json');
			        }
  				});
	  				

				//全选
				form.on('checkbox(allChoose)', function(data) {
					var child = $(data.elem).parents('table').find('tbody input[type="checkbox"]');
					child.each(function(index, item) {
						item.checked = data.elem.checked;
					});
					form.render('checkbox');
				});

			});
		</script>

	</body>

</html>