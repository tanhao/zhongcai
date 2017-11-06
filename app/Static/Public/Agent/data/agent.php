<?php
header('Content-type:text/html; Charset=utf8');  
header( "Access-Control-Allow-Origin:*");  
header('Access-Control-Allow-Methods:POST');    
header('Access-Control-Allow-Headers:x-requested-with,content-type');
$type=$_REQUEST['type'];
$data=array();

if($type=='list'){
    $data['total']=30;
    $data['item']=array();
    for($i=0;$i<36;$i++){
       $data['item'][$i]['username']='user_'.$i;
       $data['item'][$i]['zj']=rand(100,90000);
       $data['item'][$i]['fd']=rand(0.01,0.06);
       $data['item'][$i]['tx']=rand(500,30000); 
    }
    print json_encode($data);
}
?>