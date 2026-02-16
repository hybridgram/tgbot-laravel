---
name: update-docs-on-pull-request
description: Обнови документацию проекта по измениям из пуллреквеста. Используй этот скил когда пользователь скидывает ссылку на пуллреквест содержащий hybridgram/tgbot-laravel/pulls в адресной строке и упоминает документацию.
---

## 🎯 КОГДА ИСПОЛЬЗОВАТЬ

Используй этот скил если пользователь:
- Скидывает ссылку на PR (например: `https://github.com/hybridgram/tgbot-laravel/pull/9`)
- Просит "документировать", "обновить документацию", "добавить в docs"
- Упоминает документацию в контексте PR

**ИСКЛЮЧЕНИЯ:**
- Если PR содержит только изменения в тестах → обновление НЕ требуется
- Если PR содержит только изменения в vendor → обновление НЕ требуется
- Если PR содержит только изменения в миграциях → обновление НЕ требуется

## ⚠️ ТРЕБОВАНИЯ ПЕРЕД ЗАПУСКОМ

**ОБЯЗАТЕЛЬНО проверь ДО начала работы:**
1. Вызови `gh auth status` - должна быть авторизация в GitHub
2. Если нет авторизации → вызови `gh auth login`
3. Проверь что Python скрипт существует:
   ```bash
   ls -la /home/andrey/projects/hybridgram/.claude/update-docs-on-pull-request/get_pr_diffs.py
   ```

## 📋 ПОЛНЫЙ WORKFLOW

**ВАЖНО:** Этот скил требует выполнения ВСЕ ШАГ ПОДРЯД без пропусков!
Пропуск шагов приведёт к неполной работе.

## Инструкция

## ЭТАП 1️⃣ : АНАЛИЗ PR (в репозитории tgbot-laravel)

### Шаг 1: Извлекаем номер PR
Из полученной ссылки вида `https://github.com/hybridgram/tgbot-laravel/pull/9` извлеки номер:
- Сохрани его в переменную `$PULL_NUMBER` (например: 9)

### Шаг 2: Получаем информацию о PR
**ВЫПОЛНИ:**
```bash
gh pr view $PULL_NUMBER --repo hybridgram/tgbot-laravel --json number,state,title,body,url,headRefName,baseRefName
```
Проверь что PR находится в статусе `OPEN` или `MERGED`.

### Шаг 3: 🔴 КРИТИЧЕСКИЙ ШАГ - Запуск Python скрипта
**ЭТОТ ШАГ НЕЛЬЗЯ ПРОПУСКАТЬ!**

Запусти скрипт для получения диффов:
```bash
cd /home/andrey/projects/hybridgram/.claude/update-docs-on-pull-request
python3 get_pr_diffs.py $PULL_NUMBER > pr_diffs.json
```

**Что делает скрипт:**
- Фильтрует только публичные файлы (`src/`, `app/`, `config/`, `routes/`, `*.php`)
- Исключает тесты, vendor, миграции и т.д.
- Сжимает большие диффы (>1000 строк), сохраняя значимые изменения
- Возвращает JSON с полным анализом

**Ожидаемый результат:** файл `pr_diffs.json` должен быть создан

### Шаг 4: Анализируем diff результаты
**ВЫПОЛНИ:** Прочитай файл `pr_diffs.json` используя Read tool

**Анализируй для каждого файла:**
- Изменения в публичных классах/методах/интерфейсах
- Новые публичные методы или классы
- Изменения сигнатур методов
- Breaking changes (удаление, изменение параметров)
- Изменения в поведении публичного API

**Поля для внимания:**
- `compressed: true` → смотри `significant_changes_found` в diff
- `change_lines` → реальное кол-во строк изменений
- Для сжатых файлов → ищи секцию `# Significant changes`

**КРИТИЧЕСКАЯ ПРОВЕРКА:** Если изменения ТОЛЬКО в private методах/тестах/внутренней логике → ОСТАНОВИ ПРОЦЕСС и сообщи что обновление не требуется.

### Шаг 5: Определяем файлы документации для обновления
На основе анализа diff определи ЧТО нужно обновить:

**Сопоставление изменений:**
- Роутинг → `src/content/docs/en/routing/`
- Команды/сообщения → `src/content/docs/en/basics/`
- Middleware/состояния → `src/content/docs/en/advanced/`
- Отправка сообщений → `src/content/docs/en/sending/`
- Webhook/polling → `src/content/docs/en/modes/`
- Конфигурация → `src/content/docs/en/getting-started/`

**ЧТО обновить:**
- Новое? → добавь новый раздел
- Изменено? → обнови существующий раздел
- Breaking change? → добавь предупреждение

### Шаг 6: Читаем существующую документацию
**ВЫПОЛНИ:** Прочитай ВСЕ файлы документации которые нужно обновить (Read tool)

Это нужно чтобы понять текущую структуру и стиль документации.

---

## ЭТАП 2️⃣ : ОБНОВЛЕНИЕ ДОКУМЕНТАЦИИ (в репозитории docs)

### Шаг 7: Переходим в репозиторий docs
**ВЫПОЛНИ:**
```bash
cd /home/andrey/projects/hybridgram/docs
git checkout main
git pull origin main
```

**Проверка:** Должны быть на ветке `main` и она должна быть актуальной.

### Шаг 8: Создаем новую ветку
**ВЫПОЛНИ:**
```bash
git checkout -b docs-update-pr-$PULL_NUMBER
```

Пример: `docs-update-pr-9`

### Шаг 9: Обновляем документацию
**ВЫПОЛНИ:** Используй Edit/Write tool для обновления файлов документации:
- Добавь примеры кода для новых возможностей
- Обнови описания измененных методов
- Добавь warning/notice если есть breaking changes
- Обнови оба языка (EN и RU если нужно)

**ВАЖНО:** Добавь примеры из PR в документацию!

### Шаг 10: Стейджим все изменения
**ВЫПОЛНИ:**
```bash
git add .
git status
```

Проверь что только нужные файлы добавлены.

### Шаг 11: Коммитим изменения
**ВЫПОЛНИ:**
```bash
git commit -m "Update documentation for PR #$PULL_NUMBER

- Updated: <перечисли обновленные разделы>
- Added: <перечисли добавленные разделы>
- Related: hybridgram/tgbot-laravel#$PULL_NUMBER

Co-Authored-By: Claude Haiku 4.5 <noreply@anthropic.com>"
```

### Шаг 12: Пушим изменения в origin
**ВЫПОЛНИ:**
```bash
git push -u origin docs-update-pr-$PULL_NUMBER
```

**Если ошибка HTTPS:** Измени remote на SSH:
```bash
git remote set-url origin git@github.com:hybridgram/docs.git
git push -u origin docs-update-pr-$PULL_NUMBER
```

**Ожидаемый результат:** GitHub выведет ссылку на создание PR

### Шаг 13: Создаем PR в docs репозитории
**ВЫПОЛНИ:**
```bash
gh pr create \
  --title "Update documentation for tgbot-laravel PR #$PULL_NUMBER" \
  --body "Documentation update based on tgbot-laravel changes

## Updated Sections
- <перечисли какие разделы обновлены>

## Changes
- <кратко опиши что изменилось в документации>

## Related PR
hybridgram/tgbot-laravel#$PULL_NUMBER

🤖 Generated with Claude Code" \
  --repo hybridgram/docs
```

**Ожидаемый результат:** GitHub выведет ссылку на созданный PR

---

## ЭТАП 3️⃣ : ЗАВЕРШЕНИЕ

### Шаг 14: Возвращаемся в основной репозиторий
**ВЫПОЛНИ:**
```bash
cd /home/andrey/projects/hybridgram
```

### Шаг 15: Сообщаем пользователю результаты
**Сообщи:**
1. ✅ Какие изменения были обнаружены в PR
2. ✅ Какие разделы документации были обновлены
3. ✅ Ссылку на созданный PR в docs репозитории (формат: `https://github.com/hybridgram/docs/pull/X`)
4. ✅ Краткую сводку изменений документации

**Если обновление не требуется - объясни ПОЧЕМУ**

---

## 🚨 ТИПИЧНЫЕ ПРОБЛЕМЫ И РЕШЕНИЯ

### Проблема: `gh auth status` показывает что не авторизирован

**Решение:**
```bash
gh auth login
# Выбери: GitHub.com
# Выбери: HTTPS
# Выбери: Paste an authentication token
# Вставь токен с правами repo,gist,read:org
```

### Проблема: Python скрипт не найден
**Решение:**
```bash
ls -la /home/andrey/projects/hybridgram/.claude/update-docs-on-pull-request/
# Если get_pr_diffs.py нет - сообщи об ошибке пользователю
```

### Проблема: `git push` падает с ошибкой HTTPS
**Решение:**
```bash
# Смени remote на SSH:
git remote set-url origin git@github.com:hybridgram/docs.git
git push -u origin docs-update-pr-$PULL_NUMBER
```

### Проблема: Неудачный коммит (pre-commit hook ошибка)
**Решение:**
1. Исправь ошибки в файлах
2. Заново добавь файлы: `git add .`
3. Создай НОВЫЙ коммит (не --amend!)

### Проблема: PR не создался, но пуш успешен
**Решение:**
```bash
# GitHub должен был вывести URL для создания PR
# Если нет - создай вручную через gh pr create
gh pr create --title "..." --body "..." --repo hybridgram/docs
```

### Проблема: Изменения в main перезаписали мою работу
**Решение:**
```bash
# Сначала вернись на main и обнови:
git checkout main
git pull origin main
# Потом пересоздай ветку:
git checkout -b docs-update-pr-$PULL_NUMBER
```

---

## ✅ ЧЕКЛИСТ ПЕРЕД ФИНИШЕМ

Перед тем как сообщить пользователю, проверь:

- [ ] Python скрипт был запущен и `pr_diffs.json` создан
- [ ] Все шаги ЭТАПА 1 выполнены (анализ PR)
- [ ] Все шаги ЭТАПА 2 выполнены (обновление docs)
- [ ] Файлы документации обновлены (EN и RU если нужно)
- [ ] Git коммит создан
- [ ] Ветка запушена на origin
- [ ] PR создан в hybridgram/docs репозитории
- [ ] Ты вернулся в основной репозиторий (/home/andrey/projects/hybridgram)
- [ ] Ты сообщил пользователю результаты с ссылкой на PR
