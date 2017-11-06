<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>layui</title>
  <meta name="renderer" content="webkit">
  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
  <link rel="stylesheet" href="/Static/Public/Cs/frame//layui/css/layui.css"  media="all">
  <!-- 注意：如果你直接复制所有代码到本地，上述css路径需要改成你本地的 -->
</head>
<body>            
<fieldset class="layui-elem-field layui-field-title" style="margin-top: 20px;">
  <legend>平台统计</legend>
</fieldset>
  
<form class="layui-form">
  <div class="layui-form-item">
    <label class="layui-form-label">数据显示</label>
    <div class="layui-input-block">
      <table class="layui-table" style="width: auto;">
        <thead>
          <tr style="background-color: #5599FF;">
            <th>字段</th>
            <th>值</th>
          </tr> 
        </thead>
        <tbody>
          <tr><td style="background-color: #5FB878; color: #fff;">代理人数</td><td><?php echo ($agent_count); ?>人</td></tr>
          <tr><td style="background-color: #5FB878; color: #fff;">会员人数</td><td><?php echo ($user_count); ?>人</td></tr>
          <tr><td style="background-color: #5FB878; color: #fff;">在线人数</td><td><?php echo ($online_count); ?>人</td></tr>
          <tr><td style="background-color: #5FB878; color: #fff;">充值金额</td><td><?php echo ($recharge_balance); ?>元</td></tr>
          <tr><td style="background-color: #5FB878; color: #fff;">代理提现</td><td><?php echo ($agent_draw_balance); ?>元</td></tr>
          <tr><td style="background-color: #5FB878; color: #fff;">会员提现</td><td><?php echo ($user_draw_balance); ?>元</td></tr>
          <tr><td style="background-color: #5FB878; color: #fff;">平台总佣金</td><td><?php echo ($total_commission); ?>元</td></tr>
          <tr><td style="background-color: #5FB878; color: #fff;">公司总收入</td><td><?php echo ($my_commission); ?>元</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</form>
<script src="/Static/Public/Cs/frame/layui/layui.js" charset="utf-8"></script>

</body>
</html>