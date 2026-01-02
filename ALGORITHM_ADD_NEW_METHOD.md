# Универсальный алгоритм добавления нового метода роутинга

## Описание
Этот алгоритм описывает шаги для добавления нового метода роутинга (например, `onVideo`, `onDocument`, `onAudio` и т.д.) в систему роутинга Telegram бота.

## Шаги реализации

### 1. Добавить новый тип роута в `RouteType.php`
**Файл:** `src/Core/Routing/RouteType.php`

**Действие:** Добавить новый case в enum `RouteType`
```php
case VIDEO; // или DOCUMENT, AUDIO и т.д.
```

**Пример:**
```php
enum RouteType
{
    case COMMAND;
    case MESSAGE;
    case PHOTO;
    case VIDEO; // новый тип
    // ...
}
```

---

### 2. Создать класс данных для роута (если нужен)
**Файл:** `src/Core/Routing/RouteData/{MethodName}Data.php` (НОВЫЙ ФАЙЛ)

**Действие:** Создать новый класс, наследующийся от `AbstractRouteData`

**Шаблон:**
```php
<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use Phptg\BotApi\Type\Update\Update;

final readonly class VideoData extends AbstractRouteData
{
    public function __construct(
        Update $update,
        public array $videos, // или другие данные
        string $botId,
    ) {
        parent::__construct($update, $botId);
    }
}
```
---

### 3. Добавить метод в `TelegramRouteBuilder.php`
**Файл:** `src/Core/Routing/TelegramRouteBuilder.php`

**Действие:** Добавить метод `on{MethodName}()` после последнего метода `on*()`

**Шаблон:**
```php
public function onVideo(callable|string|array $action, \Closure|string|null $pattern = null): void
{
    $this->route->type = RouteType::VIDEO;
    $this->route->action = $action;
    $this->route->pattern = $pattern;

    $this->register();
}
```

**Примечание:** 
- Если метод не требует pattern, можно убрать параметр `$pattern`
- Если нужны дополнительные опции (как у `onPoll`), добавить параметр с опциями

---

### 4. Добавить метод в `TelegramRouter.php`
**Файл:** `src/Core/Routing/TelegramRouter.php`

**Действие:** Добавить метод `on{MethodName}()` после последнего метода `on*()`

**Шаблон:**
```php
public function onVideo(callable|string|array $action, string $botId = '*', callable|null $pattern = null): void
{
    new TelegramRouteBuilder()
        ->forBot($botId)
        ->onVideo($action, $pattern);
}
```

**Примечание:** Порядок параметров: `$action`, `$botId`, `$pattern` (если нужен)

---

---

### 6. Добавить логику матчинга в `TelegramRoute.php`
**Файл:** `src/Core/Routing/TelegramRoute.php`

**Действие 1:** Добавить case в метод `matches()` в match-выражении

**Шаблон:**
```php
return match ($this->type) {
    RouteType::PHOTO => $this->matchesPhoto($update),
    RouteType::VIDEO => $this->matchesVideo($update), // новый case
    // ...
};
```

**Действие 2:** Добавить метод `matches{MethodName}()` после последнего метода `matches*()`

**Шаблон:**
```php
protected function matchesVideo(Update $update): ?VideoData
{
    if (is_null($update->message)) {
        return null;
    }

    if (empty($update->message->video)) {
        return null;
    }

    return new VideoData($update, $update->message->video, $this->botId);
}
```

**Примечание:** 
- Адаптируйте проверки под конкретный тип данных
- Если нужна проверка pattern, добавьте логику аналогично `matchesMessage()` или `matchesCommand()`

---

### 7. Добавить PHPDoc в Facade
**Файл:** `src/Facades/TelegramRouter.php`

**Действие:** Добавить PHPDoc метод в комментарии `@method`

**Шаблон:**
```php
/**
 * @method static void onVideo(array|string|callable $action, string $botId = '*', ?callable $pattern = null)
 * @see TelegramRouterService
 */
```

## 7.1 доработать
\HybridGram\Core\UpdateHelper::mapToRouteType

---

### 8. Добавить тесты

#### 8.1 Unit тест
**Файл:** `tests/Unit/TelegramRouterTest.php`

**Действие:** Добавить тест для проверки регистрации роута в группе

**Шаблон:**
```php
it('can use onVideo in group', function () {
    TelegramRouter::forBot('main_bot')
        ->group(['from_state' => 'state'], function (TelegramRouteBuilder $builder) {
            $builder->onVideo(function (HybridGram\Core\Routing\RouteData\VideoData $videoData) {
                logger()->info("video received", ['videos' => $videoData->videos]);
            });
        });

    expect(true)->toBeTrue();
});
```

#### 8.2 Feature тест
**Файл:** `tests/Feature/TelegramRouterTest.php`

**Действие:** Добавить тест для проверки роутинга

**Шаблон:**
```php
test('onVideo routes correctly', function () {
    TelegramRouter::forBot('main_bot')
        ->onVideo(function (HybridGram\Core\Routing\RouteData\VideoData $videoData) {
            return 'video_handler';
        });

    $user = new User(1, false, 'TestUser');
    $chat = new Chat(1, 'private');
    $video = new \Phptg\BotApi\Type\Video(/* параметры */);
    $message = new Message(
        messageId: 1,
        date: new DateTimeImmutable,
        chat: $chat,
        from: $user,
        video: $video
    );
    $update = new Update(
        updateId: 1,
        message: $message
    );

    $route = app(HybridGram\Core\Routing\TelegramRouter::class)->resolveActionsByUpdate($update, 'main_bot');
    expect($route)->toBeInstanceOf(TelegramRoute::class);
    expect($route->type)->toBe(RouteType::VIDEO);
});
```

**Примечание:** Адаптируйте создание объектов под конкретный тип данных из Telegram API

---

## Чеклист

- [ ] 1. Добавлен case в `RouteType.php`
- [ ] 2. Создан класс данных `{MethodName}Data.php` (если нужен)
- [ ] 3. Добавлен метод `on{MethodName}()` в `TelegramRouteBuilder.php`
- [ ] 4. Добавлен метод `on{MethodName}()` в `TelegramRouter.php`
- [ ] 5. Добавлен метод `on{MethodName}()` в `RouteGroup.php`
- [ ] 6. Добавлен case в `matches()` в `TelegramRoute.php`
- [ ] 7. Добавлен метод `matches{MethodName}()` в `TelegramRoute.php`
- [ ] 8. Добавлен PHPDoc в `TelegramRouter.php` (Facade)
- [ ] 9. Добавлен unit тест в `tests/Unit/TelegramRouterTest.php`
- [ ] 10. Добавлен feature тест в `tests/Feature/TelegramRouterTest.php`

---

## Примеры использования алгоритма

### Пример 1: `onPhoto` (уже реализован)
- RouteType: `PHOTO`
- Data класс: `PhotoData` с массивом `$photos`
- Pattern: опциональный `Closure`
- Матчинг: проверка наличия `$update->message->photo`

### Пример 2: `onPoll` (уже реализован)
- RouteType: `POLL`
- Data класс: `PollData` с объектом `$poll`
- Опции: `PollOptions` для фильтрации
- Матчинг: проверка наличия `$update->message->poll` + проверка опций

---

## Важные замечания

1. **Порядок параметров:** Следуйте существующему порядку параметров в методах
2. **Типы данных:** Используйте правильные типы из `Phptg\BotApi\Type\*`
3. **Проверки:** Всегда проверяйте наличие данных перед созданием объектов данных
4. **Тесты:** Минимально необходимые тесты - только happy path
5. **Согласованность:** Следуйте паттернам существующих методов (`onCommand`, `onMessage`, `onPhoto`)

---

## Быстрая справка по файлам

| Файл | Что делать |
|------|-----------|
| `RouteType.php` | Добавить case |
| `RouteData/{Method}Data.php` | Создать класс данных |
| `TelegramRouteBuilder.php` | Добавить `on{Method}()` |
| `TelegramRouter.php` | Добавить `on{Method}()` |
| `RouteGroup.php` | Добавить `on{Method}()` |
| `TelegramRoute.php` | Добавить case и `matches{Method}()` |
| `Facades/TelegramRouter.php` | Добавить PHPDoc |
| `tests/Unit/TelegramRouterTest.php` | Добавить unit тест |
| `tests/Feature/TelegramRouterTest.php` | Добавить feature тест |

