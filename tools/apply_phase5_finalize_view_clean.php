<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$viewPath=$root.'/app/Views/tickets/show.php';
$view=(string)@file_get_contents($viewPath);
if($view===''){
    fwrite(STDERR,"[ERROR] No se pudo leer app/Views/tickets/show.php\n");
    exit(1);
}

if(str_contains($view,'ticket-activity-complete-details')&&str_contains($view,'ticket-activity-complete-grid')){
    echo "[OK] El layout limpio de Finalizar ya está aplicado.\n";
    exit(0);
}

$old='                  <details><summary class="btn btn-primary btn-sm">Finalizar</summary><form class="ticket-activity-transition" method="post" action="<?= APP_BASE_URL ?>/tickets/activities/complete" enctype="multipart/form-data" data-single-submit><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="activity_id" value="<?= $activityId ?>"><label><span class="form-label">Resultado</span><select class="form-control" name="result_code" required><option value="">Selecciona</option><?php foreach(($activityResults??[]) as $result): ?><option value="<?= htmlspecialchars((string)$result) ?>"><?= htmlspecialchars($activityResultLabels[$result]??str_replace(\'_\',\' \',(string)$result)) ?></option><?php endforeach; ?></select></label><label><span class="form-label">Trabajo realizado</span><textarea class="form-control" name="work_performed" required placeholder="Qué se hizo durante la actividad"></textarea></label><label><span class="form-label">Resultado obtenido</span><textarea class="form-control" name="result_summary" required placeholder="Qué se comprobó o resolvió"></textarea></label><label><span class="form-label">Pendientes <span class="optional">Opcional</span></span><textarea class="form-control" name="pending_items" placeholder="Qué queda pendiente o requiere seguimiento"></textarea></label><label><span class="form-label">Evidencia <span class="optional">Opcional · máximo 10 MB</span></span><input class="form-control" type="file" name="evidence" accept=".pdf,.jpg,.jpeg,.png,.webp,.txt,.csv,.doc,.docx,.xls,.xlsx"></label><button class="btn btn-primary btn-sm" type="submit">Finalizar actividad</button></form></details>';

$new='                  <details class="ticket-activity-complete-details"><summary class="btn btn-primary btn-sm">Finalizar</summary><form class="ticket-activity-transition ticket-activity-transition--complete" method="post" action="<?= APP_BASE_URL ?>/tickets/activities/complete" enctype="multipart/form-data" data-single-submit><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="activity_id" value="<?= $activityId ?>"><div class="ticket-activity-complete-grid"><label><span class="form-label">Resultado</span><select class="form-control" name="result_code" required><option value="">Selecciona</option><?php foreach(($activityResults??[]) as $result): ?><option value="<?= htmlspecialchars((string)$result) ?>"><?= htmlspecialchars($activityResultLabels[$result]??str_replace(\'_\',\' \',(string)$result)) ?></option><?php endforeach; ?></select></label><label><span class="form-label">Evidencia <span class="optional">Opcional · máximo 10 MB</span></span><input class="form-control" type="file" name="evidence" accept=".pdf,.jpg,.jpeg,.png,.webp,.txt,.csv,.doc,.docx,.xls,.xlsx"></label><label><span class="form-label">Trabajo realizado</span><textarea class="form-control" name="work_performed" required placeholder="Qué se hizo durante la actividad"></textarea></label><label><span class="form-label">Resultado obtenido</span><textarea class="form-control" name="result_summary" required placeholder="Qué se comprobó o resolvió"></textarea></label><label class="activity-span-2"><span class="form-label">Pendientes <span class="optional">Opcional</span></span><textarea class="form-control" name="pending_items" placeholder="Qué queda pendiente o requiere seguimiento"></textarea></label><div class="activity-span-2 ticket-activity-complete-actions"><button class="btn btn-primary btn-sm" type="submit">Finalizar actividad</button></div></div></form></details>';

if(!str_contains($view,$old)){
    fwrite(STDERR,"[ERROR] No se encontró el bloque canónico original de Finalizar. No se modificó el archivo.\n");
    exit(1);
}

$updated=str_replace($old,$new,$view,$count);
if($count!==1){
    fwrite(STDERR,"[ERROR] Se esperó reemplazar exactamente 1 bloque y se detectaron {$count}. No se guardó.\n");
    exit(1);
}

if(file_put_contents($viewPath,$updated)===false){
    fwrite(STDERR,"[ERROR] No se pudo escribir app/Views/tickets/show.php\n");
    exit(1);
}

echo "[OK] Se aplicó únicamente el layout de Finalizar en show.php.\n";
echo "[OK] No se reescribieron otros bloques ni saltos de línea.\n";
