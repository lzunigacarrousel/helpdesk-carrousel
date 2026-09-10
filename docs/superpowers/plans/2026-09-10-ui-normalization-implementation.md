# UI Normalization Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Normalizar la UI completa del Helpdesk Carrousel para eliminar espacios muertos, reducir copy repetitivo y usar tablas reales, responsive y consistentes en los módulos operativos y administrativos.

**Architecture:** La normalización se hará desde una única capa global de geometría y tablas, retirando reglas contradictorias y manteniendo el markup funcional existente. Los módulos que actualmente simulan tablas con cards se convertirán a tablas semánticas conservando endpoints, formularios, CSRF, permisos y acciones. La limpieza de copy será una pasada final, separada de la refactorización estructural.

**Tech Stack:** PHP 8.2, HTML semántico, CSS responsive, JavaScript vanilla, MariaDB sin cambios de esquema, GitHub Actions.

**Spec:** `docs/superpowers/specs/2026-09-10-ui-normalization-design.md`

## Global Constraints

- Mantener una sola base `helpdesk_carrousel`; no crear ni modificar SQL.
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

- [ ] **Step 1: Write the failing tests**

Agregar verificaciones que fallen mientras sigan presentes estas condiciones: `sticky-footer.css` cargado como override independiente, `report-table{min-width:1450px}`, `white-space:nowrap` aplicado globalmente a todas las celdas, y módulos objetivo sin `<table>` semántica.

```php
check(!str_contains($appEnd,'sticky-footer.css'),'Footer no depende de una segunda capa contradictoria');
check(!preg_match('/report-table\s*\{[^}]*min-width\s*:\s*1450px/s',$managementCss),'Informes no fuerzan ancho de 1450px');
foreach($requiredTableViews as $relative){
    $view=(string)file_get_contents($root.'/app/Views/'.$relative);
    check(str_contains($view,'<table'),'Vista usa tabla semántica: '.$relative);
}
```

- [ ] **Step 2: Run tests and verify RED**

Run:
```bash
php tests/project_quality.php
php tests/static_checks.php
```
Expected: FAIL en footer/layout y en las vistas que todavía usan cards.

- [ ] **Step 3: Commit tests only**

```bash
git add tests/project_quality.php tests/static_checks.php
git commit -m "Tests: proteger normalizacion UI global"
```

### Task 2: Consolidar geometría global y footer

**Files:**
- Modify: `app/Views/shared/app_end.php`
- Modify: `public/assets/css/layout-density-v25.css`
- Modify: `public/assets/css/shell-v2.css`
- Modify: `public/assets/css/layout-fixes.css`
- Delete: `public/assets/css/sticky-footer.css`

**Interfaces:**
- Consumes: `.main-wrap`, `.content`, `.app-corporate-footer` del shell actual.
- Produces: una sola estrategia flex del shell: `main-wrap` columna con `min-height`, `content` flexible, footer con `margin-top:auto`, sin overrides posteriores contradictorios.

- [ ] **Step 1: Remove contradictory footer layer**

Eliminar la carga de `sticky-footer.css` de `app_end.php` y trasladar la única estrategia válida a la capa global normalizada.

- [ ] **Step 2: Normalize shell geometry**

Usar una sola definición final:

```css
.main-wrap{display:flex;flex-direction:column;min-height:calc(100vh - 4px)}
.content{display:flex;flex-direction:column;flex:1 0 auto;min-height:0;width:100%}
.content>.app-corporate-footer{margin-top:auto;flex:0 0 auto}
```

Retirar o neutralizar en hojas históricas las reglas incompatibles de `display:block`, `flex:none` y alturas artificiales.

- [ ] **Step 3: Run quality checks**

```bash
php tests/project_quality.php
php tests/static_checks.php
```
Expected: footer/layout gates PASS; table gates todavía pueden fallar.

- [ ] **Step 4: Commit**

```bash
git add app/Views/shared/app_end.php public/assets/css/layout-density-v25.css public/assets/css/shell-v2.css public/assets/css/layout-fixes.css public/assets/css/sticky-footer.css
git commit -m "UI: consolidar geometria global y footer"
```

### Task 3: Crear componente tabular responsive único

**Files:**
- Modify: `public/assets/css/components.css`
- Modify: `public/assets/css/management.css`
- Modify: `public/assets/css/layout-density-v25.css`

**Interfaces:**
- Produces: `.data-table-shell`, `.data-table`, `.data-table-actions`, `.data-table-muted`, `.data-table-toolbar` y responsive basado en `data-label`.

- [ ] **Step 1: Add table regression assertions**

Añadir al quality gate comprobaciones de que no existe `min-width:1450px` y de que las tablas convertidas incluyen `data-label` en celdas de cuerpo.

- [ ] **Step 2: Run and verify RED**

```bash
php tests/project_quality.php
```
Expected: FAIL por ausencia del patrón responsive.

- [ ] **Step 3: Implement table system**

Escritorio: ancho 100%, `table-layout:auto`, wrapping controlado. Tablet: esconder columnas `.data-table-secondary` cuando sea necesario. Móvil <=760px: ocultar `thead`, convertir `tr` en bloque y mostrar `td::before{content:attr(data-label)}`. No usar scroll horizontal como comportamiento principal.

- [ ] **Step 4: Run quality checks**

```bash
php tests/project_quality.php
```
Expected: PASS del componente global; vistas objetivo todavía pendientes.

- [ ] **Step 5: Commit**

```bash
git add tests/project_quality.php public/assets/css/components.css public/assets/css/management.css public/assets/css/layout-density-v25.css
git commit -m "UI: estandarizar tablas responsive"
```

### Task 4: Convertir Usuarios y Auditoría a tablas reales

**Files:**
- Modify: `app/Views/admin/users.php`
- Modify: `app/Views/admin/audit.php`

**Interfaces:**
- Usuarios conserva alta, asignación, retiro de acceso y edición expandible.
- Auditoría conserva filtros, paginación, fechas, actor, acción, entidad y detalle.

- [ ] **Step 1: Run table view gate and confirm RED**

```bash
php tests/project_quality.php
```
Expected: FAIL para `admin/users.php` y `admin/audit.php`.

- [ ] **Step 2: Convert Users markup**

Tabla con columnas: Usuario, Correo, Perfil, Ubicación, Puesto, Estado, Acciones. Cada `<td>` debe incluir `data-label`. La edición secundaria permanece en fila expandible o `<details>` dentro de Acciones.

- [ ] **Step 3: Convert Audit markup**

Tabla con columnas: Fecha, Actor, Acción, Entidad, Identificador, Detalle. Mantener paginación existente y filtros fuera de la tabla.

- [ ] **Step 4: Run checks**

```bash
php tests/project_quality.php
php tests/static_checks.php
```
Expected: gates de Usuarios/Auditoría PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Views/admin/users.php app/Views/admin/audit.php
git commit -m "UI: convertir usuarios y auditoria en tablas"
```

### Task 5: Convertir Equipo de soporte y Proveedores

**Files:**
- Modify: `app/Views/management/support_team.php`
- Modify: `app/Views/admin/externals.php`
- Modify: `app/Views/management/external_report.php`

**Interfaces:**
- Equipo conserva KPIs y exportación XLSX.
- Proveedores conserva registrar proveedor, compartir caso, permisos y revocar acceso.
- Historial conserva filtros y descarga XLSX.

- [ ] **Step 1: Confirm RED**

```bash
php tests/project_quality.php
```
Expected: FAIL para las vistas objetivo que aún no sean tablas semánticas.

- [ ] **Step 2: Convert Support Team**

Tabla: Integrante, Perfil/área, Activos, En proceso, En espera, Resueltos 30 días, Primera respuesta, Resolución promedio, NPS, Último acceso.

- [ ] **Step 3: Convert External directory and active accesses**

Directorio: Proveedor, Empresa/servicio, Contacto, Correo/Teléfono, Casos activos. Casos compartidos: Ticket, Proveedor, Permisos, Desde, Acción.

- [ ] **Step 4: Normalize External Report table**

Usar el mismo componente global para Proveedor, Ticket, Asignado, Estado, Participación, Respuestas/archivos y Permisos. Conservar filtros y XLSX.

- [ ] **Step 5: Run checks and commit**

```bash
php tests/project_quality.php
php tests/static_checks.php
git add app/Views/management/support_team.php app/Views/admin/externals.php app/Views/management/external_report.php
git commit -m "UI: normalizar equipo y proveedores como tablas"
```

### Task 6: Normalizar todas las tablas ya existentes

**Files:**
- Modify: `app/Views/tickets/queue.php`
- Modify: `app/Views/admin/mail.php`
- Modify: `app/Views/problems/index.php`
- Modify: `app/Views/management/dashboard.php`
- Modify: `app/Views/management/reports.php`

**Interfaces:**
- Mantener filtros, acciones, rutas y datos actuales.
- Aplicar clases del componente global y `data-label` donde corresponda.

- [ ] **Step 1: Remove local table hacks**

Eliminar clases o inline styles que reintroduzcan anchos mínimos, nowrap indiscriminado o scroll horizontal obligatorio.

- [ ] **Step 2: Apply unified markup**

Agregar `.data-table-shell` / `.data-table` y etiquetas responsive sin cambiar columnas funcionales.

- [ ] **Step 3: Run checks**

```bash
php tests/project_quality.php
php tests/static_checks.php
```
Expected: PASS.

- [ ] **Step 4: Commit**

```bash
git add app/Views/tickets/queue.php app/Views/admin/mail.php app/Views/problems/index.php app/Views/management/dashboard.php app/Views/management/reports.php
git commit -m "UI: unificar tablas operativas existentes"
```

### Task 7: Pasada global de copy y densidad

**Files:**
- Modify: `app/Views/dashboard/index.php`
- Modify: `app/Views/management/dashboard.php`
- Modify: `app/Views/tickets/show.php`
- Modify: `app/Views/tickets/show_external.php`
- Modify: `app/Views/admin/externals.php`
- Modify: `app/Views/auth/login.php`
- Modify: `app/Views/auth/otp.php`
- Modify: `app/Views/auth/register.php`
- Modify: `app/Views/tickets/public_create.php`
- Modify: `app/Views/tickets/feedback.php`
- Modify: `app/Views/problems/index.php`
- Modify: `app/Views/knowledge/index.php`
- Modify: `app/Views/search/index.php`

**Interfaces:**
- Copy visible únicamente; no cambia controladores ni datos.

- [ ] **Step 1: Identify duplicate helper copy**

Eliminar subtítulos que repitan literalmente el encabezado, instrucciones del tipo “desde aquí puedes…”, “consulta…”, “revisa…” cuando la acción sea evidente, y explicaciones extensas ya presentes en `help_widget.php` o `help/manual.php`.

- [ ] **Step 2: Preserve critical copy**

Conservar validaciones, consecuencias de acciones, privacidad de conversación interna/proveedor, vencimiento OTP, límites de archivo y textos necesarios para evitar errores del usuario.

- [ ] **Step 3: Compact empty states**

Estados vacíos deben usar una frase corta y una acción si aplica; no reservar alturas artificiales.

- [ ] **Step 4: Run syntax/quality checks**

```bash
php tests/project_quality.php
php tests/static_checks.php
```
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Views
git commit -m "UI: reducir copy repetitivo y compactar modulos"
```

### Task 8: Verificación integral y cierre

**Files:**
- Modify if needed: `tests/project_quality.php`
- Modify if needed: `docs/ESTANDAR_VISUAL_CARROUSEL.md`

**Interfaces:**
- Produce una rama lista para revisión contra `v2-rebuild`.

- [ ] **Step 1: Run complete automated suite**

```bash
php tests/static_checks.php
php tests/project_quality.php
php tests/xlsx_smoke.php
```
Expected: todos `[OK]`, exit code 0.

- [ ] **Step 2: Run PHP and JS syntax**

```bash
for file in $(find app config public tests tools -type f -name '*.php'); do php -l "$file" || exit 1; done
for file in $(find public/assets/js -type f -name '*.js'); do node --check "$file" || exit 1; done
```
Expected: sin errores.

- [ ] **Step 3: Manual responsive matrix**

Verificar: escritorio 1920, laptop 1366, iPad/tablet ~1024/768 y móvil <=760. Revisar Inicio, Centro de soporte, Mis casos, Equipo, Informes, Usuarios, Proveedores, Auditoría, Correo, Problemas, Conocimiento, Buscar y detalle de ticket.

- [ ] **Step 4: Verify no database changes**

```bash
git diff v2-rebuild...HEAD -- database/
```
Expected: sin salida.

- [ ] **Step 5: Final commit only if verification changed files**

```bash
git add tests/project_quality.php docs/ESTANDAR_VISUAL_CARROUSEL.md
git commit -m "Docs: fijar estandar UI normalizado"
```
