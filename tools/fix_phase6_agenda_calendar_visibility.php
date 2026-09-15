<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$php='C:\\xampp\\php\\php.exe';
$view=$root.'/app/Views/agenda/index.php';
$test=$root.'/tests/phase6_agenda_calendar_visibility_regression.php';
$uiTest=$root.'/tests/phase6_agenda_ui_regression.php';

function fail(string $message): never {
    fwrite(STDERR,'[ERROR] '.$message.PHP_EOL);
    exit(1);
}
function readStrict(string $path): string {
    $value=@file_get_contents($path);
    if(!is_string($value)) fail('No se pudo leer '.$path);
    return $value;
}
function writeStrict(string $path,string $content): void {
    if(file_put_contents($path,$content)===false) fail('No se pudo escribir '.$path);
}
function replaceOnce(string $content,string $search,string $replace,string $label): string {
    $count=substr_count($content,$search);
    if($count!==1) fail($label.' esperaba exactamente 1 coincidencia y encontró '.$count.'.');
    return str_replace($search,$replace,$content);
}
function run(string $command): int {
    passthru($command,$code);
    return $code;
}

chdir($root) || fail('No se pudo entrar al repositorio.');

$body=readStrict($view);
$eol=str_contains($body,"\r\n")?"\r\n":"\n";
$normalized=str_replace(["\r\n","\r"],"\n",$body);

$oldOpen=<<<'PHP'
    <?php if(!$calendarActivities): ?>
      <?php if(!$multiDayActivities): ?><div class="empty-state">No hay actividades visibles en esta semana.</div><?php endif; ?>
    <?php else: ?>
PHP;
$newOpen=<<<'PHP'
    <?php if(!$calendarActivities&&!$multiDayActivities): ?><div class="empty-state">No hay actividades visibles en esta semana.</div><?php endif; ?>
PHP;
$normalized=replaceOnce($normalized,$oldOpen,$newOpen,'Apertura condicional del calendario');

$oldClose=<<<'PHP'
    </div>
    <?php endif; ?>
  </section>
  <?php endif; ?>
PHP;
$newClose=<<<'PHP'
    </div>
  </section>
  <?php endif; ?>
PHP;
$normalized=replaceOnce($normalized,$oldClose,$newClose,'Cierre condicional del calendario');

if($eol==="\r\n")$normalized=str_replace("\n","\r\n",$normalized);
writeStrict($view,$normalized);

echo '[OK] La cuadrícula semanal ya no depende de tener actividades de un solo día.'.PHP_EOL;

if(run('"'.$php.'" -l "'.$view.'"')!==0) fail('Falló PHP lint en Agenda.');
if(run('"'.$php.'" "'.$test.'"')!==0) fail('La regresión de visibilidad del calendario no quedó en verde.');
if(run('"'.$php.'" "'.$uiTest.'"')!==0) fail('La regresión UI de Agenda no quedó en verde.');

echo '[OK] GREEN confirmado: Calendario permanece visible con actividades multiday.'.PHP_EOL;
