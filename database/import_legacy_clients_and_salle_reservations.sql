-- Import legacy vers la base Laravel
-- Base source: quee_QueenDB
-- Base cible: la base Laravel courante (celle dans laquelle tu lances le script)
-- Objet: importer les clients (tbl_users roleId = 4), les salles, puis les reservations salle
-- Execution: selectionne la base cible avant de lancer ce script

START TRANSACTION;

CREATE TEMPORARY TABLE IF NOT EXISTS legacy_client_map (
    old_user_id INT NOT NULL PRIMARY KEY,
    new_client_id BIGINT NOT NULL
);

CREATE TEMPORARY TABLE IF NOT EXISTS legacy_salle_map (
    old_salle_id INT NOT NULL PRIMARY KEY,
    new_salle_id BIGINT NOT NULL
);

DROP PROCEDURE IF EXISTS import_legacy_clients_and_salle_reservations;
DELIMITER $$
CREATE PROCEDURE import_legacy_clients_and_salle_reservations()
BEGIN
    DECLARE done INT DEFAULT 0;

    DECLARE v_old_user_id INT;
    DECLARE v_email VARCHAR(128);
    DECLARE v_mobile VARCHAR(32);
    DECLARE v_mobile2 VARCHAR(32);
    DECLARE v_cin VARCHAR(32);
    DECLARE v_dateCin DATE;
    DECLARE v_name VARCHAR(255);
    DECLARE v_prenom VARCHAR(255);
    DECLARE v_type VARCHAR(255);
    DECLARE v_raisonSocial VARCHAR(255);
    DECLARE v_sexe VARCHAR(32);
    DECLARE v_birthday DATE;
    DECLARE v_n VARCHAR(32);
    DECLARE v_rue VARCHAR(255);
    DECLARE v_ville VARCHAR(255);
    DECLARE v_source TEXT;
    DECLARE v_createdDtm DATETIME;
    DECLARE v_updatedDtm DATETIME;

    DECLARE v_old_salle_id INT;
    DECLARE v_salle_nom VARCHAR(50);
    DECLARE v_salle_type VARCHAR(50);
    DECLARE v_capacity INT;
    DECLARE v_etat VARCHAR(50);
    DECLARE v_prix INT;
    DECLARE v_photo TEXT;
    DECLARE v_nomAr TEXT;
    DECLARE v_acro TEXT;
    DECLARE v_description TEXT;

    DECLARE v_old_reservation_id INT;
    DECLARE v_titre VARCHAR(255);
    DECLARE v_client_old_id INT;
    DECLARE v_locataire_id INT;
    DECLARE v_dateDebut DATE;
    DECLARE v_heureDebut TIME;
    DECLARE v_dateFin DATE;
    DECLARE v_heureFin TIME;
    DECLARE v_salle_old_id INT;
    DECLARE v_prix_reservation INT;
    DECLARE v_noteAdmin TEXT;
    DECLARE v_statut INT;
    DECLARE v_createdDTM_reservation DATETIME;
    DECLARE v_demandeEcheance DATE;

    DECLARE v_client_id BIGINT;
    DECLARE v_salle_id BIGINT;
    DECLARE v_status VARCHAR(20);
    DECLARE v_title VARCHAR(255);
    DECLARE v_total_amount DECIMAL(12,2);
    DECLARE v_note TEXT;

    DECLARE cur_clients CURSOR FOR
        SELECT
            u.userId,
            u.email,
            CAST(u.mobile AS CHAR),
            CAST(u.mobile2 AS CHAR),
            u.cin,
            u.dateCin,
            u.nom,
            u.prenom,
            u.type,
            u.raisonSocial,
            u.sexe,
            u.birthday,
            CAST(u.n AS CHAR),
            u.rue,
            u.ville,
            u.source,
            u.createdDtm,
            u.updatedDtm
        FROM `quee_QueenDB`.tbl_users u
        WHERE u.roleId = 4
        ORDER BY u.userId;

    DECLARE cur_salles CURSOR FOR
        SELECT
            s.salleID,
            s.nom,
            s.type,
            s.`capacité`,
            s.etat,
            s.`Prix`,
            s.photo,
            s.nomAr,
            s.acro,
            s.description
        FROM `quee_QueenDB`.tbl_salle s
        ORDER BY s.salleID;

    DECLARE cur_reservations CURSOR FOR
        SELECT
            r.reservationID,
            r.titre,
            r.clientId,
            r.locataireId,
            r.dateDebut,
            r.heureDebut,
            r.dateFin,
            r.heureFin,
            r.salleId,
            r.prix,
            r.noteAdmin,
            r.statut,
            r.createdDTM,
            r.demandeEcheance
        FROM `quee_QueenDB`.tbl_reservation r
        ORDER BY r.reservationID;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

    OPEN cur_clients;
    clients_loop: LOOP
        FETCH cur_clients INTO
            v_old_user_id,
            v_email,
            v_mobile,
            v_mobile2,
            v_cin,
            v_dateCin,
            v_name,
            v_prenom,
            v_type,
            v_raisonSocial,
            v_sexe,
            v_birthday,
            v_n,
            v_rue,
            v_ville,
            v_source,
            v_createdDtm,
            v_updatedDtm;

        IF done = 1 THEN
            LEAVE clients_loop;
        END IF;

        IF EXISTS (SELECT 1 FROM clients c WHERE c.cin <=> NULLIF(v_cin, '')) THEN
            INSERT INTO legacy_client_map (old_user_id, new_client_id)
            SELECT v_old_user_id, c.id
            FROM clients c
            WHERE c.cin <=> NULLIF(v_cin, '')
            LIMIT 1;
        ELSE
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
            ) VALUES (
                NULL,
                CASE WHEN v_type = 'societe' THEN 'societe' ELSE 'personne-physique' END,
                NULL,
                CASE WHEN v_type = 'societe' THEN NULLIF(v_raisonSocial, '') ELSE NULL END,
                NULLIF(v_prenom, ''),
                NULLIF(v_name, ''),
                NULLIF(v_sexe, ''),
                v_birthday,
                NULLIF(v_email, ''),
                NULLIF(v_mobile, ''),
                NULL,
                NULLIF(v_mobile2, ''),
                NULL,
                NULLIF(v_cin, ''),
                NULLIF(v_n, ''),
                NULLIF(v_rue, ''),
                v_dateCin,
                NULLIF(v_ville, ''),
                NULL,
                NULLIF(v_source, ''),
                NULL,
                'active',
                COALESCE(v_createdDtm, NOW()),
                COALESCE(v_updatedDtm, NOW())
            );

            INSERT INTO legacy_client_map (old_user_id, new_client_id)
            VALUES (v_old_user_id, LAST_INSERT_ID());
        END IF;
    END LOOP;
    CLOSE cur_clients;

    SET done = 0;

    OPEN cur_salles;
    salles_loop: LOOP
        FETCH cur_salles INTO
            v_old_salle_id,
            v_salle_nom,
            v_salle_type,
            v_capacity,
            v_etat,
            v_prix,
            v_photo,
            v_nomAr,
            v_acro,
            v_description;

        IF done = 1 THEN
            LEAVE salles_loop;
        END IF;

        IF EXISTS (SELECT 1 FROM salles s WHERE s.name = v_salle_nom) THEN
            INSERT INTO legacy_salle_map (old_salle_id, new_salle_id)
            SELECT v_old_salle_id, s.id
            FROM salles s
            WHERE s.name = v_salle_nom
            LIMIT 1;
        ELSE
            INSERT INTO salles (
                name,
                capacity,
                price_per_day,
                location,
                description,
                status,
                created_at,
                updated_at
            ) VALUES (
                v_salle_nom,
                COALESCE(v_capacity, 0),
                COALESCE(v_prix, 0),
                NULL,
                NULLIF(v_description, ''),
                CASE WHEN LOWER(COALESCE(v_etat, 'active')) IN ('active', 'actif', '1') THEN 'active' ELSE 'inactive' END,
                NOW(),
                NOW()
            );

            INSERT INTO legacy_salle_map (old_salle_id, new_salle_id)
            VALUES (v_old_salle_id, LAST_INSERT_ID());
        END IF;
    END LOOP;
    CLOSE cur_salles;

    SET done = 0;

    OPEN cur_reservations;
    reservations_loop: LOOP
        FETCH cur_reservations INTO
            v_old_reservation_id,
            v_titre,
            v_client_old_id,
            v_locataire_id,
            v_dateDebut,
            v_heureDebut,
            v_dateFin,
            v_heureFin,
            v_salle_old_id,
            v_prix_reservation,
            v_noteAdmin,
            v_statut,
            v_createdDTM_reservation,
            v_demandeEcheance;

        IF done = 1 THEN
            LEAVE reservations_loop;
        END IF;

        SELECT new_client_id INTO v_client_id
        FROM legacy_client_map
        WHERE old_user_id = v_client_old_id
        LIMIT 1;

        SELECT new_salle_id INTO v_salle_id
        FROM legacy_salle_map
        WHERE old_salle_id = v_salle_old_id
        LIMIT 1;

        SET v_status = CASE WHEN v_statut = 1 THEN 'confirmed' ELSE 'pending' END;
        SET v_title = COALESCE(NULLIF(v_titre, ''), CONCAT('Reservation #', v_old_reservation_id));
        SET v_total_amount = COALESCE(v_prix_reservation, 0);
        SET v_note = CONCAT(
            'Import legacy reservationID=', v_old_reservation_id,
            ' | legacy clientId=', v_client_old_id,
            ' | legacy salleId=', v_salle_old_id,
            ' | noteAdmin=', COALESCE(v_noteAdmin, ''),
            ' | demandeEcheance=', COALESCE(DATE_FORMAT(v_demandeEcheance, '%Y-%m-%d'), '')
        );

        IF v_client_id IS NOT NULL AND v_salle_id IS NOT NULL THEN
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
            ) VALUES (
                v_client_id,
                v_salle_id,
                NULL,
                v_title,
                v_dateDebut,
                v_dateFin,
                v_heureDebut,
                v_heureFin,
                v_status,
                v_total_amount,
                v_note,
                COALESCE(v_createdDtm_reservation, NOW()),
                COALESCE(v_createdDtm_reservation, NOW())
            );
        END IF;
    END LOOP;
    CLOSE cur_reservations;
END$$
DELIMITER ;

CALL import_legacy_clients_and_salle_reservations();
DROP PROCEDURE IF EXISTS import_legacy_clients_and_salle_reservations;

COMMIT;

-- Contrôles rapides
SELECT COUNT(*) AS nb_clients_importes FROM clients;
SELECT COUNT(*) AS nb_salles_importees FROM salles;
SELECT COUNT(*) AS nb_reservations_importees FROM reservations;
