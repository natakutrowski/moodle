# Using the CRM Inbox

The CRM Inbox centralises emails received by `support@campusfr.fr`.

## External contacts

A sender does not need to have a CampusFR account.

The conversation is stored with an external contact. If the person later creates an account with the same email address, the CRM can automatically attach the previous conversation history.

A locked manual match is never replaced automatically.

## Statuses

- **Open**: an action is required.
- **Pending**: waiting for the contact or another party.
- **Resolved**: the request has been handled.
- **Closed**: the conversation is complete.
- **Spam**: the message is not a legitimate request.

## Priorities

Use high and urgent priorities only when a request requires prompt handling.

## Assignment

A conversation can be assigned to:

- an administrator;
- a team;
- an administrator who belongs to a team.

Customer matching and administrative assignment are separate concepts.

## Replying

You can save a draft or send the reply directly from the CRM.

Replies are sent from `support@campusfr.fr`.

## Attachments

Attachments are copied to Moodle File API and remain protected by the Inbox read capability.

## Archiving and deletion

- **Local deletion**: hides the conversation in the CRM only.
- **Archive**: moves the provider message to the archive folder.
- **Trash**: moves the provider message to the trash folder.

No permanent remote deletion is performed automatically.

## Diagnostics

The diagnostics page checks:

- the PHP IMAP extension;
- credentials;
- IMAP connectivity;
- SMTP connectivity;
- database tables;
- folders;
- synchronisation errors;
- failed attachments.