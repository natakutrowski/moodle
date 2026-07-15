# Diagnosing the CRM Inbox

The CRM Inbox diagnostics page checks whether receiving, sending, synchronisation and AI assistance are operating correctly.

Use the diagnostics before manually editing database records or repeatedly restarting a synchronisation.

## Before starting

You need permission to manage CRM configuration.

Diagnostics may expose technical information about:

- configured email accounts;
- IMAP and SMTP connectors;
- remote folders;
- synchronisation runs;
- attachments;
- the AI provider;
- quotas;
- cache usage;
- recent errors.

Passwords are never displayed in clear text.

## IMAP diagnostics

IMAP is used to receive email.

The diagnostic checks:

- whether the PHP IMAP extension is installed;
- whether the Inbox account is enabled;
- whether required credentials are present;
- whether the remote server is reachable;
- whether authentication succeeds;
- whether configured folders exist.

An IMAP failure may stop new messages from being received while previously imported conversations remain available.

### Common causes

- missing PHP IMAP extension;
- PHP-FPM not restarted after installing the extension;
- invalid password;
- incorrect hostname or port;
- rejected TLS connection;
- renamed remote folder;
- IMAP access disabled by the provider.

## SMTP diagnostics

SMTP is used to send replies.

The diagnostic checks:

- SMTP server access;
- authentication;
- port;
- encryption;
- sender address.

A successful IMAP connection does not guarantee that SMTP works. Both connectors must be checked separately.

## Synchronisation

Inbox synchronisation is idempotent.

The same remote email must not be imported more than once.

Important counters include:

- **fetched**: messages read from the provider;
- **created**: new messages stored in Moodle;
- **updated**: known messages whose data changed;
- **skipped**: messages requiring no update;
- **errors**: messages that could not be processed;
- **has more**: additional remote messages remain available.

### Incomplete synchronisation

Do not immediately start several synchronisations at the same time.

Check:

1. active locks;
2. batch size;
3. the latest cursor;
4. the error count;
5. the remote folder;
6. scheduled task logs.

The CLI and scheduled task can continue processing in batches.

## Attachments

Attachments are copied into the Moodle File API.

Diagnostics may identify:

- failed remote downloads;
- empty content;
- inconsistent MIME types;
- oversized files;
- Moodle storage errors;
- remote references that are no longer available.

Deleting a CRM conversation is not the same as deleting a message at the provider.

## Contacts and user matching

An email may be received from someone who is not yet known by the CRM.

In that case:

- an external contact is created;
- the conversation remains available;
- no Moodle user is invented;
- matching can occur later.

When a CampusFR account is created with the same address, automatic rematching may connect the contact to that user.

A locked manual match must never be overwritten automatically.

## AI diagnostics

AI diagnostics check:

- the active provider;
- OpenAI configuration;
- the selected model;
- available capabilities;
- daily quotas;
- recent failures;
- cache availability;
- local fallback availability.

An available provider does not guarantee that every response is valid. Results still pass through local validation and Structured Outputs.

## AI result states

Results may be:

- **success**: valid output;
- **partial**: usable but incomplete output;
- **failed**: missing, invalid or rejected output;
- **cached**: a valid result loaded from cache.

Invalid output must never be presented as an approved suggestion.

## Quotas

Two limits may apply:

- global daily quota;
- daily quota per administrator.

When a quota is reached:

- no new remote request should be sent;
- existing cached results may remain available;
- core Inbox functions continue to work without AI.

## Recommended troubleshooting sequence

1. Open Inbox diagnostics.
2. Check IMAP and SMTP separately.
3. Review the latest synchronisation log.
4. Check attachment errors.
5. Run one manual synchronisation.
6. Open AI diagnostics when the problem concerns analysis.
7. Check quotas and provider status.
8. Review Moodle logs.
9. Run the plugin CLI tests.

## Avoid

Do not:

- manually modify synchronisation cursors;
- delete provider keys;
- empty Inbox tables to fix duplicates;
- copy passwords into logs;
- disable AI output validation;
- run concurrent synchronisations;
- send an AI suggestion without human review.

## Final validation

After applying a correction:

- IMAP must connect successfully;
- SMTP must connect successfully;
- synchronisation must complete without blocking errors;
- a new email must be imported exactly once;
- replies must be stored in conversation history;
- attachments must only be accessible to authorised users;
- AI diagnostics must detect the configured provider;
- AI suggestions must remain editable before sending.