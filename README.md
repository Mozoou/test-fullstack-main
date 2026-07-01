# Test Kalitics fullstack

## Prérequis
* Installer le CLI de symfony
* Préparer une base de donnée SQl (le projet a été créé avec mariaDB)

## Commandes à exécuter
```shell
# Adapter DATABASE_URL dans .env pour votre database SQL
symfony composer install
symfony console doctrine:migrations:migrate

# Pour démarrer le serveur local
symfony serve
```

## Démarrer avec Docker

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