# Импорт товаров

Импорт товаров из `.xlsx` в базу данных через Slim + Angular.

| Слой | Технологии |
|------|-----------|
| Backend | Slim 4 + Doctrine ORM, Symfony Messenger + RabbitMQ |
| Frontend | Angular 22 (TypeScript), NgRx, Angular Material |
| Хранилище | PostgreSQL |

## Запуск

Нужны Docker и make.

```bash
make init
```

- Приложение: http://localhost:8080 (вход `admin@example.com` / `admin123`)
- Swagger: http://localhost:8080/docs/
- RabbitMQ: http://localhost:15672 (`app` / `app`)

Пример файла для импорта: `backend/tests/fixtures/import-example.xlsx`. В нём три ссылки на фото отвечают 403 (строки 24, 34, 38), импорт показывает их в отчёте как предупреждения.

## Страницы

| Страница | Описание |
|----------|----------|
| Импорт товаров | Загрузка `.xlsx`, прогресс обработки и отчёт об ошибках |
| Список товаров | Пагинация, поиск по названию, фильтр по цене |
| Карточка товара | Поля, доп. атрибуты, изображения |

## Команды

```bash
make up      # запустить
make down    # остановить
make test    # тесты
make lint    # PHPStan, PHP CS Fixer, ESLint
make e2e     # e2e-тесты (Playwright)
```

## Переменные окружения

| Переменная | Описание |
|------------|----------|
| `APP_DEBUG` | 1 — детали ошибок в ответах API |
| `APP_PORT` | порт приложения |
| `FRONTEND_PORT` | порт dev-сервера Angular |
| `DB_*` | подключение к PostgreSQL |
| `RABBITMQ_*` | учётная запись и порт веб-интерфейса RabbitMQ |
| `MESSENGER_TRANSPORT_DSN` | очередь импорта |
| `LOG_LEVEL` | уровень логов |
| `JWT_SECRET`, `JWT_TTL` | секрет и время жизни токена (секрет генерирует `make init`) |
| `ADMIN_EMAIL`, `ADMIN_PASSWORD` | администратор, которого создают сиды |
| `IMPORT_MAX_FILE_SIZE` | максимальный размер xlsx, байт |
| `IMPORT_RATE_LIMIT`, `IMPORT_RATE_INTERVAL` | лимит загрузок на пользователя |
| `IMAGE_*` | таймаут, размер и параллельность скачивания картинок |
