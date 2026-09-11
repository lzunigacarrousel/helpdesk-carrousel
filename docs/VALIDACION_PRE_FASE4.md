# Validación integral Pre-Fase 4

Esta guía cierra las Fases 1–3 antes de iniciar **Fase 4 — Feedback**.

La regla es simple: **no avanzar a Fase 4 hasta que el gate automático esté aprobado y esta validación manual quede completada**.

## 1. Gate automático

Ejecutar desde la raíz del proyecto:

`VALIDAR_PRE_FASE4.bat`

Debe finalizar con `GATE AUTOMATICO APROBADO`.

El BAT es de solo lectura sobre la base de datos. No instala, no migra, no elimina ni modifica información.

## 2. Solicitante

- [ ] Login/OTP funciona correctamente.
- [ ] Crear solicitud muestra lenguaje humano en **¿En qué necesitas ayuda?**.
- [ ] La ayuda de **Cuéntanos qué está pasando** cambia según el tipo seleccionado.
- [ ] Nombre, correo, teléfono y ubicación conocida se reutilizan cuando corresponde.
- [ ] Se puede cambiar la ubicación si el reporte pertenece a otro lugar.
- [ ] La solicitud se crea una sola vez y muestra un único número de ticket.
- [ ] Llega correo de **Solicitud recibida** al correo real del solicitante.
- [ ] Mis solicitudes muestra el ticket sin textos ni botones duplicados.
- [ ] El solicitante puede abrir, responder y adjuntar un archivo permitido.
- [ ] Los estados visibles usan lenguaje comprensible y no códigos internos.

## 3. IT

- [ ] Centro de soporte muestra únicamente casos permitidos por scope.
- [ ] Filtros: Todos, Míos, Sin asignar, En proceso, En espera, Críticos, Vencidos, Por vencer y Reabiertos funcionan.
- [ ] Tomar un ticket asigna correctamente el responsable.
- [ ] Continuar abre el ticket y se ve como botón primario azul con texto blanco.
- [ ] No aparecen botones redundantes que conduzcan exactamente al mismo destino.
- [ ] SLA muestra tiempo restante, porcentaje usado y estado operativo correcto.
- [ ] Respuesta pública llega al solicitante y queda en historial.
- [ ] Nota interna no es visible para solicitante ni proveedor.
- [ ] Adjuntos respetan permisos y visibilidad.
- [ ] Poner en espera registra correctamente usuario, proveedor, compra, visita, aprobación o tercero.
- [ ] Cambiar el motivo de espera conserva correctamente el historial de tiempos.
- [ ] Reasignación respeta permisos y scopes.
- [ ] Problemas conocidos y sugerencias de solución se muestran cuando corresponde.
- [ ] Resolver exige documentación de solución y deja el ticket pendiente de confirmación del solicitante.

## 4. Proveedor

- [ ] Un proveedor solo puede entrar a tickets compartidos explícitamente.
- [ ] Nunca ve notas internas.
- [ ] Nunca ve administración, reportes internos, otros proveedores ni otros tickets.
- [ ] Puede responder y adjuntar evidencia cuando tiene permisos.
- [ ] Su actividad queda registrada en historial/auditoría.
- [ ] Revocar acceso elimina inmediatamente su acceso al ticket.

## 5. Gerencia / Supervisión

- [ ] Dashboard respeta scope regional/parque/área.
- [ ] Informes respetan el mismo scope.
- [ ] Equipo de soporte muestra integrantes activos reales.
- [ ] Solo usuarios autorizados pueden agregar o retirar integrantes.
- [ ] No se puede dejar el equipo sin ningún integrante activo.
- [ ] Exportación XLSX abre correctamente y respeta filtros/scopes.
- [ ] Auditoría muestra acciones relevantes sin exponer secretos.

## 6. Correo y notificaciones

- [ ] El correo SMTP real funciona con una contraseña de aplicación vigente.
- [ ] Solicitud nueva envía confirmación al solicitante.
- [ ] Solicitud nueva avisa a los integrantes activos de **Equipo de soporte**.
- [ ] `sistemas@carrousel.com.gt` no recibe avisos por configuración fija.
- [ ] Respuestas, asignaciones y resoluciones generan únicamente los avisos esperados.
- [ ] No aparecen correos duplicados ni spam por una misma acción.
- [ ] Administración → Correo y notificaciones registra `SENT`, `FAILED` o `PENDING` de forma coherente.
- [ ] Una falla SMTP deja un diagnóstico útil y no rompe la operación del ticket.

## 7. Conocimiento y búsqueda

- [ ] Buscar encuentra ticket por número, asunto y contenido permitido.
- [ ] Según permisos, busca comentarios, resoluciones, problemas conocidos y artículos.
- [ ] Proveedor no obtiene resultados fuera de sus tickets compartidos.
- [ ] Problemas conocidos permiten abrir detalle, recurrencias y solución.
- [ ] Base de conocimiento permite abrir artículos y conserva permisos de visibilidad.
- [ ] No hay CTAs principales duplicados en estados vacíos.

## 8. Administración y seguridad

- [ ] Usuarios: crear, editar, asignar y retirar acceso respetan permisos.
- [ ] Roles y scopes no pueden eludirse escribiendo una URL manualmente.
- [ ] Formularios POST importantes rechazan CSRF inválido.
- [ ] Sesión/OTP siguen funcionando después de los cambios recientes.
- [ ] Archivos no permitidos o demasiado grandes son rechazados.
- [ ] Auditoría registra acciones administrativas y operativas importantes.
- [ ] `helpdesk_carrousel` histórica permanece intacta.
- [ ] V2 sigue apuntando únicamente a `carrousel_helpdesk`.

## 9. Visual y Responsive

Validar al menos en:

- [ ] Escritorio **1920 px**.
- [ ] Laptop **1366 px**.
- [ ] iPad/tablet aproximadamente **1024 / 768 px**.
- [ ] Móvil **<=760 px**.

En cada resolución revisar:

- [ ] Modo **Claro**.
- [ ] Modo **Oscuro**.
- [ ] Modo **System**.
- [ ] Botones primarios: azul + texto blanco.
- [ ] Botones secundarios: fondo neutro + borde legible.
- [ ] Acciones destructivas: tratamiento de peligro únicamente cuando corresponde.
- [ ] Tablas legibles sin scroll horizontal accidental en operaciones principales.
- [ ] Formularios y selects buscables utilizables con teclado y táctil.
- [ ] Footer permanece al final y no deja huecos grandes artificiales.
- [ ] No hay textos, párrafos, contadores o CTAs redundantes.
- [ ] **Animaciones** y transiciones se perciben con claridad equivalente en Claro y Oscuro.
- [ ] Hover de botones, tarjetas, filas y filtros produce una señal visible en ambos temas.
- [ ] Focus de buscador y formularios conserva un anillo visible en ambos temas.
- [ ] Spinners de acceso, acciones globales e informes muestran claramente el giro en Oscuro.
- [ ] Barras de progreso e indicadores de gestión conservan contraste en Oscuro.
- [ ] Apertura/cierre de sidebar, ayuda y selects conserva su transición sin saltos visuales.

## 10. Criterio de cierre

La Pre-Fase 4 se considera aprobada únicamente cuando:

1. `VALIDAR_PRE_FASE4.bat` termina sin fallos automáticos.
2. GitHub Actions está verde para el mismo commit probado.
3. Todas las casillas manuales aplicables están revisadas.
4. Cualquier defecto encontrado fue corregido y vuelto a probar.
5. No hay cambios pendientes de BD, correo, permisos o visuales relacionados con Fases 1–3.

Solo después de esto se inicia **Fase 4 — Feedback**.
