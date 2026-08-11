<?php

declare(strict_types=1);

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\catalog\resolution\CommerceLegacyStorefrontProductResolver;
use local_subscriptions\commerce\course\storefront\CommerceCourseStorefrontTargetResolver;

[$options] = cli_get_params(
    [
        'courseid' => 0,
        'accesslevel' => 'all',
        'sku' => '',
        'planid' => 0,
        'json' => false,
        'help' => false,
    ],
    [
        'h' => 'help',
    ]
);

if (!empty($options['help']) || (int)$options['courseid'] <= 0) {
    echo "Diagnose the complete Course -> Legacy -> Native Commerce chain.\n\n";
    echo "This command is strictly read-only.\n\n";
    echo "Required:\n";
    echo "  --courseid=ID\n\n";
    echo "Optional:\n";
    echo "  --accesslevel=all|grammar|full|subscriber\n";
    echo "  --sku=SKU                Also inspect one specific Native product\n";
    echo "  --planid=ID              Also inspect one specific Legacy plan\n";
    echo "  --json                    JSON output\n";
    echo "  -h, --help\n";
    exit(!empty($options['help']) ? 0 : 1);
}

$courseid = (int)$options['courseid'];
$accesslevel = strtolower(trim((string)$options['accesslevel']));
$sku = strtoupper(trim((string)$options['sku']));
$planid = (int)$options['planid'];

if (!in_array($accesslevel, ['all', 'grammar', 'full', 'subscriber'], true)) {
    cli_error('Invalid --accesslevel value.');
}

$course = $DB->get_record(
    'course',
    ['id' => $courseid],
    'id,fullname,shortname,visible',
    MUST_EXIST
);

/**
 * Decode one JSON value without throwing.
 *
 * @return array<string,mixed>
 */
$decode = static function (?string $json): array {
    $decoded = json_decode((string)$json, true);
    return is_array($decoded) ? $decoded : [];
};

/**
 * Extract one Native course entitlement definition.
 *
 * @return array{courseid:int,accesslevel:string}
 */
$nativeentitlement = static function (
    \stdClass $record
) use ($decode): array {
    $resolvedcourseid = 0;
    $resolvedaccesslevel = '';

    if (preg_match(
        '/^course:(\d+)(?::([a-z0-9_-]+))?$/i',
        trim((string)$record->resourcekey),
        $matches
    )) {
        $resolvedcourseid = (int)$matches[1];
        $resolvedaccesslevel = strtolower(
            trim((string)($matches[2] ?? ''))
        );
    }

    $configuration = $decode(
        (string)($record->configurationjson ?? '')
    );

    if ($resolvedcourseid <= 0) {
        $resolvedcourseid = (int)(
            $configuration['courseid']
            ?? $configuration['course_id']
            ?? 0
        );
    }

    if ($resolvedaccesslevel === '') {
        $resolvedaccesslevel = strtolower(trim((string)(
            $configuration['accesslevel']
            ?? $configuration['access_level']
            ?? ''
        )));
    }

    if ($resolvedcourseid > 0 && $resolvedaccesslevel === '') {
        $resolvedaccesslevel = 'full';
    }

    return [
        'courseid' => $resolvedcourseid,
        'accesslevel' => $resolvedaccesslevel,
    ];
};

/**
 * Resolve direct and inverse course custom-field relations.
 *
 * @return array{
 *   courseids:int[],
 *   fields:array<int,array<string,mixed>>
 * }
 */
$relatedcourses = static function (
    int $initialcourseid
) use ($DB): array {
    $courseids = [$initialcourseid => $initialcourseid];
    $fieldrows = [];

    $fields = $DB->get_records_select(
        'customfield_field',
        "shortname IN ('realcourseid', 'trialcourseid')",
        [],
        'id ASC',
        'id,shortname'
    );

    if ($fields === []) {
        return [
            'courseids' => array_values($courseids),
            'fields' => [],
        ];
    }

    $fieldids = array_map(
        static fn(\stdClass $field): int => (int)$field->id,
        array_values($fields)
    );

    [$fieldsql, $fieldparams] = $DB->get_in_or_equal(
        $fieldids,
        SQL_PARAMS_NAMED,
        'customfield'
    );

    // Two passes are enough for direct + inverse Trial/real-course pairs.
    for ($pass = 0; $pass < 2; $pass++) {
        [$instancesql, $instanceparams] = $DB->get_in_or_equal(
            array_values($courseids),
            SQL_PARAMS_NAMED,
            'instance' . $pass
        );
        [$valuesql, $valueparams] = $DB->get_in_or_equal(
            array_values($courseids),
            SQL_PARAMS_NAMED,
            'value' . $pass
        );

        $records = $DB->get_records_sql(
            "SELECT d.id,
                    d.instanceid,
                    d.value,
                    f.shortname
               FROM {customfield_data} d
               JOIN {customfield_field} f ON f.id = d.fieldid
              WHERE d.fieldid {$fieldsql}
                AND (
                    d.instanceid {$instancesql}
                    OR CAST(d.value AS UNSIGNED) {$valuesql}
                )
           ORDER BY d.id ASC",
            $fieldparams + $instanceparams + $valueparams
        );

        foreach ($records as $record) {
            $instanceid = (int)$record->instanceid;
            $value = (int)$record->value;

            if ($instanceid > 0) {
                $courseids[$instanceid] = $instanceid;
            }
            if ($value > 0) {
                $courseids[$value] = $value;
            }

            $fieldrows[(int)$record->id] = [
                'id' => (int)$record->id,
                'shortname' => (string)$record->shortname,
                'instanceid' => $instanceid,
                'value' => $value,
            ];
        }
    }

    return [
        'courseids' => array_values($courseids),
        'fields' => array_values($fieldrows),
    ];
};

$relations = $relatedcourses($courseid);
$courseids = $relations['courseids'];

[$coursesql, $courseparams] = $DB->get_in_or_equal(
    $courseids,
    SQL_PARAMS_NAMED,
    'course'
);

$relatedcourserecords = array_values($DB->get_records_select(
    'course',
    "id {$coursesql}",
    $courseparams,
    'id ASC',
    'id,fullname,shortname,visible'
));

$legacyentitlements = array_values($DB->get_records_sql(
    "SELECT e.id AS entitlementid,
            e.planid,
            e.courseid,
            e.accesslevel,
            e.roleshortname,
            e.groupname,
            e.priority,
            p.name AS planname,
            p.is_active AS planactive,
            p.is_trial AS planistrial,
            p.accessscopeid
       FROM {subscription_plan_entitlement} e
       JOIN {subscription_plan} p ON p.id = e.planid
      WHERE e.courseid {$coursesql}
   ORDER BY e.courseid ASC,
            e.accesslevel ASC,
            e.priority DESC,
            e.planid ASC",
    $courseparams
));

if ($planid > 0) {
    $specificplan = $DB->get_record(
        'subscription_plan',
        ['id' => $planid],
        '*',
        IGNORE_MISSING
    );

    if ($specificplan) {
        $alreadyincluded = false;
        foreach ($legacyentitlements as $entitlement) {
            if ((int)$entitlement->planid === $planid) {
                $alreadyincluded = true;
                break;
            }
        }

        if (!$alreadyincluded) {
            $additional = $DB->get_records_sql(
                "SELECT e.id AS entitlementid,
                        e.planid,
                        e.courseid,
                        e.accesslevel,
                        e.roleshortname,
                        e.groupname,
                        e.priority,
                        p.name AS planname,
                        p.is_active AS planactive,
                        p.is_trial AS planistrial,
                        p.accessscopeid
                   FROM {subscription_plan_entitlement} e
                   JOIN {subscription_plan} p ON p.id = e.planid
                  WHERE e.planid = :planid
               ORDER BY e.courseid ASC,
                        e.accesslevel ASC,
                        e.priority DESC",
                ['planid' => $planid]
            );
            $legacyentitlements = array_merge(
                $legacyentitlements,
                array_values($additional)
            );
        }
    }
}

$legacyplanids = [];
foreach ($legacyentitlements as $entitlement) {
    $legacyplanids[(int)$entitlement->planid] = (int)$entitlement->planid;
}
if ($planid > 0) {
    $legacyplanids[$planid] = $planid;
}

$legacyplans = [];
$legacymappings = [];
$deterministicproducts = [];
$legacyresolver = new CommerceLegacyStorefrontProductResolver($DB);

foreach ($legacyplanids as $legacyplanid) {
    $plan = $DB->get_record(
        'subscription_plan',
        ['id' => $legacyplanid],
        '*',
        IGNORE_MISSING
    );
    if (!$plan) {
        continue;
    }

    $prices = array_values($DB->get_records(
        'subscription_plan_price',
        ['planid' => $legacyplanid],
        'currency ASC, id ASC'
    ));

    $mapped = $DB->get_record_sql(
        "SELECT m.id AS mapid,
                m.legacyfamily,
                m.legacytable,
                m.legacyid,
                p.id AS productid,
                p.sku,
                p.name AS productname,
                p.type AS producttype,
                p.status AS productstatus
           FROM {local_subs_commerce_prod_map} m
           JOIN {local_subs_commerce_product} p ON p.id = m.productid
          WHERE m.legacytable = :legacytable
            AND m.legacyid = :legacyid",
        [
            'legacytable' => 'subscription_plan',
            'legacyid' => $legacyplanid,
        ],
        IGNORE_MISSING
    );

    $resolved = $legacyresolver->resolve_subscription_plan($legacyplanid);
    $deterministicsku = 'SUB.PLAN.' . $legacyplanid;
    $deterministic = $DB->get_record(
        'local_subs_commerce_product',
        ['sku' => $deterministicsku],
        '*',
        IGNORE_MISSING
    );

    $legacyplans[] = [
        'plan' => $plan,
        'prices' => $prices,
        'explicitmapping' => $mapped ?: null,
        'resolverproduct' => $resolved ?: null,
        'deterministicsku' => $deterministicsku,
        'deterministicproduct' => $deterministic ?: null,
    ];

    if ($mapped) {
        $legacymappings[] = $mapped;
    }
    if ($deterministic) {
        $deterministicproducts[] = $deterministic;
    }
}

$nativeentitlementrecords = array_values($DB->get_records_sql(
    "SELECT e.id AS entitlementid,
            e.productid,
            e.type AS entitlementtype,
            e.resourcekey,
            e.durationseconds,
            e.quantity,
            e.configurationjson,
            e.sortorder,
            p.sku,
            p.name AS productname,
            p.type AS producttype,
            p.status AS productstatus,
            p.metadatajson AS productmetadatajson,
            p.availablefrom,
            p.availableuntil
       FROM {local_subs_commerce_prod_ent} e
       JOIN {local_subs_commerce_product} p ON p.id = e.productid
   ORDER BY p.id ASC, e.sortorder ASC, e.id ASC"
));

$nativeentitlements = [];
$nativeproductids = [];

foreach ($nativeentitlementrecords as $record) {
    $definition = $nativeentitlement($record);

    $matchescourse = in_array(
        $definition['courseid'],
        $courseids,
        true
    );

    $matchesexplicit = $sku !== ''
        && strtoupper((string)$record->sku) === $sku;

    if (!$matchescourse && !$matchesexplicit) {
        continue;
    }

    $nativeproductids[(int)$record->productid] = (int)$record->productid;

    $nativeentitlements[] = [
        'record' => $record,
        'resolvedcourseid' => $definition['courseid'],
        'resolvedaccesslevel' => $definition['accesslevel'],
        'matchescourse' => $matchescourse,
    ];
}

// Include products referenced by mappings even if their entitlements do not match.
foreach ($legacymappings as $mapping) {
    $nativeproductids[(int)$mapping->productid] =
        (int)$mapping->productid;
}
foreach ($deterministicproducts as $product) {
    $nativeproductids[(int)$product->id] = (int)$product->id;
}

if ($sku !== '') {
    $specificproduct = $DB->get_record(
        'local_subs_commerce_product',
        ['sku' => $sku],
        '*',
        IGNORE_MISSING
    );
    if ($specificproduct) {
        $nativeproductids[(int)$specificproduct->id] =
            (int)$specificproduct->id;
    }
}

$nativeproducts = [];

foreach ($nativeproductids as $nativeproductid) {
    $product = $DB->get_record(
        'local_subs_commerce_product',
        ['id' => $nativeproductid],
        '*',
        IGNORE_MISSING
    );
    if (!$product) {
        continue;
    }

    $prices = array_values($DB->get_records(
        'local_subs_commerce_prod_price',
        ['productid' => $nativeproductid],
        'currency ASC, provider ASC, id ASC'
    ));

    $entitlements = array_values($DB->get_records(
        'local_subs_commerce_prod_ent',
        ['productid' => $nativeproductid],
        'sortorder ASC, id ASC'
    ));

    $maps = array_values($DB->get_records(
        'local_subs_commerce_prod_map',
        ['productid' => $nativeproductid],
        'legacyfamily ASC, id ASC'
    ));

    $translations = array_values($DB->get_records(
        'local_subs_commerce_prod_tr',
        ['productid' => $nativeproductid],
        'language ASC, id ASC'
    ));

    $decodedentitlements = [];
    foreach ($entitlements as $entitlement) {
        $definition = $nativeentitlement($entitlement);

        $decodedentitlements[] = [
            'record' => $entitlement,
            'resolvedcourseid' => $definition['courseid'],
            'resolvedaccesslevel' => $definition['accesslevel'],
            'courseinrequestedfamily' => in_array(
                $definition['courseid'],
                $courseids,
                true
            ),
        ];
    }

    $nativeproducts[] = [
        'product' => $product,
        'metadata' => $decode((string)$product->metadatajson),
        'prices' => $prices,
        'entitlements' => $decodedentitlements,
        'mappings' => $maps,
        'translations' => $translations,
    ];
}

$resolver = CommerceCourseStorefrontTargetResolver::create();

$resolutions = [];
foreach (['grammar', 'full', 'subscriber'] as $level) {
    $diagnostic = $resolver->diagnose([$courseid], $level);

    $resolutions[$level] = [
        'resolvedskus' => $diagnostic['resolvedskus'],
        'url' => $resolver->url([$courseid], $level)->out(false),
    ];
}

$issues = [];

// Grammar-specific consistency checks.
$grammarlegacy = array_values(array_filter(
    $legacyentitlements,
    static fn(\stdClass $record): bool =>
        strtolower((string)$record->accesslevel) === 'grammar'
));

$grammarnative = array_values(array_filter(
    $nativeentitlements,
    static fn(array $entry): bool =>
        $entry['matchescourse']
        && $entry['resolvedaccesslevel'] === 'grammar'
));

if ($grammarlegacy === []) {
    $issues[] = [
        'severity' => 'warning',
        'code' => 'NO_LEGACY_GRAMMAR_ENTITLEMENT',
        'message' =>
            'No Legacy grammar entitlement exists for the requested course family.',
    ];
}

if ($grammarnative === []) {
    $issues[] = [
        'severity' => 'warning',
        'code' => 'NO_NATIVE_GRAMMAR_ENTITLEMENT',
        'message' =>
            'No Native product entitlement resolves to this course family with accesslevel=grammar.',
    ];
}

foreach ($legacyplans as $entry) {
    $plan = $entry['plan'];
    $resolved = $entry['resolverproduct'];

    if ((int)$plan->is_active === 0 && $resolved) {
        $issues[] = [
            'severity' => 'notice',
            'code' => 'INACTIVE_LEGACY_PLAN_WITH_NATIVE_PRODUCT',
            'message' =>
                'Legacy plan #' . $plan->id
                . ' is inactive, while Native product '
                . $resolved->sku . ' still exists.',
        ];
    }

    if (!$entry['explicitmapping'] && $resolved) {
        $issues[] = [
            'severity' => 'notice',
            'code' => 'RECOVERED_MAPPING_ONLY',
            'message' =>
                'Plan #' . $plan->id
                . ' resolves to ' . $resolved->sku
                . ' without an explicit mapping-table row.',
        ];
    }
}

$grammarproductskus = [];
foreach ($nativeproducts as $entry) {
    $product = $entry['product'];
    $producttext = strtoupper(
        (string)$product->sku
        . ' ' . (string)$product->name
        . ' ' . json_encode($entry['metadata'])
    );

    if (str_contains($producttext, 'GRAMMAR')) {
        $grammarproductskus[] = (string)$product->sku;

        $hasmatchingentitlement = false;
        foreach ($entry['entitlements'] as $entitlement) {
            if (
                $entitlement['courseinrequestedfamily']
                && $entitlement['resolvedaccesslevel'] === 'grammar'
            ) {
                $hasmatchingentitlement = true;
                break;
            }
        }

        if (!$hasmatchingentitlement) {
            $issues[] = [
                'severity' => 'warning',
                'code' => 'GRAMMAR_PRODUCT_WITHOUT_MATCHING_ENTITLEMENT',
                'message' =>
                    'Product ' . $product->sku
                    . ' looks like a Grammar product but has no matching '
                    . 'course entitlement for this course family.',
            ];
        }
    }
}

if (
    in_array('COURSE_ACCESS.A2_GRAMMAR', $grammarproductskus, true)
    && in_array('SUB.PLAN.31', $grammarproductskus, true)
) {
    $issues[] = [
        'severity' => 'warning',
        'code' => 'DUPLICATE_GRAMMAR_PRODUCT_MODEL',
        'message' =>
            'Both COURSE_ACCESS.A2_GRAMMAR and SUB.PLAN.31 exist. '
            . 'The intended canonical Native product must be made explicit.',
    ];
}

$result = [
    'readonly' => true,
    'course' => $course,
    'relatedcourses' => $relatedcourserecords,
    'coursecustomfields' => $relations['fields'],
    'requestedaccesslevel' => $accesslevel,
    'specificsku' => $sku,
    'specificplanid' => $planid,
    'legacyentitlements' => $legacyentitlements,
    'legacyplans' => $legacyplans,
    'nativeentitlementsmatchingcourse' => $nativeentitlements,
    'nativeproducts' => $nativeproducts,
    'resolver' => $resolutions,
    'issues' => $issues,
];

if (!empty($options['json'])) {
    echo json_encode(
        $result,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
    ) . PHP_EOL;
    exit(0);
}

$yesno = static fn(bool $value): string => $value ? 'yes' : 'no';
$value = static function ($value): string {
    if ($value === null || $value === '') {
        return 'NONE';
    }
    return (string)$value;
};

echo "============================================================\n";
echo "CampusFR Commerce — Course Product Chain Diagnostic\n";
echo "READ-ONLY\n";
echo "============================================================\n\n";

echo "Course\n";
echo "------\n";
echo sprintf(
    "%s (#%d) shortname=%s visible=%s\n",
    (string)$course->fullname,
    (int)$course->id,
    (string)$course->shortname,
    $yesno((bool)$course->visible)
);
echo "Related course IDs: " . implode(', ', $courseids) . "\n";

if ($relations['fields'] !== []) {
    echo "Course relation fields:\n";
    foreach ($relations['fields'] as $field) {
        echo sprintf(
            "  - %s: instance=%d value=%d\n",
            $field['shortname'],
            $field['instanceid'],
            $field['value']
        );
    }
}

echo "\nLegacy entitlements\n";
echo "-------------------\n";
if ($legacyentitlements === []) {
    echo "NONE\n";
} else {
    foreach ($legacyentitlements as $entry) {
        echo sprintf(
            "- entitlement=%d plan=%s (#%d) active=%s "
            . "course=%d accesslevel=%s role=%s priority=%d\n",
            (int)$entry->entitlementid,
            (string)$entry->planname,
            (int)$entry->planid,
            $yesno((bool)$entry->planactive),
            (int)$entry->courseid,
            (string)$entry->accesslevel,
            (string)$entry->roleshortname,
            (int)$entry->priority
        );
    }
}

echo "\nLegacy plans and Native mappings\n";
echo "--------------------------------\n";
if ($legacyplans === []) {
    echo "NONE\n";
} else {
    foreach ($legacyplans as $entry) {
        $plan = $entry['plan'];
        $mapping = $entry['explicitmapping'];
        $resolved = $entry['resolverproduct'];
        $deterministic = $entry['deterministicproduct'];

        echo sprintf(
            "- plan=%s (#%d) active=%s trial=%s scope=%s\n",
            (string)$plan->name,
            (int)$plan->id,
            $yesno((bool)$plan->is_active),
            $yesno((bool)$plan->is_trial),
            $value($plan->accessscopeid)
        );

        echo "    explicit map: "
            . ($mapping
                ? sprintf(
                    "mapid=%d -> %s (#%d) status=%s",
                    (int)$mapping->mapid,
                    (string)$mapping->sku,
                    (int)$mapping->productid,
                    (string)$mapping->productstatus
                )
                : 'NONE')
            . "\n";

        echo "    resolver result: "
            . ($resolved
                ? sprintf(
                    "%s (#%d) status=%s type=%s",
                    (string)$resolved->sku,
                    (int)$resolved->id,
                    (string)$resolved->status,
                    (string)$resolved->type
                )
                : 'NONE')
            . "\n";

        echo "    deterministic " . $entry['deterministicsku'] . ': '
            . ($deterministic
                ? sprintf(
                    "product #%d status=%s type=%s",
                    (int)$deterministic->id,
                    (string)$deterministic->status,
                    (string)$deterministic->type
                )
                : 'NONE')
            . "\n";

        echo "    Legacy prices:";
        if ($entry['prices'] === []) {
            echo " NONE\n";
        } else {
            echo "\n";
            foreach ($entry['prices'] as $price) {
                echo sprintf(
                    "      - %s %s stripe=%s\n",
                    (string)$price->price,
                    (string)$price->currency,
                    $value($price->stripe_price_id)
                );
            }
        }
    }
}

echo "\nNative entitlements matching course family\n";
echo "------------------------------------------\n";
if ($nativeentitlements === []) {
    echo "NONE\n";
} else {
    foreach ($nativeentitlements as $entry) {
        $record = $entry['record'];

        echo sprintf(
            "- product=%s (#%d) status=%s type=%s "
            . "entitlement=%d entitlementtype=%s\n",
            (string)$record->sku,
            (int)$record->productid,
            (string)$record->productstatus,
            (string)$record->producttype,
            (int)$record->entitlementid,
            (string)$record->entitlementtype
        );
        echo sprintf(
            "    resourcekey=%s\n"
            . "    resolved course=%d accesslevel=%s matches=%s\n"
            . "    configurationjson=%s\n",
            (string)$record->resourcekey,
            (int)$entry['resolvedcourseid'],
            (string)$entry['resolvedaccesslevel'],
            $yesno((bool)$entry['matchescourse']),
            $value($record->configurationjson)
        );
    }
}

echo "\nNative product details\n";
echo "----------------------\n";
if ($nativeproducts === []) {
    echo "NONE\n";
} else {
    foreach ($nativeproducts as $entry) {
        $product = $entry['product'];

        echo sprintf(
            "- %s (#%d) name=%s type=%s status=%s "
            . "availablefrom=%s availableuntil=%s\n",
            (string)$product->sku,
            (int)$product->id,
            (string)$product->name,
            (string)$product->type,
            (string)$product->status,
            $value($product->availablefrom),
            $value($product->availableuntil)
        );

        echo "    metadata: "
            . json_encode(
                $entry['metadata'],
                JSON_UNESCAPED_UNICODE
            )
            . "\n";

        echo "    mappings:";
        if ($entry['mappings'] === []) {
            echo " NONE\n";
        } else {
            echo "\n";
            foreach ($entry['mappings'] as $map) {
                echo sprintf(
                    "      - %s:%d family=%s mapid=%d\n",
                    (string)$map->legacytable,
                    (int)$map->legacyid,
                    (string)$map->legacyfamily,
                    (int)$map->id
                );
            }
        }

        echo "    prices:";
        if ($entry['prices'] === []) {
            echo " NONE\n";
        } else {
            echo "\n";
            foreach ($entry['prices'] as $price) {
                echo sprintf(
                    "      - priceid=%d %d %s active=%s provider=%s "
                    . "providerpriceid=%s\n",
                    (int)$price->id,
                    (int)$price->amountminor,
                    (string)$price->currency,
                    $yesno((bool)$price->active),
                    $value($price->provider),
                    $value($price->providerpriceid)
                );
            }
        }

        echo "    entitlements:";
        if ($entry['entitlements'] === []) {
            echo " NONE\n";
        } else {
            echo "\n";
            foreach ($entry['entitlements'] as $entitlement) {
                $record = $entitlement['record'];
                echo sprintf(
                    "      - entitlementid=%d type=%s resourcekey=%s "
                    . "resolvedcourse=%d resolvedlevel=%s "
                    . "coursefamily=%s\n",
                    (int)$record->id,
                    (string)$record->type,
                    (string)$record->resourcekey,
                    (int)$entitlement['resolvedcourseid'],
                    (string)$entitlement['resolvedaccesslevel'],
                    $yesno((bool)$entitlement['courseinrequestedfamily'])
                );
                echo "        configurationjson="
                    . $value($record->configurationjson)
                    . "\n";
            }
        }
    }
}

echo "\nCurrent resolver result\n";
echo "-----------------------\n";
foreach ($resolutions as $level => $resolution) {
    echo sprintf(
        "- %s: skus=%s url=%s\n",
        $level,
        $resolution['resolvedskus'] === []
            ? 'NONE'
            : implode(', ', $resolution['resolvedskus']),
        $resolution['url']
    );
}

echo "\nDetected inconsistencies\n";
echo "------------------------\n";
if ($issues === []) {
    echo "NONE\n";
} else {
    foreach ($issues as $issue) {
        echo sprintf(
            "- [%s] %s: %s\n",
            strtoupper($issue['severity']),
            $issue['code'],
            $issue['message']
        );
    }
}

echo "\n============================================================\n";
echo "STATUS: READ-ONLY DIAGNOSTIC COMPLETE\n";
echo "============================================================\n";

exit(0);
