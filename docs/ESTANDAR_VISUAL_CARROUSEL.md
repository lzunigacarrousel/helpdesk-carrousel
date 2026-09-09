# Estándar visual · Helpdesk Carrousel

## Fuente de referencia

La referencia visual y de experiencia para Helpdesk Carrousel es `FernandoZL/PayOutParques`.

Esto significa reutilizar su **gramática visual y de interacción**, no copiar lógica de negocio de Payout.

## Reglas obligatorias

1. Mantener la identidad Carrousel: azul corporativo, franja multicolor, superficies limpias, bordes suaves y jerarquía clara.
2. Login, OTP y primer ingreso deben sentirse como una misma familia visual que PayOutParques, conservando autenticación OTP propia del Helpdesk.
3. Sidebar, topbar, botones, tarjetas, formularios, tablas, filtros, estados de carga, modo claro/oscuro, ayuda y responsive deben mantener patrones consistentes en toda la aplicación.
4. Ninguna pantalla debe sentirse como una página PHP aislada o usar un layout visual distinto sin una razón funcional.
5. Evitar cards gigantes, espacios muertos, formularios tipo wizard para tareas simples, textos repetidos y botones duplicados.
6. El ancho útil debe aprovechar escritorio y laptop sin perjudicar 1366×768, iPad/tablet ni móvil.
7. En administración, priorizar formularios compactos y acciones directas. Mostrar campos condicionales únicamente cuando correspondan.
8. En formularios de usuario final, ocultar complejidad técnica y mantener una tarea principal evidente.
9. El modo oscuro debe conservar contraste y jerarquía, no ser una inversión automática de colores.
10. Todo cambio visual debe conservar lógica de negocio, permisos, auditoría, seguridad, rutas, OTP, NPS, SLA, notificaciones y trazabilidad salvo que exista una solicitud funcional explícita.

## Patrón de acceso

- Fondo y panel de marca equivalentes al login de PayOutParques.
- Panel de formulario compacto.
- Un único CTA principal.
- Responsive: panel de marca se oculta en pantallas pequeñas y aparece marca compacta dentro del formulario.
- Helpdesk conserva correo + OTP; no se introducen contraseñas permanentes.

## Patrón de administración

- Encabezado de página alineado al contenido.
- Acción principal a la derecha (`Dar acceso`, `Crear`, etc.).
- Formularios de alta compactos y alineados, no wizards de múltiples tarjetas salvo necesidad real.
- Búsqueda y filtros en una misma línea cuando el ancho lo permita.
- Edición secundaria expandible para no saturar la vista.

## Patrón de tickets

- Problema primero.
- Estado y responsable visibles.
- Conversación como hilo central.
- Acciones principales según estado.
- Resolución estructurada y reutilizable.
- Cuando Soporte resuelve, el solicitante confirma, califica o devuelve el caso antes del cierre final.

## Responsive

- Escritorio: aprovechar ancho y mantener ejes alineados.
- Tablet/iPad: controles táctiles, grids de 2 columnas cuando sea viable, sidebar compacta/drawer según ancho.
- Móvil: una columna, CTA principal de ancho completo cuando corresponda, sin scroll horizontal accidental.

## Regla para nuevas pantallas

Antes de crear una nueva variante visual, comprobar si PayOutParques o un componente ya existente del Helpdesk cubre el patrón. Preferir reutilizar y extender antes que crear otra familia de estilos.
