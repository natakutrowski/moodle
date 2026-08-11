<?php

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\mail\CommerceMailQueueRepository;

[$options, $unrecognised] = cli_get_params(
    [
        'help' => false,
        'execute' => false,
        'mailid' => 0,
        'purchaseid' => 0,
        'type' => '',
        'limit' => 100,
    ],
    [
        'h' => 'help',
    ]
);

if ($unrecognised) {
    cli_error('Unknown options: ' . implode(', ', $unrecognised));
}

if ($options['help']) {
    echo <<<HELP
Retry failed Commerce transactional messages.

Dry-run by default:
  php local/subscriptions/cli/commerce/mail/retry_failed.php

Execute:
  php local/subscriptions/cli/commerce/mail/retry_failed.php --execute

Filters:
  --mailid=ID
  --purchaseid=ID
  --type=TYPE
  --limit=N

HELP;
    exit(0);
}

$repository = new CommerceMailQueueRepository();
$records = [];

if ((int)$options['mailid'] > 0) {
    $record = $repository->find_by_id((int)$options['mailid']);
    if ($record !== null && $record->status === 'failed') {
        $records[] = $record;
    }
} else {
    $records = $repository->get_failed(
        max(1, (int)$options['limit']),
        (int)$options['purchaseid'] > 0 ? (int)$options['purchaseid'] : null,
        trim((string)$options['type']) !== '' ? (string)$options['type'] : null
    );
}

if (!$records) {
    mtrace('[Commerce Mail] No failed message matches the filters.');
    exit(0);
}

$reset = 0;
foreach ($records as $record) {
    mtrace(sprintf(
        '#%d type=%s purchase=%s recipient=%s attempts=%d error=%s',
        $record->id,
        $record->mailtype,
        $record->purchaseid ?? '-',
        $record->recipientemail,
        $record->attemptcount,
        trim((string)$record->lasterror)
    ));
    if ($options['execute'] && $repository->reset_failed((int)$record->id)) {
        $reset++;
    }
}

mtrace($options['execute']
    ? sprintf('[Commerce Mail] Reset %d message(s) to queued.', $reset)
    : sprintf('[Commerce Mail] Dry-run: %d message(s) would be reset.', count($records))
);
