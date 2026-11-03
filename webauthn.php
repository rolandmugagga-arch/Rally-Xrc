<?php
// Passkey (WebAuthn) support without outside libraries. Supports ES256 and RS256 keys.
function wa_b64u($s){return rtrim(strtr(base64_encode($s),'+/','-_'),'=');}
function wa_b64d($s){return base64_decode(strtr((string)$s,'-_','+/'));}
function wa_rp(){$h=$_SERVER['HTTP_HOST']??'localhost';$id=preg_replace('/:\d+$/','',$h);$pr=$_SERVER['HTTP_X_FORWARDED_PROTO']??(!empty($_SERVER['HTTPS'])?'https':'http');return [$id,$pr.'://'.$h];}
function wa_cbor($b,&$o=0){$ib=ord($b[$o++]);$mt=$ib>>5;$ai=$ib&31;
 if($ai<24)$v=$ai;elseif($ai==24)$v=ord($b[$o++]);elseif($ai==25){$v=unpack('n',substr($b,$o,2))[1];$o+=2;}elseif($ai==26){$v=unpack('N',substr($b,$o,4))[1];$o+=4;}elseif($ai==27){$v=unpack('J',substr($b,$o,8))[1];$o+=8;}else throw new Exception('unsupported data');
 switch($mt){case 0:return $v;case 1:return -1-$v;case 2:case 3:$r=substr($b,$o,$v);$o+=$v;return $r;
  case 4:$a=[];for($i=0;$i<$v;$i++)$a[]=wa_cbor($b,$o);return $a;
  case 5:$m=[];for($i=0;$i<$v;$i++){$k=wa_cbor($b,$o);$m[$k]=wa_cbor($b,$o);}return $m;
  case 7:return $ai==20?false:($ai==21?true:null);}
 throw new Exception('unsupported data');}
function wa_authdata($ad){if(strlen($ad)<37)throw new Exception('bad data');
 $r=['hash'=>substr($ad,0,32),'flags'=>ord($ad[32]),'count'=>unpack('N',substr($ad,33,4))[1],'cred'=>null];
 if($r['flags']&0x40){$o=37+16;$l=unpack('n',substr($ad,$o,2))[1];$o+=2;$cid=substr($ad,$o,$l);$o+=$l;$r['cred']=['id'=>$cid,'key'=>wa_cbor($ad,$o)];}
 return $r;}
function wa_len($n){return $n<128?chr($n):($n<256?"\x81".chr($n):"\x82".pack('n',$n));}
function wa_int($x){$x=ltrim($x,"\0");if($x===''||ord($x[0])>127)$x="\0".$x;return "\x02".wa_len(strlen($x)).$x;}
function wa_pem($k){
 if(($k[1]??0)==2&&($k[3]??0)==-7&&strlen($k[-2]??'')==32&&strlen($k[-3]??'')==32)$spki=hex2bin('3059301306072a8648ce3d020106082a8648ce3d030107034200')."\x04".$k[-2].$k[-3];
 elseif(($k[1]??0)==3&&($k[3]??0)==-257){$ints=wa_int($k[-1]).wa_int($k[-2]);$rsa="\x30".wa_len(strlen($ints)).$ints;$bit="\x03".wa_len(strlen($rsa)+1)."\0".$rsa;$alg=hex2bin('300d06092a864886f70d0101010500');$spki="\x30".wa_len(strlen($alg)+strlen($bit)).$alg.$bit;}
 else throw new Exception('this kind of passkey is not supported');
 return "-----BEGIN PUBLIC KEY-----\n".chunk_split(base64_encode($spki),64,"\n")."-----END PUBLIC KEY-----\n";}
function wa_client($d,$type,$ch,$origin){$cd=wa_b64d($d['response']['clientDataJSON']??'');$c=json_decode($cd,true);
 if(!is_array($c)||($c['type']??'')!==$type||!hash_equals((string)$ch,(string)($c['challenge']??''))||($c['origin']??'')!==$origin)throw new Exception('the browser check failed');return $cd;}
function wa_register($d,$ch,$rp,$origin){wa_client($d,'webauthn.create',$ch,$origin);
 $att=wa_cbor(wa_b64d($d['response']['attestationObject']??''));$ad=wa_authdata($att['authData']??'');
 if(!hash_equals(hash('sha256',$rp,true),$ad['hash'])||!($ad['flags']&1)||!($ad['flags']&4)||!$ad['cred'])throw new Exception('the device did not confirm it was you');
 return [wa_b64u($ad['cred']['id']),wa_pem($ad['cred']['key']),$ad['count']];}
function wa_assert($d,$ch,$rp,$origin,$row){$cd=wa_client($d,'webauthn.get',$ch,$origin);$raw=wa_b64d($d['response']['authenticatorData']??'');$ad=wa_authdata($raw);
 if(!hash_equals(hash('sha256',$rp,true),$ad['hash'])||!($ad['flags']&1)||!($ad['flags']&4))throw new Exception('the device did not confirm it was you');
 if(openssl_verify($raw.hash('sha256',$cd,true),wa_b64d($d['response']['signature']??''),$row['pubkey'],OPENSSL_ALGO_SHA256)!==1)throw new Exception('the signature is wrong');
 if(($ad['count']>0||(int)$row['sign_count']>0)&&$ad['count']<=(int)$row['sign_count'])throw new Exception('this passkey looks copied');
 return $ad['count'];}
function wa_js(){return <<<'JS'
const b2u=b=>btoa(String.fromCharCode(...new Uint8Array(b))).replace(/\+/g,'-').replace(/\//g,'_').replace(/=+$/,'');
const u2b=s=>{s=s.replace(/-/g,'+').replace(/_/g,'/');s+='='.repeat((4-s.length%4)%4);return Uint8Array.from(atob(s),c=>c.charCodeAt(0)).buffer};
const pkOK=()=>!!(window.PublicKeyCredential&&navigator.credentials);
async function pkPost(a,body){const r=await fetch('passkey.php?a='+a,{method:'POST',headers:{'X-CSRF':PKT,'Content-Type':'application/x-www-form-urlencoded'},body});return r.json()}
async function pkLogin(next,out){try{if(!pkOK()){out.textContent='This browser does not support passkeys.';return}
const o=(await (await fetch('passkey.php?a=login_options')).json()).publicKey;o.challenge=u2b(o.challenge);
const c=await navigator.credentials.get({publicKey:o});
const d={rawId:b2u(c.rawId),response:{clientDataJSON:b2u(c.response.clientDataJSON),authenticatorData:b2u(c.response.authenticatorData),signature:b2u(c.response.signature)}};
const j=await pkPost('login_verify','data='+encodeURIComponent(JSON.stringify(d))+'&next='+encodeURIComponent(next||''));if(j.ok)location.href=j.url;else out.textContent=j.msg||'Passkey sign-in failed.'}catch(e){out.textContent='Passkey sign-in was cancelled or failed.'}}
async function pkAdd(name,out){try{if(!pkOK()){out.textContent='This browser does not support passkeys.';return}
const r=await (await fetch('passkey.php?a=reg_options',{headers:{'X-CSRF':PKT}})).json();if(!r.publicKey){out.textContent=r.msg||'Could not start.';return}
const o=r.publicKey;o.challenge=u2b(o.challenge);o.user.id=u2b(o.user.id);(o.excludeCredentials||[]).forEach(c=>c.id=u2b(c.id));
const c=await navigator.credentials.create({publicKey:o});
const d={rawId:b2u(c.rawId),response:{clientDataJSON:b2u(c.response.clientDataJSON),attestationObject:b2u(c.response.attestationObject)}};
const j=await pkPost('reg_verify','data='+encodeURIComponent(JSON.stringify(d))+'&name='+encodeURIComponent(name||''));if(j.ok)location.reload();else out.textContent=j.msg||'Could not save the passkey.'}catch(e){out.textContent='Adding the passkey was cancelled or failed.'}}
JS;}
function passkey_panel(){global $db;$u=me();if(!$u)return '';$s=$db->prepare('SELECT id,name,created FROM passkeys WHERE user_id=? ORDER BY id');$s->execute([$u['id']]);
 $o='<h3>Passkeys</h3><p>Sign in with your fingerprint, face or screen lock instead of typing a password. Passkeys only work on this website address.</p>';
 foreach($s->fetchAll(PDO::FETCH_ASSOC) as $r)$o.='<p>'.h($r['name']).' <small>added '.h(substr((string)$r['created'],0,10)).'</small> <button type="button" class="x" data-pk="'.(int)$r['id'].'">Remove</button></p>';
 $o.='<p><input id="pkn" placeholder="Name this device, for example My phone" maxlength="40"></p><p><button type="button" class="btn" id="pka">Add a passkey on this device</button></p><p id="pkm"></p>';
 return $o.'<script>const PKT='.json_encode(tok()).';'.wa_js().'document.getElementById("pka").onclick=()=>pkAdd(document.getElementById("pkn").value,document.getElementById("pkm"));document.querySelectorAll("[data-pk]").forEach(b=>b.onclick=()=>pkPost("remove","id="+b.dataset.pk).then(()=>location.reload()));</script>';}
