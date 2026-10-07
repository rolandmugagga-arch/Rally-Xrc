<?php require 'inc.php';require_once 'push.php';$u=me();if(!$u||$u['role']!='admin'){header('Location: auth.php?m=login');exit;}
$msg='';
if($_SERVER['REQUEST_METHOD']=='POST'){chk();$t=trim($_POST['title']??'');$b=trim($_POST['body']??'');$l=trim($_POST['link']??'');$l=preg_match('#^(/|https://)#',$l)?$l:'/';
 $w=['juniors'=>"u.age_group='Junior'",'seniors'=>"u.age_group='Senior'"][$_POST['who']??'']??'1';
 if(!$t||!$b)$msg='Please write a title and a message.';else{[$s,$g]=push_all($t,$b,$l,$w);$msg="Sent to $s device".($s==1?'':'s').($g?", and removed $g that no longer exist":'').'.';}}
head('Send a notification | Rally XRC');
$n=(int)$db->query('SELECT COUNT(*) FROM push_subs')->fetchColumn();?>
<main class="wrap narrow"><h2>Send a notification</h2>
<?php if($msg)echo '<p class="err">'.h($msg).'</p>';?>
<p><?=$n?> device<?=$n==1?'':'s'?> allowed notifications. Only members who pressed Allow get them.</p>
<form method="post"><input type="hidden" name="t" value="<?=tok()?>">
<label>Title<input name="title" maxlength="60" required></label>
<label>Message<textarea name="body" rows="4" maxlength="200" required></textarea></label>
<label>Page to open (optional, for example /#events)<input name="link" placeholder="/"></label>
<label>Send to<select name="who"><option value="all">All members</option><option value="juniors">Juniors</option><option value="seniors">Seniors</option></select></label>
<button class="btn">Send now</button></form><p><a href="admin.php">Back to the admin panel</a></p></main><?php foot();
