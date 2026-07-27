# CLI tools — CampusFR subscriptions

The CLI directory is organized by responsibility. H0C changes paths only; no historical tool is deleted.

- `commerce/audit`: focused Commerce diagnostics and compatibility checks.
- `commerce/certification`: phase and release certification entry points.
- `commerce/migration`: backfills, imports and reconciliation tools.
- `commerce/operations`: controlled business operations and payment checks.
- `commerce/reporting`: reports and Shadow exports.
- `crm/audit`: CRM validations and diagnostics.
- `crm/inbox`: Inbox synchronization and diagnostics.
- `crm/recommendations`, `crm/success`, `crm/automation`: domain-specific CRM tooling.
- `maintenance`: file storage, language, enrolment and cron maintenance.
- `mail`: email previews and delivery tools.
- `development`: test, seed, personal and proof-of-concept scripts; not intended for production operations.
- `operations`: remaining administrative utilities pending H0D review.

All old root paths are listed in `maintenance/h0c/h0c_cli_cleanup_manifest.php`. Run the cleanup script in dry-run mode before execution.

## Phase 7.94H0

La politique permanente de classement, bootstrap et cycle de vie des outils est documentée dans `docs/tooling/H0_TOOLING_POLICY.md`.
L'audit global est :

```bash
php local/subscriptions/cli/maintenance/tooling/audit_h0_tooling_phase.php --strict
```
