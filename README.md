# Test technique Symfony — Pointages (Kalitics)

Application de gestion de pointages (collaborateurs / chantiers / pointages) réalisée dans le cadre du test technique Symfony décrit dans [`docs/`](docs). L'énoncé complet demandait de faire évoluer un système de pointage existant pour supprimer la ressaisie répétitive du formulaire de création — voir la section [Nouveau système de pointage](#nouveau-système-de-pointage) pour le détail de la solution retenue.

## Sommaire

- [Stack technique](#stack-technique)
- [Installation](#installation)
- [Comptes de démonstration](#comptes-de-démonstration)
- [Se connecter : manager vs collaborateur](#se-connecter--manager-vs-collaborateur)
- [Modèle de données](#modèle-de-données)
- [Nouveau système de pointage](#nouveau-système-de-pointage)
- [Sécurité et rôles](#sécurité-et-rôles)
- [Pages et routes](#pages-et-routes)
- [Outils utilisés pour accélérer le développement](#outils-utilisés-pour-accélérer-le-développement)
- [Pistes d'amélioration](#pistes-damélioration)

## Stack technique

- PHP 8.2, Symfony 7.0
- Doctrine ORM 3 / Doctrine Migrations / Doctrine Fixtures (MariaDB)
- Twig + [symfonycasts/tailwind-bundle](https://github.com/symfonycasts/tailwind-bundle) (Tailwind CSS compilé côté serveur, sans toolchain Node)
- Docker / FrankenPHP (image `dunglas/symfony-docker`) pour un environnement clé en main
- FakerPHP pour générer des données de démonstration réalistes

## Installation

### Prérequis

- Installer le [CLI Symfony](https://symfony.com/download)
- Préparer une base de données SQL (le projet a été créé avec MariaDB)

### Option A — en local avec le CLI Symfony

```shell
# Adapter DATABASE_URL dans .env (ou .env.local) pour votre base de données
symfony composer install

# Créer le schéma
symfony console doctrine:migrations:migrate

# Charger les données de démonstration (comptes, chantiers, pointages)
symfony console doctrine:fixtures:load

# Démarrer le serveur local
symfony serve
```

### Option B — avec Docker

Le projet peut aussi être lancé via Docker Compose (PHP/FrankenPHP, base de données MariaDB, et Mailpit pour les emails).

```shell
docker compose up -d
```

Pour changer de ports sans toucher au fichier `.env` versionné, créez un fichier `.env.local` (ignoré par git) à la racine du projet avec des ports alternatifs :

```dotenv
HTTP_PORT=8080
HTTPS_PORT=8443
HTTP3_PORT=8443
```

Docker Compose ne charge **pas** automatiquement `.env.local` (contrairement à Symfony). Il faut donc préciser les deux fichiers explicitement au lancement :

```shell
docker compose --env-file .env --env-file .env.local up -d
```

L'application sera alors accessible sur `https://localhost:8443/` (le port `8080` en HTTP redirige automatiquement vers le HTTPS).

Une fois les conteneurs démarrés, exécutez le schéma et les fixtures dans le conteneur `php` :

```shell
docker compose exec php bin/console doctrine:migrations:migrate
docker compose exec php bin/console doctrine:fixtures:load
```

## Comptes de démonstration

Les fixtures (`src/DataFixtures/`) créent automatiquement :

| Rôle | Email | Mot de passe | Détail |
|---|---|---|---|
| Manager (chef de chantier) | `manager@example.com` | `password` | 1 compte, matricule `MGR001` |
| Collaborateur | `prenom.nom@example.com` | `password` | 20 comptes générés aléatoirement (Faker, locale `fr_FR`), matricules `COL001` à `COL020` — l'adresse email exacte de chaque collaborateur s'affiche dans la liste des collaborateurs (`/users/`) |

Elles génèrent aussi 15 chantiers (`ProjectFixtures`) et 200 pointages aléatoires (`ClockingFixtures`) pour avoir un jeu de données réaliste dès le premier lancement.

## Se connecter : manager vs collaborateur

1. Aller sur `/login`.
2. Se connecter avec l'email et le mot de passe d'un des comptes ci-dessus.
3. Cliquer sur **Pointages** puis **+ Créer** dans le menu.

Le lien "+ Créer" pointe toujours vers la même route (`app_Clocking_create`), mais le contrôleur redirige automatiquement selon le rôle de l'utilisateur connecté (`src/Controller/ClockingCollectionController.php::create`) :

- **Manager** (`ROLE_MANAGER`) → formulaire multi-collaborateurs (`app_Clocking_create_manager`)
- **Collaborateur** (`ROLE_USER`) → formulaire multi-chantiers (`app_Clocking_create_collaborator`)

Un compte manager possède aussi `ROLE_USER` (ajouté automatiquement à tous les utilisateurs dans `User::getRoles()`), mais l'écran de saisie qui lui est proposé par défaut reste celui du manager.

Les pages de liste (accueil, pointages, chantiers, collaborateurs) sont consultables sans être connecté ; seule la **création** d'un pointage nécessite une authentification (voir [Sécurité et rôles](#sécurité-et-rôles)).

## Modèle de données

Le schéma de base fourni au départ n'a pas été modifié (une seule migration, `Version20240207125847`) : la problématique fonctionnelle a été résolue au niveau formulaire/contrôleur plutôt qu'en ajoutant une table de "lot" de pointages, afin de rester au plus proche du modèle initial demandé par l'énoncé.

```
User (collaborateur)              Project (chantier)
 - id                              - id
 - firstName / lastName            - name
 - matricule (unique)              - address
 - email (identifiant de connexion)- dateStart
 - password (hashé)                - dateEnd (nullable, doit être > dateStart)
 - roles (ROLE_USER / ROLE_MANAGER)

                    Clocking (pointage)
                     - id
                     - date
                     - duration (heures, > 0 et <= 10)
                     - clockingUser   -> ManyToOne User
                     - clockingProject -> ManyToOne Project
```

Une ligne `Clocking` reste toujours : 1 collaborateur + 1 chantier + 1 date + 1 durée. C'est la création qui génère désormais plusieurs lignes en une seule soumission de formulaire (voir ci-dessous).

## Nouveau système de pointage

### Problème initial (énoncé du test)

1. Un collaborateur qui travaille sur plusieurs chantiers le même jour devait remplir le formulaire de création autant de fois qu'il y a de chantiers, en ressaisissant à chaque fois le collaborateur et la date.
2. Un chef de chantier qui voulait pointer plusieurs collaborateurs sur le même chantier le même jour devait remplir le formulaire autant de fois qu'il y a de collaborateurs.

### Solution mise en place

Deux parcours de saisie dédiés, chacun avec un DTO et un formulaire imbriqué (`CollectionType`) permettant d'ajouter/retirer des lignes dynamiquement en JavaScript, **sans rechargement de page**, avant l'unique soumission finale.

#### 1. Collaborateur — un pointage, plusieurs chantiers

- Route : `app_Clocking_create_collaborator`, formulaire `CollaboratorClockingType` (`src/Form/CollaboratorClockingType.php`), alimenté par `CollaboratorClockingDTO` (`src/Dto/CollaboratorClockingDTO.php`) :
  - `date` : saisie **une seule fois**
  - `projects` : liste de lignes `{ project, duration }` (type imbriqué `ProjectDurationType`), avec bouton **+ Ajouter un chantier** (JS, `templates/app/Clocking/create_collaborator.html.twig`) qui clone le "prototype" Symfony du `CollectionType` et un bouton `✕` pour retirer une ligne.
  - Le collaborateur connecté est automatiquement utilisé comme `clockingUser` : il n'y a même pas de champ à sélectionner sur ce formulaire.
- À la soumission (`ClockingCollectionController::createCollaborator`), le contrôleur boucle sur `$formData->projects` et crée **une entité `Clocking` par chantier saisi**, partageant le même utilisateur et la même date.
- Résultat en base : autant de lignes `Clocking` que de chantiers ajoutés, pour une seule action de saisie.

#### 2. Manager — un pointage, plusieurs collaborateurs

- Route : `app_Clocking_create_manager`, formulaire `ManagerClockingType` (`src/Form/ManagerClockingType.php`), alimenté par `ManagerClockingDTO` (`src/Dto/ManagerClockingDTO.php`) :
  - `date` et `project` (chantier) : saisis **une seule fois**
  - `collaborators` : liste de lignes `{ user, duration }` (type imbriqué `UserDurationType`), avec le même mécanisme JS d'ajout/suppression dynamique (`templates/app/Clocking/create_manager.html.twig`).
- À la soumission (`ClockingCollectionController::createManager`), le contrôleur boucle sur `$formData->collaborators` et crée **une entité `Clocking` par collaborateur ajouté**, partageant le même chantier et la même date.
- Résultat en base : autant de lignes `Clocking` que de collaborateurs pointés, pour une seule action de saisie.

#### Règles de validation

- Chaque liste (`projects` / `collaborators`) doit contenir au moins une ligne (`Assert\Count(min: 1)`).
- Chaque durée doit être renseignée et ne peut pas dépasser 10 heures (contrainte dupliquée au niveau du champ de formulaire *et* de l'entité `Clocking::duration`, pour bloquer aussi bien la saisie via le formulaire que toute création programmatique).
- La date de fin d'un chantier (`Project::dateEnd`) doit être strictement postérieure à sa date de début.

L'ancien formulaire unique (`CreateClockingType` + `templates/app/Clocking/create.html.twig`, qui ne gérait qu'un pointage à la fois) a été supprimé et remplacé par ces deux parcours.

## Sécurité et rôles

- Deux rôles : `ROLE_USER` (tout collaborateur connecté) et `ROLE_MANAGER` (chef de chantier). `User::getRoles()` garantit que `ROLE_USER` est toujours présent, même pour un manager.
- Authentification par formulaire classique (`security.yaml`), provider Doctrine sur l'email, mots de passe hashés (`password_hashers: auto`) et upgrade automatique du hash (`UserRepository implements PasswordUpgraderInterface`).
- Accès restreint via l'attribut `#[IsGranted(...)]` directement sur les actions :
  - `createCollaborator` → `#[IsGranted('ROLE_USER')]`
  - `createManager` → `#[IsGranted('ROLE_MANAGER')]`

## Pages et routes

| Route | URL | Accès | Description |
|---|---|---|---|
| `app_home` | `/` | public | Page d'accueil |
| `app_Clocking_list` | `/clockings/` | public | Liste des pointages |
| `app_Clocking_create` | `/clockings/clockings/create` | connecté | Redirige vers le bon formulaire selon le rôle |
| `app_Clocking_create_collaborator` | `/clockings/clockings/create/collaborator` | `ROLE_USER` | Formulaire multi-chantiers |
| `app_Clocking_create_manager` | `/clockings/clockings/create/manager` | `ROLE_MANAGER` | Formulaire multi-collaborateurs |
| `app_Clocking_delete` | `/clockings/{id}/` | `ROLE_MANAGER` | Suppression d'un pointage |
| `app_Project_list` | `/projects/` | public | Liste des chantiers |
| `app_User_list` | `/users/` | public | Liste des collaborateurs |
| `app_login` / `app_logout` | `/login` / `/logout` | public | Authentification |

En pratique, il n'est jamais nécessaire de saisir ces URLs à la main : la navigation se fait via les liens du menu (`templates/base.html.twig`) et le bouton **+ Créer** de la liste des pointages, qui utilisent les noms de route.

## Outils utilisés pour accélérer le développement

- **Claude Code (Anthropic)** — la quasi-totalité du code de ce projet (entités, contrôleurs, DTO, formulaires imbriqués, JS dynamique, fixtures, templates) a été écrite à la main, sans IA. Claude Code n'est intervenu qu'après coup, sur une tâche unique et clairement délimitée : lire le PDF d'énoncé fourni dans `docs/`, relire le code déjà écrit, et rédiger ce README pour documenter le travail réalisé.
- **symfonycasts/tailwind-bundle** — permet d'utiliser Tailwind CSS compilé côté serveur sans installer de toolchain Node/npm, pour une UI soignée rapidement.
- **doctrine/doctrine-fixtures-bundle + fakerphp/faker** — génération de comptes, chantiers et pointages de démonstration réalistes en une commande (`doctrine:fixtures:load`), utile pour tester manuellement les deux parcours de saisie sans créer les données à la main.
- **Docker / FrankenPHP (dunglas/symfony-docker)** — environnement de développement prêt à l'emploi (PHP, MariaDB, Mailpit) en une commande `docker compose up -d`.

## Pistes d'amélioration

- Restreindre en `access_control` les listes (pointages, chantiers, collaborateurs) si elles ne doivent pas rester publiques.
- Ajouter des tests fonctionnels (`tests/`) sur les deux parcours de création de pointage, notamment sur les cas limites de validation (durée > 10h, liste vide).
- Envisager une entité "lot de pointage" (ex. `ClockingBatch`) si le besoin d'un historique par soumission (plutôt que par ligne) devient nécessaire.
