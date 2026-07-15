# CRM Inbox AI Assistance

CRM Inbox AI Assistance helps administrators understand and process support conversations more efficiently.

It provides structured analysis and drafting support while keeping all important decisions under human control.

## Available features

The assistant can provide:

* the main language of the message;
* an urgency level;
* a support category;
* a conversation summary;
* the customer’s main requests;
* unresolved questions;
* key points from the conversation;
* a suggested reply;
* a translation;
* tone adaptation for a draft reply.

Availability depends on the configured AI provider.

The local fallback provider supports only limited heuristic analysis and does not generate complete support replies.

## Human review is mandatory

AI results are suggestions only.

The assistant cannot independently:

* send an email;
* grant access to a course;
* confirm a payment;
* refund a purchase;
* cancel a transaction;
* suspend an account;
* delete an account;
* change a subscription;
* close a sensitive support case.

Every suggested reply must be reviewed, edited when necessary, and explicitly approved by an administrator before sending.

The presence of an AI-generated suggestion does not mean that the information has been verified.

## Language detection

The assistant can identify the main language used in the latest customer message.

Possible results include:

* French;
* English;
* Russian;
* Ukrainian;
* German;
* Spanish;
* Italian;
* Portuguese;
* unknown.

Language detection is based on the message content.

A low confidence score means that the message may contain several languages, very little text, or ambiguous content.

The detected language does not automatically change the language of the CRM interface.

## Urgency classification

The assistant can classify a request as:

* **Low**
* **Normal**
* **High**
* **Critical**

Urgency is an analytical indication, not an automatic priority decision.

Examples of potentially high or critical signals include:

* a confirmed payment without access;
* suspected fraud;
* a duplicated charge;
* a compromised account;
* an imminent examination or deadline;
* a legal or security issue.

The presence of words such as “urgent” does not automatically make a request critical.

Administrators must always verify the actual CRM data before changing the conversation priority.

## Categorisation

The assistant can propose one primary category and optional secondary categories.

Available categories may include:

* payment;
* access;
* subscription;
* technical issue;
* course content;
* user account;
* refund;
* billing;
* commercial request;
* feedback;
* spam;
* other.

Categorisation helps organise support work but does not trigger any administrative action.

A payment-related category does not prove that a payment exists or has succeeded.

## Conversation summary

The summary is generated from the conversation history available in the CRM Inbox.

It may include:

* a concise overview;
* important facts;
* customer requests;
* unresolved questions;
* the most recent developments.

The assistant distinguishes customer messages from support replies whenever possible.

It must not invent:

* payment status;
* subscription status;
* access status;
* refund approval;
* account activity;
* actions that were not recorded in the CRM.

When a conversation is very long, older content may be truncated before analysis.

## Suggested replies

The assistant can draft a proposed reply in French, English, or Russian.

Available tones may include:

* professional;
* friendly;
* empathetic;
* concise.

A suggested reply must always remain editable.

The assistant must not:

* promise an access activation that has not occurred;
* confirm a payment that has not been verified;
* promise a refund;
* claim that an account has been modified;
* expose internal CRM identifiers;
* expose provider identifiers;
* include private administrator notes;
* send the message automatically.

When important information is missing, the draft should ask the customer for clarification instead of inventing an answer.

## Translation

The assistant can translate a message or draft reply.

Translation must preserve:

* names;
* dates;
* amounts;
* currencies;
* links;
* product names;
* account references;
* conditions and warnings.

Translation must not change the meaning of the original message.

The translated text should still be reviewed before use, especially for legal, payment, access, or refund matters.

## Tone adaptation

Tone adaptation rewrites an existing draft without changing its factual content.

It may make the text:

* more professional;
* more friendly;
* more empathetic;
* more concise.

Tone adaptation must not:

* add a promise;
* remove an important warning;
* modify a date;
* modify an amount;
* change a link;
* change a product name;
* imply that an administrative action has already been completed.

## CRM context

The assistant may receive a limited CRM context to improve the quality of its suggestions.

This context can include:

* the conversation subject;
* the conversation status;
* the current priority;
* the number of messages;
* whether the contact is matched to a CampusFR user;
* whether the conversation is assigned;
* relevant verified CRM facts.

The following data must never be included:

* passwords;
* tokens;
* secret keys;
* credential identifiers;
* raw authentication headers;
* provider UIDs;
* bank card details;
* CVV codes;
* complete payment credentials.

Internal Moodle user IDs should not be transmitted when they are not required.

## Confidence scores

Some AI results include a confidence score.

A higher score means that the provider found stronger evidence for its result.

A confidence score does not guarantee accuracy.

Administrators must remain particularly careful when:

* the message is very short;
* the conversation contains several languages;
* important CRM information is missing;
* the customer’s wording is ambiguous;
* the provider returns warnings;
* the result concerns payment, access, refunds, or account security.

## Quotas

AI usage may be limited by:

* a global daily quota;
* a daily quota per administrator;
* provider limits;
* configured rate limits;
* cost-control policies.

When a quota is reached, existing Inbox features remain available, but new AI analyses may be temporarily blocked.

Cached results may still be reused when valid.

## Automatic analysis

Automatic analysis can be enabled for open or pending conversations.

When enabled, the scheduled task can prepare:

* language detection;
* urgency;
* category;
* summary.

Automatic analysis must not:

* generate and send a reply;
* modify a conversation status;
* change a priority;
* assign a conversation;
* perform a refund;
* grant access;
* modify a user account.

Automatic analysis should remain disabled until the provider, quotas, privacy rules, and costs have been reviewed.

## Local fallback provider

The fallback provider works without an external AI service.

It can perform limited analysis using local keyword heuristics.

It may support:

* basic language detection;
* basic urgency estimation;
* basic categorisation;
* limited request extraction;
* a very limited summary.

It intentionally does not generate complete support replies or reliable translations.

An unavailable reply suggestion with the fallback provider is expected behaviour.

## Stored results and cache

AI results may be stored in the CRM to:

* avoid repeating the same analysis;
* reduce provider costs;
* improve performance;
* keep an audit trail;
* support diagnostics.

A cached result is reused only when the request content, capability, prompt version, and relevant context still match.

A new message changes the conversation content and should produce a new analysis fingerprint.

Administrators can force a refresh when necessary.

## Privacy and data protection

Before using an external provider, verify:

* the provider’s data-processing terms;
* data retention;
* processing location;
* GDPR compatibility;
* whether prompts are used for model training;
* whether zero-retention options are available;
* whether a data-processing agreement is required.

Only useful and proportionate data should be sent.

Support messages may contain personal, financial, educational, or sensitive information. They must therefore be handled cautiously.

## Diagnostics

The AI diagnostics page can verify:

* that the AI results table exists;
* that the fallback provider is available;
* that the orchestrator can be constructed;
* current daily usage;
* administrator usage;
* recent failures;
* automatic analysis status.

A successful diagnostic does not guarantee that an external provider is correctly configured.

## Good administrative practice

Before using an AI result:

1. read the original customer message;
2. check the conversation history;
3. verify the related user;
4. verify payment and subscription data;
5. review warnings and confidence;
6. edit the proposed reply;
7. confirm that no unsupported promise is included;
8. send the reply manually.

AI Assistance is a productivity tool, not an autonomous support agent.
