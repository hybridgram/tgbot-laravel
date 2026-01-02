Поддерживаем варианты описания роутов
Примечание: id бота не может быть равно *

Роут обязан быть привязан либо к боту либо по умолчанию будет общим

Роуты регистрируются через фасад TelegramRouter через метод forBot или make оба метода возвращают 
```php
\HybridGram\Facades\TelegramRouter::forBot()
```


Установка:
```bash
php artisan vendor:publish --provider="HybridGram\Providers\TelegramServiceProvider"
```

Указываем в переменных окружения
```
BOT_TOKEN=ваш_токен
BOT_ID=необзательное, есл ине указать то роутинг будет использовать BOT_TOKEN в качетсве разделения запросов для ботов. 
И при php artisan optimize в кеш запишутся токены ботов что не очень безопасно
```