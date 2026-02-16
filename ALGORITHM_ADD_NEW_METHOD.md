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

### 2. Создать класс данных для роута с методом `match()`
**Файл:** `src/Core/Routing/RouteData/{MethodName}Data.php` (НОВЫЙ ФАЙЛ)

**Действие:** Создать новый класс, наследующийся от `AbstractRouteData`, который содержит:
- Конструктор с данными роута
- Статический метод `match()` с логикой матчинга

**Шаблон:**
```php
<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use HybridGram\Core\Routing\TelegramRoute;
use Phptg\BotApi\Type\Update\Update;

final readonly class VideoData extends AbstractRouteData
{
    public function __construct(
        Update $update,
        public mixed $video, // или другие данные
        string $botId,
    ) {
        parent::__construct($update, $botId);
    }

    public static function match(Update $update, TelegramRoute $route): ?self
    {
        if (is_null($update->message)) {
            return null;
        }

        if (empty($update->message->video)) {
            return null;
        }

        // Если есть pattern, проверяем его
        if ($route->pattern !== null && $route->pattern !== '*') {
            if ($route->pattern instanceof \Closure) {
                if (! call_user_func($route->pattern, $update)) {
                    return null;
                }
            }
        }

        return new self($update, $update->message->video, $route->botId);
    }
}
```

**Примечание:**
- Статический метод `match()` содержит всю логику матчинга (ранее это было в `TelegramRoute`)
- Адаптируйте проверки под конкретный тип данных
- Если нужна проверка pattern, добавьте логику аналогично `PhotoData` или `TextMessageData`

---

### 3. Добавить метод в `TelegramRouteBuilder.php`
**Файл:** `src/Core/Routing/TelegramRouteBuilder.php`

**Действие:** Добавить метод `on{MethodName}()` после последнего метода `on*()`

**Шаблон:**
```php
public function onVideo(\Closure|string|array $action, \Closure|string|null $pattern = null): void
{
    $this->route->type = RouteType::VIDEO;
    $this->route->action = $action;
    $this->pattern($pattern);

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
public function onVideo(\Closure|string|array $action, string $botId = '*', \Closure|null $pattern = null): void
{
    new TelegramRouteBuilder()
        ->forBot($botId)
        ->onVideo($action, $pattern);
}
```

**Примечание:** Порядок параметров: `$action`, `$botId`, `$pattern` (если нужен)

---

### 5. Добавить case в `TelegramRoute::matches()`
**Файл:** `src/Core/Routing/TelegramRoute.php`

**Действие:** Добавить case в метод `matches()` в match-выражении, делегируя матчинг статическому методу Data-класса

**Шаблон:**
```php
return match ($this->type) {
    RouteType::PHOTO => PhotoData::match($update, $this),
    RouteType::VIDEO => VideoData::match($update, $this), // новый case
    // ...
};
```

**Примечание:** Не забудьте добавить `use` импорт Data-класса в начало файла.

---

### 6. Добавить маппинг в `UpdateHelper::mapToRouteType()`
**Файл:** `src/Core/UpdateHelper.php`

**Действие:** Добавить определение типа апдейта для нового метода

**Для типов сообщений** (photo, video, document и т.д.) — добавить в метод `mapMessageType()`:
```php
private static function mapMessageType(?Message $message): RouteType
{
    // ...
    if ($message->video) {
        return RouteType::VIDEO;
    }
    // ...
}
```

**Для типов апдейтов верхнего уровня** (poll_answer, business_connection и т.д.) — добавить в метод `mapToRouteType()`:
```php
public static function mapToRouteType(Update $update): RouteType
{
    // ...
    UpdateTypeEnum::SOME_TYPE => RouteType::SOME_TYPE,
    // ...
}
```

**Примечание:** Порядок проверок в `mapMessageType()` важен — более специфичные типы должны проверяться раньше общих.

---

### 7. Создать PHP-атрибут для нового метода
**Файл:** `src/Core/Routing/Attributes/On{MethodName}.php` (НОВЫЙ ФАЙЛ)

**Действие:** Создать атрибут, реализующий интерфейс `TelegramRouteAttribute`. Атрибут позволяет использовать декларативный стиль роутинга через PHP-атрибуты на методах контроллеров.

**Шаблон (простой метод с pattern):**
```php
<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\Attributes;

use Attribute;
use HybridGram\Core\Routing\TelegramRouteBuilder;

#[Attribute(Attribute::TARGET_METHOD)]
final class OnVideo implements TelegramRouteAttribute
{
    public function __construct(
        public ?string $pattern = null,
    ) {}

    public function registerRoute(TelegramRouteBuilder $builder, \Closure|string|array $action): void
    {
        $builder->onVideo($action, $this->pattern);
    }
}
```

**Шаблон (метод с дополнительными опциями, как OnPoll):**
```php
#[Attribute(Attribute::TARGET_METHOD)]
final class OnPoll implements TelegramRouteAttribute
{
    public function __construct(
        public ?bool $isAnonymous = null,
        public ?PollType $pollType = null,
    ) {}

    public function registerRoute(TelegramRouteBuilder $builder, \Closure|string|array $action): void
    {
        $pollOptions = ($this->isAnonymous !== null || $this->pollType !== null)
            ? new PollOptions($this->isAnonymous, $this->pollType)
            : null;

        $builder->onPoll($action, $pollOptions);
    }
}
```

**Шаблон (простой метод без параметров):**
```php
#[Attribute(Attribute::TARGET_METHOD)]
final class OnVenue implements TelegramRouteAttribute
{
    public function registerRoute(TelegramRouteBuilder $builder, \Closure|string|array $action): void
    {
        $builder->onVenue($action);
    }
}
```

**Примечание:**
- Атрибут должен реализовывать интерфейс `TelegramRouteAttribute`
- Параметры конструктора атрибута должны соответствовать параметрам метода `on{MethodName}()` в `TelegramRouteBuilder` (кроме `$action`)
- Конфиг-атрибуты (`ForBot`, `FromUserState`, `ChatTypes` и т.д.) реализуют отдельный интерфейс `TelegramRouteConfigAttribute` и применяются на уровне класса или метода

**Пример использования атрибутов в контроллере:**
```php
#[ForBot('main')]
#[ChatTypes([ChatType::PRIVATE])]
class VideoController
{
    #[OnVideo(pattern: 'some_pattern')]
    #[FromUserState(['awaiting_video'])]
    public function handleVideo(): void {}
}
```

---

### 8. Добавить PHPDoc в Facade
**Файл:** `src/Facades/TelegramRouter.php`

**Действие:** Добавить PHPDoc метод в комментарии `@method`

**Шаблон:**
```php
/**
 * @method static void onVideo(array|string|\Closure $action, string $botId = '*', ?\Closure $pattern = null)
 * @see TelegramRouterService
 */
```

---

### 9. Добавить тесты

#### 9.1 Unit тест
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

#### 9.2 Feature тест
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
- [ ] 2. Создан класс данных `{MethodName}Data.php` со статическим методом `match()`
- [ ] 3. Добавлен метод `on{MethodName}()` в `TelegramRouteBuilder.php`
- [ ] 4. Добавлен метод `on{MethodName}()` в `TelegramRouter.php`
- [ ] 5. Добавлен case `{MethodName}Data::match($update, $this)` в `TelegramRoute::matches()`
- [ ] 6. Добавлен маппинг в `UpdateHelper::mapToRouteType()` (или `mapMessageType()`)
- [ ] 7. Создан PHP-атрибут `On{MethodName}` в `src/Core/Routing/Attributes/`
- [ ] 8. Добавлен PHPDoc в `Facades/TelegramRouter.php`
- [ ] 9. Добавлен unit тест в `tests/Unit/TelegramRouterTest.php`
- [ ] 10. Добавлен feature тест в `tests/Feature/TelegramRouterTest.php`

---

## Примеры использования алгоритма

### Пример 1: `onPhoto` (уже реализован)
- RouteType: `PHOTO`
- Data класс: `PhotoData` с массивом `$photoSizes` и статическим `match()`
- Атрибут: `OnPhoto` с опциональным `$pattern`
- Pattern: опциональный `Closure|string`
- Матчинг: проверка наличия `$update->message->photo` (в `PhotoData::match()`)

### Пример 2: `onPoll` (уже реализован)
- RouteType: `POLL`
- Data класс: `PollData` с объектом `$poll` и статическим `match()`
- Атрибут: `OnPoll` с `$isAnonymous` и `$pollType`
- Опции: `PollOptions` для фильтрации
- Матчинг: проверка наличия `$update->message->poll` + проверка опций (в `PollData::match()`)

---

## Важные замечания

1. **Порядок параметров:** Следуйте существующему порядку параметров в методах
2. **Типы данных:** Используйте правильные типы из `Phptg\BotApi\Type\*`
3. **Проверки:** Всегда проверяйте наличие данных перед созданием объектов данных
4. **Тесты:** Минимально необходимые тесты - только happy path
5. **Согласованность:** Следуйте паттернам существующих методов (`onCommand`, `onMessage`, `onPhoto`)
6. **Матчинг в Data-классах:** Вся логика матчинга находится в статическом методе `match()` Data-класса, а НЕ в `TelegramRoute`
7. **Атрибуты:** Каждый новый метод роутинга должен иметь соответствующий PHP-атрибут для поддержки декларативного стиля

---

## Быстрая справка по файлам

| Файл | Что делать |
|------|-----------|
| `RouteType.php` | Добавить case |
| `RouteData/{Method}Data.php` | Создать класс данных с `match()` |
| `TelegramRouteBuilder.php` | Добавить `on{Method}()` |
| `TelegramRouter.php` | Добавить `on{Method}()` |
| `TelegramRoute.php` | Добавить case `{Method}Data::match()` в `matches()` |
| `UpdateHelper.php` | Добавить маппинг типа в `mapToRouteType()` / `mapMessageType()` |
| `Attributes/On{Method}.php` | Создать PHP-атрибут |
| `Facades/TelegramRouter.php` | Добавить PHPDoc |
| `tests/Unit/TelegramRouterTest.php` | Добавить unit тест |
| `tests/Feature/TelegramRouterTest.php` | Добавить feature тест |
