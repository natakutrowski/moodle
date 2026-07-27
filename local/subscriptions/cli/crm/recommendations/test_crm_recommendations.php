<?php

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\crm\intelligence\core\UserIntelligenceBuilder;
use local_subscriptions\crm\intelligence\recommendations\RecommendationEngine;
use local_subscriptions\crm\intelligence\recommendations\repositories\RecommendationRepository;
use local_subscriptions\crm\intelligence\recommendations\services\RecommendationLifecycleService;
use local_subscriptions\crm\intelligence\recommendations\services\RecommendationPersistenceService;
use local_subscriptions\crm\intelligence\recommendations\services\RecommendationContextBuilder;

[$options, $unrecognized] = cli_get_params(
    [
        'help' => false,
        'userid' => 0,
        'persist' => false,
        'list' => false,
        'expire' => false,
        'limit' => 20,
    ],
    [
        'h' => 'help',
        'u' => 'userid',
        'p' => 'persist',
        'l' => 'list',
    ]
);

if ($unrecognized) {
    cli_error(
        'Unknown options: ' .
        implode(', ', $unrecognized)
    );
}

if (!empty($options['help'])) {
    echo <<<HELP
CRM Recommendation Engine diagnostic.

Options:
--userid=ID     Generate recommendations for one Moodle user.
--persist       Persist the generated recommendations.
--list          List active persistent recommendations.
--expire        Expire recommendations whose validity ended.
--limit=N       Maximum number of persistent recommendations to list.
-h, --help      Display this help.

Examples:
php local/subscriptions/cli/crm/recommendations/test_crm_recommendations.php --userid=97
php local/subscriptions/cli/crm/recommendations/test_crm_recommendations.php --userid=97 --persist
php local/subscriptions/cli/crm/recommendations/test_crm_recommendations.php --list
php local/subscriptions/cli/crm/recommendations/test_crm_recommendations.php --expire

HELP;

    exit(0);
}

$requiredtables = [
    'local_subscriptions_recommendation',
    'local_subscriptions_recommendation_history',
];

foreach ($requiredtables as $tablename) {
    $exists = $DB->get_manager()->table_exists(
        new xmldb_table($tablename)
    );

    echo $tablename .
        ': ' .
        ($exists ? 'available' : 'missing') .
        PHP_EOL;

    if (!$exists) {
        cli_error(
            'Recommendation tables are unavailable. Run the Moodle upgrade first.'
        );
    }
}

if (!empty($options['expire'])) {
    $expiredcount =
        (new RecommendationLifecycleService())
            ->expire_due();

    echo 'Expired recommendations: ' .
        $expiredcount .
        PHP_EOL;
}

if (!empty($options['list'])) {
    $limit = max(
        1,
        min(
            1000,
            (int)$options['limit']
        )
    );

    $records =
        (new RecommendationRepository())
            ->get_active($limit);

    echo json_encode(
        array_values($records),
        JSON_PRETTY_PRINT |
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    ) . PHP_EOL;
}

$userid = (int)$options['userid'];

if ($userid <= 0) {
    if (
        empty($options['list']) &&
        empty($options['expire'])
    ) {
        cli_error(
            'Provide --userid, --list or --expire.'
        );
    }

    exit(0);
}

$user = $DB->get_record(
    'user',
    [
        'id' => $userid,
        'deleted' => 0,
    ],
    '*',
    MUST_EXIST
);

$intelligence =
    (new UserIntelligenceBuilder())
        ->build_for_user($user);

$context = (new RecommendationContextBuilder())->build(
    userid: $userid,
    snapshot: $intelligence->snapshot,
    leadscore: $intelligence->leadScore,
    opportunities: $intelligence->opportunities
);

$engineresult =
    (new RecommendationEngine())
        ->generate($context);

echo json_encode(
    $engineresult->to_object(),
    JSON_PRETTY_PRINT |
    JSON_UNESCAPED_UNICODE |
    JSON_UNESCAPED_SLASHES
) . PHP_EOL;

if (!empty($options['persist'])) {
    $persistenceresult =
        (new RecommendationPersistenceService())
            ->persist($engineresult);

    echo PHP_EOL .
        'Persistence:' .
        PHP_EOL;

    echo json_encode(
        $persistenceresult->to_object(),
        JSON_PRETTY_PRINT |
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    ) . PHP_EOL;
}