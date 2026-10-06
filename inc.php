<?php
ini_set('display_errors','0');error_reporting(E_ALL);
session_set_cookie_params(['lifetime'=>2592000,'httponly'=>true,'samesite'=>'Lax']);
session_start();
$dbp=getenv('DB_PATH')?:__DIR__.'/data/rallyx.sqlite';@mkdir(dirname($dbp),0775,true);@mkdir(__DIR__.'/uploads/files',0775,true);
$db=new PDO('sqlite:'.$dbp);
$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$db->exec("CREATE TABLE IF NOT EXISTS users(id INTEGER PRIMARY KEY,name,email UNIQUE,pass,role DEFAULT 'member',kind,age_group,car,phone,created DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE IF NOT EXISTS posts(id INTEGER PRIMARY KEY,title,body,created DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE IF NOT EXISTS settings(k PRIMARY KEY,v);
CREATE TABLE IF NOT EXISTS events(id INTEGER PRIMARY KEY,title,day,place);
CREATE TABLE IF NOT EXISTS media(id INTEGER PRIMARY KEY,kind,path,caption,created DEFAULT CURRENT_TIMESTAMP);");
$cap=['Wheel up on the pit stand','Controllers out, cars on the slope','Josh Rally Team meets RallyXRC','Ford Fiesta in full livery','KCB Bank Subaru','The grid before the start','Two Subarus, rear view','Mechanic plugging in the battery','Citroen C3 WRC beside a Land Cruiser','Racing on the dirt stage','Sliding through the corner','Dust on the open stage','Fans gather at the chalk line','Stage run','Driver and car','Side-on action','Volkswagen Polo WRC','Black GR car throwing dirt','Rear view of a slide'];
if(!$db->query("SELECT 1 FROM users WHERE role='admin'")->fetch()){
 $db->prepare("INSERT INTO users(name,email,pass,role,kind) VALUES('Admin',?,?,'admin','Admin')")->execute([getenv('ADMIN_EMAIL')?:'rolandmugagga@gmail.com',password_hash(getenv('ADMIN_PASSWORD')?:'Roland12',PASSWORD_DEFAULT)]);
 foreach(glob(__DIR__.'/uploads/seed/*') as $i=>$f)$db->prepare("INSERT INTO media(kind,path,caption) VALUES('image',?,?)")->execute(['uploads/seed/'.basename($f),$cap[$i]??'']);
 $db->prepare("INSERT INTO posts(title,body) VALUES(?,?)")->execute(['Welcome to Rally XRC','Rally XRC is the home of RC rally racing in Uganda. Drivers, co-drivers, teams, mechanics and fans can now register in one place. Youth and adult leagues are both open.']);
}
function h($s){return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');}
function setting($k,$d=''){global $db;$s=$db->prepare('SELECT v FROM settings WHERE k=?');$s->execute([$k]);$v=$s->fetchColumn();return($v!==false&&$v!=='')?$v:$d;}
function me(){global $db;if(empty($_SESSION['uid']))return null;$s=$db->prepare('SELECT * FROM users WHERE id=?');$s->execute([$_SESSION['uid']]);return $s->fetch(PDO::FETCH_ASSOC);}
function tok(){return $_SESSION['t']??=bin2hex(random_bytes(16));}
function chk(){if(!hash_equals(tok(),$_POST['t']??''))die('Session expired. Go back and try again.');}
function head($t='Rally XRC',$d=''){$u=me();$d=$d?:"Rally XRC is the official RC rally game of Uganda. Register your car, join the youth and adult leagues, and meet the drivers, teams and fans. News, events, photos and videos.";$pr=$_SERVER['HTTP_X_FORWARDED_PROTO']??(!empty($_SERVER['HTTPS'])?'https':'http');$base=$pr.'://'.($_SERVER['HTTP_HOST']??'localhost');?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=h($t)?></title>
<meta name="description" content="<?=h($d)?>"><meta name="theme-color" content="#ee5a2a"><meta name="google-site-verification" content="oUeRIWGccLhJhMMfUAiTSec_xvkPPdhw9kuRDzcETGI"><link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Ctext y='.9em' font-size='90'%3E%F0%9F%8F%81%3C/text%3E%3C/svg%3E">
<meta property="og:type" content="website"><meta property="og:site_name" content="Rally XRC"><meta property="og:title" content="<?=h($t)?>"><meta property="og:description" content="<?=h($d)?>"><meta property="og:url" content="<?=h($base.$_SERVER['REQUEST_URI'])?>"><meta property="og:image" content="<?=h($base)?>/uploads/seed/s12.jpeg">
<meta name="twitter:card" content="summary_large_image"><meta name="twitter:title" content="<?=h($t)?>"><meta name="twitter:description" content="<?=h($d)?>"><meta name="twitter:image" content="<?=h($base)?>/uploads/seed/s12.jpeg">
<?php if(basename($_SERVER['SCRIPT_NAME'])!='index.php')echo '<meta name="robots" content="noindex">';?>
<script type="application/ld+json">{"@context":"https://schema.org","@type":"WebSite","name":"Rally XRC","alternateName":["Rally XRC Uganda","RallyXRC","Rally XRCRC"],"url":<?=json_encode($base.'/')?>}</script>
<link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@500;700&family=Source+Sans+3:wght@400;600&display=swap" rel="stylesheet"><link rel="stylesheet" href="style.css"></head><body>
<?php $b=$GLOBALS['db']->query("SELECT v FROM settings WHERE k='banner'")->fetchColumn();if($b)echo '<div class="banner">'.h($b).'</div>';?><nav><a class="logo" href="index.php">RALLY <b>XRC</b></a><form class="sf" action="search.php" method="get" role="search"><input name="q" placeholder="Search" aria-label="Search the site" maxlength="60"><button>Go</button></form><div><a href="index.php#news">News</a><a href="index.php#leagues">Leagues</a><a href="index.php#drivers">Drivers</a><a href="index.php#gallery">Gallery</a><a href="index.php#events">Events</a><a href="index.php#videos">Videos</a><a href="admin.php">Admin</a>
<?php if($u){echo '<a href="auth.php?m=out">Log out ('.h(explode(' ',$u['name'])[0]).')</a>';}else echo '<a href="auth.php?m=login">Log in</a><a class="btn" href="auth.php?m=register">Join</a>';?></div></nav><?php }
function foot(){echo '<footer><p>Rally XRC, the official RC rally game of Uganda. Drivers, teams and fans, one grid.</p><p class="sm"><a href="index.php">Home</a><a href="index.php#news">News</a><a href="index.php#events">Events</a><a href="index.php#leagues">Leagues</a><a href="index.php#drivers">The grid</a><a href="index.php#gallery">Gallery</a><a href="index.php#videos">Videos</a><a href="auth.php?m=register">Join</a><a href="auth.php?m=login">Log in</a><a href="sitemap.xml">Sitemap</a></p></footer></body></html>';}
