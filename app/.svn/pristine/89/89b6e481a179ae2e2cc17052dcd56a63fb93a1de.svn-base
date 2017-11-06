<?php 
	$code=$_REQUEST['code'];
	if($code==""){
		$code="88888888";
	}
    /*
    步骤：
        1.分别创建大小图画布并获取它们的宽高
        2.添加文字水印
        3.执行图片水印处理
        4.输出
        5.销毁画布
     */
    //1.分别创建大小图画布并获取它们的宽高
    $big = imagecreatefromjpeg('../img/2bg.jpg');
    $bx = imagesx($big);
    $by = imagesy($big);

    $small = imagecreatefrompng('../img/kdp9.png');
    $sx = imagesx($small);
   	$sy = imagesy($small);


    //2.添加水印文字
    $blue = imagecolorallocate($big,100,100,100);
    imagettftext($big,30,0,10,1050,$blue,'../fonts/2.ttf','扫描二维码下载广彩APP,全新玩法，官方开奖,刺激好玩!');
	$blue = imagecolorallocate($big,100,100,100);
    imagettftext($big,70,0,70,1160,$blue,'../fonts/2.ttf','注册安全码:'.$code);

    //3.执行图片水印处理
    imagecopymerge($big,$small,$bx-$sx,0,0,0,$sx,$sy,100);

    //4.输出到浏览器
    header('content-type: image/jpeg');
    imagejpeg($big);

    //5.销毁画布
    imagedestroy($big);
    imagedestroy($small);

    
 ?>