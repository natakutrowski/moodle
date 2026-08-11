# Commerce customer journey certification

This document describes the final, read-only certification of the CampusFR Commerce customer journey.

## Covered journeys

- successful payment return;
- pending and processing states;
- failed payment and cancellation;
- retry;
- Course purchase and access;
- Digital purchase and secure downloads;
- Bundle expansion and fulfillment;
- Guest checkout and later account attachment;
- Mes Achats;
- Mes Ressources digitales;
- Mes Cours;
- transactional emails and invoice attachment;
- CRM, User 360, timeline, Explorer, dashboard and revenue analytics;
- support, print, CTA tracking and historical URL compatibility.

## Final command

```bash
php local/subscriptions/cli/commerce/certify_customer_journey.php --strict
```

The command is read-only. It aggregates the certified mail, My Courses, CRM and professional customer-experience subsystems, then verifies the cross-cutting payment-state, product-family, Guest, customer-page and test-matrix contracts.

## PHPUnit

```bash
vendor/bin/phpunit \
  --testsuite local_subscriptions_testsuite \
  local/subscriptions/tests/commerce/certification/commerce_customer_journey_certification_test.php
```

Full plugin suites:

```bash
vendor/bin/phpunit --testsuite local_subscriptions_testsuite
vendor/bin/phpunit --testsuite local_campus_testsuite
```

## Manual release matrix

Before production deployment, verify at least one real DEV flow for each row:

| Scenario | Expected result |
|---|---|
| Course | Payment, enrollment, Mes Achats, Mes Cours and email are coherent |
| Digital | Secure files appear in Mes Ressources and Order Details |
| Bundle | All components are expanded and delivered |
| Guest | Payment completes and the order remains securely accessible |
| Pending | No premature access; clear status is shown |
| Failed | No fulfillment; retry or support action is available |
| Cancelled | Clear cancellation state and safe navigation |
| Retry | A failed attempt can succeed without duplicate purchase or revenue |
| Email | Receipt and access email are sent once; invoice is attached |
| CRM | User 360, timeline, Explorer and revenue use the unified Native read model |

## Operational warnings

The strict command fails on warnings. Typical warnings are stale pending purchases, failed grants, or delayed transactional emails. Historical failed payments are not by themselves certification failures when a later successful attempt exists.
