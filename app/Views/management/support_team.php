<?php
$pageTitle='Equipo de soporte';$pageSection='Equipo de soporte';$activeNav='support-team';$helpContext='reports';
require APP_ROOT.'/app/Views/shared/app_start.php';
$summary=$summary??[];$members=$members??[];
$fmtMin=static function($value):string{if($value===null||$value==='')return '—';$m=(int)round((float)$value);return $m<60?$m.' min':round($m/60,1).' h';};
$fmtHours=static fn($value):string=>$value===null||$value===''?'—':round((float)$value,1).' h';
$fmtNps=static fn($value):string=>$value===null||$value===''?'—':(((int)$value>0?'+':'').(int)$value);
$norm=static function(string $value):string{return mb_strtolower(trim((string)preg_replace('/\s+/u',' ',$value)),'UTF-8');};
?>
<style nonce="<?= htmlspecialchars(CSP_NONCE) ?>">
.support-team-page{max-width:1500px;margin:0 auto}.support-team-summary{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:12px;margin:0 0 16px}.support-team-summary article,.support-member{background:var(--card);border:1px solid var(--border);border-radius:16px}.support-team-summary article{padding:15px 16px;min-height:92px}.support-team-summary span{display:block;color:var(--muted);font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.04em}.support-team-summary strong{display:block;margin-top:5px;font-size:25px;color:var(--brand-dark)}.support-team-summary small{display:block;margin-top:3px;color:var(--muted);font-size:10.5px}.support-team-list{display:grid;gap:10px}.support-member{padding:14px 16px;display:grid;grid-template-columns:minmax(260px,1.45fr) repeat(5,minmax(105px,.65fr));gap:12px;align-items:center}.support-member-main strong{display:block;font-size:16px}.support-member-main span,.support-member-main small{display:block;color:var(--muted);margin-top:3px}.support-member-main small{overflow-wrap:anywhere}.support-member-metric{min-width:0}.support-member-metric span{display:block;color:var(--muted);font-size:10.5px}.support-member-metric strong{display:block;margin-top:3px;font-size:17px}.support-member-metric small{display:block;margin-top:3px;color:var(--muted);font-size:10px;line-height:1.35}.support-member-load{display:flex;gap:6px;flex-wrap:wrap;margin-top:8px}.support-member-load span{padding:4px 8px;border:1px solid var(--border);border-radius:999px;font-size:10.5px;color:var(--text)}.support-team-head{margin-bottom:16px}.support-team-head .mgmt-head-actions{display:flex;gap:8px;flex-wrap:wrap}.support-nps-value.positive{color:var(--success)}.support-nps-value.negative{color:var(--danger)}.support-nps-value.neutral{color:var(--brand-dark)}@media(max-width:1100px){.support-team-summary{grid-template-columns:repeat(3,1fr)}.support-member{grid-template-columns:1fr 1fr 1fr}.support-member-main{grid-column:1/-1}}@media(max-width:700px){.support-team-summary{grid-template-columns:1fr 1fr}.support-member{grid-template-columns:1fr 1fr}.support-member-main{grid-column:1/-1}.support-team-head{align-items:stretch}.support-team-head .mgmt-head-actions{width:100%}.support-team-head .btn{flex:1}}
</style>
<div class="support-team-page">
  <div class="mgmt-head support-team-head">
    <div><span class="mgmt-kicker">Operación interna</span><h1>Equipo de soporte</h1><p>Carga actual, tiempos de atención y satisfacción por integrante.</p></div>
    <div class="mgmt-head-actions"><a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/gestion/informes">Informes</a><a class="btn btn-primary" href="<?= APP_BASE_URL ?>/gestion/equipo/exportar" data-action-message="Preparando archivo Excel…">Descargar Excel (.xlsx)</a></div>
  </div>

  <?php $nps=$summary['nps_value']??null;$npsClass=$nps===null?'neutral':((int)$nps>0?'positive':((int)$nps<0?'negative':'neutral')); ?>
  <section class="support-team-summary" aria-label="Resumen del equipo">
    <article><span>Integrantes</span><strong><?= (int)($summary['members']??0) ?></strong><small>Equipo habilitado</small></article>
    <article><span>Casos activos</span><strong><?= (int)($summary['active_cases']??0) ?></strong><small>Sin finalizar</small></article>
    <article><span>En proceso</span><strong><?= (int)($summary['in_progress']??0) ?></strong><small>Atención en curso</small></article>
    <article><span>En espera</span><strong><?= (int)($summary['pending_cases']??0) ?></strong><small>Pausados por dependencia</small></article>
    <article><span>Resueltos 30 días</span><strong><?= (int)($summary['resolved_30']??0) ?></strong><small>Producción reciente</small></article>
    <article><span>NPS</span><strong class="support-nps-value <?= $npsClass ?>"><?= htmlspecialchars($fmtNps($nps)) ?></strong><small><?= (int)($summary['nps_responses']??0) ?> respuesta(s)<?= ($summary['avg_rating']??null)!==null?' · prom. '.htmlspecialchars((string)$summary['avg_rating']).'/10':'' ?></small></article>
  </section>

  <section class="support-team-list" aria-label="Integrantes de soporte">
    <?php foreach($members as $m):
      $position=trim((string)($m['position_name']??''));$assignment=trim((string)($m['assignment_name']??''));
      $showAssignment=$assignment!==''&&$norm($assignment)!==$norm($position);
      $memberNps=$m['nps_value']??null;$memberNpsClass=$memberNps===null?'neutral':((int)$memberNps>0?'positive':((int)$memberNps<0?'negative':'neutral'));
    ?>
      <article class="support-member">
        <div class="support-member-main">
          <strong><?= htmlspecialchars($m['full_name']) ?></strong>
          <span><?= htmlspecialchars($m['role_name']) ?><?= $position!==''?' · '.htmlspecialchars($position):'' ?></span>
          <small><?= htmlspecialchars($m['email']) ?><?= $showAssignment?' · '.htmlspecialchars($assignment):'' ?></small>
          <div class="support-member-load"><span><?= (int)$m['active_cases'] ?> activos</span><span><?= (int)$m['in_progress'] ?> en proceso</span><span><?= (int)$m['pending_cases'] ?> en espera</span></div>
        </div>
        <div class="support-member-metric"><span>Resueltos 30 días</span><strong><?= (int)$m['resolved_30'] ?></strong></div>
        <div class="support-member-metric"><span>Primera respuesta</span><strong><?= htmlspecialchars($fmtMin($m['avg_first_response_min'])) ?></strong></div>
        <div class="support-member-metric"><span>Resolución promedio</span><strong><?= htmlspecialchars($fmtHours($m['avg_resolution_hours'])) ?></strong></div>
        <div class="support-member-metric"><span>NPS</span><strong class="support-nps-value <?= $memberNpsClass ?>"><?= htmlspecialchars($fmtNps($memberNps)) ?></strong><small><?= (int)$m['nps_responses'] ?> respuesta(s)<?= $m['avg_rating']!==null?' · '.htmlspecialchars((string)round((float)$m['avg_rating'],1)).'/10':'' ?></small></div>
        <div class="support-member-metric"><span>Último acceso</span><strong style="font-size:13px"><?= !empty($m['last_login_at'])?htmlspecialchars(date('d/m/Y H:i',strtotime((string)$m['last_login_at']))):'Sin ingreso todavía' ?></strong></div>
      </article>
    <?php endforeach; ?>
    <?php if(!$members): ?><div class="card"><div class="empty-state"><strong>No hay integrantes activos en soporte.</strong></div></div><?php endif; ?>
  </section>
</div>
<?php require APP_ROOT.'/app/Views/shared/app_end.php'; ?>
