<?php require 'inc.php';$u=me();$id=(int)($_GET['id']??0);
$s=$db->prepare("SELECT id,name,kind,age_group,team,car_type,inspired,photo,car_photo,show_photo,created FROM users WHERE id=? AND role!='admin'");$s->execute([$id]);$m=$s->fetch(PDO::FETCH_ASSOC);
head(($m?$m['name']:'Driver not found').' | Rally XRC');
if(!$m){echo '<main class="wrap narrow"><h2>Driver not found</h2><p><a href="index.php#drivers">Back to the grid</a></p></main>';foot();exit;}
$row=fn($l,$v)=>($v!==''&&$v!==null)?'<p><b>'.$l.':</b> '.h($v).'</p>':'';?>
<main class="wrap narrow"><p><a href="index.php#drivers">&larr; Back to the grid</a></p><h2><?=h($m['name'])?></h2>
<div class="dp"><div><?php if(photo_ok($u,$m))echo '<img class="pp" src="'.h($m['photo']).'" alt="Photo of '.h($m['name']).'">';else echo '<div class="ph" aria-hidden="true">'.h(mb_strtoupper(mb_substr($m['name'],0,1))).'</div>';
if($m['car_photo'])echo '<img class="carp" src="'.h($m['car_photo']).'" alt="Car of '.h($m['name']).'">';?></div>
<div><?=$row('Role',$m['kind']).$row('League',$m['age_group']).$row('Team',$m['team']).$row('Car type',$m['car_type']).$row('Inspired by',$m['inspired']).$row('Member since',substr((string)$m['created'],0,10))?>
<?php if($u&&$u['id']==$m['id'])echo '<p><a class="btn" href="profile.php">Edit my profile</a></p>';?></div></div></main><?php foot();
