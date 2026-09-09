-- Helpdesk Carrousel V2
-- LIMPIEZA CONTROLADA DE DATOS DE PRUEBA
-- Basado en dump: helpdesk_carrousel_test - 2026-09-09 16:47 GT
-- IMPORTANTE: ejecutar únicamente en helpdesk_carrousel_test.
-- No elimina usuarios, parques, regiones, roles, permisos ni asignaciones reales.

USE helpdesk_carrousel_test;
SET NAMES utf8mb4;

START TRANSACTION;

-- =========================================================
-- 1. VALIDACIÓN VISUAL PREVIA
-- =========================================================
SELECT 'ANTES' etapa,id,ticket_number,requester_email,subject,status
FROM tickets
WHERE id IN (1,2,3,4,5,7)
ORDER BY id;

SELECT 'ANTES' etapa,id,problem_number,title,status
FROM known_problems
WHERE id=1;

-- =========================================================
-- 2. AUDITORÍA DE PRUEBAS
-- audit_logs no tiene FK por entidad, por eso se depura explícitamente.
-- =========================================================
DELETE FROM audit_logs
WHERE (entity_type='ticket' AND entity_id IN ('1','2','3','4','5','7'))
   OR (entity_type IN ('problem','known_problem') AND entity_id='1');

-- =========================================================
-- 3. FEEDBACK SIN FK + TICKETS DE PRUEBA
-- Las demás tablas de ticket tienen ON DELETE CASCADE donde corresponde.
-- =========================================================
DELETE FROM ticket_feedback
WHERE ticket_id IN (1,2,3,4,5,7);

DELETE FROM tickets
WHERE id IN (1,2,3,4,5,7)
  AND (
      LOWER(TRIM(description))='esto es una prueba'
      OR LOWER(TRIM(subject)) LIKE '%prueba%'
      OR LOWER(TRIM(requester_name)) LIKE '%prueba%'
  );

-- =========================================================
-- 4. PROBLEMA CONOCIDO CREADO PARA PRUEBAS
-- Sus ocurrencias, etiquetas, adjuntos y relaciones se eliminan por cascada.
-- =========================================================
DELETE FROM known_problems
WHERE id=1
  AND LOWER(TRIM(title)) LIKE '%prueba%'
  AND LOWER(TRIM(description))='esto es una prueba';

-- =========================================================
-- 5. ÁREAS LEGACY IMPORTADAS Y YA INACTIVAS
-- Solo se borran si no poseen ninguna referencia.
-- =========================================================
DELETE a
FROM areas a
LEFT JOIN user_assignments ua ON ua.area_id=a.id
LEFT JOIN tickets t ON t.area_id=a.id AND t.deleted_at IS NULL
WHERE a.id BETWEEN 5 AND 12
  AND a.code LIKE 'CC_AREA_%'
  AND a.is_active=0
  AND ua.id IS NULL
  AND t.id IS NULL;

-- =========================================================
-- 6. NORMALIZACIÓN MENOR DE CATÁLOGO
-- =========================================================
UPDATE ticket_categories
SET code='SEMNOX',updated_at=NOW()
WHERE LOWER(code)='semnox' AND code<>'SEMNOX';

-- =========================================================
-- 7. COMPROBACIÓN POSTERIOR
-- =========================================================
SELECT 'DESPUES' etapa,COUNT(*) tickets_restantes
FROM tickets
WHERE deleted_at IS NULL;

SELECT id,ticket_number,requester_email,requester_name,subject,status,created_at
FROM tickets
WHERE deleted_at IS NULL
ORDER BY id;

SELECT 'DESPUES' etapa,COUNT(*) problemas_restantes
FROM known_problems;

SELECT 'DESPUES' etapa,COUNT(*) areas_legacy_restantes
FROM areas
WHERE code LIKE 'CC_AREA_%';

-- Mantener COMMIT al final permite revisar el script completo antes de ejecutarlo.
COMMIT;

SELECT 'OK - limpieza controlada completada' AS resultado;
