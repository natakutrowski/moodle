<?php
declare(strict_types=1);

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\catalog\persistence\CommerceCatalogHydrator;
use local_subscriptions\commerce\catalog\repository\CommerceProductEntitlementRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductRepository;
use local_subscriptions\commerce\catalog\service\CommerceEffectiveEntitlementResolver;
use local_subscriptions\commerce\grant\CommerceManualProductGrantService;
use local_subscriptions\commerce\storefront\ownership\CommerceStorefrontOwnershipResolver;

global $DB;

const N109_SCOPE_ID = 13;
const N109_COURSE_ID = 13;
const N109_TARGET_SKU = 'SUB.PLAN.30';
const N109_CONFIRM_TOKEN = 'ALLLEVELS-A2-LIFETIME';

[$options, $unrecognised] = cli_get_params(
    [
        'help' => false,
        'execute' => false,
        'confirm' => '',
        'verbose' => false,
        'userid' => 0,
    ],
    [
        'h' => 'help',
    ]
);

if ($unrecognised) {
    $unrecognised = implode(PHP_EOL . '  ', $unrecognised);
    cli_error("Unrecognised options:" . PHP_EOL . "  " . $unrecognised);
}

if (!empty($options['help'])) {
    echo <<<HELP
CampusFR Commerce N10.9 — AllLevels A2 lifetime migration

This operation is SILENT:
  - it does not call CommerceGrantAccessMailService;
  - it does not call local_subscriptions\\mailer;
  - it does not queue a Commerce grant campaign;
  - it grants the Native product directly through CommerceManualProductGrantService.

Default mode is DRY-RUN and writes nothing.

Usage:
  php local/subscriptions/cli/commerce/operations/migrate_alllevels_a2_lifetime.php

Single-user validation:
  php local/subscriptions/cli/commerce/operations/migrate_alllevels_a2_lifetime.php --userid=68 --verbose

Execute for ONE user only (does NOT modify AccessScope #13 or plan entitlements):
  php local/subscriptions/cli/commerce/operations/migrate_alllevels_a2_lifetime.php     --userid=68 --execute --confirm=ALLLEVELS-A2-LIFETIME

Execute the full migration after validating the global dry-run:
  php local/subscriptions/cli/commerce/operations/migrate_alllevels_a2_lifetime.php \
    --execute --confirm=ALLLEVELS-A2-LIFETIME

Options:
  --execute       Apply grants, then remove course #13 from AllLevels.
  --confirm=...   Required safety token for --execute.
  --userid=ID     Restrict dry-run/execute to one eligible Moodle user.
                  In single-user execute mode, only the Native lifetime grant is applied;
                  AccessScope #13 and plan entitlements are NEVER modified.
  --verbose       Print every eligible user and source subscription.
  -h, --help      Show this help.

HELP;
    exit(0);
}

$execute = !empty($options['execute']);
$verbose = !empty($options['verbose']);
$targetuserid = (int)($options['userid'] ?? 0);
$singleusermode = $targetuserid > 0;

if ($execute && (string)$options['confirm'] !== N109_CONFIRM_TOKEN) {
    cli_error(
        'Execution refused. Re-run with --execute --confirm=' . N109_CONFIRM_TOKEN
    );
}

echo "============================================================\n";
echo " CampusFR Commerce N10.9 — AllLevels → A2 lifetime\n";
echo "============================================================\n";
echo $execute
    ? "MODE: EXECUTE\n"
    : "MODE: DRY-RUN (no write)\n";
echo $singleusermode
    ? "SCOPE: SINGLE USER #{$targetuserid} — global scope cleanup disabled\n"
    : "SCOPE: FULL HISTORICAL MIGRATION\n";
echo "MAILS: SILENT — no Commerce mail service is invoked\n\n";

// -----------------------------------------------------------------------------
// 1. Validate fixed business objects.
// -----------------------------------------------------------------------------
$scope = $DB->get_record(
    'subscription_access_scope',
    ['id' => N109_SCOPE_ID],
    '*',
    MUST_EXIST
);
$course = $DB->get_record(
    'course',
    ['id' => N109_COURSE_ID],
    'id,fullname',
    MUST_EXIST
);
$product = $DB->get_record(
    'local_subs_commerce_product',
    ['sku' => N109_TARGET_SKU],
    'id,sku,name,status',
    MUST_EXIST
);

echo "[1/7] Business objects\n";
echo "  Scope:   #" . (int)$scope->id . ' ' . (string)$scope->name . "\n";
echo "  Course:  #" . (int)$course->id . ' ' . format_string((string)$course->fullname) . "\n";
echo "  Product: #" . (int)$product->id . ' ' . (string)$product->sku
    . ' — ' . format_string((string)$product->name)
    . ' [' . (string)$product->status . "]\n";

$grantservice = new CommerceManualProductGrantService($DB);
$admin = get_admin();
$actoruserid = (int)$admin->id;

// Validate the target product structurally, without inventing a beneficiary.
// CommerceManualProductGrantService::plan() validates the beneficiary email,
// so using an arbitrary Moodle account here made the dry-run environment-dependent.
// What matters for this migration is the effective Native entitlement itself.
$hydrator = new CommerceCatalogHydrator();
$productrepository = new CommerceProductRepository($DB, $hydrator);
$entitlementrepository = new CommerceProductEntitlementRepository(
    $DB,
    $hydrator,
    $productrepository
);
$entitlementresolver = new CommerceEffectiveEntitlementResolver(
    $DB,
    $productrepository,
    $entitlementrepository
);

$definitions = $entitlementresolver->resolve_by_product_sku(N109_TARGET_SKU);
$targetcoursegrantfound = false;

foreach ($definitions as $definition) {
    if ($definition->get_type() !== 'course_access') {
        continue;
    }

    $resourcekey = (string)$definition->get_resource_key();
    $configuration = $definition->get_configuration();
    $configuredcourseid = (int)($configuration['courseid'] ?? 0);

    // Native course entitlement resource keys include the access level,
    // e.g. course:13:full. Prefer the explicit courseid configuration and
    // keep the resource-key prefix as a compatibility fallback.
    $matchescourse = $configuredcourseid === N109_COURSE_ID
        || str_starts_with($resourcekey, 'course:' . N109_COURSE_ID . ':')
        || $resourcekey === 'course:' . N109_COURSE_ID;

    if (!$matchescourse) {
        continue;
    }

    $targetcoursegrantfound = true;

    if (!$definition->is_lifetime()) {
        cli_error(
            N109_TARGET_SKU
            . ' does not currently grant lifetime access to course #'
            . N109_COURSE_ID
            . '. Migration aborted.'
        );
    }
}

if (!$targetcoursegrantfound) {
    cli_error(
        N109_TARGET_SKU
        . ' does not resolve to course #' . N109_COURSE_ID
        . '. Migration aborted.'
    );
}

echo "  ✓ Target product resolves structurally to lifetime course access.\n";
if ((string)$product->status !== 'active') {
    echo "  ℹ Target product is inactive: execution will activate it only around each silent grant, then restore its original status immediately.\n";
}
echo "\n";

// -----------------------------------------------------------------------------
// 2. Resolve AllLevels plans and eligible durations.
// -----------------------------------------------------------------------------
echo "[2/7] AllLevels plans\n";

$allplans = $DB->get_records(
    'subscription_plan',
    ['accessscopeid' => N109_SCOPE_ID],
    'id ASC'
);

if ($allplans === []) {
    cli_error('No subscription plans use AccessScope #' . N109_SCOPE_ID . '.');
}

$eligibledurations = ['1year', '3years', 'lifetime'];
$eligibleplans = [];

foreach ($allplans as $plan) {
    $eligible = in_array((string)$plan->duration_key, $eligibledurations, true);
    if ($eligible) {
        $eligibleplans[(int)$plan->id] = $plan;
    }

    echo sprintf(
        "  %s #%d %-45s duration=%s\n",
        $eligible ? 'ELIGIBLE' : 'EXCLUDED',
        (int)$plan->id,
        (string)$plan->name,
        (string)$plan->duration_key
    );
}

if ($eligibleplans === []) {
    cli_error('No 1-year, 3-year or lifetime plan was found on AccessScope #13.');
}
echo "\n";

// -----------------------------------------------------------------------------
// 3. Resolve historical beneficiaries.
// -----------------------------------------------------------------------------
// "Historically subscribed" deliberately includes customers whose paid access
// has since expired/cancelled/replaced/suspended. Failed/pending/error/queued
// records are excluded because they do not prove a delivered historical access.
echo "[3/7] Historical beneficiaries\n";

$historicalstatuses = [
    'active',
    'inactive',
    'expired',
    'replaced',
    'canceled',
    'cancelled',
    'paid',
    'completed',
    'suspended',
];

[$plansql, $planparams] = $DB->get_in_or_equal(
    array_keys($eligibleplans),
    SQL_PARAMS_NAMED,
    'plan'
);
[$statussql, $statusparams] = $DB->get_in_or_equal(
    $historicalstatuses,
    SQL_PARAMS_NAMED,
    'status'
);

$userfilter = '';
$userparams = [];
if ($singleusermode) {
    $userfilter = ' AND us.userid = :targetuserid';
    $userparams['targetuserid'] = $targetuserid;
}

$sql = "SELECT us.id AS subscriptionid,
               us.userid,
               us.planid,
               us.status,
               us.start_date,
               us.end_date,
               us.creation_date,
               u.firstname,
               u.lastname,
               u.email
          FROM {user_subscription} us
          JOIN {user} u ON u.id = us.userid
         WHERE us.planid {$plansql}
           AND us.status {$statussql}
           AND u.deleted = 0
           {$userfilter}
      ORDER BY us.userid ASC, us.creation_date ASC, us.id ASC";

$subscriptions = $DB->get_records_sql(
    $sql,
    $planparams + $statusparams + $userparams
);

$beneficiaries = [];
foreach ($subscriptions as $subscription) {
    $userid = (int)$subscription->userid;
    if (!isset($beneficiaries[$userid])) {
        $beneficiaries[$userid] = [
            'userid' => $userid,
            'firstname' => (string)$subscription->firstname,
            'lastname' => (string)$subscription->lastname,
            'email' => (string)$subscription->email,
            'sources' => [],
        ];
    }

    $beneficiaries[$userid]['sources'][] = [
        'subscriptionid' => (int)$subscription->subscriptionid,
        'planid' => (int)$subscription->planid,
        'planname' => (string)$eligibleplans[(int)$subscription->planid]->name,
        'status' => (string)$subscription->status,
    ];
}

echo '  Historical subscription rows: ' . count($subscriptions) . "\n";
echo '  Unique beneficiaries:         ' . count($beneficiaries) . "\n\n";

if ($beneficiaries === []) {
    if ($singleusermode) {
        cli_error(
            'User #' . $targetuserid
            . ' is not an eligible historical beneficiary of the selected AllLevels plans.'
        );
    }
    cli_error('No historical beneficiary matched the migration criteria.');
}

// -----------------------------------------------------------------------------
// 4. Build idempotent grant plan.
// -----------------------------------------------------------------------------
echo "[4/7] Grant plan (silent)\n";

$ownership = new CommerceStorefrontOwnershipResolver($DB);
$togrant = [];
$alreadyowns = [];

foreach ($beneficiaries as $userid => $beneficiary) {
    if ($ownership->owns($userid, N109_TARGET_SKU)) {
        $alreadyowns[$userid] = $beneficiary;
        continue;
    }
    $togrant[$userid] = $beneficiary;
}

echo '  Already owns ' . N109_TARGET_SKU . ': ' . count($alreadyowns) . "\n";
echo '  New silent grants required:   ' . count($togrant) . "\n";

if ($verbose) {
    foreach ($beneficiaries as $userid => $beneficiary) {
        $action = isset($alreadyowns[$userid]) ? 'SKIP_ALREADY_OWNS' : 'GRANT';
        echo sprintf(
            "  [%s] #%d %s %s <%s>\n",
            $action,
            $userid,
            $beneficiary['firstname'],
            $beneficiary['lastname'],
            $beneficiary['email']
        );
        foreach ($beneficiary['sources'] as $source) {
            echo sprintf(
                "      subscription #%d — plan #%d %s — %s\n",
                $source['subscriptionid'],
                $source['planid'],
                $source['planname'],
                $source['status']
            );
        }
    }
}
echo "\n";

// -----------------------------------------------------------------------------
// 5. Preview scope + explicit entitlement cleanup.
// -----------------------------------------------------------------------------
echo "[5/7] Future AllLevels access cleanup\n";
if ($singleusermode) {
    echo "  SINGLE-USER MODE: cleanup is previewed only and will NOT be applied.\n";
}

$scopeids = preg_split(
    '/[\s,;]+/',
    (string)$scope->course_ids,
    -1,
    PREG_SPLIT_NO_EMPTY
);
$scopeids = array_values(array_unique(array_map('intval', $scopeids)));
$scopecontainscourse = in_array(N109_COURSE_ID, $scopeids, true);

$allplanids = array_map('intval', array_keys($allplans));
[$allplansql, $allplanparams] = $DB->get_in_or_equal(
    $allplanids,
    SQL_PARAMS_NAMED,
    'allplan'
);

$explicitentitlements = $DB->get_records_select(
    'subscription_plan_entitlement',
    "planid {$allplansql} AND courseid = :courseid",
    $allplanparams + ['courseid' => N109_COURSE_ID],
    'planid ASC, id ASC'
);

echo '  Scope #13 currently contains course #13: '
    . ($scopecontainscourse ? 'YES' : 'NO') . "\n";
echo '  Explicit plan entitlements for course #13: '
    . count($explicitentitlements) . "\n";

if ($explicitentitlements !== []) {
    echo "  Note: these explicit entitlements must also be removed.\n";
    echo "        Runtime prefers explicit plan entitlements over scope fallback.\n";
}
echo "\n";

if (!$execute) {
    echo "[6/7] DRY-RUN result\n";
    echo "  No database row changed.\n";
    echo "  No Moodle enrolment changed.\n";
    echo "  No email was queued or sent by this script.\n";
    echo "\nRe-run with:\n";
    if ($singleusermode) {
        echo "  php local/subscriptions/cli/commerce/operations/migrate_alllevels_a2_lifetime.php \\\n";
        echo "    --userid=" . $targetuserid . " --execute --confirm=" . N109_CONFIRM_TOKEN . "\n";
        echo "  (single-user execute grants only; no scope/entitlement cleanup)\n";
    } else {
        echo "  php local/subscriptions/cli/commerce/operations/migrate_alllevels_a2_lifetime.php \\\n";
        echo "    --execute --confirm=" . N109_CONFIRM_TOKEN . "\n";
    }
    echo "\nOptional full beneficiary listing: --verbose\n";
    echo "\n[7/7] Done ✅\n";
    exit(0);
}

// -----------------------------------------------------------------------------
// 6. Execute grants first. Scope is NOT modified if any grant fails.
// -----------------------------------------------------------------------------
echo "[6/7] Executing silent Native grants\n";

$granted = 0;
$failed = [];

foreach ($togrant as $userid => $beneficiary) {
    $originalproductstatus = (string)$product->status;
    $temporarilyactivated = false;

    try {
        // CommerceManualProductGrantService intentionally refuses inactive products.
        // This historical migration must keep SUB.PLAN.30 commercially inactive, so
        // activate it only for the duration of this one administrative grant.
        if ($originalproductstatus !== 'active') {
            $DB->set_field(
                'local_subs_commerce_product',
                'status',
                'active',
                ['id' => (int)$product->id]
            );
            $temporarilyactivated = true;
        }

        $result = $grantservice->grant(
            $userid,
            (int)$product->id,
            $actoruserid,
            'n10.9_alllevels_historical_a2_lifetime'
        );

        if (!$ownership->owns($userid, N109_TARGET_SKU)) {
            throw new RuntimeException(
                'Post-grant ownership verification failed for user #' . $userid
            );
        }

        // Verify the actual Moodle course enrolment is active and unlimited.
        $enrolment = $DB->get_record_sql(
            "SELECT ue.*
               FROM {user_enrolments} ue
               JOIN {enrol} e ON e.id = ue.enrolid
              WHERE ue.userid = :userid
                AND e.courseid = :courseid
                AND e.enrol = 'manual'
           ORDER BY ue.id ASC",
            [
                'userid' => $userid,
                'courseid' => N109_COURSE_ID,
            ],
            IGNORE_MULTIPLE
        );

        if (!$enrolment) {
            throw new RuntimeException(
                'No manual Moodle enrolment found after grant for user #' . $userid
            );
        }
        if ((int)$enrolment->status !== ENROL_USER_ACTIVE) {
            throw new RuntimeException(
                'Moodle enrolment is not active after grant for user #' . $userid
            );
        }
        if ((int)$enrolment->timeend !== 0) {
            throw new RuntimeException(
                'Moodle enrolment is not lifetime (timeend != 0) for user #' . $userid
            );
        }

        $granted++;
        echo "  GRANTED user #{$userid} <{$beneficiary['email']}>\n";
    } catch (Throwable $exception) {
        $failed[$userid] = $exception->getMessage();
        echo "  FAILED  user #{$userid} <{$beneficiary['email']}> — "
            . $exception->getMessage() . "\n";
    } finally {
        if ($temporarilyactivated) {
            $DB->set_field(
                'local_subs_commerce_product',
                'status',
                $originalproductstatus,
                ['id' => (int)$product->id]
            );
        }
    }
}

$currentproductstatus = (string)$DB->get_field(
    'local_subs_commerce_product',
    'status',
    ['id' => (int)$product->id],
    MUST_EXIST
);
if ($currentproductstatus !== (string)$product->status) {
    cli_error(
        'Safety invariant failed: target product status was not restored to '
        . (string)$product->status
        . '. Current status: '
        . $currentproductstatus
    );
}

if ($failed !== []) {
    echo "\nMigration stopped BEFORE modifying AccessScope #13.\n";
    echo "Successful grants are idempotent and may safely remain in place.\n";
    echo "Fix the failures and re-run the same command.\n";
    cli_error(count($failed) . ' beneficiary grant(s) failed.');
}

if ($singleusermode) {
    echo "\n[7/7] Single-user verification mode complete\n";
    echo "  Target user:                  #" . $targetuserid . "\n";
    echo "  Silent Native grants created: {$granted}\n";
    echo '  Existing target ownerships:   ' . count($alreadyowns) . "\n";
    echo "  AccessScope #13 modified:      NO\n";
    echo "  Plan entitlements modified:    NO\n";
    echo "  Emails queued/sent by script:  0 (no mail service is called)\n";
    echo "\nSingle-user operation completed ✅\n";
    exit(0);
}

// -----------------------------------------------------------------------------
// 7. Only after every eligible beneficiary owns lifetime A2, detach course #13
//    from the AllLevels scope AND from explicit plan entitlements.
// -----------------------------------------------------------------------------
echo "\n[7/7] Removing A2 from future AllLevels grants\n";

$transaction = $DB->start_delegated_transaction();

if ($scopecontainscourse) {
    $newscopeids = array_values(array_filter(
        $scopeids,
        static fn(int $courseid): bool => $courseid !== N109_COURSE_ID
    ));

    $scope->course_ids = implode(',', $newscopeids);
    $scope->last_update = time();
    $DB->update_record('subscription_access_scope', $scope);
}

$deletedentitlements = 0;
if ($explicitentitlements !== []) {
    foreach ($explicitentitlements as $entitlement) {
        $DB->delete_records(
            'subscription_plan_entitlement',
            ['id' => (int)$entitlement->id]
        );
        $deletedentitlements++;
    }
}

$transaction->allow_commit();

echo "  Silent Native grants created: {$granted}\n";
echo '  Existing target ownerships:   ' . count($alreadyowns) . "\n";
echo '  Course removed from scope:    ' . ($scopecontainscourse ? 'YES' : 'ALREADY ABSENT') . "\n";
echo "  Explicit entitlements removed: {$deletedentitlements}\n";
echo "  Emails queued/sent by script:  0 (no mail service is called)\n";
echo "\nMigration completed ✅\n";
