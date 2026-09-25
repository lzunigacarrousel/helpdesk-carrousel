<?php
declare(strict_types=1);
use App\Core\Csrf;
$pageTitle='Catálogos';$pageSection='Administración';$activeNav='catalogs';$helpContext='catalogs';
require APP_ROOT.'/app/Views/shared/app_start.php';

$activeRegions=count(array_filter($regions,static fn(array $x):bool=>(int)$x['is_active']===1));
$activeParks=count(array_filter($parks,static fn(array $x):bool=>(int)$x['is_active']===1));
$activeAreas=count(array_filter($areas,static fn(array $x):bool=>(int)$x['is_active']===1));
?>
<style nonce="<?= htmlspecialchars(CSP_NONCE) ?>">
.catalog-page{display:grid;gap:16px}
.catalog-head{display:flex;align-items:flex-end;justify-content:space-between;gap:16px}
.catalog-head h1{margin:3px 0 0}
.catalog-summary{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}
.catalog-stat{padding:14px 16px}
.catalog-stat span{display:block;font-size:11px;font-weight:800;text-transform:uppercase;color:var(--muted)}
.catalog-stat strong{display:block;margin-top:4px;font-size:24px;color:var(--brand-dark)}
.catalog-section{scroll-margin-top:84px;overflow:hidden}
.catalog-section-title{display:grid;gap:2px}
.catalog-section-title h2{margin:0;font-size:17px}
.catalog-section-title small{color:var(--muted);font-size:11px;font-weight:500}
.catalog-toolbar{display:flex;gap:8px;align-items:end;flex-wrap:wrap}
.catalog-toolbar label{margin:0}
.catalog-toolbar .catalog-search{min-width:260px}
.catalog-toolbar .catalog-filter{min-width:125px}
.catalog-create{padding:13px 14px;border-bottom:1px solid var(--border);background:color-mix(in srgb,var(--card) 97%,var(--bg) 3%)}
.catalog-create form{display:grid;gap:10px;align-items:end}
.catalog-create.region form,.catalog-create.area form{grid-template-columns:minmax(220px,1fr) minmax(260px,2fr) auto}
.catalog-create.park form{grid-template-columns:minmax(180px,1fr) minmax(220px,1.4fr) minmax(130px,.7fr) minmax(240px,1.6fr) auto}
.catalog-create .field-help{grid-column:1/-1}
.catalog-code{font-family:ui-monospace,SFMono-Regular,Consolas,monospace;font-size:10.5px}
.catalog-risk{color:var(--warning);font-size:10.5px}
.catalog-edit td{padding:0!important;background:color-mix(in srgb,var(--card) 96%,var(--bg) 4%)}
.catalog-edit form{display:grid;gap:10px;align-items:end;padding:13px 14px}
.catalog-edit.region form,.catalog-edit.area form{grid-template-columns:minmax(220px,1fr) minmax(260px,2fr) auto auto}
.catalog-edit.park form{grid-template-columns:minmax(180px,1fr) minmax(220px,1.4fr) minmax(130px,.7fr) minmax(240px,1.6fr) auto auto}
.catalog-edit[hidden]{display:none!important}
.catalog-actions-note{display:block;margin-top:3px;color:var(--muted);font-size:10px}
.catalog-empty{padding:22px;text-align:center;color:var(--muted)}
[data-catalog-row][hidden]{display:none!important}
@media(max-width:900px){
  .catalog-summary{grid-template-columns:1fr}
  .data-table-toolbar{align-items:stretch}
  .catalog-toolbar{width:100%}
  .catalog-toolbar label{flex:1 1 180px}
  .catalog-toolbar .catalog-search,.catalog-toolbar .catalog-filter{min-width:0;width:100%}
  .catalog-create form,.catalog-create.region form,.catalog-create.area form,.catalog-create.park form,
  .catalog-edit form,.catalog-edit.region form,.catalog-edit.area form,.catalog-edit.park form{grid-template-columns:1fr}
}
</style>

<div class="catalog-page">
  <div class="catalog-head">
    <div><span class="ticket-kicker">Organización</span><h1 class="page-title">Catálogos administrativos</h1><p class="subtle">Administra regiones, parques y áreas sin eliminar el historial existente.</p></div>
  </div>

  <div class="catalog-summary">
    <div class="card catalog-stat"><span>Regiones activas</span><strong><?= $activeRegions ?></strong></div>
    <div class="card catalog-stat"><span>Parques activos</span><strong><?= $activeParks ?></strong></div>
    <div class="card catalog-stat"><span>Áreas activas</span><strong><?= $activeAreas ?></strong></div>
  </div>

  <section class="catalog-section data-table-shell" id="regiones" data-catalog-section>
    <div class="data-table-toolbar"><div class="catalog-section-title"><span class="ticket-kicker">Regiones</span><h2>Regiones</h2><small>Organiza los parques por región.</small></div><div class="catalog-toolbar"><label><span class="form-label">Buscar</span><input class="form-control catalog-search" type="search" placeholder="Nombre o código…" data-catalog-search></label><label><span class="form-label">Estado</span><select class="form-control catalog-filter" data-catalog-status><option value="">Todos</option><option value="1">Activos</option><option value="0">Inactivos</option></select></label></div></div>
    <div class="catalog-create region"><form method="post" action="<?= APP_BASE_URL ?>/admin/catalogos/regiones/guardar" data-single-submit><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><label><span class="form-label">Nueva región</span><input class="form-control" name="name" maxlength="120" required placeholder="Ej. Región Norte"></label><div class="field-help">El código interno se genera automáticamente y no afecta los registros existentes.</div><button class="btn btn-primary" type="submit">Agregar región</button></form></div>
    <div class="data-table-wrap"><table class="data-table catalog-table"><thead><tr><th>Región</th><th>Código</th><th>Parques</th><th>Asignaciones</th><th>Estado</th><th></th></tr></thead><tbody>
    <?php foreach($regions as $r): $rid=(int)$r['id']; ?>
      <tr data-catalog-row data-search="<?= htmlspecialchars(mb_strtolower((string)$r['name'].' '.(string)$r['code']),ENT_QUOTES,'UTF-8') ?>" data-status="<?= (int)$r['is_active'] ?>">
        <td data-label="Región"><strong><?= htmlspecialchars((string)$r['name']) ?></strong></td>
        <td data-label="Código" class="data-table-secondary"><span class="catalog-code"><?= htmlspecialchars((string)$r['code']) ?></span></td>
        <td data-label="Parques"><?= (int)$r['active_parks'] ?> activos · <?= (int)$r['parks_total'] ?> total</td>
        <td data-label="Asignaciones"><?= (int)$r['active_assignments'] ?></td>
        <td data-label="Estado"><span class="badge <?= (int)$r['is_active']===1?'badge-success':'badge-secondary' ?>"><?= (int)$r['is_active']===1?'Activo':'Inactivo' ?></span></td>
        <td data-label="Acciones" class="data-table-actions"><div class="data-table-actions"><button class="btn btn-outline-secondary btn-sm" type="button" data-catalog-edit-toggle="region-<?= $rid ?>">Editar</button><form method="post" action="<?= APP_BASE_URL ?>/admin/catalogos/regiones/estado" data-single-submit><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="id" value="<?= $rid ?>"><input type="hidden" name="activate" value="<?= (int)$r['is_active']===1?'0':'1' ?>"><button class="btn btn-outline-secondary btn-sm" type="submit"><?= (int)$r['is_active']===1?'Desactivar':'Reactivar' ?></button></form></div></td>
      </tr>
      <tr class="catalog-edit region" id="region-<?= $rid ?>" data-catalog-edit hidden><td colspan="6" data-label=""><form method="post" action="<?= APP_BASE_URL ?>/admin/catalogos/regiones/guardar" data-single-submit><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="id" value="<?= $rid ?>"><label><span class="form-label">Nombre</span><input class="form-control" name="name" value="<?= htmlspecialchars((string)$r['name']) ?>" maxlength="120" required></label><div><span class="form-label">Código interno</span><div class="form-control catalog-code"><?= htmlspecialchars((string)$r['code']) ?></div></div><button class="btn btn-primary" type="submit">Guardar</button><button class="btn btn-outline-secondary" type="button" data-catalog-edit-close>Cancelar</button></form></td></tr>
    <?php endforeach; ?>
    </tbody></table><div class="catalog-empty" data-catalog-empty hidden>No hay regiones que coincidan con esos filtros.</div></div>
  </section>

  <section class="catalog-section data-table-shell" id="parques" data-catalog-section>
    <div class="data-table-toolbar"><div class="catalog-section-title"><span class="ticket-kicker">Parques</span><h2>Parques</h2><small>Ubicaciones operativas disponibles en tickets y usuarios.</small></div><div class="catalog-toolbar"><label><span class="form-label">Buscar</span><input class="form-control catalog-search" type="search" placeholder="Parque, región o centro de costo…" data-catalog-search></label><label><span class="form-label">Estado</span><select class="form-control catalog-filter" data-catalog-status><option value="">Todos</option><option value="1">Activos</option><option value="0">Inactivos</option></select></label></div></div>
    <div class="catalog-create park"><form method="post" action="<?= APP_BASE_URL ?>/admin/catalogos/parques/guardar" data-single-submit><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><label><span class="form-label">Región</span><select class="form-control" name="region_id" required><option value="">Selecciona</option><?php foreach($regions as $r): if((int)$r['is_active']!==1)continue; ?><option value="<?= (int)$r['id'] ?>"><?= htmlspecialchars((string)$r['name']) ?></option><?php endforeach; ?></select></label><label><span class="form-label">Nuevo parque</span><input class="form-control" name="name" maxlength="160" required placeholder="Nombre del parque"></label><label><span class="form-label">Centro de costo <span class="optional">Opcional</span></span><input class="form-control" name="cost_center" maxlength="50" placeholder="Ej. 071"></label><label><span class="form-label">Dirección <span class="optional">Opcional</span></span><input class="form-control" name="address" maxlength="255" placeholder="Centro comercial / dirección"></label><button class="btn btn-primary" type="submit">Agregar parque</button></form></div>
    <div class="data-table-wrap"><table class="data-table catalog-table"><thead><tr><th>Parque</th><th>Región</th><th>Centro costo</th><th>Uso activo</th><th>Estado</th><th></th></tr></thead><tbody>
    <?php foreach($parks as $p): $pid=(int)$p['id']; $usage=(int)$p['active_assignments']+(int)$p['open_tickets']+(int)$p['active_activities']; ?>
      <tr data-catalog-row data-search="<?= htmlspecialchars(mb_strtolower(implode(' ',[(string)$p['name'],(string)$p['code'],(string)$p['region_name'],(string)$p['cost_center'],(string)$p['address']])),ENT_QUOTES,'UTF-8') ?>" data-status="<?= (int)$p['is_active'] ?>">
        <td data-label="Parque"><strong><?= htmlspecialchars((string)$p['name']) ?></strong><small class="catalog-code"><?= htmlspecialchars((string)$p['code']) ?></small></td>
        <td data-label="Región"><?= htmlspecialchars((string)($p['region_name']??'Sin región')) ?></td>
        <td data-label="Centro costo" class="data-table-secondary"><?= htmlspecialchars((string)($p['cost_center']??'—')) ?></td>
        <td data-label="Uso activo"><span><?= (int)$p['active_assignments'] ?> asign. · <?= (int)$p['open_tickets'] ?> tickets · <?= (int)$p['active_activities'] ?> activ.</span><?php if($usage>0): ?><small class="catalog-risk">Debe quedar sin uso activo antes de desactivar.</small><?php endif; ?></td>
        <td data-label="Estado"><span class="badge <?= (int)$p['is_active']===1?'badge-success':'badge-secondary' ?>"><?= (int)$p['is_active']===1?'Activo':'Inactivo' ?></span></td>
        <td data-label="Acciones" class="data-table-actions"><div class="data-table-actions"><button class="btn btn-outline-secondary btn-sm" type="button" data-catalog-edit-toggle="park-<?= $pid ?>">Editar</button><form method="post" action="<?= APP_BASE_URL ?>/admin/catalogos/parques/estado" data-single-submit><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="id" value="<?= $pid ?>"><input type="hidden" name="activate" value="<?= (int)$p['is_active']===1?'0':'1' ?>"><button class="btn btn-outline-secondary btn-sm" type="submit"><?= (int)$p['is_active']===1?'Desactivar':'Reactivar' ?></button></form></div></td>
      </tr>
      <tr class="catalog-edit park" id="park-<?= $pid ?>" data-catalog-edit hidden><td colspan="6" data-label=""><form method="post" action="<?= APP_BASE_URL ?>/admin/catalogos/parques/guardar" data-single-submit><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="id" value="<?= $pid ?>"><label><span class="form-label">Región</span><select class="form-control" name="region_id" required><?php foreach($regions as $r): $selected=(int)$r['id']===(int)$p['region_id']; ?><option value="<?= (int)$r['id'] ?>" <?= $selected?'selected':'' ?> <?= (int)$r['is_active']!==1&&!$selected?'disabled':'' ?>><?= htmlspecialchars((string)$r['name']) ?><?= (int)$r['is_active']!==1?' · Inactiva':'' ?></option><?php endforeach; ?></select></label><label><span class="form-label">Nombre</span><input class="form-control" name="name" value="<?= htmlspecialchars((string)$p['name']) ?>" maxlength="160" required></label><label><span class="form-label">Centro de costo</span><input class="form-control" name="cost_center" value="<?= htmlspecialchars((string)($p['cost_center']??'')) ?>" maxlength="50"></label><label><span class="form-label">Dirección</span><input class="form-control" name="address" value="<?= htmlspecialchars((string)($p['address']??'')) ?>" maxlength="255"></label><button class="btn btn-primary" type="submit">Guardar</button><button class="btn btn-outline-secondary" type="button" data-catalog-edit-close>Cancelar</button></form></td></tr>
    <?php endforeach; ?>
    </tbody></table><div class="catalog-empty" data-catalog-empty hidden>No hay parques que coincidan con esos filtros.</div></div>
  </section>

  <section class="catalog-section data-table-shell" id="areas" data-catalog-section>
    <div class="data-table-toolbar"><div class="catalog-section-title"><span class="ticket-kicker">Áreas</span><h2>Áreas</h2><small>Áreas corporativas utilizadas en asignaciones y tickets.</small></div><div class="catalog-toolbar"><label><span class="form-label">Buscar</span><input class="form-control catalog-search" type="search" placeholder="Nombre, código o descripción…" data-catalog-search></label><label><span class="form-label">Estado</span><select class="form-control catalog-filter" data-catalog-status><option value="">Todos</option><option value="1">Activos</option><option value="0">Inactivos</option></select></label></div></div>
    <div class="catalog-create area"><form method="post" action="<?= APP_BASE_URL ?>/admin/catalogos/areas/guardar" data-single-submit><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><label><span class="form-label">Nueva área</span><input class="form-control" name="name" maxlength="120" required placeholder="Ej. Tecnología"></label><label><span class="form-label">Descripción <span class="optional">Opcional</span></span><input class="form-control" name="description" maxlength="255" placeholder="Descripción breve"></label><button class="btn btn-primary" type="submit">Agregar área</button></form></div>
    <div class="data-table-wrap"><table class="data-table catalog-table"><thead><tr><th>Área</th><th>Código</th><th>Descripción</th><th>Uso activo</th><th>Estado</th><th></th></tr></thead><tbody>
    <?php foreach($areas as $a): $aid=(int)$a['id']; $usage=(int)$a['active_assignments']+(int)$a['open_tickets']; ?>
      <tr data-catalog-row data-search="<?= htmlspecialchars(mb_strtolower((string)$a['name'].' '.(string)$a['code'].' '.(string)$a['description']),ENT_QUOTES,'UTF-8') ?>" data-status="<?= (int)$a['is_active'] ?>">
        <td data-label="Área"><strong><?= htmlspecialchars((string)$a['name']) ?></strong></td>
        <td data-label="Código" class="data-table-secondary"><span class="catalog-code"><?= htmlspecialchars((string)$a['code']) ?></span></td>
        <td data-label="Descripción" class="data-table-secondary"><?= htmlspecialchars((string)($a['description']??'—')) ?></td>
        <td data-label="Uso activo"><?= (int)$a['active_assignments'] ?> asign. · <?= (int)$a['open_tickets'] ?> tickets<?php if($usage>0): ?><small class="catalog-risk">Debe quedar sin uso activo antes de desactivar.</small><?php endif; ?></td>
        <td data-label="Estado"><span class="badge <?= (int)$a['is_active']===1?'badge-success':'badge-secondary' ?>"><?= (int)$a['is_active']===1?'Activo':'Inactivo' ?></span></td>
        <td data-label="Acciones" class="data-table-actions"><div class="data-table-actions"><button class="btn btn-outline-secondary btn-sm" type="button" data-catalog-edit-toggle="area-<?= $aid ?>">Editar</button><form method="post" action="<?= APP_BASE_URL ?>/admin/catalogos/areas/estado" data-single-submit><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="id" value="<?= $aid ?>"><input type="hidden" name="activate" value="<?= (int)$a['is_active']===1?'0':'1' ?>"><button class="btn btn-outline-secondary btn-sm" type="submit"><?= (int)$a['is_active']===1?'Desactivar':'Reactivar' ?></button></form></div></td>
      </tr>
      <tr class="catalog-edit area" id="area-<?= $aid ?>" data-catalog-edit hidden><td colspan="6" data-label=""><form method="post" action="<?= APP_BASE_URL ?>/admin/catalogos/areas/guardar" data-single-submit><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="id" value="<?= $aid ?>"><label><span class="form-label">Nombre</span><input class="form-control" name="name" value="<?= htmlspecialchars((string)$a['name']) ?>" maxlength="120" required></label><label><span class="form-label">Descripción</span><input class="form-control" name="description" value="<?= htmlspecialchars((string)($a['description']??'')) ?>" maxlength="255"></label><button class="btn btn-primary" type="submit">Guardar</button><button class="btn btn-outline-secondary" type="button" data-catalog-edit-close>Cancelar</button></form></td></tr>
    <?php endforeach; ?>
    </tbody></table><div class="catalog-empty" data-catalog-empty hidden>No hay áreas que coincidan con esos filtros.</div></div>
  </section>
</div>

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
  document.querySelectorAll('[data-catalog-edit-toggle]').forEach(function(button){
    button.addEventListener('click',function(){const id=button.dataset.catalogEditToggle||'';const row=document.getElementById(id);if(!row)return;document.querySelectorAll('[data-catalog-edit]').forEach(x=>{if(x!==row)x.hidden=true;});row.hidden=!row.hidden;if(!row.hidden)row.querySelector('input,select')?.focus();});
  });
  document.querySelectorAll('[data-catalog-edit-close]').forEach(function(button){button.addEventListener('click',function(){const row=button.closest('[data-catalog-edit]');if(row)row.hidden=true;});});
})();
</script>
<?php require APP_ROOT.'/app/Views/shared/app_end.php'; ?>
