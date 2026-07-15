# Understanding CRM Dashboard Periods

The CRM Dashboard in `local_subscriptions` provides several analysis periods so administrators can understand current commercial and operational activity without leaving the page.

The period selector normally switches between **Today**, **Current week**, and **Current month**.

## Today

The **Today** view includes events recorded since the start of the current day in the Moodle timezone.

Use it to monitor:

- new users;
- trial starts;
- completed digital purchases;
- recent CRM interactions;
- payment anomalies;
- inbox activity.

Because the period is short, values can change frequently.

## Current week

The **Current week** view covers the interval from the configured first day of the Moodle week up to the current time.

It is useful for:

- comparing several days;
- identifying active and quiet periods;
- monitoring weekly revenue;
- reviewing follow-up workload;
- confirming whether a daily anomaly is isolated.

This is usually the best operational view.

## Current month

The **Current month** view covers the first day of the month through the current time.

It supports:

- monthly reporting;
- conversion analysis;
- revenue monitoring by currency;
- CRM risk analysis;
- purchase trend reviews.

The figures are actual values, not forecasts.

## Hero metrics

The main Hero area can contain:

- new user count;
- trial count;
- digital purchase count;
- digital revenue;
- profiles requiring attention;
- recent CRM activity.

Changing the period must refresh all related metrics consistently.

## Revenue by currency

Digital revenue must be grouped by currency.

Example:

```text
EUR: €3,420.00
RUB: ₽186,000
```

Amounts from different currencies must never be added without an explicit conversion rule.

The values should come from the same Digital Purchases services used by user profiles and financial views.

## CRM Intelligence navigation

CRM Intelligence cards may identify:

- at-risk profiles;
- trials without conversion;
- inactive users;
- payment failures;
- unanswered conversations.

Clickable metrics should open the User Explorer with the matching filters already applied.

## Technical consistency

The Dashboard must follow the plugin architecture:

- SQL belongs in repositories;
- business calculations belong in services;
- renderers and templates must not run SQL;
- administrative access must be enforced through `AdminSecurity`;
- shared routes should come from `subscription_config`;
- sensitive features must use Moodle capabilities.

## Troubleshooting inconsistent metrics

Check the following:

1. selected period;
2. Moodle timezone;
3. transaction status;
4. scheduled tasks;
5. cache state;
6. date boundaries;
7. plugin logs.

Do not fix metrics directly in a template. Correct the repository query or business service responsible for the value.

## Administrator recommendations

Use **Today** for live monitoring, **Current week** for operations, and **Current month** for reporting.

Before sharing a figure, confirm the active period, currency, included payment statuses, generation time, and any inherited filters.
