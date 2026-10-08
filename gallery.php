<?php require 'inc.php';head('All photos and videos | Rally XRC','Every photo and video from Rally XRC, in one place.');
$img=$db->query("SELECT * FROM media WHERE kind='image' ORDER BY sort,id")->fetchAll();$vid=$db->query("SELECT * FROM media WHERE kind='video' ORDER BY sort,id")->fetchAll();?>
<main><section id="photos" class="wrap"><h2>All photos</h2><p><a href="index.php#gallery">&larr; Back to the home page</a></p><?php if(!$img)echo '<p>No photos yet.</p>';?>
<div class="masonry"><?php foreach($img as $i):?><figure><img loading="lazy" src="<?=h($i['path'])?>" alt="<?=h($i['caption'])?>"><figcaption><?=h($i['caption'])?></figcaption></figure><?php endforeach;?></div></section>
<section id="videos" class="wrap"><h2>All videos</h2><?php if(!$vid)echo '<p>No videos yet.</p>';?>
<div class="vgrid"><?php foreach($vid as $v):?><figure class="vid" data-src="<?=h(mv($v['path']))?>"><video muted playsinline preload="metadata" src="<?=h(mv($v['path']))?>#t=0.1"></video><figcaption><?=h($v['caption'])?></figcaption></figure><?php endforeach;?></div></section></main><?php foot();
