<?php
declare(strict_types=1);
use App\Core\Csrf;
$pageTitle='Catálogos';$pageSection='Administración';$activeNav='catalogs';$helpContext='catalogs';
$section=$section??'home';
require APP_ROOT.'/app/Views/shared/app_start.php';
?>
<style nonce="<?= htmlspecialchars(CSP_NONCE) ?>">
.catalog-page{display:grid;gap:16px}
.catalog-heading{display:flex;align-items:center;justify-content:space-between;gap:14px}
.catalog-heading-copy{display:grid;gap:3px}
.catalog-heading-copy h1{margin:0}
.catalog-heading-copy p{margin:0;color:var(--muted)}
.catalog-home-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}
.catalog-home-card{display:flex;flex-direction:column;min-height:190px;padding:18px;border:1px solid var(--border);border-radius:14px;background:var(--card);box-shadow:var(--shadow)}
.catalog-home-icon{width:42px;height:42px;display:grid;place-items:center;border-radius:11px;background:color-mix(in srgb,var(--brand) 10%,var(--card) 90%);color:var(--brand);font-size:20px;font-weight:900}
.catalog-home-card h2{margin:13px 0 4px;font-size:18px}
.catalog-home-card p{margin:0;color:var(--muted);font-size:12px;line-height:1.5}
.catalog-home-count{display:flex;gap:6px;align-items:baseline;margin:15px 0}
.catalog-home-count strong{font-size:26px;color:var(--brand)}
.catalog-home-count span{font-size:11px;color:var(--muted)}
.catalog-home-card .btn{margin-top:auto;align-self:flex-start}
.catalog-subnav{display:flex;gap:7px;flex-wrap:wrap}
.catalog-subnav .btn{min-height:38px;padding:8px 12px}
.catalog-subnav .is-active{background:var(--brand);border-color:var(--brand);color:#fff}
.catalog-section{overflow:hidden}
.catalog-section-title{display:grid;gap:2px}
.catalog-section-title h2{margin:0;font-size:17px}
.catalog-section-title small{color:var(--muted);font-size:11px;font-weight:500}
.catalog-toolbar{display:flex;gap:8px;align-items:end;flex-wrap:wrap}
.catalog-toolbar label{margin:0}
.catalog-toolbar .catalog-search{min-width:280px}
.catalog-toolbar .catalog-filter{min-width:130px}
.catalog-create{padding:13px 14px;border-bottom:1px solid var(--border);background:color-mix(in srgb,var(--card) 97%,var(--bg) 3%)}
.catalog-create form{display:grid;gap:10px;align-items:end}
.catalog-create.region form,.catalog-create.area form{grid-template-columns:minmax(220px,1fr) minmax(260px,2fr) auto}
.catalog-create.park form{grid-template-columns:minmax(180px,1fr) minmax(220px,1.4fr) minmax(130px,.7fr) minmax(240px,1.6fr) auto}
.catalog-create .field-help{grid-column:1/-1}
.catalog-code{font-family:ui-monospace,SFMono-Regular,Consolas,monospace;font-size:10.5px}
.catalog-risk{color:var(--warning);font-size:10.5px}
.catalog-dialog-context{padding:10px 20px;border-bottom:1px solid var(--border);background:color-mix(in srgb,var(--brand) 4%,var(--card) 96%);color:var(--muted);font-size:11px}
.catalog-dialog-form{display:grid;gap:14px;padding:18px 20px 20px}
.catalog-dialog-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
.catalog-dialog-full{grid-column:1/-1}
.catalog-dialog-actions{display:flex;justify-content:flex-end;gap:8px;padding-top:4px}
.catalog-empty{padding:22px;text-align:center;color:var(--muted)}
[data-catalog-row][hidden]{display:none!important}
@media(max-width:900px){
  .catalog-home-grid{grid-template-columns:1fr}
  .catalog-heading{align-items:stretch;flex-direction:column}
  .catalog-subnav{width:100%}
  .catalog-subnav .btn{flex:1}
  .data-table-toolbar{align-items:stretch}
  .catalog-toolbar{width:100%}
  .catalog-toolbar label{flex:1 1 180px}
  .catalog-toolbar .catalog-search,.catalog-toolbar .catalog-filter{min-width:0;width:100%}
  .catalog-create form,.catalog-create.region form,.catalog-create.area form,.catalog-create.park form{grid-template-columns:1fr}
  .catalog-dialog-grid{grid-template-columns:1fr}
  .catalog-dialog-full{grid-column:auto}
  .catalog-dialog-actions{display:grid;grid-template-columns:1fr}
  .catalog-dialog-actions .btn{width:100%}
}
</style>

<div class="catalog-page">
  <div class="catalog-heading">
    <div class="catalog-heading-copy">
      <span class="ticket-kicker">Administración</span>
      <h1 class="page-title"><?= $section==='home'?'Catálogos organizacionales':htmlspecialchars(match($section){'regions'=>'Regiones','parks'=>'Parques','areas'=>'Áreas',default=>'Catálogos'}) ?></h1>
      <p><?= $section==='home'?'Administra cada catálogo por separado. Los cambios conservan el historial y las relaciones existentes.':'Trabaja únicamente con este catálogo. Puedes volver al panel general cuando termines.' ?></p>
    </div>
    <?php if($section!=='home'): ?><a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/admin/catalogos">← Catálogos</a><?php endif; ?>
  </div>

  <?php if(!empty($flash)): ?><div class="alert alert-<?= htmlspecialchars((string)($flash['type']??'info')) ?>"><?= htmlspecialchars((string)($flash['message']??'')) ?></div><?php endif; ?>

  <?php if($section==='home'): ?>
    <div class="catalog-home-grid" data-catalog-home>
      <article class="catalog-home-card">
        <div class="catalog-home-icon">R</div>
        <h2>Regiones</h2>
        <p>Agrupa parques por zona operativa y mantiene las relaciones regionales.</p>
        <div class="catalog-home-count"><strong><?= (int)($summary['regions_active']??0) ?></strong><span>activas de <?= (int)($summary['regions_total']??0) ?></span></div>
        <a class="btn btn-primary" href="<?= APP_BASE_URL ?>/admin/catalogos/regiones">Administrar regiones</a>
      </article>
      <article class="catalog-home-card">
        <div class="catalog-home-icon">P</div>
        <h2>Parques</h2>
        <p>Administra ubicaciones, región, centro de costo, dirección y disponibilidad.</p>
        <div class="catalog-home-count"><strong><?= (int)($summary['parks_active']??0) ?></strong><span>activos de <?= (int)($summary['parks_total']??0) ?></span></div>
        <a class="btn btn-primary" href="<?= APP_BASE_URL ?>/admin/catalogos/parques">Administrar parques</a>
      </article>
      <article class="catalog-home-card">
        <div class="catalog-home-icon">A</div>
        <h2>Áreas</h2>
        <p>Administra las áreas corporativas utilizadas por usuarios y tickets.</p>
        <div class="catalog-home-count"><strong><?= (int)($summary['areas_active']??0) ?></strong><span>activas de <?= (int)($summary['areas_total']??0) ?></span></div>
        <a class="btn btn-primary" href="<?= APP_BASE_URL ?>/admin/catalogos/areas">Administrar áreas</a>
      </article>
    </div>
  <?php else: ?>
    <nav class="catalog-subnav" aria-label="Catálogos organizacionales">
      <a class="btn btn-outline-secondary <?= $section==='regions'?'is-active':'' ?>" href="<?= APP_BASE_URL ?>/admin/catalogos/regiones">Regiones</a>
      <a class="btn btn-outline-secondary <?= $section==='parks'?'is-active':'' ?>" href="<?= APP_BASE_URL ?>/admin/catalogos/parques">Parques</a>
      <a class="btn btn-outline-secondary <?= $section==='areas'?'is-active':'' ?>" href="<?= APP_BASE_URL ?>/admin/catalogos/areas">Áreas</a>
    </nav>
  <?php endif; ?>

  <?php if($section==='regions'): ?>
  <section class="catalog-section data-table-shell" data-catalog-section>
    <div class="data-table-toolbar"><div class="catalog-section-title"><span class="ticket-kicker">Regiones</span><h2>Listado de regiones</h2><small>Organiza los parques por región.</small></div><div class="catalog-toolbar"><label><span class="form-label">Buscar</span><input class="form-control catalog-search" type="search" placeholder="Nombre o código…" data-catalog-search></label><label><span class="form-label">Estado</span><select class="form-control catalog-filter" data-catalog-status><option value="">Todos</option><option value="1">Activos</option><option value="0">Inactivos</option></select></label></div></div>
    <div class="catalog-create region"><form method="post" action="<?= APP_BASE_URL ?>/admin/catalogos/regiones/guardar" data-single-submit><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><label><span class="form-label">Nueva región</span><input class="form-control" name="name" maxlength="120" required placeholder="Ej. Región Norte"></label><div class="field-help">El código interno se genera automáticamente y no afecta los registros existentes.</div><button class="btn btn-primary" type="submit">Agregar región</button></form></div>
    <div class="data-table-wrap"><table class="data-table catalog-table"><thead><tr><th>Región</th><th>Código</th><th>Parques</th><th>Asignaciones</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
    <?php foreach($regions as $r): $rid=(int)$r['id']; ?>
      <tr data-catalog-row data-search="<?= htmlspecialchars(mb_strtolower((string)$r['name'].' '.(string)$r['code']),ENT_QUOTES,'UTF-8') ?>" data-status="<?= (int)$r['is_active'] ?>">
        <td data-label="Región"><strong><?= htmlspecialchars((string)$r['name']) ?></strong></td>
        <td data-label="Código" class="data-table-secondary"><span class="catalog-code"><?= htmlspecialchars((string)$r['code']) ?></span></td>
        <td data-label="Parques"><?= (int)$r['active_parks'] ?> activos · <?= (int)$r['parks_total'] ?> total</td>
        <td data-label="Asignaciones"><?= (int)$r['active_assignments'] ?></td>
        <td data-label="Estado"><span class="badge <?= (int)$r['is_active']===1?'badge-success':'badge-secondary' ?>"><?= (int)$r['is_active']===1?'Activo':'Inactivo' ?></span></td>
        <td data-label="Acciones" class="data-table-actions"><button class="btn btn-outline-secondary btn-sm" type="button" data-catalog-edit data-kind="region" data-action="<?= APP_BASE_URL ?>/admin/catalogos/regiones/guardar" data-id="<?= $rid ?>" data-name="<?= htmlspecialchars((string)$r['name'],ENT_QUOTES,'UTF-8') ?>" data-code="<?= htmlspecialchars((string)$r['code'],ENT_QUOTES,'UTF-8') ?>">Editar</button><form method="post" action="<?= APP_BASE_URL ?>/admin/catalogos/regiones/estado" data-single-submit><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="id" value="<?= $rid ?>"><input type="hidden" name="activate" value="<?= (int)$r['is_active']===1?'0':'1' ?>"><button class="btn btn-outline-secondary btn-sm" type="submit"><?= (int)$r['is_active']===1?'Desactivar':'Reactivar' ?></button></form></td>
      </tr>
    <?php endforeach; ?>
    </tbody></table><div class="catalog-empty" data-catalog-empty hidden>No hay regiones que coincidan con esos filtros.</div></div>
  </section>
  <?php endif; ?>

  <?php if($section==='parks'): ?>
  <section class="catalog-section data-table-shell" data-catalog-section>
    <div class="data-table-toolbar"><div class="catalog-section-title"><span class="ticket-kicker">Parques</span><h2>Listado de parques</h2><small>Ubicaciones operativas disponibles en tickets y usuarios.</small></div><div class="catalog-toolbar"><label><span class="form-label">Buscar</span><input class="form-control catalog-search" type="search" placeholder="Parque, región o centro de costo…" data-catalog-search></label><label><span class="form-label">Estado</span><select class="form-control catalog-filter" data-catalog-status><option value="">Todos</option><option value="1">Activos</option><option value="0">Inactivos</option></select></label></div></div>
    <div class="catalog-create park"><form method="post" action="<?= APP_BASE_URL ?>/admin/catalogos/parques/guardar" data-single-submit><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><label><span class="form-label">Región</span><select class="form-control" name="region_id" required><option value="">Selecciona</option><?php foreach($regions as $r): if((int)$r['is_active']!==1)continue; ?><option value="<?= (int)$r['id'] ?>"><?= htmlspecialchars((string)$r['name']) ?></option><?php endforeach; ?></select></label><label><span class="form-label">Nuevo parque</span><input class="form-control" name="name" maxlength="160" required placeholder="Nombre del parque"></label><label><span class="form-label">Centro de costo <span class="optional">Opcional</span></span><input class="form-control" name="cost_center" maxlength="50" placeholder="Ej. 071"></label><label><span class="form-label">Dirección <span class="optional">Opcional</span></span><input class="form-control" name="address" maxlength="255" placeholder="Centro comercial / dirección"></label><button class="btn btn-primary" type="submit">Agregar parque</button></form></div>
    <div class="data-table-wrap"><table class="data-table catalog-table"><thead><tr><th>Parque</th><th>Región</th><th>Centro costo</th><th>Uso activo</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
    <?php foreach($parks as $p): $pid=(int)$p['id']; $usage=(int)$p['active_assignments']+(int)$p['open_tickets']+(int)$p['active_activities']; ?>
      <tr data-catalog-row data-search="<?= htmlspecialchars(mb_strtolower(implode(' ',[(string)$p['name'],(string)$p['code'],(string)$p['region_name'],(string)$p['cost_center'],(string)$p['address']])),ENT_QUOTES,'UTF-8') ?>" data-status="<?= (int)$p['is_active'] ?>">
        <td data-label="Parque"><strong><?= htmlspecialchars((string)$p['name']) ?></strong><small class="catalog-code"><?= htmlspecialchars((string)$p['code']) ?></small></td>
        <td data-label="Región"><?= htmlspecialchars((string)($p['region_name']??'Sin región')) ?></td>
        <td data-label="Centro costo" class="data-table-secondary"><?= htmlspecialchars((string)($p['cost_center']??'—')) ?></td>
        <td data-label="Uso activo"><?= (int)$p['active_assignments'] ?> asign. · <?= (int)$p['open_tickets'] ?> tickets · <?= (int)$p['active_activities'] ?> activ.<?php if($usage>0): ?><small class="catalog-risk">Debe quedar sin uso activo antes de desactivar.</small><?php endif; ?></td>
        <td data-label="Estado"><span class="badge <?= (int)$p['is_active']===1?'badge-success':'badge-secondary' ?>"><?= (int)$p['is_active']===1?'Activo':'Inactivo' ?></span></td>
        <td data-label="Acciones" class="data-table-actions"><button class="btn btn-outline-secondary btn-sm" type="button" data-catalog-edit data-kind="park" data-action="<?= APP_BASE_URL ?>/admin/catalogos/parques/guardar" data-id="<?= $pid ?>" data-name="<?= htmlspecialchars((string)$p['name'],ENT_QUOTES,'UTF-8') ?>" data-code="<?= htmlspecialchars((string)$p['code'],ENT_QUOTES,'UTF-8') ?>" data-region-id="<?= (int)$p['region_id'] ?>" data-cost-center="<?= htmlspecialchars((string)($p['cost_center']??''),ENT_QUOTES,'UTF-8') ?>" data-address="<?= htmlspecialchars((string)($p['address']??''),ENT_QUOTES,'UTF-8') ?>">Editar</button><form method="post" action="<?= APP_BASE_URL ?>/admin/catalogos/parques/estado" data-single-submit><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="id" value="<?= $pid ?>"><input type="hidden" name="activate" value="<?= (int)$p['is_active']===1?'0':'1' ?>"><button class="btn btn-outline-secondary btn-sm" type="submit"><?= (int)$p['is_active']===1?'Desactivar':'Reactivar' ?></button></form></td>
      </tr>
    <?php endforeach; ?>
    </tbody></table><div class="catalog-empty" data-catalog-empty hidden>No hay parques que coincidan con esos filtros.</div></div>
  </section>
  <?php endif; ?>

  <?php if($section==='areas'): ?>
  <section class="catalog-section data-table-shell" data-catalog-section>
    <div class="data-table-toolbar"><div class="catalog-section-title"><span class="ticket-kicker">Áreas</span><h2>Listado de áreas</h2><small>Áreas corporativas utilizadas en asignaciones y tickets.</small></div><div class="catalog-toolbar"><label><span class="form-label">Buscar</span><input class="form-control catalog-search" type="search" placeholder="Nombre, código o descripción…" data-catalog-search></label><label><span class="form-label">Estado</span><select class="form-control catalog-filter" data-catalog-status><option value="">Todos</option><option value="1">Activos</option><option value="0">Inactivos</option></select></label></div></div>
    <div class="catalog-create area"><form method="post" action="<?= APP_BASE_URL ?>/admin/catalogos/areas/guardar" data-single-submit><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><label><span class="form-label">Nueva área</span><input class="form-control" name="name" maxlength="120" required placeholder="Ej. Tecnología"></label><label><span class="form-label">Descripción <span class="optional">Opcional</span></span><input class="form-control" name="description" maxlength="255" placeholder="Descripción breve"></label><button class="btn btn-primary" type="submit">Agregar área</button></form></div>
    <div class="data-table-wrap"><table class="data-table catalog-table"><thead><tr><th>Área</th><th>Código</th><th>Descripción</th><th>Uso activo</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
    <?php foreach($areas as $a): $aid=(int)$a['id']; $usage=(int)$a['active_assignments']+(int)$a['open_tickets']; ?>
      <tr data-catalog-row data-search="<?= htmlspecialchars(mb_strtolower((string)$a['name'].' '.(string)$a['code'].' '.(string)$a['description']),ENT_QUOTES,'UTF-8') ?>" data-status="<?= (int)$a['is_active'] ?>">
        <td data-label="Área"><strong><?= htmlspecialchars((string)$a['name']) ?></strong></td>
        <td data-label="Código" class="data-table-secondary"><span class="catalog-code"><?= htmlspecialchars((string)$a['code']) ?></span></td>
        <td data-label="Descripción" class="data-table-secondary"><?= htmlspecialchars((string)($a['description']??'—')) ?></td>
        <td data-label="Uso activo"><?= (int)$a['active_assignments'] ?> asign. · <?= (int)$a['open_tickets'] ?> tickets<?php if($usage>0): ?><small class="catalog-risk">Debe quedar sin uso activo antes de desactivar.</small><?php endif; ?></td>
        <td data-label="Estado"><span class="badge <?= (int)$a['is_active']===1?'badge-success':'badge-secondary' ?>"><?= (int)$a['is_active']===1?'Activo':'Inactivo' ?></span></td>
        <td data-label="Acciones" class="data-table-actions"><button class="btn btn-outline-secondary btn-sm" type="button" data-catalog-edit data-kind="area" data-action="<?= APP_BASE_URL ?>/admin/catalogos/areas/guardar" data-id="<?= $aid ?>" data-name="<?= htmlspecialchars((string)$a['name'],ENT_QUOTES,'UTF-8') ?>" data-code="<?= htmlspecialchars((string)$a['code'],ENT_QUOTES,'UTF-8') ?>" data-description="<?= htmlspecialchars((string)($a['description']??''),ENT_QUOTES,'UTF-8') ?>">Editar</button><form method="post" action="<?= APP_BASE_URL ?>/admin/catalogos/areas/estado" data-single-submit><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="id" value="<?= $aid ?>"><input type="hidden" name="activate" value="<?= (int)$a['is_active']===1?'0':'1' ?>"><button class="btn btn-outline-secondary btn-sm" type="submit"><?= (int)$a['is_active']===1?'Desactivar':'Reactivar' ?></button></form></td>
      </tr>
    <?php endforeach; ?>
    </tbody></table><div class="catalog-empty" data-catalog-empty hidden>No hay áreas que coincidan con esos filtros.</div></div>
  </section>
  <?php endif; ?>

  <?php if($section!=='home'): ?>
  <dialog class="admin-record-dialog" data-admin-dialog data-catalog-dialog aria-labelledby="catalog-edit-title">
    <div class="admin-record-dialog-shell">
      <div class="admin-record-dialog-head">
        <div><span class="ticket-kicker">Edición</span><h2 class="admin-record-dialog-title" id="catalog-edit-title" data-catalog-dialog-title>Editar registro</h2></div>
        <button class="admin-record-dialog-close" type="button" data-admin-dialog-close aria-label="Cerrar">×</button>
      </div>
      <div class="catalog-dialog-context">Código interno: <strong class="catalog-code" data-catalog-dialog-code>—</strong></div>
      <form method="post" data-single-submit class="catalog-dialog-form admin-record-dialog-body" data-catalog-edit-form>
        <input type="hidden" name="_csrf" value="<?= Csrf::token() ?>">
        <input type="hidden" name="id" value="" data-catalog-edit-id>
        <div class="catalog-dialog-grid">
          <label class="catalog-dialog-full"><span class="form-label">Nombre</span><input class="form-control" name="name" maxlength="160" required data-catalog-edit-name></label>
          <label data-catalog-park-field hidden><span class="form-label">Región</span><select class="form-control" name="region_id" data-catalog-edit-region disabled><?php foreach($regions as $r): ?><option value="<?= (int)$r['id'] ?>" <?= (int)$r['is_active']!==1?'disabled':'' ?>><?= htmlspecialchars((string)$r['name']) ?><?= (int)$r['is_active']!==1?' · Inactiva':'' ?></option><?php endforeach; ?></select></label>
          <label data-catalog-park-field hidden><span class="form-label">Centro de costo</span><input class="form-control" name="cost_center" maxlength="50" data-catalog-edit-cost disabled></label>
          <label class="catalog-dialog-full" data-catalog-park-field hidden><span class="form-label">Dirección</span><input class="form-control" name="address" maxlength="255" data-catalog-edit-address disabled></label>
          <label class="catalog-dialog-full" data-catalog-area-field hidden><span class="form-label">Descripción</span><input class="form-control" name="description" maxlength="255" data-catalog-edit-description disabled></label>
        </div>
        <div class="catalog-dialog-actions">
          <button class="btn btn-outline-secondary" type="button" data-admin-dialog-close>Cancelar</button>
          <button class="btn btn-primary" type="submit">Guardar cambios</button>
        </div>
      </form>
    </div>
  </dialog>
  <?php endif; ?>
</div>

<?php if($section!=='home'): ?>
<script nonce="<?= htmlspecialchars(CSP_NONCE) ?>">
(function(){
  const normalize=v=>(v||'').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'').replace(/\s+/g,' ').trim();
  document.querySelectorAll('[data-catalog-section]').forEach(function(section){
    const search=section.querySelector('[data-catalog-search]');
    const status=section.querySelector('[data-catalog-status]');
    const rows=[...section.querySelectorAll('[data-catalog-row]')];
    const empty=section.querySelector('[data-catalog-empty]');
    const apply=function(){
      const q=normalize(search?.value||'');const tokens=q?q.split(' ').filter(Boolean):[];const s=status?.value||'';let visible=0;
      rows.forEach(function(row){const text=normalize(row.dataset.search||'');const okText=tokens.length===0||tokens.every(t=>text.includes(t));const okStatus=s===''||row.dataset.status===s;row.hidden=!(okText&&okStatus);if(!row.hidden)visible++;});
      if(empty)empty.hidden=visible!==0;
    };
    search?.addEventListener('input',apply);status?.addEventListener('change',apply);apply();
  });
  const dialog=document.querySelector('[data-catalog-dialog]');
  const editForm=dialog?.querySelector('[data-catalog-edit-form]');
  const idInput=dialog?.querySelector('[data-catalog-edit-id]');
  const nameInput=dialog?.querySelector('[data-catalog-edit-name]');
  const codeText=dialog?.querySelector('[data-catalog-dialog-code]');
  const title=dialog?.querySelector('[data-catalog-dialog-title]');
  const regionInput=dialog?.querySelector('[data-catalog-edit-region]');
  const costInput=dialog?.querySelector('[data-catalog-edit-cost]');
  const addressInput=dialog?.querySelector('[data-catalog-edit-address]');
  const descriptionInput=dialog?.querySelector('[data-catalog-edit-description]');
  const parkFields=[...dialog?.querySelectorAll('[data-catalog-park-field]')||[]];
  const areaFields=[...dialog?.querySelectorAll('[data-catalog-area-field]')||[]];

  const setGroup=function(fields,enabled){
    fields.forEach(function(field){
      field.hidden=!enabled;
      field.querySelectorAll('input,select,textarea').forEach(function(control){control.disabled=!enabled;});
    });
  };

  document.querySelectorAll('[data-catalog-edit]').forEach(function(button){
    button.addEventListener('click',function(){
      if(!dialog||!editForm||!idInput||!nameInput)return;
      const kind=button.dataset.kind||'';
      const label=kind==='park'?'parque':kind==='area'?'área':'región';
      editForm.action=button.dataset.action||'';
      idInput.value=button.dataset.id||'';
      nameInput.value=button.dataset.name||'';
      if(codeText)codeText.textContent=button.dataset.code||'—';
      if(title)title.textContent='Editar '+label+' · '+(button.dataset.name||'');
      setGroup(parkFields,kind==='park');
      setGroup(areaFields,kind==='area');
      if(kind==='park'){
        if(regionInput)regionInput.value=button.dataset.regionId||'';
        if(costInput)costInput.value=button.dataset.costCenter||'';
        if(addressInput)addressInput.value=button.dataset.address||'';
      }
      if(kind==='area'&&descriptionInput)descriptionInput.value=button.dataset.description||'';
      dialog.showModal();
      window.setTimeout(function(){nameInput.focus();nameInput.select();},0);
    });
  });


})();
</script>
<?php endif; ?>
<?php require APP_ROOT.'/app/Views/shared/app_end.php'; ?>
