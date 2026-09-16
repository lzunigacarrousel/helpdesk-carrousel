# Helpdesk Carrousel — Fase 8: Calidad IT → proveedor

Fecha: 2026-09-15
Rama: `fase8-calidad-proveedor`
Estado: diseño aprobado

## Objetivo

Incorporar una valoración interna de IT hacia proveedores externos, separada conceptual y estadísticamente de la satisfacción del solicitante hacia IT.

La métrica debe quedar ligada a un ciclo concreto de participación del proveedor, ser inmutable, auditable y utilizable en el Informe de proveedores, sin permitir que el proveedor cierre técnicamente el ticket ni alterar el flujo de estados del caso.

## Decisiones aprobadas

- La evaluación pertenece a un ciclo concreto de participación del proveedor.
- Solo puede registrarse después de que ese ciclo termine con `EXTERNAL_REVOKED`.
- Si el mismo proveedor participa varias veces en un ticket, cada ciclo se evalúa de forma independiente.
- Escala general única de 1 a 5.
- Etiquetas de la escala:
  - 1 = Muy deficiente
  - 2 = Deficiente
  - 3 = Adecuado
  - 4 = Bueno
  - 5 = Excelente
- Comentario opcional en 3, 4 y 5 estrellas.
- Comentario obligatorio en 1 y 2 estrellas.
- Toda corrección requiere comentario.
- La evaluación original no se edita ni se elimina.
- Una corrección crea un nuevo evento inmutable.
- La última evaluación/corrección válida del ciclo es la valoración vigente.
- Pueden evaluar o corregir `ADMIN`, `SEMIADMIN` y `TECHNICIAN`, siempre que tengan scope válido sobre el ticket.
- La evaluación y sus comentarios son internos. El proveedor no los ve.
- La captura se realiza dentro del ticket, en el bloque de participación del proveedor.
- El Informe de proveedores consume la valoración pero no será un segundo punto de captura.
- La valoración es opcional y no bloquea revocación, resolución ni cierre del ticket.
- En reportes, `Sin evaluar` no equivale a cero y no participa en promedios.
- El informe mostrará promedio vigente por proveedor, ciclos evaluados, ciclos sin evaluar y la valoración vigente de cada ciclo.

## Enfoque técnico aprobado

### Fuente de verdad

Usar `ticket_events` como fuente de verdad de la valoración.

No se crea una tabla nueva en Fase 8. La necesidad queda cubierta por eventos inmutables asociados al ticket y al ciclo exacto del proveedor.

El esquema actual ya permite esta extensión sin cambio estructural: `ticket_events.event_type` es `VARCHAR(80)` y `metadata_json` es `LONGTEXT`, por lo que los nuevos tipos de evento y su payload caben en la estructura vigente.

### Identidad del ciclo

Cada evaluación referencia de forma inequívoca:

- `ticket_id`
- `external_user_id`
- `grant_event_id`: ID del evento `EXTERNAL_GRANTED` que abrió el ciclo

El `grant_event_id` es la identidad funcional del ciclo para efectos de valoración.

## Eventos funcionales

### PROVIDER_RATED

Se registra la primera valoración de un ciclo.

Campos conceptuales:

- `event_type = PROVIDER_RATED`
- `ticket_id`
- `actor_user_id`: usuario interno que evaluó
- `metadata_json`:
  - `external_user_id`
  - `grant_event_id`
  - `score` entero 1..5
  - `comment` string opcional/obligatorio según regla

### PROVIDER_RATING_CORRECTED

Se registra una corrección sin modificar el evento previo.

Campos conceptuales:

- `event_type = PROVIDER_RATING_CORRECTED`
- `ticket_id`
- `actor_user_id`
- `metadata_json`:
  - `external_user_id`
  - `grant_event_id`
  - `score` entero 1..5
  - `comment` obligatorio
  - `corrected_rating_event_id`: ID del evento de valoración vigente que se está corrigiendo

La corrección no reemplaza físicamente el evento previo. La lectura funcional toma el último evento válido del ciclo como vigente.

## Reglas de negocio

### Elegibilidad del ciclo

Un ciclo es evaluable únicamente cuando:

1. existe un `EXTERNAL_GRANTED` válido;
2. existe su cierre correspondiente por `EXTERNAL_REVOKED`;
3. el actor es usuario interno con rol permitido;
4. el actor tiene scope sobre el ticket;
5. `score` está entre 1 y 5;
6. si `score` es 1 o 2, existe comentario no vacío;
7. si es corrección, existe comentario no vacío y referencia a una valoración previa del mismo ciclo.

No se permite valorar un ciclo activo.

### Inmutabilidad

- No existe operación de UPDATE/DELETE de valoración.
- La primera valoración genera `PROVIDER_RATED`.
- Una corrección genera `PROVIDER_RATING_CORRECTED`.
- El historial conserva actor, fecha, score y comentario de cada versión.

### Valoración vigente

Para un `grant_event_id`, ordenar `PROVIDER_RATED` y `PROVIDER_RATING_CORRECTED` por `created_at` y `id`.

La última entrada válida es la vigente.

Los reportes usan únicamente la valoración vigente de cada ciclo.

## Autorización y seguridad

### Roles permitidos

- `ADMIN`
- `SEMIADMIN`
- `TECHNICIAN`

Además del rol, se exige autorización de backend mediante el scope existente del ticket.

### Roles/perfiles no permitidos

- `REQUESTER`
- `EXTERNAL`
- perfiles de consulta que no tengan capacidad operativa

No se confía en ocultar botones como mecanismo de seguridad.

### Visibilidad

La valoración y el comentario son internos.

No deben exponerse en:

- vista externa del ticket;
- payloads o componentes dirigidos a proveedor;
- notificaciones del proveedor;
- respuestas públicas al solicitante.

## Componentes previstos

### ProviderRatingService

Responsabilidad única: reglas, lectura y registro de valoraciones de proveedor.

Funciones conceptuales:

- reconstruir valoración vigente por ciclo;
- devolver historial de valoraciones/correcciones;
- validar elegibilidad del ciclo;
- validar score/comentario;
- registrar valoración;
- registrar corrección;
- proporcionar agregados reutilizables para reportes.

No debe modificar estado del ticket ni acceso externo.

### ProviderRatingController

Responsabilidad:

- recibir POST de valoración/corrección;
- validar sesión, CSRF, rol y scope;
- delegar reglas al servicio;
- registrar auditoría correspondiente;
- redirigir al ticket con feedback de éxito/error.

### Ticket interno

En el bloque de participación del proveedor:

- ciclo activo: no mostrar formulario de evaluación;
- ciclo finalizado sin evaluación: mostrar acción `Evaluar proveedor`;
- ciclo finalizado evaluado: mostrar valoración vigente y opción `Registrar corrección`;
- mostrar historial interno de correcciones de forma compacta cuando corresponda.

No duplicar la captura en el Informe de proveedores.

### ProviderParticipationService / Informe de proveedores

La Fase 7 sigue siendo la fuente de ciclos de participación.

Fase 8 enriquece esos ciclos con:

- `provider_rating_score`
- `provider_rating_label`
- `provider_rating_comment`
- `provider_rating_at`
- `provider_rating_actor`
- `provider_rating_event_id`
- cantidad de revisiones/correcciones cuando sea útil

El informe añade:

- valoración individual vigente por ciclo;
- filtro `Valoración` con opciones `Todas`, `Sin evaluar`, `1★`, `2★`, `3★`, `4★`, `5★`;
- promedio calculado solo con ciclos evaluados dentro del conjunto filtrado;
- cantidad de ciclos evaluados;
- cantidad de ciclos sin evaluar.

`Sin evaluar` no entra en el promedio.

## Flujo de datos

1. IT comparte ticket con proveedor → `EXTERNAL_GRANTED`.
2. El proveedor trabaja y reporta mediante el flujo existente.
3. IT revoca acceso → `EXTERNAL_REVOKED`.
4. El ciclo queda elegible para valoración.
5. IT registra 1..5 y comentario según reglas.
6. Backend valida rol, scope, ciclo y datos.
7. Se inserta `PROVIDER_RATED` en `ticket_events`.
8. Si luego hay una corrección, se inserta `PROVIDER_RATING_CORRECTED`.
9. El ticket interno muestra la última valoración vigente y conserva historial.
10. El Informe de proveedores consume la última valoración por ciclo y calcula agregados.

## Error handling

Errores esperados y tratamiento:

- ciclo inexistente → rechazar;
- ciclo todavía activo → rechazar;
- proveedor no corresponde al `grant_event_id` → rechazar;
- score fuera de 1..5 → rechazar;
- 1–2 sin comentario → rechazar;
- corrección sin comentario → rechazar;
- corrección sobre evento de otro ciclo → rechazar;
- actor sin rol/scope → 403;
- CSRF inválido → rechazo por mecanismo actual;
- evento previo corrupto/no interpretable → ignorarlo para cálculo funcional y no sobrescribir historial; registrar/mostrar error controlado cuando impida una corrección.

No se deben mostrar errores SQL ni detalles internos al usuario.

## Auditoría

La valoración ya queda trazada funcionalmente en `ticket_events`.

Además, la acción debe conservar el patrón de `audit_logs` existente para registrar quién ejecutó la operación administrativa/técnica.

No usar `audit_logs` como fuente funcional del score.

## Base de datos

Objetivo: **0 cambios estructurales de BD**.

Se reutiliza `ticket_events`. No se crean:

- tablas;
- columnas;
- índices;
- migraciones.

Antes del cierre de Fase 8 debe verificarse que `database/` no tenga cambios atribuibles a esta fase.

## Reportería

La métrica debe ser explotable; de lo contrario la fase no se considera completa.

### Por ciclo

Mostrar:

- score vigente 1..5;
- etiqueta textual;
- comentario vigente interno;
- actor y fecha de la valoración vigente;
- indicador `Sin evaluar` cuando corresponda.

### Por proveedor

Calcular sobre los ciclos incluidos por los filtros actuales:

- promedio de valoraciones vigentes;
- ciclos evaluados;
- ciclos sin evaluar.

No calcular promedio sobre:

- ciclos sin evaluación;
- versiones anteriores corregidas.

La exportación XLSX del Informe de proveedores debe usar el mismo dataset, filtros y reglas que la pantalla.

## Compatibilidad con satisfacción del solicitante

La valoración de proveedor no reutiliza ni modifica `ticket_feedback.nps_score`.

Conceptos separados:

- solicitante → IT: satisfacción/feedback del servicio;
- IT → proveedor: calidad del trabajo externo por ciclo.

No se mezclan escalas, promedios ni reportes.

## No objetivos

Fase 8 no incluye:

- ranking público de proveedores;
- valoración visible para proveedor;
- bloqueo automático de proveedor por score bajo;
- SLA nuevo de proveedor;
- cierre automático del ticket;
- cambio automático de estado;
- edición destructiva de evaluaciones;
- tabla nueva de ratings;
- IA o análisis automático del comentario.

## Pruebas requeridas

Aplicar TDD.

### Servicio

Cubrir al menos:

- ciclo activo no evaluable;
- ciclo revocado evaluable;
- score 1..5;
- rechazo de score inválido;
- comentario obligatorio en 1–2;
- 3–5 permiten comentario vacío;
- primera valoración produce estado vigente;
- corrección requiere comentario;
- corrección conserva evento previo;
- última corrección pasa a ser vigente;
- ciclos repetidos del mismo proveedor permanecen independientes;
- evento de otro proveedor/ciclo no se mezcla.

### Autorización

Cubrir:

- ADMIN con scope;
- SEMIADMIN con scope;
- TECHNICIAN con scope;
- usuario sin scope rechazado;
- REQUESTER rechazado;
- EXTERNAL rechazado.

### UI

Cubrir:

- formulario solo para ciclo finalizado;
- `Sin evaluar`;
- score y etiqueta vigentes;
- acción de corrección;
- comentario interno no aparece en vista externa.

### Reportes

Cubrir:

- filtro `Valoración` por `Sin evaluar` y score 1..5;
- promedio ignora `Sin evaluar`;
- promedio usa solo última versión por ciclo;
- evaluados/sin evaluar correctos;
- XLSX coherente con pantalla/filtros.

### Gates acumulados

Antes del merge:

- sintaxis PHP;
- `tests/static_checks.php`;
- `tests/project_quality.php`;
- `tests/xlsx_smoke.php`;
- regresiones de Fase 7;
- regresiones nuevas de Fase 8;
- `git diff --check`;
- ausencia de cambios en `database/`;
- validación visual en PC TEST en claro/oscuro y responsive relevante.

## Criterios de aceptación

Fase 8 se considera implementada cuando:

1. IT puede valorar 1..5 un ciclo finalizado desde el ticket;
2. las reglas de comentario se validan en backend;
3. la evaluación queda en `ticket_events` y es inmutable;
4. una corrección genera otro evento y la última pasa a ser vigente;
5. proveedor y solicitante no ven la evaluación interna;
6. la evaluación no altera estado, SLA, resolución ni acceso del ticket;
7. el Informe de proveedores muestra valoración por ciclo, filtro de valoración y agregados por proveedor;
8. XLSX usa la misma lógica y filtros;
9. no hay cambios estructurales de BD;
10. regresiones de Fase 7 y quality gates permanecen verdes.
