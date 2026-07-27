# Phase 7.94H — Runtime Switch DEV

## Safety model

- `legacy` is the safe default.
- `shadow` keeps Legacy authoritative and executes Native without authority.
- `native` makes Native authoritative.
- Native fallback to Legacy is independently configurable.
- Rollback forces Legacy and disables Shadow.

## Functional validation checklist

### Subscription

- Stripe checkout completed
- Alfa checkout completed
- Manual purchase
- Free purchase
- Upgrade purchase

### Digital

- Stripe checkout completed
- Manual purchase
- Free purchase

### Resilience

- Payment failure
- Expired checkout
- Duplicate webhook
- Retry
- Multiple grants
- Bundle

### Runtime

- Legacy authority
- Shadow authority remains Legacy
- Native authority
- Native failure with fallback
- Native failure without fallback
- Manual rollback
- Exactly one authoritative fulfillment path

## DEV rollout

1. Certify with `audit_commerce_runtime_phase.php --strict`.
2. Confirm `legacy` mode.
3. Run a Shadow transaction and inspect Shadow persistence.
4. Enable `native` with fallback.
5. Validate representative Subscription and Digital purchases.
6. Execute rollback and confirm Legacy immediately.
7. Re-enable Native only after the rollback exercise is successful.
