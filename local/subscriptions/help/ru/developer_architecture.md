# Архитектура разработчика `local_subscriptions`

Документ описывает основные архитектурные принципы Moodle-плагина `local_subscriptions`.

Цель — сохранить сложный CRM поддерживаемым за счет разделения доступа к данным, бизнес-логики, безопасности и отображения.

## Функциональные области

Плагин включает:

- CRM Dashboard;
- User Explorer;
- CRM Intelligence;
- Digital Purchases;
- Command Center;
- Help Center;
- CRM Inbox;
- профили и timeline;
- подписки и права доступа.

Каждая область может иметь собственные repositories, services, providers и view models.

## SQL, Services и Renderers

### Repositories

Repositories отвечают за доступ к данным.

Они должны:

- использовать Moodle DML;
- привязывать параметры;
- применять сортировку и пагинацию;
- возвращать records или простые объекты;
- не генерировать HTML.

### Services

Services содержат бизнес-правила.

Примеры:

- расчет коммерческого статуса;
- проверка upgrade;
- расчет суммы;
- классификация CRM;
- синхронизация entitlements;
- координация нескольких repositories.

Services не должны зависеть от templates.

### Renderers и templates

Renderers подготавливают данные для интерфейса.

Они могут:

- создавать view models;
- форматировать даты;
- создавать URL;
- выбирать иконки и подписи;
- вызывать `get_string()`.

Они не должны выполнять SQL или сложные коммерческие расчеты.

## `AdminSecurity`

`AdminSecurity` централизует административный контроль доступа.

Используйте его для:

- проверки capabilities;
- защиты чувствительных страниц;
- защиты CRM-действий;
- устранения дублирующихся проверок;
- единой политики безопасности.

Изменяющие данные действия также проверяют sesskey.

## `subscription_config`

`subscription_config` хранит общую конфигурацию:

- административные маршруты;
- идентификаторы страниц;
- настройки плагина;
- общие функциональные значения.

Это уменьшает дублирование путей и ручную сборку URL.

## Capabilities

Capabilities определяют, что администратор может видеть и изменять.

Правила:

- объявлять их в `db/access.php`;
- сохранять стабильные имена;
- разделять просмотр и управление при необходимости;
- никогда не вызывать несуществующую capability;
- обновлять определения Moodle после изменений.

## CRM Dashboard

Dashboard агрегирует показатели из services.

Все периоды должны использовать общую логику границ дат.

## User Explorer

Надежная структура включает:

- объект фильтра;
- paginated repository;
- services статусов;
- renderer;
- ссылки из CRM Intelligence.

Сортировка выполняется до пагинации.

## CRM Intelligence

Каждый сигнал должен быть понятным, проверяемым, кликабельным, связанным со списком профилей и основанным на документированном правиле.

## Digital Purchases

Область отвечает за покупки, upgrades, валюты и возвраты.

Идемпотентность и финансовые статусы находятся в services.

## Command Center

Command Center расширяется через providers.

Каждый provider соблюдает общий контракт и проверяет права до публикации результата.

## Help Center

Help Center использует метаданные и переведенные Markdown-файлы.

CLI-валидатор должен проверять:

- категории;
- статьи;
- guides;
- наличие файлов;
- обязательные языки;
- идентификаторы.

## CRM Inbox

CRM Inbox разделяет:

- connectors;
- credential storage;
- загрузку сообщений;
- attachments;
- synchronization;
- business services;
- rendering.

Credentials не должны попадать в templates или logs.

## Стандарты Moodle

Плагин должен соблюдать:

- PSR-4 namespaces Moodle;
- `defined('MOODLE_INTERNAL') || die();`;
- DML API;
- Access API;
- String API;
- URL API;
- scheduled tasks;
- безопасные CLI;
- согласованные `install.xml` и `upgrade.php`.

## Общее правило

Административная страница должна координировать, а не содержать всю реализацию.

Рекомендуемый поток:

```text
Page PHP
  -> AdminSecurity
  -> Service
  -> Repository
  -> View model
  -> Renderer
  -> Template
```
