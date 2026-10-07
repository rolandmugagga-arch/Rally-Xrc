<?php
$f=$_GET['f']??'';if(!preg_match('/^[a-f0-9]{16}\.(mp4|mov|m4v|webm)$/',$f,$m)){http_response_code(404);exit;}
$p=__DIR__.'/uploads/files/'.$f;if(!is_file($p)){http_response_code(404);exit;}
$types=['mp4'=>'video/mp4','m4v'=>'video/mp4','mov'=>'video/quicktime','webm'=>'video/webm'];
$size=filesize($p);$start=0;$end=$size-1;
header('Content-Type: '.$types[$m[1]]);header('Accept-Ranges: bytes');header('Cache-Control: public, max-age=86400');
if(isset($_SERVER['HTTP_RANGE'])&&preg_match('/bytes=(\d*)-(\d*)/',$_SERVER['HTTP_RANGE'],$r)){
 if($r[1]===''){$start=max(0,$size-(int)$r[2]);}else{$start=(int)$r[1];if($r[2]!=='')$end=min((int)$r[2],$size-1);}
 if($start>$end||$start>=$size){http_response_code(416);header("Content-Range: bytes */$size");exit;}
 http_response_code(206);header("Content-Range: bytes $start-$end/$size");}
header('Content-Length: '.($end-$start+1));
$fh=fopen($p,'rb');fseek($fh,$start);$left=$end-$start+1;
while($left>0&&!feof($fh)){$c=fread($fh,min(131072,$left));echo $c;$left-=strlen($c);flush();if(connection_aborted())break;}
fclose($fh);
