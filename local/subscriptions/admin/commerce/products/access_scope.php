<?php

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\catalog\editing\CommerceProductEditorCapabilities;
use local_subscriptions\commerce\catalog\navigation\CommerceLegacyCatalogLinkGenerator;
use local_subscriptions\commerce\catalog\presentation\CommerceCatalogProductNameResolver;
use local_subscriptions\commerce\catalog\rendering\CommerceProductEditorNavigationRenderer;
use local_subscriptions\commerce\catalog\repository\CommerceLegacyProductMapRepository;
use local_subscriptions\commerce\catalog\service\CommerceCatalogFactory;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\commerce\presentation\CommerceDesignSystemRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

$context = AdminSecurity::require(Capabilities::MANAGE_CONFIGURATION);
$sku = required_param('sku', PARAM_RAW_TRIMMED);
$factory = new CommerceCatalogFactory($DB);
$manager = $factory->product_manager();
$product = $manager->get_editor_data($sku)->get_product();
$displayname = CommerceCatalogProductNameResolver::resolve_native_id(
    $DB,
    (int)$product->get_id(),
    $product->get_name()
);

if (!CommerceProductEditorCapabilities::for_product($product)->can_manage_access_scope()) {
    throw new coding_exception('This product type does not use an Access Scope relation.');
}

$mappingrepository = new CommerceLegacyProductMapRepository($DB);
$metadata = $product->get_metadata();
$accessmetadata = is_array($metadata['access'] ?? null) ? $metadata['access'] : [];

if (data_submitted() && confirm_sesskey()) {
    $action = required_param('action', PARAM_ALPHA);

    if ($action === 'scope') {
        $sourceplanid = optional_param('scopeplanid', 0, PARAM_INT);
        if ($sourceplanid > 0) {
            $plan = $DB->get_record('subscription_plan', ['id' => $sourceplanid], 'id,accessscopeid', MUST_EXIST);
            if (empty($plan->accessscopeid)) {
                throw new moodle_exception('commerce_access_scope_plan_without_scope', 'local_subscriptions');
            }
            $accessmetadata['sourceplanid'] = (int)$plan->id;
            $accessmetadata['scopeid'] = (int)$plan->accessscopeid;
        } else {
            unset($accessmetadata['sourceplanid'], $accessmetadata['scopeid']);
        }
        $metadata['access'] = $accessmetadata;
        $manager->save_metadata($product->get_sku(), $metadata);
    }

    if ($action === 'canonical') {
        $planid = optional_param('legacyplanid', 0, PARAM_INT);
        if ($planid <= 0) {
            $mappingrepository->unlink_product((int)$product->get_id(), 'subscription_plan');
        } else {
            $DB->get_record('subscription_plan', ['id' => $planid], 'id', MUST_EXIST);
            $conflict = $mappingrepository->find_legacy_link('subscription_plan', $planid);
            if ($conflict && (int)$conflict->productid !== (int)$product->get_id()) {
                throw new moodle_exception('commerce_access_scope_canonical_conflict', 'local_subscriptions');
            }
            $mappingrepository->link_product(
                (int)$product->get_id(),
                'subscription',
                'subscription_plan',
                $planid
            );
        }
    }

    redirect(
        new moodle_url('/local/subscriptions/admin/commerce/products/access_scope.php', ['sku' => $sku]),
        get_string('changessaved')
    );
}

$pageurl = new moodle_url('/local/subscriptions/admin/commerce/products/access_scope.php', ['sku' => $sku]);
CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    get_string('commerce_access_scope_relation_title', 'local_subscriptions'),
    'local-subscriptions-commerce-access-scope-page'
);

$plans = $DB->get_records('subscription_plan', [], 'name ASC', 'id,name,is_active,accessscopeid');
$nativeproducts = $DB->get_records('local_subs_commerce_product', [], '', 'id,sku,name');
$currentmapping = $mappingrepository->find_by_product((int)$product->get_id(), 'subscription_plan');
$currentcanonicalplanid = $currentmapping ? (int)$currentmapping->legacyid : 0;
$currentsourceplanid = (int)($accessmetadata['sourceplanid'] ?? 0);
$currentscopeid = (int)($accessmetadata['scopeid'] ?? 0);

$scopeoptions = [0 => get_string('commerce_access_scope_no_scope', 'local_subscriptions')];
$canonicaloptions = [0 => get_string('commerce_access_scope_no_canonical_plan', 'local_subscriptions')];

foreach ($plans as $plan) {
    $label = format_string($plan->name);
    if (empty($plan->accessscopeid)) {
        $label .= ' — ' . get_string('commerce_access_scope_plan_without_scope', 'local_subscriptions');
    } else {
        $scopeoptions[(int)$plan->id] = $label;
    }

    $link = $mappingrepository->find_legacy_link('subscription_plan', (int)$plan->id);
    if ($link && (int)$link->productid !== (int)$product->get_id()) {
        $linkedproduct = $nativeproducts[(int)$link->productid] ?? null;
        $linkedlabel = $linkedproduct
            ? CommerceCatalogProductNameResolver::resolve_native_id(
                $DB,
                (int)$linkedproduct->id,
                (string)$linkedproduct->name
            )
            : get_string(
                'commerce_access_scope_unknown_product',
                'local_subscriptions'
            );
        $label .= ' — ' . get_string(
            'commerce_access_scope_already_linked_to',
            'local_subscriptions',
            $linkedlabel
        );
    } else {
        $canonicaloptions[(int)$plan->id] = $label;
    }
}

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(
    CrmNavigationKeys::COMMERCE,
    $context
);
echo CommerceProductEditorNavigationRenderer::breadcrumb(
    $displayname,
    get_string(
        'commerce_product_step_access_scope',
        'local_subscriptions'
    )
);
echo CommerceSectionNavigationRenderer::render(
    CommerceSectionNavigationRenderer::PRODUCTS
);
echo CommerceProductEditorNavigationRenderer::render(
    $product,
    CommerceProductEditorNavigationRenderer::ACCESS_SCOPE
);

$scope = $currentscopeid > 0
    ? $DB->get_record(
        'subscription_access_scope',
        ['id' => $currentscopeid],
        'id,name'
    )
    : null;
$sourceplan = $currentsourceplanid > 0
    ? ($plans[$currentsourceplanid] ?? null)
    : null;

$courses = [];
if ($currentsourceplanid > 0) {
    $sql = 'SELECT c.id, c.fullname
              FROM {subscription_plan_entitlement} e
              JOIN {course} c ON c.id = e.courseid
             WHERE e.planid = :planid
          ORDER BY c.fullname ASC';
    $courses = $DB->get_records_sql(
        $sql,
        ['planid' => $currentsourceplanid]
    );
}

echo html_writer::div(
    html_writer::tag(
        'h1',
        get_string(
            'commerce_access_scope_business_title',
            'local_subscriptions'
        ),
        ['class' => 'h2 mb-1']
    )
    . html_writer::tag(
        'p',
        get_string(
            'commerce_access_scope_business_intro',
            'local_subscriptions'
        ),
        ['class' => 'text-muted mb-0']
    ),
    'crm-product-access-scope-page-header'
);

echo CommerceDesignSystemRenderer::metrics([
    [
        'label' => get_string(
            'commerce_access_scope_metric_source',
            'local_subscriptions'
        ),
        'value' => $sourceplan
            ? format_string($sourceplan->name)
            : '—',
    ],
    [
        'label' => get_string(
            'commerce_access_scope_metric_scope',
            'local_subscriptions'
        ),
        'value' => $scope
            ? format_string($scope->name)
            : '—',
    ],
    [
        'label' => get_string(
            'commerce_access_scope_metric_courses',
            'local_subscriptions'
        ),
        'value' => count($courses),
    ],
]);

// Primary business configuration.
echo html_writer::start_tag('form', [
    'method' => 'post',
    'class' => 'card card-body crm-product-access-scope-main-card',
]);
echo html_writer::empty_tag('input', [
    'type' => 'hidden',
    'name' => 'sesskey',
    'value' => sesskey(),
]);
echo html_writer::empty_tag('input', [
    'type' => 'hidden',
    'name' => 'action',
    'value' => 'scope',
]);

echo html_writer::div(
    html_writer::div(
        html_writer::tag('i', '', [
            'class' => 'fa fa-key',
            'aria-hidden' => 'true',
        ]),
        'crm-product-access-scope-section-icon'
    )
    . html_writer::div(
        html_writer::tag(
            'h2',
            get_string(
                'commerce_access_scope_customer_access_title',
                'local_subscriptions'
            ),
            ['class' => 'h5 mb-1']
        )
        . html_writer::tag(
            'p',
            get_string(
                'commerce_access_scope_customer_access_help',
                'local_subscriptions'
            ),
            ['class' => 'text-muted mb-0']
        ),
        'crm-product-access-scope-section-copy'
    ),
    'crm-product-access-scope-section-header'
);

echo html_writer::div(
    html_writer::tag(
        'label',
        get_string(
            'commerce_access_scope_source_plan_business',
            'local_subscriptions'
        ),
        [
            'for' => 'scopeplanid',
            'class' => 'form-label',
        ]
    )
    . html_writer::select(
        $scopeoptions,
        'scopeplanid',
        $currentsourceplanid,
        false,
        [
            'id' => 'scopeplanid',
            'class' => 'form-select',
        ]
    )
    . html_writer::tag(
        'div',
        get_string(
            'commerce_access_scope_source_plan_business_help',
            'local_subscriptions'
        ),
        ['class' => 'form-text']
    ),
    'crm-product-access-scope-field'
);

if ($scope !== null) {
    echo html_writer::div(
        html_writer::tag(
            'i',
            '',
            [
                'class' => 'fa fa-check-circle me-2',
                'aria-hidden' => 'true',
            ]
        )
        . get_string(
            'commerce_access_scope_effective_scope',
            'local_subscriptions',
            format_string($scope->name)
        ),
        'crm-product-access-scope-effective'
    );
}

echo html_writer::div(
    html_writer::tag(
        'button',
        html_writer::tag('i', '', [
            'class' => 'fa fa-save me-1',
            'aria-hidden' => 'true',
        ])
        . get_string('savechanges'),
        [
            'type' => 'submit',
            'class' => 'btn btn-primary',
        ]
    ),
    'crm-product-access-scope-actions'
);
echo html_writer::end_tag('form');

// Courses delivered by the selected scope.
echo html_writer::start_div(
    'card card-body crm-product-access-scope-courses-card'
);
echo html_writer::div(
    html_writer::tag(
        'h2',
        html_writer::tag('i', '', [
            'class' => 'fa fa-graduation-cap me-2',
            'aria-hidden' => 'true',
        ])
        . get_string(
            'commerce_access_scope_courses_business_title',
            'local_subscriptions'
        ),
        ['class' => 'h5 mb-1']
    )
    . html_writer::span(
        get_string(
            'commerce_access_scope_courses_count',
            'local_subscriptions',
            count($courses)
        ),
        'badge rounded-pill text-bg-light'
    ),
    'crm-product-access-scope-courses-header'
);

if ($courses === []) {
    echo html_writer::div(
        get_string(
            'commerce_access_scope_courses_empty',
            'local_subscriptions'
        ),
        'crm-product-access-scope-empty'
    );
} else {
    echo html_writer::start_div(
        'crm-product-access-scope-course-list'
    );
    foreach ($courses as $course) {
        echo html_writer::div(
            html_writer::div(
                html_writer::tag('i', '', [
                    'class' => 'fa fa-book',
                    'aria-hidden' => 'true',
                ]),
                'crm-product-access-scope-course-icon'
            )
            . html_writer::div(
                html_writer::tag(
                    'strong',
                    format_string($course->fullname)
                ),
                'crm-product-access-scope-course-copy'
            ),
            'crm-product-access-scope-course-row'
        );
    }
    echo html_writer::end_div();
}
echo html_writer::end_div();

// Legacy canonical mapping: compatibility-only, hidden by default.
$canonicalcontent = html_writer::tag(
    'p',
    get_string(
        'commerce_access_scope_legacy_compatibility_help',
        'local_subscriptions'
    ),
    ['class' => 'text-muted']
);

$canonicalcontent .= html_writer::start_tag('form', [
    'method' => 'post',
    'class' => 'crm-product-access-scope-legacy-form',
]);
$canonicalcontent .= html_writer::empty_tag('input', [
    'type' => 'hidden',
    'name' => 'sesskey',
    'value' => sesskey(),
]);
$canonicalcontent .= html_writer::empty_tag('input', [
    'type' => 'hidden',
    'name' => 'action',
    'value' => 'canonical',
]);
$canonicalcontent .= html_writer::tag(
    'label',
    get_string(
        'commerce_access_scope_legacy_plan_business',
        'local_subscriptions'
    ),
    [
        'for' => 'legacyplanid',
        'class' => 'form-label',
    ]
);
$canonicalcontent .= html_writer::select(
    $canonicaloptions,
    'legacyplanid',
    $currentcanonicalplanid,
    false,
    [
        'id' => 'legacyplanid',
        'class' => 'form-select',
    ]
);
$canonicalcontent .= html_writer::tag(
    'div',
    get_string(
        'commerce_access_scope_legacy_plan_help',
        'local_subscriptions'
    ),
    ['class' => 'form-text']
);
$canonicalcontent .= html_writer::div(
    html_writer::tag(
        'button',
        get_string('savechanges'),
        [
            'type' => 'submit',
            'class' => 'btn btn-outline-secondary',
        ]
    ),
    'crm-product-access-scope-legacy-actions'
);
$canonicalcontent .= html_writer::end_tag('form');

if ($currentcanonicalplanid > 0) {
    $canonicalplan = $plans[$currentcanonicalplanid] ?? null;
    if ($canonicalplan) {
        $canonicalcontent .= html_writer::div(
            get_string(
                'commerce_access_scope_legacy_current_mapping',
                'local_subscriptions',
                format_string($canonicalplan->name)
            ),
            'crm-product-access-scope-legacy-current'
        );
    }
}

echo html_writer::tag(
    'details',
    html_writer::tag(
        'summary',
        html_writer::tag('i', '', [
            'class' => 'fa fa-exchange me-2',
            'aria-hidden' => 'true',
        ])
        . html_writer::tag(
            'strong',
            get_string(
                'commerce_access_scope_legacy_compatibility_title',
                'local_subscriptions'
            )
        )
        . html_writer::span(
            get_string(
                'commerce_access_scope_legacy_compatibility_badge',
                'local_subscriptions'
            ),
            'badge rounded-pill text-bg-light ms-2'
        ),
        ['class' => 'crm-product-access-scope-legacy-summary']
    )
    . html_writer::div(
        $canonicalcontent,
        'crm-product-access-scope-legacy-body'
    ),
    ['class' => 'crm-product-access-scope-legacy-details']
);

// Useful shortcuts, not primary controls.
if ($currentsourceplanid > 0 && $currentscopeid > 0) {
    echo html_writer::div(
        html_writer::span(
            get_string(
                'commerce_access_scope_admin_shortcuts',
                'local_subscriptions'
            ),
            'crm-product-access-scope-shortcuts-label'
        )
        . html_writer::link(
            CommerceLegacyCatalogLinkGenerator::plan_edit_url(
                $currentsourceplanid
            ),
            html_writer::tag('i', '', [
                'class' => 'fa fa-pencil me-1',
                'aria-hidden' => 'true',
            ])
            . get_string(
                'commerce_access_scope_edit_plan',
                'local_subscriptions'
            ),
            ['class' => 'btn btn-sm btn-outline-secondary']
        )
        . html_writer::link(
            CommerceLegacyCatalogLinkGenerator::scope_edit_url(
                $currentscopeid
            ),
            html_writer::tag('i', '', [
                'class' => 'fa fa-key me-1',
                'aria-hidden' => 'true',
            ])
            . get_string(
                'commerce_access_scope_edit_scope',
                'local_subscriptions'
            ),
            ['class' => 'btn btn-sm btn-outline-secondary']
        ),
        'crm-product-access-scope-shortcuts'
    );
}

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();

