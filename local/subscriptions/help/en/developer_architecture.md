# Developer Architecture of `local_subscriptions`

This document describes the main architectural principles of the Moodle plugin `local_subscriptions`.

The purpose is to keep a feature-rich CRM maintainable by separating data access, business rules, security, and rendering.

## Functional domains

The plugin includes several domains:

- CRM Dashboard;
- User Explorer;
- CRM Intelligence;
- Digital Purchases;
- Command Center;
- Help Center;
- CRM Inbox;
- user profiles and timeline;
- subscriptions and access rights.

Each domain may contain repositories, services, providers, and presentation objects.

## SQL, Services, and Renderers

### Repositories

Repositories own data access.

They should:

- build Moodle DML queries;
- bind parameters;
- apply sorting and pagination;
- return records or simple objects;
- avoid HTML generation.

### Services

Services own business logic.

Examples:

- commercial status calculation;
- upgrade validation;
- amount calculation;
- CRM classification;
- entitlement synchronization;
- orchestration across repositories.

Services should not depend on templates.

### Renderers and templates

Renderers prepare presentation data.

They may:

- build view models;
- format dates;
- create URLs;
- select labels and icons;
- call `get_string()`.

They must not run SQL or implement complex commercial rules.

## `AdminSecurity`

`AdminSecurity` centralizes administrative access control.

Use it to:

- enforce capabilities;
- protect sensitive pages;
- secure CRM actions;
- avoid duplicated checks;
- maintain a consistent policy.

Data-changing actions must also validate the Moodle sesskey.

## `subscription_config`

`subscription_config` centralizes shared configuration such as:

- administrative routes;
- page identifiers;
- plugin settings;
- shared functional constants.

This avoids duplicated paths and manually assembled URLs.

## Capabilities

Capabilities define what administrators may view or manage.

Rules:

- declare them in `db/access.php`;
- keep names stable;
- separate read and manage permissions where appropriate;
- never call an undeclared capability;
- upgrade Moodle definitions after changes.

## CRM Dashboard

The Dashboard aggregates metrics from services.

Time periods must use shared date-boundary rules.

## User Explorer

A robust User Explorer includes:

- filter object;
- paginated repository;
- status services;
- renderer;
- incoming links from CRM Intelligence.

Sort before pagination.

## CRM Intelligence

Each signal should be understandable, traceable, navigable, linked to a profile list, and based on a documented rule.

## Digital Purchases

This domain handles purchases, upgrades, currencies, and refunds.

Idempotency and financial-status rules belong in services.

## Command Center

The Command Center is extended through providers.

Every provider must follow the shared contract and check permissions before returning results.

## Help Center

The Help Center uses metadata plus translated Markdown files.

Its validation CLI should verify categories, articles, guides, required files, translations, and identifiers.

## CRM Inbox

CRM Inbox separates:

- connectors;
- credential storage;
- message retrieval;
- attachments;
- synchronization;
- business services;
- rendering.

Credentials must never appear in templates or logs.

## Moodle standards

The plugin should follow:

- Moodle PSR-4 namespaces;
- `defined('MOODLE_INTERNAL') || die();`;
- DML API;
- Access API;
- String API;
- URL API;
- scheduled tasks;
- secure CLI scripts;
- consistent `install.xml` and `upgrade.php`.

## General rule

Administrative pages should orchestrate rather than contain the complete implementation.

A healthy request flow is:

```text
Page PHP
  -> AdminSecurity
  -> Service
  -> Repository
  -> View model
  -> Renderer
  -> Template
```
