<?php require 'inc.php';
$m=$_GET['m']??'login';$err='';$K=['Driver','Co-driver','Team owner','Mechanic','Marshal','Fan'];
if($m=='out'){session_destroy();header('Location: index.php');exit;}
if($_SERVER['REQUEST_METHOD']=='POST'){chk();
 if($m=='register'){
  $n=trim($_POST['name']??'');$e=strtolower(trim($_POST['email']??''));$p=$_POST['pass']??'';
  if(!$n||!filter_var($e,FILTER_VALIDATE_EMAIL)||strlen($p)<8)$err='Enter your name, a valid email and a password of 8 or more characters.';
  elseif(!($ph=normphone($_POST['phone']??'',$_POST['cc']??'256')))$err='Enter a valid mobile number. Pick your country, then type the number without the country code, like 771234567.';
  else try{$db->prepare('INSERT INTO users(name,email,pass,kind,age_group,team,phone,network) VALUES(?,?,?,?,?,?,?,?)')->execute([$n,$e,password_hash($p,PASSWORD_DEFAULT),in_array($_POST['kind'],$K)?$_POST['kind']:'Fan',$_POST['age']=='Junior'?'Junior':'Senior',trim($_POST['team']??''),$ph,(in_array($_POST['network']??'',['Airtel','Other'])?$_POST['network']:'MTN')]);
   session_regenerate_id(true);$_SESSION['uid']=$db->lastInsertId();header('Location: '.(preg_match('/^pay\.php(\?via=(mobile|card))?$/',$_GET['next']??'')?$_GET['next']:'index.php#drivers'));exit;}
  catch(Exception $x){$err='That email is already registered. Log in instead.';}
 }else{
  $s=$db->prepare('SELECT * FROM users WHERE email=?');$s->execute([strtolower(trim($_POST['email']??''))]);$u=$s->fetch();
  if($u&&password_verify($_POST['pass']??'',$u['pass'])){session_regenerate_id(true);$_SESSION['uid']=$u['id'];header('Location: '.($u['role']=='admin'?'admin.php':(preg_match('/^pay\.php(\?via=(mobile|card))?$/',$_GET['next']??'')?$_GET['next']:'index.php')));exit;}
  $err='Wrong email or password.';}}
head($m=='register'?'Join Rally XRC':'Log in');?>
<main class="wrap narrow"><h2><?=$m=='register'?'Join Rally XRC':'Log in'?></h2><?php if($err)echo '<p class="err">'.h($err).'</p>';?>
<form method="post"><input type="hidden" name="t" value="<?=tok()?>">
<?php if($m=='register'):?><label>Full name<input name="name" required></label>
<label>I am a<select name="kind"><?php foreach($K as $k)echo "<option>$k</option>";?></select></label>
<label>League<select name="age"><option <?=($_GET['age']??'')=='Junior'?'selected':''?>>Junior</option><option <?=($_GET['age']??'')=='Senior'?'selected':''?>>Senior</option></select></label>
<label>Team (optional)<input name="team"></label><label>Mobile money network (Uganda numbers)<select name="network"><option>MTN</option><option>Airtel</option><option>Other</option></select></label><?=phone_field($_POST['cc']??'256',$_POST['phone']??'')?><p><small>Your number is kept private.</small></p><?php endif;?>
<label>Email<input type="email" name="email" required></label><label>Password<input type="password" name="pass" minlength="<?=$m=='register'?8:1?>" required></label>
<button class="btn"><?=$m=='register'?'Create account':'Log in'?></button></form>
<p><?=$m=='register'?'Already a member? <a href="auth.php?m=login">Log in</a>':'New here? <a href="auth.php?m=register">Join Rally XRC</a>'?></p></main><?php foot();
