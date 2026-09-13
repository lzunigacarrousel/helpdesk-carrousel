<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$fails=0;
function ok(bool $cond,string $msg):void{
    global $fails;
    echo ($cond?'[OK] ':'[FALLO] ').$msg.PHP_EOL;
    if(!$cond)$fails++;
}
function body(string $path):string{
    $v=@file_get_contents($path);
    return is_string($v)?$v:'';
}

$install=body($root.'/database/INSTALAR.sql');
$verify=body($root.'/database/VERIFICAR_INSTALACION.sql');
$migration=body($root.'/database/MIGRAR_EXTERNAL_PROFILES_V2_20260912.sql');
$migrationVerify=body($root.'/database/VERIFICAR_EXTERNAL_PROFILES_V2_20260912.sql');
$controller=body($root.'/app/Controllers/ExternalController.php');
$externalView=body($root.'/app/Views/admin/externals.php');
$usersView=body($root.'/app/Views/admin/users.php');

// Esquema canónico: no esconder datos operativos dentro de notes.
ok(str_contains($install,'contact_position VARCHAR(140) NOT NULL'), 'Esquema canónico agrega cargo / función del contacto');
ok(str_contains($install,'service_name VARCHAR(190) NOT NULL'), 'Esquema canónico agrega servicio principal');
ok(str_contains($install,'support_reference VARCHAR(190) NULL'), 'Esquema canónico agrega referencia de soporte opcional');
ok(str_contains($verify,'contact_position'), 'Verificador canónico exige cargo del contacto');
ok(str_contains($verify,'service_name'), 'Verificador canónico exige servicio principal');
ok(str_contains($verify,'support_reference'), 'Verificador canónico reconoce referencia de soporte');

// Instalaciones existentes deben poder migrarse sin reinstalar.
ok($migration!=='', 'Existe migración específica de perfil externo V2');
ok(str_contains($migration,'contact_position'), 'Migración agrega cargo del contacto');
ok(str_contains($migration,'service_name'), 'Migración agrega servicio principal');
ok(str_contains($migration,'support_reference'), 'Migración agrega referencia de soporte');
ok(str_contains($migration,'information_schema.COLUMNS'), 'Migración comprueba columnas antes de agregarlas');
ok($migrationVerify!=='', 'Existe verificador específico de perfil externo V2');
ok(str_contains($migrationVerify,'external_profiles'), 'Verificador específico revisa external_profiles');

// Backend: alta, edición y conversión trabajan con el perfil completo.
ok(str_contains($controller,"Http::post('contact_position')"), 'Backend recibe cargo del contacto');
ok(str_contains($controller,"Http::post('service_name')"), 'Backend recibe servicio principal');
ok(str_contains($controller,"Http::post('support_reference')"), 'Backend recibe referencia de soporte');
ok(str_contains($controller,"mb_strlen(\$phone)"), 'Backend valida teléfono del proveedor');
ok(str_contains($controller,"mb_strlen(\$contactPosition)"), 'Backend valida cargo del contacto');
ok(str_contains($controller,"mb_strlen(\$serviceName)"), 'Backend valida servicio principal');
ok(str_contains($controller,'contact_position,service_name,support_reference'), 'Backend persiste campos estructurados en external_profiles');
ok(str_contains($controller,'ep.contact_position')&&str_contains($controller,'ep.service_name'), 'Directorio consulta datos estructurados del proveedor');

// Alta y edición: información operativa obligatoria y clara.
ok(substr_count($externalView,'name="contact_position"')>=2, 'Alta y edición capturan cargo / función');
ok(substr_count($externalView,'name="service_name"')>=2, 'Alta y edición capturan servicio principal');
ok(substr_count($externalView,'name="support_reference"')>=2, 'Alta y edición capturan referencia de soporte');
ok(substr_count($externalView,'name="phone"')>=2, 'Alta y edición conservan teléfono');
ok(substr_count($externalView,'name="phone" required')>=1, 'Alta exige teléfono');
ok(substr_count($externalView,'name="contact_position" required')>=1, 'Alta exige cargo / función');
ok(substr_count($externalView,'name="service_name" required')>=1, 'Alta exige servicio principal');
ok(str_contains($externalView,'Notas internas'), 'Notas quedan separadas del servicio principal');

// Conversión interno -> externo también debe producir un perfil completo.
ok(str_contains($usersView,'name="contact_position"'), 'Conversión a externo solicita cargo / función');
ok(str_contains($usersView,'name="service_name"'), 'Conversión a externo solicita servicio principal');
ok(str_contains($usersView,'name="support_reference"'), 'Conversión a externo permite referencia de soporte');
ok(str_contains($usersView,'name="phone"')||str_contains($controller,'$before[\'phone\']'), 'Conversión conserva teléfono de la identidad interna');

// Contratos previos que no deben romperse.
ok(str_contains($controller,"UPDATE user_sessions SET revoked_at=NOW()"), 'Se conserva revocación de sesiones');
ok(str_contains($controller,"UPDATE otp_codes SET consumed_at=NOW()"), 'Se conserva invalidación de OTP');
ok(str_contains($controller,'EXTERNAL_USER_UPDATED'), 'Se conserva auditoría de edición');
ok(str_contains($controller,'USER_CONVERTED_TO_EXTERNAL'), 'Se conserva auditoría de conversión');
ok(str_contains($controller,"LOWER(email)=?"), 'Se conserva prevención de correos duplicados');

if($fails){
    fwrite(STDERR,PHP_EOL."[ERROR] {$fails} validación(es) fallaron.".PHP_EOL);
    exit(1);
}

echo PHP_EOL."[OK] Regresión perfil operativo de proveedores completada.".PHP_EOL;
