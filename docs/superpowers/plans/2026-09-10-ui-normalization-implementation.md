# UI Normalization Implementation Plan

> **DOCUMENTO HISTÓRICO.** Esta fase de normalización visual ya fue implementada en `ui-normalization-working`. No utilizar este archivo como plan de la etapa funcional actual. El plan vigente es `docs/superpowers/plans/2026-09-10-helpdesk-functional-maturation-implementation.md`.
>
> **Estado 2026-09-10:** implementación automatizada completada en `ui-normalization-working`. GitHub Actions valida sintaxis PHP/JS, static checks, quality gate, XLSX y una instalación limpia de MariaDB. La revisión visual real en PC TEST (escritorio/iPad/móvil) queda como gate manual antes de cualquier integración posterior.
>
> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Normalizar la UI completa del Helpdesk Carrousel para eliminar espacios muertos, reducir copy repetitivo y usar tablas reales, responsive y consistentes en los módulos operativos y administrativos.

**Architecture:** La normalización se hará desde una única capa global de geometría y tablas, retirando reglas contradictorias y manteniendo el markup funcional existente. Los módulos que actualmente simulan tablas con cards se convertirán a tablas semánticas conservando endpoints, formularios, CSRF, permisos y acciones. La limpieza de copy será una pasada final, separada de la refactorización estructural.

**Tech Stack:** PHP 8.2, HTML semántico, CSS responsive, JavaScript vanilla, MariaDB sin cambios de esquema, GitHub Actions.

**Spec:** `docs/superpowers/specs/2026-09-10-ui-normalization-design.md`

## Global Constraints

- La base activa de Helpdesk Carrousel V2 es exclusivamente `carrousel_helpdesk`. La base `helpdesk_carrousel` pertenece al sistema histórico y está protegida: no debe eliminarse, recrearse, modificarse ni reutilizarse como V2.
- No cambiar roles, permisos, rutas, OTP, SLA, NPS, notificaciones, auditoría, exportaciones ni lógica de negocio.
- Mantener identidad visual Carrousel y compatibilidad con modo claro/oscuro.
- Escritorio y laptop deben aprovechar el ancho sin columnas fantasmas.
- iPad/tablet no debe depender de scroll horizontal para operaciones básicas.
- Móvil debe transformar tablas prioritarias en lectura vertical con `data-label`.
- La pantalla explica solo lo necesario; la ayuda contextual explica el uso y el manual conserva la explicación extensa.
- No convertir en tabla: conversaciones, detalle de ticket, formularios, dashboards, búsqueda mixta, feedback/NPS ni lectura de conocimiento.

---

### Task 1: Quality gates para layout y tablas

**Files:**
- Modify: `tests/project_quality.php`
- Modify: `tests/static_checks.php`

**Interfaces:**
- Consumes: estructura actual de `app/Views` y `public/assets/css`.
- Produces: verificaciones estáticas que impiden reintroducir reglas globales contradictorias y exigen el patrón tabular normalizado en los módulos definidos.

- [x] **Step 1: Write the failing tests**

Agregar verificaciones que fallen mientras sigan presentes estas condiciones: `sticky-footer.css` cargado como override independiente, una tabla operativa efectiva con ancho mínimo forzado, y módulos objetivo sin `<table>` semántica.

- [x] **Step 2: Run tests and verify RED**

Se verificó RED en GitHub Actions antes de aplicar la normalización de las tablas existentes.

- [x] **Step 3: Commit tests only**

Los gates quedaron versionados en la rama de trabajo.

### Task 2: Consolidar geometría global y footer

**Files:**
- Modify: `app/Views/shared/app_end.php`
- Modify: `public/assets/css/layout-density-v25.css`
- Delete: `public/assets/css/sticky-footer.css`

- [x] Retirar la capa contradictoria de footer.
- [x] Consolidar geometría final del shell en una sola capa efectiva.
- [x] Eliminar alturas y columnas fantasma en la capa final.

### Task 3: Crear componente tabular responsive único

**Files:**
- Add: `public/assets/css/data-tables.css`
- Add: `public/assets/js/table-normalization.js`
- Modify: `app/Views/shared/app_end.php`

- [x] Crear `.data-table-shell`, `.data-table`, `.data-table-actions`, `.data-table-muted` y responsive basado en `data-label`.
- [x] Escritorio: ancho 100%, wrapping controlado, sin ancho mínimo efectivo.
- [x] Tablet: priorizar columnas operativas.
- [x] Móvil: transformar filas en registros verticales sin depender de scroll horizontal.
- [x] Añadir compatibilidad automática para tablas históricas.

### Task 4: Convertir Usuarios y Auditoría a tablas reales

- [x] `app/Views/admin/users.php`
- [x] `app/Views/admin/audit.php`
- [x] Mantener alta, edición, filtros, asignaciones, retiro de acceso, detalles y paginación.

### Task 5: Convertir Equipo de soporte y Proveedores

- [x] `app/Views/management/support_team.php`
- [x] `app/Views/admin/externals.php`
- [x] `app/Views/management/external_report.php`
- [x] Mantener KPIs, compartir/revocar casos, permisos, filtros y XLSX.

### Task 6: Normalizar todas las tablas ya existentes

- [x] `app/Views/tickets/queue.php`
- [x] `app/Views/admin/mail.php`
- [x] `app/Views/problems/index.php`
- [x] `app/Views/management/dashboard.php`
- [x] `app/Views/management/reports.php`
- [x] Conservar filtros, acciones, rutas y datos.

### Task 7: Pasada global de copy y densidad

- [x] Inicio / dashboard.
- [x] Dashboard de gestión.
- [x] Detalle interno del ticket.
- [x] Detalle de proveedor externo.
- [x] Gestión de proveedores.
- [x] Login, OTP y primer ingreso.
- [x] Crear solicitud.
- [x] Confirmación/NPS.
- [x] Problemas conocidos.
- [x] Base de conocimiento.
- [x] Búsqueda global.
- [x] Conservar validaciones, consecuencias, privacidad de conversaciones, límites de archivo y copy necesario para evitar errores.

### Task 8: Verificación integral y cierre

- [x] PHP syntax.
- [x] JavaScript syntax.
- [x] Static checks.
- [x] Route/view/CSS quality gate.
- [x] XLSX regression smoke test.
- [x] Instalación limpia de MariaDB en CI.
- [x] Comparación contra `v2-rebuild`: sin cambios bajo `database/`.
- [x] Documentar el patrón en `docs/ESTANDAR_VISUAL_CARROUSEL.md`.
- [ ] Revisión visual manual en PC TEST: escritorio 1920, laptop 1366, iPad/tablet ~1024/768 y móvil <=760.
