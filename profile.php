<?php require 'inc.php';require_once 'push.php';$u=me();if(!$u){header('Location: auth.php?m=login');exit;}if($u['role']=='admin'){header('Location: admin.php');exit;}
$K=['Driver','Co-driver','Team owner','Mechanic','Marshal','Fan'];$msg='';$err='';
if($_SERVER['REQUEST_METHOD']=='POST'){chk();$a=$_POST['a']??'';
 if($a=='profile'){$ph=normphone($_POST['phone']??'',$_POST['cc']??'256');$n=trim($_POST['name']??'');
  if(!$n)$err='Please enter your name.';elseif(!$ph)$err='Enter a valid mobile number. Pick your country, then type the number without the country code.';
  else{$db->prepare('UPDATE users SET name=?,kind=?,age_group=?,team=?,car_type=?,inspired=?,show_photo=?,phone=?,network=? WHERE id=?')->execute([$n,in_array($_POST['kind']??'',$K)?$_POST['kind']:'Fan',($_POST['age']??'')=='Junior'?'Junior':'Senior',trim($_POST['team']??''),trim($_POST['car_type']??''),trim($_POST['inspired']??''),empty($_POST['show_photo'])?0:1,$ph,(in_array($_POST['network']??'',['Airtel','Other'])?$_POST['network']:'MTN'),$u['id']]);$msg='Profile saved.';}}
 if($a=='carphoto'){$f=$_FILES['photo']??null;$im=($f&&$f['error']===0&&$f['size']<=8*1048576)?@getimagesize($f['tmp_name']):false;$ext=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$im['mime']??'']??'';
  if(!$ext)$err='Please choose a JPG, PNG or WebP picture under 8 MB.';else{$p='uploads/files/c'.$u['id'].'-'.bin2hex(random_bytes(5)).'.'.$ext;if(is_dir(__DIR__.'/uploads/files')&&move_uploaded_file($f['tmp_name'],__DIR__.'/'.$p)){if($u['car_photo']&&preg_match('#^uploads/files/c\d+-#',$u['car_photo']))@unlink(__DIR__.'/'.$u['car_photo']);$db->prepare('UPDATE users SET car_photo=? WHERE id=?')->execute([$p,$u['id']]);$msg='Car photo saved.';}else $err='The server could not save the photo.';}}
 if($a=='rmphoto'){$col=($_POST['w']??'')=='car'?'car_photo':'photo';$p=$u[$col];if($p&&preg_match('#^uploads/files/[uc]\d+-#',$p))@unlink(__DIR__.'/'.$p);$db->prepare("UPDATE users SET $col=NULL WHERE id=?")->execute([$u['id']]);$msg='Photo removed.';}
 if($a=='password'){if(!password_verify($_POST['old']??'',$u['pass']))$err='Your current password is not right.';elseif(strlen($_POST['new']??'')<8)$err='The new password needs 8 or more characters.';else{$db->prepare('UPDATE users SET pass=? WHERE id=?')->execute([password_hash($_POST['new'],PASSWORD_DEFAULT),$u['id']]);$msg='Password changed.';}}
 if($a=='photo'){$f=$_FILES['photo']??null;$im=($f&&$f['error']===0&&$f['size']<=8*1048576)?@getimagesize($f['tmp_name']):false;$ext=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$im['mime']??'']??'';
  if(!$ext)$err='Please choose a JPG, PNG or WebP picture under 8 MB.';
  else{$p='uploads/files/u'.$u['id'].'-'.bin2hex(random_bytes(5)).'.'.$ext;
   if(is_dir(__DIR__.'/uploads/files')&&move_uploaded_file($f['tmp_name'],__DIR__.'/'.$p)){if($u['photo']&&preg_match('#^uploads/files/u\d+-#',$u['photo']))@unlink(__DIR__.'/'.$u['photo']);$db->prepare('UPDATE users SET photo=? WHERE id=?')->execute([$p,$u['id']]);$msg='Photo saved.';}else $err='The server could not save the photo.';}}
 $u=me();}
[, $pub]=vapid_keys();head('My profile | Rally XRC');?>
<main class="wrap narrow"><h2>My profile</h2>
<?php if($msg)echo '<p class="err">'.h($msg).'</p>';if($err)echo '<p class="err">'.h($err).'</p>';?>
<p><a href="driver.php?id=<?=$u['id']?>">See how other members see my profile</a></p><h3>Passport photo</h3><?php if($u['photo']){echo '<img class="pp" src="'.h($u['photo']).'" alt="Your photo">';?><form method="post" class="inl"><input type="hidden" name="t" value="<?=tok()?>"><input type="hidden" name="a" value="rmphoto"><input type="hidden" name="w" value="photo"><button class="x">Remove photo</button></form><?php }?>
<form id="pf" method="post" enctype="multipart/form-data"><input type="hidden" name="t" value="<?=tok()?>"><input type="hidden" name="a" value="photo">
<label>Choose a clear photo of your face<input type="file" name="photo" accept="image/*" required></label>
<p><small>Your photo shows on your public profile and on the grid when the box under My details is ticked. Juniors need a parent or guardian to agree.</small></p><button class="btn">Upload photo</button><p id="ps"></p></form>
<h3>Car photo</h3><?php if($u['car_photo']){echo '<img class="carp" src="'.h($u['car_photo']).'" alt="Your car">';?><form method="post" class="inl"><input type="hidden" name="t" value="<?=tok()?>"><input type="hidden" name="a" value="rmphoto"><input type="hidden" name="w" value="car"><button class="x">Remove photo</button></form><?php }?>
<form id="cf" method="post" enctype="multipart/form-data"><input type="hidden" name="t" value="<?=tok()?>"><input type="hidden" name="a" value="carphoto"><label>A clear photo of your car<input type="file" name="photo" accept="image/*" required></label><p><small>It shows next to your photo on your profile and on the grid.</small></p><button class="btn">Upload car photo</button><p id="cs"></p></form>
<h3>My details</h3><form method="post"><input type="hidden" name="t" value="<?=tok()?>"><input type="hidden" name="a" value="profile">
<label>Full name<input name="name" value="<?=h($u['name'])?>" required></label>
<label>I am a<select name="kind"><?php foreach($K as $k)echo '<option'.($k==$u['kind']?' selected':'').'>'.$k.'</option>';?></select></label>
<label>League<select name="age"><option<?=$u['age_group']=='Junior'?' selected':''?>>Junior</option><option<?=$u['age_group']=='Senior'?' selected':''?>>Senior</option></select></label>
<label>Team<input name="team" value="<?=h($u['team'])?>"></label><label>Car type<input name="car_type" value="<?=h($u['car_type'])?>" placeholder="For example, Subaru Impreza WRC"></label><label>Inspired by<input name="inspired" value="<?=h($u['inspired'])?>" placeholder="A driver or hero who inspires you"></label><label><input type="checkbox" name="show_photo" value="1" style="display:inline;width:auto"<?=(($u['show_photo']===null||$u['show_photo']==='')?$u['age_group']=='Senior':(int)$u['show_photo']==1)?' checked':''?>> Show my photo on the website, so anyone can see it. Juniors: tick this only if a parent or guardian agrees.</label>
<label>Mobile money network (Uganda numbers)<select name="network"><option<?=$u['network']=='MTN'?' selected':''?>>MTN</option><option<?=$u['network']=='Airtel'?' selected':''?>>Airtel</option><option<?=$u['network']=='Other'?' selected':''?>>Other</option></select></label>
<?php [$pcc,$pnat]=split_phone($u['phone']);echo phone_field($pcc,$pnat);?><button class="btn">Save my details</button></form>
<h3>Change password</h3><form method="post"><input type="hidden" name="t" value="<?=tok()?>"><input type="hidden" name="a" value="password">
<label>Current password<input type="password" name="old" required></label><label>New password (8 or more characters)<input type="password" name="new" minlength="8" required></label><button class="btn">Change password</button></form>
<h3>Notifications</h3><p>Get news and event alerts on this phone.</p><?php if($pub):?><button class="btn" id="nb" type="button">Turn on notifications</button> <button class="x" id="nx" type="button">Turn off</button><?php else:?><p>Notifications are not available on this server yet.</p><?php endif;?><p id="ns"></p>
<script>
const PUB='<?=h($pub)?>',TK='<?=tok()?>',ns=document.getElementById('ns'),ps=document.getElementById('ps'),pf=document.getElementById('pf');
pf.onsubmit=async e=>{e.preventDefault();const file=pf.photo.files[0];if(!file)return;ps.textContent='Preparing your photo...';
const img=await new Promise(ok=>{const i=new Image();i.onload=()=>ok(i);i.onerror=()=>ok(null);i.src=URL.createObjectURL(file)});
if(!img){pf.submit();return}
const W=640,H=800,c=document.createElement('canvas');c.width=W;c.height=H;const k=Math.max(W/img.width,H/img.height),w=img.width*k,h=img.height*k;c.getContext('2d').drawImage(img,(W-w)/2,(H-h)/2,w,h);
c.toBlob(async b=>{const d=new FormData(pf);d.set('photo',b,'photo.jpg');ps.textContent='Uploading...';try{await fetch('profile.php',{method:'POST',body:d})}catch(x){ps.textContent='Upload failed. Check your connection.';return}location.reload()},'image/jpeg',.85)};
const cf=document.getElementById('cf'),cs=document.getElementById('cs');
if(cf)cf.onsubmit=async e=>{e.preventDefault();const file=cf.photo.files[0];if(!file)return;cs.textContent='Preparing...';
const img=await new Promise(ok=>{const i=new Image();i.onload=()=>ok(i);i.onerror=()=>ok(null);i.src=URL.createObjectURL(file)});if(!img){cf.submit();return}
const k=Math.min(1,1000/Math.max(img.width,img.height)),c=document.createElement('canvas');c.width=Math.round(img.width*k);c.height=Math.round(img.height*k);c.getContext('2d').drawImage(img,0,0,c.width,c.height);
c.toBlob(async b=>{const d=new FormData(cf);d.set('photo',b,'car.jpg');cs.textContent='Uploading...';try{await fetch('profile.php',{method:'POST',body:d})}catch(x){cs.textContent='Upload failed. Check your connection.';return}location.reload()},'image/jpeg',.85)};
const u8=s=>{const p='='.repeat((4-s.length%4)%4),r=atob((s+p).replace(/-/g,'+').replace(/_/g,'/'));return Uint8Array.from([...r].map(ch=>ch.charCodeAt(0)))};
const post=o=>fetch('push_sub.php',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF':TK},body:JSON.stringify(o)});
if(document.getElementById('nb'))document.getElementById('nb').onclick=async()=>{try{
if(!('serviceWorker' in navigator)||!('PushManager' in window)){ns.textContent='This browser cannot receive notifications. On an iPhone, open the site in Safari, tap Share, choose Add to Home Screen, then open it from the Home Screen and try again.';return}
const reg=await navigator.serviceWorker.register('sw.js');await navigator.serviceWorker.ready;
if(await Notification.requestPermission()!=='granted'){ns.textContent='Notifications were not allowed. You can allow them in your browser settings.';return}
const sub=await reg.pushManager.subscribe({userVisibleOnly:true,applicationServerKey:u8(PUB)});const r=await post(sub.toJSON());ns.textContent=r.ok?'Notifications are on for this device.':'Could not save your choice. Try again.'}catch(e){ns.textContent='Could not turn on notifications: '+e.message}};
if(document.getElementById('nx'))document.getElementById('nx').onclick=async()=>{const reg=await navigator.serviceWorker.getRegistration(),s=reg&&await reg.pushManager.getSubscription();if(s){await post({unsubscribe:1,endpoint:s.endpoint});await s.unsubscribe()}ns.textContent='Notifications are off for this device.'};
</script></main><?php foot();
