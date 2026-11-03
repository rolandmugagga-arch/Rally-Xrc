<?php require 'inc.php';require_once 'webauthn.php';
[$rp,$origin]=wa_rp();$a=$_GET['a']??'';$u=me();
function pj($x,$c=200){http_response_code($c);header('Content-Type: application/json');echo json_encode($x);exit;}
function need_csrf(){if(!hash_equals(tok(),$_SERVER['HTTP_X_CSRF']??''))pj(['ok'=>false,'msg'=>'Session expired. Reload the page and try again.'],403);}
function new_ch($k){$c=wa_b64u(random_bytes(32));$_SESSION['wa']=['c'=>$c,'t'=>time(),'k'=>$k];return $c;}
function take_ch($k){$w=$_SESSION['wa']??null;unset($_SESSION['wa']);return($w&&$w['k']===$k&&time()-$w['t']<=300)?$w['c']:null;}
try{
 if($a=='reg_options'){if(!$u)pj(['ok'=>false,'msg'=>'Please log in first.'],401);need_csrf();$ex=[];
  $s=$db->prepare('SELECT cred_id FROM passkeys WHERE user_id=?');$s->execute([$u['id']]);foreach($s->fetchAll(PDO::FETCH_COLUMN) as $c)$ex[]=['type'=>'public-key','id'=>$c];
  pj(['publicKey'=>['challenge'=>new_ch('reg'),'rp'=>['name'=>'Rally XRC','id'=>$rp],'user'=>['id'=>wa_b64u(pack('N',$u['id'])),'name'=>$u['email'],'displayName'=>$u['name']],'pubKeyCredParams'=>[['type'=>'public-key','alg'=>-7],['type'=>'public-key','alg'=>-257]],'authenticatorSelection'=>['residentKey'=>'preferred','userVerification'=>'required'],'timeout'=>60000,'attestation'=>'none','excludeCredentials'=>$ex]]);}
 if($a=='reg_verify'){if(!$u)pj(['ok'=>false,'msg'=>'Please log in first.'],401);need_csrf();$d=json_decode($_POST['data']??'',true);$ch=take_ch('reg');if(!is_array($d)||!$ch)pj(['ok'=>false,'msg'=>'That took too long. Please try again.'],400);
  [$cid,$pem,$cnt]=wa_register($d,$ch,$rp,$origin);$nm=trim(preg_replace('/[^\p{L}\p{N} ._-]/u','',(string)($_POST['name']??'')));$nm=mb_substr($nm,0,40)?:'My passkey';
  $db->prepare('INSERT INTO passkeys(user_id,cred_id,pubkey,sign_count,name) VALUES(?,?,?,?,?)')->execute([$u['id'],$cid,$pem,$cnt,$nm]);pj(['ok'=>true]);}
 if($a=='login_options')pj(['publicKey'=>['challenge'=>new_ch('login'),'rpId'=>$rp,'userVerification'=>'required','timeout'=>60000]]);
 if($a=='login_verify'){$d=json_decode($_POST['data']??'',true);$ch=take_ch('login');if(!is_array($d)||!$ch)pj(['ok'=>false,'msg'=>'That took too long. Please try again.'],400);
  $s=$db->prepare('SELECT * FROM passkeys WHERE cred_id=?');$s->execute([(string)($d['rawId']??'')]);$row=$s->fetch(PDO::FETCH_ASSOC);if(!$row)pj(['ok'=>false,'msg'=>'This passkey is not registered on this website.'],400);
  $cnt=wa_assert($d,$ch,$rp,$origin,$row);$db->prepare('UPDATE passkeys SET sign_count=? WHERE id=?')->execute([$cnt,$row['id']]);
  $s=$db->prepare('SELECT * FROM users WHERE id=?');$s->execute([$row['user_id']]);$usr=$s->fetch(PDO::FETCH_ASSOC);if(!$usr)pj(['ok'=>false,'msg'=>'That account no longer exists.'],400);
  session_regenerate_id(true);$_SESSION['uid']=$usr['id'];$nx=(string)($_POST['next']??'');
  pj(['ok'=>true,'url'=>$usr['role']=='admin'?'admin.php':(preg_match('/^(?:pay\.php(?:\?via=(?:mobile|card))?|driver\.php\?id=\d+)$/',$nx)?$nx:'index.php')]);}
 if($a=='remove'){if(!$u)pj(['ok'=>false],401);need_csrf();$db->prepare('DELETE FROM passkeys WHERE id=? AND user_id=?')->execute([(int)($_POST['id']??0),$u['id']]);pj(['ok'=>true]);}
 pj(['ok'=>false,'msg'=>'Unknown request.'],404);
}catch(Exception $e){pj(['ok'=>false,'msg'=>'Passkey check failed: '.$e->getMessage().'.'],400);}
