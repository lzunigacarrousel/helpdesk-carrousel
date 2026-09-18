# Fase 9 — Matriz manual de validación final

Fecha de preparación: 2026-09-18
Rama: `main`
Entorno autorizado: **PC TEST**
Producción: **NO ejecutar esta matriz contra producción hasta aprobación explícita**

## Objetivo

Cerrar Fase 9 verificando lo que los tests automáticos no cubren por sí solos: experiencia real en navegador, alineación visual, responsive en PC/iPad/móvil, permisos por perfil, acceso directo por URL, separación entre publicación interna/pública, archivado, autoservicio y trazabilidad de referencias.

## Precondiciones

- [ ] `git status` limpio.
- [ ] `VALIDAR_FASE9.bat` completamente GREEN.
- [ ] Migración Fase 9 ejecutada y verificada en PC TEST.
- [ ] Usar datos de prueba; no modificar producción.
- [ ] Tener perfiles de prueba ADMIN/SEMIADMIN, TECHNICIAN y solicitante.

## A. Matriz visual / responsive

Validar las vistas siguientes en PC, iPad/tablet y móvil.

### A1. Base de conocimiento — `/knowledge`

- [ ] encabezado alineado;
- [ ] `+ Nuevo artículo` no se solapa;
- [ ] filtros con alturas consistentes;
- [ ] búsqueda/selects/botón `Aplicar` alineados;
- [ ] cards con alturas visualmente consistentes;
- [ ] títulos largos no rompen la card;
- [ ] resumen no desplaza metadata;
- [ ] metadata queda alineada al fondo;
- [ ] archivados NO muestran `Disponible para solicitantes`;
- [ ] no hay scroll horizontal innecesario.

### A2. Detalle de artículo activo

- [ ] título largo no desplaza `Volver`;
- [ ] barra `Estado editorial` mantiene altura estable;
- [ ] botón principal y `Más acciones` quedan alineados;
- [ ] abrir `Más acciones` no cambia la altura de la barra en escritorio;
- [ ] popover no queda cortado;
- [ ] contenido principal y panel lateral comienzan alineados;
- [ ] panel lateral no invade contenido;
- [ ] en tablet/móvil pasa a una columna sin solaparse;
- [ ] no aparece scroll horizontal.

### A3. Detalle archivado

- [ ] estado = `Archivado`;
- [ ] `Publicado para soporte` = `No disponible (archivado)`;
- [ ] `Disponible para solicitantes` = `No disponible (archivado)`;
- [ ] no hay acción de publicación;
- [ ] no hay acción de archivar otra vez;
- [ ] `Más acciones` no queda vacío;
- [ ] `Ver historial` aparece cuando corresponde.

### A4. Historial / comparación

- [ ] versiones se leen claramente;
- [ ] vigente para soporte identificada;
- [ ] versión pública identificada;
- [ ] comparación muestra Título, Resumen, Contenido y Categoría;
- [ ] columnas no se montan en tablet;
- [ ] móvil mantiene lectura usable;
- [ ] comparar no altera la versión vigente.

### A5. Crear / editar borrador

- [ ] formulario alineado;
- [ ] textarea principal no desborda;
- [ ] `Guardar borrador` permanece visible;
- [ ] iPad no tapa acción principal con teclado/viewport;
- [ ] no existe selector legacy `INTERNAL/PUBLIC`.

## B. Workflow funcional

### B1. Borrador
- [ ] crear artículo nuevo;
- [ ] queda en `Borrador`;
- [ ] soporte = `Aún no publicado`;
- [ ] solicitantes = `No`;
- [ ] aparece `Enviar a revisión`.

### B2. Revisión
- [ ] enviar a revisión;
- [ ] estado = `En revisión` sin duplicación;
- [ ] ADMIN/SEMIADMIN puede devolver con observación;
- [ ] TECHNICIAN no puede aprobar/publicar.

### B3. Publicación interna
- [ ] ADMIN/SEMIADMIN publica para soporte;
- [ ] todavía NO queda público;
- [ ] editar publicado crea revisión borrador nueva.

### B4. Publicación pública
- [ ] es una acción separada;
- [ ] solo ADMIN/SEMIADMIN puede ejecutarla;
- [ ] después aparece disponible para solicitantes;
- [ ] borradores posteriores no sustituyen automáticamente la versión pública.

### B5. Historial / restauración
- [ ] historial conserva versiones;
- [ ] comparar no altera datos;
- [ ] restaurar crea borrador nuevo;
- [ ] restaurar no mueve punteros vigentes;
- [ ] archivado no ofrece restauración directa desde historial.

## C. Matriz de permisos y seguridad

### C1. TECHNICIAN

Debe poder:
- [ ] ver conocimiento interno;
- [ ] crear/editar borrador;
- [ ] enviar a revisión;
- [ ] usar referencia.

No debe poder:
- [ ] revisar/aprobar;
- [ ] publicar para soporte;
- [ ] publicar para solicitantes;
- [ ] ver historial administrativo completo;
- [ ] restaurar.

Pruebas directas:
- [ ] conocer una URL de publicación no permite ejecutarla;
- [ ] conocer una URL de restauración no permite ejecutarla.

### C2. ADMIN / SEMIADMIN

- [ ] revisar;
- [ ] devolver a borrador;
- [ ] publicar para soporte;
- [ ] habilitar para solicitantes;
- [ ] consultar historial completo;
- [ ] comparar;
- [ ] restaurar como borrador;
- [ ] archivar.

### C3. Solicitante autenticado

- [ ] nunca ve borradores;
- [ ] nunca ve `IN_REVIEW`;
- [ ] nunca ve archivados;
- [ ] nunca ve versión interna no habilitada públicamente;
- [ ] URL directa de borrador no expone contenido;
- [ ] URL directa de artículo solo interno no expone contenido;
- [ ] URL directa de archivado no expone contenido.

### C4. Autoservicio público — `/crear-ticket`

- [ ] aparecen máximo 3 artículos relacionados;
- [ ] solo aparecen revisiones públicas vigentes;
- [ ] abrir sugerencia no obliga a abandonar formulario;
- [ ] `Enviar solicitud` siempre permanece disponible;
- [ ] sin sugerencias el formulario sigue funcionando;
- [ ] archivados no aparecen;
- [ ] borradores no aparecen;
- [ ] artículos solo internos no aparecen.

### C5. CSRF / acciones mutantes

- [ ] POST sin token válido no ejecuta cambio;
- [ ] submit repetido no crea duplicados evidentes;
- [ ] refresh después de POST no repite publicación silenciosamente.

## D. Referencias y candidatos

### D1. Usar como referencia

- [ ] aparece cuando hay sugerencia;
- [ ] no resuelve automáticamente el ticket;
- [ ] precarga Causa cuando existe;
- [ ] precarga Solución;
- [ ] precarga Prevención cuando existe;
- [ ] campos siguen editables;
- [ ] la resolución conserva trazabilidad.

### D2. Candidato a conocimiento

- [ ] solución insuficiente NO genera CTA;
- [ ] solución documentada + señal reutilizable sí genera CTA;
- [ ] CTA = `Crear borrador`;
- [ ] nunca publica automáticamente;
- [ ] conserva origen ticket/problema cuando corresponde.

## E. Evidencia mínima

Guardar capturas de:

1. listado en PC;
2. listado en iPad;
3. detalle activo con `Más acciones` abierto;
4. detalle archivado;
5. detalle en iPad;
6. una vista móvil;
7. TECHNICIAN sin acciones administrativas;
8. ADMIN/SEMIADMIN con acciones administrativas;
9. autoservicio mostrando sugerencias;
10. ticket usando referencia.

## Criterio de cierre

Fase 9 puede cerrarse solo si:

- gates automáticos permanecen GREEN;
- no hay fallos críticos de layout en PC/iPad/móvil;
- permisos coinciden con esta matriz;
- solicitantes no acceden a contenido interno;
- autoservicio solo usa contenido público vigente;
- producción permanece sin cambios hasta aprobación explícita;
- CI remoto queda GREEN o se documenta formalmente una evidencia equivalente aprobada.