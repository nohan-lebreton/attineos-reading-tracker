# Reading Tracker

Mini-application de suivi de lectures réalisée pour le test technique Attineos.

- **Front** : Vue 3, TypeScript, Vite, Vue Router, Pinia
- **Back** : Symfony 7.4 (API REST), Doctrine ORM
- **Base de données** : PostgreSQL 17 (Docker)
- **Catalogue de livres** : [Gutendex](https://gutendex.com/) (API du projet Gutenberg)

## Fonctionnalités

- Rechercher un livre par titre ou auteur, avec pagination
- Consulter le détail d'un livre (résumé, thèmes, langue)
- Ajouter un livre à ses lectures avec un statut : à lire, en cours, lu, abandonné
- Modifier une lecture : statut, date de début, date de fin, note sur 5, commentaire
- Lister ses lectures et les filtrer par statut
- Supprimer une lecture

## Prérequis

- PHP 8.2 ou plus, avec l'extension `pdo_pgsql`
- Composer
- Node.js 22.18 ou plus, et npm
- Docker (uniquement pour PostgreSQL)

## Installation et lancement

### 1. Base de données

À la racine du projet :

```bash
docker compose up -d
```

### 2. Back-end

```bash
cd backend
composer install
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console app:create-demo-user
php -S 127.0.0.1:8000 -t public
```

L'API est disponible sur http://127.0.0.1:8000.

### 3. Front-end

Dans un second terminal :

```bash
cd frontend
npm install
npm run dev
```

L'application est disponible sur http://localhost:5173.

## Configuration

Les valeurs par défaut fonctionnent sans modification. Pour les surcharger en local, créer un fichier `.env.local` (non versionné) à côté du `.env` concerné.

| Fichier | Variable | Rôle |
|---|---|---|
| `backend/.env` | `DATABASE_URL` | Connexion PostgreSQL |
| `backend/.env` | `GUTENDEX_BASE_URL` | URL de l'API Gutendex |
| `backend/.env` | `CORS_ALLOW_ORIGIN` | Origines autorisées à appeler l'API |
| `frontend/.env` | `VITE_API_URL` | URL de l'API Symfony |

## Tests

```bash
cd backend && php bin/phpunit
cd frontend && npm run test:unit -- --run
```

## API

| Méthode | Route | Description | Codes |
|---|---|---|---|
| GET | `/api/books?search=&page=` | Recherche dans le catalogue | 200, 400, 503 |
| GET | `/api/books/{gutendexId}` | Détail d'un livre et lecture associée | 200, 404, 503 |
| GET | `/api/readings?status=` | Mes lectures, filtre optionnel | 200, 400 |
| POST | `/api/readings` | Ajouter `{ "gutendexId": 1342, "status": "to_read" }` | 201, 409, 422, 503 |
| PATCH | `/api/readings/{id}` | Modifier une partie des champs | 200, 404, 422 |
| DELETE | `/api/readings/{id}` | Supprimer | 204, 404 |

Les erreurs ont toujours le même format :

```json
{ "error": { "status": 422, "message": "Les données envoyées sont invalides.", "details": { "rating": "..." } } }
```

## Architecture

```
Vue (localhost:5173) ──> API Symfony (127.0.0.1:8000) ──> PostgreSQL
                                     │
                                     └──> Gutendex (cache 1 h, timeout 5 s)
```

- Le front n'appelle jamais Gutendex directement : il passe par l'API, qui met les réponses en cache, gère les erreurs et renvoie un format maîtrisé.
- Le back dépend d'une interface `BookCatalog` ; `GutendexClient` en est l'implémentation. Changer de source de livres ne touche qu'une classe.
- Quand un livre est ajouté aux lectures, ses informations sont copiées dans la table `book`. La page « Mes lectures » n'a donc pas besoin de Gutendex.

```
backend/src/
├── Controller/      routes et réponses HTTP
├── Dto/             données reçues et règles de validation
├── Service/         règles métier
├── Repository/      requêtes en base
├── Entity/, Enum/   modèle de données
├── Catalog/         interface du catalogue de livres
├── Gutendex/        client HTTP Gutendex
└── EventListener/   conversion des exceptions en réponses JSON

frontend/src/
├── api/             client HTTP et appels à l'API
├── stores/          état partagé (Pinia)
├── views/           pages
├── components/      composants réutilisables
├── composables/     logique réutilisable
└── types/           types des réponses de l'API
```

## Choix et limites

- **Utilisateur unique** : l'application fonctionne avec un utilisateur de démo, créé par `app:create-demo-user`. Tout le code reçoit l'utilisateur via `CurrentUserProvider`, ce qui permet d'ajouter une authentification sans modifier le reste.
- **Disponibilité de Gutendex** : pendant le développement, Gutendex et gutenberg.org étaient en maintenance. L'API renvoie alors une erreur 503 avec un message clair, les couvertures sont remplacées par un visuel avec le titre, et le détail d'un livre déjà suivi s'affiche à partir des données en base.
- Les messages de validation sont en anglais (composant de traduction non installé).
- La liste « Mes lectures » n'est pas paginée.

## Pistes d'amélioration

- Authentification (JWT) et inscription
- Tests fonctionnels des endpoints
- Pagination et tri de « Mes lectures »
- Stockage des couvertures côté back
- Traduction des messages de validation

## Problèmes fréquents

- **Le port 5432 est déjà utilisé** : un PostgreSQL local tourne déjà. L'arrêter, ou changer le port dans `compose.yaml` et dans `DATABASE_URL`.
- **`npm install` échoue avec une erreur « edgesOut »** : bug de npm 10, utiliser `npx npm@11 install`.
