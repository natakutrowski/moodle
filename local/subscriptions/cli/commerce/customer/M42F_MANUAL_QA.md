# CampusFR Commerce — M4.2 Identity Operations final QA

## A — Reconciliation
- Search by partial email, name, purchase ID, reference, SKU and candidate Moodle user.
- Select several unlinked purchases.
- Dry-run must write nothing.
- Execute only unique exact matches.
- Ambiguous matches remain untouched.

## B — Similar accounts
- Same full name with a different email appears as a suggestion.
- Close name/email combinations display explicit scoring signals.
- Equivalent phone formats are detected.
- The screen itself performs no write.

## C — Merge planner
- Compare at least two accounts.
- Account with strongest learning history is recommended first.
- Verify enrolled courses, completed courses, completed activities and grades.
- Manually choose another primary account and recalculate.
- Verify the virtual final profile and warnings.

## D — Merge execution
- Safe source account with Commerce-only data can be merged.
- Commerce/CRM data moves to the chosen target.
- Source Moodle user is suspended, never deleted.
- Audit rows exist in local_subs_identity_merge and local_subs_identity_merge_source.
- A source with learning history is blocked.
- A source with unsupported Legacy subscription/payment data is blocked.
- An already-merged source cannot be merged again.

## E — Legacy Digital account provisioning
- Legacy Digital buyer without Moodle account is classed as creatable.
- Dry-run creates no Moodle user.
- Exact existing Moodle account is never duplicated.
- Similar account blocks creation by default.
- Explicit override is required to create despite a similarity warning.
- Bulk preview and execution work with several identities.
- Newly provisioned account is suspended/unconfirmed.
- All matching paid/completed Legacy Digital purchases are linked.
- Matching unresolved Native purchases are reconciled.
- Activation mail is queued.
- One-time activation sets a password, confirms and unsuspends the account.
- Activated user lands on Mon Campus and can reach purchases/resources.

## Cross-cutting
- FR / EN / RU.
- CRM shell + breadcrumb/navigation on every Identity Operations page.
- Write actions require correct capability and sesskey.
- Repeat with at least one realistic anonymised customer case before PROD.
