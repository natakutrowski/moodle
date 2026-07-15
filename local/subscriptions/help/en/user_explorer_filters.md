# Using User Explorer Filters

The **User Explorer** is the primary search and segmentation interface in the `local_subscriptions` CRM.

It helps administrators turn a large user base into actionable lists for support, retention, sales follow-up, and investigation.

## Filter model

The feature should preserve a clean separation:

1. repositories select and paginate data;
2. services calculate business statuses;
3. renderers prepare display data.

Templates must not contain SQL or complex business rules.

## Text search

Text search may include:

- display name;
- primary email;
- conversation subject;
- business identifiers;
- indexed CRM fields.

Search normalization should use Moodle-compatible database helpers rather than engine-specific SQL functions.

## Commercial status filters

Common segments include:

- active trial;
- expired trial;
- course purchase;
- active subscription;
- expired subscription;
- lifetime customer;
- upgrade available;
- payment issue.

These statuses should be produced by business services.

## Activity filters

Activity filters can identify:

- recently active users;
- inactive users;
- users who never logged in;
- recent CRM interactions;
- profiles without contact for a defined period.

All dates must follow the Moodle timezone rules.

## CRM Intelligence filters

CRM Intelligence can open the User Explorer with filters already applied.

Examples:

- at-risk profiles;
- high-intent trials;
- users to contact;
- failed payments;
- unanswered conversations;
- incomplete onboarding.

The active segment should be clearly displayed.

## Digital Purchases filters

Purchase-related filters may include:

- plan;
- currency;
- payment status;
- purchase date;
- operation type;
- order source;
- refund state;
- upgrade state.

The data source should be consistent with the Dashboard and user profile.

## Combining filters

Most independent filters use **AND** logic.

Example:

```text
Active trial
AND
No purchase
AND
Inactive for more than 7 days
```

Multiple values inside the same filter group may use **OR** logic.

## Sorting and pagination

Sorting must happen in SQL before pagination.

Useful sort fields include:

- last activity;
- registration date;
- last purchase;
- total spend;
- risk score;
- display name.

Client-side sorting alone is not sufficient for paginated data.

## Security

Access must be protected by a Moodle capability.

Sensitive actions should pass through `AdminSecurity`.

URLs should use `moodle_url` and shared routes from `subscription_config` where applicable.

## Resetting filters

A full reset should:

- clear query parameters;
- remove inherited Dashboard segments;
- return to page zero;
- restore default sorting;
- remove hidden URL state.

## Troubleshooting

When a profile is missing:

1. reset all filters;
2. search the exact email;
3. confirm the Moodle account status;
4. check administrator permissions;
5. verify purchase and subscription data;
6. check date boundaries;
7. purge caches after code changes.

Selection issues should be corrected in the repository or service layer, not in the renderer.
