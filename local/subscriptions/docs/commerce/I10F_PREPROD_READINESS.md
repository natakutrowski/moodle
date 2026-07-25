# I10F — Native Commerce Finalization & PRE-PROD Readiness

## Safety contract

I10F is additive and reversible. It does not remove Legacy Commerce tables, Legacy read bridges, upgrade migrations, fallback flags, or rollback projections.

Legacy writes are split into three explicit groups:

1. **Native persistence** — authoritative Native Commerce tables.
2. **Legacy compatibility projection** — retained during PRE-PROD for upgrade and rollback safety.
3. **Operational Commerce writes** — email markers, task state, logs and reminders that are not Purchase projections.
4. **Catalogue and configuration writes** — products, plans, prices, scopes and translations, outside the Purchase runtime projection.

Only an unclassified fifth group, **migration candidate**, blocks technical readiness.

## Commands

```bash
php local/subscriptions/cli/audit_i10f_commerce_runtime.php --strict
php local/subscriptions/cli/audit_i10f_migration_safety.php --strict
php local/subscriptions/cli/audit_i10f_preprod_readiness.php
vendor/bin/phpunit --testsuite local_subscriptions_testsuite
```

The final readiness audit intentionally warns when the current flag order is historically inconsistent. It does not silently modify flags.

## Functional PRE-PROD evidence

Technical readiness must be followed by real smoke tests for:

- Stripe digital and subscription flows;
- Alfa digital and subscription flows;
- duplicate callbacks and idempotency;
- fulfillment and transactional emails;
- manual Admin and Quick Actions;
- cron, reconciliation and repair dry-run;
- upgrade from the currently deployed PROD version;
- rollback with Legacy fallback enabled.

## Retirement policy

No compatibility component is removed in I10F. Potential removals are deferred until after PRE-PROD and PROD observation, in a dedicated Legacy retirement phase.
