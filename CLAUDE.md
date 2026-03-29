# Claude Code — Agent Instructions

This file is loaded automatically on every prompt. Follow these rules without exception.

---

## Project Context

Product Review system (Amazon/Alza-like) — a CloudTalk PHP assignment.
Stack: Symfony 7.4 · Doctrine ORM 3.6 · MySQL 8 · Redis 7 · RabbitMQ 3 · PHP 8.5.
See `PLAN.md` for the full implementation roadmap and progress tracker.

---

## Decision-Making Priority Order

When two rules conflict, higher number wins:

1. Assignment must be **deliverable and runnable** (`make up && make seed && make import-products` works end-to-end).
2. Architecture must match **PLAN.md** — no unilateral layer/pattern changes.
3. Code must be **complete and correct** within a phase before moving to the next.
4. Documentation and README must explain **WHY**, not just what.

---

## Architecture Rules (non-negotiable)

### Layer boundaries
- **Domain layer** (`*/Domain/`) — pure PHP only. Zero imports from Symfony, Doctrine, or any infrastructure library. No exceptions.
- **Application layer** (`*/Application/`) — use cases, command/query handlers, message handlers, port interfaces. No HTTP, no ORM, no Predis.
- **Infrastructure layer** (`*/Infrastructure/`) — Doctrine entities, mappers, repository implementations, Redis, HTTP clients. Never expose ORM entities outside this layer.
- **Presentation layer** (`*/Presentation/`) — controllers, request DTOs, exception listeners. Never contain business logic.

### Domain model
- All domain aggregates (`Product`, `Review`, `User`) and value objects: **`public readonly` properties only**. No getters, no setters, no mutation methods.
- `DomainEventInterface::occurredOn()` is the only method permitted on domain event classes.
- Value objects extend `UuidValueObject` for all UUIDs.

### ORM / Persistence
- ORM entities (`*OrmEntity`) live only in `Infrastructure/Persistence/ORM/`. They are **never returned** from repositories or passed to application/domain code.
- Repositories accept and return **Domain objects**. The mapper (`*Mapper`) handles conversion.
- **Database updates via DQL UPDATE** — never load an existing ORM entity, mutate fields, and flush. This applies to products and users.
- Doctrine mappings use PHP attributes only (no XML, no YAML).

### Repositories
- Repository interfaces live in `Domain/Repository/`. Implementations live in `Infrastructure/Persistence/Repository/`.
- Use `#[AsAlias]` on infrastructure implementations to auto-bind interfaces without explicit `services.yaml` entries.

### Messaging
- `ReviewAddedMessage` and `GenerateFakeReviewsMessage` route to the `async` transport (RabbitMQ in prod).
- In `when@test`: transport `async` is overridden to `sync://`. Never mock the message bus itself in tests.
- Message classes are `readonly`.

### Caching
- Rating cache key format: `product:rating:{uuid}` → Redis Hash with fields `sum` and `count`.
- Cache updates: `HINCRBY sum {rating}` + `HINCRBY count 1` (atomic increments, no recalculation).
- Cache miss in read path: calculate from DB (`SUM`/`COUNT` query), then warm the cache.
- `RatingCacheInterface` is in `Application/Port/` (hexagonal port). Redis implementation is in `Infrastructure/Cache/`.

---

## Testing Rules

- **Integration tests hit a real database.** Never mock Doctrine repositories in integration tests.
- **Unit tests** use test doubles (`RecordingRatingCache`) wired via `when@test` in `services.yaml`, not PHPUnit mocks of infrastructure.
- Test environment messenger transport is `sync://` — messages execute synchronously in tests.
- JWT keys for tests are generated on-the-fly in `tests/bootstrap.php` (RSA 2048, written to system temp). No key files are committed to the repo.
- New handler or use case = new unit test. New repository method = new integration test.
- **Always declare test cases with `#[Test]`** (PHPUnit 11 attribute style). Method names use **snake_case without a `test` prefix** — e.g. `saves_review_and_dispatches_message()`, not `testSavesReviewAndDispatchesMessage()`. `#[DataProvider]` method names follow the same snake_case convention.

---

## Symfony Conventions

- PHP version constraint in `composer.json` must match Dockerfile: `"php": ">=8.5"`.
- Service autowiring is on by default. Add explicit `services.yaml` entries only when autowiring is insufficient.
- `when@test` blocks in config override prod behaviour — use them for all test-specific bindings.
- Security firewall JWT configuration goes in `config/packages/security.yaml`, not in controllers.
- Exception-to-HTTP mapping goes in a single `ExceptionListener` — not scattered across controllers.

---

## CQRS Conventions

- **Commands**: carry intent, mutate state, handlers return `void`.
- **Queries**: read-only, handlers return a `*Result` DTO, never an ORM entity or domain object directly.
- `CommandBus` and `QueryBus` are in `Shared/Infrastructure/Bus/` and implement interfaces in `Shared/Application/`.
- Handlers are `final` classes.

---

## Code Style

- No docblocks on methods whose signature is self-documenting.
- No `// removed` comments, no backwards-compat stubs for deleted code.
- No defensive null checks on values the constructor already validated.
- `Rating` VO validates 1–5 in its constructor — callers do not re-validate.

---

## Delivery Requirements (from the assignment)

- `make up` → all Docker services healthy.
- `make seed` → demo user created (`demo@example.com` / `demo1234`) with fixtures.
- `make import-products` → products imported from dummyjson, Redis warmed.
- All API endpoints listed in PLAN.md must return correct HTTP status codes.
- `POST /api/products/{id}/reviews` duplicate → **409 Conflict**.
- README.md must cover: setup, architecture rationale, trade-offs, agentic coding note, curl examples.
- Commit messages: imperative, scoped (`feat(review): add AddReviewCommandHandler`).
