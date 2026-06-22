# Руководство по развёртыванию (DEPLOY)

Данное руководство предназначено для системных администраторов Комитета по чрезвычайным ситуациям. Приложение разработано на **Laravel 11+** и **React (Inertia.js)**, оптимизировано для работы как на современных VPS, так и на традиционном Shared-хостинге (виртуальный хостинг).

---

## 1. Системные требования

- **PHP**: 8.4
- **СУБД**: MySQL 8.0+ или MariaDB 10.5+
- **Веб-сервер**: Nginx или Apache
- **Дополнительные утилиты**: `composer`, `npm` (только для сборки ассетов, если сборка происходит на сервере, что не рекомендуется).

> **Примечание:** Приложение НЕ требует Redis. Кеширование и сессии настроены на использование базы данных и файловой системы (требование D-10).

---

## 2. Локальная разработка (Docker)

Для разработчиков подготовлена среда на базе Laravel Sail:

1. Установите зависимости (если у вас локально есть PHP и Composer):
   ```bash
   composer install
   ```
2. Запустите контейнеры:
   ```bash
   ./vendor/bin/sail up -d
   ```
3. Выполните миграции и сборку фронтенда:
   ```bash
   ./vendor/bin/sail artisan migrate --seed
   ./vendor/bin/sail npm install
   ./vendor/bin/sail npm run dev
   ```

---

## 3. Деплой на Shared-хостинг

Архитектура Laravel предполагает, что корневая папка веб-сервера (Document Root) указывает на папку `public/`. На виртуальном хостинге это часто папка `public_html`.

### Шаг 3.1: Подготовка файлов (на CI/CD или локальной машине)
Никогда не выполняйте `npm run build` на слабом сервере. Собирайте проект перед отправкой:
```bash
composer install --optimize-autoloader --no-dev
npm ci
npm run build
```

### Шаг 3.2: Загрузка файлов
Загрузите все файлы проекта на хостинг. Структура должна выглядеть так (если хостинг использует `public_html`):

**Вариант А (Если есть доступ к SSH и симлинкам):**
1. Загрузите файлы в папку `~/khf-app` (выше `public_html`).
2. Удалите стандартную `public_html`:
   ```bash
   rm -rf ~/public_html
   ```
3. Создайте симлинк:
   ```bash
   ln -s ~/khf-app/public ~/public_html
   ```

**Вариант Б (Если симлинки запрещены):**
Поместите всё содержимое папки `public/` в вашу папку `public_html`, а остальные папки Laravel (`app`, `bootstrap`, `resources` и т.д.) на один уровень выше `public_html`.
Затем в `public_html/index.php` измените пути:
```php
require __DIR__.'/../khf-app/vendor/autoload.php';
$app = require_once __DIR__.'/../khf-app/bootstrap/app.php';
```

### Шаг 3.3: Настройка окружения (`.env`)
Скопируйте `.env.example` в `.env` и настройте параметры:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://khf.tj

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=khf_db
DB_USERNAME=user
DB_PASSWORD=secret

CACHE_STORE=file
SESSION_DRIVER=database
QUEUE_CONNECTION=database
```

Выполните генерацию ключа:
```bash
php artisan key:generate
```

### Шаг 3.4: Настройка Cron (Обязательно!)
Для работы оповещений, очистки логов и фоновых задач необходимо добавить Laravel Scheduler в cron (в панели управления хостингом — cPanel/ISPManager):
```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

### Шаг 3.5: Миграции и Кеширование
Настройте базу данных и закешируйте конфигурации для производительности:
```bash
php artisan migrate --force
php artisan optimize
php artisan view:cache
php artisan storage:link
```

---

## 4. Режим высокой нагрузки (Graceful Degradation)

Во время чрезвычайных ситуаций сайт может испытывать пиковые нагрузки. Для сохранения работоспособности экстренных оповещений администратор может перевести систему в режим защиты:

**Включение:**
```bash
php artisan system:high-load on
```
*Что произойдёт:*
- Будет полностью отключен полнотекстовый поиск (пользователи увидят страницу 503 с локализованным уведомлением).
- Из архива инцидентов и лент новостей пропадут все "завершенные" и архивные записи. Сайт будет отдавать только активные ЧС.

**Отключение:**
```bash
php artisan system:high-load off
```

---

## 5. Создание учетной записи Администратора

После развёртывания создайте первого пользователя для доступа к CMS (`/admin`):
```bash
php artisan tinker
```
Внутри консоли выполните:
```php
App\Models\User::create([
    'name' => 'Admin',
    'email' => 'admin@khf.tj',
    'password' => bcrypt('StrongPassword123!'),
    'role' => 'admin'
]);
```

---

## 6. Резервное копирование

Рекомендуется настроить ежедневный бэкап базы данных и директории `storage/app/public/` (где хранятся загруженные медиафайлы). Храните бэкапы не менее 30 дней в соответствии с политикой безопасности.
