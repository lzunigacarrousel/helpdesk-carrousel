# Fase 7 — Proveedores

Fecha: 2026-09-15

Rama: `fase7-proveedores`

## Objetivo

Madurar la participación de proveedores externos sin reconstruir el flujo existente y sin introducir cambios estructurales de base de datos.

La fase debe reutilizar `external_ticket_access`, `ticket_events`, `ticket_comments`, `ticket_work_reports`, `ticket_attachments`, perfiles externos y permisos actuales. La prioridad es obtener métricas confiables por ciclo de participación y mejorar el informe existente de proveedores.

## Alcance aprobado

La Fase 7 se limita estrictamente a:

- asignación y duración de participación;
- primera respuesta del proveedor;
- tiempo hasta primera respuesta;
- actividad actual;
- tiempo de trabajo declarado;
- respuestas, archivos e informes técnicos;
- entregas `READY_FOR_REVIEW`;
- devoluciones/reaperturas asociadas a una entrega del proveedor;
- consolidación de estas métricas en el informe y XLSX de proveedores;
- preservación del aislamiento EXTERNAL/INTERNAL.

Quedan fuera de esta fase:

- rediseño global del chat;
- rediseño del dashboard externo;
- rediseño de correos/notificaciones fuera de ajustes mínimos necesarios;
- valoración IT → proveedor de la Fase 8;
- cambios estructurales de BD;
- nuevos permisos.

## Decisiones funcionales aprobadas

### Ciclo de participación

Cada `EXTERNAL_GRANTED → EXTERNAL_REVOKED` constituye un ciclo independiente.

Si el acceso sigue activo, el ciclo termina en el momento actual para efectos de duración.

Si el mismo proveedor recibe nuevamente acceso al mismo ticket, la nueva asignación crea un nuevo ciclo con métricas independientes.

### Primera respuesta

La primera respuesta del proveedor es el evento más temprano, dentro del ciclo, entre:

1. primer comentario del proveedor con visibilidad `EXTERNAL`;
2. primer `ticket_work_report` creado por ese proveedor.

Si ambos existen, se toma el más antiguo.

El origen debe exponerse como `Mensaje` o `Informe técnico`.

No cuentan mensajes internos, notas internas, acciones de otros proveedores ni actividad fuera del ciclo.

### Tiempo hasta primera respuesta

Se calcula desde `granted_at` hasta la primera respuesta del ciclo.

Si no existe respuesta, el resultado es `Sin respuesta`; no se inventa una duración.

### Duración de participación

Se calcula desde `granted_at` hasta `revoked_at`, o hasta el momento actual si el ciclo sigue activo.

Esta métrica es distinta de `time_spent_minutes`.

### Tiempo de trabajo declarado

Se suma `time_spent_minutes` de los informes técnicos del proveedor dentro del ciclo.

No se mezcla con la duración de participación.

### Actividad actual

Se toma el `work_status` del último informe técnico del proveedor dentro del ciclo.

Si no existe informe, se muestra `Sin actualización`.

Etiquetas esperadas:

- `ANALYSIS` → En análisis / diagnóstico;
- `WAITING_CARROUSEL` → Esperando información de Carrousel;
- `WAITING_THIRD_PARTY` → Esperando tercero / fabricante;
- `IN_PROGRESS` → En atención / trabajando;
- `VALIDATING` → En validación;
- `READY_FOR_REVIEW` → Listo para revisión de Carrousel;
- sin informe → Sin actualización.

### READY_FOR_REVIEW

`READY_FOR_REVIEW` es únicamente una señal del proveedor.

No debe cambiar el estado del ticket, resolverlo ni cerrarlo automáticamente. El técnico de Carrousel conserva el control del flujo.

### Entregas

Cada informe del proveedor con `work_status = READY_FOR_REVIEW` cuenta como una entrega lista para revisión dentro de su ciclo.

### Devoluciones / reaperturas

Una devolución atribuible al proveedor se cuenta únicamente cuando:

1. el proveedor registra `READY_FOR_REVIEW` dentro del ciclo;
2. posteriormente el ticket vuelve a atención o reapertura.

Una reapertura anterior a la entrega no cuenta.

Una reapertura fuera del ciclo no cuenta.

La implementación debe utilizar el historial existente de `ticket_events`, especialmente eventos `STATUS_CHANGED`, y considerar cambios posteriores a la entrega hacia estados operativos como `REOPENED` o equivalentes que representen regreso a atención.

No se utilizarán simples mensajes de soporte como evidencia de devolución.

## Arquitectura

### ProviderParticipationService

Crear `app/Services/ProviderParticipationService.php` como única fuente de cálculo para las métricas de proveedores.

Responsabilidades:

- reconstruir ciclos desde eventos `EXTERNAL_GRANTED` y `EXTERNAL_REVOKED`;
- asociar cada ciclo con proveedor, ticket y actor que otorgó/revocó acceso;
- calcular duración;
- localizar primera respuesta y su origen;
- sumar tiempo declarado;
- obtener último `work_status`;
- contar respuestas, adjuntos e informes;
- contar entregas `READY_FOR_REVIEW`;
- contar devoluciones posteriores a entrega;
- devolver un dataset estable para pantalla y XLSX.

El servicio no debe escribir en BD.

### ExternalReportController

`ExternalReportController` debe dejar de contener la lógica detallada de reconstrucción de ciclos y pasar a consumir `ProviderParticipationService`.

El controlador conserva:

- autorización del informe;
- lectura y normalización de filtros;
- aplicación de filtros o delegación equivalente al servicio;
- preparación de resumen;
- render de pantalla;
- exportación XLSX.

Pantalla y exportación deben consumir exactamente las mismas métricas.

### WorkReportController

No se prevén cambios funcionales en `WorkReportController`.

Ya registra:

- autor externo;
- `work_status`;
- `time_spent_minutes`;
- `ready_for_review`;
- comentario externo asociado;
- evento `WORK_REPORT_ADDED`.

Si durante implementación se descubre que una métrica no puede derivarse de forma confiable con los datos actuales, la implementación se detiene antes de proponer cambios estructurales.

## Informe de proveedores

Se mantiene la ruta existente:

`/admin/externos/informe`

No se crea un dashboard paralelo.

### Resumen

Mostrar de forma compacta:

- Participaciones;
- Activas;
- Sin respuesta;
- Tiempo promedio de primera respuesta;
- Devoluciones.

Las métricas deben responder a los filtros aplicados.

### Filtros

Mantener o ampliar a:

- Buscar;
- Proveedor;
- Estado del ciclo: Todas / Activas / Finalizadas;
- Actividad actual;
- Desde;
- Hasta.

El filtro de actividad actual debe incluir:

- Todas;
- Sin actualización;
- En análisis / diagnóstico;
- Esperando información de Carrousel;
- Esperando tercero / fabricante;
- En atención / trabajando;
- En validación;
- Listo para revisión de Carrousel.

### Tabla

La tabla debe priorizar análisis operativo, no configuración administrativa.

Columnas principales:

1. Proveedor / Ticket
2. Asignación
3. Primera respuesta
4. Participación
5. Actividad actual
6. Trabajo
7. Resultado

Contenido esperado:

- Proveedor / Ticket: organización, contacto, ticket y asunto;
- Asignación: fecha de inicio y quién compartió el caso;
- Primera respuesta: fecha, tiempo transcurrido y origen;
- Participación: duración del ciclo;
- Actividad actual: último `work_status` o `Sin actualización`;
- Trabajo: tiempo declarado, informes, respuestas y archivos;
- Resultado: entregas listas para revisión, devoluciones y estado actual del ticket.

`can_comment` y `can_upload` dejan de ser columnas principales del informe. Siguen siendo parte de la gestión administrativa y no del análisis de rendimiento.

## XLSX

La exportación debe usar el mismo dataset y filtros que la pantalla.

Debe incluir, como mínimo:

- proveedor;
- contacto;
- correo;
- ticket;
- asunto;
- inicio del ciclo;
- otorgado por;
- fin del ciclo;
- revocado por;
- duración;
- primera respuesta;
- tiempo hasta primera respuesta;
- origen de primera respuesta;
- actividad actual;
- última actualización;
- tiempo declarado;
- respuestas;
- adjuntos;
- informes;
- entregas `READY_FOR_REVIEW`;
- devoluciones/reaperturas;
- estado actual del ticket;
- estado del ciclo.

## Seguridad y aislamiento

La fase no debe debilitar el modelo externo actual.

Reglas:

- usuarios EXTERNAL continúan viendo solo tickets con acceso activo;
- no se usan comentarios `INTERNAL` para métricas de respuesta del proveedor;
- informes técnicos se asocian por `author_user_id`, ticket y ventana temporal del ciclo;
- adjuntos externos se cuentan solo cuando pertenecen al proveedor y al ciclo;
- no se exponen notas internas en el informe;
- `READY_FOR_REVIEW` no cambia estado del ticket;
- no se agregan permisos nuevos;
- el informe conserva el control de acceso actual: ADMIN, SEMIADMIN, `external.manage` o `reports.view` según la lógica existente.

## Persistencia

Objetivo de BD: 0 cambios.

No se crean tablas ni columnas nuevas.

No se modifica el significado histórico de eventos existentes.

No se persistirán métricas derivadas.

## Manejo de casos límite

- ciclo activo sin respuesta → `Sin respuesta`;
- ciclo cerrado sin respuesta → `Sin respuesta`;
- comentario e informe en la misma ventana → gana el timestamp más temprano;
- múltiples informes → actividad actual = último informe del ciclo;
- múltiples `READY_FOR_REVIEW` → contar cada entrega;
- múltiples retornos posteriores a entregas → contar únicamente devoluciones respaldadas por historial de estado y dentro del ciclo;
- nuevo ciclo del mismo proveedor/ticket → métricas reiniciadas para ese ciclo;
- datos históricos incompletos → mostrar métricas disponibles sin inventar valores.

## Pruebas requeridas

Crear `tests/phase7_provider_participation_regression.php` y agregarlo al CI.

Cobertura mínima:

1. ciclo activo sin respuesta;
2. primera respuesta por mensaje;
3. primera respuesta por informe técnico;
4. mensaje + informe: elegir el más antiguo;
5. dos ciclos del mismo proveedor/ticket permanecen independientes;
6. duración de ciclo cerrado;
7. duración de ciclo activo;
8. suma de `time_spent_minutes` dentro del ciclo;
9. último `work_status` como actividad actual;
10. `READY_FOR_REVIEW` no cambia estado del ticket;
11. devolución posterior a `READY_FOR_REVIEW`;
12. reapertura anterior a la entrega no cuenta;
13. comentarios internos no cuentan como respuesta externa;
14. adjuntos externos se cuentan solo dentro del ciclo;
15. filtros por proveedor, estado, actividad y fechas;
16. resumen usa las filas filtradas;
17. XLSX usa el mismo dataset;
18. permisos actuales del informe se conservan;
19. sintaxis PHP de archivos modificados;
20. regresiones existentes relacionadas con externos, reportes y quality gates.

## Archivos previstos

Nuevo:

- `app/Services/ProviderParticipationService.php`
- `tests/phase7_provider_participation_regression.php`

Modificar:

- `app/Controllers/ExternalReportController.php`
- `app/Views/management/external_report.php`
- `.github/workflows/helpdesk-ci.yml`
- documentación de roadmap, manual, README o CHANGELOG cuando corresponda al cierre de fase.

No previsto:

- cambios de esquema SQL;
- cambios funcionales a `WorkReportController`;
- nuevos permisos;
- nuevas rutas principales;
- cambios de dashboard externo;
- Fase 8 de valoración IT → proveedor.

## Criterios de aceptación

La Fase 7 queda aceptada cuando:

- cada participación se calcula por ciclo independiente;
- primera respuesta utiliza mensaje o informe, el que ocurra primero;
- duración y tiempo declarado permanecen separados;
- actividad actual refleja el último informe del ciclo;
- `READY_FOR_REVIEW` no modifica el ticket;
- devoluciones se cuentan solo después de una entrega lista para revisión;
- pantalla y XLSX muestran cifras coherentes;
- filtros afectan resumen, tabla y exportación de forma consistente;
- no se exponen datos internos a externos;
- no hay cambios estructurales de BD;
- las regresiones de Fase 7 y las existentes relevantes pasan en PC TEST y CI.

## Stop conditions

Detener la implementación y pedir aprobación si:

- se requiere una tabla o columna nueva;
- se necesita un permiso nuevo;
- la métrica de devolución no puede derivarse inequívocamente de eventos actuales;
- se necesita cambiar el significado histórico de `ticket_events`;
- es necesario permitir que EXTERNAL cambie el estado del ticket;
- el alcance empieza a mezclarse con Fase 8, dashboard externo, rediseño global del chat o correos.
