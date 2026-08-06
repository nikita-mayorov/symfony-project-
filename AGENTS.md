# AGENTS.md

## Project

Symfony Task Manager — a production-quality application suitable for a public GitHub portfolio.

Code quality is more important than development speed.
Readability is more important than cleverness.

---

## General Principles

When multiple solutions exist, prefer: simplicity, readability, maintainability, consistency.

Follow: KISS, YAGNI, PSR-12, Symfony Best Practices.

Avoid: overengineering, unnecessary abstractions, clever code.

Use explicit, descriptive names. Prefer plain PHP over obscure language features — the code should be understandable without documentation.

---

## Architecture

Standard Symfony flow:

```
Controller
   ↓
Form (validation, CSRF, data transformation)
   ↓
Service (business logic)
   ↓
Repository (persistence)
   ↓
Entity (data model)
```

- **Controllers**: thin — routing, delegation, response. No business logic.
- **Forms**: all user-facing data entry goes through form types.
- **Services**: all business rules live here. One service per domain concept.
- **Repositories**: database queries only. Inject interface from `src/Domain/Repository/`, implement in `src/Repository/`.

### Allowed Patterns

| Directory | Purpose |
|-----------|---------|
| `Controller/` | Web and API controllers |
| `Entity/` | Doctrine entities with attribute mapping |
| `Form/` | Symfony form types |
| `Application/` (or `Service/`) | Business logic services |
| `Repository/` | Doctrine repository implementations |
| `Domain/Repository/` | Repository interfaces (for testability) |
| `Domain/Exception/` | Domain-specific exceptions |
| `Domain/Enum/` | Backed PHP enums |
| `Dto/` | Input/output DTOs for forms and API |
| `Security/` | User providers, voters |
| `EventListener/` | Event subscribers and listeners |

### Forbidden Patterns (require explicit approval)

CQRS, Event Sourcing, full DDD (aggregates, value objects, bounded contexts), hexagonal architecture.

---

## Testing

- Every controller action must have at least one functional test.
- Tests go in `tests/Functional/`, mirroring the source namespace.
- Run `php bin/phpunit` before pushing — all tests must pass.
- Use `APP_ENV=test` with a separate database (already configured in `doctrine.yaml`).

---

## Doctrine

- Use PHP attributes for mapping (`#[ORM\Entity]`, `#[ORM\Column]`). Never XML or YAML.
- Use lifecycle callbacks for timestamps (`#[PrePersist]`, `#[PreUpdate]`).
- Prefer unidirectional relationships. Use bidirectional only when required by a feature.
- Never use eager loading (`fetch: 'EAGER'`). Write explicit queries for related data.
- All schema changes go through migrations. Run `php bin/console make:migration` after entity changes.

---

## Security

- All POST/PUT/DELETE routes must be CSRF-protected (handled automatically by Symfony forms).
- Twig auto-escapes output. Never use `|raw` on user-provided data.
- Use `#[IsGranted]` on controller classes — avoid inline `$this->isGranted()` in method bodies.
- Secrets (`APP_SECRET`, database passwords) live in `.env.local`, never committed.

---

## Code Style

- `declare(strict_types=1)` in every PHP file.
- Run `vendor/bin/php-cs-fixer fix --dry-run --diff` before committing — zero changes required.
- Use `@PSR12` and `@Symfony` rulesets.
- PHPStan level 6 minimum on `src/`. No baseline — every error must be fixed.

---

## Naming

| Element | Convention | Example |
|---------|-----------|---------|
| Controller | Singular + `Controller` | `TaskController` |
| Route name | `snake_case` | `app_task_create` |
| Service method | Verb-first | `createTask()` |
| Form type | Entity + `Type` | `TaskType` |
| Repository method | `find` for entities, `get` for scalars | `findOverdue()` |

---

## API

- All API routes under `/api`, return `application/json`.
- Use HTTP status codes consistently: 200, 201, 204, 400, 401, 403, 404, 422.
- Use Symfony Serializer with attribute groups (`task:read`, `task:write`).
- Validation errors return 422 with field-level messages.

---

## Git

- Commit format: `type(scope): description` (Conventional Commits).
- Types: `feat`, `fix`, `test`, `docs`, `chore`, `refactor`.
- One logical change per commit.
- Never commit `var/`, `vendor/`, `.env.local`, or IDE directories.

---

## Workflow

Before writing any code, the Developer must:

1. Explain what will be implemented.
2. Explain why it should be implemented.
3. List every file that will be created or modified.
4. Wait for explicit user approval.

After implementation, the Developer must:

1. Summarize completed work.
2. Explain important design decisions.
3. List all modified files.
4. Suggest a Git commit message.

Never continue to the next implementation step without user approval.

---

## Rejected Recommendations

The following suggestions from the AGENTS.md review were not adopted:

| Recommendation | Reason |
|---------------|--------|
| Service dependencies must always be interfaces | Over-engineering. Only repositories benefit from interfaces for test isolation. |
| PHPStan level 8 target for `src/Entity/` and `src/Service/` | Level 6 catches all practical bugs. Higher levels catch academic edge cases with diminishing returns for this project size. |
| Separate Environment section | Rules for secrets and `APP_ENV` values are already covered in Security and Code Style sections. |
