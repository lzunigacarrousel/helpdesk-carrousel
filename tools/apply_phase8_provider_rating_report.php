<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$servicePath=$root.'/app/Services/ProviderParticipationService.php';
$controllerPath=$root.'/app/Controllers/ExternalReportController.php';
$viewPath=$root.'/app/Views/management/external_report.php';

function readLf(string $path): string
{
    if(!is_file($path)){
        fwrite(STDERR,'[ERROR] No existe: '.$path.PHP_EOL);
        exit(1);
    }
    $body=(string)file_get_contents($path);
    return str_replace(["\r\n","\r"],"\n",$body);
}

function writeLf(string $path,string $body): void
{
    file_put_contents($path,str_replace(["\r\n","\r"],"\n",$body));
}

function replaceOnce(string $body,string $old,string $new,string $label): string
{
    $count=substr_count($body,$old);
    if($count!==1){
        fwrite(STDERR,"[ERROR] {$label}: esperaba 1 coincidencia y encontro {$count}.".PHP_EOL);
        exit(1);
    }
    echo "[OK] {$label}.".PHP_EOL;
    return str_replace($old,$new,$body);
}

// 1) Filtro de valoración sobre filas ya enriquecidas.
$service=readLf($servicePath);
if(str_contains($service,"\$rating=(string)(\$filters['rating']??'');")){
    echo "[OK] ProviderParticipationService filtra por valoración: ya aplicado.".PHP_EOL;
}else{
    $service=replaceOnce(
        $service,
        "        \$activity=(string)(\$filters['activity']??'');\n        \$from=(string)(\$filters['from']??'');",
        "        \$activity=(string)(\$filters['activity']??'');\n        \$rating=(string)(\$filters['rating']??'');\n        \$from=(string)(\$filters['from']??'');",
        'ProviderParticipationService lee filtro rating'
    );
    $service=replaceOnce(
        $service,
        "return array_values(array_filter(\$rows,static function(array \$row)use(\$q,\$provider,\$state,\$activity,\$from,\$to):bool{",
        "return array_values(array_filter(\$rows,static function(array \$row)use(\$q,\$provider,\$state,\$activity,\$rating,\$from,\$to):bool{",
        'ProviderParticipationService captura rating en closure'
    );
    $service=replaceOnce(
        $service,
        "            if(\$activity!==''&&\$activity!=='NONE'&&(string)(\$row['work_status']??'')!==\$activity)return false;\n\n            \$day=substr((string)(\$row['granted_at']??''),0,10);",
        "            if(\$activity!==''&&\$activity!=='NONE'&&(string)(\$row['work_status']??'')!==\$activity)return false;\n            if(\$rating==='UNRATED'&&(\$row['provider_rating_score']??null)!==null)return false;\n            if(\$rating!==''&&\$rating!=='UNRATED'){\n                \$ratingScore=(int)\$rating;\n                if(\$ratingScore<1||\$ratingScore>5||(int)(\$row['provider_rating_score']??0)!==\$ratingScore)return false;\n            }\n\n            \$day=substr((string)(\$row['granted_at']??''),0,10);",
        'ProviderParticipationService aplica rating vigente'
    );
    writeLf($servicePath,$service);
}

// 2) Controller: enriquecer una sola vez, filtrar, resumir y exportar.
$controller=readLf($controllerPath);
if(!str_contains($controller,'ProviderRatingService')){
    $controller=replaceOnce(
        $controller,
        'use App\\Services\\{ProviderParticipationService,XlsxExportService};',
        'use App\\Services\\{ProviderParticipationService,ProviderRatingService,XlsxExportService};',
        'ExternalReportController importa ProviderRatingService'
    );
}

if(!str_contains($controller,'$ratingService=new ProviderRatingService($pdo);')){
    $controller=replaceOnce(
        $controller,
        "        \$service=new ProviderParticipationService(Database::pdo());\n        \$allRows=\$service->rows();\n        \$filters=\$this->filters();\n        \$rows=ProviderParticipationService::applyFilters(\$allRows,\$filters);\n\n        View::render('management/external_report',[\n            'user'=>Auth::user(),\n            'rows'=>\$rows,\n            'summary'=>ProviderParticipationService::summary(\$rows),\n            'filters'=>\$filters,\n            'providers'=>\$service->registeredProviders(),\n            'activityOptions'=>ProviderParticipationService::activityOptions(),\n        ]);",
        "        \$pdo=Database::pdo();\n        \$service=new ProviderParticipationService(\$pdo);\n        \$ratingService=new ProviderRatingService(\$pdo);\n        \$allRows=\$ratingService->enrichRows(\$service->rows());\n        \$filters=\$this->filters();\n        \$rows=ProviderParticipationService::applyFilters(\$allRows,\$filters);\n        \$providerRatingSummary=ProviderRatingService::providerSummary(\$rows);\n\n        View::render('management/external_report',[\n            'user'=>Auth::user(),\n            'rows'=>\$rows,\n            'summary'=>ProviderParticipationService::summary(\$rows),\n            'filters'=>\$filters,\n            'providers'=>\$service->registeredProviders(),\n            'activityOptions'=>ProviderParticipationService::activityOptions(),\n            'ratingOptions'=>ProviderRatingService::ratingOptions(),\n            'providerRatingSummary'=>\$providerRatingSummary,\n        ]);",
        'Informe enriquece filas y expone resumen de calidad'
    );
}else{
    echo "[OK] Informe enriquece filas y expone resumen de calidad: ya aplicado.".PHP_EOL;
}

if(!str_contains($controller,'$providerRatingSummary=ProviderRatingService::providerSummary($rows);') || substr_count($controller,'$providerRatingSummary=ProviderRatingService::providerSummary($rows);')<2){
    $controller=replaceOnce(
        $controller,
        "        \$service=new ProviderParticipationService(Database::pdo());\n        \$filters=\$this->filters();\n        \$rows=ProviderParticipationService::applyFilters(\$service->rows(),\$filters);\n        \$summary=ProviderParticipationService::summary(\$rows);\n        \$data=[];",
        "        \$pdo=Database::pdo();\n        \$service=new ProviderParticipationService(\$pdo);\n        \$ratingService=new ProviderRatingService(\$pdo);\n        \$filters=\$this->filters();\n        \$rows=ProviderParticipationService::applyFilters(\$ratingService->enrichRows(\$service->rows()),\$filters);\n        \$summary=ProviderParticipationService::summary(\$rows);\n        \$providerRatingSummary=ProviderRatingService::providerSummary(\$rows);\n        \$data=[];",
        'XLSX usa dataset enriquecido y resumen de calidad'
    );
}

if(!str_contains($controller,"(string)(\$r['provider_rating_label']??'Sin evaluar')")){
    $controller=replaceOnce(
        $controller,
        "                (int)(\$r['returns']??0),\n                \$this->ticketStatusLabel((string)\$r['ticket_status']),",
        "                (int)(\$r['returns']??0),\n                \$r['provider_rating_score']===null?'':(int)\$r['provider_rating_score'],\n                (string)(\$r['provider_rating_label']??'Sin evaluar'),\n                (string)(\$r['provider_rating_comment']??''),\n                (string)(\$r['provider_rating_actor']??''),\n                \$this->displayDate(\$r['provider_rating_at']??null),\n                (int)(\$r['provider_rating_revisions']??0),\n                \$this->ticketStatusLabel((string)\$r['ticket_status']),",
        'XLSX agrega detalle de valoración vigente'
    );
}

if(!str_contains($controller,'$qualityData=[];')){
    $controller=replaceOnce(
        $controller,
        "        Audit::log('EXTERNAL_REPORT_EXPORTED_XLSX','report',null,null,null,[",
        "        \$qualityData=[];\n        foreach(\$providerRatingSummary as \$quality){\n            \$qualityData[]=[\n                (string)(\$quality['organization']??''),\n                \$quality['average_score']===null?'':(float)\$quality['average_score'],\n                (int)(\$quality['rated_cycles']??0),\n                (int)(\$quality['unrated_cycles']??0),\n            ];\n        }\n\n        Audit::log('EXTERNAL_REPORT_EXPORTED_XLSX','report',null,null,null,[",
        'XLSX prepara resumen por proveedor'
    );
}

if(!str_contains($controller,"'Valoración proveedor'")){
    $controller=replaceOnce(
        $controller,
        "                    'Respuestas','Archivos','Informes','Entregas listas','Devoluciones','Estado ticket','Estado ciclo',",
        "                    'Respuestas','Archivos','Informes','Entregas listas','Devoluciones',\n                    'Valoración proveedor','Etiqueta valoración','Comentario valoración','Registró valoración','Fecha valoración','Correcciones valoración',\n                    'Estado ticket','Estado ciclo',",
        'XLSX agrega encabezados de valoración'
    );
}

if(!str_contains($controller,"'Promedio calidad'")){
    $controller=replaceOnce(
        $controller,
        "                'rows'=>\$data,\n            ],\n        ]);",
        "                'rows'=>\$data,\n            ],\n            [\n                'name'=>'Calidad proveedores',\n                'title'=>'Helpdesk Carrousel · Calidad por proveedor',\n                'subtitle'=>'Promedio vigente; los ciclos sin evaluar no equivalen a cero',\n                'headers'=>['Proveedor','Promedio calidad','Ciclos evaluados','Ciclos sin evaluar'],\n                'rows'=>\$qualityData,\n            ],\n        ]);",
        'XLSX agrega hoja de calidad por proveedor'
    );
}

if(!str_contains($controller,"\$rating=(string)(\$_GET['rating']??'');")){
    $controller=replaceOnce(
        $controller,
        "        \$activity=(string)(\$_GET['activity']??'');\n        \$validActivities=array_keys(ProviderParticipationService::activityOptions());\n        if(\$activity!==''&&!in_array(\$activity,\$validActivities,true))\$activity='';\n\n        return [",
        "        \$activity=(string)(\$_GET['activity']??'');\n        \$validActivities=array_keys(ProviderParticipationService::activityOptions());\n        if(\$activity!==''&&!in_array(\$activity,\$validActivities,true))\$activity='';\n\n        \$rating=(string)(\$_GET['rating']??'');\n        \$validRatings=array_keys(ProviderRatingService::ratingOptions());\n        if(\$rating!==''&&!in_array(\$rating,\$validRatings,true))\$rating='';\n\n        return [",
        'Controller normaliza filtro de valoración'
    );
    $controller=replaceOnce(
        $controller,
        "            'activity'=>\$activity,\n            'from'=>\$this->validDate",
        "            'activity'=>\$activity,\n            'rating'=>\$rating,\n            'from'=>\$this->validDate",
        'Controller devuelve filtro de valoración'
    );
}
writeLf($controllerPath,$controller);

// 3) Vista: filtro, resumen por proveedor y valoración por ciclo.
$view=readLf($viewPath);
if(!str_contains($view,'$ratingOptions=$ratingOptions??[];')){
    $view=replaceOnce(
        $view,
        "\$filters=\$filters??['q'=>'','provider'=>0,'state'=>'','activity'=>'','from'=>'','to'=>''];\n\$providers=\$providers??[];\n\$activityOptions=\$activityOptions??[];",
        "\$filters=\$filters??['q'=>'','provider'=>0,'state'=>'','activity'=>'','rating'=>'','from'=>'','to'=>''];\n\$providers=\$providers??[];\n\$activityOptions=\$activityOptions??[];\n\$ratingOptions=\$ratingOptions??[];\n\$providerRatingSummary=\$providerRatingSummary??[];",
        'Vista prepara opciones y resumen de valoración'
    );
}

if(!str_contains($view,"'rating'=>\$filters['rating']")){
    $view=replaceOnce(
        $view,
        "    'activity'=>\$filters['activity'],\n    'from'=>\$filters['from'],",
        "    'activity'=>\$filters['activity'],\n    'rating'=>\$filters['rating'],\n    'from'=>\$filters['from'],",
        'Exportación conserva filtro de valoración'
    );
}

if(!str_contains($view,'name="rating"')){
    $view=replaceOnce(
        $view,
        "    <label>Actividad actual<select class=\"form-control\" name=\"activity\"><option value=\"\">Todas</option><?php foreach(\$activityOptions as \$value=>\$label): ?><option value=\"<?= htmlspecialchars((string)\$value) ?>\" <?= \$filters['activity']===\$value?'selected':'' ?>><?= htmlspecialchars((string)\$label) ?></option><?php endforeach; ?></select></label>\n    <label>Desde",
        "    <label>Actividad actual<select class=\"form-control\" name=\"activity\"><option value=\"\">Todas</option><?php foreach(\$activityOptions as \$value=>\$label): ?><option value=\"<?= htmlspecialchars((string)\$value) ?>\" <?= \$filters['activity']===\$value?'selected':'' ?>><?= htmlspecialchars((string)\$label) ?></option><?php endforeach; ?></select></label>\n    <label>Valoración<select class=\"form-control\" name=\"rating\"><option value=\"\">Todas</option><?php foreach(\$ratingOptions as \$value=>\$label): ?><option value=\"<?= htmlspecialchars((string)\$value) ?>\" <?= \$filters['rating']===(string)\$value?'selected':'' ?>><?= htmlspecialchars((string)\$label) ?></option><?php endforeach; ?></select></label>\n    <label>Desde",
        'Vista agrega filtro Valoración'
    );
}

if(!str_contains($view,'Calidad por proveedor')){
    $qualityBlock=<<<'PHP'

  <section class="data-table-shell" aria-label="Calidad por proveedor">
    <div class="case-section-head"><div><span class="mgmt-kicker">Calidad interna</span><h2>Calidad por proveedor</h2><p>Solo las valoraciones vigentes entran al promedio; Sin evaluar no equivale a cero.</p></div></div>
    <div class="data-table-wrap"><table class="data-table">
      <thead><tr><th>Proveedor</th><th>Promedio de calidad</th><th>Ciclos evaluados</th><th>Sin evaluar</th></tr></thead>
      <tbody>
      <?php foreach($providerRatingSummary as $quality): ?>
        <tr>
          <td data-label="Proveedor"><strong><?= htmlspecialchars((string)($quality['organization']??'')) ?></strong></td>
          <td data-label="Promedio de calidad"><strong><?= ($quality['average_score']??null)===null?'—':htmlspecialchars(number_format((float)$quality['average_score'],2)) ?></strong></td>
          <td data-label="Ciclos evaluados"><?= (int)($quality['rated_cycles']??0) ?></td>
          <td data-label="Sin evaluar"><?= (int)($quality['unrated_cycles']??0) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if(!$providerRatingSummary): ?><tr><td class="data-table-empty" data-label="" colspan="4">No hay ciclos para resumir con estos filtros.</td></tr><?php endif; ?>
      </tbody>
    </table></div>
  </section>
PHP;
    $view=replaceOnce(
        $view,
        "\n  <section class=\"data-table-shell\" aria-label=\"Participación operativa de proveedores\">",
        $qualityBlock."\n\n  <section class=\"data-table-shell\" aria-label=\"Participación operativa de proveedores\">",
        'Vista agrega resumen de calidad por proveedor'
    );
}

if(!str_contains($view,'<th>Valoración</th>')){
    $view=replaceOnce(
        $view,
        '<thead><tr><th>Proveedor / Ticket</th><th>Asignación</th><th>Primera respuesta</th><th>Participación</th><th>Actividad actual</th><th>Trabajo</th><th>Resultado</th></tr></thead>',
        '<thead><tr><th>Proveedor / Ticket</th><th>Asignación</th><th>Primera respuesta</th><th>Participación</th><th>Actividad actual</th><th>Trabajo</th><th>Resultado</th><th>Valoración</th></tr></thead>',
        'Tabla agrega columna Valoración'
    );
    $ratingCell=<<<'PHP'
          <td data-label="Valoración">
            <?php if(($r['provider_rating_score']??null)===null): ?>
              <div class="external-metric-stack"><strong>Sin evaluar</strong><small>No afecta el promedio.</small></div>
            <?php else: ?>
              <div class="external-metric-stack"><strong><?= (int)$r['provider_rating_score'] ?>★ · <?= htmlspecialchars((string)($r['provider_rating_label']??'')) ?></strong><?php if(!empty($r['provider_rating_comment'])): ?><small><?= htmlspecialchars((string)$r['provider_rating_comment']) ?></small><?php endif; ?><?php if(!empty($r['provider_rating_revisions'])): ?><small><?= (int)$r['provider_rating_revisions'] ?> corrección(es)</small><?php endif; ?></div>
            <?php endif; ?>
          </td>
PHP;
    $view=replaceOnce(
        $view,
        "          </td>\n        </tr>\n      <?php endforeach; ?>\n      <?php if(!\$rows): ?><tr><td class=\"data-table-empty\" data-label=\"\" colspan=\"7\">No hay resultados con estos filtros.</td></tr><?php endif; ?>",
        "          </td>\n".$ratingCell."        </tr>\n      <?php endforeach; ?>\n      <?php if(!\$rows): ?><tr><td class=\"data-table-empty\" data-label=\"\" colspan=\"8\">No hay resultados con estos filtros.</td></tr><?php endif; ?>",
        'Tabla renderiza valoración vigente'
    );
}
writeLf($viewPath,$view);

foreach([$servicePath,$controllerPath,$viewPath] as $path){
    $cmd='"'.PHP_BINARY.'" -l '.escapeshellarg($path);
    passthru($cmd,$code);
    if($code!==0)exit($code);
}

echo '[OK] Informe de calidad de proveedores aplicado. No se modifico la BD.'.PHP_EOL;
