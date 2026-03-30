# Product Review System

A product review API built as a CloudTalk PHP assignment. Allows users to browse products imported from dummyjson.com, submit reviews, and see live average ratings cached in Redis.

**Stack:** Symfony 7.4 · Doctrine ORM 3.6 · MySQL 8 · Redis 7 · RabbitMQ 3 · PHP 8.5 · FrankenPHP · React + Vite

---

## Quick Start

**Prerequisites:** Docker, Make

```bash
git clone <repo>
cd product-review

# One-shot setup (copies .env, starts containers, runs migrations, loads fixtures, imports products)
make setup

# Or step by step:
make up                  # start all services
make migrate             # run DB migrations
make seed                # create demo user
make import-products     # pull products from dummyjson.com, warm Redis cache
```

| Service | URL |
|---|---|
| API | http://localhost:8080 |
| Frontend | http://localhost:5173 |
| RabbitMQ UI | http://localhost:15672 (guest/guest) |

**Demo credentials:** `demo@example.com` / `demo1234`

---

## API Reference

| Method | Path | Auth | Notes |
|---|---|---|---|
| POST | `/api/auth/register` | — | `{ email, password, name }` |
| POST | `/api/auth/login` | — | Returns JWT |
| GET | `/api/products` | — | `?search=&category=&minPrice=&maxPrice=&page=&limit=` |
| GET | `/api/products/{id}` | — | Includes `averageRating` from Redis |
| GET | `/api/products/{id}/reviews` | — | Paginated |
| POST | `/api/products/{id}/reviews` | JWT | `{ rating, body }` — **409** on duplicate |
| POST | `/api/demo/generate-reviews` | — | `{ count, ratingMin, ratingMax, productId? }` |

### curl examples

```bash
# Register
curl -X POST http://localhost:8080/api/auth/register \
  -H 'Content-Type: application/json' \
  -d '{"email":"user@example.com","password":"secret123","name":"Test User"}'

# Login
TOKEN=$(curl -s -X POST http://localhost:8080/api/auth/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"demo@example.com","password":"demo1234"}' | jq -r .token)

# List products
curl "http://localhost:8080/api/products?search=phone&page=1&limit=10"

# Product detail with rating
curl http://localhost:8080/api/products/{id}

# Add review
curl -X POST http://localhost:8080/api/products/{id}/reviews \
  -H "Authorization: Bearer $TOKEN" \
  -H 'Content-Type: application/json' \
  -d '{"rating":4,"body":"Great product"}'

# Duplicate review → 409
curl -X POST http://localhost:8080/api/products/{id}/reviews \
  -H "Authorization: Bearer $TOKEN" \
  -H 'Content-Type: application/json' \
  -d '{"rating":5,"body":"Again"}'

# Generate fake reviews
curl -X POST http://localhost:8080/api/demo/generate-reviews \
  -H 'Content-Type: application/json' \
  -d '{"count":20,"ratingMin":3,"ratingMax":5}'
```

---

## Architecture

### Bounded Contexts

```
Catalog/    Product import, browsing, search
Review/     Submitting and reading reviews, rating cache
User/       Registration, authentication
Shared/     Cross-cutting: buses, base value objects, domain event interface
```

### Layers (same structure in each context)

| Layer | Responsibility | Rules |
|---|---|---|
| `Domain/` | Aggregates, value objects, repository interfaces | Pure PHP — zero framework imports |
| `Application/` | Commands, queries, message handlers, port interfaces | No HTTP, no ORM |
| `Infrastructure/` | Doctrine ORM, Redis, HTTP clients, message classes | ORM entities never leave this layer |
| `Presentation/` | Controllers, request DTOs, exception listener | No business logic |

---

## Design Decisions & Trade-offs

### DDD + CQRS for a review system
Product, Review, and User have separate consistency scopes — a natural fit for bounded contexts. CQRS lets the read path (product listing, reviews) pull from Redis without touching the write model, keeping query latency independent of write throughput.

### `public readonly` everywhere
Immutability is enforced at the language level rather than by convention. Domain aggregates and ORM entities both use constructor-promoted `readonly` properties — no getters, no setters, no accidental mutation.

### DQL UPDATE for persistence
`ImportProductsCommand` runs repeatedly. Loading an existing ORM entity, mutating fields, and flushing re-hydrates the full object graph for no reason. DQL `UPDATE` writes directly to the database, avoiding unnecessary memory allocation and Doctrine unit-of-work overhead.

### Async rating cache updates via RabbitMQ
When a review is submitted, `ReviewAddedMessage` is dispatched to the `async` transport. The consumer (`messenger:consume async`) processes it and increments the Redis hash atomically with `HINCRBY`. The HTTP response is not blocked waiting for Redis. The consumer can be scaled independently, and Redis downtime does not cause review submissions to fail.

### Redis Hash `{ sum, count }` instead of a float
`HINCRBY sum {rating}` and `HINCRBY count 1` are individually atomic — no Lua script or transaction needed for concurrent writes. Average is derived at read time (`sum / count`). A stored float would require a read-modify-write cycle to update correctly under concurrency.

### FrankenPHP instead of nginx + php-fpm
FrankenPHP runs PHP in a long-lived worker process, eliminating repeated application bootstrapping per request. This simplifies the Docker setup (one image, no separate web server container) and reduces per-request overhead for read-heavy workloads.

Trade-offs: long-lived workers require more care around shared state (Symfony's `kernel.reset` handles this), and the setup is less familiar than the classic nginx + php-fpm stack.

### Boilerplate trade-off
DDD/Hexagonal adds mappers, separate ORM entities, and interface/implementation pairs. In a production MVP, lighter Symfony CRUD would be the right call. Here it demonstrates that architectural patterns are understood and can be applied deliberately.

---

## Testing

```bash
make test
```

- **Unit tests** — pure domain logic and message handlers. `RecordingRatingCache` test double replaces Redis; no PHPUnit mocks of infrastructure.
- **Integration tests** — repositories against a real MySQL database. Messenger transport is overridden to `sync://` in `when@test`, so message handlers execute synchronously without RabbitMQ.
- JWT keys for the test suite are generated on-the-fly in `tests/bootstrap.php` — no key files committed to the repo.

---

## Agentic Coding

Claude Code (Anthropic) was used to accelerate scaffolding of boilerplate layers — generating ORM entities, mapper stubs, command/query class skeletons, and test structure from architectural specs.

All architectural decisions, layer boundaries, data models, Redis strategy, and business logic were authored and reviewed by the developer. Agent instructions are codified in `CLAUDE.md` at the repo root and served as the authoritative spec the agent executed against.
