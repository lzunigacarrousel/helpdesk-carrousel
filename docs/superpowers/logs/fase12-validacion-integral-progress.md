# Fase 12 — Validación integral · progreso

Fecha de inicio: 2026-09-19
Rama: `main`

## Estado inicial

- Fase 11 Manual: gate final PC TEST GREEN.
- Fases 5–11: implementadas; validación transversal pendiente.
- Fase 12: iniciada.
- APP_VERSION: `2.4.0-dev`.
- Producción: sin cambios.

## Decisiones

- No reconstruir funcionalidad existente.
- Unificar la validación final en un gate transversal, evitando ejecutar gates anidados redundantes.
- Separar validación automatizada, SQL, E2E y visual/manual.
- Toda corrección real debe agregar o reforzar una regresión.
- No aprobar producción automáticamente al cerrar Fase 12.

## Task 1 — Preflight y baseline

Estado: en implementación.


## Task 1 — Preflight y baseline · IMPLEMENTADA EN CÓDIGO

- README marca Fase 12 como **EN CURSO — cierre transversal y readiness**.
- Se creó spec, plan y log canónico de Fase 12.
- `tests/phase12_release_baseline_regression.php` valida estado de fases 5–11, versión, artefactos SQL, protección de BD histórica y wiring del gate.
- `VALIDAR_FASE12.bat` se creó como gate transversal inicial sin encadenar gates antiguos completos.
- El gate cubre baseline, instalación, solicitante, tickets/SLA, feedback, Actividades, Agenda, Proveedores/Calidad, Conocimiento, Reportes, Manual, navegación, modo oscuro, botones, checks estáticos, rutas/CSS y XLSX.
- El gate declara explícitamente que su resultado es preliminar y **no autoriza producción**.
- CI incorpora el preflight de Fase 12.
- APP_VERSION permanece en `2.4.0-dev`.
- BD: sin cambios.
- Producción: sin cambios.

Siguiente: Task 2 — integridad de BD, instalación limpia, verificadores e idempotencia de migraciones vigentes en PC TEST.


### Corrección de baseline — regresiones históricas de roadmap

- Primer `VALIDAR_FASE12.bat` llegó hasta el final y solo reportó dos fallos:
  - `phase10_closeout_regression.php` todavía exigía que Fase 11 fuera “SIGUIENTE”.
  - `phase11_closeout_regression.php` todavía exigía que Fase 12 fuera “SIGUIENTE”.
- Ambos fallos son expectativas históricas obsoletas: Fase 11 ya está implementada y Fase 12 está actualmente EN CURSO.
- Se actualizaron únicamente los asserts de roadmap.
- No se modificó funcionalidad, README, configuración, BD ni producción.
- Debe repetirse primero ambos closeouts y después `VALIDAR_FASE12.bat`.
