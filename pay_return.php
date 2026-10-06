<?php require 'inc.php';
$tx=$_GET['tx_ref']??'';$id=$_GET['transaction_id']??'';$s=$_GET['status']??'';
if($s=='successful'&&$id&&flw_confirm($id))$_SESSION['flash']='Payment received. Thank you!';
elseif($s=='cancelled'){$db->prepare("UPDATE deposits SET status='cancelled' WHERE txid=? AND status='initiated'")->execute([$tx]);$_SESSION['flash']='Payment cancelled. You were not charged.';}
else $_SESSION['flash']='We could not confirm the payment yet. If money left your phone, it will show as Paid here once it is confirmed.';
header('Location: pay.php');
