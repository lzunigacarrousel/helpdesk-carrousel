# Fase 11 — Manual · progreso

Fecha de inicio: 2026-09-18
Rama: `main`

## Estado inicial

- Fase 10 Reportes: gate final PC TEST GREEN.
- Fase 11 Manual: iniciada.
- Fase 12 Validación integral: siguiente después del cierre.
- Producción: sin cambios.

## Decisiones

- Reutilizar el Manual actual; no reconstruir.
- Mantener buscador, índice, FAQ y ayuda flotante existentes.
- Añadir perfil funcional explícito y navegación por tareas.
- No exponer instrucciones por rol si la capacidad no existe.
- BD: sin cambios.

## Task 1 — Perfil + navegación por tareas

Estado: en implementación.

## Task 1 — Perfil + navegación por tareas · IMPLEMENTADA

- `HelpController` define seis perfiles funcionales del Manual: Solicitante, Técnico, Supervisor, Gerencia, Administrador/Semiadmin y Colaborador.
- El Manual muestra un bloque contextual con el perfil y una descripción breve de su alcance funcional.
- Se agregaron filtros por tema combinables con la búsqueda existente: Todo, Solicitudes, Soporte, Actividades, Conocimiento, Gestión, Administración y Ayuda/FAQ según permisos.
- Cada sección declara su tema; el índice se sincroniza con los resultados visibles.
- Acción `Limpiar` restablece texto + tema y devuelve foco al buscador.
- Filtros exponen `aria-pressed` y estilos focus visibles.
- Responsive: filtros en 2 columnas en móvil y perfil apilado sin tratar tablet como móvil.
- `tests/phase11_manual_profile_navigation_regression.php` creado.
- `VALIDAR_FASE11.bat` creado.
- CI incorpora la regresión inicial de Fase 11.
- BD: sin cambios.

Siguiente: Task 2 — consolidar contenido final por perfil y eliminar instrucciones que no correspondan a cada rol/capacidad.
