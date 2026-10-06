<?php require 'inc.php';
$h=getenv('FLW_WEBHOOK_HASH');
if(!$h||!hash_equals($h,$_SERVER['HTTP_VERIF_HASH']??'')){http_response_code(401);exit;}
$j=json_decode(file_get_contents('php://input'),true);$id=$j['data']['id']??($j['id']??0);
if($id)flw_confirm($id);
http_response_code(200);echo 'ok';
