# Commerce 7.95H0–H1 — Unified Checkout foundations

## Boundary

This batch introduces the backend-only boundary between the certified Cart domain and the existing provider-neutral Payment domain.

```text
CartSnapshot
    -> CheckoutSummary
    -> PurchaseRequest (payment_pending)
    -> PaymentRequest
    -> CommercePaymentOrchestrator
```

No page, template, CSS rule or legacy entry point is changed in H0–H1.

## Invariants

- Cart remains the only source of pricing and promotion calculation.
- Checkout never recalculates prices, discounts or taxes.
- Checkout, Purchase and Payment totals must be identical.
- A blocking Cart message prevents purchase creation.
- The Purchase request freezes product references, quantities, allocated totals, customer, provider and navigation URLs.
- Provider selection and remote initialization remain delegated to the existing `CommercePaymentOrchestrator`.
- Provider launch is idempotent through the existing `CommercePaymentProviderContextFactory`.

## Public foundation API

- `CommerceCheckoutContext`
- `CommerceCheckoutValidationIssue`
- `CommerceCheckoutValidationResult`
- `CommerceCheckoutValidator`
- `CommerceCheckoutSummary`
- `CommerceCheckoutSummaryBuilder`
- `CommerceCheckoutPurchaseBuilder`
- `CommerceCheckoutPaymentRequestBuilder`
- `CommerceCheckoutSnapshot`
- `CommerceCheckoutLaunchResult`
- `CommerceCheckoutRuntime`
- `CommerceCheckoutRuntimeFactory`

These classes live under `commerce/checkout/unified` so that the historical transitional checkout facade remains untouched until the adapter phase.

## Deferred to later H batches

- SQL persistence of the pending Purchase snapshot.
- Checkout page and customer form.
- Migration adapters for historical checkout entry points.
- Final allocation strategy for catalogue products allowing quantities greater than one.
- End-to-end provider launch from the browser.
