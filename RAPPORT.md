# Rapport – Reading Tracker

## 1. Périmètre

Application de suivi de lectures : recherche de livres via Gutendex, ajout à ses lectures avec un statut, suivi (dates, note, commentaire), filtre par statut, suppression.

- **Réalisé** : l'ensemble du MVP, côté API et côté interface.
- **Hors périmètre** : authentification et inscription. L'application fonctionne avec un utilisateur unique (voir 3.3).

## 2. Architecture

```
Vue 3 (SPA) ──HTTP/JSON──> API Symfony ──Doctrine──> PostgreSQL
                                │
                                └──HttpClient + cache──> Gutendex
```

| Couche | Rôle |
|---|---|
| `Controller/` | Routes, lecture de la requête, réponse JSON et code HTTP. Aucune règle métier. |
| `Dto/` | Données acceptées en entrée et règles de validation (`#[Assert]`). |
| `Service/ReadingService` | Règles métier : doublons, dates automatiques, cohérence des dates, mise à jour partielle. |
| `Repository/` | Requêtes en base. |
| `Catalog/` + `Gutendex/` | Interface `BookCatalog` et son implémentation HTTP. |
| `EventListener/ApiExceptionListener` | Conversion de toute exception en réponse JSON au format unique. |
| Front `api/` | Unique point d'accès HTTP (`client.ts`), erreurs normalisées en `ApiError`. |
| Front `stores/` | État partagé des lectures (Pinia). |

**Exemple : `POST /api/readings {"gutendexId": 1342}`**

1. `#[MapRequestPayload]` désérialise le JSON en `CreateReadingInput` et le valide : 422 si invalide, le contrôleur n'est pas appelé.
2. `ReadingService::add()` cherche le livre en base. S'il est déjà suivi par l'utilisateur : 409.
3. Si le livre n'est pas en base, il est récupéré via `BookCatalog::getBook()`, depuis le cache ou depuis Gutendex.
4. `Book` et `Reading` sont créés, puis enregistrés en une transaction (`flush`).
5. La réponse est sérialisée avec le groupe `reading` et renvoyée avec le code 201.

## 3. Choix techniques

| Choix | Alternative écartée | Raison |
|---|---|---|
| 3.1 Le front passe par l'API pour accéder à Gutendex | Appel direct depuis le front | Cache, timeout, format de réponse maîtrisé, source remplaçable. |
| 3.2 Copie locale des livres suivis (table `book`, `gutendexId` unique) | Appeler Gutendex à chaque affichage | Clés étrangères, « Mes lectures » sans appel externe, résistance aux pannes. |
| 3.3 Utilisateur unique via `CurrentUserProvider` | JWT dès le départ | Temps limité. Le reste du code reçoit un `User` : passer au JWT ne modifie que `CurrentUserProvider::get()`. |
| 3.4 Contrôleurs écrits à la main | API Platform | Code explicite, contrôle total des codes HTTP et des règles métier. |
| 3.5 Interface `BookCatalog` | Dépendre directement de `GutendexClient` | Changer de source ou simuler le catalogue dans les tests sans toucher au service. |
| 3.6 Auteurs et langues en JSONB | Tables `author` et `book_author` | Données en lecture seule, toujours affichées avec le livre, jamais filtrées. |
| 3.7 Cache de 1 h, timeout de 5 s, durée maximale de 10 s | Pas de cache | Catalogue quasi statique. Une API lente ne bloque pas l'utilisateur. Les erreurs ne sont pas mises en cache. |
| 3.8 Symfony 7.4 | Symfony 8.0 | Version LTS. |
| 3.9 Docker pour PostgreSQL uniquement | Conteneuriser PHP et Node aussi | La base est le seul prérequis difficile à installer. Contrepartie : le correcteur doit avoir PHP 8.2+ et Node 22. |
| 3.10 `fetch` encapsulé | axios | Pas de dépendance supplémentaire pour 6 appels. |
| 3.11 Pinia uniquement pour les lectures | Tout l'état dans Pinia | Seules les lectures sont partagées entre plusieurs écrans. La recherche reste locale à sa page. |
| 3.12 Recherche dans l'URL (`?q=&page=`), debounce de 400 ms, `AbortController` | État local | URL partageable, bouton retour fonctionnel, moins de requêtes, pas de résultat périmé affiché. |
| 3.13 Filtre par statut côté front | Filtre côté serveur (`?status=`, disponible) | Volume faible, filtrage instantané, compteurs par statut. |

## 4. Modèle de données

| Table | Champs principaux | Contraintes |
|---|---|---|
| `user` | email, password (hashé), roles, created_at | email unique |
| `book` | gutendex_id, title, authors (jsonb), languages (jsonb), cover_url, created_at | gutendex_id unique |
| `reading` | user_id, book_id, status, started_at, finished_at, rating, comment, created_at, updated_at | unique (user_id, book_id) ; clés étrangères ; `ON DELETE CASCADE` sur user |

- `status` : enum PHP `ReadingStatus` (`to_read`, `reading`, `read`, `abandoned`).
- Index sur (user_id, status) pour la liste filtrée.
- Règles métier :
  - passer au statut « en cours » ou « lu » renseigne `started_at` s'il est vide ;
  - passer au statut « lu » renseigne `finished_at` s'il est vide ;
  - `finished_at` doit être postérieure ou égale à `started_at` ;
  - la note va de 1 à 5 ;
  - le commentaire fait au plus 2000 caractères.
- `updated_at` est mis à jour automatiquement (`#[ORM\PreUpdate]`).
- Schéma versionné par une migration Doctrine.

## 5. Bonnes pratiques

- **Validation à trois niveaux** :
  - la forme des données dans le DTO (422) ;
  - les règles métier dans le service (409, 422) ;
  - l'intégrité dans la base (contrainte unique). La contrainte unique couvre aussi deux requêtes simultanées : l'exception Doctrine est convertie en 409.
- **Codes HTTP** : 200, 201, 204, 400, 404, 409, 422 et 503. Toutes les erreurs ont le même format : `{"error": {"status", "message", "details"}}`.
- **Données exposées** : seules les propriétés des groupes de sérialisation sont renvoyées. L'utilisateur n'est jamais exposé.
- **Accès** : chaque lecture est recherchée par `id` **et** par utilisateur. La lecture d'un autre utilisateur renvoie donc un 404.
- **PATCH partiel** : seuls les champs envoyés sont modifiés. `null` efface la valeur, un champ absent n'est pas touché. Le front n'envoie que les champs modifiés.
- **Sécurité** :
  - requêtes Doctrine paramétrées ;
  - pas de `v-html`, donnée externe affichée échappée ;
  - URL des images limitées à http(s) (`safeUrl`) ;
  - CORS limité à localhost ;
  - mots de passe hashés.
- **Performance** : jointure `book` chargée dans la même requête que les lectures (pas de N+1), cache des réponses Gutendex.
- **Interface** :
  - états chargement, erreur et vide sur chaque page ;
  - erreurs de validation affichées sous le champ concerné ;
  - confirmation avant suppression ;
  - accessibilité de base : labels, `alt`, `aria-pressed`, navigation au clavier.
- **Tests** :
  - 6 tests PHPUnit sur `GutendexClient` avec `MockHttpClient` : correspondance des champs, cache, 404, 503, timeout, erreurs non mises en cache ;
  - 4 tests Vitest : `safeUrl`, formatage.
- **Git** : un commit par étape, messages conventionnels.

## 6. Difficultés rencontrées

| Difficulté | Solution |
|---|---|
| Gutendex, puis gutenberg.org, indisponibles pendant le développement : seules les URL encore en cache chez Cloudflare répondaient | Timeout de 5 s et réponse 503 avec un message clair. Couverture remplacée par un visuel avec le titre. Le détail d'un livre déjà suivi s'affiche à partir des données en base. Tests du client avec `MockHttpClient`, indépendants de l'API réelle. |
| PATCH : distinguer un champ absent d'un champ à `null` (le DTO renvoie `null` dans les deux cas) | Le contrôleur transmet la liste des clés reçues, et le service n'applique que celles-ci. |
| Doublon en cas de requêtes simultanées | Contrainte unique en base, exception convertie en 409. |
| `#[MapQueryParameter]` renvoie 404 sur une valeur invalide | `validationFailedStatusCode: 400`. |

## 7. Limites et améliorations

| Limite actuelle | Amélioration |
|---|---|
| Utilisateur unique | Authentification JWT, inscription, Voter Symfony |
| Pas de tests fonctionnels des endpoints | `WebTestCase` sur la création, le doublon, l'accès à la lecture d'un autre, la validation |
| « Mes lectures » non paginée | Pagination et filtre côté serveur |
| Couvertures chargées depuis gutenberg.org | Stockage des couvertures côté back |
| Messages de validation en anglais | Composant Translation |
| Dates calculées en UTC | Fuseau horaire configurable |
| Lancement en plusieurs commandes | Conteneuriser PHP et Node |
| Note autorisée quel que soit le statut | Règle à valider avec le métier |

## 8. Installation

Voir le [README](README.md).

## 9. Utilisation de l'IA

J'ai utilisé un assistant IA (Claude) tout au long du projet, comme accompagnement :

- explication des concepts Symfony et Doctrine, en comparaison avec Laravel que je connais mieux ;
- discussion des choix d'architecture et de leurs alternatives ;
- génération par l'IA d'une partie du code sur les sujets générique ou que je maîtrise, avec ma relecture, et à l'inverse relecture par l'IA du code que j'écris ;
- aide à la rédaction de la documentation.

Chaque fichier a été relu, et chaque choix discuté avant d'être retenu.
