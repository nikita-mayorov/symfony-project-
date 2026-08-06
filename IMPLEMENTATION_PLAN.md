# Task Manager — Implementation Plan

## Table of Contents

1. [Current Project State](#1-current-project-state)
2. [Missing Functionality](#2-missing-functionality)
3. [Folder Structure](#3-folder-structure)
4. [Entity Design](#4-entity-design)
5. [Database Schema](#5-database-schema)
6. [Development Roadmap](#6-development-roadmap)
7. [Priority Order](#7-priority-order)
8. [Risks](#8-risks)
9. [Suggested Git Commits](#9-suggested-git-commits)

---

## 1. Current Project State

### 1.1 General

- **Framework**: Symfony 8.1 (PHP `>=8.4`, Docker image uses PHP 8.5)
- **Location**: `/home/nikita/symfony-project/`
- **Runtime**: FrankenPHP with Caddy (multi-stage Dockerfile: dev/prod)
- **Database**: PostgreSQL 16 (Alpine) via Docker Compose
- **Code style**: `.editorconfig` present (4-space indent, LF, UTF-8)

### 1.2 Installed Composer Packages

| Package | Version | Purpose |
|---------|---------|---------|
| `symfony/framework-bundle` | 8.1.* | HTTP kernel, DI container, config |
| `symfony/console` | 8.1.* | CLI commands |
| `symfony/runtime` | 8.1.* | Runtime component |
| `symfony/dotenv` | 8.1.* | Environment variable loading |
| `symfony/yaml` | 8.1.* | YAML parsing |
| `symfony/flex` | ^2 | Symfony recipe system |
| `doctrine/orm` | ^3.6 | ORM |
| `doctrine/doctrine-bundle` | ^3.3 | Doctrine integration |
| `doctrine/doctrine-migrations-bundle` | ^4.0 | Database migrations |

### 1.3 Registered Bundles (`config/bundles.php`)

- `FrameworkBundle`
- `DoctrineBundle`
- `DoctrineMigrationsBundle`

### 1.4 Infrastructure (Docker)

- **`Dockerfile`** — 167 lines, multi-stage build:
  - `frankenphp_base` — PHP extensions: `apcu`, `intl`, `opcache`, `zip`, `pdo_pgsql`
  - `frankenphp_dev` — Xdebug, non-root user, hot-reload
  - `frankenphp_prod_builder` — Composer install (no-dev)
  - `frankenphp_prod` — Minimal Debian 13 Slim, runs as `www-data`
- **`compose.yaml`** — Two services: `php` (FrankenPHP, ports 80/443) + `database` (PostgreSQL 16 Alpine)
- **`compose.override.yaml`** — Dev: bind-mount `./:/app`, excludes `var/`, exposes port 5432
- **`frankenphp/Caddyfile`** — Caddy with Mercure, Vulcain, PHP worker mode

### 1.5 Existing Configuration Files

| File | Status |
|------|--------|
| `config/packages/doctrine.yaml` | DBAL + ORM configured, PostgreSQL identity generation, attribute mapping |
| `config/packages/doctrine_migrations.yaml` | Namespace `DoctrineMigrations`, path `migrations/` |
| `config/packages/framework.yaml` | Session enabled, test env with mock_file storage |
| `config/packages/cache.yaml` | Default filesystem cache |
| `config/packages/routing.yaml` | `default_uri` from env |
| `config/routes.yaml` | Auto-imports controller attributes |
| `config/services.yaml` | Autowire + autoconfigure, scans `src/` |

### 1.6 Application Code

- `src/Entity/` — Empty (only `.gitignore`)
- `src/Controller/` — Empty (only `.gitignore`)
- `src/Repository/` — Empty (only `.gitignore`)
- `src/Kernel.php` — Standard microkernel, allows envs: `prod`, `dev`, `test`
- `migrations/` — Empty (only `.gitignore`)
- `tests/` — **Does not exist**
- `templates/` — **Does not exist**

### 1.7 Environment Variables

```
APP_ENV=dev
APP_SECRET=847f45a7455b96391f5836f914a81b00
DATABASE_URL=postgresql://app:!ChangeMe!@127.0.0.1:5432/app?serverVersion=16&charset=utf8
```

---

## 2. Missing Functionality

### 2.1 Composer Packages (Not Yet Installed)

| Package | Purpose |
|---------|---------|
| `symfony/security-bundle` | Authentication, authorization, firewalls, password hashing |
| `symfony/twig-bundle` | Twig templating engine |
| `symfony/validator` | Validation constraints |
| `symfony/serializer` | JSON serialization for REST API |
| `symfony/form` | Form handling for web CRUD |
| `symfony/phpunit-bridge` | PHPUnit integration |
| `phpunit/phpunit` | Test runner |
| `dama/doctrine-test-bundle` | Transactional test isolation |
| `friendsofphp/php-cs-fixer` | Code style enforcement (PSR-12 + Symfony rulesets) |
| `phpstan/phpstan` | Static analysis (level 6) |

### 2.2 Configuration (Not Yet Created)

- `config/packages/security.yaml` — Firewalls, password hashers, access control
- `config/packages/twig.yaml` — Twig settings + Bootstrap 5 form theme
- `config/packages/validator.yaml` — Validation settings
- `config/packages/serializer.yaml` — Serializer attributes
- `config/routes/api.yaml` — API route prefix
- `.php-cs-fixer.dist.php` — CS fixer rules
- `phpstan.neon` — Static analysis configuration
- `phpunit.xml.dist` — PHPUnit configuration
- `.env.test` — Test environment variables

### 2.3 Application Code (Nonexistent)

- No entities, repositories, controllers, form types, Twig templates, DTOs, services, event listeners, or tests.

### 2.4 Database

- No tables or migrations exist.

---

## 3. Folder Structure

Standard Symfony architecture with practical layering:

```
symfony-project/
├── bin/
│   └── console                              # (exists)
├── config/
│   ├── packages/
│   │   ├── cache.yaml                       # (exists)
│   │   ├── doctrine_migrations.yaml         # (exists)
│   │   ├── doctrine.yaml                    # (exists)
│   │   ├── framework.yaml                   # (exists)
│   │   ├── routing.yaml                     # (exists)
│   │   ├── security.yaml                    # NEW
│   │   ├── twig.yaml                        # NEW
│   │   ├── validator.yaml                   # NEW
│   │   └── serializer.yaml                  # NEW
│   ├── routes/
│   │   ├── framework.yaml                   # (exists)
│   │   └── api.yaml                         # NEW
│   ├── bundles.php                          # (exists)
│   ├── routes.yaml                          # (exists)
│   └── services.yaml                        # (exists)
├── frankenphp/                              # (exists)
├── migrations/                              # (exists — empty)
├── public/
│   └── index.php                            # (exists)
├── src/
│   ├── Application/
│   │   ├── TaskService.php                  # NEW
│   │   └── UserService.php                  # NEW
│   ├── Controller/
│   │   ├── Web/
│   │   │   ├── DashboardController.php      # NEW
│   │   │   ├── SecurityController.php       # NEW
│   │   │   └── TaskController.php           # NEW
│   │   └── Api/
│   │       └── TaskApiController.php        # NEW
│   ├── Domain/
│   │   ├── Enum/
│   │   │   ├── TaskPriority.php             # NEW
│   │   │   └── TaskStatus.php               # NEW
│   │   ├── Exception/
│   │   │   └── TaskNotFoundException.php    # NEW
│   │   └── Repository/
│   │       ├── TaskRepositoryInterface.php  # NEW
│   │       └── UserRepositoryInterface.php  # NEW
│   ├── Dto/
│   │   ├── TaskInput.php                    # NEW
│   │   └── TaskOutput.php                   # NEW
│   ├── Entity/
│   │   ├── Task.php                         # NEW
│   │   └── User.php                         # NEW
│   ├── EventListener/
│   │   └── ExceptionListener.php            # NEW
│   ├── Form/
│   │   ├── RegistrationType.php             # NEW
│   │   └── TaskType.php                     # NEW
│   ├── Repository/
│   │   ├── TaskRepository.php               # NEW
│   │   └── UserRepository.php               # NEW
│   └── Kernel.php                           # (exists)
├── templates/
│   ├── base.html.twig                       # NEW
│   ├── dashboard.html.twig                  # NEW
│   ├── security/
│   │   ├── login.html.twig                  # NEW
│   │   └── register.html.twig               # NEW
│   └── task/
│       ├── index.html.twig                  # NEW
│       ├── new.html.twig                    # NEW
│       ├── edit.html.twig                   # NEW
│       └── show.html.twig                   # NEW
├── tests/
│   ├── Functional/
│   │   ├── Controller/
│   │   │   ├── Web/
│   │   │   │   ├── SecurityControllerTest.php   # NEW
│   │   │   │   └── TaskControllerTest.php       # NEW
│   │   │   └── Api/
│   │   │       └── TaskApiControllerTest.php    # NEW
│   │   └── bootstrap.php                    # NEW
│   └── bootstrap.php                        # (if needed)
├── .editorconfig                            # (exists)
├── .env                                     # (exists)
├── .env.dev                                 # (exists)
├── .env.test                                # NEW
├── .gitignore                               # (exists)
├── .php-cs-fixer.dist.php                   # NEW
├── compose.yaml                             # (exists)
├── compose.override.yaml                    # (exists)
├── composer.json                            # (exists)
├── Dockerfile                               # (exists)
├── phpstan.neon                             # NEW
├── phpunit.xml.dist                         # NEW
└── symfony.lock                             # (exists)
```

---

## 4. Entity Design

### 4.1 User

| Property | Type | DB Column | Constraints |
|----------|------|-----------|-------------|
| `$id` | `int` | `id (INT, PK, IDENTITY)` | Auto-generated |
| `$email` | `string(180)` | `email (VARCHAR(180))` | `NOT NULL`, `UNIQUE` |
| `$password` | `string` | `password (VARCHAR(255))` | `NOT NULL` |
| `$roles` | `array` | `roles (JSON)` | `NOT NULL`, default `["ROLE_USER"]` |
| `$createdAt` | `\DateTimeImmutable` | `created_at (TIMESTAMP)` | `NOT NULL` |
| `$updatedAt` | `\DateTimeImmutable` | `updated_at (TIMESTAMP)` | `NOT NULL` |

**Implements:**
- `Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface`
- `Symfony\Component\Security\Core\User\UserInterface`

**Relations:**
- None (unidirectional preferred — tasks are queried via `TaskRepository`, not through the User entity).

**Lifecycle callbacks:**
- `#[PrePersist]` — sets `$createdAt` and `$updatedAt`
- `#[PreUpdate]` — updates `$updatedAt`

### 4.2 TaskStatus (Enum)

```php
enum TaskStatus: string
{
    case Todo = 'todo';
    case InProgress = 'in_progress';
    case Done = 'done';
}
```

### 4.3 TaskPriority (Enum)

```php
enum TaskPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
}
```

### 4.4 Task

| Property | Type | DB Column | Constraints |
|----------|------|-----------|-------------|
| `$id` | `int` | `id (INT, PK, IDENTITY)` | Auto-generated |
| `$title` | `string(255)` | `title (VARCHAR(255))` | `NOT NULL` |
| `$description` | `?string` | `description (TEXT)` | `NULLABLE` |
| `$status` | `TaskStatus` | `status (VARCHAR(20))` | `NOT NULL`, default `todo` |
| `$priority` | `TaskPriority` | `priority (VARCHAR(20))` | `NOT NULL`, default `medium` |
| `$dueDate` | `?\DateTimeImmutable` | `due_date (TIMESTAMP)` | `NULLABLE` |
| `$assignedTo` | `?User` | `assigned_to (INT)` | `NULLABLE`, FK to `user.id` |
| `$createdAt` | `\DateTimeImmutable` | `created_at (TIMESTAMP)` | `NOT NULL` |
| `$updatedAt` | `\DateTimeImmutable` | `updated_at (TIMESTAMP)` | `NOT NULL` |

**Relations:**
- `#[ORM\ManyToOne]` → `User` (unidirectional). No inverse side on User.

**Validation constraints (attributes on the entity):**
- `$title`: `#[NotBlank]`, `#[Length(max: 255)]`
- `$status`: `#[NotBlank]`
- `$priority`: `#[NotBlank]`

**Lifecycle callbacks:**
- `#[PrePersist]` — sets `$createdAt` and `$updatedAt`
- `#[PreUpdate]` — updates `$updatedAt`

---

## 5. Database Schema

### 5.1 Users Table

```sql
CREATE TABLE "user" (
    id          INT GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY,
    email       VARCHAR(180) NOT NULL,
    password    VARCHAR(255) NOT NULL,
    roles       JSON         NOT NULL DEFAULT '["ROLE_USER"]',
    created_at  TIMESTAMP    NOT NULL,
    updated_at  TIMESTAMP    NOT NULL
);
CREATE UNIQUE INDEX UNIQ_USER_EMAIL ON "user" (email);
```

### 5.2 Tasks Table

```sql
CREATE TABLE task (
    id          INT GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY,
    assigned_to INT          DEFAULT NULL,
    title       VARCHAR(255) NOT NULL,
    description TEXT         DEFAULT NULL,
    status      VARCHAR(20)  NOT NULL DEFAULT 'todo',
    priority    VARCHAR(20)  NOT NULL DEFAULT 'medium',
    due_date    TIMESTAMP    DEFAULT NULL,
    created_at  TIMESTAMP    NOT NULL,
    updated_at  TIMESTAMP    NOT NULL,
    CONSTRAINT FK_TASK_USER FOREIGN KEY (assigned_to)
        REFERENCES "user" (id) ON DELETE SET NULL
);
CREATE INDEX IDX_TASK_USER    ON task (assigned_to);
CREATE INDEX IDX_TASK_STATUS  ON task (status);
```

> **Note**: Backed enums (`TaskStatus`, `TaskPriority`) use `VARCHAR` columns, not native PostgreSQL ENUMs. Doctrine maps backed enum cases to string values. The `ON DELETE SET NULL` foreign key preserves tasks when a user account is deleted.

---

## 6. Development Roadmap

### Phase 1 — Foundation

**Step 1.1: Install required packages**

```bash
composer require \
    symfony/security-bundle \
    symfony/twig-bundle \
    symfony/validator \
    symfony/serializer \
    symfony/form \
    symfony/phpunit-bridge \
    friendsofphp/php-cs-fixer \
    phpstan/phpstan

composer require --dev \
    phpunit/phpunit \
    dama/doctrine-test-bundle
```

**Step 1.2: Create `.env.test`**

```
APP_ENV=test
APP_SECRET=test_not_used_in_production
DATABASE_URL="postgresql://app:!ChangeMe!@127.0.0.1:5432/app_test?serverVersion=16&charset=utf8"
```

**Step 1.3: Create `phpunit.xml.dist`**

- `APP_ENV=test` in `<env>`
- Test suite at `tests/`
- DAMA extension for transactional tests

**Step 1.4: Create `.php-cs-fixer.dist.php`**

```php
$finder = PhpCsFixer\Finder::create()->in(__DIR__);
return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        '@Symfony' => true,
        'declare_strict_types' => true,
    ])->setFinder($finder);
```

**Step 1.5: Create `phpstan.neon`**

```neon
parameters:
    level: 6
    paths:
        - src/
```

**Step 1.6: Configure Twig** (`config/packages/twig.yaml`)

```yaml
twig:
    file_name_pattern: '*.twig'
    form_themes: ['bootstrap_5_layout.html.twig']
when@test:
    twig:
        strict_variables: true
```

The Bootstrap 5 form theme is provided by `symfony/twig-bridge` (installed with `symfony/twig-bundle`).

**Step 1.7: Configure Validator** (`config/packages/validator.yaml`)

```yaml
framework:
    validation:
        email_validation_mode: html5
```

**Step 1.8: Configure Serializer** (`config/packages/serializer.yaml`)

```yaml
framework:
    serializer:
        enable_attributes: true
```

**Commit:** `chore: add project dependencies, CS fixer, phpstan, phpunit`

---

### Phase 2 — Authentication

**Step 2.1: Configure Security** (`config/packages/security.yaml`)

```yaml
security:
    password_hashers:
        App\Entity\User: auto

    providers:
        app_user_provider:
            entity:
                class: App\Entity\User
                property: email

    firewalls:
        main:
            lazy: true
            provider: app_user_provider
            form_login:
                login_path: app_login
                check_path: app_login
                enable_csrf: true
            logout:
                path: app_logout

    access_control:
        - { path: ^/login,    roles: PUBLIC_ACCESS }
        - { path: ^/register, roles: PUBLIC_ACCESS }
        - { path: ^/,         roles: ROLE_USER }
```

> **Note**: Built-in entity provider is used — no custom `AppUserProvider` class needed. The `access_control` at `^/` protects all routes including `/api`; individual controllers use `#[IsGranted]` as a second layer.

**Step 2.2: Create User entity** (`src/Entity/User.php`)

- `#[ORM\Entity(repositoryClass: UserRepository::class)]`
- Implements `UserInterface`, `PasswordAuthenticatedUserInterface`
- `#[ORM\HasLifecycleCallbacks]` with `#[PrePersist]` / `#[PreUpdate]`
- Getters for all properties; setter only for `$email`

**Step 2.3: Create UserRepositoryInterface** (`src/Domain/Repository/UserRepositoryInterface.php`)

```php
interface UserRepositoryInterface
{
    public function findByEmail(string $email): ?User;
    public function save(User $user): void;
}
```

**Step 2.4: Create UserRepository** (`src/Repository/UserRepository.php`)

- Extends `ServiceEntityRepository`, implements `UserRepositoryInterface`
- Method `findByEmail(string $email): ?User`

**Step 2.5: Create RegistrationType** (`src/Form/RegistrationType.php`)

- Fields: `email` (EmailType), `plainPassword` (RepeatedType, PasswordType)
- `data_class` mapped to `User` entity
- Constraint: `#[UniqueEntity(fields: ['email'])]` on the User entity

**Step 2.6: Create UserService** (`src/Application/UserService.php`)

```php
public function register(string $email, string $plainPassword): User
```

- Uses `UserRepositoryInterface` to check for existing email
- Uses `UserPasswordHasherInterface` to hash password
- Uses `EntityManagerInterface` to persist (wrapped in `UserRepositoryInterface::save`)

**Step 2.7: Create SecurityController** (`src/Controller/Web/SecurityController.php`)

| Route name | Route | Method | Action |
|-----------|-------|--------|--------|
| `app_login` | `/login` | GET | Renders login form |
| `app_login` | `/login` | POST | Handled by `form_login` firewall |
| `app_logout` | `/logout` | GET | Handled by firewall |
| `app_register` | `/register` | GET/POST | Registration form |

- `#[IsGranted]` not used here — these are public routes.
- On successful registration, redirect to `app_login`.

**Step 2.8: Create Twig templates**

- `templates/base.html.twig` — base layout with Bootstrap 5 CDN, navigation bar
- `templates/security/login.html.twig` — login form
- `templates/security/register.html.twig` — registration form

**Step 2.9: Generate and run migration**

```bash
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

**Verification before commit:**
```bash
vendor/bin/php-cs-fixer fix --dry-run --diff   # must return 0
vendor/bin/phpstan analyse                      # must return 0
php bin/console lint:container                  # must pass
```

**Commit:** `feat(auth): implement user entity, registration, and login`

---

### Phase 3 — Task Entity + Web CRUD

**Step 3.1: Create enums** (`src/Domain/Enum/`)

- `TaskStatus.php` — `todo`, `in_progress`, `done`
- `TaskPriority.php` — `low`, `medium`, `high`

**Step 3.2: Create domain exception**

- `src/Domain/Exception/TaskNotFoundException.php` — extends `\RuntimeException`

**Step 3.3: Create Repository interface** (`src/Domain/Repository/TaskRepositoryInterface.php`)

```php
interface TaskRepositoryInterface
{
    public function findByUser(User $user): array;
    public function find(int $id): ?Task;
    public function save(Task $task): void;
    public function remove(Task $task): void;
}
```

Methods follow naming convention: `find` prefix for entities.

**Step 3.4: Create Task entity** (`src/Entity/Task.php`)

- `#[ORM\Entity(repositoryClass: TaskRepository::class)]`
- `#[ORM\HasLifecycleCallbacks]`
- Backed enums stored as `VARCHAR` via `#[ORM\Column(type: 'string', enumType: TaskStatus::class)]`
- `#[ORM\ManyToOne]` to User (unidirectional)
- Validation constraints as attributes on entity properties

**Step 3.5: Create TaskRepository** (`src/Repository/TaskRepository.php`)

- Extends `ServiceEntityRepository`, implements `TaskRepositoryInterface`
- `findByUser(User $user): array` — returns tasks ordered by `createdAt DESC`

**Step 3.6: Create DTOs** (`src/Dto/`)

- `TaskInput` — `title`, `description`, `status`, `priority`, `dueDate` with validation attributes (used by API)
- `TaskOutput` — read-only, all fields, serialization groups (`task:read`)

**Step 3.7: Create TaskType form** (`src/Form/TaskType.php`)

- `data_class` mapped to `Task` entity (not DTO — simpler for web)
- Fields: `title` (TextType), `description` (TextareaType), `status` (ChoiceType), `priority` (ChoiceType), `dueDate` (DateType, not required)

**Step 3.8: Create TaskService** (`src/Application/TaskService.php`)

```php
public function list(User $user): array
public function create(Task $task, User $user): Task
public function update(Task $task): Task
public function delete(Task $task): void
```

Dependencies: `TaskRepositoryInterface`, `EntityManagerInterface`.

The `create` method assigns the current user to `$task->setAssignedTo($user)`. The `update` and `delete` methods validate that the task belongs to the current user before proceeding.

**Step 3.9: Create TaskController** (`src/Controller/Web/TaskController.php`)

| Route name | Route | Method | Action |
|-----------|-------|--------|--------|
| `app_task_index` | `/tasks` | GET | List user's tasks |
| `app_task_new` | `/tasks/new` | GET/POST | Create form |
| `app_task_show` | `/tasks/{id}` | GET | Show detail |
| `app_task_edit` | `/tasks/{id}/edit` | GET/POST | Edit form |
| `app_task_delete` | `/tasks/{id}/delete` | POST | Delete (redirects to index) |

- `#[IsGranted('ROLE_USER')]` on the class
- Injects `TaskService`

**Step 3.10: Create DashboardController** (`src/Controller/Web/DashboardController.php`)

- Route: `/` (name: `app_dashboard`)
- Displays task counts by status

**Step 3.11: Create Twig templates**

- `templates/task/index.html.twig` — table with status/priority badges
- `templates/task/new.html.twig` — form
- `templates/task/edit.html.twig` — form
- `templates/task/show.html.twig` — detail view with edit/delete buttons
- `templates/dashboard.html.twig` — dashboard with task summary

**Step 3.12: Generate and run migration**

```bash
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

**Verification:**
```bash
vendor/bin/php-cs-fixer fix --dry-run --diff
vendor/bin/phpstan analyse
php bin/console lint:container
php bin/console doctrine:schema:validate
```

**Commit:** `feat(task): implement task entity, CRUD, and web interface`

---

### Phase 4 — REST API

**Step 4.1: Create API routing** (`config/routes/api.yaml`)

```yaml
api:
    resource: ../src/Controller/Api/
    type: attribute
    prefix: /api
    defaults:
        _format: json
```

**Step 4.2: Create TaskApiController** (`src/Controller/Api/TaskApiController.php`)

| Method | Route | Status | Action |
|--------|-------|--------|--------|
| GET | `/api/tasks` | 200 | List user's tasks (paginated via query params) |
| GET | `/api/tasks/{id}` | 200 | Show single task |
| POST | `/api/tasks` | 201 | Create task (JSON body → `TaskInput` DTO) |
| PUT | `/api/tasks/{id}` | 200 | Update task (JSON body → `TaskInput` DTO) |
| DELETE | `/api/tasks/{id}` | 204 | Delete task |

- `#[IsGranted('ROLE_USER')]` on the class
- CSRF is disabled for API (stateless — no form-based CSRF tokens)
- POST/PUT: deserialize JSON into `TaskInput`, validate, delegate to `TaskService`
- Validation errors: 422 with `{"errors": {"field": "message"}}`
- Success responses serialized via serializer with `task:read` group

**Step 4.3: Configure serialization groups** on entities

- `#[Groups(['task:read'])]` on `Task` readable properties
- `#[Groups(['task:read'])]` on embedded `User` (only `id` and `email`)

**Step 4.4: Create ExceptionListener** (`src/EventListener/ExceptionListener.php`)

- Listens to `kernel.exception` only when request format is `json`
- Maps `NotFoundHttpException` → 404 JSON
- Maps `AccessDeniedException` → 403 JSON
- Maps `MethodNotAllowedHttpException` → 405 JSON
- Catches `\Throwable` → 500 JSON with generic message (no stack trace in prod)

**Verification:**
```bash
vendor/bin/php-cs-fixer fix --dry-run --diff
vendor/bin/phpstan analyse
php bin/console lint:container
php bin/console debug:router | grep '/api/'
```

**Commit:** `feat(api): implement REST API for tasks with JSON serialization`

---

### Phase 5 — Functional Tests

**Step 5.1: Create `tests/Functional/bootstrap.php`**

- Ensure `APP_ENV=test`

**Step 5.2: SecurityController tests** (`tests/Functional/Controller/Web/SecurityControllerTest.php`)

| Test | Asserts |
|------|---------|
| `testRegistrationPageLoads` | GET `/register` → 200 |
| `testRegistrationSuccess` | POST valid data → 302 redirect, user exists in DB |
| `testRegistrationValidation` | POST empty fields → 200 (form re-rendered with errors) |
| `testLoginPageLoads` | GET `/login` → 200 |
| `testLoginSuccess` | POST valid credentials → 302 redirect to dashboard |
| `testLoginWrongPassword` | POST wrong password → 302 redirect back, has error |
| `testAuthenticatedUserRedirectedFromLogin` | Already logged in, GET `/login` → 302 |

**Step 5.3: TaskController tests** (`tests/Functional/Controller/Web/TaskControllerTest.php`)

| Test | Asserts |
|------|---------|
| `testIndexRequiresAuthentication` | GET `/tasks` unauthenticated → 302 |
| `testIndexListsUserTasks` | Authenticated, tasks exist → 200, table rows present |
| `testNewTaskPageLoads` | Authenticated, GET `/tasks/new` → 200 |
| `testCreateTask` | POST valid form → 302 redirect, record in DB |
| `testCreateTaskValidation` | POST empty title → 200 with form errors |
| `testShowTask` | GET `/tasks/{id}` → 200, task data on page |
| `testShowNotFoundTask` | GET `/tasks/999` → 404 |
| `testEditTask` | POST updated form → 302 redirect, changes in DB |
| `testDeleteTask` | POST delete → 302 redirect, record removed from DB |
| `testUserCannotAccessOtherUserTask` | GET another user's task → 403 |

**Step 5.4: TaskApiController tests** (`tests/Functional/Controller/Api/TaskApiControllerTest.php`)

| Test | Asserts |
|------|---------|
| `testListTasks` | GET `/api/tasks` → 200, valid JSON array |
| `testCreateTask` | POST valid JSON → 201, JSON response with task data |
| `testCreateTaskValidation` | POST missing title → 422, `{"errors": {"title": "..."}}` |
| `testShowTask` | GET `/api/tasks/{id}` → 200, JSON |
| `testShowNotFoundTask` | GET `/api/tasks/999` → 404 |
| `testUpdateTask` | PUT valid JSON → 200, updated data |
| `testDeleteTask` | DELETE → 204, empty body |
| `testUnauthenticatedAccess` | Any `/api/*` unauthenticated → 401 |

**Verification:**
```bash
vendor/bin/phpunit                              # all tests must pass
vendor/bin/php-cs-fixer fix --dry-run --diff    # zero changes
vendor/bin/phpstan analyse                      # no errors
```

**Commit:** `test: add functional tests for auth, task CRUD, and API`

---

### Phase 6 — Polish

**Step 6.1: Error pages**

- Custom Twig templates for 404 and 500 errors (optional — Symfony provides defaults)

**Step 6.2: Navigation**

- Update `base.html.twig` with conditional navigation: logged-in users see Dashboard, Tasks, Logout; anonymous users see Login, Register

**Step 6.3: Final verification**

```bash
vendor/bin/phpunit
vendor/bin/php-cs-fixer fix
vendor/bin/phpstan analyse
php bin/console lint:container
php bin/console doctrine:schema:validate
```

**Commit:** `chore: add error pages and navigation`

---

## 7. Priority Order

### Must-Have (MVP)

| # | Feature | Rationale |
|---|---------|-----------|
| 1 | Foundation (packages, CS fixer, PHPStan, phpunit config) | AGENTS.md requires these before any commit |
| 2 | User entity + authentication | Security is foundational; blocks all authenticated routes |
| 3 | Task entity + migration | Core domain object |
| 4 | Task CRUD (Web) | Primary user interaction |
| 5 | Functional tests | AGENTS.md requires tests for every controller action |

### Should-Have

| # | Feature |
|---|---------|
| 6 | REST API |
| 7 | Dashboard with task summary |
| 8 | Error pages + navigation polish |

### Nice-to-Have (Not in Scope)

- Email verification during registration
- Password reset flow
- API token authentication (stateless)
- Task comments / file attachments
- Mercure real-time updates (infrastructure exists but not used yet)
- Pagination for API
- OpenAPI/Swagger documentation
- CI/CD pipeline

---

## 8. Risks

| Risk | Impact | Mitigation |
|------|--------|------------|
| **Symfony 8.1 immaturity** | Possible BC breaks in minor releases | Versions pinned in `composer.json`; test after updates |
| **Enum mapping in Doctrine 3.x** | Backed enums may need custom DBAL types | Use `VARCHAR` columns with `enumType` attribute option (simplest) |
| **Docker dev workflow** | Bind-mount I/O issues with `www-data` user | Already handled: `var/` excluded from bind-mount in compose.override.yaml |
| **Test database isolation** | Tests may interfere without transactions | `dama/doctrine-test-bundle` provides automatic rollback |
| **API CSRF protection** | Symfony enables CSRF by default; APIs must explicitly disable it | Add `stateless: true` or disable CSRF in API controller |
| **Mercure startup issues** | Caddyfile includes Mercure; unused but may cause startup warnings | Leave as-is — Mercure is pre-configured and ignored at runtime if unused |

---

## 9. Suggested Git Commits

All commits follow Conventional Commits: `type(scope): description`.

```
1. chore: add project dependencies, CS fixer, phpstan, phpunit
2. feat(auth): implement user entity, registration, and login
3. feat(task): implement task entity, CRUD, and web interface
4. feat(api): implement REST API for tasks with JSON serialization
5. test: add functional tests for auth, task CRUD, and API
6. chore: add error pages and navigation
```

**After each commit, verify:**
```bash
vendor/bin/php-cs-fixer fix --dry-run --diff   # zero changes
vendor/bin/phpstan analyse --no-progress        # no errors
vendor/bin/phpunit --no-configuration           # all pass (once tests exist)
php bin/console lint:container                  # valid container
php bin/console doctrine:schema:validate        # valid schema (post-migration)
```

**Branching strategy:**
- `main` — production-ready
- Feature branches: `feat/auth`, `feat/task-crud`, `feat/api`, `test/functional`
