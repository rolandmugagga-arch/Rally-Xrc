<?php require 'inc.php';$u=me();$via=($_POST['via']??$_GET['via']??'');$via=in_array($via,['card','mobile'])?$via:'';
if(!$u){header('Location: auth.php?m=login&next='.urlencode('pay.php'.($via?"?via=$via":'')));exit;}if($u['role']=='admin'){header('Location: payments.php');exit;}
$on=(bool)getenv('FLW_SECRET_KEY');$err='';
if($_SERVER['REQUEST_METHOD']=='POST'&&$on){chk();
 $amt=(int)preg_replace('/\D/','',$_POST['amount']??'');$mo=preg_match('/^\d{4}-\d{2}$/',$_POST['month']??'')?$_POST['month']:date('Y-m');
 if($amt<1000||$amt>10000000)$err='Enter an amount between UGX 1,000 and 10,000,000.';
 else{$ref='RXRC-'.$u['id'].'-'.time().'-'.bin2hex(random_bytes(3));
  $db->prepare("INSERT INTO deposits(user_id,amount,month,network,phone,txid,status,seen) VALUES(?,?,?,?,?,?,'initiated',1)")->execute([$u['id'],$amt,$mo,$u['network']?:'MTN',$u['phone'],$ref]);
  $pr=$_SERVER['HTTP_X_FORWARDED_PROTO']??(!empty($_SERVER['HTTPS'])?'https':'http');
  $r=flw('POST','/payments',['tx_ref'=>$ref,'amount'=>$amt,'currency'=>'UGX','redirect_url'=>$pr.'://'.$_SERVER['HTTP_HOST'].'/pay_return.php','payment_options'=>$via=='card'?'card':($via=='mobile'?'mobilemoneyuganda':'mobilemoneyuganda,card'),'customer'=>['email'=>$u['email'],'phonenumber'=>$u['phone'],'name'=>$u['name']],'customizations'=>['title'=>'Rally XRC monthly fee','description'=>'Fee for '.$mo]]);
  $link=$r['data']['link']??'';
  if($link){header('Location: '.$link);exit;}
  $db->prepare("UPDATE deposits SET status='failed' WHERE txid=?")->execute([$ref]);$err='Could not start the payment. Please try again in a moment.';}}
head('Pay fees | Rally XRC');
$fee=(int)setting('monthly_fee',0);
$st=$db->prepare("SELECT * FROM deposits WHERE user_id=? AND status NOT IN ('failed','cancelled') ORDER BY id DESC");$st->execute([$u['id']]);$my=$st->fetchAll(PDO::FETCH_ASSOC);
$tot=0;foreach($my as $d)if($d['status']=='confirmed')$tot+=$d['amount'];
$lab=['confirmed'=>'Paid','pending'=>'Waiting for the admin','rejected'=>'Rejected','initiated'=>'Not completed'];?>
<main class="wrap narrow"><h2>Pay monthly fees</h2>
<?php if(!empty($_SESSION['flash'])){echo '<p class="err">'.h($_SESSION['flash']).'</p>';unset($_SESSION['flash']);}if($err)echo '<p class="err">'.h($err).'</p>';?>
<?php if(!$on):?><p>Online payments are not switched on yet. Please check back soon.</p><?php else:?>
<form method="post"><input type="hidden" name="t" value="<?=tok()?>"><input type="hidden" name="via" value="<?=h($via)?>">
<label>Amount (UGX)<input name="amount" inputmode="numeric" value="<?=$fee?:''?>" required></label>
<label>Month this pays for<input type="month" name="month" value="<?=date('Y-m')?>" required></label>
<p>Your number on file: <b><?=h($u['phone'])?></b>. <?=$via=='card'?'On the next page you enter your card details.':($via=='mobile'?'On the next page you choose MTN or Airtel, then approve the payment on your phone.':'On the next page you choose MTN, Airtel or card, then approve the payment on your phone.')?></p>
<button class="btn">Pay now</button></form><?php endif;?>
<h3>My payments</h3><p>Total paid: <b><?=ugx($tot)?></b></p>
<?php foreach($my as $d)echo '<div class="mem"><p><b>'.ugx($d['amount']).'</b> for '.h($d['month']).'<br>Status: <b>'.h($lab[$d['status']]??$d['status']).'</b></p></div><br>';if(!$my)echo '<p>No payments yet.</p>';?>
</main><?php foot();
