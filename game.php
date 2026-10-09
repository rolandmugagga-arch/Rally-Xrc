<?php require 'inc.php';head('The game | Rally XRC','How RC rally works in Uganda: stages, cars, leagues, safety and how to join Rally XRC.');?>
<main class="wrap"><h2>The game</h2><p>Everything you need to know about RC rally in Uganda, from how a stage works to how to join.</p>
<?php $n=0;foreach($db->query('SELECT * FROM articles ORDER BY sort,id') as $a){$n++;?><article class="art"><?php if($a['image'])echo '<img class="ai" src="'.h($a['image']).'" alt="">';?><h3><?=h($a['title'])?></h3><small><?=h(substr((string)$a['created'],0,10))?></small><p><?=nl2br(h($a['body']))?></p></article><?php }if(!$n)echo '<p>Articles about the game will appear here soon.</p>';?>
<p class="more"><a class="btn" href="auth.php?m=register">Join Rally XRC</a></p></main><?php foot();
