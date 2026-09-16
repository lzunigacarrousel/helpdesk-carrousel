# Log de continuidad — Fase 8 · Calidad IT → proveedor

**Rama:** `fase8-calidad-proveedor`  
**Fecha de checkpoint:** 2026-09-16  
**Objetivo:** valoración interna 1–5 de IT por ciclo finalizado de participación de proveedor, con correcciones inmutables, captura dentro del ticket e informe de proveedores.  
**BD:** 0 cambios estructurales. Fuente de verdad: `ticket_events`.

## Regla operativa del log

Este archivo debe actualizarse:
- después de cada cambio funcional relevante;
- después de cada RED/GREEN importante;
- al encontrar o resolver un blocker;
- al cerrar cada Task;
- antes de un commit de cierre importante;
- antes de cambiar de chat o pausar la sesión;
- antes de fusionar o eliminar la rama.

No guardar secretos, credenciales ni datos sensibles.

## Diseño aprobado

- Evaluación por ciclo exacto de participación.
- Solo ciclo terminado mediante `EXTERNAL_REVOKED` es evaluable.
- Escala: 1 Muy deficiente, 2 Deficiente, 3 Adecuado, 4 Bueno, 5 Excelente.
- Comentario obligatorio para 1–2 estrellas y para toda corrección.
- Evaluación opcional; no bloquea cierre ni flujo del ticket.
- Roles que pueden evaluar/corregir: `ADMIN`, `SEMIADMIN`, `TECHNICIAN`, siempre con scope válido.
- Proveedor y solicitante no ven score/comentario interno.
- Primera valoración: `PROVIDER_RATED`.
- Corrección: `PROVIDER_RATING_CORRECTED`.
- Correcciones son nuevos eventos; nunca se modifica/elimina una valoración anterior.
- Último evento válido del ciclo es la valoración vigente.
- Cada rating referencia `external_user_id` + `grant_event_id`.
- `Sin evaluar` no vale 0 ni entra al promedio.

## Flujo de fases — estado actual

### Task 1 — Identidad exacta del ciclo ✅
Implementado `grant_event_id`, `revoke_event_id`, `rowsForTicket()` y cierre implícito sin `revoke_event_id`.

### Task 2 — Dominio inmutable de valoración ✅
Implementado `ProviderRatingService` con escala, validación, valoración vigente, correcciones, enriquecimiento y resumen.

### Task 3 — Persistencia + autorización backend ✅
Implementado `rateCycle()`, `correctCycle()`, `FOR UPDATE`, controller, CSRF, scope, auditoría y rutas POST. Sin UPDATE/DELETE de ratings.

### Task 4 — UI interna de calidad ✅ CERRADA
Commit funcional: `67160ba ui: evaluar proveedores desde el ticket`.

Incluye:
- bloque interno `#provider-quality`;
- primera valoración;
- corrección;
- actor/fecha/comentario vigentes;
- aislamiento de `show_external.php`.

### Task 5 — Informe + valoración + XLSX ✅ CERRADA
Commit funcional: `a8811c6 feat: integrar calidad de proveedores en informes`.

Incluye:
- filtro `rating=UNRATED|1|2|3|4|5`;
- dataset enriquecido antes de filtrar;
- resumen por proveedor;
- valoración vigente en pantalla;
- XLSX con score, comentario y resumen.

### Task 6 — Seguridad, historial y casos límite ✅ CERRADA
Commit funcional: `8b17a4a feat: endurecer reglas de calidad proveedor`.

Incluye:
- `ProviderRatingService::isCycleEvaluable()`;
- cierre explícito evaluable;
- cierre implícito no evaluable;
- ciclo activo no evaluable;
- corrección obsoleta no desplaza vigente;
- rating de otro proveedor no se aplica al ciclo;
- proveedor y solicitante siguen sin acceso a calidad interna.

### Task 7 — CI, Manual y cierre documental ✅ CERRADA Y LIMPIA
Commit de cierre: `15ac1c4 docs: cerrar fase 8 calidad proveedor`.

Incluye:
- seis gates de Fase 8 en CI;
- README con Fase 8 implementada y Fase 9 como siguiente;
- CHANGELOG con `PROVIDER_RATED` y `PROVIDER_RATING_CORRECTED`;
- separación explícita de `ticket_feedback.nps_score`;
- Manual integrado con escala, correcciones, privacidad y `Sin evaluar`;
- ajuste estable de regresión Fase 7 para no fijar eternamente qué fase es la siguiente.

Herramientas temporales de Task 7 eliminadas.

### Task 8 — Gate integral y validación PC TEST 🚧 EN CURSO

#### Gate técnico ✅ GREEN COMPLETO

Ejecutado en PC TEST sobre `fase8-calidad-proveedor` sincronizada.

Confirmado:
- sintaxis PHP GREEN en servicios, controllers, vistas y router modificados;
- 6 regresiones de Fase 8: GREEN;
- 5 regresiones de Fase 7: GREEN;
- `project_quality.php`: GREEN;
- `xlsx_smoke.php`: GREEN;
- `static_checks.php`: GREEN;
- `git diff --check`: sin errores;
- `git diff --name-only origin/main...HEAD -- database`: sin salida;
- por lo tanto, 0 cambios en `database/` respecto a `main`;
- working tree limpio;
- rama local sincronizada con `origin/fase8-calidad-proveedor`.

Diff acumulado contra `origin/main` al ejecutar el gate:
- 22 archivos;
- 3234 inserciones;
- 291 eliminaciones;
- sin archivos de `database/`.

#### Validación funcional/visual ⏳ EN PROGRESO

No cerrar Task 8 ni fusionar a `main` hasta completar:

1. Ticket con proveedor finalizado explícitamente:
   - aparece `Calidad del proveedor`;
   - permite primera valoración;
   - 3–5 estrellas pueden guardarse sin comentario;
   - se muestra valoración vigente, actor y fecha.
2. 1–2 estrellas:
   - sin comentario debe rechazarse;
   - con comentario debe guardarse.
3. Corrección:
   - `Registrar corrección` exige comentario;
   - crea nueva valoración vigente;
   - no elimina historial anterior.
4. Casos no evaluables:
   - ciclo activo no permite evaluar;
   - cierre implícito por nuevo grant no permite evaluar.
5. Privacidad:
   - proveedor EXTERNAL no ve score/comentario/bloque;
   - solicitante REQUESTER no ve score/comentario/bloque.
6. Informe de proveedores:
   - filtro `Sin evaluar`;
   - filtros 1★–5★;
   - promedio de calidad;
   - ciclos evaluados/sin evaluar;
   - valoración vigente.
7. XLSX:
   - respeta filtros;
   - incluye valoración/score;
   - incluye comentario interno;
   - incluye resumen por proveedor.
8. Visual:
   - modo claro;
   - modo oscuro;
   - laptop/PC 1366px;
   - tablet/iPad;
   - móvil.

Especial atención a tablet/iPad por antecedentes de responsive en otras pantallas.

#### Evidencia manual registrada — 2026-09-16

Captura revisada de un ticket interno con proveedor `Pruebas Comunicacion` y participación activa:
- el bloque `Calidad del proveedor` aparece correctamente en la vista interna;
- proveedor visible correctamente;
- fecha de asignación visible;
- estado de participación: `Activa`;
- valoración vigente: `Sin evaluar`;
- mensaje mostrado: `Podrás evaluar cuando finalice la participación.`;
- no aparece formulario de evaluación mientras el ciclo sigue activo.

Resultado de este checkpoint manual:
- ✅ render del bloque interno confirmado;
- ✅ ciclo activo tratado como no evaluable;
- ✅ `Sin evaluar` mostrado correctamente;
- ✅ mensaje operativo correcto;
- ⏳ todavía falta validar primera valoración sobre un ciclo cerrado explícitamente.

Siguiente paso manual exacto:
1. finalizar explícitamente la participación del proveedor desde el flujo normal de la aplicación, o abrir otro ticket que ya tenga un `EXTERNAL_REVOKED` real;
2. volver al ticket interno;
3. confirmar que el bloque cambia de `Activa / Sin evaluar` a ciclo finalizado evaluable;
4. probar primera valoración 4★ o 5★ sin comentario;
5. comprobar valoración vigente, actor y fecha después de guardar.

## Estado exacto al pausar la sesión

- Tasks 1–7: ✅ cerradas.
- Task 8 técnico: ✅ GREEN.
- Task 8 funcional/visual: ⏳ en progreso.
- Rama de trabajo: `fase8-calidad-proveedor`.
- No se ha fusionado a `main`.
- No se debe iniciar Fase 9 todavía.
- La siguiente sesión debe continuar **exactamente en Task 8, validación funcional/visual**.

## Checkpoint final de sincronización local

Confirmado por PC TEST al pausar:
- rama local sincronizada con `origin/fase8-calidad-proveedor`;
- `git status` limpio;
- sin cambios locales pendientes.

## Integración a main

**NO HACER MERGE todavía.**

Solo preparar integración a `main` después de:
- completar checklist funcional/visual;
- resolver cualquier hallazgo;
- repetir gate técnico si hubo cambios;
- actualizar este log;
- obtener aprobación explícita del usuario.

## Próxima fase después de cerrar Fase 8

El roadmap documenta **Fase 9 — Conocimiento** como siguiente fase. No iniciar hasta cerrar formalmente Task 8 y Fase 8.
