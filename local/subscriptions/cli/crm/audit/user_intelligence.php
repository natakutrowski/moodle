<?php

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

[$options, $unrecognized] = cli_get_params([
    'help' => false,
    'userid' => 0,
], [
    'h' => 'help',
    'u' => 'userid',
]);

if (!empty($options['help']) || empty($options['userid'])) {
    echo "Show CRM intelligence for one user.\n\n";
    echo "Options:\n";
    echo "--userid=97\n";
    exit(0);
}

$user = $DB->get_record('user', ['id' => (int)$options['userid']], '*', MUST_EXIST);

$intelligence = (new \local_subscriptions\crm\intelligence\core\UserIntelligenceBuilder())
    ->build_for_user($user)
    ->to_object();

echo json_encode($intelligence, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;