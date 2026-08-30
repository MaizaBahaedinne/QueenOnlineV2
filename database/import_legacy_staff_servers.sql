-- ============================================================================
-- IMPORT LEGACY -> RH: SERVEURS / STAFF EVENEMENT
-- ============================================================================
-- Source legacy: quee_QueenDB
-- Cible Laravel: quee_QueenBD (adapter si besoin)
--
-- Objectif:
-- 1) Importer les personnes staff depuis tbl_users + tbl_roles
-- 2) Creer users (si absent)
-- 3) Creer staff (si absent) avec fonction + departement normalises
-- 4) Mapper old_user_id -> new_user_id pour extraction avatar BLOB via commande Laravel
--
-- IMPORTANT:
-- - Teste d'abord en dry-run (ROLLBACK)
-- - Verifie que les tables users, staff, departments existent
-- ============================================================================

USE quee_QueenBD;

SET SESSION sql_mode = 'STRICT_TRANS_TABLES';
SET FOREIGN_KEY_CHECKS = 0;
START TRANSACTION;

-- Mapping legacy staff -> users Laravel (necessaire pour importer avatars BLOB)
CREATE TABLE IF NOT EXISTS migration_map_staff_users (
    old_user_id INT NOT NULL PRIMARY KEY,
    new_user_id BIGINT UNSIGNED NOT NULL,
    mapped_by VARCHAR(40) NOT NULL DEFAULT 'script',
    mapped_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_new_user_id (new_user_id)
);

-- --------------------------------------------------------------------------
-- A) DEPARTEMENTS PREDEFINIS (RH)
-- --------------------------------------------------------------------------
INSERT INTO departments (name, status, created_at, updated_at)
SELECT t.name, 'active', NOW(), NOW()
FROM (
    SELECT 'Direction' AS name
    UNION ALL SELECT 'Commercial'
    UNION ALL SELECT 'Marketing'
    UNION ALL SELECT 'Maintenance'
    UNION ALL SELECT 'Securite'
    UNION ALL SELECT 'Artistique'
    UNION ALL SELECT 'Services des receptions'
) AS t
LEFT JOIN departments d ON LOWER(d.name) = LOWER(t.name)
WHERE d.id IS NULL;

-- --------------------------------------------------------------------------
-- B) EXTRACTION DES CANDIDATS STAFF LEGACY
-- --------------------------------------------------------------------------
DROP TEMPORARY TABLE IF EXISTS tmp_legacy_staff_candidates;
CREATE TEMPORARY TABLE tmp_legacy_staff_candidates AS
SELECT
    u.userId,
    u.email,
    u.password,
    u.mobile,
    u.cin,
    u.matricule,
    u.nom,
    u.prenom,
    u.name AS legacy_name,
    u.departement AS legacy_department,
    u.avatar,
    u.dateCin,
    u.createdDtm,
    u.updatedDtm,
    r.role AS legacy_role,
    CASE
        WHEN LOWER(CONCAT(COALESCE(r.role, ''), ' ', COALESCE(u.departement, ''))) REGEXP 'secur' THEN 'Agent de securite'
        WHEN LOWER(CONCAT(COALESCE(r.role, ''), ' ', COALESCE(u.departement, ''))) REGEXP 'menage|femme' THEN 'Femme de menage'
        WHEN LOWER(CONCAT(COALESCE(r.role, ''), ' ', COALESCE(u.departement, ''))) REGEXP 'anim' THEN 'Annimateur'
        WHEN LOWER(CONCAT(COALESCE(r.role, ''), ' ', COALESCE(u.departement, ''))) REGEXP 'chef[[:space:]]*service|service' THEN 'Chef Service'
        ELSE NULL
    END AS mapped_position_title,
    CASE
        WHEN LOWER(CONCAT(COALESCE(r.role, ''), ' ', COALESCE(u.departement, ''))) REGEXP 'secur' THEN 'Securite'
        WHEN LOWER(CONCAT(COALESCE(r.role, ''), ' ', COALESCE(u.departement, ''))) REGEXP 'anim' THEN 'Artistique'
        ELSE 'Services des receptions'
    END AS mapped_department_name
FROM quee_QueenDB.tbl_users u
LEFT JOIN quee_QueenDB.tbl_roles r ON r.roleId = u.roleId
WHERE COALESCE(u.isDeleted, 0) = 0
  AND (
      LOWER(COALESCE(r.role, '')) REGEXP 'serveur|service|reception|anim|secur|menage|femme'
      OR LOWER(COALESCE(u.departement, '')) REGEXP 'service|reception|anim|secur|menage|femme'
  );

-- Tu peux auditer les candidats avant import:
SELECT 'legacy_staff_candidates' AS metric, COUNT(*) AS total FROM tmp_legacy_staff_candidates;
SELECT userId, legacy_role, legacy_department, mapped_position_title, mapped_department_name
FROM tmp_legacy_staff_candidates
ORDER BY userId
LIMIT 50;

-- --------------------------------------------------------------------------
-- C) CREATION DES USERS (SI ABSENTS)
-- --------------------------------------------------------------------------
-- Note password:
-- - si password legacy est vide, on met une valeur placeholder.
-- - pour connexion Laravel, prevoir reset de mot de passe ensuite.
INSERT INTO users (name, email, password, role_id, phone, cin, status, created_at, updated_at)
SELECT
    TRIM(
        COALESCE(NULLIF(CONCAT(COALESCE(c.prenom, ''), ' ', COALESCE(c.nom, '')), ' '), NULLIF(c.legacy_name, ''), CONCAT('Legacy User #', c.userId))
    ) AS mapped_name,
    LOWER(
        COALESCE(
            NULLIF(TRIM(c.email), ''),
            CONCAT('legacy-staff-', c.userId, '@no-mail.local')
        )
    ) AS mapped_email,
    COALESCE(NULLIF(c.password, ''), CONCAT('legacy-import-', c.userId)) AS mapped_password,
    NULL AS role_id,
    NULLIF(CAST(c.mobile AS CHAR), '') AS phone,
    NULLIF(c.cin, '') AS cin,
    'active' AS status,
    COALESCE(c.createdDtm, NOW()) AS created_at,
    COALESCE(c.updatedDtm, NOW()) AS updated_at
FROM tmp_legacy_staff_candidates c
LEFT JOIN users u
    ON u.email = LOWER(
        COALESCE(
            NULLIF(TRIM(c.email), ''),
            CONCAT('legacy-staff-', c.userId, '@no-mail.local')
        )
    )
WHERE c.mapped_position_title IS NOT NULL
  AND u.id IS NULL;

-- --------------------------------------------------------------------------
-- D) MAPPING user legacy -> user laravel
-- --------------------------------------------------------------------------
DROP TEMPORARY TABLE IF EXISTS tmp_user_map;
CREATE TEMPORARY TABLE tmp_user_map AS
SELECT
    c.userId AS old_user_id,
    u.id AS new_user_id,
    c.mapped_position_title,
    c.mapped_department_name,
    c.matricule,
    c.nom,
    c.prenom,
    c.legacy_name,
    c.cin,
    c.mobile,
    c.dateCin,
    c.avatar,
    c.createdDtm,
    c.updatedDtm
FROM tmp_legacy_staff_candidates c
JOIN users u
    ON u.email = LOWER(
        COALESCE(
            NULLIF(TRIM(c.email), ''),
            CONCAT('legacy-staff-', c.userId, '@no-mail.local')
        )
    )
WHERE c.mapped_position_title IS NOT NULL;

INSERT INTO migration_map_staff_users (old_user_id, new_user_id, mapped_by)
SELECT
    m.old_user_id,
    m.new_user_id,
    'script'
FROM tmp_user_map m
ON DUPLICATE KEY UPDATE
    new_user_id = VALUES(new_user_id),
    mapped_by = 'script',
    mapped_at = NOW();

-- --------------------------------------------------------------------------
-- E) CREATION DES FICHES STAFF (SI ABSENTES)
-- --------------------------------------------------------------------------
INSERT INTO staff (
    photo_path,
    first_name,
    last_name,
    cin,
    cin_issued_at,
    phone,
    email,
    employee_code,
    hire_date,
    position_title,
    department_id,
    employment_type,
    contract_type,
    user_id,
    status,
    created_at,
    updated_at
)
SELECT
    NULL AS photo_path,
    COALESCE(NULLIF(TRIM(m.prenom), ''), 'Prenom') AS first_name,
    COALESCE(NULLIF(TRIM(m.nom), ''), CONCAT('User ', m.old_user_id)) AS last_name,
    NULLIF(TRIM(m.cin), '') AS cin,
    m.dateCin,
    NULLIF(CAST(m.mobile AS CHAR), '') AS phone,
    (SELECT email FROM users WHERE id = m.new_user_id) AS email,
    COALESCE(NULLIF(TRIM(m.matricule), ''), CONCAT('SRV-', m.old_user_id)) AS employee_code,
    NULL AS hire_date,
    m.mapped_position_title,
    d.id AS department_id,
    'permanent' AS employment_type,
    'CDI' AS contract_type,
    m.new_user_id,
    'active' AS status,
    COALESCE(m.createdDtm, NOW()) AS created_at,
    COALESCE(m.updatedDtm, NOW()) AS updated_at
FROM tmp_user_map m
JOIN departments d ON LOWER(d.name) = LOWER(m.mapped_department_name)
LEFT JOIN staff s_user ON s_user.user_id = m.new_user_id
LEFT JOIN staff s_code ON s_code.employee_code = COALESCE(NULLIF(TRIM(m.matricule), ''), CONCAT('SRV-', m.old_user_id))
WHERE s_user.id IS NULL
  AND s_code.id IS NULL;

-- --------------------------------------------------------------------------
-- F) CONTROLES
-- --------------------------------------------------------------------------
SELECT 'created_users_staff_import' AS metric, COUNT(*) AS total
FROM users
WHERE email LIKE 'legacy-staff-%@no-mail.local'
UNION ALL
SELECT 'staff_total_after_import' AS metric, COUNT(*) AS total
FROM staff
UNION ALL
SELECT 'mapped_staff_users' AS metric, COUNT(*) AS total
FROM migration_map_staff_users;

SELECT id, first_name, last_name, position_title, user_id, photo_path
FROM staff
WHERE position_title IN ('Chef Service', 'Annimateur', 'Femme de menage', 'Agent de securite')
ORDER BY first_name, last_name
LIMIT 100;

SET FOREIGN_KEY_CHECKS = 1;

-- DRY RUN (recommande en premier):
ROLLBACK;

-- EXECUTION REELLE:
-- 1) verifier les resultats ci-dessus
-- 2) remplacer ROLLBACK par COMMIT
-- 3) puis executer: php artisan legacy:import-staff-avatars
-- COMMIT;
