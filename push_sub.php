<?php require 'inc.php';$u=me();header('Content-Type: application/json');
if(!$u||$_SERVER['REQUEST_METHOD']!='POST'||!hash_equals(tok(),$_SERVER['HTTP_X_CSRF']??'')){http_response_code(403);echo '{"ok":false}';exit;}
$j=json_decode(file_get_contents('php://input'),true)?:[];$ep=$j['endpoint']??'';
if(!empty($j['unsubscribe'])){$db->prepare('DELETE FROM push_subs WHERE endpoint=? AND user_id=?')->execute([$ep,$u['id']]);echo '{"ok":true}';exit;}
$p=$j['keys']['p256dh']??'';$a=$j['keys']['auth']??'';
if(!preg_match('#^https://#',$ep)||!$p||!$a){http_response_code(400);echo '{"ok":false}';exit;}
$db->prepare('REPLACE INTO push_subs(user_id,endpoint,p256dh,auth) VALUES(?,?,?,?)')->execute([$u['id'],$ep,$p,$a]);echo '{"ok":true}';
