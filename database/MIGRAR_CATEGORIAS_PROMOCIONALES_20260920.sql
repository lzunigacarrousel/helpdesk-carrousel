USE carrousel_helpdesk;
SET NAMES utf8mb4;

-- =========================================================
-- CATEGORIAS PROMOCIONALES: SEMNOX VS PAYOUT
-- Corrige etiquetas ambiguas y reclasifica SOLO casos con
-- evidencia inequívoca de que corresponden a Payout.
-- Idempotente. No elimina datos.
-- =========================================================

START TRANSACTION;

UPDATE ticket_categories
SET name='Promoción de venta en Semnox / Parafait',
    description='Promociones o codigos configurados y utilizados dentro de Semnox/Parafait; no usar para dar salida en Payout.'
WHERE code='SEMNOX_PROMO';

UPDATE ticket_categories
SET name='Promocional o código para dar salida',
    description='Codigos, promocionales o inventario que no aparecen al dar salida o registrar informacion en Payout.'
WHERE code='PAYOUT_PROMOS';

SET @semnox_promo_id := (SELECT id FROM ticket_categories WHERE code='SEMNOX_PROMO' LIMIT 1);
SET @payout_promo_id := (SELECT id FROM ticket_categories WHERE code='PAYOUT_PROMOS' LIMIT 1);

UPDATE tickets t
SET t.category_id=@payout_promo_id,
    t.updated_at=NOW()
WHERE @semnox_promo_id IS NOT NULL
  AND @payout_promo_id IS NOT NULL
  AND t.category_id=@semnox_promo_id
  AND t.deleted_at IS NULL
  AND (
      LOWER(CONCAT_WS(' ',t.subject,t.description)) REGEXP 'dar[[:space:]]+salida'
      OR LOWER(CONCAT_WS(' ',t.subject,t.description)) REGEXP 'darle[[:space:]]+salida'
      OR LOWER(CONCAT_WS(' ',t.subject,t.description)) REGEXP 'salida[[:space:]]+(de|del)[[:space:]]+(promocional|codigo|código)'
      OR LOWER(CONCAT_WS(' ',t.subject,t.description)) LIKE '%payout%'
  );

SET @tickets_reclassified := ROW_COUNT();

INSERT INTO schema_migrations(version,name)
VALUES('2026-09-20-promo-category-routing','Distinguir promociones Semnox de promocionales para salida en Payout')
ON DUPLICATE KEY UPDATE name=VALUES(name);

COMMIT;

SELECT @tickets_reclassified AS tickets_reclasificados,
       (SELECT name FROM ticket_categories WHERE code='SEMNOX_PROMO' LIMIT 1) AS categoria_semnox,
       (SELECT name FROM ticket_categories WHERE code='PAYOUT_PROMOS' LIMIT 1) AS categoria_payout;
