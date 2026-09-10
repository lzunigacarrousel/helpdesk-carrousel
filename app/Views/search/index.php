<?php
$statusLabels=['NEW'=>'Nuevo','AVAILABLE'=>'Pendiente de atención','IN_PROGRESS'=>'En proceso','PENDING'=>'En espera','RESOLVED'=>'Resuelto','CLOSED'=>'Cerrado','REOPENED'=>'Reabierto','CANCELLED'=>'Cancelado'];
$priorityLabels=['LOW'=>'Baja','MEDIUM'=>'Media','HIGH'=>'Alta','CRITICAL'=>'Crítica'];
$searchTerm=(string)($q??'');$pageTitle='Buscar';$pageSection='Buscar';$activeNav='search';$helpContext='search';
require APP_ROOT.'/app/Views/shared/app_start.php';
$total=count($tickets)+count($problems)+count($articles);
$normalize=static function(string $value):string{$value=mb_strtolower(trim((string)preg_replace('/\s+/u',' ',$value)),'UTF-8');return trim((string)preg_replace('/[^\p{L}\p{N}]+/u',' ',$value));};
?>
<style nonce="<?= htmlspecialchars(CSP_NONCE) ?>">
.search-command{border:1px solid color-mix(in srgb,var(--brand) 34%,var(--border) 66%);border-left:5px solid var(--brand);background:linear-gradient(135deg,color-mix(in srgb,var(--brand) 6%,var(--card) 94%),var(--card));border-radius:16px;padding:20px;margin-bottom:16px}.search-command-head{display:flex;justify-content:space-between;gap:18px;align-items:flex-start;margin-bottom:14px}.search-command-head h1{margin:3px 0 5px}.search-page-form{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:10px}.search-page-form input{font-size:15px}.search-hints{display:flex;gap:7px;flex-wrap:wrap;margin-top:10px}.search-hints span{display:inline-flex;padding:5px 8px;border:1px solid var(--border);border-radius:999px;background:var(--card);font-size:10.5px;color:var(--muted)}.search-scopebar{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin:14px 0}.search-scopebar button{border:1px solid var(--border);background:var(--card);color:var(--ink);border-radius:999px;padding:8px 12px;font-weight:800;cursor:pointer}.search-scopebar button.active{background:var(--brand);border-color:var(--brand);color:#fff}.search-scopebar b{margin-left:5px}.search-result-section{margin-top:14px}.search-section-head{display:flex;align-items:center;justify-content:space-between;gap:12px}.search-section-head>span{display:inline-flex;min-width:30px;justify-content:center;padding:5px 8px;border-radius:999px;background:var(--info-bg);color:var(--brand);font-weight:850}.search-ticket-list{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(460px,100%),1fr));gap:12px}.search-ticket-card{display:block;border:1px solid var(--border);border-radius:14px;padding:15px 16px;background:var(--card);text-decoration:none;color:inherit;box-shadow:0 1px 3px rgba(16,24,40,.05)}.search-ticket-card:hover{border-color:color-mix(in srgb,var(--brand) 40%,var(--border) 60%)}.search-ticket-top{display:flex;justify-content:space-between;gap:10px;align-items:center}.search-ticket-top>span:first-child{font-size:11px;font-weight:850;color:var(--brand)}.search-ticket-card h3{margin:7px 0 5px;font-size:17px}.search-ticket-card p{margin:0;color:var(--muted);font-size:12.5px;line-height:1.5}.search-ticket-meta{display:flex;gap:8px 14px;flex-wrap:wrap;margin-top:11px;padding-top:10px;border-top:1px solid var(--border);font-size:11px;color:var(--muted)}.search-result-summary{display:flex;align-items:baseline;gap:7px;margin-top:12px}.search-result-summary strong{font-size:24px;color:var(--brand)}.search-empty-card{margin-top:14px}.search-empty-card p{margin:0;color:var(--muted)}
@media(max-width:700px){.search-page-form{grid-template-columns:1fr}.search-command{padding:16px}.search-command-head{display:block}.search-page-form .btn{width:100%}}
</style>

<div class="global-search-page" data-global-search>
  <section class="search-command">
    <div class="search-command-head"><div><span class="ticket-kicker">Búsqueda global</span><h1 class="page-title">Buscar en Helpdesk</h1></div></div>
    <form class="search-page-form" method="get" action="<?= APP_BASE_URL ?>/buscar" data-no-loading="1">
      <input class="form-control" type="search" name="q" value="<?= htmlspecialchars($searchTerm) ?>" placeholder="Número de caso, persona, correo, parque, problema o solución…" autocomplete="off" autofocus>
      <button class="btn btn-primary" type="submit">Buscar</button>
    </form>
    <div class="search-hints" aria-label="Ejemplos de búsqueda"><span>HD-2026-000001</span><span>Nombre o correo</span><span>Parque / área</span><span>Causa o solución</span></div>
  </section>

  <?php if($searchTerm===''): ?>
    <section class="card search-empty-card"><div class="card-body"><p>Busca por número, persona, ubicación o texto del caso.</p></div></section>
  <?php elseif($total===0): ?>
    <section class="card search-empty-card"><div class="card-body"><strong>Sin coincidencias para “<?= htmlspecialchars($searchTerm) ?>”.</strong></div></section>
  <?php else: ?>
    <div class="search-result-summary"><strong><?= $total ?></strong><span>resultado<?= $total===1?'':'s' ?></span></div>
    <div class="search-scopebar" role="group" aria-label="Filtrar tipo de resultado">
      <button type="button" class="active" data-search-scope="all">Todo <b><?= $total ?></b></button>
      <button type="button" data-search-scope="tickets">Tickets <b><?= count($tickets) ?></b></button>
      <?php if($problems): ?><button type="button" data-search-scope="problems">Problemas <b><?= count($problems) ?></b></button><?php endif; ?>
      <?php if($articles): ?><button type="button" data-search-scope="articles">Conocimiento <b><?= count($articles) ?></b></button><?php endif; ?>
    </div>

    <?php if($tickets): ?>
      <section class="search-result-section" data-search-section="tickets">
        <div class="search-section-head"><div><span class="ticket-kicker">Tickets</span><h2>Casos relacionados</h2></div><span><?= count($tickets) ?></span></div>
        <div class="search-ticket-list">
          <?php foreach($tickets as $t): $description=trim((string)$t['description']);$showDescription=$description!==''&&$normalize($description)!==$normalize((string)$t['subject']); ?>
            <a class="search-ticket-card" href="<?= APP_BASE_URL ?>/tickets/view?id=<?= (int)$t['id'] ?>">
              <div class="search-ticket-top"><span><?= htmlspecialchars($t['ticket_number']) ?></span><span class="ticket-status-pill status-<?= strtolower((string)$t['status']) ?>"><?= htmlspecialchars($statusLabels[$t['status']]??$t['status']) ?></span></div>
              <h3><?= htmlspecialchars($t['subject']) ?></h3>
              <?php if($showDescription): ?><p><?= htmlspecialchars(mb_strimwidth($description,0,220,'…')) ?></p><?php endif; ?>
              <div class="search-ticket-meta">
                <span><?= htmlspecialchars($t['requester_name']??'Sin solicitante') ?></span>
                <span><?= htmlspecialchars($t['park_name']??'Ubicación no especificada') ?><?= !empty($t['area_name'])?' · '.htmlspecialchars($t['area_name']):'' ?></span>
                <span><?= htmlspecialchars($t['category_name']??'Sin categoría') ?></span>
                <span><?= htmlspecialchars($priorityLabels[$t['priority']]??$t['priority']) ?></span>
                <span><?= htmlspecialchars($t['assigned_name']??'Sin responsable') ?></span>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endif; ?>

    <?php if($problems): ?>
      <section class="search-result-section" data-search-section="problems">
        <div class="search-section-head"><div><span class="ticket-kicker">Problemas conocidos</span><h2>Patrones identificados</h2></div><span><?= count($problems) ?></span></div>
        <div class="search-knowledge-grid"><?php foreach($problems as $p): ?><a class="card search-knowledge-card itsm-result-link" href="<?= APP_BASE_URL ?>/problems/view?id=<?= (int)$p['id'] ?>"><div class="card-body"><div class="search-knowledge-kicker"><strong><?= htmlspecialchars($p['problem_number']) ?></strong><span><?= htmlspecialchars($p['status']) ?></span></div><h3><?= htmlspecialchars($p['title']) ?></h3><p><?= htmlspecialchars(mb_strimwidth(trim((string)$p['description']),0,220,'…')) ?></p><?php if(!empty($p['workaround'])): ?><div class="search-answer"><span>Solución temporal</span><p><?= htmlspecialchars(mb_strimwidth(trim((string)$p['workaround']),0,260,'…')) ?></p></div><?php endif; ?><small><?= htmlspecialchars($p['park_name']??'Todos los parques') ?> · <?= htmlspecialchars($p['category_name']??'Sin categoría') ?> · <?= (int)$p['occurrence_count'] ?> ocurrencia(s)</small></div></a><?php endforeach; ?></div>
      </section>
    <?php endif; ?>

    <?php if($articles): ?>
      <section class="search-result-section" data-search-section="articles">
        <div class="search-section-head"><div><span class="ticket-kicker">Conocimiento</span><h2>Documentación relacionada</h2></div><span><?= count($articles) ?></span></div>
        <div class="search-knowledge-grid"><?php foreach($articles as $a): ?><a class="card search-knowledge-card itsm-result-link" href="<?= APP_BASE_URL ?>/knowledge/view?id=<?= (int)$a['id'] ?>"><div class="card-body"><div class="search-knowledge-kicker"><strong><?= htmlspecialchars($a['article_number']) ?></strong><span><?= htmlspecialchars($a['status']) ?></span></div><h3><?= htmlspecialchars($a['title']) ?></h3><?php if(!empty($a['summary'])): ?><p><?= htmlspecialchars(mb_strimwidth(trim((string)$a['summary']),0,240,'…')) ?></p><?php endif; ?><div class="search-answer"><span>Contenido</span><p><?= nl2br(htmlspecialchars(mb_strimwidth(trim(strip_tags((string)$a['content'])),0,420,'…'))) ?></p></div><small><?= htmlspecialchars($a['category_name']??'Sin categoría') ?> · <?= htmlspecialchars($a['visibility']) ?></small></div></a><?php endforeach; ?></div>
      </section>
    <?php endif; ?>
  <?php endif; ?>
</div>

<script nonce="<?= htmlspecialchars(CSP_NONCE) ?>">
(()=>{const root=document.querySelector('[data-global-search]');if(!root)return;const buttons=[...root.querySelectorAll('[data-search-scope]')];const sections=[...root.querySelectorAll('[data-search-section]')];buttons.forEach(btn=>btn.addEventListener('click',()=>{const scope=btn.dataset.searchScope||'all';buttons.forEach(x=>x.classList.toggle('active',x===btn));sections.forEach(section=>{section.hidden=scope!=='all'&&section.dataset.searchSection!==scope;});}));})();
</script>
<?php require APP_ROOT.'/app/Views/shared/app_end.php'; ?>