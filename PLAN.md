# Product Review System — Implementation Plan

## Core Principles (decision filter for every task)

1. **Deliverability first** — a running system beats elegant incomplete code. Complete phases end-to-end before adding complexity.
2. **Domain purity** — `*/Domain/` has zero framework imports. If a class needs Doctrine or Symfony, it belongs in Infrastructure.
3. **Immutability by default** — `public readonly` on all domain aggregates and ORM entities. No getters/setters, no mutation after construction.
4. **Repositories as anti-corruption layer** — ORM entities never cross the Infrastructure boundary. Callers always receive Domain objects.
5. **DQL UPDATE for writes** — never load-mutate-flush an existing ORM entity. Keeps persistence side-effect-free.
6. **Async decoupling** — rating cache updates run in the consumer worker, not in the HTTP request. Cache miss falls back to DB + warm.
7. **Test boundaries at ports** — integration tests use real DB; unit tests use `RecordingRatingCache` test double; no mocking Doctrine internals.
8. **Document WHY** — every non-obvious decision gets a sentence in README. The assignment scores on thought process, not just working code.

---

## Architecture Decisions

- **Domain layer** — pure PHP, no framework or ORM dependencies.
- **Infrastructure layer** — Doctrine ORM entities (separate classes), mapper Domain ↔ ORM.
- **Pattern**: Repository accepts/returns Domain objects; internally converts via Mapper.
- **Field model**: domain aggregates and ORM entities use **`public readonly` only** (no getters/setters), except for Symfony interface methods (`UserOrmEntity`) and `occurredOn()` required by `DomainEventInterface`. Updates to `Product`/`User` in DB go via **DQL UPDATE** — no mutation of a loaded ORM entity.

```
Catalog/
  Domain/
    Product.php                    ← pure PHP, no ORM
    ProductId.php                  ← Value Object
    Repository/
      ProductRepositoryInterface.php
  Application/
    Query/GetProducts/             ← GetProductsQuery + Handler + Result DTO
    Query/GetProduct/              ← GetProductQuery  + Handler + Result DTO
    Command/ImportProducts/        ← ImportProductsCommand + Handler
  Infrastructure/
    Persistence/
      ORM/
        ProductOrmEntity.php       ← Doctrine #[ORM\Entity], no business logic
      Mapper/
        ProductMapper.php          ← ProductOrmEntity ↔ Product (domain)
      Repository/
        DoctrineProductRepository.php  ← implements ProductRepositoryInterface
    Http/
      DummyJsonApiClient.php
  Presentation/
    Http/
      ProductController.php

Review/   (same structure)
  Domain/
    Review.php, ReviewId.php, Rating.php
    Event/ReviewAddedEvent.php
    Exception/ReviewAlreadyExistsException.php
    Repository/ReviewRepositoryInterface.php
  Application/
    Command/AddReview/
    Command/GenerateFakeReviews/
    Query/GetProductReviews/
    MessageHandler/ReviewAddedHandler.php   ← async consumer
  Infrastructure/
    Persistence/
      ORM/ReviewOrmEntity.php
      Mapper/ReviewMapper.php
      Repository/DoctrineReviewRepository.php
    Cache/RedisRatingCache.php
    Message/ReviewAddedMessage.php
    Message/GenerateFakeReviewsMessage.php
  Presentation/
    Http/ReviewController.php

User/   (same structure)
  Domain/
    User.php, UserId.php
    Repository/UserRepositoryInterface.php
  Application/
    Command/Register/
  Infrastructure/
    Persistence/
      ORM/UserOrmEntity.php
      Mapper/UserMapper.php
      Repository/DoctrineUserRepository.php
  Presentation/
    Http/AuthController.php

Shared/
  Domain/
    ValueObject/UuidValueObject.php
    Event/DomainEventInterface.php
    AggregateRoot.php             ← base class for event recording (see note below)
  Infrastructure/
    Bus/MessengerCommandBus.php
    Bus/MessengerQueryBus.php
```

> **Note — Aggregate root domain events (demonstrative only):** `AggregateRoot` adds `recordEvent()` / `releaseEvents()` so aggregates can self-publish domain events. `Review::create()` records a `ReviewAddedEvent` this way. In this project the event bus is Symfony Messenger and the async pipeline uses `ReviewAddedMessage` directly — `releaseEvents()` is never called in production. The pattern is included purely to show the technique; it is **not required** for the assignment to function correctly.

---

## Database Schema

### `users`
```sql
id           CHAR(36) PK
email        VARCHAR(180) UNIQUE
password_hash VARCHAR(255)
name         VARCHAR(255)
created_at   DATETIME
```

### `products`
```sql
id           CHAR(36) PK
external_id  INT UNIQUE        -- from dummyjson
title        VARCHAR(255)
description  TEXT
price        DECIMAL(10,2)
category     VARCHAR(100)
thumbnail    VARCHAR(512) NULL
stock        INT
brand        VARCHAR(255) NULL
created_at   DATETIME
```

### `reviews`
```sql
id           CHAR(36) PK
product_id   CHAR(36) FK → products.id
user_id      CHAR(36) FK → users.id
rating       TINYINT(1)        -- 1-5
body         TEXT
created_at   DATETIME
UNIQUE KEY uq_product_user (product_id, user_id)
```

---

## Redis Rating Cache

Key `product:rating:{uuid}` → Hash `{ sum, count }`

- **Read**: avg = sum / count (0 if count = 0). Cache miss → calculate from DB + warm cache.
- **Write** (ReviewAddedHandler, async):
  ```
  HINCRBY product:rating:{id} sum {rating}
  HINCRBY product:rating:{id} count 1
  ```

---

## API Endpoints

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| POST | `/api/auth/register` | public | Register a new user |
| POST | `/api/auth/login` | public | Obtain JWT token |
| GET | `/api/products` | public | `?search= &page= &limit= &category= &minPrice= &maxPrice=` |
| GET | `/api/products/{id}` | public | Product details + rating from Redis |
| GET | `/api/products/{id}/reviews` | public | Reviews with pagination |
| POST | `/api/products/{id}/reviews` | JWT | Add review, `409` on duplicate |
| POST | `/api/demo/generate-reviews` | public | Dispatch GenerateFakeReviewsMessage |

---

## Docker Services

| Service | Image |
|---------|-------|
| `mysql` | mysql:8.0 |
| `redis` | redis:7-alpine |
| `rabbitmq` | rabbitmq:3-management |
| `app` | PHP 8.3-fpm (custom) |
| `nginx` | nginx:alpine |
| `consumer` | same as app, runs `messenger:consume async` |
| `frontend` | node:20 (Vite dev) |

---

## Progress

### Phase 1 — Project Scaffold & Docker
- [x] Monorepo structure (`/backend`, `/frontend`, `docker-compose.yml`, `Makefile`)
- [x] Symfony 7 skeleton installed
- [x] Docker Compose (mysql, redis, rabbitmq, app, nginx, consumer)
- [x] PHP Dockerfile + nginx.conf + consumer Dockerfile
- [x] `.env.example` with variable descriptions
- [x] Makefile targets: `make up`, `make seed`, `make import-products`

### Phase 2 — Domain Layer (pure PHP)
- [x] `Shared/Domain/ValueObject/UuidValueObject.php`
- [x] `Shared/Domain/Event/DomainEventInterface.php`
- [x] `Catalog/Domain/ProductId.php` (VO)
- [x] `Catalog/Domain/Product.php` (`public readonly`; `createdAt` injected externally)
- [x] `Catalog/Domain/Repository/ProductRepositoryInterface.php`
- [x] `Review/Domain/ReviewId.php` (VO)
- [x] `Review/Domain/Rating.php` (VO 1–5, `public readonly int $value`)
- [x] `Review/Domain/Review.php` (`public readonly`)
- [x] `Review/Domain/Exception/ReviewAlreadyExistsException.php`
- [x] `Review/Domain/Repository/ReviewRepositoryInterface.php`
- [x] `User/Domain/UserId.php` (VO)
- [x] `User/Domain/User.php` (`public readonly`, field `passwordHash`)
- [x] `User/Domain/Repository/UserRepositoryInterface.php`
- [x] `Review/Domain/Event/ReviewAddedEvent.php` (`public readonly` + `occurredOn()` for interface)

### Phase 3 — ORM Entities + Mappers
- [x] `Catalog/Infrastructure/Persistence/ORM/ProductOrmEntity.php` (constructor promotion, `public readonly`)
- [x] `Catalog/Infrastructure/Persistence/Mapper/ProductMapper.php`
- [x] `Review/Infrastructure/Persistence/ORM/ReviewOrmEntity.php` (`public readonly`)
- [x] `Review/Infrastructure/Persistence/Mapper/ReviewMapper.php`
- [x] `User/Infrastructure/Persistence/ORM/UserOrmEntity.php` (`public readonly` + `UserInterface` methods)
- [x] `User/Infrastructure/Persistence/Mapper/UserMapper.php`
- [x] Doctrine migrations (users, products, reviews + UNIQUE `uq_product_user`)

### Phase 4 — Repositories (Infrastructure)
- [x] `Catalog/Infrastructure/Persistence/Repository/DoctrineProductRepository.php` (`#[AsAlias]`, upsert: persist / DQL UPDATE)
- [x] `Review/Infrastructure/Persistence/Repository/DoctrineReviewRepository.php`
- [x] `User/Infrastructure/Persistence/Repository/DoctrineUserRepository.php`
- [x] `Review/Infrastructure/Cache/RedisRatingCache.php` (Predis `HGET`/`HINCRBY`/`hmset`, `REDIS_URL` in `services.yaml` + `.env`)
- [x] `Catalog/Infrastructure/Http/DummyJsonApiClient.php` (Symfony HttpClient)

### Phase 5 — Messaging
- [x] `Review/Infrastructure/Message/ReviewAddedMessage.php`
- [x] `Review/Infrastructure/Message/GenerateFakeReviewsMessage.php`
- [x] `Review/Application/MessageHandler/ReviewAddedHandler.php` (via `RatingCacheInterface` → Redis `HINCRBY`)
- [x] `Review/Application/MessageHandler/GenerateFakeReviewsHandler.php` (Faker + `findAllIds` for users)
- [x] Symfony Messenger: transport `async` = `%env(MESSENGER_TRANSPORT_DSN)%`, `sync://` in test; both messages routed to `async`
- [x] PHPUnit: unit + integration (`RecordingRatingCache` in `test/services.yaml`, JWT keys in `tests/fixtures/jwt/`)

### Phase 6 — Application Layer (Use Cases)
- [x] `User/Application/Command/Register/RegisterCommand.php`
- [x] `User/Application/Command/Register/RegisterCommandHandler.php`
- [x] `Catalog/Application/Command/ImportProducts/ImportProductsCommand.php`
- [x] `Catalog/Application/Command/ImportProducts/ImportProductsCommandHandler.php`
- [x] `Catalog/Application/Query/GetProducts/GetProductsQuery.php`
- [x] `Catalog/Application/Query/GetProducts/GetProductsQueryHandler.php`
- [x] `Catalog/Application/Query/GetProducts/GetProductsResult.php` + `ProductSummary.php`
- [x] `Catalog/Application/Query/GetProduct/GetProductQuery.php`
- [x] `Catalog/Application/Query/GetProduct/GetProductQueryHandler.php`
- [x] `Catalog/Application/Query/GetProduct/GetProductResult.php`
- [x] `Review/Application/Command/AddReview/AddReviewCommand.php`
- [x] `Review/Application/Command/AddReview/AddReviewCommandHandler.php`
- [x] `Review/Application/Query/GetProductReviews/GetProductReviewsQuery.php`
- [x] `Review/Application/Query/GetProductReviews/GetProductReviewsQueryHandler.php`
- [x] `Review/Application/Query/GetProductReviews/GetProductReviewsResult.php` + `ReviewItem.php`
- [x] `Shared/Application/Bus/CommandBusInterface.php` + `QueryBusInterface.php`
- [x] `Shared/Infrastructure/Bus/MessengerCommandBus.php` + `MessengerQueryBus.php`
- [x] `Catalog/Application/Port/ProductApiClientInterface.php`
- [x] `Catalog/Domain/Exception/ProductNotFoundException.php`
- [x] `User/Domain/Exception/EmailAlreadyExistsException.php`

### Phase 7 — Presentation (REST Controllers)
- [x] `User/Presentation/Http/AuthController.php` (`/api/auth/register`, `/api/auth/login`)
- [x] `Catalog/Presentation/Http/ProductController.php`
- [x] `Review/Presentation/Http/ReviewController.php`
- [x] `Shared/Presentation/Http/DemoController.php`
- [x] Exception listener → HTTP error mapping (409, 422, 404)
- [x] Request DTOs + Symfony Validator constraints
- [x] LexikJWT config + security.yaml firewall
- [x] Symfony UserInterface adapter (`UserOrmEntity implements UserInterface`) — already in infrastructure
- [x] `Catalog/Infrastructure/Console/ImportProductsConsoleCommand.php` (`app:import-products`)

### Phase 8 — Frontend (Vite + React + Redux Toolkit)
- [ ] Vite + React + TypeScript + Redux Toolkit scaffold
- [ ] Axios client + JWT interceptor
- [ ] `authSlice` + login/register pages (demo credentials shown on login)
- [ ] `ProtectedRoute` component
- [ ] Products list page: SearchBar, CategoryFilter, PriceRangeFilter, Pagination
- [ ] Product detail page: info, reviews list, AddReviewForm (auth guard)
- [ ] Demo page: GenerateReviewsForm (count, ratingMin, ratingMax, productId?)
- [ ] React Router routes setup

### Phase 9 — Seed & Polish
- [x] `DataFixtures/AppFixtures.php`: demo user `demo@example.com` / `demo1234`
- [x] `make seed` → runs fixtures
- [x] `make import-products` → dispatches `ImportProductsCommand` via `app:import-products` console command
- [ ] `make up`, `make down`, `make migrate` targets verified
- [x] Fix `composer.json` PHP constraint: `"php": ">=8.3"` (matches Dockerfile `php:8.3-fpm`)

### Phase 10 — Documentation

#### `README.md` (project root) — required by the assignment

**Section: Quick Start**
- [ ] Prerequisites (Docker, Make)
- [ ] `git clone && make up && make seed && make import-products`
- [ ] Access URLs: API `http://localhost:8080`, Frontend `http://localhost:5173`, RabbitMQ UI `http://localhost:15672`
- [ ] Demo credentials shown explicitly

**Section: Architecture**
- [ ] Bounded contexts diagram (`Catalog`, `Review`, `User`, `Shared`)
- [ ] Layer table: Domain → Application → Infrastructure → Presentation
- [ ] Explanation of `public readonly` choice (immutability enforced at language level, not convention; keeps ORM concerns separate)
- [ ] Explanation of DQL UPDATE upsert (avoids loading entity into memory for high-frequency product import)

**Section: Design Decisions & Trade-offs**
- [ ] Why DDD + CQRS for a review system: natural aggregate boundaries (Product, Review, User are separate consistency scopes); CQRS lets query side read from Redis without touching the write model
- [ ] Why async review processing (Symfony Messenger → RabbitMQ): rating cache update is decoupled from the HTTP response; consumer can be scaled independently; resilient to Redis downtime
- [ ] Why Redis Hash `{ sum, count }` not a simple float: `HINCRBY` is atomic without a transaction; avoids full `AVG()` recalculation; handles concurrent writes safely
- [ ] Trade-off accepted: DDD/Hexagonal adds boilerplate (mappers, separate ORM entities). Justified for demonstrating scalable patterns; in a production startup MVP, lighter Symfony CRUD would be the right call
- [ ] Trade-off accepted: no GraphQL, no event sourcing — out of scope for the assignment's complexity level

**Section: Testing Strategy**
- [ ] Unit tests: pure domain logic + message handlers via `RecordingRatingCache` test double
- [ ] Integration tests: repositories against real MySQL (no Doctrine mocks); message flow via sync transport
- [ ] `make test` command

**Section: Agentic Coding**
- [ ] Note that Claude Code (Anthropic) was used to accelerate scaffolding of boilerplate layers
- [ ] All architectural decisions, layer boundaries, data models, and business logic were authored by the developer
- [ ] Agent instructions live in `CLAUDE.md` and `.github/copilot-instructions.md`

**Section: API Reference**
- [ ] Table of all endpoints with method, path, auth, request body, response shape
- [ ] `curl` examples for each endpoint (register, login, list products, get product with rating, add review, duplicate review 409, generate fake reviews)

#### Commit History Convention
- [ ] Imperative subject line, scoped: `feat(review): add AddReviewCommandHandler`
- [ ] Scope values: `catalog`, `review`, `user`, `shared`, `infra`, `frontend`, `docs`, `docker`
- [ ] Breaking architectural changes noted in body

---

## Verification Checklist
- [ ] `make up && make seed && make import-products` runs without errors
- [ ] `POST /api/auth/login` returns a JWT token
- [ ] `GET /api/products?search=phone&category=smartphones&page=2` returns correct data
- [ ] `GET /api/products/{id}` includes `averageRating` from Redis
- [ ] `POST /api/products/{id}/reviews` (auth) → 201, message dispatched to RabbitMQ
- [ ] Duplicate POST by the same user → **409 Conflict**
- [ ] Consumer processed message → Redis `HINCRBY` executed, rating updated
- [ ] Cache miss → rating calculated from DB and warmed in Redis
- [ ] `POST /api/demo/generate-reviews` → N reviews appear asynchronously
- [ ] All frontend pages render, auth guard works
- [ ] `make test` → all tests green
- [ ] README.md readable in 5 minutes and explains WHY, not just HOW
