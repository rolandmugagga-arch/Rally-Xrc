<?php
// Upload this file, open it in your browser, then DELETE it.
$ok=fn($b)=>$b?'YES':'NO  <-- needs fixing';
echo '<pre>Rally XRC hosting check'."\n\n";
echo 'PHP version 7.4 or newer .... '.$ok(version_compare(PHP_VERSION,'7.4','>=')).' ('.PHP_VERSION.")\n";
echo 'SQLite database (pdo_sqlite) . '.$ok(extension_loaded('pdo_sqlite'))."\n";
echo 'mbstring ..................... '.$ok(extension_loaded('mbstring'))."\n";
echo 'curl (payments, alerts) ...... '.$ok(extension_loaded('curl'))."\n";
echo 'openssl (notifications) ...... '.$ok(extension_loaded('openssl'))."\n";
echo 'data/ folder writable ........ '.$ok(is_dir(__DIR__.'/data')?is_writable(__DIR__.'/data'):is_writable(__DIR__))."\n";
echo 'uploads/ folder writable ..... '.$ok(is_dir(__DIR__.'/uploads')&&is_writable(__DIR__.'/uploads'))."\n";
echo 'Biggest upload allowed ....... '.ini_get('upload_max_filesize').' (videos need this to be large)'."\n";
echo 'Biggest form allowed ......... '.ini_get('post_max_size')."\n";
echo '</pre>';
