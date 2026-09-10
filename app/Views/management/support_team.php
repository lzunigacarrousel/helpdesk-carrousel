<?php
$pageTitle='Equipo de soporte';$pageSection='Equipo de soporte';$activeNav='support-team';$helpContext='reports';
require APP_ROOT.'/app/Views/shared/app_start.php';
$summary=$summary??[];$members=$members??[];
$fmtMin=static function($value):string{if($value===null||$value==='')return '—';$m=(int)round((float)$value);return $m<60?$m.' min':round($m/60,1).' h';};
$fmtHours=static fn($value):string=>$value===null||$value===''?'—':round((float)$value,1).' h';
?>
<style nonce="<?= htmlspecialchars(CSP_NONCE) ?>">
.support-team-page{max-width:1500px;margin:0 auto}.support-team-summary{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:12px;margin:0 0 16px}.support-team-summary article,.support-member{background:var(--card);border:1px solid var(--border);border-radius:16px}.support-team-summary article{padding:15px 16px}.support-team-summary span{display:block;color:var(--muted);font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.04em}.support-team-summary strong{display:block;margin-top:5px;font-size:25px;color:var(--brand-dark)}.support-team-list{display:grid;gap:12px}.support-member{padding:16px 18px;display:grid;grid-template-columns:minmax(240px,1.4fr) repeat(5,minmax(100px,.7fr));gap:14px;align-items:center}.support-member-main strong{display:block;font-size:16px}.support-member-main span,.support-member-main small{display:block;color:var(--muted);margin-top:3px}.support-member-metric span{display:block;color:var(--muted);font-size:11px}.support-member-metric strong{display:block;margin-top:3px;font-size:18px}.support-member-load{display:flex;gap:6px;flex-wrap:wrap;margin-top:8px}.support-member-load span{padding:4px 8px;border:1px solid var(--border);border-radius:999px;font-size:11px;color:var(--text)}.support-team-head{margin-bottom:16px}.support-team-head .mgmt-head-actions{display:flex;gap:8px;flex-wrap:wrap}@media(max-width:1100px){.support-team-summary{grid-template-columns:repeat(3,1fr)}.support-member{grid-template-columns:1fr 1fr 1fr}.support-member-main{grid-column:1/-1}}@media(max-width:700px){.support-team-summary{grid-template-columns:1fr 1fr}.support-member{grid-template-columns:1fr 1fr}.support-member-main{grid-column:1/-1}.support-team-head{align-items:stretch}.support-team-head .mgmt-head-actions{width:100%}.support-team-head .btn{flex:1}}
</style>
<div class="support-team-page">
  <div class="mgmt-head support-team-head">
    <div><span class="mgmt-kicker">Operación interna</span><h1>Equipo de soporte</h1><p>Quién atiende, qué carga tiene y cómo está respondiendo el equipo.</p></div>
    <div class="mgmt-head-actions"><a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/gestion/informes">Informes</a><a class="btn btn-primary" href="<?= APP_BASE_URL ?>/gestion/equipo/exportar" data-action-message="Preparando archivo Excel…">Descargar Excel (.xlsx)</a></div>
  </div>

  <section class="support-team-summary" aria-label="Resumen del equipo">
    <article><span>Integrantes</span><strong><?= (int)($summary['members']??0) ?></strong></article>
    <article><span>Casos activos</span><strong><?= (int)($summary['active_cases']??0) ?></strong></article>
    <article><span>En proceso</span><strong><?= (int)($summary['in_progress']??0) ?></strong></article>
    <article><span>En espera</span><strong><?= (int)($summary['pending_cases']??0) ?></strong></article>
    <article><span>Resueltos 30 días</span><strong><?= (int)($summary['resolved_30']??0) ?></strong></article>
    <article><span>Calificación</span><strong><?= $summary['avg_nps']!==null?htmlspecialchars((string)$summary['avg_nps']).'/10':'—' ?></strong></article>
  </section>

  <section class="support-team-list" aria-label="Integrantes de soporte">
    <?php foreach($members as $m): ?>
      <article class="support-member">
        <div class="support-member-main"><strong><?= htmlspecialchars($m['full_name']) ?></strong><span><?= htmlspecialchars($m['role_name']) ?><?= !empty($m['position_name'])?' · '.htmlspecialchars($m['position_name']):'' ?></span><small><?= htmlspecialchars($m['email']) ?><?= !empty($m['assignment_name'])?' · '.htmlspecialchars($m['assignment_name']):'' ?></small><div class="support-member-load"><span><?= (int)$m['active_cases'] ?> activos</span><span><?= (int)$m['in_progress'] ?> en proceso</span><span><?= (int)$m['pending_cases'] ?> en espera</span></div></div>
        <div class="support-member-metric"><span>Resueltos 30 días</span><strong><?= (int)$m['resolved_30'] ?></strong></div>
        <div class="support-member-metric"><span>Primera respuesta</span><strong><?= htmlspecialchars($fmtMin($m['avg_first_response_min'])) ?></strong></div>
        <div class="support-member-metric"><span>Resolución promedio</span><strong><?= htmlspecialchars($fmtHours($m['avg_resolution_hours'])) ?></strong></div>
        <div class="support-member-metric"><span>Satisfacción</span><strong><?= $m['avg_nps']!==null?htmlspecialchars((string)round((float)$m['avg_nps'],1)).'/10':'—' ?></strong></div>
        <div class="support-member-metric"><span>Último acceso</span><strong style="font-size:13px"><?= !empty($m['last_login_at'])?htmlspecialchars(date('d/m/Y H:i',strtotime((string)$m['last_login_at']))):'Nunca' ?></strong></div>
      </article>
    <?php endforeach; ?>
    <?php if(!$members): ?><div class="card"><div class="empty-state"><strong>No hay integrantes activos en soporte.</strong></div></div><?php endif; ?>
  </section>
</div>
<?php require APP_ROOT.'/app/Views/shared/app_end.php'; ?>
