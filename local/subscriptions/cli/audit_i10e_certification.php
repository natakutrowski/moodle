<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../config.php');
use local_subscriptions\commerce\rollout\CommerceRolloutCertification;
use local_subscriptions\commerce\rollout\CommerceRolloutGuard;
(new CommerceRolloutGuard())->assert_safe_configuration();
cli_writeln('== I10E pre-PROD certification checklist ==');
cli_writeln('');
foreach ((new CommerceRolloutCertification())->checklist() as $key => $label) {
    cli_writeln(sprintf('  [ ] %-24s %s', $key, $label));
}
cli_writeln('');
cli_writeln('This checklist is intentionally manual: the CLI does not certify external provider payments by itself.');
cli_writeln('[OK] I10E certification tooling is available.');
