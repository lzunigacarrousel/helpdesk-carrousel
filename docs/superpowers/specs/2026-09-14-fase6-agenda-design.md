# Fase 6 — Agenda — Diseño aprobado

**Fecha:** 2026-09-14
**Rama:** `fase6-agenda`
**Baseline:** `main @ c9a6f41`
**Estado:** Diseño funcional y técnico aprobado; pendiente plan de implementación.
**Roadmap:** Fase 6 de 12. Reutiliza obligatoriamente `ticket_activities` de Fase 5.

## 1. Objetivo

Incorporar una Agenda operativa global al Helpdesk Carrousel para consultar, ordenar y entender las actividades programadas o en curso sin crear un segundo sistema de actividades.

La Agenda debe permitir responder rápidamente:

- qué actividades hay hoy y esta semana;
- cuándo inicia y termina cada atención;
- quién es responsable;
- en qué parque se realizará;
- qué tipo de actividad es;
- a qué ticket pertenece;
- qué actividades están atrasadas;
- qué actividades del mismo responsable se traslapan;
- qué actividades históricas finalizaron o se cancelaron.

La Agenda es principalmente una vista de consulta y navegación. Las operaciones de Fase 5 —reprogramar, iniciar, finalizar y cancelar— continúan realizándose dentro del ticket en `#actividades`.

## 2. Principios de diseño

1. Agenda reutiliza `ticket_activities`; no crea una tabla de calendario.
2. Toda actividad continúa perteneciendo obligatoriamente a un ticket.
3. Agenda no duplica formularios ni reglas operativas de Fase 5.
4. El alcance se aplica en backend mediante `ScopeService`; nunca se confía en filtros del frontend.
5. El color comunica estado, no tipo de actividad.
6. Los conflictos de horario se advierten, pero no bloquean la programación.
7. `ATRASADA` es una condición de presentación, no un nuevo estado persistido.
8. La experiencia principal combina Calendario y Lista sobre la misma fuente de datos.
9. La UI debe ser útil en 1920, 1366, iPad horizontal, iPad vertical y móvil sin exigir scroll horizontal en móvil.
10. Gerencia y Supervisión pueden consultar, pero no operar actividades desde Agenda.
11. Solicitantes y colaboradores externos no pueden acceder a Agenda.
12. La funcionalidad esencial debe seguir siendo server-rendered; JavaScript solo mejora interacción y no se convierte en requisito para seguridad ni acceso.

## 3. Alcance y perfiles

### ADMIN

- Acceso a Agenda.
- Vista global.
- Puede usar filtros de responsable, parque, tipo, estado y rango.
- Puede usar `Programar actividad` para localizar un ticket y continuar en `#actividades`.

### SEMIADMIN

Mismo comportamiento operativo que ADMIN dentro de la política actual del sistema.

### TECHNICIAN

- Acceso a Agenda.
- Vista inicial: **Mis actividades**.
- Puede cambiar a **Todo mi alcance**.
- El backend aplica los scopes vigentes del técnico.
- Puede usar `Programar actividad` sobre tickets a los que realmente tiene acceso.

### MANAGEMENT

- Acceso de consulta.
- Vista global.
- Puede filtrar y abrir tickets autorizados para consulta.
- No ve controles para programar ni operar actividades.

### SUPERVISOR

- Acceso de consulta.
- Solo recibe actividades de tickets dentro de su región/parque/área según `ScopeService`.
- No ve controles para programar ni operar actividades.

### REQUESTER

Sin acceso a `/agenda`.

### EXTERNAL

Sin acceso a `/agenda`.

No se crea un permiso nuevo en Fase 6. Se reutilizan roles, helpers de autenticación y reglas de alcance existentes.

## 4. Arquitectura

Se crea un módulo de lectura separado de la lógica transaccional de Fase 5:

```text
GET /agenda
    |
    v
AgendaController
    |
    v
AgendaService
    |-- ticket_activities
    |-- tickets
    |-- users
    |-- parks
    `-- ScopeService
```

### `AgendaController`

Responsabilidades:

- validar que el perfil tenga acceso;
- normalizar parámetros GET;
- resolver vista, rango y filtros;
- definir el modo inicial `mine/all` según perfil;
- solicitar datos y catálogos a `AgendaService`;
- renderizar `app/Views/agenda/index.php`.

No ejecuta SQL directo ni cambia estados.

### `AgendaService`

Responsabilidades de solo lectura:

- consultar actividades visibles en un rango;
- aplicar `ScopeService` sobre el ticket relacionado;
- filtrar por responsable, parque, tipo y estado;
- calcular `is_overdue`;
- detectar conflictos de horario;
- devolver opciones de filtros limitadas por alcance;
- devolver actividades atrasadas activas anteriores al rango visible;
- localizar tickets visibles para el flujo `Programar actividad`.

No puede crear, reprogramar, iniciar, finalizar ni cancelar actividades.

### Fase 5 permanece como única capa operativa

`TicketActivityService` y `TicketActivityController` conservan las transiciones y validaciones de actividad. Agenda solo enlaza a:

```text
/tickets/view?id={ticket_id}#actividades
```

## 5. Ruta y parámetros GET

Ruta principal:

```text
GET /agenda
```

Parámetros soportados:

```text
view=calendar|list
from=YYYY-MM-DD
to=YYYY-MM-DD
responsible_user_id={id}
park_id={id}
activity_type={enum}
status=active|all|PROGRAMADA|EN_CURSO|FINALIZADA|CANCELADA
scope_mode=mine|all
history=0|1
program=0|1
ticket_q={texto para localizar ticket al programar}
```

Todos los parámetros son opcionales y se normalizan en servidor.

Reglas:

- valores enum desconocidos se ignoran;
- IDs no positivos se ignoran;
- fechas inválidas usan el rango por defecto correspondiente;
- `from > to` no se acepta y vuelve al rango por defecto con aviso visible;
- rango personalizado máximo: **90 días**;
- consultas SQL siempre parametrizadas;
- con `history=0`, el dominio efectivo queda limitado a `PROGRAMADA` y `EN_CURSO` aunque llegue un estado histórico por URL;
- con `history=1`, se permiten los cuatro estados;
- el acceso rápido **Historial** usa `history=1&status=all`.

## 6. Rangos temporales

### Calendario

Sin `from/to` explícitos:

- inicio: lunes de la semana actual;
- fin: domingo de la semana actual.

Navegación:

- `Semana anterior`;
- `Hoy`;
- `Semana siguiente`.

### Lista

Sin `from/to` explícitos:

- hoy;
- siguientes 6 días;
- total: 7 días calendario.

### Accesos rápidos

- Hoy;
- Esta semana;
- Próximos 30 días;
- Historial;
- rango personalizado.

Cuando el usuario ya estableció un rango explícito, cambiar entre Calendario y Lista conserva ese rango.

## 7. Estados visibles

Por defecto Agenda muestra únicamente:

- `PROGRAMADA`;
- `EN_CURSO`.

Al activar Historial se habilitan además:

- `FINALIZADA`;
- `CANCELADA`.

`history=1&status=all` muestra los cuatro estados dentro del rango. No se crea ningún estado adicional en BD.

## 8. Actividades atrasadas

Una actividad se considera atrasada para Agenda cuando:

```text
status = PROGRAMADA
AND scheduled_end_at < NOW()
```

Agenda agrega:

```text
is_overdue = true
```

sin modificar `ticket_activities.status`.

Las actividades atrasadas activas anteriores al inicio del rango visible no se pierden:

- en Calendario aparecen en una banda/resumen **Pendientes atrasadas** sobre la cuadrícula semanal;
- en Lista aparecen en un bloque **Pendientes atrasadas** antes de los grupos por fecha.

No se intentan posicionar dentro de un día que no pertenece al rango actual.

## 9. Conflictos de horario

Se detectan en memoria/servicio y no se persisten.

Dos actividades tienen conflicto si pertenecen al mismo `responsible_user_id` y sus intervalos se traslapan:

```text
start_A < end_B
AND
end_A > start_B
```

Reglas:

- se evalúan actividades activas visibles (`PROGRAMADA` / `EN_CURSO`);
- el conflicto se marca en ambas actividades;
- no se evalúan participantes secundarios para Fase 6;
- dos intervalos que solo se tocan en el límite (`end_A = start_B`) no son conflicto;
- no se bloquea programación;
- no se crea tabla ni columna adicional.

Campo derivado:

```text
has_conflict = true|false
```

La UI muestra una advertencia discreta: `Conflicto de horario`.

## 10. Estructura de cada resultado

Cada actividad entregada a la vista debe contener como mínimo:

```text
activity_id
ticket_id
ticket_code
ticket_subject
activity_type
status
scheduled_start_at
scheduled_end_at
responsible_user_id
responsible_name
park_id
park_name
is_overdue
has_conflict
ticket_url
```

El `ticket_url` siempre apunta al ticket y al bloque de actividades.

No se exponen notas internas, preparación, resultados técnicos o datos que Agenda no necesita.

## 11. Filtros

Barra compartida por Calendario y Lista:

- Responsable;
- Parque;
- Tipo;
- Estado;
- Rango.

### Responsable

TECHNICIAN inicia con `scope_mode=mine`.

Puede cambiar a `scope_mode=all`, pero el backend mantiene el alcance real.

ADMIN, SEMIADMIN, MANAGEMENT y SUPERVISOR inician con todo lo visible según su alcance.

Seleccionar un responsable específico nunca amplía el scope del usuario autenticado.

### Parque

Solo se ofrecen parques presentes en tickets/actividades visibles para el usuario o válidos dentro de su alcance.

### Tipo

Catálogo de Fase 5:

- Visita en sitio;
- Soporte remoto;
- Seguimiento;
- Intervención de proveedor;
- Otra atención.

### Estado

La UI usa etiquetas humanas y no obliga al usuario a conocer los códigos internos.

## 12. Vista Calendario

### Escritorio e iPad horizontal

Calendario semanal lunes-domingo con eje horario.

Cada actividad muestra de forma compacta:

```text
09:00–10:00
Seguimiento
HD-2026-000145
Andaria
Luis Fernando Zuniga
```

El bloque completo es enlazable al ticket.

### Rango horario de la cuadrícula

Para evitar grandes áreas vacías y a la vez no ocultar actividades:

- rango base sin actividades: **08:00–18:00**;
- con actividades, inicio visible = mínimo entre `08:00` y una hora antes del inicio más temprano, redondeado a hora completa;
- fin visible = máximo entre `18:00` y una hora después del fin más tardío, redondeado a hora completa;
- límites absolutos: `00:00–24:00`;
- ninguna actividad queda oculta por ocurrir temprano o tarde.

No se pretende replicar toda la complejidad de Google Calendar u Outlook.

### Semántica visual

- `PROGRAMADA`: azul suave;
- `EN_CURSO`: verde suave;
- `is_overdue`: ámbar/rojo suave;
- `FINALIZADA`: neutro, solo en historial;
- `CANCELADA`: neutro tenue, solo en historial.

El tipo se comunica con texto/icono, no mediante un segundo sistema de colores.

## 13. Vista Lista

Agrupación cronológica por día:

```text
HOY · 14 SEP
09:00  Seguimiento · HD-...
11:30  Visita en sitio · HD-...

MAÑANA · 15 SEP
08:00  Soporte remoto · HD-...
```

Cada tarjeta/fila muestra:

- hora;
- tipo;
- ticket;
- responsable;
- parque;
- estado;
- conflicto si existe.

El bloque `Pendientes atrasadas` aparece antes de los días del rango.

## 14. Responsive

### >= 1024 px

- Calendario horario completo.
- Filtros en una barra compacta.
- Lista disponible mediante selector de vista.

### iPad horizontal

- Conserva calendario horario.
- Tarjetas más compactas.
- No se trata automáticamente como móvil.

### iPad vertical

La vista Calendario cambia visualmente a tarjetas agrupadas por día; no intenta comprimir siete columnas horarias ilegibles.

### <= 760 px

- una columna;
- tarjetas por día;
- filtros apilables;
- sin scroll horizontal obligatorio;
- controles táctiles cómodos;
- Lista sigue disponible.

La adaptación se resuelve principalmente con CSS/media queries. La seguridad y los datos no dependen del viewport.

## 15. Selector Calendario / Lista

La cabecera muestra:

```text
[ Calendario ] [ Lista ]
```

Predeterminado:

- escritorio / iPad horizontal: Calendario;
- iPad vertical / móvil: Calendario en representación por día, con Lista disponible.

No se requieren dos consultas diferentes de dominio: ambas vistas consumen la misma colección filtrada.

## 16. Programar actividad desde Agenda

Agenda no crea actividades sueltas.

Para ADMIN, SEMIADMIN y TECHNICIAN se muestra:

```text
Programar actividad
```

El control activa `program=1` y abre un buscador/selector de tickets visibles para el usuario. La búsqueda utiliza `ticket_q`.

Reglas:

- búsqueda server-side y limitada;
- máximo 10 resultados por consulta;
- respeta `ScopeService`;
- muestra código, asunto y ubicación suficiente para distinguir casos;
- seleccionar un ticket redirige a `/tickets/view?id={id}#actividades`;
- no se duplica el formulario de Fase 5.

MANAGEMENT y SUPERVISOR no ven este control.

## 17. Navegación principal

### Equipo de soporte

Agenda aparece dentro de la sección Soporte junto a:

- Centro de soporte;
- Mis casos;
- Disponibles;
- Agenda.

### Gerencia / Supervisión

Agenda aparece como función de consulta dentro de Gestión.

### Solicitante / Externo

No aparece enlace de Agenda.

Ocultar el enlace no sustituye el gate del backend.

## 18. Seguridad y alcance

Agenda debe conservar las reglas existentes de `ScopeService`.

La consulta parte de `ticket_activities a` y `tickets t`, y la restricción organizacional se aplica sobre `t`.

Reglas mínimas:

- ADMIN / SEMIADMIN / MANAGEMENT: alcance global según la política existente;
- SUPERVISOR: región/parque/área asignada;
- TECHNICIAN: support scopes actuales, con compatibilidad global cuando no hay scopes configurados según la política vigente;
- REQUESTER / EXTERNAL: denegado.

Los catálogos de filtros también deben respetar alcance. No basta con filtrar la consulta principal si los selects exponen nombres de responsables o parques fuera del scope.

La Agenda no introduce nuevas acciones POST ni necesita CSRF para su lectura. El flujo de programación termina en la UI de Fase 5, donde las acciones POST continúan protegidas por CSRF y permisos.

## 19. Error handling

- perfil no autorizado: **HTTP 403** y vista amigable siguiendo el patrón de errores existente;
- rango inválido: volver al rango por defecto y mostrar aviso no técnico;
- filtro fuera de scope: ignorarlo/rechazarlo sin ampliar resultados;
- ticket desaparecido o sin acceso al abrirlo: usar el manejo existente del ticket;
- fallo de consulta: no exponer SQL ni stack trace al usuario;
- agenda vacía: mostrar estado vacío útil, no una cuadrícula enorme sin contexto.

## 20. Rendimiento

- consultas limitadas al rango visible;
- rango personalizado máximo de 90 días;
- búsqueda de tickets para programar limitada a 10 resultados;
- evitar N+1: responsable, parque y ticket se resuelven en la consulta principal;
- conflictos se calculan agrupando por responsable sobre el conjunto visible, no mediante una consulta por actividad;
- no incorporar frameworks JS ni librerías de calendario grandes.

Fase 12 podrá revisar índices si las mediciones reales demuestran necesidad; Fase 6 no añade índices por anticipación sin evidencia.

## 21. Archivos previstos

### Nuevos

```text
app/Controllers/AgendaController.php
app/Services/AgendaService.php
app/Views/agenda/index.php
public/assets/css/agenda.css
public/assets/js/agenda.js
tests/phase6_agenda_service_regression.php
tests/phase6_agenda_ui_regression.php
docs/superpowers/plans/2026-09-14-fase6-agenda-implementation.md
```

`agenda.js` debe mantenerse pequeño y progresivo; la página básica debe seguir funcionando sin depender de JS para seguridad o filtrado.

### Modificados de forma controlada

```text
public/index.php
app/Views/shared/app_start.php
app/Views/help/manual.php
README.md
CHANGELOG.md
docs/superpowers/plans/2026-09-10-helpdesk-functional-maturation-implementation.md
.github/workflows/helpdesk-ci.yml
```

### Sin cambios estructurales

```text
database/INSTALAR.sql
database/MIGRAR_*.sql
ticket_activities schema
```

Si durante implementación apareciera una necesidad estructural real de BD, se activa la stop condition y se solicita aprobación antes de modificar esquema.

## 22. Estrategia de pruebas

### Servicio

Validar al menos:

- consulta de `PROGRAMADA` y `EN_CURSO` por defecto;
- historial agrega `FINALIZADA` y `CANCELADA`;
- `mine` filtra por responsable autenticado;
- `all` no rompe ScopeService;
- filtros responsable/parque/tipo/estado;
- rango lunes-domingo;
- rango lista de 7 días;
- máximo personalizado de 90 días;
- `is_overdue` sin cambiar `status`;
- conflictos por responsable;
- no conflicto cuando intervalos solo se tocan en el límite;
- opciones de filtros limitadas por alcance;
- búsqueda de ticket limitada y scoped.

### Perfiles

- ADMIN: acceso global;
- SEMIADMIN: acceso permitido;
- TECHNICIAN: Mis actividades por defecto y Todo mi alcance opcional;
- MANAGEMENT: consulta sin controles operativos;
- SUPERVISOR: consulta según alcance;
- REQUESTER: denegado;
- EXTERNAL: denegado.

### UI

- selector Calendario / Lista;
- semana anterior / Hoy / siguiente;
- filtros conservan estado en GET;
- bloques enlazan a `#actividades`;
- semántica visual por estado;
- atrasadas visibles;
- conflicto visible;
- historial no se mezcla por defecto;
- estado vacío útil;
- botón Programar actividad solo para perfiles operativos autorizados.

### Responsive

Validación manual y gates razonables para:

- 1920x1080;
- 1366x768;
- iPad horizontal;
- iPad vertical;
- móvil <=760;
- tema claro;
- tema oscuro.

### Quality gates acumulados

```bat
C:\xampp\php\php.exe tests\static_checks.php
C:\xampp\php\php.exe tests\project_quality.php
C:\xampp\php\php.exe tests\xlsx_smoke.php
```

Además deben ejecutarse las pruebas específicas de Fase 5 para comprobar que Agenda no rompe la entidad que consume.

## 23. Orden de implementación

1. Pruebas RED del servicio y acceso.
2. `AgendaService`.
3. `AgendaController` y `GET /agenda`.
4. Vista Lista y filtros.
5. Calendario semanal desktop/iPad horizontal.
6. Adaptación iPad vertical/móvil.
7. Detección visual de atrasadas y conflictos.
8. Navegación principal.
9. Flujo `Programar actividad` hacia ticket.
10. Manual y documentación.
11. CI y quality gates.
12. Validación visual en PC TEST.

## 24. Criterios de aceptación

Fase 6 se considera funcionalmente cerrada cuando:

- `/agenda` usa exclusivamente `ticket_activities` como fuente de actividades;
- no existe tabla nueva de calendario;
- Calendario y Lista muestran el mismo dominio filtrado;
- TECHNICIAN abre por defecto en Mis actividades;
- alcance se aplica en backend;
- MANAGEMENT y SUPERVISOR pueden consultar sin operar;
- REQUESTER y EXTERNAL no acceden;
- PROGRAMADA y EN_CURSO son el dominio activo por defecto;
- Historial expone FINALIZADA y CANCELADA solo bajo solicitud explícita;
- atrasadas activas no desaparecen por quedar antes del rango;
- conflictos se identifican sin bloquear;
- cada actividad abre su ticket en `#actividades`;
- Programar actividad localiza un ticket y reutiliza Fase 5;
- claro/oscuro y tamaños objetivo son utilizables;
- las regresiones de Fase 5 siguen verdes;
- static checks, project quality y XLSX smoke siguen verdes;
- README, Manual, CHANGELOG y roadmap quedan actualizados.

## 25. No objetivos de Fase 6

No se implementa:

- drag & drop para reprogramar;
- creación directa de actividades desde celdas del calendario;
- WebSockets;
- sincronización con Google Calendar / Outlook;
- recordatorios externos nuevos;
- nueva tabla de calendario;
- nuevos estados de actividad;
- cambios automáticos del estado del ticket;
- asignación automática por disponibilidad;
- bloqueo de conflictos;
- agenda del proveedor externo;
- métricas de productividad o reportes históricos avanzados de actividades —eso corresponde a Fase 10.

## 26. Stop conditions

Detener implementación y pedir aprobación si:

- se requiere cambio estructural de BD;
- aparece la necesidad de un permiso nuevo;
- la Agenda necesita modificar actividades directamente para cumplir el flujo;
- se requiere debilitar o duplicar `ScopeService`;
- la UI necesita incorporar un framework/librería grande;
- una prueba crítica de Fase 5 falla de manera no explicada;
- el alcance empieza a mezclarse con Proveedores, Reportes u otra fase grande.

## 27. Resultado esperado

La experiencia debe responder de inmediato:

> ¿Qué atención tenemos pendiente, cuándo ocurre, dónde, quién la tiene y a qué caso pertenece?

Sin obligar al usuario a operar desde otra copia de los formularios.

La Agenda organiza el trabajo; el ticket continúa siendo el lugar donde el trabajo se ejecuta y documenta.
