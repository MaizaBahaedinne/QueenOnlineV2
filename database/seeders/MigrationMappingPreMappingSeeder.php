<?php

namespace Database\Seeders;

use App\Models\MigrationMapping;
use Illuminate\Database\Seeder;

class MigrationMappingPreMappingSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            // tbl_users -> clients (clients legacy: roleId = 4)
            ['source_table' => 'tbl_users', 'source_column' => 'userId', 'target_table' => 'clients', 'target_column' => 'id', 'condition_value' => 'roleId=4', 'signification' => 'ID client legacy a tracer via table migration_map_clients.'],
            ['source_table' => 'tbl_users', 'source_column' => 'nom', 'target_table' => 'clients', 'target_column' => 'name', 'condition_value' => 'roleId=4', 'signification' => 'Nom du client.'],
            ['source_table' => 'tbl_users', 'source_column' => 'prenom', 'target_table' => 'clients', 'target_column' => 'first_name', 'condition_value' => 'roleId=4', 'signification' => 'Prenom du client.'],
            ['source_table' => 'tbl_users', 'source_column' => 'email', 'target_table' => 'clients', 'target_column' => 'email', 'condition_value' => 'roleId=4', 'signification' => 'Email client si non vide.'],
            ['source_table' => 'tbl_users', 'source_column' => 'mobile', 'target_table' => 'clients', 'target_column' => 'phone', 'condition_value' => 'roleId=4', 'signification' => 'Telephone principal client.'],
            ['source_table' => 'tbl_users', 'source_column' => 'mobile2', 'target_table' => 'clients', 'target_column' => 'phone_2', 'condition_value' => 'roleId=4', 'signification' => 'Telephone secondaire client.'],
            ['source_table' => 'tbl_users', 'source_column' => 'cin', 'target_table' => 'clients', 'target_column' => 'cin', 'condition_value' => 'roleId=4', 'signification' => 'CIN client.'],
            ['source_table' => 'tbl_users', 'source_column' => 'dateCin', 'target_table' => 'clients', 'target_column' => 'date_cin', 'condition_value' => 'roleId=4', 'signification' => 'Date emission CIN.'],
            ['source_table' => 'tbl_users', 'source_column' => 'type', 'target_table' => 'clients', 'target_column' => 'client_type', 'condition_value' => 'roleId=4 AND type=societe', 'signification' => 'Normaliser en societe.'],
            ['source_table' => 'tbl_users', 'source_column' => 'type', 'target_table' => 'clients', 'target_column' => 'client_type', 'condition_value' => 'roleId=4 AND type!=societe', 'signification' => 'Normaliser en personne-physique.'],
            ['source_table' => 'tbl_users', 'source_column' => 'raisonSocial', 'target_table' => 'clients', 'target_column' => 'company_name', 'condition_value' => 'roleId=4 AND type=societe', 'signification' => 'Raison sociale si client societe.'],
            ['source_table' => 'tbl_users', 'source_column' => 'sexe', 'target_table' => 'clients', 'target_column' => 'gender', 'condition_value' => 'roleId=4', 'signification' => 'Genre du client.'],
            ['source_table' => 'tbl_users', 'source_column' => 'birthday', 'target_table' => 'clients', 'target_column' => 'birth_date', 'condition_value' => 'roleId=4', 'signification' => 'Date de naissance client.'],
            ['source_table' => 'tbl_users', 'source_column' => 'n', 'target_table' => 'clients', 'target_column' => 'address_number', 'condition_value' => 'roleId=4', 'signification' => 'Numero adresse client.'],
            ['source_table' => 'tbl_users', 'source_column' => 'rue', 'target_table' => 'clients', 'target_column' => 'address_street', 'condition_value' => 'roleId=4', 'signification' => 'Rue adresse client.'],
            ['source_table' => 'tbl_users', 'source_column' => 'ville', 'target_table' => 'clients', 'target_column' => 'city', 'condition_value' => 'roleId=4', 'signification' => 'Ville client.'],
            ['source_table' => 'tbl_users', 'source_column' => 'source', 'target_table' => 'clients', 'target_column' => 'source', 'condition_value' => 'roleId=4', 'signification' => 'Source acquisition client a normaliser selon cles cibles.'],
            ['source_table' => 'tbl_users', 'source_column' => 'createdDtm', 'target_table' => 'clients', 'target_column' => 'created_at', 'condition_value' => 'roleId=4', 'signification' => 'Date creation legacy.'],
            ['source_table' => 'tbl_users', 'source_column' => 'updatedDtm', 'target_table' => 'clients', 'target_column' => 'updated_at', 'condition_value' => 'roleId=4', 'signification' => 'Date mise a jour legacy.'],

            // tbl_users -> users (staff/backoffice)
            ['source_table' => 'tbl_users', 'source_column' => 'email', 'target_table' => 'users', 'target_column' => 'email', 'condition_value' => 'roleId!=4', 'signification' => 'Comptes internes: email.'],
            ['source_table' => 'tbl_users', 'source_column' => 'mobile', 'target_table' => 'users', 'target_column' => 'phone', 'condition_value' => 'roleId!=4', 'signification' => 'Comptes internes: telephone.'],
            ['source_table' => 'tbl_users', 'source_column' => 'cin', 'target_table' => 'users', 'target_column' => 'cin', 'condition_value' => 'roleId!=4', 'signification' => 'Comptes internes: CIN.'],
            ['source_table' => 'tbl_users', 'source_column' => 'roleId', 'target_table' => 'users', 'target_column' => 'role_id', 'condition_value' => 'roleId!=4', 'signification' => 'Role user a mapper via table roles.'],

            // tbl_roles -> roles
            ['source_table' => 'tbl_roles', 'source_column' => 'roleId', 'target_table' => 'roles', 'target_column' => 'id', 'condition_value' => null, 'signification' => 'ID role legacy a conserver pour mapping.'],
            ['source_table' => 'tbl_roles', 'source_column' => 'role', 'target_table' => 'roles', 'target_column' => 'name', 'condition_value' => null, 'signification' => 'Libelle role.'],

            // tbl_salle -> salles
            ['source_table' => 'tbl_salle', 'source_column' => 'salleID', 'target_table' => 'salles', 'target_column' => 'id', 'condition_value' => null, 'signification' => 'ID salle legacy a tracer via migration_map_salles.'],
            ['source_table' => 'tbl_salle', 'source_column' => 'nom', 'target_table' => 'salles', 'target_column' => 'name', 'condition_value' => null, 'signification' => 'Nom salle.'],
            ['source_table' => 'tbl_salle', 'source_column' => 'type', 'target_table' => 'salles', 'target_column' => 'salle_type', 'condition_value' => null, 'signification' => 'Type salle (couvert/plein-air) a normaliser.'],
            ['source_table' => 'tbl_salle', 'source_column' => 'capacité', 'target_table' => 'salles', 'target_column' => 'capacity', 'condition_value' => null, 'signification' => 'Capacite salle.'],
            ['source_table' => 'tbl_salle', 'source_column' => 'Prix', 'target_table' => 'salles', 'target_column' => 'price_per_day', 'condition_value' => null, 'signification' => 'Prix journalier salle.'],
            ['source_table' => 'tbl_salle', 'source_column' => 'etat', 'target_table' => 'salles', 'target_column' => 'status', 'condition_value' => null, 'signification' => 'Statut salle a normaliser (active/inactive).'],
            ['source_table' => 'tbl_salle', 'source_column' => 'description', 'target_table' => 'salles', 'target_column' => 'description', 'condition_value' => null, 'signification' => 'Description salle.'],

            // tbl_reservation -> reservations
            ['source_table' => 'tbl_reservation', 'source_column' => 'reservationID', 'target_table' => 'reservations', 'target_column' => 'id', 'condition_value' => null, 'signification' => 'ID reservation legacy a tracer via migration_map_reservations.'],
            ['source_table' => 'tbl_reservation', 'source_column' => 'clientId', 'target_table' => 'reservations', 'target_column' => 'client_id', 'condition_value' => null, 'signification' => 'FK client via migration_map_clients (old userId -> new client id).'],
            ['source_table' => 'tbl_reservation', 'source_column' => 'salleId', 'target_table' => 'reservations', 'target_column' => 'salle_id', 'condition_value' => null, 'signification' => 'FK salle via migration_map_salles (old salleID -> new salle id).'],
            ['source_table' => 'tbl_reservation', 'source_column' => 'locataireId', 'target_table' => 'reservations', 'target_column' => 'user_id', 'condition_value' => null, 'signification' => 'Utilisateur gestionnaire de la reservation (si mappe).'],
            ['source_table' => 'tbl_reservation', 'source_column' => 'titre', 'target_table' => 'reservations', 'target_column' => 'title', 'condition_value' => null, 'signification' => 'Titre reservation.'],
            ['source_table' => 'tbl_reservation', 'source_column' => 'type', 'target_table' => 'reservations', 'target_column' => 'event_type', 'condition_value' => null, 'signification' => 'Type evenement legacy.'],
            ['source_table' => 'tbl_reservation', 'source_column' => 'dateDebut', 'target_table' => 'reservations', 'target_column' => 'start_date', 'condition_value' => null, 'signification' => 'Date debut reservation.'],
            ['source_table' => 'tbl_reservation', 'source_column' => 'dateFin', 'target_table' => 'reservations', 'target_column' => 'end_date', 'condition_value' => null, 'signification' => 'Date fin reservation.'],
            ['source_table' => 'tbl_reservation', 'source_column' => 'heureDebut', 'target_table' => 'reservations', 'target_column' => 'start_time', 'condition_value' => null, 'signification' => 'Heure debut reservation.'],
            ['source_table' => 'tbl_reservation', 'source_column' => 'heureFin', 'target_table' => 'reservations', 'target_column' => 'end_time', 'condition_value' => null, 'signification' => 'Heure fin reservation.'],
            ['source_table' => 'tbl_reservation', 'source_column' => 'nbPlace', 'target_table' => 'reservations', 'target_column' => 'guest_count', 'condition_value' => null, 'signification' => 'Nombre de places/invites.'],
            ['source_table' => 'tbl_reservation', 'source_column' => 'prix', 'target_table' => 'reservations', 'target_column' => 'total_amount', 'condition_value' => null, 'signification' => 'Montant total reservation.'],
            ['source_table' => 'tbl_reservation', 'source_column' => 'noteAdmin', 'target_table' => 'reservations', 'target_column' => 'note_admin', 'condition_value' => null, 'signification' => 'Note admin legacy a conserver.'],
            ['source_table' => 'tbl_reservation', 'source_column' => 'demandeEcheance', 'target_table' => 'reservations', 'target_column' => 'payment_due_date', 'condition_value' => null, 'signification' => 'Date echeance de paiement.'],
            ['source_table' => 'tbl_reservation', 'source_column' => 'type', 'target_table' => 'reservations', 'target_column' => 'service_slug', 'condition_value' => 'FORCE=salles', 'signification' => 'Reservation salle: service_slug force a salles.'],
            ['source_table' => 'tbl_reservation', 'source_column' => 'statut', 'target_table' => 'reservations', 'target_column' => 'status', 'condition_value' => 'statut=0', 'signification' => 'Proposition pre-mapping: pending.'],
            ['source_table' => 'tbl_reservation', 'source_column' => 'statut', 'target_table' => 'reservations', 'target_column' => 'status', 'condition_value' => 'statut=1', 'signification' => 'Proposition pre-mapping: confirmed.'],
            ['source_table' => 'tbl_reservation', 'source_column' => 'statut', 'target_table' => 'reservations', 'target_column' => 'status', 'condition_value' => 'statut=2', 'signification' => 'Proposition pre-mapping: cancelled.'],
            ['source_table' => 'tbl_reservation', 'source_column' => 'statut', 'target_table' => 'reservations', 'target_column' => 'status', 'condition_value' => 'statut=3', 'signification' => 'Proposition pre-mapping: completed.'],

            // Flags reservation -> reservation_additional_services (a verifier metier)
            ['source_table' => 'tbl_reservation', 'source_column' => 'cuisine', 'target_table' => 'reservation_additional_services', 'target_column' => 'module_slug', 'condition_value' => 'cuisine=1', 'signification' => 'Ajouter service additionnel: module_slug=salles, label=Cuisine.'],
            ['source_table' => 'tbl_reservation', 'source_column' => 'tableCM', 'target_table' => 'reservation_additional_services', 'target_column' => 'module_slug', 'condition_value' => 'tableCM=1', 'signification' => 'Ajouter service additionnel: module_slug=salles, label=Table CM.'],
            ['source_table' => 'tbl_reservation', 'source_column' => 'voiture', 'target_table' => 'reservation_additional_services', 'target_column' => 'module_slug', 'condition_value' => 'voiture=1', 'signification' => 'Ajouter service additionnel: module_slug=voiture.'],
            ['source_table' => 'tbl_reservation', 'source_column' => 'troupe', 'target_table' => 'reservation_additional_services', 'target_column' => 'module_slug', 'condition_value' => 'troupe=1', 'signification' => 'Ajouter service additionnel: module_slug=troupe-musicale.'],
            ['source_table' => 'tbl_reservation', 'source_column' => 'prestation', 'target_table' => 'reservation_additional_services', 'target_column' => 'module_slug', 'condition_value' => 'prestation=1', 'signification' => 'Ajouter service additionnel: module_slug=animation.'],
            ['source_table' => 'tbl_reservation', 'source_column' => 'photographe', 'target_table' => 'reservation_additional_services', 'target_column' => 'module_slug', 'condition_value' => 'photographe=1', 'signification' => 'Ajouter service additionnel: module_slug=photographe.'],
            ['source_table' => 'tbl_reservation', 'source_column' => 'gateau', 'target_table' => 'reservation_additional_services', 'target_column' => 'module_slug', 'condition_value' => 'gateau=1', 'signification' => 'Ajouter service additionnel: module_slug=salles, label=Gateau.'],

            // tbl_paiement -> payments
            ['source_table' => 'tbl_paiement', 'source_column' => 'paiementId', 'target_table' => 'payments', 'target_column' => 'id', 'condition_value' => null, 'signification' => 'ID paiement legacy, utile pour trace.'],
            ['source_table' => 'tbl_paiement', 'source_column' => 'reservationId', 'target_table' => 'payments', 'target_column' => 'reservation_id', 'condition_value' => null, 'signification' => 'FK reservation via migration_map_reservations.'],
            ['source_table' => 'tbl_paiement', 'source_column' => 'recepteurId', 'target_table' => 'payments', 'target_column' => 'user_id', 'condition_value' => null, 'signification' => 'Utilisateur recepteur (si mappe).'],
            ['source_table' => 'tbl_paiement', 'source_column' => 'valeur', 'target_table' => 'payments', 'target_column' => 'amount', 'condition_value' => null, 'signification' => 'Montant paiement.'],
            ['source_table' => 'tbl_paiement', 'source_column' => 'createdDate', 'target_table' => 'payments', 'target_column' => 'paid_at', 'condition_value' => null, 'signification' => 'Date de paiement.'],
            ['source_table' => 'tbl_paiement', 'source_column' => 'libele', 'target_table' => 'payments', 'target_column' => 'note', 'condition_value' => null, 'signification' => 'Libelle legacy conserve dans note.'],

            // tbl_access_matrix -> role_feature_permissions (a ajuster)
            ['source_table' => 'tbl_access_matrix', 'source_column' => 'roleId', 'target_table' => 'role_feature_permissions', 'target_column' => 'role_id', 'condition_value' => null, 'signification' => 'Role legacy associe aux droits.'],
            ['source_table' => 'tbl_access_matrix', 'source_column' => 'access', 'target_table' => 'role_feature_permissions', 'target_column' => 'can_view', 'condition_value' => 'depends_on_access_json', 'signification' => 'Decodage JSON access vers can_view/create/update/delete.'],

            // Packs/services legacy -> service modules
            ['source_table' => 'tbl_pack_prestation', 'source_column' => 'nom', 'target_table' => 'service_module_packs', 'target_column' => 'name', 'condition_value' => null, 'signification' => 'Nom pack prestation.'],
            ['source_table' => 'tbl_pack_prestation', 'source_column' => 'prix', 'target_table' => 'service_module_packs', 'target_column' => 'price', 'condition_value' => null, 'signification' => 'Prix pack prestation.'],
            ['source_table' => 'tbl_pack_prestation', 'source_column' => 'description', 'target_table' => 'service_module_packs', 'target_column' => 'description', 'condition_value' => null, 'signification' => 'Description pack prestation.'],
            ['source_table' => 'tbl_pack_prestation', 'source_column' => 'type', 'target_table' => 'service_module_packs', 'target_column' => 'module_slug', 'condition_value' => null, 'signification' => 'type legacy a normaliser vers module_slug cible.'],
            ['source_table' => 'tbl_pack_troupe', 'source_column' => 'nom', 'target_table' => 'service_module_packs', 'target_column' => 'name', 'condition_value' => 'module_slug=troupe-musicale', 'signification' => 'Nom pack troupe.'],
            ['source_table' => 'tbl_pack_troupe', 'source_column' => 'prix', 'target_table' => 'service_module_packs', 'target_column' => 'price', 'condition_value' => 'module_slug=troupe-musicale', 'signification' => 'Prix pack troupe.'],
            ['source_table' => 'tbl_pack_photographe', 'source_column' => 'nom', 'target_table' => 'service_module_packs', 'target_column' => 'name', 'condition_value' => 'module_slug=photographe', 'signification' => 'Nom pack photographe.'],
            ['source_table' => 'tbl_pack_photographe', 'source_column' => 'prix', 'target_table' => 'service_module_packs', 'target_column' => 'price', 'condition_value' => 'module_slug=photographe', 'signification' => 'Prix pack photographe.'],
            ['source_table' => 'tbl_pack_troupe_artiste', 'source_column' => 'nom', 'target_table' => 'service_module_items', 'target_column' => 'name', 'condition_value' => 'module_slug=chanteur', 'signification' => 'Nom artiste/chanteur.'],
            ['source_table' => 'tbl_pack_troupe_artiste', 'source_column' => 'prix', 'target_table' => 'service_module_items', 'target_column' => 'base_price', 'condition_value' => 'module_slug=chanteur', 'signification' => 'Prix de base artiste/chanteur.'],
            ['source_table' => 'tbl_pack_troupe_artiste', 'source_column' => 'statut', 'target_table' => 'service_module_items', 'target_column' => 'status', 'condition_value' => 'module_slug=chanteur', 'signification' => 'Statut artiste a normaliser (active/inactive).'],
        ];

        foreach ($rows as $index => $row) {
            MigrationMapping::query()->updateOrCreate(
                [
                    'source_table' => $row['source_table'],
                    'source_column' => $row['source_column'],
                    'target_table' => $row['target_table'],
                    'target_column' => $row['target_column'],
                    'condition_value' => $row['condition_value'],
                ],
                [
                    'signification' => $row['signification'],
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ]
            );
        }
    }
}
