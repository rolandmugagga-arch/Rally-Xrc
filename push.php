<?php
function b64u($s){return rtrim(strtr(base64_encode($s),'+/','-_'),'=');}
function b64d($s){return base64_decode(strtr($s,'-_','+/'));}
function ossl_cfg(){static $c=null;if($c===null){$c=['curve_name'=>'prime256v1','private_key_type'=>OPENSSL_KEYTYPE_EC];if(!@openssl_pkey_new($c)){$f=sys_get_temp_dir().'/rx_openssl.cnf';@file_put_contents($f,'');$c['config']=$f;}}return $c;}
function ossl_new(){return openssl_pkey_new(ossl_cfg());}
function vapid_keys(){global $db;$pr=setting('vapid_priv');$pb=setting('vapid_pub');if($pr&&$pb)return [$pr,$pb];
 $k=ossl_new();if(!$k)return [null,null];$o=ossl_cfg();openssl_pkey_export($k,$pem,null,isset($o['config'])?['config'=>$o['config']]:[]);$d=openssl_pkey_get_details($k)['ec'];
 $pub=b64u("\x04".str_pad($d['x'],32,"\0",STR_PAD_LEFT).str_pad($d['y'],32,"\0",STR_PAD_LEFT));
 $st=$db->prepare('REPLACE INTO settings(k,v) VALUES(?,?)');$st->execute(['vapid_priv',$pem]);$st->execute(['vapid_pub',$pub]);return [$pem,$pub];}
function der_to_raw($der){$o=4;$rl=ord($der[3]);$r=substr($der,$o,$rl);$o+=$rl+1;$sl=ord($der[$o]);$s=substr($der,$o+1,$sl);
 return str_pad(ltrim($r,"\0"),32,"\0",STR_PAD_LEFT).str_pad(ltrim($s,"\0"),32,"\0",STR_PAD_LEFT);}
function vapid_jwt($aud){[$pem]=vapid_keys();$h=b64u(json_encode(['typ'=>'JWT','alg'=>'ES256']));$c=b64u(json_encode(['aud'=>$aud,'exp'=>time()+43200,'sub'=>'mailto:'.(getenv('ADMIN_EMAIL')?:'admin@example.com')]));
 openssl_sign("$h.$c",$sig,$pem,OPENSSL_ALGO_SHA256);return "$h.$c.".b64u(der_to_raw($sig));}
function push_encrypt($payload,$p256dh,$auth){$ua=b64d($p256dh);$as=b64d($auth);
 $k=ossl_new();$d=openssl_pkey_get_details($k)['ec'];
 $asPub="\x04".str_pad($d['x'],32,"\0",STR_PAD_LEFT).str_pad($d['y'],32,"\0",STR_PAD_LEFT);
 $spki=hex2bin('3059301306072a8648ce3d020106082a8648ce3d030107034200').$ua;
 $uaKey=openssl_pkey_get_public("-----BEGIN PUBLIC KEY-----\n".chunk_split(base64_encode($spki),64,"\n")."-----END PUBLIC KEY-----\n");
 $secret=openssl_pkey_derive($uaKey,$k);
 $ikm=hash_hkdf('sha256',$secret,32,"WebPush: info\0".$ua.$asPub,$as);$salt=random_bytes(16);
 $cek=hash_hkdf('sha256',$ikm,16,"Content-Encoding: aes128gcm\0",$salt);$nonce=hash_hkdf('sha256',$ikm,12,"Content-Encoding: nonce\0",$salt);
 $ct=openssl_encrypt($payload."\x02",'aes-128-gcm',$cek,OPENSSL_RAW_DATA,$nonce,$tag,'',16);
 return $salt.pack('N',4096).chr(65).$asPub.$ct.$tag;}
function push_send($sub,$title,$body,$url='/'){$ep=$sub['endpoint'];$u=parse_url($ep);$aud=$u['scheme'].'://'.$u['host'];[, $pub]=vapid_keys();
 $data=push_encrypt(json_encode(['title'=>$title,'body'=>$body,'url'=>$url]),$sub['p256dh'],$sub['auth']);
 $c=curl_init($ep);curl_setopt_array($c,[CURLOPT_POST=>1,CURLOPT_POSTFIELDS=>$data,CURLOPT_RETURNTRANSFER=>1,CURLOPT_TIMEOUT=>8,CURLOPT_HTTPHEADER=>['Content-Type: application/octet-stream','Content-Encoding: aes128gcm','TTL: 86400','Urgency: normal','Authorization: vapid t='.vapid_jwt($aud).', k='.$pub]]);
 curl_exec($c);return (int)curl_getinfo($c,CURLINFO_HTTP_CODE);}
function push_all($title,$body,$url='/',$where='1'){global $db;$sent=0;$gone=0;if(!vapid_keys()[0])return [0,0];
 foreach($db->query("SELECT s.* FROM push_subs s JOIN users u ON u.id=s.user_id WHERE $where")->fetchAll(PDO::FETCH_ASSOC) as $s){$c=push_send($s,$title,$body,$url);
  if($c>=200&&$c<300)$sent++;elseif($c==404||$c==410){$db->prepare('DELETE FROM push_subs WHERE id=?')->execute([$s['id']]);$gone++;}}
 return [$sent,$gone];}
