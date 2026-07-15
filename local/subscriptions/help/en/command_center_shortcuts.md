# Command Center Search and Shortcuts

The **Command Center** gives administrators fast access to CRM pages, user searches, payment tools, inbox views, and Help Center content.

Commands are supplied by internal providers that share a common contract.

## Opening the Command Center

Common shortcuts are:

```text
Ctrl + K
Cmd + K
```

The shortcut must not interfere with standard Moodle form controls.

## Searching commands

Type a few characters to filter commands.

Examples:

```text
dashboard
users
payments
inbox
help
```

Aliases and prefixes may also be supported.

## Help prefix

When enabled, this query:

```text
> help
```

lists Help Center categories or articles.

The plain query:

```text
help
```

may also return help results when the provider supports unprefixed searches.

## Keyboard navigation

Recommended behavior:

- `Arrow Down`: next result;
- `Arrow Up`: previous result;
- `Enter`: open or execute;
- `Escape`: close;
- `Tab`: accessible focus navigation;
- `Ctrl/Cmd + K`: toggle.

Focus must remain visible.

## Recent commands

Recent commands may be stored in browser local storage.

The **Clear recent commands** action should remove only local history.

After clearing, the initial state should refresh immediately.

## Providers

A provider should:

- accept a normalized query;
- return structured results;
- enforce permissions;
- create safe URLs;
- avoid unnecessary heavy queries;
- avoid returning arbitrary untrusted HTML.

## User commands

User-related commands may open:

- CRM profiles;
- User Explorer;
- conversations;
- recent purchase history;
- email-based search results.

All results must respect the current administrator capabilities.

## Help commands

The Help Center provider should read article metadata and translated Markdown files.

A validation CLI should report missing language files.

## Security

The Command Center does not grant additional privileges.

Every destination must enforce its own access checks through:

- `require_login()`;
- `AdminSecurity`;
- Moodle capabilities;
- sesskeys for changes.

Hiding a command is not a security boundary.

## Troubleshooting

When a command is missing:

1. purge caches;
2. confirm provider registration;
3. check aliases;
4. test prefixed and plain searches;
5. verify capabilities;
6. inspect the AJAX response;
7. check JavaScript errors;
8. confirm the result receives a positive score.

## Writing good commands

Prefer short, clear, translatable labels such as:

```text
Open CRM Dashboard
Search users
View digital purchases
Open CRM Inbox
Open Help Center
```
