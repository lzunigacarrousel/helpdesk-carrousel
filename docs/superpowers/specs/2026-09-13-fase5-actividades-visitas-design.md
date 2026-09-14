# Fase 5 — Actividades / visitas — Diseño aprobado

**Fecha:** 2026-09-13  
**Rama:** `fase5-activities`  
**Baseline:** `main @ 855455f`  
**Estado:** Diseño funcional y técnico aprobado; pendiente plan de implementación.  
**Roadmap:** Fase 5 de 12. Fase 6 — Agenda dependerá de esta entidad y no debe rediseñarla.

## 1. Objetivo

Incorporar al Helpdesk Carrousel una entidad operativa única para registrar trabajo programado y ejecutado dentro de un ticket: visitas en sitio, soporte remoto, seguimiento, intervención de proveedor y otras actividades.

La fase debe permitir responder, desde el propio ticket:

- qué trabajo está programado;
- quién es responsable;
- quiénes participan;
- dónde y cuándo se realizará;
- si fue reprogramado o cancelado;
- cuándo inició y terminó realmente;
- qué se hizo y qué resultado tuvo;
- qué evidencias quedaron asociadas;
- qué información puede ver el solicitante;
- qué historial auditable dejó la operación.

Finalizar una actividad **no** resuelve ni cierra automáticamente el ticket.

## 2. Principios de diseño

1. Toda actividad pertenece obligatoriamente a un ticket.
2. Un ticket puede tener múltiples actividades, incluso simultáneas.
3. Existe una sola entidad `ticket_activities`; no se crean tablas separadas por tipo de actividad.
4. `ticket_events` continúa siendo la bitácora inmutable de cambios operativos.
5. La actividad conserva el estado actual; los eventos conservan cómo llegó a ese estado.
6. Los adjuntos continúan perteneciendo al ticket y pueden relacionarse opcionalmente con una actividad.
7. Actividades y estado del ticket son independientes. No existen cambios silenciosos del estado del ticket.
8. Carrousel mantiene control del ciclo operativo aun cuando intervenga un proveedor externo.
9. La información para el solicitante es interna por defecto y se publica solo mediante un resumen seguro explícito.
10. Fase 5 no implementa calendario/agenda global; Fase 6 consumirá esta estructura.

## 3. Tipos de actividad

`activity_type` admite:

- `VISITA_EN_SITIO`
- `SOPORTE_REMOTO`
- `SEGUIMIENTO`
- `INTERVENCION_PROVEEDOR`
- `OTRA`

El título mostrado en UI se genera a partir del tipo y del ticket; no se solicita un título libre adicional.

Ejemplo: `Visita en sitio · HD-000245`.

## 4. Estados y transiciones

`status` admite:

- `PROGRAMADA`
- `EN_CURSO`
- `FINALIZADA`
- `CANCELADA`

Transiciones permitidas:

```text
PROGRAMADA -> EN_CURSO -> FINALIZADA
     |            |
     +------------+-> CANCELADA
```

Reglas:

- `PROGRAMADA` puede reprogramarse, iniciarse o cancelarse.
- `EN_CURSO` puede finalizarse o cancelarse.
- `FINALIZADA` no se reabre ni se edita libremente.
- `CANCELADA` no vuelve a `PROGRAMADA`; si el trabajo debe retomarse se crea una nueva actividad.
- Correcciones administrativas posteriores deben ser explícitas y auditables; nunca se reescribe historia de forma silenciosa.

## 5. Resultado de una actividad

Al finalizar, `result_code` admite:

- `RESUELTA`
- `PARCIAL`
- `SIN_RESOLVER`
- `REQUIERE_SEGUIMIENTO`

Finalizar exige:

- `result_code`;
- `work_performed`;
- `result_summary`;
- `started_at`;
- `finished_at`.

`pending_items` es opcional.

Si el resultado es `REQUIERE_SEGUIMIENTO`, la UI puede sugerir programar otra actividad, pero nunca la crea automáticamente.

Si el resultado es `RESUELTA`, la UI puede sugerir registrar la solución del ticket, pero nunca modifica por sí sola el estado del ticket.

## 6. Programación y tiempos

Toda actividad programada requiere:

- `scheduled_start_at`;
- `scheduled_end_at`.

Regla básica:

`scheduled_start_at < scheduled_end_at`.

Al iniciar:

- `started_at = fecha/hora real de inicio`;
- `status = EN_CURSO`.

Al finalizar:

- `finished_at = fecha/hora real de finalización`;
- `status = FINALIZADA`.

Reglas:

- `finished_at >= started_at`;
- se permite registrar actividades históricas si el trabajo se documentó tarde;
- una fecha pasada debe producir advertencia y quedar auditada, no bloquearse de forma absoluta.

La separación entre tiempo programado y tiempo real permitirá en Fase 6 y reportes medir duración estimada, duración real, retraso de inicio y reprogramaciones.

## 7. Reprogramaciones

Reprogramar no crea un estado `REPROGRAMADA`.

La actividad permanece `PROGRAMADA`, se actualizan sus fechas vigentes y se genera un evento `ACTIVITY_RESCHEDULED` con, como mínimo:

- `activity_id`;
- fecha/hora anterior de inicio y fin;
- nueva fecha/hora de inicio y fin;
- motivo obligatorio;
- usuario que realizó el cambio;
- fecha/hora del cambio.

La historia anterior nunca se elimina.

## 8. Cancelaciones

Cancelar exige motivo obligatorio.

La actividad conserva:

- `cancel_reason`;
- `cancelled_at`;
- `cancelled_by`;
- `status = CANCELADA`.

También se genera un `ticket_event` auditable.

La actividad cancelada permanece visible en el historial del ticket.

## 9. Responsable, participantes y proveedor

### Responsable principal

Cada actividad requiere `responsible_user_id`.

Debe ser:

- usuario interno activo;
- operador de soporte autorizado;
- con acceso operativo al ticket según alcance vigente.

El responsable es el dueño operativo de la actividad.

### Participantes

Una actividad puede tener cero o más participantes internos en `ticket_activity_participants`.

Reglas:

- no se permiten duplicados;
- el responsable no necesita duplicarse como participante;
- pueden ser usuarios internos activos relacionados con la ejecución, aunque no todos sean operadores de soporte;
- ser participante no concede permisos para administrar la actividad;
- pueden agregarse o retirarse mientras la actividad no esté finalizada ni cancelada.

### Proveedor

`provider_user_id` es opcional salvo en `INTERVENCION_PROVEEDOR`, donde es obligatorio.

Debe referenciar un usuario `access_type = EXTERNAL` con acceso vigente al ticket mediante `external_ticket_access`. Si no existe acceso vigente, primero debe otorgarse usando el flujo de colaboración externa existente; la actividad no se crea con un proveedor sin acceso al caso.

El proveedor puede documentar su trabajo a través del flujo externo ya existente, pero no administra los estados de la actividad.

## 10. Ubicación

La actividad hereda por defecto `park_id` del ticket, pero soporte puede cambiarlo cuando corresponda.

Reglas:

- `VISITA_EN_SITIO`: `park_id` obligatorio e `is_remote = 0`.
- `SOPORTE_REMOTO`: `park_id` puede ser `NULL` e `is_remote = 1`.
- `SEGUIMIENTO`: `park_id` puede ser `NULL`; `is_remote` depende de la ejecución.
- `INTERVENCION_PROVEEDOR`: puede tener o no ubicación física; `is_remote` debe reflejar la modalidad real.
- `OTRA`: depende del caso.

`is_remote` distingue explícitamente trabajo sin ubicación física cuando el tipo por sí solo no sea suficiente.

## 11. Objetivo y creación

No se captura un título redundante.

Al crear una actividad se solicita:

### Obligatorio

- tipo de actividad;
- responsable principal;
- inicio programado;
- fin estimado;
- objetivo/motivo (`objective`);
- ubicación cuando aplique.

### Opcional

- participantes;
- proveedor involucrado;
- notas internas de preparación;
- visibilidad al solicitante.

## 12. Visibilidad al solicitante

`requester_visible` es `0` por defecto.

Si `requester_visible = 1`, `requester_summary` es obligatorio.

El solicitante puede ver únicamente un resumen operativo seguro, por ejemplo:

```text
Visita programada
16 de septiembre · 10:00–12:00
Tikal Futura
```

No ve:

- notas internas;
- participantes internos;
- proveedor involucrado;
- motivos internos de reprogramación;
- diagnóstico técnico;
- auditoría;
- metadatos internos.

Si una actividad visible cambia de fecha o se cancela, puede notificarse al solicitante usando únicamente texto seguro.

## 13. Independencia respecto al estado del ticket

Crear, reprogramar, iniciar, finalizar o cancelar una actividad **no cambia automáticamente** el estado del ticket.

Ejemplo:

- ticket: `IN_PROGRESS`;
- actividad: `VISITA_EN_SITIO`, `PROGRAMADA`.

El técnico puede decidir explícitamente poner el ticket en espera con el mecanismo actual, por ejemplo `PENDING` + `WAITING_VISIT`.

La UI puede ofrecer sugerencias contextuales, pero nunca automatismos ocultos.

## 14. Modelo de datos propuesto

### 14.1 `ticket_activities`

Tabla principal:

```text
id BIGINT UNSIGNED PK
ticket_id BIGINT UNSIGNED NOT NULL
activity_type ENUM(...) NOT NULL
status ENUM('PROGRAMADA','EN_CURSO','FINALIZADA','CANCELADA') NOT NULL DEFAULT 'PROGRAMADA'
responsible_user_id BIGINT UNSIGNED NOT NULL
provider_user_id BIGINT UNSIGNED NULL
park_id BIGINT UNSIGNED NULL
is_remote TINYINT(1) NOT NULL DEFAULT 0
objective TEXT NOT NULL
internal_preparation_notes TEXT NULL
scheduled_start_at DATETIME NOT NULL
scheduled_end_at DATETIME NOT NULL
started_at DATETIME NULL
finished_at DATETIME NULL
result_code ENUM('RESUELTA','PARCIAL','SIN_RESOLVER','REQUIERE_SEGUIMIENTO') NULL
work_performed TEXT NULL
result_summary TEXT NULL
pending_items TEXT NULL
requester_visible TINYINT(1) NOT NULL DEFAULT 0
requester_summary VARCHAR(500) NULL
cancel_reason VARCHAR(500) NULL
cancelled_at DATETIME NULL
cancelled_by BIGINT UNSIGNED NULL
created_by BIGINT UNSIGNED NOT NULL
created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
```

Relaciones:

- `ticket_id -> tickets.id`;
- `responsible_user_id -> users.id`;
- `provider_user_id -> users.id`;
- `park_id -> parks.id`;
- `cancelled_by -> users.id`;
- `created_by -> users.id`.

Índices previstos:

- `(ticket_id, status, scheduled_start_at)`;
- `(responsible_user_id, status, scheduled_start_at)`;
- `(park_id, status, scheduled_start_at)`;
- `(provider_user_id, status)`.

Estos índices se justifican por la operación del ticket y por Fase 6 — Agenda.

### 14.2 `ticket_activity_participants`

```text
activity_id BIGINT UNSIGNED NOT NULL
user_id BIGINT UNSIGNED NOT NULL
created_by BIGINT UNSIGNED NOT NULL
created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
PRIMARY KEY (activity_id, user_id)
```

Relaciones:

- `activity_id -> ticket_activities.id ON DELETE CASCADE`;
- `user_id -> users.id`;
- `created_by -> users.id`.

### 14.3 Evidencias

No se crea otra tabla de archivos.

`ticket_attachments` recibe:

```text
activity_id BIGINT UNSIGNED NULL
```

con FK hacia `ticket_activities.id ON DELETE SET NULL` y un índice apropiado. Esto preserva el adjunto del ticket incluso ante una eliminación administrativa excepcional de la actividad.

Todo adjunto sigue perteneciendo obligatoriamente a su ticket. La relación con actividad es opcional y el backend debe impedir asociar un adjunto de un ticket con una actividad de otro ticket.

## 15. Historial y eventos

No se crean tablas `ticket_activity_history`, `ticket_activity_reschedules` ni similares.

Se reutiliza `ticket_events`.

Eventos previstos:

- `ACTIVITY_CREATED`
- `ACTIVITY_RESCHEDULED`
- `ACTIVITY_STARTED`
- `ACTIVITY_COMPLETED`
- `ACTIVITY_CANCELLED`
- `ACTIVITY_PARTICIPANT_ADDED`
- `ACTIVITY_PARTICIPANT_REMOVED`
- `ACTIVITY_REQUESTER_VISIBILITY_CHANGED`

`metadata_json` contiene los datos necesarios para reconstruir el cambio, incluyendo `activity_id` y valores anteriores/nuevos cuando corresponda.

Cada operación importante también registra `Audit::log(...)`.

## 16. Permisos

Permisos funcionales propuestos:

- `activities.view`
- `activities.create`
- `activities.manage`
- `activities.cancel`

### ADMIN

Ver, crear, reprogramar, iniciar, finalizar y cancelar.

### SEMIADMIN

Mismas operaciones respetando alcance.

### TECHNICIAN

Puede operar actividades de tickets dentro de su alcance. Las reglas de backend determinan si puede modificar una actividad según ticket, responsable y permisos.

### MANAGEMENT

Solo lectura según alcance.

### SUPERVISOR

Solo lectura según alcance.

### REQUESTER

No recibe permisos internos de actividades. Ve únicamente el resumen seguro publicado en su ticket.

### EXTERNAL

No administra estados de actividades. Su participación continúa mediante el flujo externo autorizado.

## 17. UI dentro del ticket

La operación principal vive dentro del workspace del ticket.

### Sección `Actividades del caso`

Orden sugerido:

1. botón `+ Programar actividad`;
2. próximas/activas;
3. historial.

Cada tarjeta muestra, según permisos:

- tipo;
- estado;
- responsable;
- fecha/hora programada;
- ubicación;
- resultado cuando exista;
- acciones válidas para el estado actual.

### Programar

Formulario progresivo con:

- tipo;
- responsable;
- participantes;
- proveedor cuando aplique;
- ubicación;
- inicio;
- fin estimado;
- objetivo;
- visibilidad al solicitante;
- resumen visible cuando aplique.

### Iniciar

Registra `started_at`, cambia a `EN_CURSO`, crea evento y auditoría.

### Reprogramar

Solicita nueva fecha/hora de inicio, nuevo fin estimado y motivo obligatorio.

### Finalizar

Solicita resultado, qué se hizo, resultado obtenido, pendientes opcionales y evidencia opcional.

### Cancelar

Solicita motivo obligatorio y conserva la actividad en historial.

## 18. Experiencia del solicitante

Las actividades no agregan un módulo administrativo al solicitante.

Dentro del ticket se muestra, solo cuando corresponda, una tarjeta simple como:

```text
Próxima atención
Visita programada
16 de septiembre · 10:00–12:00
Tikal Futura
```

El solicitante no ve controles de gestión ni información interna.

## 19. Proveedores externos

Fase 5 no reemplaza ni duplica el módulo de documentación externa existente.

Cuando exista `INTERVENCION_PROVEEDOR`:

- Carrousel programa y administra la actividad;
- el proveedor ya debe tener acceso vigente al ticket;
- el proveedor documenta su trabajo en el flujo externo autorizado;
- Carrousel inicia/finaliza/cancela la actividad;
- el proveedor no resuelve ni cierra el ticket automáticamente.

## 20. Notificaciones

Notificar únicamente eventos con valor operativo:

- asignación de responsable;
- reprogramación;
- cancelación;
- incorporación de proveedor cuando corresponda;
- cambio de fecha de actividad visible al solicitante;
- finalización con `REQUIERE_SEGUIMIENTO`.

No enviar correo por cada microcambio interno.

Las notificaciones respetan la infraestructura y trazabilidad existentes.

## 21. Transacciones y consistencia

Operaciones sensibles deben ser transaccionales.

Ejemplo al finalizar:

```text
BEGIN
  validar permisos, scope y transición
  actualizar ticket_activities
  crear ticket_event
  registrar auditoría
  generar notificación cuando corresponda
COMMIT
```

Ante error:

`ROLLBACK`.

Las validaciones de estado, fechas, responsables y permisos viven en backend. JavaScript solo mejora UX.

## 22. Migración y esquema canónico

Fase 5 rompe el objetivo histórico de cero cambios de BD de forma deliberada y aprobada por el hard gate del roadmap.

La implementación deberá actualizar:

- `database/INSTALAR.sql`;
- `database/VERIFICAR_INSTALACION.sql`;
- `database/VERIFICAR_ESTABILIDAD_V2.sql`;
- CI/quality gates cuando corresponda.

Además debe incorporar una migración incremental idempotente para la BD TEST existente y su verificador específico, con nombres consistentes con la fecha/convención vigente del repositorio.

La migración debe:

- crear `ticket_activities`;
- crear `ticket_activity_participants`;
- agregar `ticket_attachments.activity_id`;
- crear FKs e índices;
- registrar `schema_migrations`;
- ser segura para datos existentes;
- no tocar la base histórica `helpdesk_carrousel`.

## 23. Pruebas mínimas obligatorias

Antes de implementar UI se crearán regresiones RED que cubran:

1. esquema canónico contiene actividades y participantes;
2. migración incremental segura/idempotente;
3. un ticket admite varias actividades;
4. creación exige ticket, responsable, tipo, objetivo e intervalo válido;
5. `VISITA_EN_SITIO` exige parque;
6. remoto/seguimiento permiten ubicación nula;
7. `INTERVENCION_PROVEEDOR` exige proveedor externo válido y con acceso vigente al ticket;
8. responsable debe ser operador interno autorizado;
9. participantes no se duplican y no adquieren permisos por participar;
10. transición `PROGRAMADA -> EN_CURSO -> FINALIZADA`;
11. transición inválida rechazada;
12. cancelación exige motivo;
13. reprogramación exige motivo y conserva valores anteriores en evento;
14. finalizar exige resultado, trabajo realizado y resultado obtenido;
15. `REQUIERE_SEGUIMIENTO` no crea actividad automáticamente;
16. finalizar actividad no cierra ticket;
17. ninguna operación cambia silenciosamente el estado del ticket;
18. `requester_visible = 1` exige resumen seguro;
19. solicitante no ve datos internos;
20. externo no administra estados;
21. scopes/permisos se aplican en backend;
22. CSRF en endpoints mutables;
23. evento y auditoría por operación;
24. adjunto solo puede vincularse a actividad del mismo ticket;
25. reglas `is_remote`/`park_id` son coherentes con el tipo;
26. comportamiento responsive y tema claro/oscuro no regresan.

También deben mantenerse verdes las regresiones existentes de ITSM, feedback, usuarios, proveedores, flujo externo, notificaciones y calidad del proyecto.

## 24. Fuera de alcance de Fase 5

No implementar en esta fase:

- calendario mensual/semanal;
- agenda global;
- drag & drop;
- disponibilidad automática de técnicos;
- recordatorios avanzados;
- rutas o geolocalización;
- check-in GPS;
- control de viáticos;
- inventario/repuestos;
- evaluación de calidad del proveedor;
- cierre automático del ticket;
- tablas separadas por tipo de actividad.

Estas exclusiones evitan mezclar Fase 5 con Fase 6 u otros módulos.

## 25. Criterios de aceptación

Fase 5 se considera funcionalmente completa cuando:

- un operador puede programar múltiples actividades dentro de un ticket;
- cada actividad tiene responsable, horario, objetivo y ubicación coherente;
- puede reprogramarse con historial auditable;
- puede iniciarse, finalizarse y cancelarse según transición válida;
- la finalización conserva resultado y documentación obligatoria;
- participantes y proveedor quedan correctamente relacionados;
- evidencias pueden asociarse a una actividad sin abandonar el ticket;
- el solicitante ve solo actividades publicadas y texto seguro;
- el proveedor no controla estados internos;
- actividades no alteran automáticamente el estado del ticket;
- eventos, auditoría, permisos, scope y CSRF están cubiertos;
- la instalación limpia y la migración incremental pasan sus verificadores;
- las pruebas nuevas y las regresiones existentes quedan verdes;
- la UI funciona en escritorio, 1366, iPad/tablet y móvil, claro/oscuro;
- Fase 6 puede consultar la misma entidad por responsable, parque, tipo, estado y rango de fechas sin introducir otra tabla de agenda.

## 26. Decisión arquitectónica final

Se adopta el enfoque **actividad normalizada + participantes + eventos existentes**:

- estado vigente en `ticket_activities`;
- participantes en `ticket_activity_participants`;
- historial en `ticket_events`;
- archivos en `ticket_attachments` con relación opcional a actividad;
- proveedor reutilizando usuarios externos y colaboración existente.

Este diseño añade únicamente la estructura necesaria para operación y futura Agenda, sin convertir Helpdesk Carrousel en un gestor de proyectos ni duplicar subsistemas ya existentes.
