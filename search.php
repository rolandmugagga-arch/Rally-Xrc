<?php require 'inc.php';
$q=mb_substr(trim($_GET['q']??''),0,60);
head('Search | Rally XRC');?>
<main class="wrap"><h2>Search</h2>
<form action="search.php" method="get" role="search" class="bigsf"><input name="q" value="<?=h($q)?>" placeholder="Search news, events, drivers, photos and videos" maxlength="60" autofocus><button class="btn">Search</button></form>
<?php
if(mb_strlen($q)<2){echo '<p>Type at least 2 letters to search.</p>';}else{
$l='%'.addcslashes($q,'%_\\').'%';
$run=function($sql,$n)use($db,$l){$s=$db->prepare($sql);$s->execute(array_fill(0,$n,$l));return $s->fetchAll(PDO::FETCH_ASSOC);};
$news=$run("SELECT title,body,created FROM posts WHERE title LIKE ? ESCAPE '\\' OR body LIKE ? ESCAPE '\\' ORDER BY id DESC LIMIT 20",2);
$ev=$run("SELECT title,day,place FROM events WHERE title LIKE ? ESCAPE '\\' OR place LIKE ? ESCAPE '\\' ORDER BY day LIMIT 20",2);
$mem=$run("SELECT id,name,kind,age_group,team,car_type FROM users WHERE role!='admin' AND (name LIKE ? ESCAPE '\\' OR kind LIKE ? ESCAPE '\\' OR team LIKE ? ESCAPE '\\' OR car_type LIKE ? ESCAPE '\\' OR inspired LIKE ? ESCAPE '\\') ORDER BY id DESC LIMIT 30",5);
$art=$run("SELECT title,body FROM articles WHERE title LIKE ? ESCAPE '\\' OR body LIKE ? ESCAPE '\\' ORDER BY sort,id LIMIT 10",2);
$med=$run("SELECT kind,path,caption FROM media WHERE caption LIKE ? ESCAPE '\\' ORDER BY id DESC LIMIT 40",1);
$pages=[['Junior league','index.php#leagues','junior youth under 18 kids school'],['Senior league','index.php#leagues','senior teams sponsors'],['Join and register','auth.php?m=register','join register sign up member account'],['Log in','auth.php?m=login','login log in account'],['News','index.php#news','news announcements updates'],['Events','index.php#events','events races calendar dates'],['The grid, all drivers','index.php#drivers','drivers members grid teams'],['The game','game.php','game rally rules stage cars safety about how it works'],['Gallery','index.php#gallery','photos pictures gallery images'],['Videos','index.php#videos','videos clips watch']];
$pg=array_filter($pages,fn($p)=>stripos($p[0].' '.$p[2],$q)!==false);
$total=count($art)+count($news)+count($ev)+count($mem)+count($med)+count($pg);
echo '<p>'.$total.' result'.($total==1?'':'s').' for <b>'.h($q).'</b></p>';
if(!$total)echo '<p>Nothing found. Try a shorter word, a driver name or a car name.</p>';
if($pg){echo '<h3>Pages</h3><div class="grid">';foreach($pg as $p)echo '<div class="mem"><h3><a href="'.h($p[1]).'">'.h($p[0]).'</a></h3></div>';echo '</div>';}
if($art){echo '<h3>The game</h3>';foreach($art as $a)echo '<details open><summary><a href="game.php">'.h($a['title']).'</a></summary><p>'.h(mb_strimwidth($a['body'],0,220,'...')).'</p></details>';}
if($news){echo '<h3>News</h3>';foreach($news as $n)echo '<details open><summary><a href="index.php#news">'.h($n['title']).'</a> <small>'.h(substr($n['created'],0,10)).'</small></summary><p>'.h(mb_strimwidth($n['body'],0,220,'...')).'</p></details>';}
if($ev){echo '<h3>Events</h3><div class="grid">';foreach($ev as $e)echo '<div class="mem"><h3>'.h($e['title']).'</h3><p>'.h($e['day']).($e['place']?', '.h($e['place']):'').'</p></div>';echo '</div>';}
if($mem){echo '<h3>Drivers and members</h3><div class="grid">';foreach($mem as $m)echo '<div class="mem"><h3><a href="driver.php?id='.$m['id'].'">'.h($m['name']).'</a></h3><p>'.h($m['kind']).', '.h($m['age_group']).($m['team']?'. Team: '.h($m['team']):'').($m['car_type']?'. Car: '.h($m['car_type']):'').'</p></div>';echo '</div>';}
if($med){echo '<h3>Photos and videos</h3><div class="masonry">';foreach($med as $m)echo '<figure'.($m['kind']=='video'?' class="vid" data-src="'.h(mv($m['path'])).'"':'').'>'.($m['kind']=='video'?'<video muted playsinline preload="metadata" src="'.h(mv($m['path'])).'#t=0.1"></video>':'<img loading="lazy" src="'.h($m['path']).'" alt="'.h($m['caption']).'">').'<figcaption>'.h($m['caption']).'</figcaption></figure>';echo '</div>';}
}?>
</main><?php foot();
