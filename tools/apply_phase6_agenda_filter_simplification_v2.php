<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$php='C:\\xampp\\php\\php.exe';
$controllerPath=$root.'/app/Controllers/AgendaController.php';
$servicePath=$root.'/app/Services/AgendaService.php';
$viewPath=$root.'/app/Views/agenda/index.php';
$cssPath=$root.'/public/assets/css/agenda.css';
$test='tests\\phase6_agenda_filter_ux_regression.php';

function fail(string $message): never { fwrite(STDERR,'[ERROR] '.$message.PHP_EOL); exit(1); }
function run(string $command): int { passthru($command,$code); return $code; }
function readNormalized(string $path,?string &$eol=null): string {
    $raw=@file_get_contents($path);
    if(!is_string($raw))fail('No se pudo leer '.$path);
    $eol=str_contains($raw,"\r\n")?"\r\n":"\n";
    return str_replace(["\r\n","\r"],"\n",$raw);
}
function writeNormalized(string $path,string $content,string $eol): void {
    $content=str_replace(["\r\n","\r"],"\n",$content);
    if($eol==="\r\n")$content=str_replace("\n","\r\n",$content);
    if(file_put_contents($path,$content)===false)fail('No se pudo escribir '.$path);
}
function replaceRegexLiteralOnce(string $content,string $pattern,string $replace,string $label): string {
    $matches=[];
    $count=preg_match_all($pattern,$content,$matches,PREG_OFFSET_CAPTURE);
    if($count!==1)fail($label.' esperaba exactamente 1 coincidencia y encontró '.(int)$count.'.');
    $match=$matches[0][0][0];
    $offset=$matches[0][0][1];
    return substr_replace($content,$replace,$offset,strlen($match));
}

chdir($root)||fail('No se pudo entrar al repositorio.');
exec('git status --porcelain',$status,$statusCode);
if($statusCode!==0)fail('No se pudo consultar git status.');
if(array_filter(array_map('trim',$status)))fail('El working tree debe estar limpio antes de aplicar este ajuste.');

echo '=== RED esperado: filtros actuales todavía son redundantes ==='.PHP_EOL;
$red=run('"'.$php.'" '.$test);
if($red===0)fail('El RED ya está verde; no se aplicará un cambio innecesario.');
echo '[OK] RED confirmado.'.PHP_EOL.PHP_EOL;

$controller=readNormalized($controllerPath,$controllerEol);
$service=readNormalized($servicePath,$serviceEol);
$view=readNormalized($viewPath,$viewEol);
$css=readNormalized($cssPath,$cssEol);
$originals=[
    $controllerPath=>[$controller,$controllerEol],
    $servicePath=>[$service,$serviceEol],
    $viewPath=>[$view,$viewEol],
    $cssPath=>[$css,$cssEol],
];

try{
    $controller=replaceRegexLiteralOnce(
        $controller,
        '~^\s*\$history=.*\R~m',
        '',
        'Eliminar dependencia history del controller'
    );
    $controller=replaceRegexLiteralOnce(
        $controller,
        '~^\s*[\'\"]history[\'\"]=>\$history,\R~m',
        '',
        'Eliminar history del contrato de filtros'
    );

    $service=replaceRegexLiteralOnce(
        $service,
        '~    private function effectiveStates\(array \$filters\): array\n    \{.*?\n    \}\n\n    private function scopedWhere~s',
        <<<'PHP'
    private function effectiveStates(array $filters): array
    {
        $status=(string)($filters['status']??'active');
        if($status==='active')return self::ACTIVE_STATUSES;
        if($status==='all')return self::ALL_STATUSES;
        if(!in_array($status,self::ALL_STATUSES,true))return self::ACTIVE_STATUSES;
        return[$status];
    }

    private function scopedWhere
PHP,
        'Estados efectivos autoritativos'
    );

    if(!str_contains($view,'$weekSwitchStart=')){
        $view=replaceRegexLiteralOnce(
            $view,
            '~\$anchorWeekStart=.*?\n\$monthStart=.*?\n\$monthEnd=.*?\n~',
            <<<'PHP'
$anchorWeekStart=$anchorDate->modify('-'.((int)$anchorDate->format('N')-1).' days');
$monthStart=$anchorDate->modify('first day of this month');
$monthEnd=$anchorDate->modify('last day of this month');
$weekSwitchStart=$activeView==='month'&&$monthStart->format('Y-m')===date('Y-m')
    ?new DateTimeImmutable('monday this week')
    :$anchorWeekStart;
PHP
            ."\n",
            'Ancla Mes a Semana'
        );
    }

    $view=replaceRegexLiteralOnce(
        $view,
        '~\[\'view\'=>\'calendar\',\'from\'=>\$anchorWeekStart->format\(\'Y-m-d\'\),\'to\'=>\$anchorWeekStart->modify\(\'\+6 days\'\)->format\(\'Y-m-d\'\)\]~',
        "['view'=>'calendar','from'=>\$weekSwitchStart->format('Y-m-d'),'to'=>\$weekSwitchStart->modify('+6 days')->format('Y-m-d')]",
        'Enlace Semana'
    );

    $quickLinks=<<<'PHP'
  <div class="agenda-quick-links" aria-label="Navegación de periodo">
    <?php if($activeView==='month'):
      $previousMonth=$monthStart->modify('-1 month');
      $nextMonth=$monthStart->modify('+1 month');
      $todayMonth=new DateTimeImmutable('first day of this month'); ?>
      <a class="btn btn-outline-secondary btn-sm" href="<?= $h($withFilter(['from'=>$previousMonth->format('Y-m-d'),'to'=>$previousMonth->modify('last day of this month')->format('Y-m-d')])) ?>">Mes anterior</a>
      <a class="btn btn-outline-secondary btn-sm" href="<?= $h($withFilter(['from'=>$todayMonth->format('Y-m-d'),'to'=>$todayMonth->modify('last day of this month')->format('Y-m-d')])) ?>">Hoy</a>
      <a class="btn btn-outline-secondary btn-sm" href="<?= $h($withFilter(['from'=>$nextMonth->format('Y-m-d'),'to'=>$nextMonth->modify('last day of this month')->format('Y-m-d')])) ?>">Mes siguiente</a>
    <?php elseif($activeView==='calendar'):
      $currentWeek=new DateTimeImmutable('monday this week'); ?>
      <a class="btn btn-outline-secondary btn-sm" href="<?= $h($withFilter(['from'=>$weekStart->modify('-7 days')->format('Y-m-d'),'to'=>$weekStart->modify('-1 day')->format('Y-m-d')])) ?>">Semana anterior</a>
      <a class="btn btn-outline-secondary btn-sm" href="<?= $h($withFilter(['from'=>$currentWeek->format('Y-m-d'),'to'=>$currentWeek->modify('+6 days')->format('Y-m-d')])) ?>">Hoy</a>
      <a class="btn btn-outline-secondary btn-sm" href="<?= $h($withFilter(['from'=>$weekStart->modify('+7 days')->format('Y-m-d'),'to'=>$weekStart->modify('+13 days')->format('Y-m-d')])) ?>">Semana siguiente</a>
    <?php endif; ?>
  </div>
PHP;
    $view=replaceRegexLiteralOnce(
        $view,
        '~  <div class="agenda-quick-links" aria-label="Rangos rápidos">.*?  </div>~s',
        $quickLinks,
        'Navegación temporal simplificada'
    );

    $filterForm=<<<'PHP'
  <form class="card agenda-filter-card" method="get" action="<?= APP_BASE_URL ?>/agenda">
    <div class="card-body agenda-filters">
      <input type="hidden" name="view" value="<?= $h($filters['view']??'month') ?>">
      <label class="form-label">Responsable<select class="form-control" name="responsible_user_id"><option value="0">Todos visibles</option><?php foreach($options['responsibles']??[] as $person): $id=(int)($person['id']??0); ?><option value="<?= $id ?>" <?= (int)($filters['responsible_user_id']??0)===$id?'selected':'' ?>><?= $h($person['full_name']??'') ?></option><?php endforeach; ?></select></label>
      <label class="form-label">Parque<select class="form-control" name="park_id"><option value="0">Todos visibles</option><?php foreach($options['parks']??[] as $park): $id=(int)($park['id']??0); ?><option value="<?= $id ?>" <?= (int)($filters['park_id']??0)===$id?'selected':'' ?>><?= $h($park['name']??'') ?></option><?php endforeach; ?></select></label>
      <label class="form-label">Tipo<select class="form-control" name="activity_type"><option value="">Todos</option><?php foreach($typeLabels as $code=>$label): ?><option value="<?= $h($code) ?>" <?= ($filters['activity_type']??'')===$code?'selected':'' ?>><?= $h($label) ?></option><?php endforeach; ?></select></label>
      <label class="form-label">Estado<select class="form-control" name="status"><option value="active" <?= ($filters['status']??'active')==='active'?'selected':'' ?>>Activas</option><?php foreach(['PROGRAMADA','EN_CURSO','FINALIZADA','CANCELADA'] as $code): ?><option value="<?= $code ?>" <?= ($filters['status']??'')===$code?'selected':'' ?>><?= $h($statusLabels[$code]) ?></option><?php endforeach; ?><option value="all" <?= ($filters['status']??'')==='all'?'selected':'' ?>>Todas</option></select></label>
      <?php if($activeView==='list'): ?>
        <label class="form-label">Desde<input class="form-control" type="date" name="from" value="<?= $h($filters['from']??'') ?>"></label>
        <label class="form-label">Hasta<input class="form-control" type="date" name="to" value="<?= $h($filters['to']??'') ?>"></label>
      <?php else: ?>
        <input type="hidden" name="from" value="<?= $h($filters['from']??'') ?>">
        <input type="hidden" name="to" value="<?= $h($filters['to']??'') ?>">
      <?php endif; ?>
      <?php if(($filters['scope_mode']??'all')==='mine'||($filters['scope_mode']??'all')==='all'): ?><input type="hidden" name="scope_mode" value="<?= $h($filters['scope_mode']??'all') ?>"><?php endif; ?>
      <div class="agenda-filter-actions"><a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/agenda">Limpiar</a><button class="btn btn-primary" type="submit">Aplicar</button></div>
    </div>
  </form>
PHP;
    $view=replaceRegexLiteralOnce(
        $view,
        '~  <form class="card agenda-filter-card" method="get" action="<\?= APP_BASE_URL \?>/agenda">.*?  </form>~s',
        $filterForm,
        'Formulario de filtros simplificado'
    );

    $view=str_replace("\$filters['view']??'calendar'","\$filters['view']??'month'",$view);

    $css=replaceRegexLiteralOnce(
        $css,
        '~\.agenda-filters\{display:grid;grid-template-columns:repeat\(5,minmax\(150px,1fr\)\);gap:10px;align-items:end\}~',
        '.agenda-filters{display:grid;grid-template-columns:repeat(4,minmax(160px,1fr));gap:10px;align-items:end}',
        'Grid de filtros desktop'
    );
    $css=replaceRegexLiteralOnce(
        $css,
        '~\.agenda-filter-actions\{display:flex;gap:8px;align-items:center;flex-wrap:wrap\}~',
        '.agenda-filter-actions{grid-column:1/-1;display:flex;gap:8px;align-items:center;justify-content:flex-end;flex-wrap:wrap}',
        'Acciones de filtros'
    );

    writeNormalized($controllerPath,$controller,$controllerEol);
    writeNormalized($servicePath,$service,$serviceEol);
    writeNormalized($viewPath,$view,$viewEol);
    writeNormalized($cssPath,$css,$cssEol);

    foreach([$controllerPath,$servicePath,$viewPath] as $file){
        if(run('"'.$php.'" -l "'.$file.'"')!==0)throw new RuntimeException('Falló PHP lint en '.$file);
    }

    $tests=[
        $test,
        'tests\\phase6_agenda_service_regression.php',
        'tests\\phase6_agenda_month_regression.php',
        'tests\\phase6_agenda_ui_regression.php',
        'tests\\phase6_agenda_list_range_regression.php',
        'tests\\phase6_agenda_multiday_calendar_regression.php',
        'tests\\phase6_agenda_calendar_visibility_regression.php',
    ];
    foreach($tests as $case){
        echo PHP_EOL.'=== '.$case.' ==='.PHP_EOL;
        if(run('"'.$php.'" '.$case)!==0)throw new RuntimeException('Regresión falló: '.$case);
    }
    if(run('git --no-pager diff --check')!==0)throw new RuntimeException('git diff --check reportó problemas.');
}catch(Throwable $e){
    foreach($originals as $path=>[$content,$eol])writeNormalized($path,$content,$eol);
    fwrite(STDERR,'[ERROR] '.$e->getMessage().PHP_EOL);
    fwrite(STDERR,'[OK] Archivos productivos restaurados automáticamente.'.PHP_EOL);
    exit(1);
}

echo PHP_EOL.'[OK] GREEN confirmado: filtros de Agenda simplificados.'.PHP_EOL;
echo '[OK] Mes y Semana conservan solo navegación temporal propia; Lista usa Desde/Hasta.'.PHP_EOL;
echo '[OK] Estado es autoritativo; se eliminó Incluir historial y botones duplicados.'.PHP_EOL;
echo '[OK] No se modificó la base de datos en este ajuste.'.PHP_EOL;
