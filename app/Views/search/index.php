<?php
$statusLabels=['NEW'=>'Nuevo','AVAILABLE'=>'Pendiente de atención','IN_PROGRESS'=>'En proceso','PENDING'=>'En espera','RESOLVED'=>'Resuelto','CLOSED'=>'Cerrado','REOPENED'=>'Reabierto','CANCELLED'=>'Cancelado'];
$priorityLabels=['LOW'=>'Baja','MEDIUM'=>'Media','HIGH'=>'Alta','CRITICAL'=>'Crítica'];
$knowledgeStatusLabels=['DRAFT'=>'Borrador','IN_REVIEW'=>'En revisión','PUBLISHED'=>'Publicado para soporte','ARCHIVED'=>'Archivado'];
$searchTerm=(string)($q??'');$helpTopics=$helpTopics??[];$pageTitle='Buscar';$pageSection='Buscar';$activeNav='search';$helpContext='search';
require APP_ROOT.'/app/Views/shared/app_start.php';
$total=count($tickets)+count($problems)+count($articles)+count($helpTopics);
$normalize=static function(string $value):string{$value=mb_strtolower(trim((string)preg_replace('/\s+/u',' ',$value)),'UTF-8');return trim((string)preg_replace('/[^\p{L}\p{N}]+/u',' ',$value));};
?>
<style nonce="<?= htmlspecialchars(CSP_NONCE) ?>">
.search-scopebar{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin:14px 0}.search-scopebar button{border:1px solid var(--border);background:var(--card);color:var(--ink);border-radius:999px;padding:8px 12px;font-weight:800;cursor:pointer}.search-scopebar button.active{background:var(--brand);border-color:var(--brand);color:#fff}.search-scopebar b{margin-left:5px}.search-result-section{margin-top:14px}.search-section-head{display:flex;align-items:center;justify-content:space-between;gap:12px}.search-section-head>span{display:inline-flex;min-width:30px;justify-content:center;padding:5px 8px;border-radius:999px;background:var(--info-bg);color:var(--brand);font-weight:850}.search-ticket-list{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(460px,100%),1fr));gap:12px}.search-ticket-card{display:block;border:1px solid var(--border);border-radius:14px;padding:15px 16px;background:var(--card);text-decoration:none;color:inherit;box-shadow:0 1px 3px rgba(16,24,40,.05)}.search-ticket-card:hover{border-color:color-mix(in srgb,var(--brand) 40%,var(--border) 60%)}.search-ticket-top{display:flex;justify-content:space-between;gap:10px;align-items:center}.search-ticket-top>span:first-child{font-size:11px;font-weight:850;color:var(--brand)}.search-ticket-card h3{margin:7px 0 5px;font-size:17px}.search-ticket-card p{margin:0;color:var(--muted);font-size:12.5px;line-height:1.5}.search-ticket-meta{display:flex;gap:8px 14px;flex-wrap:wrap;margin-top:11px;padding-top:10px;border-top:1px solid var(--border);font-size:11px;color:var(--muted)}.search-result-summary{display:flex;align-items:baseline;gap:7px;margin-top:12px}.search-result-summary strong{font-size:24px;color:var(--brand)}.search-empty-state{margin-top:8px}

</style>

<div class="global-search-page" data-global-search>
  <?php if($searchTerm===''): ?>
    <section class="search-empty-state"><span class="search-empty-state-icon" aria-hidden="true">⌕</span><div><strong>Buscar en Helpdesk</strong><p>Escribe arriba un ticket, nombre, correo, parque, área, NIT, sistema, problema o solución.</p></div></section>
  <?php elseif($total===0): ?>
    <section class="search-empty-state"><span class="search-empty-state-icon" aria-hidden="true">⌕</span><div><strong>Sin coincidencias para “<?= htmlspecialchars($searchTerm) ?>”.</strong><p>Prueba con otra palabra o una parte del nombre, ticket, parque, sistema o solución.</p></div></section>
  <?php else: ?>
    <div class="search-result-summary"><strong><?= $total ?></strong><span>resultado<?= $total===1?'':'s' ?></span></div>
    <div class="search-scopebar" role="group" aria-label="Filtrar tipo de resultado">
      <button type="button" class="active" data-search-scope="all">Todo <b><?= $total ?></b></button>
      <button type="button" data-search-scope="tickets">Tickets <b><?= count($tickets) ?></b></button>
      <?php if($helpTopics): ?><button type="button" data-search-scope="help">Ayuda <b><?= count($helpTopics) ?></b></button><?php endif; ?>
      <?php if($problems): ?><button type="button" data-search-scope="problems">Problemas <b><?= count($problems) ?></b></button><?php endif; ?>
      <?php if($articles): ?><button type="button" data-search-scope="articles">Conocimiento <b><?= count($articles) ?></b></button><?php endif; ?>
    </div>

    <?php if($helpTopics): ?>
      <section class="search-result-section" data-search-section="help">
        <div class="search-section-head"><div><span class="ticket-kicker">Catálogo de ayuda</span><h2>Opciones relacionadas</h2></div><span><?= count($helpTopics) ?></span></div>
        <div class="search-knowledge-grid">
          <?php foreach($helpTopics as $topic): ?>
            <a class="card search-knowledge-card itsm-result-link" href="<?= APP_BASE_URL ?>/crear-ticket?topic=<?= rawurlencode((string)$topic['key']) ?>">
              <div class="card-body">
                <div class="search-knowledge-kicker"><strong><?= htmlspecialchars((string)$topic['group']) ?></strong><span>Solicitar ayuda</span></div>
                <h3><?= htmlspecialchars((string)$topic['label']) ?></h3>
                <p><?= htmlspecialchars((string)$topic['help']) ?></p>
                <small><?= htmlspecialchars((string)$topic['category_code']) ?></small>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endif; ?>

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
        <div class="search-knowledge-grid"><?php foreach($articles as $a): ?><a class="card search-knowledge-card itsm-result-link" href="<?= APP_BASE_URL ?>/knowledge/view?id=<?= (int)$a['id'] ?>"><div class="card-body"><div class="search-knowledge-kicker"><strong><?= htmlspecialchars($a['article_number']) ?></strong><span><?= htmlspecialchars($knowledgeStatusLabels[$a['status']]??$a['status']) ?></span></div><h3><?= htmlspecialchars($a['title']) ?></h3><?php if(!empty($a['summary'])): ?><p><?= htmlspecialchars(mb_strimwidth(trim((string)$a['summary']),0,240,'…')) ?></p><?php endif; ?><div class="search-answer"><span>Contenido</span><p><?= nl2br(htmlspecialchars(mb_strimwidth(trim(strip_tags((string)$a['content'])),0,420,'…'))) ?></p></div><small><?= htmlspecialchars($a['category_name']??'Sin categoría') ?><?= !empty($a['public_available'])?' · Disponible para solicitantes':'' ?></small></div></a><?php endforeach; ?></div>
      </section>
    <?php endif; ?>
  <?php endif; ?>
</div>

<script nonce="<?= htmlspecialchars(CSP_NONCE) ?>">
(()=>{const root=document.querySelector('[data-global-search]');if(!root)return;const buttons=[...root.querySelectorAll('[data-search-scope]')];const sections=[...root.querySelectorAll('[data-search-section]')];buttons.forEach(btn=>btn.addEventListener('click',()=>{const scope=btn.dataset.searchScope||'all';buttons.forEach(x=>x.classList.toggle('active',x===btn));sections.forEach(section=>{section.hidden=scope!=='all'&&section.dataset.searchSection!==scope;});}));})();
</script>
<?php require APP_ROOT.'/app/Views/shared/app_end.php'; ?>