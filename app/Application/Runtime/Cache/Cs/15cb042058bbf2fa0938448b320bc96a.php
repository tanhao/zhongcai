<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>layui</title>
  <meta name="renderer" content="webkit">
  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
  <link rel="stylesheet" href="/Static/Public/Cs/frame/layui/css/layui.css"  media="all">
  <!-- 注意：如果你直接复制所有代码到本地，上述css路径需要改成你本地的 -->
</head>
<body>            
<!--fieldset class="layui-elem-field layui-field-title" style="margin-top: 20px;">
  <legend>基本设置</legend>
</fieldset-->
<form class="layui-form" action="" style="margin-top: 10px;">
  <div class="layui-form-item">
    <label class="layui-form-label">最低充值</label>
    <div class="layui-input-inline">
      <input name="min_recharge" class="layui-input" type="number" placeholder="请输入金额" autocomplete="off" lay-verify="required" value="<?php echo ($list['min_recharge']); ?>">
    </div>
  </div>
  
  <div class="layui-form-item">
    <label class="layui-form-label">最高充值</label>
    <div class="layui-input-inline">
      <input name="max_recharge" class="layui-input" type="number" placeholder="请输入金额" autocomplete="off" lay-verify="required" value="<?php echo ($list['max_recharge']); ?>">
    </div>
  </div>
  
  <div class="layui-form-item">
    <div class="layui-inline">
      <label class="layui-form-label">最低提现</label>
      <div class="layui-input-inline">
        <input name="min_draw" class="layui-input" type="number" autocomplete="off"  placeholder="请输入金额" value="<?php echo ($list['min_draw']); ?>">
      </div>
    </div>
  </div>
  <div class="layui-form-item">
    <div class="layui-inline">
      <label class="layui-form-label">最高提现</label>
      <div class="layui-input-inline">
        <input name="max_draw" class="layui-input" type="number" autocomplete="off"  placeholder="请输入金额" value="<?php echo ($list['max_draw']); ?>">
      </div>
    </div>
  </div>
  <div class="layui-form-item">
    <div class="layui-inline">
      <label class="layui-form-label">次数限制</label>
      <div class="layui-input-inline">
        <input name="free_draw_times" class="layui-input" type="number" autocomplete="off"  placeholder="请输入整数" value="<?php echo ($list['free_draw_times']); ?>">
      </div>
    </div>
  </div>
  <div class="layui-form-item">
    <div class="layui-inline">
      <label class="layui-form-label">手续费</label>
      <div class="layui-input-inline">
        <input name="draw_fee" class="layui-input" type="number" autocomplete="off"  placeholder="请输入小于1的金额" value="<?php echo ($list['draw_fee']); ?>">
      </div>
    </div>
  </div>
  <div class="layui-form-item">
    <div class="layui-inline">
      <label class="layui-form-label">流水倍数</label>
      <div class="layui-input-inline">
        <input name="water_times" class="layui-input" type="number" autocomplete="off"  placeholder="请输入整数" value="<?php echo ($list['water_times']); ?>">
      </div>
    </div>
  </div>
  <div class="layui-form-item">
    <div class="layui-input-block">
      <button class="layui-btn" lay-filter="demo1" lay-submit="">确定修改</button>
    </div>
  </div>
</form>
<script src="/Static/Public/Cs/frame/layui/layui.js" charset="utf-8"></script>
<!-- 注意：如果你直接复制所有代码到本地，上述js路径需要改成你本地的 -->
<script>
layui.use(['form', 'layer'], function () {
  var $ = layui.jquery,form = layui.form(),layer = layui.layer;

  //监听提交
  form.on('submit(demo1)', function(data){
    // layer.alert(JSON.stringify(data.field), {
    //   title: '最终的提交信息'
    // });
    // console.log(data.field);
    $.post("<?php echo U('Manage/cash');?>",data.field,function(res){
        if(res.code > 0){
            layer.msg(res.msg,{time:1800,icon: 1},function(){
                location.href = res.url;
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