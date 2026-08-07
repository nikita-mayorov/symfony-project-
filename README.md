# Symfony Task Manager

A production-quality task management application built with Symfony 8.1.
Designed as a portfolio project for a junior PHP/Symfony developer role.

## Features

- **User authentication** — register, login, logout with CSRF protection
- **Email verification** — 6-digit code sent via SMTP (Mailpit for development)
- **Validation** — strict email (RFC), password ≥ 8 chars, case-insensitive email
- **Task management** — create, view, edit, delete tasks with status and priority
- **Due date validation** — cannot set a past due date for new/edited tasks
- **Ownership enforcement** — users can only access their own tasks
- **Dashboard** — statistics by status (Todo, In Progress, Done)
- **REST API** — full CRUD with token-based authentication, JSON responses
- **Filtering** — filter tasks by status and priority
- **Pagination** — browse large task lists
- **Overdue highlighting** — overdue tasks marked in red
- **Functional tests** — 35 tests, 100+ assertions

## Tech Stack

| Technology | Version |
|-----------|---------|
| PHP | 8.5 |
| Symfony | 8.1 |
| PostgreSQL | 16 (Alpine) |
| FrankenPHP + Caddy | Latest |
| Docker Compose | — |
| PHPUnit | 13 |
| PHPStan | Level 6 |
| PHP-CS-Fixer | PSR-12 + Symfony |

## Quick Start

```bash
git clone <repo-url>
cd symfony-project
docker compose up -d
```

The application will be available at `https://localhost`.

Email verification uses **Mailpit** — web UI at `http://localhost:8025`.

## Run Tests

```bash
docker compose exec php php bin/phpunit tests/Functional/
```

## Code Quality

```bash
# Static analysis
docker compose exec php vendor/bin/phpstan analyse src/ --no-progress

# Code style check
docker compose exec php vendor/bin/php-cs-fixer fix --dry-run --diff

# Container check
docker compose exec php php bin/console lint:container

# Schema check
docker compose exec php php bin/console doctrine:schema:validate
```

## Project Structure

```
src/
├── Application/          # Business logic services
│   ├── EmailVerificationService.php
│   └── TaskService.php
├── Controller/
│   ├── Api/              # REST API controllers
│   │   └── TaskApiController.php
│   └── Web/              # Web controllers
│       ├── DashboardController.php
│       ├── SecurityController.php
│       ├── TaskController.php
│       └── VerificationController.php
├── Domain/
│   ├── Enum/             # Backed enums
│   ├── Exception/        # Domain exceptions
│   └── Repository/       # Repository interfaces
├── Dto/                  # API data transfer objects
├── Entity/               # Doctrine entities (User, Task)
├── Form/                 # Symfony form types
├── Repository/           # Doctrine repository implementations
└── Security/             # UserChecker, ApiTokenHandler
```

## Architecture

- **Pattern**: Controller → Form/DTO → Service → Repository → Entity
- **Relationships**: unidirectional (Task → User), no bidirectional
- **API**: `access_token` authenticator, serialization groups (`task:read`, `task:write`)
- **Tests**: DAMA Doctrine Test Bundle (transactional rollback)

## API

Authentication: `Authorization: Bearer <api_token>` (token shown on Dashboard)

| Method | URL | Status |
|--------|-----|--------|
| `GET` | `/api/tasks` | 200 |
| `GET` | `/api/tasks/{id}` | 200 |
| `POST` | `/api/tasks` | 201 |
| `PUT` | `/api/tasks/{id}` | 200 |
| `DELETE` | `/api/tasks/{id}` | 204 |

Error responses: 401 (unauthorized), 404 (not found), 422 (validation, RFC 7807).
