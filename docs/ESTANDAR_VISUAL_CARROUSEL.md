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

## Patrón de botones y acciones

La aplicación usa una sola jerarquía de botones. No crear variantes visuales por módulo.

- `btn-primary`: acción principal que hace avanzar el flujo (Crear, Guardar, Enviar a revisión, Publicar, Confirmar).
- `btn-outline-secondary`: navegación o acción secundaria no destructiva (Volver, Ver historial, Comparar, Más acciones, Devolver a borrador).
- `btn-danger`: acción destructiva o de retiro (Archivar, Eliminar, Retirar acceso, Revocar).
- `btn-sm`: únicamente para controles compactos donde la densidad lo exige; no sustituye la jerarquía semántica.
- Los botones de acción normales comparten altura, radio, padding, tipografía y estados hover/focus.
- Dentro de un menú de acciones, las opciones deben ocupar el mismo ancho y conservar su color semántico.
- No usar un botón neutro para una acción destructiva ni inventar colores/medidas locales.

## Patrón de administración

- Encabezado de página alineado al contenido.
- Acción principal a la derecha (`Dar acceso`, `Crear`, etc.).
- Formularios de alta compactos y alineados, no wizards de múltiples tarjetas salvo necesidad real.
- Búsqueda y filtros en una misma línea cuando el ancho lo permita.
- Edición secundaria expandible para no saturar la vista.

## Patrón de tablas

- Los datos tabulares deben usar tabla HTML semántica y el componente común `.data-table`.
- Escritorio: aprovechar el ancho disponible, permitir ajuste de texto y evitar anchos mínimos artificiales.
- Tablet/iPad: priorizar las columnas operativas; la información secundaria puede reducirse sin eliminarse del modo móvil.
- Móvil: cada fila debe convertirse en un registro vertical mediante `data-label`, sin depender de scroll horizontal.
- No simular tablas mediante grids de tarjetas cuando las filas representan registros comparables.
- Las acciones deben mantenerse dentro de la fila correspondiente y conservar su comportamiento, permisos y CSRF.
- Una nueva tabla no debe crear su propia estrategia de responsive si el componente común resuelve el caso.

## Patrón de contenido y copy

- La pantalla principal muestra lo necesario para decidir o actuar: título, estado, datos, validaciones y consecuencias relevantes.
- Evitar párrafos que repitan el título, expliquen un botón obvio o describan información ya visible.
- La ayuda extensa, tutoriales y explicación del proceso deben vivir en Ayuda/Manual o en ayuda contextual desplegable cuando previene errores reales.
- No retirar advertencias de seguridad, visibilidad, límites de archivo, consecuencias de una acción o información necesaria para completar correctamente un formulario.
- Los estados vacíos deben ser breves y accionables; no deben reservar altura artificial.

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