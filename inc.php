<?php
ini_set('display_errors','0');error_reporting(E_ALL);
$sp=getenv('DB_PATH')?dirname(getenv('DB_PATH')).'/sessions':'';if($sp){@mkdir($sp,0775,true);if(is_dir($sp)&&is_writable($sp))session_save_path($sp);}
ini_set('session.gc_maxlifetime','2592000');
session_set_cookie_params(['lifetime'=>2592000,'httponly'=>true,'samesite'=>'Lax']);
session_start();
$dbp=getenv('DB_PATH')?:__DIR__.'/data/rallyx.sqlite';@mkdir(dirname($dbp),0775,true);@mkdir(__DIR__.'/uploads/files',0775,true);
$db=new PDO('sqlite:'.$dbp);
$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$db->exec("CREATE TABLE IF NOT EXISTS users(id INTEGER PRIMARY KEY,name,email UNIQUE,pass,role DEFAULT 'member',kind,age_group,car,phone,created DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE IF NOT EXISTS posts(id INTEGER PRIMARY KEY,title,body,created DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE IF NOT EXISTS settings(k PRIMARY KEY,v);
CREATE TABLE IF NOT EXISTS events(id INTEGER PRIMARY KEY,title,day,place);
CREATE TABLE IF NOT EXISTS accounts(id INTEGER PRIMARY KEY,label,number,network);
CREATE TABLE IF NOT EXISTS deposits(id INTEGER PRIMARY KEY,user_id INTEGER,amount INTEGER,month,network,phone,txid,note,status DEFAULT 'pending',seen INTEGER DEFAULT 0,created DEFAULT CURRENT_TIMESTAMP,UNIQUE(network,txid));
CREATE TABLE IF NOT EXISTS media(id INTEGER PRIMARY KEY,kind,path,caption,created DEFAULT CURRENT_TIMESTAMP);");
if(!in_array('network',array_column($db->query('PRAGMA table_info(users)')->fetchAll(PDO::FETCH_ASSOC),'name')))$db->exec('ALTER TABLE users ADD COLUMN network');
if(!$db->query('SELECT 1 FROM accounts')->fetch())$db->exec("INSERT INTO accounts(label,number,network) VALUES('Club mobile money','0708719834','MTN and Airtel')");
if($hv=(int)$db->query("SELECT v FROM settings WHERE k='hero_video'")->fetchColumn()){$db->exec("INSERT OR IGNORE INTO settings(k,v) VALUES('hero_media',$hv)");$db->exec("DELETE FROM settings WHERE k='hero_video'");}
foreach([['users','photo'],['media','sort'],['users','team'],['users','car_type'],['users','inspired'],['users','show_photo'],['media','featured'],['users','car_photo'],['media','circle']] as [$t,$c])if(!in_array($c,array_column($db->query("PRAGMA table_info($t)")->fetchAll(PDO::FETCH_ASSOC),'name')))$db->exec("ALTER TABLE $t ADD COLUMN $c");
$db->exec("UPDATE media SET sort=id WHERE sort IS NULL");$db->exec("UPDATE users SET team=car WHERE team IS NULL AND car IS NOT NULL AND car!=''");
$db->exec("UPDATE users SET age_group='Junior' WHERE age_group='".'Y'."outh'");$db->exec("UPDATE users SET age_group='Senior' WHERE age_group='".'A'."dult'");
$db->exec("CREATE TABLE IF NOT EXISTS push_subs(id INTEGER PRIMARY KEY,user_id INTEGER,endpoint TEXT UNIQUE,p256dh,auth,created DEFAULT CURRENT_TIMESTAMP)");
$db->exec("UPDATE users SET phone='+256'||substr(phone,2) WHERE phone LIKE '0%' AND length(phone)=10");
$db->exec("CREATE TABLE IF NOT EXISTS sponsors(id INTEGER PRIMARY KEY,name,logo,url,sort INTEGER)");
if(!setting('sponsors_seeded')&&is_file(__DIR__.'/uploads/amra-u.jpg')){$db->exec("INSERT INTO sponsors(name,logo,url,sort) VALUES('AMRA.U','uploads/amra-u.jpg','',1)");$db->exec("REPLACE INTO settings(k,v) VALUES('sponsors_seeded','1')");}
$db->exec("CREATE TABLE IF NOT EXISTS articles(id INTEGER PRIMARY KEY,title,body,image,sort INTEGER,created DEFAULT CURRENT_TIMESTAMP)");
if(!setting('articles_seeded')&&is_file(__DIR__.'/seed_articles.php')){foreach(require __DIR__.'/seed_articles.php' as $i=>$a){$g=glob(__DIR__.'/uploads/seed/'.$a[2].'.*');$db->prepare('INSERT INTO articles(title,body,image,sort) VALUES(?,?,?,?)')->execute([$a[0],$a[1],$g?'uploads/seed/'.basename($g[0]):null,$i+1]);}$db->exec("REPLACE INTO settings(k,v) VALUES('articles_seeded','1')");}
$cap=['Wheel up on the pit stand','Controllers out, cars on the slope','Josh Rally Team meets RallyXRC','Ford Fiesta in full livery','KCB Bank Subaru','The grid before the start','Two Subarus, rear view','Mechanic plugging in the battery','Citroen C3 WRC beside a Land Cruiser','Racing on the dirt stage','Sliding through the corner','Dust on the open stage','Fans gather at the chalk line','Stage run','Driver and car','Side-on action','Volkswagen Polo WRC','Black GR car throwing dirt','Rear view of a slide'];
if(!$db->query("SELECT 1 FROM users WHERE role='admin'")->fetch()){
 $db->prepare("INSERT INTO users(name,email,pass,role,kind) VALUES('Admin',?,?,'admin','Admin')")->execute([getenv('ADMIN_EMAIL')?:'rolandmugagga@gmail.com',password_hash(getenv('ADMIN_PASSWORD')?:'Roland12',PASSWORD_DEFAULT)]);
 foreach(glob(__DIR__.'/uploads/seed/*.{jpg,jpeg,png,webp}',GLOB_BRACE) as $i=>$f)$db->prepare("INSERT INTO media(kind,path,caption,sort) VALUES('image',?,?,(SELECT COALESCE(MAX(sort),0)+1 FROM media))")->execute(['uploads/seed/'.basename($f),$cap[$i]??'']);
 $db->prepare("INSERT INTO posts(title,body) VALUES(?,?)")->execute(['Welcome to Rally XRC','Rally XRC is the home of RC rally racing in Uganda. Drivers, co-drivers, teams, mechanics and fans can now register in one place. Junior and senior leagues are both open.']);
}
function h($s){return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');}
function setting($k,$d=''){global $db;$s=$db->prepare('SELECT v FROM settings WHERE k=?');$s->execute([$k]);$v=$s->fetchColumn();return($v!==false&&$v!=='')?$v:$d;}
function countries(){static $c=null;if($c===null)$c=require __DIR__.'/countries.php';return $c;}
function dial_ok($cc){foreach(countries() as $r)if($r[2]===$cc)return true;return false;}
function normphone($p,$cc='256'){$p=preg_replace('/[\s\-().]/','',(string)$p);$cc=preg_replace('/\D/','',(string)$cc);if(!dial_ok($cc))return false;
 if(preg_match('/^(?:\+|00)(\d{8,15})$/',$p,$m))$d=$m[1];else{$n=ltrim($p,'0');if(!preg_match('/^\d{6,14}$/',$n))return false;$d=$cc.$n;}
 if(strpos($d,'256')===0&&!preg_match('/^256\d{9}$/',$d))return false;
 return preg_match('/^\d{8,15}$/',$d)?'+'.$d:false;}
function split_phone($p){$p=(string)$p;if(preg_match('/^0\d{9}$/',$p))return ['256',substr($p,1)];$d=ltrim($p,'+');foreach([3,2,1] as $n){$c=substr($d,0,$n);if(strlen($d)>$n&&dial_ok($c))return [$c,substr($d,$n)];}return ['256',ltrim($p,'+0')];}
function phone_field($cc='256',$nat=''){$cc=preg_replace('/\D/','',(string)$cc);$cur='';foreach(countries() as $r)if($r[2]===$cc){$cur=$r[0].' +'.$r[2];break;}
 $data=json_encode(array_map(fn($r)=>['n'=>$r[0],'i'=>$r[1],'d'=>$r[2]],countries()),JSON_UNESCAPED_UNICODE|JSON_HEX_TAG);
 $js=<<<'PJ'
(function(){const D=__DATA__,w=document.currentScript.parentNode,i=w.querySelector('.cs'),l=w.querySelector('.cl'),h=w.querySelector('[name=cc]');h.dataset.t=i.value;
const nm=s=>s.toLowerCase().replace(/[^a-z0-9 ]/g,'');
function render(q){q=nm(q);const m=D.filter(c=>!q||nm(c.n).includes(q)||c.i.toLowerCase()==q||c.d.startsWith(q)).slice(0,60);
l.innerHTML=m.map(c=>'<div role="option" data-d="'+c.d+'" data-t="'+c.n.replace(/"/g,'&quot;')+' +'+c.d+'">'+c.n+' <small>+'+c.d+'</small></div>').join('')||'<div>No match</div>';l.hidden=false}
function pick(el){if(!el||!el.dataset.d)return;h.value=el.dataset.d;h.dataset.t=el.dataset.t;i.value=el.dataset.t;l.hidden=true}
i.addEventListener('focus',()=>{i.select();render('')});i.addEventListener('input',()=>render(i.value));
i.addEventListener('blur',()=>{l.hidden=true;i.value=h.dataset.t});
i.addEventListener('keydown',e=>{if(e.key=='Enter'){e.preventDefault();pick(l.querySelector('div[data-d]'))}if(e.key=='Escape'){l.hidden=true;i.blur()}});
l.addEventListener('mousedown',e=>{e.preventDefault();pick(e.target.closest('div'))});
})()
PJ;
 return '<div class="fld"><b>Mobile number</b><span class="phf"><span class="cp"><input class="cs" type="text" value="'.h($cur).'" placeholder="Search country or code" autocomplete="off" aria-label="Country, search by name or calling code"><span class="cl" hidden></span></span><input type="hidden" name="cc" value="'.h($cc).'"><input name="phone" type="tel" inputmode="tel" value="'.h($nat).'" placeholder="Number without country code" required></span><script>'.str_replace('__DATA__',$data,$js).'</script></div>';}
function ugx($n){return 'UGX '.number_format((int)$n);}
function notify($t){$tk=getenv('TELEGRAM_BOT_TOKEN');$ch=getenv('TELEGRAM_CHAT_ID');if(!$tk||!$ch)return;@file_get_contents("https://api.telegram.org/bot$tk/sendMessage",false,stream_context_create(['http'=>['method'=>'POST','header'=>"Content-Type: application/x-www-form-urlencoded\r\n",'content'=>http_build_query(['chat_id'=>$ch,'text'=>$t]),'timeout'=>3]]));}
function flw($method,$path,$body=null){$k=getenv('FLW_SECRET_KEY');if(!$k)return null;$c=curl_init('https://api.flutterwave.com/v3'.$path);curl_setopt_array($c,[CURLOPT_RETURNTRANSFER=>1,CURLOPT_TIMEOUT=>20,CURLOPT_CUSTOMREQUEST=>$method,CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$k,'Content-Type: application/json']]);if($body!==null)curl_setopt($c,CURLOPT_POSTFIELDS,json_encode($body));$r=curl_exec($c);return $r?json_decode($r,true):null;}
function flw_confirm($id){global $db;$r=flw('GET','/transactions/'.rawurlencode($id).'/verify');$d=$r['data']??null;
 if(!$d||($d['status']??'')!=='successful'||($d['currency']??'')!=='UGX')return false;
 $s=$db->prepare('SELECT d.*,u.name AS uname FROM deposits d LEFT JOIN users u ON u.id=d.user_id WHERE d.txid=?');$s->execute([$d['tx_ref']??'']);$p=$s->fetch(PDO::FETCH_ASSOC);
 if(!$p||(float)$d['amount']<(float)$p['amount'])return false;
 if($p['status']!='confirmed'){$db->prepare("UPDATE deposits SET status='confirmed',seen=0,note=? WHERE id=?")->execute(['Flutterwave #'.$d['id'],$p['id']]);notify('Payment received: '.ugx($p['amount']).' from '.$p['uname'].' for '.$p['month'].'.');}
 return true;}
function fcsv($o,$r){fputcsv($o,$r,',','"','');}
function mv($p){if($p==='uploads/cover.mp4')return 'video.php?f=cover.mp4';return preg_match('#^uploads/files/([a-f0-9]{16}\.(?:mp4|mov|m4v|webm))$#',$p,$m)?'video.php?f='.$m[1]:$p;}
function viewer(){return <<<'VW'
<div id="vw" hidden role="dialog" aria-label="Photo and video viewer"><button type="button" class="vx" aria-label="Close">&times;</button><button type="button" class="vp" aria-label="Previous">&#8249;</button><button type="button" class="vn" aria-label="Next">&#8250;</button><div class="vs"></div><p class="vc"></p></div>
<script>(function(){const vw=document.getElementById('vw');if(!vw)return;let L=[],i=0;
const items=()=>[...document.querySelectorAll('.masonry figure, #videos figure')].filter(f=>f.querySelector('img,video'));
function show(){const f=L[i],s=vw.querySelector('.vs'),v=f.dataset.src,im=f.querySelector('img'),cap=f.querySelector('figcaption');s.innerHTML='';let el;
if(v){el=document.createElement('video');el.src=v;el.controls=true;el.autoplay=true;el.setAttribute('playsinline','')}else{el=document.createElement('img');el.src=im.currentSrc||im.src;el.alt=im.alt||''}
s.appendChild(el);vw.querySelector('.vc').textContent=(cap?cap.textContent:'')+(L.length>1?'  ('+(i+1)+' of '+L.length+')':'')}
function open(k){L=items();i=k;vw.hidden=false;document.body.style.overflow='hidden';show()}
function close(){vw.hidden=true;vw.querySelector('.vs').innerHTML='';document.body.style.overflow=''}
function go(d){i=(i+d+L.length)%L.length;show()}
document.addEventListener('click',e=>{const f=e.target.closest('.masonry figure, #videos figure');if(f&&!e.target.closest('a[href]')){const k=items().indexOf(f);if(k>-1){e.preventDefault();open(k)}}});
vw.querySelector('.vx').onclick=close;vw.querySelector('.vp').onclick=()=>go(-1);vw.querySelector('.vn').onclick=()=>go(1);
vw.addEventListener('click',e=>{if(e.target===vw||e.target.classList.contains('vs'))close()});
addEventListener('keydown',e=>{if(vw.hidden)return;if(e.key=='Escape')close();if(e.key=='ArrowLeft')go(-1);if(e.key=='ArrowRight')go(1)});
let x0=null;vw.addEventListener('touchstart',e=>{x0=e.touches[0].clientX},{passive:true});vw.addEventListener('touchend',e=>{if(x0===null)return;const dx=e.changedTouches[0].clientX-x0;if(Math.abs(dx)>50)go(dx<0?1:-1);x0=null});
})()</script>
<script>(function(){const b=document.getElementById('th');if(!b)return;const r=document.documentElement;function s(){const d=r.dataset.theme=='dark';b.innerHTML=d?'&#9728; Light':'&#9790; Dark';b.setAttribute('aria-pressed',d)}s();b.onclick=()=>{const n=r.dataset.theme=='dark'?'light':'dark';r.dataset.theme=n;try{localStorage.setItem('rxtheme',n)}catch(e){}s()}})()</script>
VW;
}
function photo_ok($v,$m){if(empty($m['photo']))return false;if($v&&($v['role']=='admin'||$v['id']==$m['id']))return true;return(isset($m['show_photo'])&&$m['show_photo']!=='')?(int)$m['show_photo']==1:(($m['age_group']??'')=='Senior');}
function me(){global $db;if(empty($_SESSION['uid']))return null;$s=$db->prepare('SELECT * FROM users WHERE id=?');$s->execute([$_SESSION['uid']]);return $s->fetch(PDO::FETCH_ASSOC);}
function tok(){return $_SESSION['t']??=bin2hex(random_bytes(16));}
function chk(){if(!hash_equals(tok(),$_POST['t']??''))die('Your session expired. Go back, log in again and send the form again. What you typed is kept in this browser and will be filled in again.');}
function head($t='Rally XRC',$d=''){$u=me();$d=$d?:"Rally XRC is the official RC rally game of Uganda. Register your car, join the junior and senior leagues, and meet the drivers, teams and fans. News, events, photos and videos.";$pr=$_SERVER['HTTP_X_FORWARDED_PROTO']??(!empty($_SERVER['HTTPS'])?'https':'http');$base=$pr.'://'.($_SERVER['HTTP_HOST']??'localhost');?><!doctype html><html lang="en"><head><meta charset="utf-8"><script>try{document.documentElement.dataset.theme=localStorage.getItem("rxtheme")||"light"}catch(e){}</script><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=h($t)?></title>
<meta name="description" content="<?=h($d)?>"><meta name="theme-color" content="#ee5a2a"><link rel="manifest" href="manifest.json"><link rel="apple-touch-icon" href="icon-192.png"><meta name="google-site-verification" content="oUeRIWGccLhJhMMfUAiTSec_xvkPPdhw9kuRDzcETGI"><link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Ctext y='.9em' font-size='90'%3E%F0%9F%8F%81%3C/text%3E%3C/svg%3E">
<meta property="og:type" content="website"><meta property="og:site_name" content="Rally XRC"><meta property="og:title" content="<?=h($t)?>"><meta property="og:description" content="<?=h($d)?>"><meta property="og:url" content="<?=h($base.$_SERVER['REQUEST_URI'])?>"><meta property="og:image" content="<?=h($base)?>/uploads/seed/s12.jpeg">
<meta name="twitter:card" content="summary_large_image"><meta name="twitter:title" content="<?=h($t)?>"><meta name="twitter:description" content="<?=h($d)?>"><meta name="twitter:image" content="<?=h($base)?>/uploads/seed/s12.jpeg">
<?php if(!in_array(basename($_SERVER['SCRIPT_NAME']),['index.php','gallery.php','game.php']))echo '<meta name="robots" content="noindex">';?>
<script type="application/ld+json">{"@context":"https://schema.org","@type":"WebSite","name":"Rally XRC","alternateName":["Rally XRC Uganda","RallyXRC","Rally XRCRC"],"url":<?=json_encode($base.'/')?>}</script>
<link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@500;700&family=Source+Sans+3:wght@400;600&display=swap" rel="stylesheet"><link rel="stylesheet" href="style.css"></head><body>
<?php $b=$GLOBALS['db']->query("SELECT v FROM settings WHERE k='banner'")->fetchColumn();if($b)echo '<div class="banner">'.h($b).'</div>';?><nav><a class="logo" href="index.php">RALLY <b>XRC</b></a><form class="sf" action="search.php" method="get" role="search"><input name="q" placeholder="Search" aria-label="Search the site" maxlength="60"><button>Go</button></form><div><a href="index.php#news">News</a><a href="index.php#leagues">Leagues</a><a href="index.php#drivers">Drivers</a><a href="index.php#gallery">Gallery</a><a href="index.php#events">Events</a><a href="game.php">The game</a><a href="index.php#videos">Videos</a><a href="admin.php">Admin</a>
<?php if($u){echo ($u['role']=='admin'?'<a href="payments.php">Payments'.(($n=(int)$GLOBALS['db']->query("SELECT COUNT(*) FROM deposits WHERE seen=0")->fetchColumn())?' <b class="bd">'.$n.'</b>':'').'</a>':'<a href="profile.php">My profile</a><a href="pay.php">Pay fees</a>').'<a href="auth.php?m=out">Log out ('.h(explode(' ',$u['name'])[0]).')</a>';}else echo '<a href="auth.php?m=login">Log in</a><a class="btn" href="auth.php?m=register">Join</a>';?><button type="button" class="th" id="th" aria-label="Switch between light and dark mode">&#9790; Dark</button></div></nav><?php }
function sponsors_row(){global $db;if(in_array(basename($_SERVER['SCRIPT_NAME']),['admin.php','payments.php','notify.php']))return '';
 $r=$db->query('SELECT * FROM sponsors ORDER BY sort,id')->fetchAll(PDO::FETCH_ASSOC);if(!$r)return '';
 $o='<div class="fsp" id="sponsors"><small>Our sponsors</small><div>';
 foreach($r as $s){$im='<img src="'.h($s['logo']).'" alt="'.h($s['name']).'" title="'.h($s['name']).'" loading="lazy">';
  $o.=$s['url']?'<a href="'.h($s['url']).'" target="_blank" rel="noopener sponsored">'.$im.'</a>':'<span>'.$im.'</span>';}
 return $o.'</div></div>';}
function foot(){echo '<footer><p>Rally XRC, the official RC rally game of Uganda. Drivers, teams and fans, one grid.</p>'.sponsors_row().'<p class="sm"><a href="index.php">Home</a><a href="index.php#news">News</a><a href="index.php#events">Events</a><a href="index.php#leagues">Leagues</a><a href="index.php#drivers">The grid</a><a href="index.php#gallery">Gallery</a><a href="game.php">The game</a><a href="index.php#videos">Videos</a><a href="gallery.php">All photos and videos</a><a href="auth.php?m=register">Join</a><a href="auth.php?m=login">Log in</a><a href="sitemap.xml">Sitemap</a></p></footer>'.viewer().'</body></html>';}
