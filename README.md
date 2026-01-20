# Vehicles Fleet

Système de gestion de flotte de véhicules permettant aux utilisateurs de réserver des véhicules et aux administrateurs de gérer l'ensemble du système.

## Contexte de l'application

Cette application est un système complet de gestion de flotte de véhicules conçu pour faciliter la réservation et la gestion des véhicules au sein d'une organisation. Elle permet :

- **Aux utilisateurs** : de consulter les véhicules disponibles et de créer/gérer leurs propres réservations
- **Aux administrateurs** : de gérer l'ensemble du système (utilisateurs, véhicules, réservations) via une interface d'administration complète
- **Aux développeurs** : d'accéder aux fonctionnalités via une API REST complète avec authentification par tokens

L'application gère les conflits de réservation, les statuts des véhicules et des réservations, ainsi que les rôles et permissions des utilisateurs.

## Choix techniques

### Backend

- **Laravel 12** : Framework PHP moderne et robuste pour le développement backend
- **Laravel Sanctum** : Authentification par tokens pour l'API REST
- **Architecture en couches** :
  - **Services** : Logique métier centralisée (`UserService`, `VehicleService`, `ReservationService`, etc.)
  - **Form Requests** : Validation des données d'entrée
  - **Controllers séparés** : `Web` pour l'interface web et `Api` pour l'API REST
  - **Exceptions métier** : Gestion des erreurs spécifiques (`ReservationConflictException`, `VehicleNotAvailableException`, etc.)

### Frontend

- **Inertia.js** : Framework permettant de créer des applications SPA sans API, en utilisant les contrôleurs Laravel classiques
- **React 19** : Bibliothèque JavaScript pour l'interface utilisateur
- **Tailwind CSS 4** : Framework CSS utilitaire pour le design
- **Vite** : Build tool moderne pour le développement et la compilation des assets
- **Composants réutilisables** : Architecture modulaire avec composants React (`Button`, `Input`, `Select`, `Textarea`, `DateInput`)

### Base de données

- **Eloquent ORM** : ORM de Laravel pour les interactions avec la base de données
- **Relations Eloquent** : Relations `hasMany`, `belongsTo`, `belongsToMany` pour modéliser les entités
- **Migrations** : Versioning du schéma de base de données

### Sécurité et autorisation

- **Laravel Gates** : Système d'autorisation pour contrôler l'accès aux fonctionnalités
- **Rôles et permissions** : Système de rôles (admin, employee) avec gestion des permissions
- **Validation stricte** : Validation côté serveur avec Form Requests
- **Protection CSRF** : Protection intégrée de Laravel

### Tests

- **PHPUnit** : Framework de tests unitaires et fonctionnels
- **Tests Feature** : Tests d'intégration pour les endpoints API et web
- **Tests Unit** : Tests unitaires pour les services

## Principales fonctionnalités

### Authentification

- **Connexion/Déconnexion** : Authentification par session pour l'interface web
- **Authentification API** : Authentification par tokens Sanctum pour l'API REST
- **Redirection automatique** : Redirection vers l'interface d'administration pour les administrateurs après connexion

### Gestion des utilisateurs (Admin)

- **CRUD complet** : Création, lecture, mise à jour et suppression d'utilisateurs
- **Gestion des rôles** : Attribution et retrait de rôles aux utilisateurs
- **Génération automatique de mot de passe** : Les mots de passe sont générés automatiquement lors de la création
- **Régénération de mot de passe** : Possibilité de régénérer le mot de passe d'un utilisateur
- **Pagination** : Liste paginée des utilisateurs

### Gestion des véhicules (Admin)

- **CRUD complet** : Gestion complète des véhicules (marque, modèle, plaque d'immatriculation, année, couleur)
- **Gestion des statuts** : Statuts des véhicules (disponible, en maintenance, hors service) avec transitions validées
- **Véhicules disponibles** : Filtrage des véhicules disponibles pour les réservations
- **Pagination** : Liste paginée des véhicules

### Gestion des réservations

#### Interface client

- **Liste des véhicules disponibles** : Consultation des véhicules disponibles avec pagination
- **Création de réservation** : Les utilisateurs peuvent créer leurs propres réservations
- **Mes réservations** : Consultation de toutes les réservations de l'utilisateur connecté
- **Annulation** : Possibilité d'annuler ses propres réservations

#### Interface admin

- **CRUD complet** : Gestion complète des réservations (création, modification, suppression)
- **Gestion des statuts** : Statuts des réservations (en attente, confirmée, annulée, terminée) avec transitions validées
- **Actions rapides** : Confirmation, annulation et complétion des réservations
- **Détection de conflits** : Vérification automatique des conflits de dates lors de la création/modification
- **Pagination** : Liste paginée des réservations

### API REST

L'application expose une API REST complète avec les endpoints suivants :

- **Authentification** : `/api/login`, `/api/logout`
- **Utilisateurs** : CRUD complet + gestion des rôles
- **Véhicules** : CRUD complet + mise à jour du statut + liste des véhicules disponibles
- **Réservations** : CRUD complet + actions sur les réservations (annuler, confirmer, compléter) + réservations par utilisateur/véhicule
- **Rôles** : Gestion des rôles
- **Export/Import** : Export des réservations, import de véhicules
- **Statistiques** : Statistiques générales, réservations par mois, véhicules les plus utilisés, taux d'occupation

### Interface utilisateur

- **Layouts séparés** : `AdminLayout` pour l'administration et `UserLayout` pour les utilisateurs
- **Composants réutilisables** : Composants React réutilisables pour une interface cohérente
- **Messages flash** : Affichage des messages de succès, d'erreur et des mots de passe générés
- **Design responsive** : Interface adaptée aux différentes tailles d'écran avec Tailwind CSS

## Structure du projet

```
app/
├── Enums/              # Énumérations (ReservationStatus, VehicleStatus)
├── Exceptions/          # Exceptions métier personnalisées
├── Http/
│   ├── Controllers/
│   │   ├── Api/        # Contrôleurs API REST
│   │   └── Web/        # Contrôleurs interface web
│   ├── Middleware/     # Middleware (HandleInertiaRequests)
│   └── Requests/        # Form Requests pour la validation
├── Models/              # Modèles Eloquent
└── Services/           # Services métier

resources/
├── js/
│   ├── Components/      # Composants React réutilisables
│   ├── Layouts/         # Layouts (AdminLayout, UserLayout)
│   └── Pages/           # Pages Inertia
│       ├── Admin/       # Pages d'administration
│       └── Client/      # Pages client
└── views/
    └── app.blade.php    # Template principal Inertia

routes/
├── api.php              # Routes API REST
└── web.php              # Routes interface web
```

## Installation

1. **Cloner le projet**
   ```bash
   git clone <repository-url>
   cd vehicles-fleet
   ```

2. **Installer les dépendances PHP**
   ```bash
   composer install
   ```

3. **Installer les dépendances JavaScript**
   ```bash
   npm install
   ```

4. **Configurer l'environnement**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Configurer la base de données**
   - Modifier le fichier `.env` avec vos paramètres de base de données
   - Exécuter les migrations : `php artisan migrate`
   - (Optionnel) Exécuter les seeders : `php artisan db:seed`

6. **Compiler les assets**
   ```bash
   npm run build
   ```

## Utilisation

### Développement

Pour lancer l'application en mode développement :

```bash
composer run dev
```

Cette commande lance simultanément :
- Le serveur PHP (`php artisan serve`)
- La queue Laravel (`php artisan queue:listen`)
- Les logs Laravel Pail (`php artisan pail`)
- Le serveur Vite (`npm run dev`)

### Tests

Exécuter les tests :

```bash
composer run test
```

### Production

Compiler les assets pour la production :

```bash
npm run build
```

## Technologies utilisées

- **Backend** : PHP 8.2+, Laravel 12
- **Frontend** : React 19, Inertia.js, Tailwind CSS 4
- **Build Tool** : Vite 7
- **Authentification API** : Laravel Sanctum 4
- **Tests** : PHPUnit 11
