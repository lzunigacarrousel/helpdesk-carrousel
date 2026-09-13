# Normalización UX de Externos — Diseño

**Fecha:** 2026-09-12

## Objetivo

Unificar la experiencia visual y operativa de usuarios internos, administración de proveedores externos y participación del proveedor dentro de un ticket, usando la misma gramática visual ya aprobada en `Usuarios`.

El resultado debe sentirse como una sola aplicación: mismos encabezados, tarjetas, jerarquía, botones, filas expandibles, responsive, modo claro/oscuro, ayuda contextual, manual y tutorial.

## Alcance

### 1. Usuarios

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

### 2. Administración de proveedores externos

La pantalla `/admin/externos` debe seguir la misma línea visual que `/admin/users`.

#### Encabezado y resumen

- conservar título, acceso a historial y KPIs;
- mantener `Compartir un caso` como acción operativa principal;
- mantener `Registrar nuevo proveedor` como acción secundaria desplegable;
- mejorar separación visual y jerarquía sin alterar la lógica existente.

#### Directorio de proveedores

Cada proveedor se muestra en una fila compacta. La columna `Acciones` contiene un control dedicado `Editar`.

Al editar:

- abrir una fila independiente inmediatamente debajo del proveedor;
- la fila de edición ocupa todo el ancho de la tabla;
- solo un editor puede permanecer abierto a la vez;
- el panel tiene encabezado contextual, botón `Cancelar edición` y botón `Guardar cambios`;
- edición, conversión a interno y desactivación se mantienen dentro del panel completo;
- no se incrustan formularios anchos dentro de la celda `Acciones`.

Estructura visual objetivo:

```text
Proveedor | Tipo | Contacto | Correo | Estado | Casos | Acciones
---------------------------------------------------------------
Proveedor X ...                                      [Editar]
---------------------------------------------------------------
Editar proveedor · Proveedor X                        [×]
Proveedor / empresa   Tipo          Estado
Contacto              Correo        Teléfono
Servicio / referencia
                         [Cancelar] [Guardar cambios]

▸ Convertir a usuario interno
▸ Desactivar proveedor
```

Responsive:

- escritorio: 3 columnas de formulario cuando aplique;
- tablet: 2 columnas;
- móvil: 1 columna;
- la tabla no debe deformarse al abrir el editor.

### 3. Vista del ticket para proveedor externo

La vista `/tickets/view?id=...` reutiliza el mismo ticket interno, pero cuando `access_type=EXTERNAL` debe presentar una experiencia diseñada específicamente para el proveedor.

#### Debe mostrar

- número de caso, asunto, estado, ubicación y categoría;
- bloque principal `Qué necesita Carrousel` con la descripción del caso;
- estado actual del caso;
- bloque `Tu participación` indicando si puede responder y/o adjuntar;
- conversación pública del caso;
- formulario para responder y adjuntar evidencia cuando tenga permiso;
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

La vista debe reutilizar datos y permisos existentes. No se agrega una nueva fuente de verdad ni una segunda lógica de ticket.

### 4. Manual, tutorial y ayuda contextual

Toda modificación visual debe quedar explicada en las ayudas existentes.

#### Administrador

El manual/tutorial debe cubrir:

- crear y editar proveedor;
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
- cómo responder;
- cómo adjuntar evidencia;
- qué significa responder como proveedor;
- qué ocurre cuando se revoca su acceso;
- que nunca ve información interna del Helpdesk.

Se reutilizan `help_widget`, `manual.php` y `help-tour.js`. No se crea un sistema paralelo de documentación.

## Reglas funcionales que NO cambian

- No cambiar esquema de BD.
- No cambiar rutas existentes salvo corrección necesaria demostrada por una prueba.
- No cambiar OTP.
- No cambiar permisos ni alcance.
- No cambiar reglas de auditoría.
- No cambiar histórico ni identidad del usuario.
- No cambiar la lógica de compartir/revocar casos.
- No cambiar reglas de conversión interno ↔ externo.
- No duplicar usuarios por correo.
- No mostrar conversación interna a proveedores.

## Seguridad y consistencia

- Las acciones administrativas continúan protegidas por permisos y CSRF.
- Las conversiones conservan revocación de sesiones/OTP y auditoría existentes.
- La vista externa respeta `external_ticket_access`, `can_comment` y `can_upload` existentes.
- Las rutas internas no se exponen mediante el tutorial o manual del proveedor.

## Estrategia de pruebas

Se seguirá TDD.

### Regresiones nuevas

1. `admin_users_alignment_regression.php`
   - valida alineación visual de `Qué representa` y `Responsable directo`;
   - conserva endpoints y lógica del formulario.

2. `external_admin_ux_regression.php`
   - valida fila expandida de proveedor;
   - valida un solo editor abierto;
   - valida responsive;
   - conserva editar, convertir, desactivar y compartir.

3. `external_ticket_ux_regression.php`
   - valida estructura específica de proveedor externo;
   - valida bloques `Qué necesita Carrousel`, `Tu participación`, conversación, respuesta y solución;
   - valida ausencia de controles internos.

4. `external_help_ux_regression.php`
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

- crear proveedor;
- editar;
- compartir caso;
- revocar;
- desactivar;
- interno → externo;
- externo → interno;
- bloqueo cuando existen tickets activos;
- desktop/tablet/móvil;
- claro/oscuro.

### Proveedor externo

- dashboard;
- Mis casos;
- abrir ticket;
- responder;
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
- `git diff --check` no reporta errores;
- working tree queda limpio;
- las pruebas visuales en PC, tablet/iPad y móvil son correctas;
- `ux-admin-users-edit` se integra a `main` mediante fast-forward o merge limpio;
- después del merge no se continúa refactorizando por inercia: se inicia Fase 5.