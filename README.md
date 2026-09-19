# Helpdesk Carrousel 360

Aplicación interna de Service Desk / Helpdesk de Corporación Carrousel.

## Estado canónico

La rama estable y fuente de verdad es:

`main`

Las nuevas fases deben iniciar desde `main` en una rama temporal específica, validarse en PC TEST y solo después integrarse nuevamente a `main`.

El repositorio fue depurado antes de iniciar Fase 5. El trabajo histórico que no forma parte de la rama estable se conserva mediante tags:

- `archive/legacy-v1`
- `archive/v2-rebuild-20260913`

No usar esos tags como ramas de desarrollo. Son puntos de recuperación y consulta histórica.

## Bases de datos

Base activa del Helpdesk V2:

`carrousel_helpdesk`

Base histórica protegida:

`helpdesk_carrousel`

La base histórica no debe eliminarse, recrearse ni modificarse como parte de la instalación o actualización del Helpdesk V2.

TEST y Producción utilizan el mismo nombre lógico `carrousel_helpdesk`; la separación depende del equipo/servidor y de `config/local.php`.

## SQL vigente

La carpeta `database/` contiene el esquema canónico, verificadores y migraciones incrementales vigentes.

Archivos base:

- `INSTALAR.sql` — instalación limpia del esquema actual.
- `VERIFICAR_INSTALACION.sql` — valida una instalación limpia.
- `VERIFICAR_ESTABILIDAD_V2.sql` — diagnóstico de integridad en modo lectura.

Migraciones incrementales actualmente conservadas para instalaciones existentes:

- `MIGRAR_TICKET_WORK_REPORTS_20260912.sql`
- `MIGRAR_EXTERNAL_REPORT_TEMPLATES_20260912.sql`
- `MIGRAR_FASE5_ACTIVIDADES_20260913.sql`
- `MIGRAR_FASE9_CONOCIMIENTO_20260916.sql`

Verificadores asociados:

- `VERIFICAR_TICKET_WORK_REPORTS_20260912.sql`
- `VERIFICAR_EXTERNAL_REPORT_TEMPLATES_20260912.sql`
- `VERIFICAR_FASE5_ACTIVIDADES_20260913.sql`
- `VERIFICAR_FASE9_CONOCIMIENTO_20260916.sql`

Una instalación nueva debe construirse desde `database/INSTALAR.sql`. Las migraciones incrementales existen para actualizar una instalación previa sin reconstruirla.

## Instalación en PC TEST

Requisitos:

- XAMPP con Apache y MariaDB/MySQL.
- PHP de XAMPP.
- Composer.

Flujo recomendado:

1. Confirmar que el repositorio está limpio con `git status`.
2. Trabajar desde la rama de fase aprobada, creada desde `main`.
3. Confirmar `config/local.php` con `db_name => carrousel_helpdesk`.
4. Para instalación limpia, ejecutar `INSTALAR_PC_TEST.bat` y seguir sus confirmaciones.
5. Ejecutar los verificadores y pruebas relacionadas antes de considerar válida la instalación.

También puede validarse manualmente una instalación limpia sobre la base V2:

```bat
C:\xampp\mysql\bin\mysql.exe -u root -e "DROP DATABASE IF EXISTS carrousel_helpdesk;"
C:\xampp\mysql\bin\mysql.exe -u root --default-character-set=utf8mb4 < database\INSTALAR.sql
C:\xampp\mysql\bin\mysql.exe -u root --default-character-set=utf8mb4 < database\VERIFICAR_INSTALACION.sql
```

**Nunca ejecutar `DROP DATABASE helpdesk_carrousel` como parte de la instalación V2.**

## Configuración local

Copiar:

`config/local.php.example` → `config/local.php`

`config/local.php` no se versiona.

Valores base de PC TEST:

- `db_host`: `127.0.0.1`
- `db_name`: `carrousel_helpdesk`
- `db_user`: `root`
- `mail_mode`: `log`

Administrador principal:

`luis@carrousel.com.gt`

El acceso utiliza OTP, no contraseña permanente. En PC TEST con `mail_mode => 'log'`, el código queda registrado en:

`storage/logs/mail.log`

## Capacidades actuales

La base estable incluye, entre otras funciones:

- acceso OTP, sesiones, perfiles, permisos y auditoría;
- usuarios, jerarquía organizacional y alcances;
- tickets, clasificación ITSM, SLA y estados de espera;
- conversación pública, notas internas y adjuntos;
- resoluciones, confirmación del solicitante, reapertura y feedback;
- cola de soporte, búsqueda, informes y exportación XLSX;
- notificaciones internas y correo;
- problemas conocidos y conocimiento;
- proveedores/colaboradores externos y control de acceso por caso;
- documentación estructurada de trabajo externo por tipo de servicio;
- actividades operativas ligadas a tickets: visitas, soporte remoto, seguimientos e intervenciones de proveedor;
- Agenda con vistas Calendario y Lista sobre `ticket_activities`, respetando scope backend por perfil;
- manual y ayuda integrada.

Perfiles principales:

- `ADMIN`
- `SEMIADMIN`
- `TECHNICIAN`
- `MANAGEMENT`
- `SUPERVISOR`
- `REQUESTER`
- `EXTERNAL`

Gerencia y Supervisor son perfiles de consulta y seguimiento; no operan tickets como equipo de soporte.

## Roadmap funcional de 12 fases

El roadmap de maduración se mantiene como guía funcional. El estado canónico actual es:

| Fase | Tema | Estado |
|---|---|---|
| 1 | Baseline y documentación | Cerrada |
| 2 | UX del solicitante | Cerrada funcionalmente |
| 3 | Operación IT y SLA | Cerrada funcionalmente |
| 4 | Feedback del solicitante | Cerrada |
| 5 | Actividades / visitas | **Implementada — pendiente validación integral Fase 12** |
| 6 | Agenda | **Implementada — pendiente validación integral Fase 12** |
| 7 | Proveedores | **Implementada — pendiente validación integral Fase 12** |
| 8 | Calidad IT → proveedor | **Implementada — pendiente validación integral Fase 12** |
| 9 | Conocimiento | **Implementada — gate PC TEST GREEN; validación transversal final en Fase 12** |
| 10 | Reportes | **Implementada — pendiente validación integral Fase 12** |
| 11 | Manual | **Implementada — pendiente validación integral Fase 12** |
| 12 | Validación integral | **EN CURSO — cierre transversal y readiness** |

La validación responsive acumulada, revisión claro/oscuro y cierre transversal se consolidan en Fase 12 aunque las fases previas ya tengan validaciones parciales.

### Fase 5 — Actividades / visitas

Estado: **IMPLEMENTADA — pendiente validación integral Fase 12**.

La Fase 5 incorpora una entidad operativa reutilizable para trabajo ligado obligatoriamente a tickets:

- `ticket_activities` mantiene el estado actual de visitas, soporte remoto, seguimientos, intervenciones de proveedor y otras atenciones;
- `ticket_activity_participants` registra participantes y `ticket_attachments.activity_id` permite asociar evidencia sin crear almacenamiento paralelo;
- estados: programada, en curso, finalizada y cancelada;
- resultados: resuelta, parcial, sin resolver y requiere seguimiento;
- una reprogramación conserva la actividad y registra fecha anterior, nueva fecha, motivo y actor;
- las operaciones respetan permisos, scope, CSRF, auditoría y trazabilidad mediante `ticket_events`;
- crear, reprogramar, iniciar, finalizar o cancelar una actividad **no cambia automáticamente el estado del ticket**;
- el solicitante solo recibe el resumen publicado explícitamente por soporte mediante **Próxima atención**;
- La Agenda de Fase 6 reutiliza `ticket_activities` y no crea otra entidad de calendario.

### Fase 6 — Agenda

Estado: **IMPLEMENTADA — pendiente validación integral Fase 12**.

La Fase 6 incorpora una Agenda operativa sin crear tablas nuevas ni mover la operación fuera del ticket:

- vistas **Calendario** y **Lista** para consultar actividades programadas;
- backend filtrado por `ScopeService`, conservando alcance por perfil y evitando accesos REQUESTER/EXTERNAL;
- lectura de actividades activas e historial desde `ticket_activities`;
- atrasadas y conflictos derivados de fechas existentes, sin estados ni tablas paralelas;
- filtros por responsable, parque, tipo, estado y rango de fechas;
- MANAGEMENT y SUPERVISOR tienen consulta sin operar;
- el ticket sigue siendo el workspace operativo para programar, reprogramar, iniciar, finalizar o cancelar actividades.
### Fase 7 — Proveedores

Estado: **IMPLEMENTADA — pendiente validación integral Fase 12**.

La Fase 7 consolida la medición operativa de proveedores externos sobre la información que ya genera el Helpdesk:

- cada asignación `EXTERNAL_GRANTED → EXTERNAL_REVOKED` se trata como un ciclo independiente, incluso si el mismo proveedor vuelve a participar en el mismo ticket;
- la primera respuesta se calcula con el primer mensaje externo independiente o el primer informe técnico del proveedor dentro del ciclo;
- duración de participación y tiempo de trabajo declarado (`time_spent_minutes`) se mantienen como métricas separadas;
- la actividad actual proviene del último `work_status` del informe técnico y, si no existe informe, se muestra **Sin actualización**;
- `READY_FOR_REVIEW` / **Listo para revisión** es una señal del proveedor y no modifica automáticamente el estado del ticket;
- las devoluciones se cuentan únicamente cuando, después de una entrega lista para revisión, el ticket vuelve a `REOPENED` o `IN_PROGRESS` dentro del mismo ciclo;
- el **Informe de proveedores** y su exportación XLSX consumen el mismo dataset filtrado, con proveedor, estado del ciclo, actividad y rango de fechas;
- la administración de colaboradores y sus accesos permanece separada del informe operativo.

**BD: sin cambios.** La fase reutiliza `ticket_events`, `ticket_comments`, `ticket_attachments`, `ticket_work_reports` y el control de acceso externo existente; no crea tablas, columnas ni migraciones.
### Fase 8 — Calidad IT → proveedor

Estado: **IMPLEMENTADA — pendiente validación integral Fase 12**.

La Fase 8 incorpora una valoración interna de IT sobre cada ciclo finalizado de participación de proveedor, sin convertirla en ranking público ni mezclarla con la satisfacción del solicitante:

- escala interna de 1 a 5: 1 **Muy deficiente**, 2 **Deficiente**, 3 **Adecuado**, 4 **Bueno**, 5 **Excelente**;
- solo ciclos cerrados mediante `EXTERNAL_REVOKED` son evaluables; ciclos activos o cerrados implícitamente por una nueva asignación no muestran formulario de valoración;
- la primera valoración se registra como evento inmutable `PROVIDER_RATED`;
- una corrección se registra como un nuevo evento `PROVIDER_RATING_CORRECTED`, conservando el historial anterior;
- comentario obligatorio para 1–2 estrellas y para toda corrección;
- `Sin evaluar` no equivale a cero y no participa en promedios;
- solo ADMIN, SEMIADMIN y TECHNICIAN con scope válido pueden evaluar o corregir;
- proveedor y solicitante no ven score ni comentario interno;
- el Informe de proveedores y XLSX muestran valoración vigente, filtros y agregados de calidad por proveedor;
- esta valoración es independiente de `ticket_feedback.nps_score`, que corresponde al feedback del solicitante.

**BD: sin cambios.** La fase reutiliza `ticket_events` como fuente de verdad y no crea tablas, columnas, índices ni migraciones.

### Fase 9 — Conocimiento versionado

Estado: **IMPLEMENTADA — gate PC TEST GREEN; pendiente validación transversal Fase 12**.

La Fase 9 convierte Conocimiento en un flujo versionado y reutilizable:

- `knowledge_articles` conserva la identidad estable del artículo;
- `knowledge_revisions` conserva cada versión editorial;
- publicación para soporte y disponibilidad para solicitantes usan punteros independientes;
- TECHNICIAN puede crear/mejorar borradores; revisión y publicación usan permisos separados;
- restaurar una versión crea un borrador nuevo y no sobrescribe una publicación vigente;
- tickets resueltos pueden sugerir crear conocimiento cuando existe documentación útil;
- sugerencias internas permiten **Usar como referencia** sin resolver automáticamente el caso;
- autoservicio muestra hasta tres artículos públicos relacionados y nunca bloquea la creación del ticket;
- métricas de conocimiento registran sugerido, abierto y usado, mientras la efectividad se deriva del historial real de resolución/reapertura;
- búsqueda global, Problemas conocidos y Dashboard consumen también el modelo versionado.

Migración incremental:

`database/MIGRAR_FASE9_CONOCIMIENTO_20260916.sql`

Verificación:

`database/VERIFICAR_FASE9_CONOCIMIENTO_20260916.sql`

Gate local:

`VALIDAR_FASE9.bat`

**Producción no ha sido migrada a Fase 9. La migración debe validarse primero en PC TEST.**

### Fase 10 — Reportes consolidados

Estado: **IMPLEMENTADA — pendiente validación integral Fase 12**.

La Fase 10 consolida los informes existentes sin crear un sistema paralelo:

- `/gestion/informes` funciona como Centro de informes para Tickets/SLA, Agenda/Actividades, Proveedores, Equipo de soporte y Conocimiento según capacidad;
- `TicketReportFilterService` unifica período, parque, categoría/subcategorías, responsable, estado, prioridad, etiquetas y `ScopeService` entre pantalla y XLSX;
- los KPIs usan todo el conjunto filtrado; la tabla web limita únicamente el detalle visual a 500 registros y Excel conserva todo el filtro;
- Conocimiento aporta publicación, autoservicio y reutilización; Agenda aporta programadas/en curso/finalizadas/canceladas/atrasadas;
- Proveedores resume participación, respuesta, entregas, devoluciones y calidad sin duplicar el informe especializado;
- `SupportTeamReportService` unifica carga/desempeño del equipo y mantiene separada la administración de integrantes;
- informes especializados General, Proveedores y Equipo documentan período/filtros/alcance y reutilizan `XlsxExportService`;
- responsive conserva grids de escritorio/tablet y pasa a una columna clara en móvil pequeño;
- Gerencia y Supervisión continúan en modo consulta y no reciben acciones operativas desde Reportes;
- gate local `VALIDAR_FASE10.bat` y CI cubren hub, conocimiento, actividades, tickets/SLA, proveedores, equipo, exportaciones, UI y closeout.

**BD: sin cambios.** Fase 10 reutiliza el esquema existente y no requiere migración.

### Fase 11 — Manual consolidado e interactivo

Estado: **IMPLEMENTADA — pendiente validación integral Fase 12**.

La Fase 11 consolida la ayuda existente sin crear un sistema paralelo:

- `/manual` adapta perfil, accesos rápidos, contenido y FAQ a Solicitante, Técnico, Supervisor, Gerencia, Administrador/Semiadmin y Colaborador;
- búsqueda, filtros por tema e índice trabajan juntos y aceptan enlaces profundos mediante `topic`, `q` y anclas;
- la ayuda flotante conserva tres niveles máximos: **Iniciar tutorial**, **Abrir manual aquí** y **Preguntas de esta pantalla** cuando existe una FAQ útil;
- tickets, Agenda, Gestión, Reportes, Proveedores, Equipo, Problemas, Conocimiento, Usuarios, Correo, Auditoría y Búsqueda enlazan al contexto exacto del Manual;
- los tutoriales cubren siete preguntas: qué estás viendo, para qué sirve, qué hacer primero, qué puede esperar, qué ocurre después, errores comunes y dónde obtener más ayuda;
- Gerencia y Supervisión reciben orientación de consulta y seguimiento, sin instrucciones para operar tickets;
- Colaborador recibe únicamente el contexto necesario para casos compartidos y nunca instrucciones sobre notas internas;
- ayuda, Manual y tutoriales gestionan foco, teclado, `aria-live`, tamaño táctil, `prefers-reduced-motion`, claro/oscuro y responsive;
- tablet/iPad conserva composición de tablet; móvil pequeño apila filtros y acciones cuando ya no existe ancho útil;
- gate local `VALIDAR_FASE11.bat` y CI cubren perfil/navegación, contenido por perfil, FAQ contextual, tutoriales y accesibilidad.

**BD: sin cambios.** Fase 11 reutiliza vistas, permisos y servicios existentes y no requiere migración.

## Flujo Git oficial

Regla general:

```text
main = versión estable
rama de fase = desarrollo + pruebas
PC TEST = validación funcional y visual
merge a main = solo después de aprobación
```

Antes de iniciar una fase:

```bat
git switch main
git pull --ff-only origin main
git status
```

La rama de fase debe crearse desde un `main` limpio. No desarrollar directamente sobre tags históricos ni reutilizar ramas antiguas para nuevas fases.

## Calidad y seguridad

Antes de integrar una fase deben ejecutarse, como mínimo, los quality gates base:

```bat
C:\xampp\php\php.exe tests\static_checks.php
C:\xampp\php\php.exe tests\project_quality.php
C:\xampp\php\php.exe tests\xlsx_smoke.php
```

Además deben ejecutarse las regresiones específicas de la fase y, cuando corresponda, los verificadores SQL.

Cada fase debe preservar:

- permisos y scopes;
- CSRF;
- auditoría;
- separación INTERNAL / EXTERNAL;
- trazabilidad de tickets;
- adjuntos y evidencias;
- funcionamiento claro/oscuro;
- experiencia responsive relevante.

`.gitignore` excluye configuración local, logs, adjuntos, exportaciones, caché, sesiones, temporales, backups y `vendor/`.

## Documentación de referencia

- `docs/superpowers/specs/2026-09-10-helpdesk-functional-maturation-design.md`
- `docs/superpowers/plans/2026-09-10-helpdesk-functional-maturation-implementation.md`
- `docs/ESTANDAR_VISUAL_CARROUSEL.md`
- `CHANGELOG.md`

Algunas referencias históricas dentro de esos documentos pueden mencionar ramas ya archivadas. Para operación actual, este README y `main` son la referencia canónica.
