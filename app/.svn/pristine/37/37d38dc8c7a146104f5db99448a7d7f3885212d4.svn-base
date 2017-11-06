<?php
header('Content-Type: application/x-javascript; charset=UTF-8');
//一级菜单 彩种
$data=array();

$data[0]['value']='all';
$data[0]['text']='全部期数';
$t=1;

for($i=$t;$i<$t+11;$i++){
    $qishu=658001+$i;
	$data[$i]['value']="1-".$qishu;
	$data[$i]['text']="北京赛车 ".$qishu."期";
}
$t=$i;
for($i=$t;$i<$t+11;$i++){
    $qishu=20170822001+$i;
	$data[$i]['value']="2-".$qishu."";
	$data[$i]['text']="重庆时时彩".$qishu."期";
}
$t=$i;
for($i=$t;$i<$t+11;$i++){
    $qishu=20170822001+$i;
	$data[$i]['value']="'3-".$qishu."'";
	$data[$i]['text']="幸运飞艇".$qishu."期";
}
//print_r($data);
//exit;
$data_str=json_encode($data);
echo 'var qishuData='.$data_str.';'; 
?>