# Diagnosing Digital Payment Issues

The **Digital Purchases** domain records course purchases, subscriptions, upgrades, refunds, and financial events shown in the CRM.

The goal of troubleshooting is to separate display issues, synchronization issues, and actual transaction failures.

## Transaction statuses

A provider transaction may be:

- created;
- pending;
- authorized;
- paid;
- failed;
- cancelled;
- refunded;
- partially refunded.

Only statuses considered final by the payment service should contribute to revenue.

## Paid transaction missing locally

When the provider confirms payment but the CRM does not show the purchase:

1. verify the provider transaction ID;
2. find the user by exact email;
3. inspect webhook or callback logs;
4. run or verify synchronization tasks;
5. check the local order record;
6. review PHP and DML errors;
7. confirm that the target plan exists and is active.

Avoid inserting a manual record immediately. A delayed retry could later create a duplicate.

## Purchase exists but access is missing

Purchasing and granting access are separate operations.

Verify:

- purchased plan;
- plan entitlements;
- access scopes;
- target course ID;
- expected role;
- start and expiry dates;
- upgrade state;
- enrollment synchronization tasks.

Access rules belong in services, not renderers.

## Incorrect amount

Possible causes include:

- discount;
- upgrade difference;
- currency mismatch;
- rounding;
- refund;
- outdated plan configuration;
- provider amount mismatch.

For an upgrade, the expected amount is normally the target price minus the source price, followed by any valid discount.

Amounts less than or equal to zero must be rejected by business rules.

## Currency problems

Always preserve the original currency.

Never combine:

```text
100 EUR + 10,000 RUB
```

The Dashboard must show one subtotal per currency.

Implicit conversion is not allowed unless the plugin defines the exchange-rate source and reference date.

## Duplicate payments

Check:

- provider transaction ID;
- timestamp and amount;
- duplicate callback delivery;
- idempotency key;
- database uniqueness;
- previous attempts.

Idempotency belongs in the service or repository layer.

## Refunds

A refund must not remove the original transaction history.

The CRM should retain:

- original purchase;
- paid amount;
- purchase date;
- refund amount;
- refund reason;
- refund status;
- access impact.

Access removal is a business rule and must not be inferred from financial status alone.

## Security

Administrative payment actions require:

- a Moodle capability;
- `AdminSecurity`;
- sesskey validation;
- strict parameter validation;
- auditable logs.

Provider secrets and sensitive payment data must never be exposed in templates or logs.

## Architecture

Recommended responsibilities:

- repositories: SQL;
- services: payment rules;
- connectors/providers: external communication;
- renderers/templates: display;
- `subscription_config`: shared configuration and routes;
- capabilities: authorization.

## Resolution checklist

Before closing an incident, confirm provider status, local status, linked user, amount, currency, idempotency, access, CRM timeline, and corrective action.
