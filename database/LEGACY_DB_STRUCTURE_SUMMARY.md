# Structure Ancienne Base de Donnees (Legacy)

Cette synthese decrit la structure utile de l ancienne base pour preparer un mapping fiable vers l application Laravel actuelle.

## 1) Tables metier principales

### users
- PK: id
- Champs principaux: name, email, password, role_id, status
- Role: comptes applicatifs / authentification

### roles
- PK: id
- Champs: name, slug, description
- Role: roles d acces

### clients
- PK: id
- FK: user_id -> users.id (nullable)
- Champs principaux:
  - client_type
  - fiscal_number, company_name
  - first_name, name
  - gender, birth_date
  - email, phone, phone_2
  - cin, date_cin
  - address_number, address_street, city, governorate
  - source, status
- Role: profil client complet

### salles
- PK: id
- Champs principaux:
  - name
  - salle_type (couvert / plein-air)
  - color_code
  - capacity
  - price_per_day
  - location, description
  - status
- Role: catalogue des salles

### reservations
- PK: id
- FK:
  - client_id -> clients.id
  - salle_id -> salles.id
  - user_id -> users.id (nullable)
- Champs principaux:
  - service_slug
  - title
  - start_date, end_date
  - start_time, end_time
  - status
  - total_amount
  - note_admin
- Role: reservation principale (salle ou service)

### payments
- PK: id
- FK:
  - reservation_id -> reservations.id
  - user_id -> users.id (nullable)
- Champs principaux: amount, method, reference, status, paid_at, note
- Role: reglements lies a reservation

### reservation_additional_services
- PK: id
- FK:
  - reservation_id -> reservations.id
  - linked_reservation_id -> reservations.id (nullable)
  - service_module_item_id -> service_module_items.id (nullable)
  - service_module_pack_id -> service_module_packs.id (nullable)
- Champs principaux: module_slug, label, amount, note
- Role: services additionnels rattaches a une reservation salle

### service_module_items
- PK: id
- Champs principaux: module_slug, name, phone, base_price, status, notes
- Role: prestataires par module (troupe, photographe, chanteur, notaire, animation, voiture)

### service_module_packs
- PK: id
- FK: service_module_item_id -> service_module_items.id (nullable)
- Champs principaux: module_slug, name, price, status, description
- Role: packs de service

## 2) Tables administration / permissions

### modules
- PK: id
- Champs: name, slug, description, is_active, sort_order

### module_features
- PK: id
- FK: module_id -> modules.id
- Champs: name, slug, is_active, sort_order

### role_feature_permissions
- PK: id
- FK:
  - role_id -> roles.id
  - module_feature_id -> module_features.id
- Champs: can_view, can_create, can_update, can_delete

## 3) Tables techniques Laravel

- cache
- cache_locks
- sessions
- jobs
- job_batches
- failed_jobs
- password_reset_tokens
- migrations

## 4) Relations metier cle

- Un client peut avoir plusieurs reservations
- Une salle peut avoir plusieurs reservations
- Une reservation peut avoir plusieurs paiements
- Une reservation salle peut avoir plusieurs services additionnels
- Un service additionnel peut pointer vers une reservation liee (linked_reservation_id)

## 5) Points de vigilance pour migration

1. service_slug
- Valeurs attendues cote application: salles, troupe-musicale, photographe, chanteur, notaire, animation, voiture
- Normaliser les valeurs legacy avant insertion

2. source client
- Normaliser vers les cles autorisees en cible (passager, presence-event, etc.)

3. statuts reservation
- Mapper les codes legacy numeriques vers pending/confirmed/cancelled/completed

4. dedoublonnage client
- Priorite CIN, puis nom+prenom+telephone

5. traçabilite
- Conserver old IDs dans des tables de mapping persistantes (clients/salles/reservations)

## 6) Resume de compatibilite

La structure legacy est globalement compatible avec la cible Laravel actuelle pour:
- clients
- salles
- reservations
- paiements
- services additionnels

Le mapping doit surtout securiser:
- normalisation des enums textuels
- mapping des statuts
- unicite client
- coherences FK
