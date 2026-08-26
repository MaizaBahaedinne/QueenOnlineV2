-- ============================================================================
-- MIGRATION MEME SERVEUR AVEC MAPPING (SOURCE -> CIBLE)
-- ============================================================================
-- Source legacy: quee_QueenDB
-- Cible Laravel: quee_QueenBD (a adapter)
--
-- Objectif:
-- - importer clients, salles, reservations salle
-- - garder un mapping explicite old_id -> new_id
-- - securiser la migration avec transaction + verifications
--
-- IMPORTANT:
-- 1) Lancer d'abord les migrations Laravel (php artisan migrate)
-- 2) Tester ce script sur copie de base avant production
-- 3) Par defaut, finir par ROLLBACK pour dry-run
-- ============================================================================

USE quee_QueenBD;

SET SESSION sql_mode = 'STRICT_TRANS_TABLES';
SET FOREIGN_KEY_CHECKS = 0;

START TRANSACTION;

-- --------------------------------------------------------------------------
-- A) TABLES DE MAPPING PERSISTANTES (audit et reprise)
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS migration_map_clients (
    old_user_id INT NOT NULL PRIMARY KEY,
    new_client_id BIGINT NOT NULL,
    mapped_by VARCHAR(40) NOT NULL DEFAULT 'script',
    mapped_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS migration_map_salles (
    old_salle_id INT NOT NULL PRIMARY KEY,
    new_salle_id BIGINT NOT NULL,
    mapped_by VARCHAR(40) NOT NULL DEFAULT 'script',
    mapped_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS migration_map_reservations (
    old_reservation_id INT NOT NULL PRIMARY KEY,
    new_reservation_id BIGINT NOT NULL,
    mapped_by VARCHAR(40) NOT NULL DEFAULT 'script',
    mapped_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- --------------------------------------------------------------------------
-- B) PRE-CHECK RAPIDE
-- --------------------------------------------------------------------------
SELECT 'legacy_clients' AS metric, COUNT(*) AS total
FROM quee_QueenDB.tbl_users u
WHERE u.roleId = 4
UNION ALL
SELECT 'legacy_salles' AS metric, COUNT(*) AS total
FROM quee_QueenDB.tbl_salle
UNION ALL
SELECT 'legacy_reservations' AS metric, COUNT(*) AS total
FROM quee_QueenDB.tbl_reservation;

-- --------------------------------------------------------------------------
-- C) IMPORT CLIENTS + MAPPING
-- --------------------------------------------------------------------------
-- Regle principale: dedoublonner d'abord sur CIN, sinon sur (nom, prenom, mobile)

INSERT INTO clients (
    user_id,
    client_type,
    fiscal_number,
    company_name,
    first_name,
    name,
    gender,
    birth_date,
    email,
    phone,
    phone_label_1,
    phone_2,
    phone_label_2,
    cin,
    date_cin,
    address_number,
    address_street,
    city,
    governorate,
    source,
    note,
    status,
    created_at,
    updated_at
)
SELECT
    NULL,
    CASE WHEN u.type = 'societe' THEN 'societe' ELSE 'personne-physique' END,
    NULL,
    CASE WHEN u.type = 'societe' THEN NULLIF(u.raisonSocial, '') ELSE NULL END,
    NULLIF(u.prenom, ''),
    NULLIF(u.nom, ''),
    NULLIF(u.sexe, ''),
    u.birthday,
    NULLIF(u.email, ''),
    NULLIF(CAST(u.mobile AS CHAR), ''),
    NULL,
    NULLIF(CAST(u.mobile2 AS CHAR), ''),
    NULL,
    NULLIF(u.cin, ''),
    u.dateCin,
    NULLIF(CAST(u.n AS CHAR), ''),
    NULLIF(u.rue, ''),
    NULLIF(u.ville, ''),
    NULL,
    CASE
        WHEN u.source = 'passager' THEN 'passager'
        WHEN u.source = 'presence-event' THEN 'presence-event'
        WHEN u.source = 'reseaux-sociaux-web' THEN 'reseaux-sociaux-web'
        WHEN u.source = 'recommandation' THEN 'recommandation'
        WHEN u.source = 'connaissance-queenpark' THEN 'connaissance-queenpark'
        ELSE 'passager'
    END,
    CONCAT('Legacy userId=', u.userId),
    'active',
    COALESCE(u.createdDtm, NOW()),
    COALESCE(u.updatedDtm, NOW())
FROM quee_QueenDB.tbl_users u
WHERE u.roleId = 4
AND NOT EXISTS (
    SELECT 1
    FROM clients c
    WHERE (
        (NULLIF(u.cin, '') IS NOT NULL AND c.cin = NULLIF(u.cin, ''))
        OR (
            NULLIF(u.cin, '') IS NULL
            AND LOWER(COALESCE(c.name, '')) = LOWER(COALESCE(NULLIF(u.nom, ''), ''))
            AND LOWER(COALESCE(c.first_name, '')) = LOWER(COALESCE(NULLIF(u.prenom, ''), ''))
            AND COALESCE(c.phone, '') = COALESCE(NULLIF(CAST(u.mobile AS CHAR), ''), '')
        )
    )
);

-- Construire mapping clients (CIN prioritaire, sinon nom/prenom/mobile)
INSERT INTO migration_map_clients (old_user_id, new_client_id, mapped_by)
SELECT
    u.userId,
    c.id,
    'script'
FROM quee_QueenDB.tbl_users u
JOIN clients c
    ON (
        (NULLIF(u.cin, '') IS NOT NULL AND c.cin = NULLIF(u.cin, ''))
        OR (
            NULLIF(u.cin, '') IS NULL
            AND LOWER(COALESCE(c.name, '')) = LOWER(COALESCE(NULLIF(u.nom, ''), ''))
            AND LOWER(COALESCE(c.first_name, '')) = LOWER(COALESCE(NULLIF(u.prenom, ''), ''))
            AND COALESCE(c.phone, '') = COALESCE(NULLIF(CAST(u.mobile AS CHAR), ''), '')
        )
    )
WHERE u.roleId = 4
ON DUPLICATE KEY UPDATE
    new_client_id = VALUES(new_client_id),
    mapped_by = 'script',
    mapped_at = NOW();

-- --------------------------------------------------------------------------
-- D) IMPORT SALLES + MAPPING
-- --------------------------------------------------------------------------
INSERT INTO salles (
    name,
    salle_type,
    color_code,
    capacity,
    price_per_day,
    status,
    location,
    description,
    created_at,
    updated_at
)
SELECT
    NULLIF(s.nom, ''),
    CASE WHEN LOWER(COALESCE(s.type, '')) IN ('plein-air', 'plein air', 'air') THEN 'plein-air' ELSE 'couvert' END,
    '#3b82f6',
    COALESCE(s.capacité, 0),
    COALESCE(s.Prix, 0),
    CASE WHEN LOWER(COALESCE(s.etat, 'active')) IN ('active', 'actif') THEN 'active' ELSE 'inactive' END,
    NULL,
    NULLIF(s.description, ''),
    NOW(),
    NOW()
FROM quee_QueenDB.tbl_salle s
WHERE NOT EXISTS (
    SELECT 1 FROM salles dst
    WHERE LOWER(dst.name) = LOWER(COALESCE(NULLIF(s.nom, ''), ''))
);

INSERT INTO migration_map_salles (old_salle_id, new_salle_id, mapped_by)
SELECT
    s.salleID,
    dst.id,
    'script'
FROM quee_QueenDB.tbl_salle s
JOIN salles dst
    ON LOWER(dst.name) = LOWER(COALESCE(NULLIF(s.nom, ''), ''))
ON DUPLICATE KEY UPDATE
    new_salle_id = VALUES(new_salle_id),
    mapped_by = 'script',
    mapped_at = NOW();

-- --------------------------------------------------------------------------
-- E) IMPORT RESERVATIONS SALLE + MAPPING
-- --------------------------------------------------------------------------
INSERT INTO reservations (
    client_id,
    salle_id,
    service_slug,
    user_id,
    title,
    guest_count,
    event_type,
    start_date,
    end_date,
    start_time,
    end_time,
    payment_due_date,
    status,
    total_amount,
    note_admin,
    created_at,
    updated_at
)
SELECT
    mc.new_client_id,
    ms.new_salle_id,
    'salles',
    NULL,
    NULLIF(r.titre, ''),
    NULL,
    NULL,
    r.dateDebut,
    COALESCE(r.dateFin, r.dateDebut),
    COALESCE(r.heureDebut, '08:00:00'),
    COALESCE(r.heureFin, '23:59:00'),
    COALESCE(r.demandeEcheance, DATE_SUB(r.dateDebut, INTERVAL 30 DAY)),
    CASE
        WHEN COALESCE(r.statut, 0) IN (2, 3) THEN 'cancelled'
        WHEN COALESCE(r.statut, 0) IN (1, 4) THEN 'confirmed'
        ELSE 'pending'
    END,
    COALESCE(r.prix, 0),
    CONCAT(
        'Legacy reservationID=', r.reservationID,
        ' | legacy clientId=', r.clientId,
        ' | legacy salleId=', r.salleId,
        ' | legacy noteAdmin=', COALESCE(r.noteAdmin, '')
    ),
    COALESCE(r.createdDTM, NOW()),
    COALESCE(r.createdDTM, NOW())
FROM quee_QueenDB.tbl_reservation r
JOIN migration_map_clients mc ON mc.old_user_id = r.clientId
JOIN migration_map_salles ms ON ms.old_salle_id = r.salleId
WHERE NOT EXISTS (
    SELECT 1
    FROM reservations dst
    WHERE dst.note_admin LIKE CONCAT('%Legacy reservationID=', r.reservationID, '%')
);

INSERT INTO migration_map_reservations (old_reservation_id, new_reservation_id, mapped_by)
SELECT
    r.reservationID,
    dst.id,
    'script'
FROM quee_QueenDB.tbl_reservation r
JOIN reservations dst
    ON dst.note_admin LIKE CONCAT('%Legacy reservationID=', r.reservationID, '%')
ON DUPLICATE KEY UPDATE
    new_reservation_id = VALUES(new_reservation_id),
    mapped_by = 'script',
    mapped_at = NOW();

-- --------------------------------------------------------------------------
-- F) CONTROLES POST-MIGRATION
-- --------------------------------------------------------------------------
SELECT 'mapped_clients' AS metric, COUNT(*) AS total FROM migration_map_clients
UNION ALL
SELECT 'mapped_salles' AS metric, COUNT(*) AS total FROM migration_map_salles
UNION ALL
SELECT 'mapped_reservations' AS metric, COUNT(*) AS total FROM migration_map_reservations;

SELECT 'clients_without_mapping' AS metric, COUNT(*) AS total
FROM quee_QueenDB.tbl_users u
LEFT JOIN migration_map_clients mc ON mc.old_user_id = u.userId
WHERE u.roleId = 4 AND mc.old_user_id IS NULL
UNION ALL
SELECT 'salles_without_mapping' AS metric, COUNT(*) AS total
FROM quee_QueenDB.tbl_salle s
LEFT JOIN migration_map_salles ms ON ms.old_salle_id = s.salleID
WHERE ms.old_salle_id IS NULL
UNION ALL
SELECT 'reservations_without_mapping' AS metric, COUNT(*) AS total
FROM quee_QueenDB.tbl_reservation r
LEFT JOIN migration_map_reservations mr ON mr.old_reservation_id = r.reservationID
WHERE mr.old_reservation_id IS NULL;

SET FOREIGN_KEY_CHECKS = 1;

-- DRY RUN (recommande d'abord):
ROLLBACK;

-- EXECUTION REELLE (decommenter apres validation des controles):
-- COMMIT;
