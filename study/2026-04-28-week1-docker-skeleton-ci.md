# День 1 — Docker, скелет проекту, CI

## Що ми побудували сьогодні

Запустили повний стек: PHP 8.3 + Nginx + PostgreSQL + Redis в Docker.
Написали перші HTTP-класи (`Request`, `JsonResponse`, `Router`) через TDD.
Підключили PHPStan, PHP CS Fixer, PHPUnit і GitHub Actions CI.
Ендпоінт `GET /api/v1/health` повертає `{"status":"ok"}`.

---

## Docker — навіщо і як організований

### Навіщо Docker взагалі

Без Docker кожен розробник ставить PHP, PostgreSQL, Redis окремо на свою машину. Версії відрізняються — у тебе PHP 8.2, у колеги 8.3, на сервері 8.1. Код поводиться по-різному. Docker вирішує це: всі запускають **один і той самий образ**, і поведінка скрізь однакова.

### Як організований `docker-compose.yml`

```
nginx (порт 8080) → php-fpm (порт 9000) → postgres + redis
```

- **nginx** — приймає HTTP-запити ззовні, пробрасовує PHP-файли до php-fpm через FastCGI протокол. Сам PHP не вміє нормально слухати HTTP в продакшені.
- **php** — PHP-FPM (FastCGI Process Manager). Запускає PHP-скрипти у пулі процесів. OPcache кешує скомпільований байткод — не парсить файли при кожному запиті.
- **postgres** — база даних. Запускається з `init.sql` де підключаємо розширення `earthdistance` (для геопошуку) і `pgcrypto` (для UUID).
- **redis** — кеш, черги, rate limiting. В пам'яті, дуже швидкий.
- **worker** — той самий PHP образ, але замість php-fpm запускає скрипт для обробки черг (emails, notifications).

### Чому `COPY --from=composer:2` в Dockerfile

Це multi-stage build. Замість того щоб встановлювати Composer через `apt-get` або `curl`, ми просто копіюємо готовий бінарник з офіційного образу `composer:2`. Чистіше і менше розмір фінального образу.

---

## Composer — менеджер залежностей PHP

### Що таке `composer.json`

Аналог `package.json` в Node.js. Описує:
- які пакети потрібні (`require`, `require-dev`)
- як автозавантажувати класи (`autoload`)

```json
"autoload": {
    "psr-4": {
        "App\\": "src/"
    }
}
```

Це означає: клас `App\Presentation\Http\Router` → файл `src/Presentation/Http/Router.php`. PHP знає де шукати клас за його namespace — не потрібно писати `require_once` вручну.

### `require` vs `require-dev`

- `require` — пакети потрібні в продакшені (поки нічого)
- `require-dev` — тільки для розробки: phpunit, phpstan, php-cs-fixer. На сервер не потрапляють.

### `composer.lock`

Фіксує точні версії всіх пакетів. Якщо у `composer.json` написано `"phpunit/phpunit": "^11"` — це діапазон (11.0, 11.1, ...). `composer.lock` зберігає конкретну версію (11.5.55). Завдяки lock-файлу всі розробники і CI встановлюють **однакові** версії.

---

## PHPStan — статичний аналіз

### Що це

PHPStan читає твій код **без його запуску** і знаходить помилки: неправильні типи, виклики неіснуючих методів, null pointer errors тощо. Як TypeScript, але для PHP.

### Рівень 8 з 10

Чим вищий рівень — тим суворіші перевірки. Рівень 8 вимагає, зокрема:
- всі масиви мають бути типізовані: не просто `array`, а `array<string, mixed>` або `array<int, User>`

Саме через це ми отримали 9 помилок і виправляли анотації:

```php
// ❌ PHPStan level 8 не приймає
public readonly array $data;

// ✅ Треба вказати тип ключів і значень
/** @var array<string, mixed> */
public readonly array $data;
```

### Навіщо так строго

Більшість runtime-помилок у PHP — це помилки типів. PHPStan level 8 змушує думати про типи заздалегідь. У Senior-проектах це стандарт.

---

## PHP CS Fixer — автоформатування коду

### Що це

Автоматично форматує код за стандартом PSR-12. Замість того щоб сперечатись як розставляти пробіли і дужки — запускаєш `php-cs-fixer fix` і все стає однаково.

### PSR-12

PHP Standards Recommendation — угода між PHP-спільнотою про стиль коду. Більшість фреймворків (Laravel, Symfony) і бібліотек дотримуються PSR-12.

### `--dry-run`

Перевіряє без змін (для CI). В CI ми запускаємо `--dry-run` — якщо є порушення, pipeline падає. Локально запускаємо без `--dry-run` — фіксує автоматично.

---

## Тестування — як організовані тести

### Піраміда тестів

```
      /\
     /E2E\         ← мало, повільні, дорогі
    /------\
   /Integr. \      ← середньо (репозиторії, HTTP)
  /------------\
 /    Unit      \  ← багато, швидкі, дешеві
/________________\
```

У нашому проекті:
- `tests/Unit/` — тестуємо окремі класи ізольовано
- `tests/Integration/` — тестуємо взаємодію з реальною БД/Redis
- `tests/Feature/` — тестуємо HTTP flow через Router

### Як PHPUnit знаходить тести

`phpunit.xml` описує де шукати тести і які env-змінні задати:

```xml
<testsuite name="Feature">
    <directory>tests/Feature</directory>
</testsuite>
```

PHPUnit шукає всі класи що розширюють `TestCase` і всі методи що починаються з `test`.

### TDD — Red → Green → Refactor

Ми дотримувались циклу:
1. **Red** — пишемо тест, який падає (клас ще не існує)
2. **Green** — пишемо мінімальний код щоб тест пройшов
3. **Refactor** — покращуємо код, тест повинен залишатись зеленим

Навіщо: тест написаний **до** коду описує *що* повинен робити код, а не *як*. Це змушує думати про інтерфейс класу перед реалізацією.

---

## HTTP примітиви — Request, JsonResponse

### Request

Обгортка над HTTP-запитом. Замість того щоб скрізь писати `$_SERVER['REQUEST_METHOD']` і `$_GET`, маємо один об'єкт:

```php
$request->method  // 'GET'
$request->uri     // '/api/v1/hotels'
$request->query   // ['page' => '2']
$request->body    // ['name' => 'Hotel']
```

Два конструктори:
- `Request::fromGlobals()` — для реального HTTP (читає `$_SERVER`, `php://input`)
- `Request::create('GET', '/api/v1/test')` — для тестів (не потрібен реальний HTTP)

Клас `final` — не можна наслідувати. Всі властивості `readonly` — не можна змінити після створення. Це **immutable value object**.

### JsonResponse

Обгортка над HTTP-відповіддю. Зберігає `statusCode` і `data`, але не відправляє одразу — тільки коли викликаєш `send()`. Це дозволяє тестувати відповідь без реального HTTP:

```php
$response = JsonResponse::ok(['status' => 'ok']);
self::assertSame(200, $response->statusCode); // ✅ легко тестується
```

Фабричні методи (`ok`, `created`, `notFound`) — зручний API. Не пишемо `new JsonResponse(200, [...])` — пишемо `JsonResponse::ok([...])`.

---

## Router — патерн Front Controller

### Що таке Front Controller

Всі HTTP-запити йдуть в одну точку входу — `public/index.php`. Nginx направляє туди через:

```nginx
try_files $uri $uri/ /index.php?$query_string;
```

`index.php` створює `Request`, передає в `Router`, отримує `JsonResponse`, відправляє відповідь. Жоден інший PHP-файл не доступний напряму через HTTP.

### Як працює Router

```php
$router->get('/api/v1/health', [$health, 'health']);
```

Всередині зберігає масив: `routes['GET']['/api/v1/health'] = callable`.

При `dispatch()`:
1. Шукає маршрут за методом і URI
2. Якщо знайшов — викликає handler, передає `$request`
3. Якщо URI є але інший метод — 405 Method Not Allowed
4. Якщо URI не знайдено — 404 Not Found

### Чому маршрути в `bootstrap/routes.php`

Щоб Router не знав про конкретні контролери — це відповідальність bootstrap. Router — загальний механізм, routes.php — конфігурація. У тестах ми підключаємо `routes.php` щоб протестувати повний цикл:

```php
$this->router = require __DIR__ . '/../../bootstrap/routes.php';
```

---

## GitHub Actions CI

### Що таке CI (Continuous Integration)

Кожен раз коли пушиш код — автоматично запускається набір перевірок. Якщо щось зламав — дізнаєшся одразу, а не через тиждень.

### Три джоби

```
quality → CS Fixer + PHPStan
tests   → PHPUnit (з реальними Postgres + Redis сервісами)
security → composer audit (перевірка вразливостей в залежностях)
```

Джоби запускаються паралельно — швидше.

### `shivammathur/setup-php@v2`

GitHub Actions — Ubuntu без PHP. Ця action встановлює потрібну версію PHP і розширення (pdo_pgsql, redis) за 30 секунд.

### Services в GitHub Actions

```yaml
services:
  postgres:
    image: postgres:16-alpine
```

GitHub Actions запускає реальний PostgreSQL контейнер поруч з тестами. Integration тести працюють з реальною БД — не з моками.

---

## Архітектура — куди що кладемо

```
src/
└── Presentation/        ← HTTP шар
    ├── Http/            ← інструменти (Request, Response, Router)
    │   ├── Request.php
    │   ├── JsonResponse.php
    │   └── Router.php
    └── Controller/      ← обробники конкретних ендпоінтів
        └── HealthController.php

bootstrap/
└── routes.php           ← реєстрація маршрутів

public/
└── index.php            ← єдина точка входу

tests/
├── Unit/                ← тести окремих класів
├── Integration/         ← тести з реальними сервісами
└── Feature/             ← тести HTTP flow
```

На наступних тижнях з'являться:
- `src/Domain/` — бізнес-логіка (без HTTP, без БД)
- `src/Application/` — use cases
- `src/Infrastructure/` — PDO репозиторії, Redis, JWT

---

## Підсумок дня

| Що | Навіщо |
|----|--------|
| Docker Compose | Однакове середовище для всіх |
| Composer | Управління залежностями і автозавантаження |
| PHPStan level 8 | Ловить помилки типів до runtime |
| PHP CS Fixer | Єдиний стиль коду без сперечань |
| TDD | Тест описує поведінку, код — реалізацію |
| Front Controller | Один вхід, централізована маршрутизація |
| GitHub Actions | Автоматична перевірка при кожному пуші |
