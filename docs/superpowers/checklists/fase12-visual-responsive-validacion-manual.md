# Fase 12 — Matriz visual / responsive integral

Completar en PC TEST. El gate automatizado no sustituye esta revisión.

## Viewports obligatorios

| Código | Viewport | Uso |
| --- | --- | --- |
| D1920 | 1920 × 1080 | Escritorio amplio |
| D1366 | 1366 × 768 | Escritorio operativo |
| IPAD-H | 1024 × 768 | iPad horizontal |
| IPAD-V | 768 × 1024 | iPad vertical |
| MOB | 390 × 844 aprox. | Móvil |

## Temas

- Claro.
- Oscuro.

## Perfiles / superficies críticas

| Perfil | Pantallas mínimas |
| --- | --- |
| Solicitante | Inicio, crear ticket, mis solicitudes, detalle |
| Técnico/Soporte | Dashboard, cola, ticket, agenda, problemas, conocimiento |
| Administrador/Semiadmin | Dashboard, usuarios, proveedores, correo, auditoría |
| Supervisor | Dashboard/gestión, informes, agenda en consulta |
| Gerencia | Gestión, informes, problemas, conocimiento |
| Colaborador | Mis casos, detalle compartido, conversación/adjunto |

## Criterios por pantalla

- No scroll horizontal global.
- No botones fuera del viewport.
- Acción principal visible y jerarquizada.
- Máximo 2–3 acciones secundarias visibles; resto en Más acciones cuando aplique.
- No duplicación evidente de estado/prioridad/ubicación.
- Tarjetas alineadas y sin espacios muertos excesivos.
- Texto principal legible; microtexto solo para metadata.
- Tablas se adaptan o se convierten a patrón móvil sin perder etiquetas.
- iPad vertical no adopta automáticamente shell móvil.
- Sidebar/topbar no tapan contenido.
- Popovers, ayuda y tutorial permanecen dentro del viewport.
- Modo oscuro mantiene contraste y estados legibles.
- Focus visible en controles de teclado.
- Formularios táctiles y acciones críticas >= 42/44px.

## Evidencia

Registrar por cada combinación crítica:

| Perfil | Pantalla | Viewport | Tema | PASS/FAIL | Observación |
| --- | --- | --- | --- | --- | --- |
| | | | | | |

## Defectos

Todo FAIL debe registrar ruta, perfil, viewport, tema, captura y descripción. Corregir solo defectos reales y agregar regresión cuando sea automatizable.

Producción permanece fuera de alcance hasta el closeout de Fase 12.
