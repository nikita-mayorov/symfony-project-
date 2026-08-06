# Project State

## Project

| Параметр | Значение |
|----------|----------|
| Название | Symfony Task Manager |
| Фреймворк | Symfony 8.1 (PHP 8.5 в Docker) |
| База данных | PostgreSQL 16 (Alpine) |
| Сервер | FrankenPHP + Caddy |
| Оркестрация | Docker Compose |
| Пакетный менеджер | Composer |
| Code style | PHP-CS-Fixer (PSR-12 + Symfony rulesets) |
| Статический анализ | PHPStan level 6 |
| Тесты | PHPUnit 13 + DAMA Doctrine Test Bundle |
| Текущая цель | Завершить Task Manager до состояния production-ready demo |

## Completed

| Этап | Статус | Коммит |
|------|--------|--------|
| Skeleton проекта (Symfony + Docker + PostgreSQL + FrankenPHP) | Done | — |
| Foundation: security-bundle, twig, validator, serializer, form, phpunit, cs-fixer, phpstan | Done | `chore: add project dependencies, CS fixer, phpstan, phpunit` |
| User entity (id, email, password, roles, timestamps) | Done | `feat(auth): implement user entity, registration, and login` |
| `UserRepositoryInterface` + `UserRepository` | Done | — |
| `RegistrationType` (form, CSRF, RepeatedType для пароля) | Done | — |
| `SecurityController` (login, register, logout) | Done | — |
| `DashboardController` (заглушка `/`) | Done | — |
| `config/packages/security.yaml` (entity provider, form_login, access_control) | Done | — |
| Twig templates: `base.html.twig`, `login`, `register`, `dashboard` | Done | — |
| Validation: `#[UniqueEntity]` для email при регистрации | Done | — |
| Migration `Version20260806161124` — таблица `user` | Done | — |
| `doctrine:migrations:migrate` — применена | Done | — |
| Все проверки пройдены: php-cs-fixer 0 changes, phpstan 0 errors, lint:container OK, schema valid | Done | — |

## Current Phase

**Phase 2 — Authentication** завершена.

Последнее действие: исправлена обработка дубликата email через `#[UniqueEntity]` (вместо HTTP 500 — ошибка в форме).

Проверки, пройденные после последнего изменения:
- `vendor/bin/php-cs-fixer fix --dry-run` → 0 изменений
- `vendor/bin/phpstan analyse src/` → 0 ошибок
- `php bin/console lint:container` → OK
- `php bin/console doctrine:schema:validate` → в синхронизации
- `/register` → 200, `/login` → 200, `/` → 302 → `/login`
- Дубликат email → 422 + сообщение в форме «This email is already registered.»

## Pending Tasks

### Следующая фаза

**Phase 3 — Task Entity + Web CRUD**
- `TaskStatus` / `TaskPriority` backed enums
- `Task` entity (unidirectional ManyToOne → User)
- `TaskRepositoryInterface` + `TaskRepository`
- `TaskType` (form, mapped to Task entity)
- `TaskService` (create, update, delete, list)
- `TaskInput` / `TaskOutput` DTOs
- `TaskController` (index, new, show, edit, delete)
- Twig templates: `task/index`, `task/new`, `task/edit`, `task/show`
- Обновление `DashboardController` с реальными данными
- Migration для таблицы `task`
- Функциональные тесты (Phase 5)

### Инфраструктура

| Задача | Статус |
|--------|--------|
| Cloudflare Tunnel для демонстрации | Настроен. Скрипт: `/home/nikita/cloudflare-tunnel.sh` |
| GitHub repo (init, push) | Не сделано |
| `.env.local` с реальными секретами | Не сделано |

### Полный roadmap

| Phase | Содержание |
|-------|-----------|
| 1 | Foundation (done) |
| 2 | Authentication (done) |
| 3 | Task CRUD (Web) |
| 4 | REST API |
| 5 | Functional tests |
| 6 | Polish |

## Development Rules

1. **Изменения только с одобрения.** Перед написанием кода: описать что, почему, список файлов. Дождаться подтверждения.
2. **Одна фаза — один коммит.** После завершения фазы: проверить php-cs-fixer, phpstan, lint:container, сделать commit в формате `type(scope): description`.
3. **Не выходить за рамки фазы.** Не писать код, относящийся к будущим фазам.
4. **Следовать AGENTS.md.** KISS, YAGNI, PSR-12, Symfony Best Practices.
5. **Архитектура:** Controller → Form → Service → Repository → Entity.
6. **CSRF на всех POST/PUT/DELETE** (автоматически через Symfony forms).
7. **`#[IsGranted]` на контроллерах** (не `$this->isGranted()` в методах).
8. **`declare(strict_types=1)` в каждом файле.**

## Important Commands

```bash
# Docker
cd /home/nikita/symfony-project
docker compose up -d                        # запуск
docker compose ps                           # статус контейнеров
docker compose exec php bash                # shell в PHP-контейнере

# Composer (внутри контейнера)
docker compose exec php composer require ...   # добавить пакет
docker compose exec php composer install       # установить зависимости

# Проверки
docker compose exec php vendor/bin/php-cs-fixer fix --dry-run --diff
docker compose exec php vendor/bin/phpstan analyse src/ --no-progress
docker compose exec php php bin/console lint:container
docker compose exec php php bin/console doctrine:schema:validate

# Тесты (пока нет)
docker compose exec php php bin/phpunit

# Doctrine
docker compose exec php php bin/console make:migration
docker compose exec php php bin/console doctrine:migrations:migrate
docker compose exec php php bin/console dbal:run-sql "SELECT * FROM \"user\""

# Демонстрация (Cloudflare Tunnel)
/home/nikita/cloudflare-tunnel.sh           # запуск
pkill -f cloudflared                        # остановка

# Git (из директории проекта)
cd /home/nikita/symfony-project
git add .
git commit -m "type(scope): description"
```
