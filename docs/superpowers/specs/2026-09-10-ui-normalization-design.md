# Helpdesk Carrousel — Normalización UI global

Fecha: 2026-09-10
Estado: **documento histórico de la fase de normalización visual**.
Rama donde quedó aplicada: `ui-normalization-working`.

> Esta especificación conserva el diseño de la fase visual ya ejecutada. No debe utilizarse como plan de la etapa actual de maduración funcional. La etapa vigente se documenta en `docs/superpowers/specs/2026-09-10-helpdesk-functional-maturation-design.md`.

## Objetivo

Normalizar la experiencia visual de todo Helpdesk Carrousel para eliminar espacios muertos, reducir textos repetitivos y convertir en tablas reales los módulos administrativos que actualmente simulan tablas mediante tarjetas o artículos. La intervención debe conservar intacta la lógica de negocio, permisos, OTP, auditoría, SLA, NPS, rutas, notificaciones, exportaciones y trazabilidad.

## Problemas detectados

1. Existen varias capas CSS globales que redefinen las mismas propiedades de `.main-wrap`, `.content`, grids y footer. Esto provoca que correcciones de densidad sean anuladas por hojas cargadas posteriormente.
2. El footer usa más de una estrategia para permanecer al fondo, generando estiramientos y espacios verticales artificiales.
3. Varias pantallas repiten instrucciones en tres niveles: contenido de la pantalla, ayuda contextual `?` y manual integrado.
4. Algunos datasets administrativos se representan con tarjetas o `<article>` aunque su estructura natural es tabular.
5. Las tablas actuales heredan `white-space: nowrap` y anchos mínimos históricos que generan scroll horizontal innecesario, especialmente en laptop e iPad.
6. Los tests actuales validan estructura técnica, pero no detectan regresiones de layout, tablas o duplicación de patrones visuales.

## Principios de diseño aprobados

- La pantalla debe explicar solo lo necesario para completar la tarea.
- La ayuda contextual explica cómo usar la pantalla.
- El manual contiene la explicación extensa.
- No convertir en tablas aquello que conceptualmente es una tarjeta, documento, conversación o formulario.
- Los datasets administrativos sí deben utilizar tablas reales.
- Una sola estrategia global de geometría, densidad y footer.
- Una sola familia de tablas responsive para toda la aplicación.
- Sin cambios funcionales salvo los estrictamente necesarios para representar correctamente la interfaz.

## Alcance

### 1. Consolidación de geometría global

Revisar y consolidar las reglas duplicadas de:

- `.main-wrap`
- `.content`
- `.app-corporate-footer`
- anchos máximos de páginas
- gaps verticales globales
- alturas mínimas heredadas
- grids variables

La hoja final debe evitar `flex` contradictorio y alturas artificiales. El footer debe quedar al fondo cuando la pantalla tiene poco contenido y después del contenido cuando la pantalla es larga, sin crear huecos adicionales.

### 2. Componente tabular estándar

Definir un patrón reutilizable para tablas de Carrousel:

- encabezado compacto y legible;
- filas densas pero táctiles;
- texto con wrapping controlado;
- columnas prioritarias y secundarias;
- acciones alineadas al final;
- estados mediante badges/pills existentes;
- filtros y búsqueda arriba de la tabla cuando aplique;
- paginación 10/25/50/100 donde el volumen lo requiera;
- sin `min-width` global excesivo;
- escritorio y laptop: tabla completa;
- iPad/tablet: ocultar o combinar columnas secundarias cuando sea necesario;
- móvil: fila transformada en registro vertical usando `data-label`, sin scroll horizontal como comportamiento principal.

### 3. Conversión de falsas tablas

Convertir a tabla real los siguientes módulos:

- Administración > Usuarios.
- Administración > Auditoría.
- Gestión > Equipo de soporte.
- Administración > Proveedores registrados.
- Administración > Casos compartidos con proveedores.
- Gestión > Historial de proveedores.

La edición de usuario seguirá siendo secundaria y expandible; no se eliminarán formularios ni permisos. Los formularios de alta o compartir proveedor continuarán como formularios.

### 4. Tablas existentes

Revisar y normalizar:

- Centro de soporte.
- Correo y notificaciones.
- Problemas conocidos.
- Dashboard de gestión > Casos recientes.
- Informes.

Se conservarán sus datos, acciones y filtros actuales. Se eliminarán anchos mínimos y `nowrap` innecesarios y se aplicará el mismo responsive global.

### 5. Reducción de copy repetitivo

Revisar todas las vistas y eliminar microexplicaciones que repitan el título, una acción obvia o contenido que ya exista en la ayuda contextual o el manual.

Áreas prioritarias:

- Dashboard inicial.
- Dashboard de gestión.
- Detalle de ticket.
- Caso externo/proveedor.
- Administración de proveedores.
- Login, OTP y registro.
- Crear solicitud pública.
- Feedback/NPS.
- Problemas y conocimiento.

Se conservarán textos necesarios para seguridad, validación, consecuencias de acciones, estados no evidentes y formularios donde una mala interpretación pueda causar errores.

### 6. Pantallas que no deben convertirse en tabla

Mantener como tarjetas, documentos o layout especializado:

- Mis solicitudes / Mis casos.
- Base de conocimiento para lectura.
- Búsqueda global con resultados mixtos.
- Detalle de ticket.
- Conversaciones.
- Feedback/NPS.
- Formularios públicos y autenticación.
- Dashboards y gráficas.

Estas pantallas solo recibirán compactación, eliminación de repetición y normalización visual.

## Estrategia técnica

### CSS

Crear una única capa final de normalización que sustituya reglas redundantes de densidad/tablas. Reducir progresivamente las reglas duplicadas en hojas históricas cuando afecten esta fase. No añadir otra cadena de parches sin retirar o neutralizar las reglas contradictorias.

### Vistas PHP

Modificar únicamente markup y textos de presentación. Mantener variables, formularios, endpoints, CSRF, acciones y permisos. Para tablas responsive agregar `data-label` por celda cuando corresponda.

### JavaScript

Reutilizar JS existente para filtros, búsqueda y expansión. Solo agregar comportamiento si una tabla convertida necesita filtrado/paginación cliente y el controlador ya entrega todos los registros. No duplicar lógica de servidor.

## Orden de implementación

1. Tests/reglas de calidad para detectar patrones conflictivos y tablas responsive.
2. Consolidación de geometría global y footer.
3. Componente global de tabla responsive.
4. Convertir Usuarios.
5. Convertir Auditoría.
6. Convertir Equipo de soporte.
7. Convertir Proveedores y Casos compartidos.
8. Convertir Historial de proveedores.
9. Normalizar tablas existentes.
10. Pasada global de reducción de copy.
11. Verificación PHP/CSS/tests y revisión responsive estructural.

## Criterios de aceptación

- No quedan huecos verticales artificiales provocados por `flex`, `min-height` o footer.
- No existe una cadena de hojas CSS que se contradigan deliberadamente para corregirse entre sí.
- Usuarios, Auditoría, Equipo, Proveedores, Casos compartidos e Historial de proveedores se muestran como tablas reales.
- Las tablas no dependen de un ancho mínimo de ~1450 px para ser utilizables.
- En iPad/tablet no se fuerza scroll horizontal para operaciones básicas.
- En móvil las tablas prioritarias pasan a lectura vertical mediante etiquetas por campo.
- Los textos de pantalla no duplican instrucciones detalladas que ya están en ayuda/manual.
- Se conservan permisos, endpoints, CSRF, OTP, SLA, NPS, auditoría, notificaciones y exportaciones.
- `tests/project_quality.php`, `tests/static_checks.php` y pruebas relacionadas continúan pasando.
- Se agregan verificaciones estáticas para evitar reintroducir reglas conflictivas de layout y tablas.

## Fuera de alcance

- Cambios en base de datos.
- Cambios en roles o permisos.
- Rediseño de lógica de tickets.
- Cambios en autenticación OTP.
- Cambios de cálculo de SLA/NPS.
- Cambios en exportaciones XLSX salvo ajustes de presentación fuera del archivo exportado.
- Nuevos módulos funcionales.

## Riesgos y mitigación

- **Riesgo:** romper responsive al retirar CSS histórico. **Mitigación:** cambios incrementales, pruebas por módulo y mantener un único punto de override final.
- **Riesgo:** eliminar texto necesario. **Mitigación:** conservar advertencias, consecuencias y ayuda contextual crítica; eliminar solo repetición semántica.
- **Riesgo:** perder acciones al convertir cards a tablas. **Mitigación:** mantener los mismos formularios, rutas, CSRF y permisos dentro de celdas de acción o detalles expandibles.
- **Riesgo:** tablas demasiado densas en tablet. **Mitigación:** priorización de columnas y layout vertical en móvil.

## Resultado esperado

Una interfaz más compacta, consistente y mantenible, donde los datos operativos se leen como datos, las instrucciones viven principalmente en Ayuda/Manual y el layout deja de depender de capas CSS contradictorias.
