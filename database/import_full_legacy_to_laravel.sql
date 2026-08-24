-- ============================================================================
-- MIGRATION COMPLÈTE: quee_QueenDB → quee_QueenBD
-- ============================================================================
-- Objectif: Importer tous les données legacy avec préservation des relations
-- et métadonnées dans note_admin pour traçabilité
-- Utilise le VRAI schéma legacy (colonnes réelles)
-- ============================================================================

USE quee_QueenBD;
SET FOREIGN_KEY_CHECKS = 0;
SET SESSION sql_mode = 'STRICT_TRANS_TABLES';

-- Vider les tables cibles (dans l'ordre inverse des dépendances FK)
DELETE FROM `quee_QueenBD`.payments;
DELETE FROM `quee_QueenBD`.reservations;
DELETE FROM `quee_QueenBD`.service_module_packs;
DELETE FROM `quee_QueenBD`.service_module_items;
DELETE FROM `quee_QueenBD`.clients;
DELETE FROM `quee_QueenBD`.salles;

-- Réinitialiser les auto-increment
ALTER TABLE `quee_QueenBD`.clients AUTO_INCREMENT = 1;
ALTER TABLE `quee_QueenBD`.salles AUTO_INCREMENT = 1;
ALTER TABLE `quee_QueenBD`.service_module_items AUTO_INCREMENT = 1;
ALTER TABLE `quee_QueenBD`.service_module_packs AUTO_INCREMENT = 1;
ALTER TABLE `quee_QueenBD`.reservations AUTO_INCREMENT = 1;
ALTER TABLE `quee_QueenBD`.payments AUTO_INCREMENT = 1;

SET FOREIGN_KEY_CHECKS = 1;

START TRANSACTION;

-- ============================================================================
-- 1) TABLES TEMPORAIRES DE MAPPING
-- ============================================================================

DROP TEMPORARY TABLE IF EXISTS legacy_client_map;
DROP TEMPORARY TABLE IF EXISTS legacy_salle_map;
DROP TEMPORARY TABLE IF EXISTS legacy_reservation_map;

CREATE TEMPORARY TABLE legacy_client_map (
    old_user_id INT NOT NULL PRIMARY KEY,
    new_client_id BIGINT NOT NULL
);

CREATE TEMPORARY TABLE legacy_salle_map (
    old_salle_id INT NOT NULL PRIMARY KEY,
    new_salle_id BIGINT NOT NULL
);

CREATE TEMPORARY TABLE legacy_reservation_map (
    old_reservation_id INT NOT NULL PRIMARY KEY,
    new_reservation_id BIGINT NOT NULL
);

-- ============================================================================
-- 2) IMPORT CLIENTS (tbl_users avec roleId = 4)
-- ============================================================================

INSERT INTO clients (cin, first_name, last_name, phone, phone2, email, governorate, city, address, status, created_at, updated_at)
SELECT DISTINCT
    COALESCE(u.cin, CONCAT('LEGACY_', u.userId)),
    COALESCE(u.prenom, ''),
    COALESCE(u.nom, 'Client Legacy'),
    COALESCE(CAST(u.mobile AS CHAR), ''),
    COALESCE(CAST(u.mobile2 AS CHAR), ''),
    COALESCE(u.email, ''),
    COALESCE(u.ville, ''),
    '',
    CONCAT(COALESCE(CAST(u.n AS CHAR), ''), ' ', COALESCE(u.rue, '')),
    'active',
    NOW(),
    NOW()
FROM `quee_QueenDB`.tbl_users u
WHERE u.roleId = 4
ORDER BY u.userId;

-- Construire la table de mapping clients
INSERT INTO legacy_client_map (old_user_id, new_client_id)
SELECT u.userId, MIN(c.id)
FROM `quee_QueenDB`.tbl_users u
INNER JOIN clients c ON (
    -- Priorité 1: CIN exact
    (u.cin IS NOT NULL AND u.cin != '' AND c.cin = u.cin)
    -- Priorité 2: Pas de CIN et match sur nom/prenom/téléphone
    OR (
        (u.cin IS NULL OR u.cin = '')
        AND CONVERT(c.last_name USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(u.nom USING utf8mb4) COLLATE utf8mb4_unicode_ci
        AND CONVERT(c.first_name USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(u.prenom USING utf8mb4) COLLATE utf8mb4_unicode_ci
        AND (
            c.phone = COALESCE(CAST(u.mobile AS CHAR), '')
            OR c.phone2 = COALESCE(CAST(u.mobile2 AS CHAR), '')
        )
    )
)
WHERE u.roleId = 4
GROUP BY u.userId;

-- ============================================================================
-- 3) IMPORT SALLES (tbl_salle)
-- ============================================================================

INSERT INTO salles (name, capacity, price_per_hour, available_hours, status, created_at, updated_at)
SELECT DISTINCT
    CONVERT(s.nom USING utf8mb4) COLLATE utf8mb4_unicode_ci,
    COALESCE(s.capacité, 0),
    COALESCE(s.Prix, 0),
    '',
    CASE 
        WHEN COALESCE(s.etat, '') IN ('actif', 'active') THEN 'active'
        ELSE 'inactive'
    END,
    NOW(),
    NOW()
FROM `quee_QueenDB`.tbl_salle s
ORDER BY s.salleID;

-- Construire la table de mapping salles
INSERT INTO legacy_salle_map (old_salle_id, new_salle_id)
SELECT s.salleID, MIN(sl.id)
FROM `quee_QueenDB`.tbl_salle s
INNER JOIN salles sl ON (
    CONVERT(sl.name USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(s.nom USING utf8mb4) COLLATE utf8mb4_unicode_ci
    AND sl.capacity = COALESCE(s.capacité, 0)
    AND sl.price_per_hour = COALESCE(s.Prix, 0)
)
GROUP BY s.salleID;

-- ============================================================================
-- 4) IMPORT SERVICE MODULES - ITEMS
-- ============================================================================

-- 4.1) Troupe musicale - items (artistes)
INSERT INTO service_module_items (module_slug, name, phone, base_price, status, notes, created_at, updated_at)
SELECT DISTINCT
    'troupe-musicale',
    CONVERT(a.nom USING utf8mb4) COLLATE utf8mb4_unicode_ci,
    '',
    COALESCE(a.prix, 0),
    'active',
    CONCAT('Artiste legacy ID ', a.artisteId),
    NOW(),
    NOW()
FROM `quee_QueenDB`.tbl_pack_troupe_artiste a
WHERE a.nom IS NOT NULL AND a.nom != '';

-- 4.2) Animation - items (chanteurs/prestations individuelles)
INSERT INTO service_module_items (module_slug, name, phone, base_price, status, notes, created_at, updated_at)
SELECT
    'animation',
    CONVERT(p.nom USING utf8mb4) COLLATE utf8mb4_unicode_ci,
    '',
    COALESCE(p.prix, 0),
    'active',
    CONCAT('Prestataire legacy ID ', p.packId, ' | type=', p.type),
    NOW(),
    NOW()
FROM `quee_QueenDB`.tbl_pack_prestation p
WHERE p.nom IS NOT NULL AND p.nom != '';

-- 4.3) Voiture - items (types de voitures)
INSERT INTO service_module_items (module_slug, name, phone, base_price, status, notes, created_at, updated_at)
SELECT DISTINCT
    'voiture',
    CONVERT(r.voitureName USING utf8mb4) COLLATE utf8mb4_unicode_ci,
    '',
    0,
    'active',
    'Type voiture legacy depuis tbl_reservation_voiture',
    NOW(),
    NOW()
FROM `quee_QueenDB`.tbl_reservation_voiture r
WHERE r.voitureName IS NOT NULL AND r.voitureName != '';

-- ============================================================================
-- 5) IMPORT SERVICE MODULES - PACKS
-- ============================================================================

-- 5.1) Troupe musicale - packs
INSERT INTO service_module_packs (module_slug, name, base_price, status, description, created_at, updated_at)
SELECT
    'troupe-musicale',
    CONVERT(t.nom USING utf8mb4) COLLATE utf8mb4_unicode_ci,
    COALESCE(t.prix, 0),
    'active',
    CONCAT('Pack troupe legacy ID ', t.packId),
    NOW(),
    NOW()
FROM `quee_QueenDB`.tbl_pack_troupe t
WHERE t.nom IS NOT NULL AND t.nom != '';

-- 5.2) Photographe - packs
INSERT INTO service_module_packs (module_slug, name, base_price, status, description, created_at, updated_at)
SELECT
    'photographe',
    CONVERT(p.nom USING utf8mb4) COLLATE utf8mb4_unicode_ci,
    COALESCE(p.prix, 0),
    'active',
    CONCAT('Pack photographe legacy ID ', p.packId),
    NOW(),
    NOW()
FROM `quee_QueenDB`.tbl_pack_photographe p
WHERE p.nom IS NOT NULL AND p.nom != '';

-- 5.3) Animation - packs (tbl_pack_prestation)
INSERT INTO service_module_packs (module_slug, name, base_price, status, description, created_at, updated_at)
SELECT
    'animation',
    CONVERT(p.nom USING utf8mb4) COLLATE utf8mb4_unicode_ci,
    COALESCE(p.prix, 0),
    'active',
    CONCAT('Pack animation legacy ID ', p.packId, ' | type=', p.type),
    NOW(),
    NOW()
FROM `quee_QueenDB`.tbl_pack_prestation p
WHERE p.nom IS NOT NULL AND p.nom != '';

-- ============================================================================
-- 6) IMPORT RESERVATIONS - SALLES
-- ============================================================================

INSERT INTO reservations (client_id, salle_id, reservation_date, start_time, end_time, total_price, payment_status, service_slug, status, note_admin, created_at, updated_at)
SELECT
    lcm.new_client_id,
    lsm.new_salle_id,
    r.dateDebut,
    COALESCE(r.heureDebut, '00:00:00'),
    COALESCE(r.heureFin, '23:59:59'),
    COALESCE(r.prix, 0),
    'pending',
    'salle',
    CASE 
        WHEN COALESCE(r.statut, 0) = 1 THEN 'confirmed'
        ELSE 'pending'
    END,
    CONCAT(
        'Legacy reservation ID: ', r.reservationID,
        ' | clientId: ', r.clientId,
        ' | salleId: ', r.salleId,
        ' | dateDebut: ', r.dateDebut,
        ' | dateFin: ', r.dateFin,
        ' | prix: ', COALESCE(r.prix, 0),
        ' | statut: ', r.statut,
        ' | titre: ', COALESCE(r.titre, ''),
        ' | type: ', COALESCE(r.type, ''),
        ' | nbPlace: ', COALESCE(r.nbPlace, 0),
        ' | noteAdmin: ', COALESCE(r.noteAdmin, '')
    ),
    NOW(),
    NOW()
FROM `quee_QueenDB`.tbl_reservation r
INNER JOIN legacy_client_map lcm ON r.clientId = lcm.old_user_id
INNER JOIN legacy_salle_map lsm ON r.salleId = lsm.old_salle_id;

-- Construire la table de mapping réservations
INSERT INTO legacy_reservation_map (old_reservation_id, new_reservation_id)
SELECT r.reservationID, res.id
FROM `quee_QueenDB`.tbl_reservation r
INNER JOIN reservations res ON (
    DATE(res.reservation_date) = DATE(r.dateDebut)
    AND res.service_slug = 'salle'
    AND res.note_admin LIKE CONCAT('%Legacy reservation ID: ', r.reservationID, '%')
)
ORDER BY r.reservationID;

-- ============================================================================
-- 7) IMPORT RESERVATIONS - SERVICES (photographe, prestation, troupe, voiture)
-- ============================================================================

-- 7.1) Photographe
INSERT INTO reservations (client_id, salle_id, reservation_date, start_time, end_time, total_price, payment_status, service_slug, status, user_id, note_admin, created_at, updated_at)
SELECT
    parent.client_id,
    parent.salle_id,
    parent.reservation_date,
    parent.start_time,
    parent.end_time,
    COALESCE(rp.prix, 0),
    'pending',
    'photographe',
    CASE 
        WHEN COALESCE(rp.statut, 0) = 1 THEN 'confirmed'
        ELSE 'pending'
    END,
    CASE r.createdBy
        WHEN 32 THEN 4
        WHEN 25 THEN 2
        WHEN 1 THEN 1
        WHEN 5 THEN 33
        WHEN 400 THEN 3
        ELSE NULL
    END,
    CONCAT(
        'Legacy reservation photographe ID: ', rp.reservationPId,
        ' | parent_salle_reservation: ', r.reservationID,
        ' | date: ', rp.date,
        ' | prix: ', COALESCE(rp.prix, 0),
        ' | createdBy: ', r.createdBy
    ),
    NOW(),
    NOW()
FROM `quee_QueenDB`.tbl_reservation_photographe rp
INNER JOIN `quee_QueenDB`.tbl_reservation r ON rp.reservationId = r.reservationID
INNER JOIN reservations parent ON parent.note_admin LIKE CONCAT('%Legacy reservation ID: ', r.reservationID, '%');

-- 7.2) Animation (prestation)
INSERT INTO reservations (client_id, salle_id, reservation_date, start_time, end_time, total_price, payment_status, service_slug, status, user_id, note_admin, created_at, updated_at)
SELECT
    parent.client_id,
    parent.salle_id,
    parent.reservation_date,
    parent.start_time,
    parent.end_time,
    COALESCE(rpr.prix, 0),
    'pending',
    'animation',
    CASE 
        WHEN COALESCE(rpr.statut, 0) = 1 THEN 'confirmed'
        ELSE 'pending'
    END,
    CASE r.createdBy
        WHEN 32 THEN 4
        WHEN 25 THEN 2
        WHEN 1 THEN 1
        WHEN 5 THEN 33
        WHEN 400 THEN 3
        ELSE NULL
    END,
    CONCAT(
        'Legacy reservation prestation ID: ', rpr.prestationId,
        ' | parent_salle_reservation: ', r.reservationID,
        ' | date: ', rpr.date,
        ' | heure: ', rpr.heure,
        ' | prix: ', COALESCE(rpr.prix, 0),
        ' | createdBy: ', r.createdBy
    ),
    NOW(),
    NOW()
FROM `quee_QueenDB`.tbl_reservation_prestation rpr
INNER JOIN `quee_QueenDB`.tbl_reservation r ON rpr.reservationId = r.reservationID
INNER JOIN reservations parent ON parent.note_admin LIKE CONCAT('%Legacy reservation ID: ', r.reservationID, '%');

-- 7.3) Troupe-musicale
INSERT INTO reservations (client_id, salle_id, reservation_date, start_time, end_time, total_price, payment_status, service_slug, status, user_id, note_admin, created_at, updated_at)
SELECT
    parent.client_id,
    parent.salle_id,
    parent.reservation_date,
    parent.start_time,
    parent.end_time,
    COALESCE(rt.prix, 0),
    'pending',
    'troupe-musicale',
    CASE 
        WHEN COALESCE(rt.statut, 0) = 1 THEN 'confirmed'
        ELSE 'pending'
    END,
    CASE r.createdBy
        WHEN 32 THEN 4
        WHEN 25 THEN 2
        WHEN 1 THEN 1
        WHEN 5 THEN 33
        WHEN 400 THEN 3
        ELSE NULL
    END,
    CONCAT(
        'Legacy reservation troupe ID: ', rt.reservationTId,
        ' | parent_salle_reservation: ', r.reservationID,
        ' | date: ', rt.date,
        ' | prix: ', COALESCE(rt.prix, 0),
        ' | chanteurs: ', COALESCE(rt.chanteurs, ''),
        ' | createdBy: ', r.createdBy
    ),
    NOW(),
    NOW()
FROM `quee_QueenDB`.tbl_reservation_troupe rt
INNER JOIN `quee_QueenDB`.tbl_reservation r ON rt.reservationId = r.reservationID
INNER JOIN reservations parent ON parent.note_admin LIKE CONCAT('%Legacy reservation ID: ', r.reservationID, '%');

-- 7.4) Voiture
INSERT INTO reservations (client_id, salle_id, reservation_date, start_time, end_time, total_price, payment_status, service_slug, status, user_id, note_admin, created_at, updated_at)
SELECT
    parent.client_id,
    parent.salle_id,
    parent.reservation_date,
    parent.start_time,
    parent.end_time,
    COALESCE(rv.prix, 0),
    'pending',
    'voiture',
    CASE 
        WHEN COALESCE(rv.statut, 0) = 1 THEN 'confirmed'
        ELSE 'pending'
    END,
    CASE r.createdBy
        WHEN 32 THEN 4
        WHEN 25 THEN 2
        WHEN 1 THEN 1
        WHEN 5 THEN 33
        WHEN 400 THEN 3
        ELSE NULL
    END,
    CONCAT(
        'Legacy reservation voiture ID: ', rv.reservationVId,
        ' | parent_salle_reservation: ', r.reservationID,
        ' | date: ', rv.date,
        ' | voitureName: ', COALESCE(rv.voitureName, ''),
        ' | prix: ', COALESCE(rv.prix, 0),
        ' | createdBy: ', r.createdBy
    ),
    NOW(),
    NOW()
FROM `quee_QueenDB`.tbl_reservation_voiture rv
INNER JOIN `quee_QueenDB`.tbl_reservation r ON rv.reservationId = r.reservationID
INNER JOIN reservations parent ON (parent.note_admin LIKE CONCAT('%Legacy reservation ID: ', r.reservationID, '%') AND parent.salle_id IS NOT NULL);

-- ============================================================================
-- 8) IMPORT PAIEMENTS - SALLE (tbl_paiement)
-- ============================================================================

INSERT INTO payments (reservation_id, amount, payment_method, payment_date, status, user_id, notes, created_at, updated_at)
SELECT
    parent.id,
    COALESCE(p.valeur, 0),
    'cash',
    COALESCE(p.createdDate, NOW()),
    'pending',
    CASE p.recepteurId
        WHEN 32 THEN 4
        WHEN 25 THEN 2
        WHEN 1 THEN 1
        WHEN 5 THEN 33
        WHEN 400 THEN 3
        ELSE NULL
    END,
    CONCAT(
        'Legacy paiement salle ID: ', p.paiementId,
        ' | legacy_reservation_id: ', p.reservationId,
        ' | valeur: ', COALESCE(p.valeur, 0),
        ' | libele: ', COALESCE(p.libele, ''),
        ' | recepteurId: ', p.recepteurId
    ),
    NOW(),
    NOW()
FROM `quee_QueenDB`.tbl_paiement p
INNER JOIN `quee_QueenDB`.tbl_reservation r ON p.reservationId = r.reservationID
INNER JOIN reservations parent ON (parent.note_admin LIKE CONCAT('%Legacy reservation ID: ', r.reservationID, '%') AND parent.service_slug = 'salle');

-- ============================================================================
-- 9) IMPORT PAIEMENTS - SERVICES
-- ============================================================================

-- 9.1) Paiements photographe
INSERT INTO payments (reservation_id, amount, payment_method, payment_date, status, user_id, notes, created_at, updated_at)
SELECT
    parent.id,
    COALESCE(p.valeur, 0),
    'cash',
    COALESCE(p.createdDate, NOW()),
    'pending',
    CASE p.recepteurId
        WHEN 32 THEN 4
        WHEN 25 THEN 2
        WHEN 1 THEN 1
        WHEN 5 THEN 33
        WHEN 400 THEN 3
        ELSE NULL
    END,
    CONCAT(
        'Legacy paiement photographe ID: ', p.paiementId,
        ' | valeur: ', COALESCE(p.valeur, 0)
    ),
    NOW(),
    NOW()
FROM `quee_QueenDB`.tbl_paiement_photographe p
INNER JOIN `quee_QueenDB`.tbl_reservation_photographe rp ON p.reservationPId = rp.reservationPId
INNER JOIN `quee_QueenDB`.tbl_reservation r ON rp.reservationId = r.reservationID
INNER JOIN reservations parent ON (parent.note_admin LIKE CONCAT('%Legacy reservation photographe ID: ', rp.reservationPId, '%'));

-- 9.2) Paiements prestation/animation
INSERT INTO payments (reservation_id, amount, payment_method, payment_date, status, user_id, notes, created_at, updated_at)
SELECT
    parent.id,
    COALESCE(p.valeur, 0),
    'cash',
    COALESCE(p.createdDate, NOW()),
    'pending',
    CASE p.recepteurId
        WHEN 32 THEN 4
        WHEN 25 THEN 2
        WHEN 1 THEN 1
        WHEN 5 THEN 33
        WHEN 400 THEN 3
        ELSE NULL
    END,
    CONCAT(
        'Legacy paiement prestation ID: ', p.paiementId,
        ' | valeur: ', COALESCE(p.valeur, 0)
    ),
    NOW(),
    NOW()
FROM `quee_QueenDB`.tbl_paiement_prestation p
INNER JOIN `quee_QueenDB`.tbl_reservation_prestation rpr ON p.reservationPresId = rpr.prestationId
INNER JOIN `quee_QueenDB`.tbl_reservation r ON rpr.reservationId = r.reservationID
INNER JOIN reservations parent ON (parent.note_admin LIKE CONCAT('%Legacy reservation prestation ID: ', rpr.prestationId, '%'));

-- 9.3) Paiements troupe
INSERT INTO payments (reservation_id, amount, payment_method, payment_date, status, user_id, notes, created_at, updated_at)
SELECT
    parent.id,
    COALESCE(p.valeur, 0),
    'cash',
    COALESCE(p.createdDate, NOW()),
    'pending',
    CASE p.recepteurId
        WHEN 32 THEN 4
        WHEN 25 THEN 2
        WHEN 1 THEN 1
        WHEN 5 THEN 33
        WHEN 400 THEN 3
        ELSE NULL
    END,
    CONCAT(
        'Legacy paiement troupe ID: ', p.paiementId,
        ' | valeur: ', COALESCE(p.valeur, 0)
    ),
    NOW(),
    NOW()
FROM `quee_QueenDB`.tbl_paiement_troupe p
INNER JOIN `quee_QueenDB`.tbl_reservation_troupe rt ON p.reservationTroupeId = rt.reservationTId
INNER JOIN `quee_QueenDB`.tbl_reservation r ON rt.reservationId = r.reservationID
INNER JOIN reservations parent ON (parent.note_admin LIKE CONCAT('%Legacy reservation troupe ID: ', rt.reservationTId, '%'));

-- 9.4) Paiements voiture
INSERT INTO payments (reservation_id, amount, payment_method, payment_date, status, user_id, notes, created_at, updated_at)
SELECT
    parent.id,
    COALESCE(p.valeur, 0),
    'cash',
    COALESCE(p.createdDate, NOW()),
    'pending',
    CASE p.recepteurId
        WHEN 32 THEN 4
        WHEN 25 THEN 2
        WHEN 1 THEN 1
        WHEN 5 THEN 33
        WHEN 400 THEN 3
        ELSE NULL
    END,
    CONCAT(
        'Legacy paiement voiture ID: ', p.paiementId,
        ' | valeur: ', COALESCE(p.valeur, 0)
    ),
    NOW(),
    NOW()
FROM `quee_QueenDB`.tbl_paiement_voiture p
INNER JOIN `quee_QueenDB`.tbl_reservation_voiture rv ON p.reservationVId = rv.reservationVId
INNER JOIN `quee_QueenDB`.tbl_reservation r ON rv.reservationId = r.reservationID
INNER JOIN reservations parent ON (parent.note_admin LIKE CONCAT('%Legacy reservation voiture ID: ', rv.reservationVId, '%'));

-- ============================================================================
-- FINALISATION
-- ============================================================================

COMMIT;

-- Réactiver les vérifications de clé étrangère
SET FOREIGN_KEY_CHECKS = 1;

-- Afficher un résumé des imports
SELECT 'RÉSUMÉ DE L\'IMPORT' AS section;
SELECT COUNT(*) AS clients_importes FROM clients;
SELECT COUNT(*) AS salles_importes FROM salles;
SELECT COUNT(*) AS service_items_importes FROM service_module_items;
SELECT COUNT(*) AS service_packs_importes FROM service_module_packs;
SELECT COUNT(*) AS reservations_importees FROM reservations WHERE service_slug = 'salle';
SELECT COUNT(*) AS reservations_services FROM reservations WHERE service_slug IN ('photographe', 'animation', 'troupe-musicale', 'voiture');
SELECT COUNT(*) AS paiements_importes FROM payments;

-- FIN DU SCRIPT D'IMPORT
