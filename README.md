publish assets
```bash
php artisan vendor:publish --provider="HybridGram\Providers\TelegramServiceProvider"
```

- **Polling режим (dev hot-reload)**

Если вы используете polling, можно включить авто‑перезапуск при изменении кода (удобно в дебаге — не нужно вручную перезапускать команду).

```bash
# обычный polling
php artisan hybridgram:polling

# polling c hot-reload (dev only)
php artisan hybridgram:polling --hot-reload

# выбор бота + настройка watch путей
php artisan hybridgram:polling main --hot-reload --watch=app,routes,config,src --watch-interval=1
```

- [x] Кеширование и очистка роутов через php artisan optimize 
- [x] Для контроллера реализовать принуждение к работе через json ответы
- [ ] Добавить сохранение в базу при полинге
- [ ] Публикация миграций
- [x] Реализовать базовую работу через вебхук
- [ ] мидлварь для авторизации по ключу и сикреттокену
- [x] Удобный интерфейс для отправки асинхронных сообщений с поддержкой очередей
- [x] Система отслеживания количества запросов в телеграм в минуту через кеш с приоритизацией
- [ ] При установке вебхука командой парсить роуты и предлагать поставить только необходимый набор экшеноов на прослушивание
- [ ] Проверить работу мидлварей в клиентском коде и создание своих
- [ ] Протестировать асинхронную отправку в телеграм
- [ ] Протестировать асинхронное получение данных от телеграма
- [ ] Написать тесты
- [ ] Протестировать LoggingTelegramRouteMiddleware::class
- [ ] Метод автоматически отвечающий телеграму $this->botApi->answerCallbackQuery($data->query->id);
- [ ] Скопировать логику из пакета https://github.com/mollsoft/laravel-telegram-bot через view чтобы можно было формировать ответы
- [ ] Вебморда которая будет эмулитровать телеграм для тетсировая и сразу тест кейсы можно будет писать еще
- [ ] Удобный инстрмент (скорее всего команда по настроке бота, или в сервис провайдере описание). Установка имени команд программно на разных языках.
- [ ] Миддлварь для устаовки языка на основе пользовательской локали или модели зера по заданому в конфиге полю
- [x] Мидлварь для авторизации юзера по указанной в конфиге модели или по гарду
- [ ] Доработаь мидлварь с логирование
- [ ] рисовалку стейтов и транзишенов на основе роута
- [ ] стаб для роутинга пример (протестить)
- [ ] событие вызывать что стейт переключисля чтобы в клиентском коде можно было завязаться и делать какие-то действия нарпимер удалять клавиатуру
- [ ] \HybridGram\Http\Middlewares\SetStateTelegramRouteMiddleware должен поддерживать еще постустановку стейта
- [ ] Атоматическоге экранирование точки при отправке запросыо с етстовм в телеграм

## Outgoing Message Sending

Пакет поддерживает производительную отправку сообщений с автоматическим управлением rate limiting и приоритизацией.

### Настройка

В файле конфигурации `config/hybridgram.php`:

```php
'sending' => [
    // Включить отправку через очереди
    'queue_enabled' => env('TELEGRAM_QUEUE_ENABLED', false),
    
    // Лимит запросов в минуту на бота (по умолчанию 1800 ≈ 30/сек)
    'rate_limit_per_minute' => env('TELEGRAM_RATE_LIMIT_PER_MINUTE', 1800),
    
    // Резерв слотов для HIGH приоритета (ответы на входящие апдейты)
    'reserve_high_per_minute' => env('TELEGRAM_RESERVE_HIGH_PER_MINUTE', 300),
    
    // Максимальное время ожидания в sync режиме (мс)
    // Note: rate limiting is applied only in queue mode. This option is used by the worker job sender.
    'sync_max_wait_ms' => env('TELEGRAM_SYNC_MAX_WAIT_MS', 2000),
    
    // Имена очередей для разных приоритетов
    'queues' => [
        'high' => env('TELEGRAM_QUEUE_HIGH', 'telegram-high'),
        'low' => env('TELEGRAM_QUEUE_LOW', 'telegram-low'),
    ],
],
```

### Режимы работы

#### Sync режим (queue_enabled = false)

Все запросы отправляются синхронно без rate limiting.

#### Queue режим (queue_enabled = true)

Запросы ставятся в очереди Laravel с приоритетами:
- HIGH — ответы на входящие апдейты (обрабатываются первыми)
- LOW — рассылки (заполняют свободные слоты)

**Запуск воркеров:**

```bash
# Обработка всех очередей
php artisan queue:work --queue=telegram-high,telegram-low

# Или отдельно по приоритетам
php artisan queue:work --queue=telegram-high
php artisan queue:work --queue=telegram-low
```

### Использование

#### В route handlers

```php
use HybridGram\Telegram\TelegramBotApi;
use HybridGram\Telegram\Priority;

TelegramRouter::onCommand('/start', function(CommandData $data) {
    $telegram = app(TelegramBotApi::class);
    
    // Автоматически HIGH приоритет для ответов на входящие
    $telegram->sendMessage($data->chatId, 'Hello!');
});

// Или с явным указанием приоритета
TelegramRouter::onMessage(function(MessageData $data) {
    $telegram = app(TelegramBotApi::class);
    
    // Рассылка с LOW приоритетом
    $telegram->withPriority(Priority::LOW)
        ->sendMessage($data->chatId, 'Newsletter message');
});
```

#### Прямое использование

```php
use HybridGram\Telegram\TelegramBotApi;
use HybridGram\Telegram\Priority;

$telegram = app(TelegramBotApi::class);

// Обычная отправка (приоритет из контекста или DEFAULT)
$telegram->sendMessage($chatId, 'Message');

// Явный приоритет
$telegram->withPriority(Priority::LOW)->sendMessage($chatId, 'Low priority');

// Использование call() напрямую (всегда через dispatcher)
use Phptg\BotApi\Method\SendMessage;
$telegram->call(new SendMessage($chatId, 'Text'));
```

### Приоритеты

- **HIGH**: Приоритет по умолчанию (включая ответы на входящие апдейты)
- **LOW**: Рассылки и фоновые задачи

Низкоприоритетные запросы не могут использовать зарезервированные слоты (`reserve_high_per_minute`), гарантируя что ответы на входящие всегда обрабатываются.

### Rate Limiting

- Лимит считается **per bot** (отдельно для каждого бота)
- Используется скользящее окно через кеш (60 секунд)
- В **queue** режиме лимит применяется на воркерах: job будет `release()`-иться обратно в очередь до момента, когда появится слот. Воркеры не блокируются `sleep()`-ом.

### Важные замечания

1. **InputFile с resource**: Методы с `InputFile(resource)` не поддерживаются в queue режиме. Используйте sync режим или конвертируйте файл в путь.

2. **Служебные методы**: Методы типа `getUpdates`, `setWebhook`, `getMe` всегда выполняются синхронно без rate limiting (они не проходят через dispatcher).

3. **Приоритет по умолчанию**: В route handlers автоматически используется HIGH приоритет. Для рассылок явно указывайте LOW.