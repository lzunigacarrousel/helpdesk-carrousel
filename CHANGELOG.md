# CHANGELOG

## v2.3.2-dev · Notificaciones reales + correo + vista externa · 2026-09-09
- La campana deja de ser un contador de carga de trabajo y se convierte en centro persistente de notificaciones con novedades no leídas, historial reciente y acción para marcar como leído.
- `notification_events` y `notification_deliveries` se aprovechan como infraestructura real; se añade canal `IN_APP`, contenido legible, URL de acción y fecha de lectura mediante migración incremental.
- Movimientos importantes del ticket generan notificaciones: creación, toma, asignación/reasignación, devolución a cola, cambio de estado, motivo de espera, respuesta pública, nota interna, resolución y colaboración externa.
- Las respuestas públicas notifican a las partes involucradas; las notas internas permanecen únicamente dentro del equipo de Soporte y no se envían al solicitante.
- Los correos del Helpdesk usan una plantilla Carrousel consistente con logo, franja multicolor, jerarquía clara y botón de acción; se eliminan las URLs visibles en crudo del HTML.
- La entrega por correo queda registrada como PENDING/SENT/FAILED con cantidad de intentos y último error para poder diagnosticar notificaciones que no salieron.
- El dashboard de proveedor/solicitante reutiliza los mismos componentes operativos del resto del sistema: resumen con padding correcto, métricas legibles y casos recientes presentados como fichas accionables.
- Se incorpora `components.css` como capa pequeña de componentes nuevos para notificaciones y experiencia externa, evitando seguir acumulando arreglos dentro de hojas sin relación semántica.
- Verificación de estabilidad añade controles de notificaciones internas y contadores de entregas/fallos; checks estáticos validan rutas, servicio, controlador, JS, CSS y plantilla de correo.

## v2.3.1-dev · Consistencia visual + ayuda integrada · 2026-09-09
- Alineación transversal del contenido interno sobre un mismo eje visual y ancho máximo consistente; Dashboard, Cola, Tickets, ITSM, Gestión y Administración dejan de sentirse como módulos separados.
- Dashboard corrige tarjetas sin padding interno y mantiene jerarquía operativa sin contenido pegado a bordes.
- Informes reorganizados para lectura real: KPIs en 4 columnas, detalle por ticket en bloques 2×2, textos más legibles y secciones renombradas a Qué ocurrió / Cuánto tomó / Cómo avanzó / Qué aprendimos.
- Tutorial flotante contextual por pantalla con pasos guiados, resaltado del elemento activo, navegación Anterior/Siguiente y recordatorio flotante de primera visita.
- Panel de ayuda contextual incorpora acceso directo a Tutorial guiado y Manual completo.
- Nuevo Manual del Helpdesk integrado y adaptado por perfil: solicitudes, soporte, Problem Management, Knowledge Management, gestión y administración según permisos.
- Sidebar incorpora Ayuda de esta pantalla, Tutorial guiado y Manual del Helpdesk como funciones diferenciadas.
- Asset version incrementada para evitar reutilizar CSS/JS anterior después de actualizar la rama.
- Checks estáticos ampliados para validar ruta, controlador, vista de manual y motor de tutorial contextual.

## v2.3.0-dev · ITSM Problemas + Conocimiento · 2026-09-09
- Problem Management operativo sobre las tablas existentes: listado con filtros, creación, edición, estados, propietario, causa raíz, workaround y solución permanente.
- Tickets recurrentes se relacionan mediante `problem_occurrences`; el sistema recalcula automáticamente cantidad de ocurrencias, primera aparición y última aparición.
- Creación de problema conocido desde un ticket o desde varios números de ticket, sin duplicar los casos originales.
- Workspace del problema con tickets relacionados, conocimiento relacionado, solución principal y timeline respaldado por auditoría.
- Knowledge Management operativo sobre `knowledge_articles`: listado, borradores, edición, publicación explícita, visibilidad interna/pública y archivado.
- Creación de artículo desde una resolución de ticket o desde un problema conocido; el contenido se prellena con información técnica y excluye datos personales del solicitante.
- Relación Problema ↔ Artículo mediante `problem_solutions`, incluyendo artículo marcado como solución principal.
- Workspace del ticket incorpora Problema conocido relacionado, workaround disponible y Posibles soluciones.
- Sugerencias sin IA externa combinan categoría, parque, términos del caso, recurrencia, tags de problemas y resoluciones reutilizables anteriores.
- Búsqueda global ampliada y agrupada en Tickets, Problemas conocidos y Conocimiento respetando permisos y visibilidad.
- Sidebar incorpora la sección Conocimiento según permisos; proveedores externos no reciben acceso a los módulos internos.
- Dashboard de soporte añade métricas funcionales de problemas abiertos, problemas en investigación y artículos en borrador según permisos.
- Auditoría registra creación/edición de problemas, relaciones ticket-problema, vínculos problema-artículo, creación/edición/publicación/archivado de conocimiento.
- Nueva migración incremental `ACTUALIZAR_ITSM_PROBLEMAS_CONOCIMIENTO_V2.sql`, sin borrado de datos, para asegurar tablas/permisos ITSM en TEST.
- Verificación de estabilidad ampliada con consistencia de recurrencias, números ITSM, solución principal y publicación de artículos.
- Checks estáticos ampliados para controladores, servicios, vistas, rutas y migración de Problemas + Conocimiento.

## v2.2.0-dev · UX operacional · 2026-09-09
- Dashboard de soporte reorganizado por prioridad operativa: SLA vencidos, próximos a vencer, críticos, trabajo propio, cola y actividad reciente.
- Navegación de soporte simplificada con accesos directos a Centro de soporte, Mis casos y Disponibles; se conserva Mis solicitudes como espacio personal.
- Centro de soporte convertido en cola operacional con filtros rápidos, filtros avanzados plegables, prioridad visual del problema, SLA legible y vista móvil tipo lista/card.
- Workspace del ticket reorganizado con composición 70/30 en escritorio, panel contextual sticky, encabezado sin duplicaciones y resumen del problema como contenido principal.
- Acciones del ticket adaptadas al estado y jerarquizadas; reasignación queda dentro de Más acciones.
- Flujo En espera conserva la lógica existente y muestra motivo/detalle de forma contextual para operación e informes.
- Conversación rediseñada como hilo de soporte: respuesta al usuario y Nota interna claramente diferenciadas; la nota interna indica que solo la ve Soporte.
- Adjuntos muestran nombre, tamaño y acción de descarga; el selector visual ya no promueve archivos CSV.
- Resolución estructurada reorganizada como Qué encontramos / Qué hicimos / Cómo evitarlo, con acción principal Guardar solución y resolver.
- Tickets resueltos destacan una tarjeta de Solución y los casos similares muestran acceso compacto Ver solución.
- Responsive específico para iPad: sidebar compacta en horizontal y drawer en vertical; workspace mantiene dos columnas cuando existe espacio suficiente.
- Dashboard de solicitante y externo simplificado para priorizar solicitudes abiertas, historial y casos recientes.

## v2.1.0-dev · P0/P1 · 2026-09-09
- Acceso, OTP y primer ingreso alineados visualmente con la pantalla de autenticación de Caja Chica Carrousel: fondo corporativo oscuro, contenedor centrado, panel de marca y tarjeta de acceso con las mismas proporciones base.
- Buscador global `/buscar` con acceso por perfil y atajo `Ctrl/Cmd + K`.
- El workspace del ticket prioriza el problema reportado sobre los datos administrativos.
- Nuevo flujo de espera con motivo obligatorio y detalle opcional para explicar pausas operativas.
- Dashboard e Informes incorporan motivos de espera y trazabilidad de cambios de estado.
- Exportación operativa únicamente en Excel `.xlsx`, con hojas Resumen, Tickets y Esperas.
- Se retiró del flujo activo la exportación CSV heredada.
- `schema_migrations` inicia el control de actualizaciones incrementales de BD.
- Evidencias de `storage/ticket_uploads` quedan protegidas fuera de Git.
- CSP reforzada con nonce para JavaScript inline autorizado.
- Ayuda contextual actualizada a búsqueda, resolución, espera e informes XLSX.
- CI de GitHub valida Composer, sintaxis PHP y checks estáticos en `v2-rebuild`.

## v1.0.1 · Fase 1.1 · 2026-09-07
- Alineación visual real con el shell de PayOutParques: sidebar azul, topbar, tarjetas, botones, tablas y footer institucional.
- Logo Carrousel incorporado como recurso local.
- Tema Claro / Oscuro / Sistema persistido en `carrousel-theme`.
- Login y OTP rediseñados; OTP usa `autocomplete="one-time-code"`.
- `luis@carrousel.com.gt` definido como Administrador principal en instalación limpia.
- Nuevo `database/ACTUALIZAR_FASE1_1.sql` para corregir una BD TEST ya instalada sin borrarla.
- Modo OTP `log` continúa disponible en TEST; SMTP permanece configurable solo fuera de Git.

## v1.0.0 · Fase 1
- Base inicial: OTP, usuarios, roles, permisos, scopes, organización y auditoría.
