<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$viewPath=$root.'/app/Views/tickets/show.php';

if(!is_file($viewPath)){
    fwrite(STDERR,"No existe app/Views/tickets/show.php".PHP_EOL);
    exit(1);
}

$content=(string)file_get_contents($viewPath);
if($content===''){
    fwrite(STDERR,"No fue posible leer app/Views/tickets/show.php".PHP_EOL);
    exit(1);
}

if(str_contains($content,'ticket-requester-activities')){
    echo "[OK] El resumen seguro para solicitante ya estaba aplicado.".PHP_EOL;
    exit(0);
}

$needle="  <?php if(\$isSupport):\n    \$activityTypeLabels=[";
if(!str_contains($content,$needle)){
    $needle="  <?php if(\$isSupport):\r\n    \$activityTypeLabels=[";
}
if(!str_contains($content,$needle)){
    fwrite(STDERR,"No se encontro el punto seguro de insercion antes del modulo interno de actividades.".PHP_EOL);
    exit(1);
}

$block=<<<'PHP'
  <?php if($isRequester&&!empty($requesterActivities)):
    $requesterActivityTypeLabels=[
      'VISITA_EN_SITIO'=>'Visita en sitio',
      'SOPORTE_REMOTO'=>'Soporte remoto',
      'SEGUIMIENTO'=>'Seguimiento',
      'INTERVENCION_PROVEEDOR'=>'Intervención programada',
      'OTRA'=>'Atención programada',
    ];
    $requesterActivityStatusLabels=[
      'PROGRAMADA'=>'Programada',
      'EN_CURSO'=>'En curso',
      'FINALIZADA'=>'Finalizada',
      'CANCELADA'=>'Cancelada',
    ];
  ?>
  <section class="card ticket-activities-card ticket-requester-activities">
    <div class="card-body">
      <div class="case-section-head ticket-activities-head">
        <div>
          <span class="ticket-kicker">Seguimiento</span>
          <h2>Próxima atención</h2>
          <p class="ticket-activities-intro">Aquí verás únicamente la información de atención que el equipo de soporte publicó para ti.</p>
        </div>
      </div>
      <div class="ticket-activity-grid <?= count($requesterActivities)===1?'is-single':'' ?>">
        <?php foreach($requesterActivities as $activity):
          $requesterType=(string)($activity['activity_type']??'OTRA');
          $requesterStatus=(string)($activity['status']??'PROGRAMADA');
        ?>
          <article class="ticket-activity-item <?= in_array($requesterStatus,['PROGRAMADA','EN_CURSO'],true)?'is-active':'' ?>">
            <div class="ticket-activity-item-head">
              <div class="ticket-activity-kind">
                <strong><?= htmlspecialchars($requesterActivityTypeLabels[$requesterType]??'Atención programada') ?></strong>
                <small>Actualización publicada por soporte</small>
              </div>
              <span class="ticket-activity-status status-<?= strtolower($requesterStatus) ?>"><?= htmlspecialchars($requesterActivityStatusLabels[$requesterStatus]??$requesterStatus) ?></span>
            </div>
            <div class="ticket-activity-meta">
              <div><span>Fecha programada</span><strong><?= !empty($activity['scheduled_start_at'])?htmlspecialchars(date('d/m/Y H:i',strtotime((string)$activity['scheduled_start_at']))):'Por confirmar' ?></strong></div>
              <?php if(!empty($activity['scheduled_end_at'])): ?><div><span>Fin estimado</span><strong><?= htmlspecialchars(date('d/m/Y H:i',strtotime((string)$activity['scheduled_end_at']))) ?></strong></div><?php endif; ?>
              <?php if(!empty($activity['park_name'])): ?><div><span>Ubicación</span><strong><?= htmlspecialchars((string)$activity['park_name']) ?></strong></div><?php endif; ?>
            </div>
            <p class="ticket-activity-objective"><?= nl2br(htmlspecialchars((string)$activity['requester_summary'])) ?></p>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

PHP;

$eol=str_contains($content,"\r\n")?"\r\n":"\n";
$block=str_replace("\n",$eol,$block);
$content=str_replace($needle,$block.$needle,$content);

if(str_contains($content,'Ã')||str_contains($content,'Â')){
    fwrite(STDERR,"Se detecto mojibake despues de aplicar Task 8. No se escribio la vista.".PHP_EOL);
    exit(1);
}

file_put_contents($viewPath,$content);

$cmd='"'.PHP_BINARY.'" -l "'.$viewPath.'"';
exec($cmd.' 2>&1',$lint,$code);
foreach($lint as $line)echo $line.PHP_EOL;
if($code!==0){
    fwrite(STDERR,"La vista generada no supera php -l.".PHP_EOL);
    exit(1);
}

echo "[OK] Resumen seguro para solicitante aplicado.".PHP_EOL;
echo "[OK] La vista consume solo requesterActivities y no agrega controles internos.".PHP_EOL;
echo "[OK] No se modifico la base de datos.".PHP_EOL;
