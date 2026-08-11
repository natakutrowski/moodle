# Commerce 7.95F7B–F7D certification

This delivery is read-only apart from PHPUnit fixtures rolled back by Moodle.

## F7B — Pricing and promotions

Checks negative prices, invalid currencies, duplicate active price groups, active products without prices, malformed promotion metadata, invalid promotional amounts and invalid promotion windows.

## F7C — Checkout and purchases

Checks orphan purchase children, immutable total consistency, currency consistency, provider-reference uniqueness, fulfillment idempotency and fulfilled purchases without a completed fulfillment.

## F7D — Ownership and entitlements

Checks entitlement definitions, grant validity and references, beneficiaries, digital access linkage/download limits and fulfillment-state linkage.

## Commands

```bash
php local/subscriptions/cli/commerce/audit_795f7b_pricing_promotions.php
php local/subscriptions/cli/commerce/audit_795f7c_checkout_purchases.php
php local/subscriptions/cli/commerce/audit_795f7d_ownership_entitlements.php
```

Add `--json` for machine-readable output.
