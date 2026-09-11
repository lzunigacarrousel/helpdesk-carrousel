<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$file=$root.'/app/Controllers/ExternalController.php';
$src=@file_get_contents($file);
if($src===false){fwrite(STDERR,"[ERROR] No se pudo leer ExternalController.php\n");exit(1);}
$fail=0;
function ok(bool $condition,string $label):void{global $fail;if($condition){echo "[OK] {$label}\n";}else{echo "[FALLO] {$label}\n";$fail++;}}
ok(str_contains($src,"CAST(al.entity_id AS UNSIGNED)=u.id"),'Origen de proveedor compara entity_id numericamente');
ok(!str_contains($src,"al.entity_id=CAST(u.id AS CHAR)"),'Origen de proveedor no compara collations de texto incompatibles');
ok(str_contains($src,"USER_CONVERTED_TO_EXTERNAL"),'Se conserva deteccion de origen convertido desde usuario interno');
if($fail>0){echo "\n[ERROR] {$fail} validacion(es) fallaron.\n";exit(1);}echo "\n[OK] Regresion de collation de proveedores externos.\n";
