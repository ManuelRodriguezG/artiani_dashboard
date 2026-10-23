<?php
$_SERVER['SERVER_NAME']='panel.com.local';
require __DIR__.'/app/config/mysql.php';
$path='C:\\xampp\\panel_db_backups\\artianicom_sys_panel_20261022_antes_aplicar_hobby_pet_32.sql';
$cmd='"C:\\xampp\\mysql\\bin\\mysqldump.exe" --single-transaction --quick --skip-lock-tables --host='.escapeshellarg(MYSQLHOST).' --port='.escapeshellarg(MYSQLPORT).' --user='.escapeshellarg(MYSQLUSER).' --password='.escapeshellarg(MYSQLPASS).' '.escapeshellarg(MYSQLBASE).' erp_listas_precios erp_listas_precios_detalle erp_listas_precios_eventos';
$des=[1=>['file',$path,'w'],2=>['pipe','w']];$p=proc_open($cmd,$des,$pipes);if(!is_resource($p)){fwrite(STDERR,"NO_PROC\n");exit(1);} $err=stream_get_contents($pipes[2]);fclose($pipes[2]);$code=proc_close($p);if($code!==0){fwrite(STDERR,$err);exit($code);}echo $path."|".filesize($path)."\n";
