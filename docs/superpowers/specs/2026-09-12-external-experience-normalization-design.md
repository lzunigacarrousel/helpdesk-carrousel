# Normalización UX de Externos — Diseño

**Fecha:** 2026-09-12

## Objetivo

Unificar la experiencia visual y operativa de usuarios internos, administración de proveedores externos y participación del proveedor dentro de un ticket, usando la misma gramática visual ya aprobada en `Usuarios`.

La revisión visual confirmó que no basta con mejorar el CSS: el perfil externo actual captura muy poca información operativa y el formulario de respuesta del proveedor es un textarea con plantillas, por lo que este diseño amplía el alcance para capturar datos útiles y obligatorios sin crear una segunda lógica de tickets.

El resultado debe sentirse como una sola aplicación: mismos encabezados, tarjetas, jerarquía, botones, filas expandibles, responsive, modo claro/oscuro, ayuda contextual, manual y tutorial.

## Alcance

### 1. Usuarios internos

Cerrar dos defectos visuales pendientes sin cambiar lógica:

- alinear `¿Qué representa esta cuenta?` con el resto de campos del grid;
- alinear `Responsable directo` con la estructura del formulario;
- conservar el editor expandido de ancho completo ya implementado;
- conservar endpoints, permisos, validaciones, conversión a externo y actualización histórica.

Estructura visual objetivo para alta y edición:

```text
Nombre               Correo               Teléfono
Perfil               Estado               Puesto
Qué representa       Dónde trabaja        Parque/Área/Región
Responsable directo  [ancho amplio]
```

En tablet el formulario pasa a 2 columnas y en móvil a 1 columna.

### 2. Perfil administrado de proveedor externo

La pantalla `/admin/externos` deja de tratar al proveedor como un registro mínimo y pasa a administrar un perfil operativo comparable en calidad al módulo `Usuarios`.

#### Datos obligatorios del proveedor

Cada cuenta externa debe tener:

- `Proveedor / empresa` (`organization_name`);
- `Tipo` (`external_type`: proveedor, socio/aliado u otro);
- `Nombre del contacto` (`users.full_name`);
- `Cargo / función del contacto` (`contact_position`);
- `Correo` (`users.email`);
- `Teléfono` (`users.phone`);
- `Servicio principal / especialidad` (`service_name`).

Datos opcionales:

- `Referencia de soporte / contrato / cuenta` (`support_reference`);
- `Notas internas` (`notes`).

`organization_name`, `contact_position`, `service_name`, nombre, correo y teléfono son obligatorios tanto al crear como al editar. Las conversiones interno → externo también deben solicitar los datos que no puedan inferirse de la cuenta interna.

#### Cambio de esquema aprobado por necesidad funcional

El esquema actual de `external_profiles` solo contiene `organization_name`, `external_type` y `notes`. Para no esconder información estructurada dentro de `notes`, se agregan columnas explícitas:

```sql
contact_position VARCHAR(140) NOT NULL
service_name VARCHAR(190) NOT NULL
support_reference VARCHAR(190) NULL
```

Se debe crear una migración idempotente para la BD existente, actualizar `INSTALAR.sql` y `VERIFICAR_INSTALACION.sql`, y no perder perfiles ya creados. Los registros históricos existentes podrán recibir valores temporales seguros durante la migración y deberán quedar identificables para completar sus datos desde Administración.

### 3. Administración de proveedores externos

La pantalla `/admin/externos` debe seguir la misma línea visual que `/admin/users`.

#### Encabezado y resumen

- conservar título, acceso a historial y KPIs;
- mantener `Compartir un caso` como acción operativa principal;
- reemplazar el `<details>` de alta por un control visual equivalente a `+ Dar acceso`, con panel completo;
- mejorar separación visual y jerarquía.

#### Directorio de proveedores

Cada proveedor se muestra en una fila compacta. La columna `Acciones` contiene un botón dedicado `Editar`.

Al editar:

- abrir una fila independiente inmediatamente debajo del proveedor;
- la fila de edición ocupa todo el ancho de la tabla;
- solo un editor puede permanecer abierto a la vez;
- el panel tiene encabezado contextual, `Cancelar edición` y `Guardar cambios`;
- edición, conversión a interno y desactivación permanecen dentro del panel completo;
- no se incrustan formularios anchos dentro de la celda `Acciones`.

Estructura visual objetivo:

```text
Proveedor | Tipo | Contacto | Servicio | Estado | Casos | Acciones
------------------------------------------------------------------
Proveedor X ...                                           [Editar]
------------------------------------------------------------------
Editar proveedor · Proveedor X                             [×]
Proveedor / empresa      Tipo                 Estado actual
Contacto                 Cargo / función      Correo
Teléfono                 Servicio principal  Referencia soporte
Notas internas [ancho completo]
                                  [Cancelar] [Guardar cambios]

▸ Convertir a usuario interno
▸ Desactivar proveedor
```

Responsive:

- escritorio: 3 columnas;
- tablet: 2 columnas;
- móvil: 1 columna;
- la tabla no debe deformarse al abrir el editor.

### 4. Vista del ticket para proveedor externo

La vista `/tickets/view?id=...` reutiliza el mismo ticket interno, pero cuando `access_type=EXTERNAL` debe presentar una experiencia diseñada específicamente para el proveedor.

#### Debe mostrar

- número de caso, asunto, estado, ubicación y categoría;
- bloque principal `Qué necesita Carrousel` con la descripción del caso;
- estado actual del caso;
- bloque `Tu participación` indicando si puede responder y/o adjuntar;
- conversación compartida con el proveedor;
- módulo estructurado para actualizar el caso;
- solución cuando exista;
- botón claro para volver a `Mis casos`.

#### No debe mostrar al proveedor

- clasificación ITSM interna;
- SLA interno;
- corrección histórica de ubicación;
- problemas conocidos;
- sugerencias internas de solución;
- acciones de soporte;
- conversación interna;
- auditoría o herramientas administrativas.

### 5. Actualización estructurada del proveedor

Se elimina el comportamiento de botones que solo insertan texto prefabricado en el textarea.

El proveedor primero selecciona obligatoriamente un tipo de actualización:

1. `Enviar avance` (`PROGRESS`)
2. `Solicitar información` (`INFO_REQUEST`)
3. `Trabajo realizado` (`WORK_COMPLETED`)

#### Datos comunes

- detalle de la actualización: obligatorio;
- referencia del proveedor: opcional;
- archivo/evidencia: opcional y sujeto a `can_upload`.

#### Avance

Además exige:

- porcentaje de avance: entero de 0 a 100;
- fecha/hora de compromiso: obligatoria.

#### Solicitar información

El detalle debe expresar concretamente qué información necesita del equipo de Carrousel.

#### Trabajo realizado

El detalle debe explicar qué se hizo y el resultado queda listo para revisión de Carrousel. No cierra automáticamente el ticket.

#### Persistencia

No se crea una segunda tabla de conversación. La respuesta sigue creando `ticket_comments` con visibilidad `EXTERNAL` y adjuntos existentes.

Para conservar estructura sin duplicar datos se registra además un `ticket_event` específico con metadata JSON:

```json
{
  "update_type": "PROGRESS|INFO_REQUEST|WORK_COMPLETED",
  "progress_percent": 60,
  "commitment_at": "2026-09-15 14:00:00",
  "provider_reference": "INC-12345",
  "comment_id": 123,
  "attachment_id": 456
}
```

El backend valida los campos obligatorios cuando el autor es externo. Los usuarios internos continúan usando el flujo de conversación existente.

### 6. Manual, tutorial y ayuda contextual

Toda modificación visual debe quedar explicada en las ayudas existentes.

#### Administrador

El manual/tutorial debe cubrir:

- crear y editar proveedor con datos obligatorios;
- compartir caso;
- revocar acceso;
- desactivar proveedor;
- convertir interno → externo;
- convertir externo → interno;
- preservación de identidad e historial;
- reglas de seguridad al convertir cuentas.

#### Proveedor externo

El manual/tutorial debe cubrir:

- qué casos puede ver;
- cómo abrir un caso compartido;
- cómo enviar avance;
- cómo solicitar información;
- cómo reportar trabajo realizado;
- cómo adjuntar evidencia;
- qué ocurre cuando se revoca su acceso;
- que nunca ve información interna del Helpdesk.

Se reutilizan `help_widget`, `manual.php` y `help-tour.js`. No se crea un sistema paralelo de documentación.

## Reglas funcionales que se conservan

- No cambiar OTP.
- No cambiar permisos ni alcance.
- No cambiar reglas de auditoría.
- No cambiar histórico ni identidad del usuario.
- No cambiar la lógica de compartir/revocar casos.
- No cambiar reglas de conversión interno ↔ externo salvo exigir el perfil externo completo.
- No duplicar usuarios por correo.
- No mostrar conversación interna a proveedores.
- Mantener `external_ticket_access`, `can_comment` y `can_upload` como fuente de permisos del caso.

## Seguridad y consistencia

- Las acciones administrativas continúan protegidas por permisos y CSRF.
- Las conversiones conservan revocación de sesiones/OTP y auditoría existentes.
- Cambiar correo externo revoca sesiones/OTP como hoy.
- La vista externa respeta `external_ticket_access`, `can_comment` y `can_upload`.
- Las rutas internas no se exponen mediante el tutorial o manual del proveedor.
- El proveedor no puede alterar estado, SLA, clasificación, responsable ni campos internos del ticket.

## Estrategia de pruebas

Se seguirá TDD.

### Regresiones nuevas

1. `admin_users_alignment_regression.php`
   - valida alineación visual de `Qué representa` y `Responsable directo`;
   - conserva endpoints y lógica del formulario.

2. `external_provider_profile_regression.php`
   - exige columnas canónicas del perfil externo;
   - exige migración y verificación;
   - exige datos obligatorios en alta, edición y conversión interno → externo;
   - conserva identidad, OTP y auditoría.

3. `external_admin_ux_regression.php`
   - valida alta en panel completo;
   - valida fila expandida de proveedor;
   - valida un solo editor abierto;
   - valida responsive;
   - conserva editar, convertir, desactivar y compartir.

4. `external_ticket_update_regression.php`
   - exige tipo de actualización;
   - exige avance + fecha compromiso cuando corresponde;
   - exige detalle;
   - conserva comentario/adjunto;
   - exige evento estructurado y validación backend.

5. `external_ticket_ux_regression.php`
   - valida estructura específica de proveedor externo;
   - valida bloques `Qué necesita Carrousel`, `Tu participación`, conversación, actualización y solución;
   - valida ausencia de controles internos.

6. `external_help_ux_regression.php`
   - valida manual y tutorial para administrador y proveedor externo;
   - valida ayuda contextual de externos.

### Regresiones existentes obligatorias

- `external_provider_conversion_regression.php`
- `internal_external_reversibility_regression.php`
- `ux_help_notifications_regression.php`
- `ux_tour_visual_regression.php`
- `itsm21_phase_closure_regression.php`
- `itsm21_requester_scope_location_regression.php`

## Validación manual antes de integrar a main

### Usuarios

- alta alineada;
- edición alineada;
- desktop/tablet/móvil;
- claro/oscuro.

### Administración de proveedores

- crear proveedor con datos obligatorios;
- editar en fila expandida;
- compartir caso;
- revocar;
- desactivar;
- interno → externo solicitando datos externos faltantes;
- externo → interno;
- bloqueo cuando existen tickets activos;
- desktop/tablet/móvil;
- claro/oscuro.

### Proveedor externo

- dashboard;
- Mis casos;
- abrir ticket;
- enviar avance con porcentaje y compromiso;
- solicitar información;
- reportar trabajo realizado;
- adjuntar;
- ver solución;
- no ver contenido interno;
- notificaciones;
- desktop/tablet/móvil;
- claro/oscuro.

### Ayuda

- manual por perfil;
- tutorial de administrador;
- tutorial de proveedor;
- ayuda flotante sin pasos inexistentes.

## Criterio de cierre

Este trabajo se considera terminado únicamente cuando:

- todas las regresiones nuevas están en GREEN;
- todas las regresiones existentes obligatorias siguen en GREEN;
- migración y verificación de BD están validadas en PC TEST;
- `git diff --check` no reporta errores;
- working tree queda limpio;
- las pruebas visuales en PC, tablet/iPad y móvil son correctas;
- `ux-admin-users-edit` se integra a `main` mediante fast-forward o merge limpio;
- después del merge no se continúa refactorizando por inercia: se inicia Fase 5.