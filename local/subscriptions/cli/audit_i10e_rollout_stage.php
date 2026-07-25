<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
use local_subscriptions\commerce\rollout\CommerceRolloutGuard;
use local_subscriptions\commerce\rollout\CommerceRolloutStageEvaluator;
[$options] = cli_get_params(['strict' => false], ['h' => 'help']);
$state=(new CommerceRolloutGuard())->state(); $evaluator=new CommerceRolloutStageEvaluator(); $violations=$evaluator->violations($state);
cli_writeln('== I10E rollout stage =='); cli_writeln('current stage: ' . $evaluator->current($state));
foreach ($state as $key=>$enabled) cli_writeln(sprintf('  %-24s %s',$key,$enabled?'enabled':'disabled'));
foreach ($violations as $violation) cli_writeln('  [WARN] '.$violation);
if (!empty($options['strict']) && $violations!==[]) cli_error('Unsafe rollout ordering detected.');
cli_writeln($violations===[]?'[OK] Rollout order is coherent.':'[WARN] Review rollout order before proceeding.');
