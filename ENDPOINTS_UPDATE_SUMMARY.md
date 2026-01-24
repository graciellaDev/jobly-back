# Обновление эндпоинтов Rabota.ru API v4

## Выполненные изменения

Все эндпоинты обновлены согласно OpenAPI спецификации v4: https://api.rabota.ru/v4/openapi.json

### Основные изменения:

1. **Все запросы используют POST метод** (даже для получения данных)
2. **Эндпоинты обновлены** согласно структуре OpenAPI v4
3. **Параметры передаются в теле POST запроса** (JSON формат)

### Обновленные эндпоинты:

| Старый эндпоинт | Новый эндпоинт (OpenAPI v4) | Метод |
|----------------|----------------------------|-------|
| `/v4/me.json` | `/v4/me.json` | POST |
| `/v4/employers/{id}/vacancies/active` | `/v4/me/vacancies.json` | POST |
| `/v4/vacancies/{id}` | `/v4/me/vacancies.json` (с параметром `vacancy_id`) | POST |
| `/v4/vacancies/drafts` | `/v4/me/vacancies.json` (с параметром `folder=drafts`) | POST |
| `/v4/employers/{id}/managers/{id}/vacancies/available_types` | `/v4/me/vacancies/filters.json` | POST |
| `/v4/professional_roles` | `/v4/dictionaries/professional_roles.json` | POST |
| `/v4/negotiations/response?vacancy_id={id}` | `/v4/me/responses.json` (с параметром `vacancy_id`) | POST |
| `/v4/negotiations/{id}` | `/v4/me/response/contact.json` (с параметром `response_id`) | POST |
| `/v4/employers/{id}/addresses` | `/v4/me/company/addresses.json` | POST |
| `/v4/vacancies/{id}/visitors` | `/v4/me/vacancies/responses/counters.json` (с параметром `vacancy_id`) | POST |

### Обновленные файлы:

1. **config/rabota.php** - обновлены все URL эндпоинтов
2. **app/Http/Controllers/api/RabotaRuController.php** - обновлены все методы для использования POST запросов

### Важные замечания:

- Все GET запросы заменены на POST
- Параметры передаются в теле запроса в формате JSON (`$jsonData = true`)
- Убрана зависимость от `employer_id` в URL - теперь используется `/me/...` эндпоинты
- Архивные вакансии получаются через `/v4/me/vacancies/archive.json`

### Следующие шаги:

1. Протестировать все эндпоинты с реальным API
2. Проверить формат ответов API
3. При необходимости скорректировать обработку ответов
