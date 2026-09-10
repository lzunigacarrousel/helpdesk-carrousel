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
.support-team-page{width:100%;margin:0}.support-team-summary{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:10px;margin:0 0 14px}.support-team-summary article{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:13px 14px;min-height:0}.support-team-summary span{display:block;color:var(--muted);font-size:10px;font-weight:850;text-transform:uppercase;letter-spacing:.04em}.support-team-summary strong{display:block;margin-top:4px;font-size:23px;color:var(--brand-dark)}.support-team-summary small{display:block;margin-top:2px;color:var(--muted);font-size:10px}.support-team-head{margin-bottom:14px}.support-team-head .mgmt-head-actions{display:flex;gap:8px;flex-wrap:wrap}.support-nps-value.positive{color:var(--success)}.support-nps-value.negative{color:var(--danger)}.support-nps-value.neutral{color:var(--brand-dark)}.support-member-name strong{display:block;font-size:13px}.support-member-name small{display:block;margin-top:2px}.support-load{display:flex;gap:5px;flex-wrap:wrap;margin-top:6px}.support-load span{padding:3px 6px;border:1px solid var(--border);border-radius:999px;font-size:9.5px;color:var(--text)}@media(max-width:1100px){.support-team-summary{grid-template-columns:repeat(3,1fr)}}@media(max-width:700px){.support-team-summary{grid-template-columns:1fr 1fr}.support-team-head{align-items:stretch}.support-team-head .mgmt-head-actions{width:100%}.support-team-head .btn{flex:1}}
</style>
<div class="support-team-page">
  <div class="mgmt-head support-team-head">
    <div><span class="mgmt-kicker">Operación interna</span><h1>Equipo de soporte</h1></div>
    <div class="mgmt-head-actions"><a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/gestion/informes">Informes</a><a class="btn btn-primary" href="<?= APP_BASE_URL ?>/gestion/equipo/exportar" data-action-message="Preparando archivo Excel…">Descargar Excel (.xlsx)</a></div>
  </div>

  <?php $nps=$summary['nps_value']??null;$npsClass=$nps===null?'neutral':((int)$nps>0?'positive':((int)$nps<0?'negative':'neutral')); ?>
  <section class="support-team-summary" aria-label="Resumen del equipo">
    <article><span>Integrantes</span><strong><?= (int)($summary['members']??0) ?></strong></article>
    <article><span>Casos activos</span><strong><?= (int)($summary['active_cases']??0) ?></strong></article>
    <article><span>En proceso</span><strong><?= (int)($summary['in_progress']??0) ?></strong></article>
    <article><span>En espera</span><strong><?= (int)($summary['pending_cases']??0) ?></strong></article>
    <article><span>Resueltos 30 días</span><strong><?= (int)($summary['resolved_30']??0) ?></strong></article>
    <article><span>NPS</span><strong class="support-nps-value <?= $npsClass ?>"><?= htmlspecialchars($fmtNps($nps)) ?></strong><small><?= (int)($summary['nps_responses']??0) ?> respuesta(s)<?= ($summary['avg_rating']??null)!==null?' · '.htmlspecialchars((string)$summary['avg_rating']).'/10':'' ?></small></article>
  </section>

  <section class="data-table-shell" aria-label="Integrantes de soporte">
    <div class="data-table-wrap">
      <table class="data-table">
        <thead><tr><th>Integrante</th><th>Activos</th><th>En proceso</th><th>En espera</th><th>Resueltos 30 días</th><th>Primera respuesta</th><th>Resolución promedio</th><th>NPS</th><th>Último acceso</th></tr></thead>
        <tbody>
        <?php foreach($members as $m):
          $position=trim((string)($m['position_name']??''));$assignment=trim((string)($m['assignment_name']??''));
          $showAssignment=$assignment!==''&&$norm($assignment)!==$norm($position);
          $memberNps=$m['nps_value']??null;$memberNpsClass=$memberNps===null?'neutral':((int)$memberNps>0?'positive':((int)$memberNps<0?'negative':'neutral'));
        ?>
          <tr>
            <td data-label="Integrante" class="support-member-name"><strong><?= htmlspecialchars($m['full_name']) ?></strong><small><?= htmlspecialchars($m['role_name']) ?><?= $position!==''?' · '.htmlspecialchars($position):'' ?></small><small><?= htmlspecialchars($m['email']) ?><?= $showAssignment?' · '.htmlspecialchars($assignment):'' ?></small></td>
            <td data-label="Activos" class="data-table-number"><?= (int)$m['active_cases'] ?></td>
            <td data-label="En proceso" class="data-table-number"><?= (int)$m['in_progress'] ?></td>
            <td data-label="En espera" class="data-table-number"><?= (int)$m['pending_cases'] ?></td>
            <td data-label="Resueltos 30 días" class="data-table-number"><?= (int)$m['resolved_30'] ?></td>
            <td data-label="Primera respuesta"><?= htmlspecialchars($fmtMin($m['avg_first_response_min'])) ?></td>
            <td data-label="Resolución promedio"><?= htmlspecialchars($fmtHours($m['avg_resolution_hours'])) ?></td>
            <td data-label="NPS"><strong class="support-nps-value <?= $memberNpsClass ?>"><?= htmlspecialchars($fmtNps($memberNps)) ?></strong><small><?= (int)$m['nps_responses'] ?> respuesta(s)<?= $m['avg_rating']!==null?' · '.htmlspecialchars((string)round((float)$m['avg_rating'],1)).'/10':'' ?></small></td>
            <td data-label="Último acceso" class="data-table-nowrap"><?= !empty($m['last_login_at'])?htmlspecialchars(date('d/m/Y H:i',strtotime((string)$m['last_login_at']))):'Sin ingreso todavía' ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if(!$members): ?><tr><td class="data-table-empty" data-label="" colspan="9">No hay integrantes activos en soporte.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>
</div>
<?php require APP_ROOT.'/app/Views/shared/app_end.php'; ?>