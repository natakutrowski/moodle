<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
use local_subscriptions\commerce\rollout\CommerceShadowRolloutService;
[$options] = cli_get_params(['family' => null, 'ids' => null, 'strict' => false], ['h' => 'help']);
$family = strtolower(trim((string)$options['family']));
$ids = array_values(array_filter(array_map('intval', preg_split('/[,\s]+/', (string)$options['ids']))));
if (!in_array($family, ['digital', 'subscription'], true) || $ids === []) { cli_error('Use --family=digital|subscription --ids=1,2,3'); }
$service = new CommerceShadowRolloutService(); $service->assert_safe_flags();
$report = $service->compare($family, $ids);
cli_writeln('== I10E shadow rollout comparison ==');
foreach ($report->get_results() as $row) {
    cli_writeln(sprintf('  %-12s #%d equal=%s issues=%d repaired=%s', $row['family'], $row['id'],
        $row['equal'] ? 'yes' : 'no', $row['issues'], $row['repaired'] ? 'yes' : 'no'));
}
if (!empty($options['strict']) && !$report->is_equal()) { cli_error('Shadow rollout differences detected.'); }
cli_writeln(sprintf('[OK] Compared %d purchase(s); issues=%d.', count($report->get_results()), $report->get_issue_count()));
