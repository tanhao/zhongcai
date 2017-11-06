<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html>

	<head>
		<meta charset="utf-8">
		<title>充值列表</title>
		<meta name="renderer" content="webkit">
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
		<link rel="stylesheet" href="/Static/Public/Cs/frame/layui/css/layui.css" media="all">
		<link rel="stylesheet" href="/Static/Public/Cs/frame/mystyle.css" />
		<!-- 注意：如果你直接复制所有代码到本地，上述css路径需要改成你本地的 -->
	</head>

	<body>
		<div class="my-btn-box">
			<form class="layui-form search-form" action="<?php echo U('Pay/recharge');?>" method="get">
				<span class="fl">
					 <div class="layui-form-item">
					    <div class="layui-input-inline sl" style="width: 100px;">
					      <select name="type">
					        <option value="-1" <?php if($type == -1): ?>selected<?php endif; ?>>全部类型</option>
					        <option value="1" <?php if($type == 1): ?>selected<?php endif; ?>>线下</option>
					        <option value="2" <?php if($type == 2): ?>selected<?php endif; ?>>支付宝</option>
					        <option value="3" <?php if($type == 3): ?>selected<?php endif; ?>>微信</option>
					        <option value="4" <?php if($type == 4): ?>selected<?php endif; ?>>其他</option>
					      </select>
					    </div>
					    <div class="layui-input-inline sl" style="width: 100px;">
					      <select name="sync">
					        <option value="-1" <?php if($sync == -1): ?>selected<?php endif; ?>>全部状态</option>
					        <option value="0" <?php if($sync == 0): ?>selected<?php endif; ?>>未到账</option>
					        <option value="1" <?php if($sync == 1): ?>selected<?php endif; ?>>已到账</option>
					      </select>
					    </div>
					  </div>
				</span>
				<span class="fr">
	     		   <span class="layui-form-label">搜索条件：</span>
					<div class="layui-input-inline">
						<input type="text" name="user_name" autocomplete="off" placeholder="请输入账号" class="layui-input" value="<?php echo ($user_name); ?>">
					</div>
					<button class="layui-btn mgl-20" type="submit">查询</button>
				</span>
    		</form>
		</div>
		<div class="layui-form">
			<table class="layui-table"> 
				<thead>
					<tr>
						<th>ID</th>
						<th>会员账号</th>
						<th>当前余额</th>
						<th>类型</th>
						<th>金额</th>
						<th>渠道名称</th>
						<th>订单号/卡号</th>
						<th>留言码</th>
						<th>充值时间</th>
						<th>到账金额</th>
						<th>到账时间</th>
						<th>状态</th>
						<th>操作人</th>
						<th>操作</th>
					</tr>
				</thead>
				<tbody>
					<?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$data): $mod = ($i % 2 );++$i;?><tr>
						<td class="id"><?php echo ($data['id']); ?></td>
						<td class="user_name"><?php echo ($data['user_name']); ?></td>
						<td><?php echo ($data['balance']); ?></td>
						<td>
							<?php if($data['type'] == 1): ?><a class="look-bank" data-id="<?php echo ($data['bank_id']); ?>" style="color: blue;">线下</a>
							<?php elseif($data['type'] == 2): ?>
							支付宝
							<?php else: ?>
							微信<?php endif; ?>
						</td>
						<td class="recharge_cash"><?php echo ($data['recharge_cash']); ?></td>
						<td><?php echo ($data['wayName']); ?></td>
						<td><?php echo ($data['account']); ?></td>
						<td><?php echo ($data['message']); ?></td>
						<td><?php echo (date("Y-m-d H:i:s",$data['add_time'])); ?></td>
						<td><?php echo ($data['real_cash']); ?></td>
						<td><?php if($data['pay_time']): echo (date("Y-m-d H:i:s",$data['pay_time'])); endif; ?></td>
						<td><?php if($data['sync'] == 1): ?>已到账<?php else: ?><font color="red">未到账</font><?php endif; ?></td>
						<td><?php echo ($data['mm_name']); ?></td>
						<td>
							<?php if($data['show_button']): ?><input type="hidden" class="bank_name" value="<?php echo ($data['bank_name']); ?>">
							<input type="hidden" class="real_name" value="<?php echo ($data['real_name']); ?>">
							<input type="hidden" class="account_number" value="<?php echo ($data['account_number']); ?>">
							<a class="layui-btn layui-btn-mini layui-btn-warm recharge-btn" data-balance="<?php echo ($data['recharge_cash']); ?>">充值</a><?php endif; ?>
						</td>
					</tr><?php endforeach; endif; else: echo "" ;endif; ?>
				</tbody>
			</table>
		</div>
		<div id="demo1"></div>

		<div id="form-content" style="display: none;">
			<form class="layui-form" action="" style="padding: 20px;">
			  <div class="layui-form-item">
			    <div class="layui-inline">
			      <label class="layui-form-label">银行卡：</label>
			      <div class="layui-input-inline">
			        <input type="text" name="bank_name" lay-verify="required" autocomplete="off" class="layui-input" disabled style="border: 0">
			      </div>
			    </div>
			  </div>
			  <div class="layui-form-item">
			    <div class="layui-inline">
			      <label class="layui-form-label">开户人：</label>
			      <div class="layui-input-inline">
			        <input type="text" name="real_name" lay-verify="required" autocomplete="off" class="layui-input" disabled style="border: 0">
			      </div>
			    </div>
			  </div>
			  <div class="layui-form-item">
			    <div class="layui-inline">
			      <label class="layui-form-label">卡号：</label>
			      <div class="layui-input-inline">
			        <input type="text" name="account_number" lay-verify="required" autocomplete="off" class="layui-input" disabled style="border: 0">
			      </div>
			    </div>
			  </div>
			  <div class="layui-form-item">
			    <div class="layui-inline">
			      <label class="layui-form-label">充值金额：</label>
			      <div class="layui-input-inline">
			        <input type="text" name="real_cash" lay-verify="required" autocomplete="off" class="layui-input">
			      </div>
			    </div>
			  </div>
			  <div class="layui-form-item">
			    <div class="layui-input-block">
			      <input type="hidden" name="id">
			      <button class="layui-btn" lay-submit="" lay-filter="demo1">立即提交</button>
			    </div>
			  </div>
			</form>
		</div>
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
				    		location.href = "<?php echo U('Pay/recharge');?>?type=<?php echo ($type); ?>&sync=<?php echo ($sync); ?>&user_name=<?php echo ($user_name); ?>&page="+page;
				    	}
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

				$('.sl dd').click(function(){
					var type = $(this).attr('lay-value');
					$('.search-form').submit();
				});

				//充值
				$('.recharge-btn').click(function(){
					var user_name = $(this).parents('tr').find('.user_name').text();
					var real_cash = $(this).parents('tr').find('.recharge_cash').text();
					var bank_name = $(this).parents('tr').find('.bank_name').val();
					var account_number = $(this).parents('tr').find('.account_number').val();
					var real_name = $(this).parents('tr').find('.real_name').val();
					var id = $(this).parents('tr').find('.id').text();
					$("#form-content input[name=real_cash]").val(real_cash);
					$("#form-content input[name=id]").val(id);
					$("#form-content input[name=bank_name]").val(bank_name);
					$("#form-content input[name=account_number]").val(account_number);
					$("#form-content input[name=real_name]").val(real_name);
					// 锁定哪个客服操作
					$.post("<?php echo U('Pay/lockOperateUser');?>",{id:id},function(res){
				        if(res.code > 0){
				            layer.open({
							  type: 1,
							  title: '充值给'+user_name,
							  closeBtn: 1,
							  shadeClose: true,
							  cancel:function(index, layero){location.reload();},
							  area:['auto','auto'],
							  content: $('#form-content')
							});
				        }else{
				            layer.msg(res.msg,{time:1800,icon: 5},function(){
				            	location.reload();
				            });
				        }
				    },'json');
						
				});

				// 查看下线充值银行卡
  				$('.look-bank').on('click',function(){
  					var bank_id = $(this).attr('data-id');
  					layer.open({
						type:2,
						title:'查看银行卡',
						shadeClose:true,
						shade:0.8,
						area:['600px','550px'],
						content:"<?php echo U('Pay/bankInfo');?>?bank_id="+bank_id
					});
  				});

				//监听提交
			  form.on('submit(demo1)', function(data){
			    $.post("<?php echo U('Pay/syncRecharge');?>",data.field,function(res){
			        if(res.code > 0){
			            layer.msg(res.msg,{time:1800,icon: 1},function(){
			                location.reload();
			            });
			        }else{
			            layer.msg(res.msg,{time:1800,icon: 5});
			        }
			    },'json');
			    return false;
			  });
			});
		</script>

	</body>

</html>