# CampusFR Commerce Transactional Mailing Engine

**Status:** Certified and stable
**Status:** CERTIFIED
**Lifecycle:** FROZEN
**Frozen on:** 1 August 2026

## Purpose

The engine sends reliable transactional messages for Native Commerce purchases while preserving compatibility with the historical mailer. It supports Moodle users and guests, Course, Digital, Bundle and mixed carts, FR/EN/RU templates, invoices, immediate delivery, retries and independent audit copies.

## Architecture

```text
Payment confirmed
    -> persistent purchase_receipt intention
    -> immediate targeted attempt
    -> sent OR queued for retry

Fulfillment completed and Grants active
    -> persistent purchase_access intention
    -> immediate targeted attempt
    -> sent OR queued for retry
```

The scheduled task remains the recovery mechanism:

```bash
php admin/cli/scheduled_task.php \
  --execute='\local_subscriptions\task\process_commerce_mail_queue_task'
```

## Main components

- `CommerceMailQueueService`: persists idempotent intentions.
- `CommerceMailQueueRepository`: reads and transitions outbox records.
- `CommerceMailQueueProcessor`: targeted immediate processing and cron batches.
- `CommerceMailDispatcher`: template rendering, customer-content policy and transport.
- `MoodleCommerceMailTransport`: Moodle delivery and temporary attachments.
- `CommercePurchaseMailContextFactory`: serializable Purchase/Grant read model.
- `CommerceTransactionalPurchaseMailService`: payment and fulfillment entry points.
- `CommerceInvoicePdfService`: one invoice generator for Order Details and email.
- `CommerceMailTemplateDefaults`: 15 editorial defaults (5 types × 3 languages).
- Template Studio: TinyMCE customisation, controlled tokens and header images.

## Idempotence

Customer keys are stable per purchase and intent:

```text
purchase:{purchaseid}:purchase_receipt
purchase:{purchaseid}:purchase_access
```

Audit copies use a distinct `:audit` suffix and never determine customer-message success.

## Status lifecycle

```text
queued -> processing -> sent
                    -> queued (retry)
                    -> failed (attempts exhausted)
queued/failed -> cancelled (admin action)
```

## Customer references

Only public references such as `CFR-2026-XXXXXX` may be visible. Internal `cmp_*` references are accepted only inside technical URLs. `CommerceMailCustomerContentPolicy` blocks visible leakage in subject, HTML, text and attachment filenames.

## Invoice attachment

`purchase_receipt` attaches the exact PDF produced by `CommerceInvoicePdfService`. A generation or transport failure keeps the message eligible for retry; a receipt is never marked sent without its required invoice.

## Editorial model

TinyMCE controls only:

- subject and preheader;
- heading;
- introduction;
- conclusion;
- signature;
- optional header image.

Commerce controls the protected technical block: products, totals, payment, access links, downloads and invoice.

## Supervision

CRM route:

```text
/local/subscriptions/admin/commerce/mail/index.php
```

It provides filters, translated badges, preview modes, retry/cancel actions and the read-only health banner.

## Certification commands

Structural and operational health:

```bash
php local/subscriptions/cli/commerce/mail/certify_engine.php --strict
```

Final frozen-release certification:

```bash
php local/subscriptions/cli/commerce/mail/certify_release.php
```

Machine-readable output:

```bash
php local/subscriptions/cli/commerce/mail/certify_release.php --json
```

## Extension rules after freeze

- No new responsibility in `mailer.php`; it remains a Legacy adapter.
- No direct SMTP call from Payment or Fulfillment handlers.
- Persist before delivery.
- Every new mail intent requires a stable idempotency key.
- Every customer-visible identifier must use the public CFR reference.
- New functionality belongs to a future roadmap phase; I6 accepts critical hotfixes only.
