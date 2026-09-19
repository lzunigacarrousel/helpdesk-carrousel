<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$_SERVER['SCRIPT_NAME']='/HelpdeskCarrousel/public/index.php';
$_SERVER['HTTP_HOST']='localhost';
require_once $root.'/bootstrap.php';

use App\Core\Database;

$errors=0;
function ok(bool $condition,string $message):void
{
    global $errors;
    echo ($condition?'[OK] ':'[FALLO] ').$message.PHP_EOL;
    if(!$condition)$errors++;
}

foreach(['pdo_mysql','fileinfo','mbstring','zip'] as $ext){
    ok(extension_loaded($ext),'Extensión PHP disponible: '.$ext);
}

ok(DB_NAME==='carrousel_helpdesk','Runtime apunta a carrousel_helpdesk');
ok(defined('STORAGE_PATH')&&is_dir(STORAGE_PATH),'Directorio storage existe');
ok(is_dir(STORAGE_PATH.'/logs'),'Directorio storage/logs existe');
ok(is_writable(STORAGE_PATH),'storage es escribible');
ok(is_writable(STORAGE_PATH.'/logs'),'storage/logs es escribible');

$uploads=STORAGE_PATH.'/ticket_uploads';
if(!is_dir($uploads)){
    @mkdir($uploads,0775,true);
}
ok(is_dir($uploads),'Directorio ticket_uploads disponible');
ok(is_writable($uploads),'ticket_uploads es escribible');

$probe=$uploads.'/.phase12_write_probe_'.bin2hex(random_bytes(4)).'.tmp';
$written=@file_put_contents($probe,'phase12');
ok($written===7,'Prueba de escritura en almacenamiento');
if(is_file($probe))@unlink($probe);
ok(!is_file($probe),'Prueba temporal de almacenamiento se limpia');

$start=microtime(true);
$pdo=Database::pdo();
$connectMs=(microtime(true)-$start)*1000;
ok($connectMs<5000,'Conexión DB menor a 5 s ('.round($connectMs,1).' ms)');

$start=microtime(true);
$value=$pdo->query('SELECT 1')->fetchColumn();
$pingMs=(microtime(true)-$start)*1000;
ok((int)$value===1,'SELECT 1 responde');
ok($pingMs<2000,'SELECT 1 menor a 2 s ('.round($pingMs,1).' ms)');

$requiredTables=['tickets','ticket_comments','ticket_attachments','ticket_events','ticket_resolutions','ticket_feedback','notification_events','notification_deliveries','knowledge_articles','knowledge_revisions','ticket_activities'];
$ph=implode(',',array_fill(0,count($requiredTables),'?'));
$q=$pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_TYPE='BASE TABLE' AND TABLE_NAME IN ($ph)");
$start=microtime(true);
$q->execute($requiredTables);
$count=(int)$q->fetchColumn();
$tableMs=(microtime(true)-$start)*1000;
ok($count===count($requiredTables),'Tablas operativas críticas disponibles');
ok($tableMs<3000,'Consulta de metadata menor a 3 s ('.round($tableMs,1).' ms)');

$start=microtime(true);
$q=$pdo->query("SELECT COUNT(*) FROM tickets WHERE deleted_at IS NULL");
$ticketCount=(int)$q->fetchColumn();
$countMs=(microtime(true)-$start)*1000;
ok($ticketCount>=0,'Conteo de tickets operativo');
ok($countMs<3000,'Conteo de tickets menor a 3 s ('.round($countMs,1).' ms)');

echo '[INFO] Tickets activos: '.$ticketCount.PHP_EOL;
echo '[INFO] DB connect: '.round($connectMs,1).' ms'.PHP_EOL;
echo '[INFO] DB ping: '.round($pingMs,1).' ms'.PHP_EOL;
echo '[INFO] Metadata: '.round($tableMs,1).' ms'.PHP_EOL;
echo '[INFO] Ticket count: '.round($countMs,1).' ms'.PHP_EOL;

if($errors){
    fwrite(STDERR,"[ERROR] {$errors} validación(es) fallaron.".PHP_EOL);
    exit(1);
}

echo '[OK] Health runtime Fase 12 validado sin modificar datos de negocio.'.PHP_EOL;
