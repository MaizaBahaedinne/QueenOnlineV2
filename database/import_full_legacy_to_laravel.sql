-- Import complet de l'ancienne base quee_QueenDB vers la base Laravel actuelle
-- Hypothese: la base cible est vide ou quasi vide avant execution
-- Base source: quee_QueenDB (meme serveur MySQL)
-- Cible: base Laravel de QueenOnlineV2
-- Couvre:
--   - clients depuis tbl_users roleId = 4
--   - salles depuis tbl_salle
--   - packs/items legacy vers service_module_items / service_module_packs
--   - reservations salle
--   - reservations service: photographe, prestation -> animation, troupe -> troupe-musicale, voiture
--   - paiements salle et paiements service

START TRANSACTION;

DROP TEMPORARY TABLE IF EXISTS legacy_client_map;
DROP TEMPORARY TABLE IF EXISTS legacy_salle_map;
DROP TEMPORARY TABLE IF EXISTS legacy_item_map;
DROP TEMPORARY TABLE IF EXISTS legacy_pack_map;
DROP TEMPORARY TABLE IF EXISTS legacy_reservation_map;

CREATE TEMPORARY TABLE legacy_client_map (
    old_user_id INT NOT NULL PRIMARY KEY,
    new_client_id BIGINT NOT NULL
);

CREATE TEMPORARY TABLE legacy_salle_map (
    old_salle_id INT NOT NULL PRIMARY KEY,
    new_salle_id BIGINT NOT NULL
);

CREATE TEMPORARY TABLE legacy_item_map (
    source_table VARCHAR(64) NOT NULL,
    old_item_id INT NOT NULL,
    new_item_id BIGINT NOT NULL,
    PRIMARY KEY (source_table, old_item_id)
);

CREATE TEMPORARY TABLE legacy_pack_map (
    source_table VARCHAR(64) NOT NULL,
    old_pack_id INT NOT NULL,
    new_pack_id BIGINT NOT NULL,
    PRIMARY KEY (source_table, old_pack_id)
);

CREATE TEMPORARY TABLE legacy_reservation_map (
    source_table VARCHAR(64) NOT NULL,
    old_reservation_id INT NOT NULL,
    new_reservation_id BIGINT NOT NULL,
    PRIMARY KEY (source_table, old_reservation_id)
);

-- ----------------------------------------------------------------------
-- 1) Clients
-- ----------------------------------------------------------------------
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
    address_number,
    address_street,
    date_cin,
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
    NULLIF(CAST(u.n AS CHAR), ''),
    NULLIF(u.rue, ''),
    u.dateCin,
    NULLIF(u.ville, ''),
    NULL,
    NULLIF(u.source, ''),
    NULL,
    'active',
    COALESCE(u.createdDtm, NOW()),
    COALESCE(u.updatedDtm, NOW())
FROM `quee_QueenDB`.tbl_users u
WHERE u.roleId = 4;

INSERT INTO legacy_client_map (old_user_id, new_client_id)
SELECT
    u.userId,
    MIN(c.id)
FROM `quee_QueenDB`.tbl_users u
JOIN clients c
    ON (
        (NULLIF(u.cin, '') IS NOT NULL AND c.cin = u.cin COLLATE utf8mb3_unicode_ci)
        OR (
            NULLIF(u.cin, '') IS NULL
            AND c.name = NULLIF(u.nom, '') COLLATE utf8mb3_unicode_ci
            AND IFNULL(c.first_name, '') = IFNULL(NULLIF(u.prenom, ''), '') COLLATE utf8mb3_unicode_ci
            AND IFNULL(c.phone, '') = IFNULL(NULLIF(CAST(u.mobile AS CHAR), ''), '') COLLATE utf8mb3_unicode_ci
        )
    )
WHERE u.roleId = 4
GROUP BY u.userId;

-- ----------------------------------------------------------------------
-- 2) Salles
-- ----------------------------------------------------------------------
INSERT INTO salles (
    name,
    capacity,
    price_per_day,
    location,
    description,
    status,
    created_at,
    updated_at
)
SELECT
    s.nom,
    COALESCE(s.`capacité`, 0),
    COALESCE(s.`Prix`, 0),
    NULL,
    NULLIF(s.description, ''),
    CASE WHEN LOWER(COALESCE(s.etat, 'active')) IN ('active', 'actif', '1') THEN 'active' ELSE 'inactive' END,
    NOW(),
    NOW()
FROM `quee_QueenDB`.tbl_salle s;

INSERT INTO legacy_salle_map (old_salle_id, new_salle_id)
SELECT
    s.salleID,
    n.id
FROM `quee_QueenDB`.tbl_salle s
JOIN salles n
    ON n.name = s.nom COLLATE utf8mb3_unicode_ci
   AND n.capacity = COALESCE(s.`capacité`, 0)
   AND n.price_per_day = COALESCE(s.`Prix`, 0);

-- ----------------------------------------------------------------------
-- 3) Packs / items legacy -> service modules
--    mapping choisi:
--    - tbl_pack_troupe -> troupe-musicale
--    - tbl_pack_photographe -> photographe
--    - tbl_pack_prestation -> animation
--    - tbl_pack_troupe_artiste -> service_module_items de troupe-musicale
-- ----------------------------------------------------------------------
INSERT INTO service_module_items (
    module_slug,
    name,
    phone,
    base_price,
    status,
    notes,
    created_at,
    updated_at
)
SELECT
    'troupe-musicale',
    a.nom,
    NULL,
    COALESCE(a.prix, 0),
    CASE WHEN a.statut = 1 THEN 'active' ELSE 'inactive' END,
    CONCAT('Legacy artisteId=', a.artisteId),
    NOW(),
    NOW()
FROM `quee_QueenDB`.tbl_pack_troupe_artiste a;

INSERT INTO legacy_item_map (source_table, old_item_id, new_item_id)
SELECT
    'tbl_pack_troupe_artiste',
    a.artisteId,
    n.id
FROM `quee_QueenDB`.tbl_pack_troupe_artiste a
JOIN service_module_items n
    ON n.module_slug = 'troupe-musicale'
   AND n.name = a.nom COLLATE utf8mb3_unicode_ci
   AND n.base_price = COALESCE(a.prix, 0);

INSERT INTO service_module_packs (
    module_slug,
    service_module_item_id,
    name,
    price,
    status,
    description,
    created_at,
    updated_at
)
SELECT
    'photographe',
    NULL,
    p.nom,
    COALESCE(p.prix, 0),
    'active',
    CONCAT(
        'Legacy packId=', p.packId,
        ' | type=', COALESCE(p.type, ''),
        ' | nbPhotos=', COALESCE(p.nombrePhotos, ''),
        ' | nbCamera=', COALESCE(p.nombreCamera, ''),
        ' | photos=', COALESCE(p.photos, ''),
        ' | video=', COALESCE(p.video, ''),
        ' | ghiraphe=', COALESCE(p.ghiraphe, ''),
        ' | drone=', COALESCE(p.drone, ''),
        ' | shooting=', COALESCE(p.shooting, ''),
        ' | description=', COALESCE(p.description, '')
    ),
    NOW(),
    NOW()
FROM `quee_QueenDB`.tbl_pack_photographe p;

INSERT INTO legacy_pack_map (source_table, old_pack_id, new_pack_id)
SELECT
    'tbl_pack_photographe',
    p.packId,
    n.id
FROM `quee_QueenDB`.tbl_pack_photographe p
JOIN service_module_packs n
    ON n.module_slug = 'photographe'
   AND n.name = p.nom COLLATE utf8mb3_unicode_ci
   AND n.price = COALESCE(p.prix, 0);

INSERT INTO service_module_packs (
    module_slug,
    service_module_item_id,
    name,
    price,
    status,
    description,
    created_at,
    updated_at
)
SELECT
    'troupe-musicale',
    NULL,
    t.nom,
    COALESCE(t.prix, 0),
    'active',
    CONCAT('Legacy packId=', t.packId, ' | description=', COALESCE(t.description, ''), ' | mobile=', COALESCE(t.mobile, '')),
    NOW(),
    NOW()
FROM `quee_QueenDB`.tbl_pack_troupe t;

INSERT INTO legacy_pack_map (source_table, old_pack_id, new_pack_id)
SELECT
    'tbl_pack_troupe',
    t.packId,
    n.id
FROM `quee_QueenDB`.tbl_pack_troupe t
JOIN service_module_packs n
    ON n.module_slug = 'troupe-musicale'
   AND n.name = t.nom COLLATE utf8mb3_unicode_ci
   AND n.price = COALESCE(t.prix, 0);

INSERT INTO service_module_packs (
    module_slug,
    service_module_item_id,
    name,
    price,
    status,
    description,
    created_at,
    updated_at
)
SELECT
    'animation',
    NULL,
    p.nom,
    COALESCE(p.prix, 0),
    'active',
    CONCAT(
        'Legacy packId=', p.packId,
        ' | type=', COALESCE(p.type, ''),
        ' | notification=', COALESCE(p.notification, ''),
        ' | mobile=', COALESCE(p.mobile, ''),
        ' | description=', COALESCE(p.description, '')
    ),
    NOW(),
    NOW()
FROM `quee_QueenDB`.tbl_pack_prestation p;

INSERT INTO legacy_pack_map (source_table, old_pack_id, new_pack_id)
SELECT
    'tbl_pack_prestation',
    p.packId,
    n.id
FROM `quee_QueenDB`.tbl_pack_prestation p
JOIN service_module_packs n
    ON n.module_slug = 'animation'
   AND n.name = p.nom COLLATE utf8mb3_unicode_ci
   AND n.price = COALESCE(p.prix, 0);

-- ----------------------------------------------------------------------
-- 4) Reservation salle (tbl_reservation)
-- ----------------------------------------------------------------------
INSERT INTO reservations (
    client_id,
    salle_id,
    user_id,
    title,
    start_date,
    end_date,
    start_time,
    end_time,
    status,
    total_amount,
    note_admin,
    created_at,
    updated_at
)
SELECT
    cm.new_client_id,
    sm.new_salle_id,
    NULL,
    COALESCE(NULLIF(r.titre, ''), CONCAT('Reservation #', r.reservationID)),
    r.dateDebut,
    r.dateFin,
    r.heureDebut,
    r.heureFin,
    CASE WHEN r.statut = 1 THEN 'confirmed' ELSE 'pending' END,
    COALESCE(r.prix, 0),
    CONCAT(
        'legacy:tbl_reservation:', r.reservationID,
        ' | legacy clientId=', r.clientId,
        ' | legacy locataireId=', r.locataireId,
        ' | legacy statut=', r.statut,
        ' | legacy demandeEcheance=', COALESCE(DATE_FORMAT(r.demandeEcheance, '%Y-%m-%d'), ''),
        ' | noteAdmin=', COALESCE(r.noteAdmin, ''),
        ' | cuisine=', COALESCE(r.cuisine, ''),
        ' | tableCM=', COALESCE(r.tableCM, ''),
        ' | voiture=', COALESCE(r.voiture, ''),
        ' | troupe=', COALESCE(r.troupe, ''),
        ' | prestation=', COALESCE(r.prestation, ''),
        ' | photographe=', COALESCE(r.photographe, ''),
        ' | gateau=', COALESCE(r.gateau, '')
    ),
    COALESCE(r.createdDTM, NOW()),
    COALESCE(r.createdDTM, NOW())
FROM `quee_QueenDB`.tbl_reservation r
JOIN legacy_client_map cm ON cm.old_user_id = r.clientId
JOIN legacy_salle_map sm ON sm.old_salle_id = r.salleId;

INSERT INTO legacy_reservation_map (source_table, old_reservation_id, new_reservation_id)
SELECT
    'tbl_reservation',
    r.reservationID,
    n.id
FROM `quee_QueenDB`.tbl_reservation r
JOIN reservations n
    ON n.note_admin LIKE CONCAT('legacy:tbl_reservation:', r.reservationID, '%')
   AND n.start_date = r.dateDebut
   AND n.end_date = r.dateFin
   AND n.start_time = r.heureDebut
   AND n.end_time = r.heureFin;

-- ----------------------------------------------------------------------
-- 5) Reservations service
-- ----------------------------------------------------------------------
-- Photographe
INSERT INTO reservations (
    client_id,
    salle_id,
    user_id,
    title,
    start_date,
    end_date,
    start_time,
    end_time,
    service_slug,
    status,
    total_amount,
    note_admin,
    created_at,
    updated_at
)
SELECT
    parent.client_id,
    parent.salle_id,
    NULL,
    CONCAT('Photographe - ', COALESCE(p.nom, CONCAT('Pack #', r.packId)), ' #', r.reservationPId),
    p.date,
    p.date,
    parent.start_time,
    parent.end_time,
    'photographe',
    CASE WHEN r.statut = 1 THEN 'confirmed' ELSE 'pending' END,
    COALESCE(r.prix, 0),
    CONCAT(
        'legacy:tbl_reservation_photographe:', r.reservationPId,
        ' | parentReservationId=', r.reservationId,
        ' | legacy packId=', r.packId,
        ' | avance=', COALESCE(r.avance, ''),
        ' | createdBy=', COALESCE(r.createdBy, ''),
        ' | noteAdmin=', COALESCE(r.noteAdmin, '')
    ),
    COALESCE(r.createdDTM, NOW()),
    COALESCE(r.createdDTM, NOW())
FROM `quee_QueenDB`.tbl_reservation_photographe r
JOIN legacy_reservation_map prm ON prm.source_table = 'tbl_reservation' AND prm.old_reservation_id = r.reservationId
JOIN reservations parent ON parent.id = prm.new_reservation_id
LEFT JOIN `quee_QueenDB`.tbl_pack_photographe p ON p.packId = r.packId;

INSERT INTO legacy_reservation_map (source_table, old_reservation_id, new_reservation_id)
SELECT
    'tbl_reservation_photographe',
    r.reservationPId,
    n.id
FROM `quee_QueenDB`.tbl_reservation_photographe r
JOIN reservations n
    ON n.note_admin LIKE CONCAT('legacy:tbl_reservation_photographe:', r.reservationPId, '%');

-- Prestation -> animation
INSERT INTO reservations (
    client_id,
    salle_id,
    user_id,
    title,
    start_date,
    end_date,
    start_time,
    end_time,
    service_slug,
    status,
    total_amount,
    note_admin,
    created_at,
    updated_at
)
SELECT
    parent.client_id,
    parent.salle_id,
    NULL,
    CONCAT('Prestation - ', COALESCE(p.nom, CONCAT('Pack #', r.packId)), ' #', r.prestationId),
    r.date,
    r.date,
    r.heure,
    ADDTIME(r.heure, '01:00:00'),
    'animation',
    CASE WHEN r.statut = 1 THEN 'confirmed' ELSE 'pending' END,
    COALESCE(r.prix, 0),
    CONCAT(
        'legacy:tbl_reservation_prestation:', r.prestationId,
        ' | parentReservationId=', r.reservationId,
        ' | legacy packId=', r.packId,
        ' | avance=', COALESCE(r.avance, ''),
        ' | createdBy=', COALESCE(r.createdBy, ''),
        ' | noteAdmin=', COALESCE(r.noteAdmin, '')
    ),
    COALESCE(r.createdDTM, NOW()),
    COALESCE(r.createdDTM, NOW())
FROM `quee_QueenDB`.tbl_reservation_prestation r
JOIN legacy_reservation_map prm ON prm.source_table = 'tbl_reservation' AND prm.old_reservation_id = r.reservationId
JOIN reservations parent ON parent.id = prm.new_reservation_id
LEFT JOIN `quee_QueenDB`.tbl_pack_prestation p ON p.packId = r.packId;

INSERT INTO legacy_reservation_map (source_table, old_reservation_id, new_reservation_id)
SELECT
    'tbl_reservation_prestation',
    r.prestationId,
    n.id
FROM `quee_QueenDB`.tbl_reservation_prestation r
JOIN reservations n
    ON n.note_admin LIKE CONCAT('legacy:tbl_reservation_prestation:', r.prestationId, '%');

-- Troupe -> troupe-musicale
INSERT INTO reservations (
    client_id,
    salle_id,
    user_id,
    title,
    start_date,
    end_date,
    start_time,
    end_time,
    service_slug,
    status,
    total_amount,
    note_admin,
    created_at,
    updated_at
)
SELECT
    parent.client_id,
    parent.salle_id,
    NULL,
    CONCAT('Troupe - ', COALESCE(p.nom, CONCAT('Pack #', r.packId)), ' #', r.reservationTId),
    r.date,
    r.date,
    SEC_TO_TIME(COALESCE(r.heure, 0) * 3600),
    ADDTIME(SEC_TO_TIME(COALESCE(r.heure, 0) * 3600), '01:00:00'),
    'troupe-musicale',
    CASE WHEN r.statut = 1 THEN 'confirmed' ELSE 'pending' END,
    COALESCE(r.prix, 0),
    CONCAT(
        'legacy:tbl_reservation_troupe:', r.reservationTId,
        ' | parentReservationId=', r.reservationId,
        ' | legacy packId=', r.packId,
        ' | chanteurs=', COALESCE(r.chanteurs, ''),
        ' | avance=', COALESCE(r.avance, ''),
        ' | createdBy=', COALESCE(r.createdBy, ''),
        ' | noteAdmin=', COALESCE(r.noteAdmin, '')
    ),
    COALESCE(r.createdDTM, NOW()),
    COALESCE(r.createdDTM, NOW())
FROM `quee_QueenDB`.tbl_reservation_troupe r
JOIN legacy_reservation_map prm ON prm.source_table = 'tbl_reservation' AND prm.old_reservation_id = r.reservationId
JOIN reservations parent ON parent.id = prm.new_reservation_id
LEFT JOIN `quee_QueenDB`.tbl_pack_troupe p ON p.packId = r.packId;

INSERT INTO legacy_reservation_map (source_table, old_reservation_id, new_reservation_id)
SELECT
    'tbl_reservation_troupe',
    r.reservationTId,
    n.id
FROM `quee_QueenDB`.tbl_reservation_troupe r
JOIN reservations n
    ON n.note_admin LIKE CONCAT('legacy:tbl_reservation_troupe:', r.reservationTId, '%');

-- Voiture
INSERT INTO reservations (
    client_id,
    salle_id,
    user_id,
    title,
    start_date,
    end_date,
    start_time,
    end_time,
    service_slug,
    status,
    total_amount,
    note_admin,
    created_at,
    updated_at
)
SELECT
    COALESCE(parent.client_id, cm.new_client_id),
    parent.salle_id,
    NULL,
    CONCAT('Voiture - ', COALESCE(r.voitureName, CONCAT('Reservation #', r.reservationVId)), ' #', r.reservationVId),
    r.date,
    r.date,
    r.depart,
    ADDTIME(r.depart, '01:00:00'),
    'voiture',
    CASE WHEN r.statut = 1 THEN 'confirmed' ELSE 'pending' END,
    COALESCE(r.prix, 0),
    CONCAT(
        'legacy:tbl_reservation_voiture:', r.reservationVId,
        ' | parentReservationId=', r.reservationId,
        ' | legacy clientId=', r.clientId,
        ' | l1=', COALESCE(r.l1, ''),
        ' | l2=', COALESCE(r.l2, ''),
        ' | l3=', COALESCE(r.l3, ''),
        ' | l4=', COALESCE(r.l4, ''),
        ' | mobile1=', COALESCE(r.mobile1, ''),
        ' | mobile2=', COALESCE(r.mobile2, ''),
        ' | avance=', COALESCE(r.avance, ''),
        ' | createdBy=', COALESCE(r.createdBy, ''),
        ' | noteAdmin=', COALESCE(r.noteAdmin, '')
    ),
    COALESCE(r.createdDTM, NOW()),
    COALESCE(r.createdDTM, NOW())
FROM `quee_QueenDB`.tbl_reservation_voiture r
LEFT JOIN legacy_client_map cm ON cm.old_user_id = r.clientId
LEFT JOIN legacy_reservation_map prm ON prm.source_table = 'tbl_reservation' AND prm.old_reservation_id = r.reservationId
LEFT JOIN reservations parent ON parent.id = prm.new_reservation_id;

INSERT INTO legacy_reservation_map (source_table, old_reservation_id, new_reservation_id)
SELECT
    'tbl_reservation_voiture',
    r.reservationVId,
    n.id
FROM `quee_QueenDB`.tbl_reservation_voiture r
JOIN reservations n
    ON n.note_admin LIKE CONCAT('legacy:tbl_reservation_voiture:', r.reservationVId, '%');

-- ----------------------------------------------------------------------
-- 6) Paiements
-- ----------------------------------------------------------------------
INSERT INTO payments (
    reservation_id,
    user_id,
    amount,
    method,
    reference,
    status,
    paid_at,
    note,
    created_at,
    updated_at
)
SELECT
    rm.new_reservation_id,
    NULL,
    COALESCE(p.valeur, 0),
    'cash',
    NULLIF(p.libele, ''),
    'paid',
    p.createdDate,
    CONCAT('legacy:tbl_paiement | paiementId=', p.paiementId, ' | recepteurId=', p.recepteurId),
    p.createdDate,
    p.createdDate
FROM `quee_QueenDB`.tbl_paiement p
JOIN legacy_reservation_map rm
    ON rm.source_table = 'tbl_reservation'
   AND rm.old_reservation_id = p.reservationId;

INSERT INTO payments (
    reservation_id,
    user_id,
    amount,
    method,
    reference,
    status,
    paid_at,
    note,
    created_at,
    updated_at
)
SELECT
    rm.new_reservation_id,
    NULL,
    COALESCE(p.valeur, 0),
    'cash',
    NULLIF(p.libele, ''),
    'paid',
    p.createdDate,
    CONCAT('legacy:tbl_paiement_photographe | paiementId=', p.paiementId, ' | recepteurId=', p.recepteurId),
    p.createdDate,
    p.createdDate
FROM `quee_QueenDB`.tbl_paiement_photographe p
JOIN legacy_reservation_map rm
    ON rm.source_table = 'tbl_reservation_photographe'
   AND rm.old_reservation_id = p.reservationPId;

INSERT INTO payments (
    reservation_id,
    user_id,
    amount,
    method,
    reference,
    status,
    paid_at,
    note,
    created_at,
    updated_at
)
SELECT
    rm.new_reservation_id,
    NULL,
    COALESCE(p.valeur, 0),
    'cash',
    NULLIF(p.libele, ''),
    'paid',
    p.createdDate,
    CONCAT('legacy:tbl_paiement_prestation | paiementId=', p.paiementId, ' | recepteurId=', p.recepteurId),
    p.createdDate,
    p.createdDate
FROM `quee_QueenDB`.tbl_paiement_prestation p
JOIN legacy_reservation_map rm
    ON rm.source_table = 'tbl_reservation_prestation'
   AND rm.old_reservation_id = p.reservationPresId;

INSERT INTO payments (
    reservation_id,
    user_id,
    amount,
    method,
    reference,
    status,
    paid_at,
    note,
    created_at,
    updated_at
)
SELECT
    rm.new_reservation_id,
    NULL,
    COALESCE(p.valeur, 0),
    'cash',
    NULLIF(p.libele, ''),
    'paid',
    p.createdDate,
    CONCAT('legacy:tbl_paiement_troupe | paiementId=', p.paiementId, ' | recepteurId=', p.recepteurId),
    p.createdDate,
    p.createdDate
FROM `quee_QueenDB`.tbl_paiement_troupe p
JOIN legacy_reservation_map rm
    ON rm.source_table = 'tbl_reservation_troupe'
   AND rm.old_reservation_id = p.reservationTroupeId;

INSERT INTO payments (
    reservation_id,
    user_id,
    amount,
    method,
    reference,
    status,
    paid_at,
    note,
    created_at,
    updated_at
)
SELECT
    rm.new_reservation_id,
    NULL,
    COALESCE(p.valeur, 0),
    'cash',
    NULLIF(p.libele, ''),
    'paid',
    p.createdDate,
    CONCAT('legacy:tbl_paiement_voiture | paiementId=', p.paiementId, ' | recepteurId=', p.recepteurId),
    p.createdDate,
    p.createdDate
FROM `quee_QueenDB`.tbl_paiement_voiture p
JOIN legacy_reservation_map rm
    ON rm.source_table = 'tbl_reservation_voiture'
   AND rm.old_reservation_id = p.reservationVId;

COMMIT;

-- Contrôles rapides
SELECT COUNT(*) AS nb_clients_importes FROM clients;
SELECT COUNT(*) AS nb_salles_importees FROM salles;
SELECT COUNT(*) AS nb_items_importes FROM service_module_items;
SELECT COUNT(*) AS nb_packs_importes FROM service_module_packs;
SELECT COUNT(*) AS nb_reservations_importees FROM reservations;
SELECT COUNT(*) AS nb_payments_importes FROM payments;
