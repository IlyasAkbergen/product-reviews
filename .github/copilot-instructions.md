# GitHub Copilot — Workspace Instructions

Applied to every suggestion in this repository. Read before generating code.

---

## Project

Product Review system — Symfony 7.4 · PHP 8.5 · Doctrine ORM · MySQL 8 · Redis 7 · RabbitMQ 3.
Bounded contexts: `Catalog`, `Review`, `User`, `Shared`.
Architecture: Clean/Hexagonal, CQRS, Domain-Driven Design.

---

## Critical Rules

### 1. Layer isolation is absolute

| Layer | Allowed imports | Forbidden imports |
|-------|----------------|-------------------|
| `*/Domain/` | PHP stdlib, `ramsey/uuid` | Symfony, Doctrine, Predis, any framework |
| `*/Application/` | Domain, Symfony Messenger contracts | Doctrine ORM, Predis, HTTP |
| `*/Infrastructure/` | Everything | Domain objects leaked outside via return types |
| `*/Presentation/` | Application (commands/queries), Symfony HTTP | Domain entities, ORM entities |

### 2. `public readonly` everywhere in Domain and ORM entities

```php
// CORRECT
final class Review {
    public function __construct(
        public readonly ReviewId $id,
        public readonly ProductId $productId,
        public readonly UserId $userId,
        public readonly Rating $rating,
        public readonly string $body,
        public readonly \DateTimeImmutable $createdAt,
    ) {}
}

// WRONG — never add getters or setters to domain/ORM classes
public function getRating(): Rating { return $this->rating; }
public function setRating(Rating $r): void { $this->rating = $r; }
```

### 3. Repositories return Domain objects, not ORM entities

```php
// CORRECT — DoctrineProductRepository
public function findById(ProductId $id): ?Product
{
    $orm = $this->em->find(ProductOrmEntity::class, $id->value);
    return $orm ? $this->mapper->toDomain($orm) : null;
}

// WRONG — never expose ORM entity outside Infrastructure
public function findById(ProductId $id): ?ProductOrmEntity { ... }
```

### 4. DB updates via DQL UPDATE, not load-mutate-flush

```php
// CORRECT
$this->em->createQuery('UPDATE App\...\ProductOrmEntity p SET p.stock = :s WHERE p.id = :id')
    ->setParameters(['s' => $product->stock, 'id' => $product->id->value])
    ->execute();

// WRONG
$orm = $this->em->find(ProductOrmEntity::class, $id);
$orm->stock = 5; // ORM entities are readonly — this won't work
$this->em->flush();
```

### 5. Redis rating cache is append-only increments

```php
// CORRECT
$this->redis->hIncrBy("product:rating:{$id}", 'sum', $rating);
$this->redis->hIncrBy("product:rating:{$id}", 'count', 1);

// WRONG — never recalculate by overwriting
$this->redis->hSet("product:rating:{$id}", 'sum', $newSum);
```

### 6. CQRS — Commands return void, Queries return Result DTOs

```php
// Command handler — void
final class AddReviewCommandHandler {
    public function __invoke(AddReviewCommand $cmd): void { ... }
}

// Query handler — DTO, never a domain object or ORM entity
final class GetProductQueryHandler {
    public function __invoke(GetProductQuery $q): GetProductResult { ... }
}
```

### 7. Messages and their handlers follow Messenger conventions

```php
// Message: readonly, no logic
final readonly class ReviewAddedMessage {
    public function __construct(
        public string $productId,
        public int $rating,
    ) {}
}

// Handler: #[AsMessageHandler], final
#[AsMessageHandler]
final class ReviewAddedHandler {
    public function __invoke(ReviewAddedMessage $msg): void { ... }
}
```

### 8. Exception-to-HTTP mapping is centralised

- `ReviewAlreadyExistsException` → 409 Conflict
- Validation failure → 422 Unprocessable Entity
- `AuthenticationException` → 401
- `AccessDeniedException` → 403

All mappings live in a single `ExceptionListener` in `Shared/Presentation/`. Controllers never catch and re-throw HTTP exceptions.

### 9. Test doubles, not mocks, for infrastructure boundaries

```php
// CORRECT — RecordingRatingCache implements the port
class RecordingRatingCache implements RatingCacheInterface {
    public array $recorded = [];
    public function increment(string $productId, int $rating): void {
        $this->recorded[] = [$productId, $rating];
    }
}

// WRONG — do not mock Doctrine EntityManager in integration tests
$em = $this->createMock(EntityManagerInterface::class);
```

### 10. PHP version and Symfony version consistency

- Dockerfile: `php:8.5fpm`
- `composer.json` require: `"php": ">=8.5"`
- All Symfony packages: `7.4.*`
- Do not suggest PHP 8.4+ features or Symfony 8.x APIs.

---

## File Naming Convention

```
*Command.php          — command data object (readonly, no logic)
*CommandHandler.php   — final class, __invoke(Command): void
*Query.php            — query data object (readonly)
*QueryHandler.php     — final class, __invoke(Query): Result
*Result.php           — readonly DTO returned by query handlers
*OrmEntity.php        — Doctrine entity (Infrastructure only)
*Mapper.php           — converts OrmEntity ↔ Domain object
*RepositoryInterface  — in Domain/Repository/
Doctrine*Repository   — in Infrastructure/Persistence/Repository/
```

---

## What NOT to generate

- Getters/setters on domain classes or ORM entities.
- `#[ORM\Entity]` attributes inside `*/Domain/` directories.
- Business logic inside controllers.
- Direct `EntityManager` usage in Application or Domain layers.
- `array` return types where a typed DTO exists or should exist.
- Symfony annotations (use PHP attributes only).
- `@throws` docblocks — let the type system and the code speak.
