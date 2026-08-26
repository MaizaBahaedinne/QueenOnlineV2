# Migration Base -> Base (Meme Serveur) avec Mapping

Ce guide permet de migrer une application legacy vers cette application Laravel, sur le meme serveur MySQL/MariaDB, avec mapping traceable et verifiable.

## 1. Principe

- Base source (legacy): `quee_QueenDB`
- Base cible (Laravel): `quee_QueenBD` (ou ta base actuelle)
- Script principal: `database/migration_same_server_mapping.sql`
- Mapping persistant:
  - `migration_map_clients` (old userId -> new client id)
  - `migration_map_salles` (old salleID -> new salle id)
  - `migration_map_reservations` (old reservationID -> new reservation id)

## 2. Mapping metier (source -> cible)

### Clients
- `tbl_users.userId` -> `clients.id` (via `migration_map_clients`)
- `tbl_users.nom` -> `clients.name`
- `tbl_users.prenom` -> `clients.first_name`
- `tbl_users.mobile` -> `clients.phone`
- `tbl_users.mobile2` -> `clients.phone_2`
- `tbl_users.cin` -> `clients.cin`
- `tbl_users.dateCin` -> `clients.date_cin`
- `tbl_users.ville` -> `clients.city`
- `tbl_users.type` -> `clients.client_type`
- `tbl_users.raisonSocial` -> `clients.company_name` (si societe)
- `tbl_users.source` -> `clients.source` (normalise)

### Salles
- `tbl_salle.salleID` -> `salles.id` (via `migration_map_salles`)
- `tbl_salle.nom` -> `salles.name`
- `tbl_salle.type` -> `salles.salle_type`
- `tbl_salle.capacité` -> `salles.capacity`
- `tbl_salle.Prix` -> `salles.price_per_day`
- `tbl_salle.etat` -> `salles.status`

### Reservations salle
- `tbl_reservation.reservationID` -> `reservations.id` (via `migration_map_reservations`)
- `tbl_reservation.clientId` -> `reservations.client_id` (par table mapping clients)
- `tbl_reservation.salleId` -> `reservations.salle_id` (par table mapping salles)
- `tbl_reservation.titre` -> `reservations.title`
- `tbl_reservation.dateDebut/dateFin` -> `reservations.start_date/end_date`
- `tbl_reservation.heureDebut/heureFin` -> `reservations.start_time/end_time`
- `tbl_reservation.prix` -> `reservations.total_amount`
- `tbl_reservation.statut` -> `reservations.status` (normalise)
- `tbl_reservation.demandeEcheance` -> `reservations.payment_due_date`
- `service_slug` force a `salles`
- Legacy trace ajoutee dans `note_admin`

## 3. Workflow recommande

1. Sauvegarder les 2 bases.
2. Verifier que les migrations Laravel sont a jour.
3. Adapter les noms de base dans le script SQL si necessaire.
4. Lancer le script en mode dry-run (fin sur `ROLLBACK`).
5. Lire les controles en fin de script:
   - totals mapped
   - rows non mappees
6. Corriger les cas non mappes (doublons, donnees incoherentes).
7. Relancer et remplacer `ROLLBACK` par `COMMIT`.

## 4. Controle qualite post-migration

Verifier notamment:
- Nombre de clients/salles/reservations coherents avec source.
- Echantillon manuel de 20 dossiers clients.
- Echantillon de reservations salle avec plages horaires.
- Presence du trace legacy dans `reservations.note_admin`.

## 5. Extension (prochaine etape)

Une fois ce noyau valide, on peut etendre le mapping vers:
- paiements legacy
- packs photographe/troupe
- partenaires chanteurs
- options de salle

en reutilisant les memes tables de mapping et la meme methode (dry-run + controles + commit).
