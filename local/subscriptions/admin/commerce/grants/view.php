<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\commerce\rendering\CommerceOffersAccessDetailRenderer;
use local_subscriptions\crm\commerce\rendering\CommerceOffersAccessNavigationRenderer;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\subscription_config;

$context = AdminSecurity::require(Capabilities::VIEW_PAYMENTS);
$id = required_param('id', PARAM_INT);

$grant = $DB->get_record(
    'local_subs_commerce_grant',
    ['id' => $id],
    '*',
    MUST_EXIST
);

$url = new moodle_url(
    '/local/subscriptions/admin/commerce/grants/view.php',
    ['id' => $id]
);
$title = get_string(
    'commerce_offers_access_grant_detail_title',
    'local_subscriptions'
);

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $url,
    $title,
    'local-subscriptions-commerce-grant-detail'
);

$user = null;
if (!empty($grant->beneficiaryuserid)) {
    $user = $DB->get_record(
        'user',
        [
            'id' => (int)$grant->beneficiaryuserid,
            'deleted' => 0,
        ],
        '*',
        IGNORE_MISSING
    );
}

$product = $DB->get_record(
    'local_subs_commerce_product',
    ['sku' => (string)$grant->productsku],
    'id,name,sku,type,status',
    IGNORE_MISSING
);

$purchase = null;
if (trim((string)$grant->purchasereference) !== '') {
    $purchase = $DB->get_record(
        'local_subscriptions_commerce_purchase',
        ['reference' => (string)$grant->purchasereference],
        'id,reference,status,timecreated',
        IGNORE_MISSING
    );
}

$name = $user ? fullname($user) : '';
$clientlabel = $name !== ''
    ? $name
    : (string)$grant->beneficiaryemail;

$statuslabels = [
    'planned' => get_string(
        'commerce_offers_access_grant_status_planned',
        'local_subscriptions'
    ),
    'active' => get_string(
        'commerce_offers_access_grant_status_active',
        'local_subscriptions'
    ),
    'failed' => get_string(
        'commerce_offers_access_grant_status_failed',
        'local_subscriptions'
    ),
    'completed' => get_string(
        'commerce_offers_access_grant_status_completed',
        'local_subscriptions'
    ),
];
$statusclasses = [
    'planned' => 'is-warning',
    'active' => 'is-success',
    'failed' => 'is-error',
    'completed' => 'is-success',
];
$grantstatus = (string)$grant->status;
$statuslabel = $statuslabels[$grantstatus] ?? $grantstatus;
$statusclass = $statusclasses[$grantstatus] ?? '';

$productlabel = $product
    ? (string)$product->name
    : (string)$grant->productsku;
$producthtml = $product
    ? html_writer::link(
        new moodle_url(
            '/local/subscriptions/admin/commerce/products/view.php',
            ['id' => (int)$product->id]
        ),
        s($productlabel),
        ['class' => 'crm-offers-access-detail-link']
    )
    : s($productlabel);

$validity = userdate(
    (int)$grant->validfrom,
    get_string('strftimedate', 'langconfig')
);
$validity .= ' → ';
$validity .= !empty($grant->validuntil)
    ? userdate(
        (int)$grant->validuntil,
        get_string('strftimedate', 'langconfig')
    )
    : get_string(
        'commerce_offers_access_no_expiry',
        'local_subscriptions'
    );

$configuration = json_decode(
    (string)$grant->configurationjson,
    true
);
$metadata = json_decode(
    (string)$grant->metadatajson,
    true
);
$configuration = is_array($configuration) ? $configuration : [];
$metadata = is_array($metadata) ? $metadata : [];

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(
    CrmNavigationKeys::COMMERCE,
    $context
);
echo CrmBreadcrumbRenderer::render([
    [
        'label' => get_string(
            'crm_commerce_title',
            'local_subscriptions'
        ),
        'url' => new moodle_url(
            '/local/subscriptions/admin/commerce/index.php'
        ),
    ],
    [
        'label' => get_string(
            'commerce_offers_access_title',
            'local_subscriptions'
        ),
        'url' => new moodle_url(
            '/local/subscriptions/admin/commerce/offers-access/index.php'
        ),
    ],
    [
        'label' => get_string(
            'commerce_grants_title',
            'local_subscriptions'
        ),
        'url' => new moodle_url(
            '/local/subscriptions/admin/commerce/grants/index.php'
        ),
    ],
    ['label' => $title, 'url' => null],
]);
echo CrmPageHeader::render(
    $title,
    get_string(
        'commerce_offers_access_grant_detail_help',
        'local_subscriptions'
    ),
    HelpContext::COMMERCE
);
echo CommerceSectionNavigationRenderer::render(
    CommerceSectionNavigationRenderer::OFFERS_ACCESS,
    $context
);
echo CommerceOffersAccessNavigationRenderer::render(
    CommerceOffersAccessNavigationRenderer::GRANTS
);

echo CommerceOffersAccessDetailRenderer::hero(
    'grant',
    $clientlabel,
    (string)$grant->beneficiaryemail,
    $statuslabel,
    $statusclass,
    [
        [
            'label' => get_string(
                'commerce_offers_access_config_product',
                'local_subscriptions'
            ),
            'value' => $producthtml,
            'html' => true,
        ],
        [
            'label' => get_string(
                'commerce_offers_access_validity',
                'local_subscriptions'
            ),
            'value' => $validity,
        ],
        [
            'label' => get_string(
                'commerce_offers_access_grant_quantity',
                'local_subscriptions'
            ),
            'value' => (string)$grant->quantity,
        ],
        [
            'label' => get_string(
                'commerce_offers_access_created',
                'local_subscriptions'
            ),
            'value' => userdate(
                (int)$grant->timecreated,
                get_string(
                    'strftimedatetimeshort',
                    'langconfig'
                )
            ),
        ],
    ]
);

$actions = [];
if ($user) {
    $actions[] = [
        'label' => get_string(
            'commerce_offers_access_config_open_user360',
            'local_subscriptions'
        ),
        'url' => new moodle_url(
            subscription_config::admin_user_view_page(),
            ['id' => (int)$user->id]
        ),
        'icon' => 'fa-user',
        'class' => 'btn btn-outline-secondary crm-offers-access-action is-client',
    ];
}
if ($purchase) {
    $actions[] = [
        'label' => get_string(
            'commerce_offers_access_open_source_sale',
            'local_subscriptions'
        ),
        'url' => new moodle_url(
            '/local/subscriptions/admin/commerce/purchases/view.php',
            ['id' => (int)$purchase->id]
        ),
        'icon' => 'fa-shopping-cart',
        'class' => 'btn btn-outline-primary crm-offers-access-action is-sale',
    ];
}
$actions[] = [
    'label' => get_string(
        'commerce_offers_access_grant_mail_journal',
        'local_subscriptions'
    ),
    'url' => new moodle_url(
        '/local/subscriptions/admin/commerce/mail/index.php',
        ['q' => (string)$grant->beneficiaryemail]
    ),
    'icon' => 'fa-envelope-o',
        'class' => 'btn btn-outline-primary crm-offers-access-action is-communication',
];
echo CommerceOffersAccessDetailRenderer::actions($actions);

echo html_writer::start_div(
    'crm-offers-access-detail-grid'
);

echo CommerceOffersAccessDetailRenderer::panel(
    get_string(
        'commerce_offers_access_grant_access_title',
        'local_subscriptions'
    ),
    CommerceOffersAccessDetailRenderer::rows([
        [
            'label' => get_string(
                'commerce_offers_access_config_product',
                'local_subscriptions'
            ),
            'value' => $producthtml,
            'html' => true,
        ],
        [
            'label' => get_string(
                'commerce_offers_access_grant_type',
                'local_subscriptions'
            ),
            'value' => (string)$grant->type,
        ],
        [
            'label' => get_string(
                'commerce_offers_access_validity',
                'local_subscriptions'
            ),
            'value' => $validity,
        ],
        [
            'label' => get_string(
                'commerce_offers_access_grant_quantity',
                'local_subscriptions'
            ),
            'value' => (string)$grant->quantity,
        ],
    ]),
    'fa-key'
);

$originrows = [];
if ($purchase) {
    $originrows[] = [
        'label' => get_string(
            'commerce_offers_access_source_sale',
            'local_subscriptions'
        ),
        'value' => html_writer::link(
            new moodle_url(
                '/local/subscriptions/admin/commerce/purchases/view.php',
                ['id' => (int)$purchase->id]
            ),
            s((string)$purchase->reference),
            ['class' => 'crm-offers-access-detail-link']
        ),
        'html' => true,
    ];
} else {
    $originrows[] = [
        'label' => get_string(
            'commerce_offers_access_source_sale',
            'local_subscriptions'
        ),
        'value' => get_string(
            'commerce_offers_access_grant_no_sale',
            'local_subscriptions'
        ),
    ];
}
$reason = trim((string)($metadata['reason'] ?? ''));
if ($reason !== '') {
    $originrows[] = [
        'label' => get_string(
            'commerce_bulk_grant_campaign_reason',
            'local_subscriptions'
        ),
        'value' => $reason,
    ];
}

echo CommerceOffersAccessDetailRenderer::panel(
    get_string(
        'commerce_offers_access_grant_origin_title',
        'local_subscriptions'
    ),
    CommerceOffersAccessDetailRenderer::rows(
        $originrows
    ),
    'fa-link'
);

echo CommerceOffersAccessDetailRenderer::panel(
    get_string(
        'commerce_offers_access_grant_lifecycle_title',
        'local_subscriptions'
    ),
    CommerceOffersAccessDetailRenderer::rows([
        [
            'label' => get_string('status'),
            'value' => $statuslabel,
        ],
        [
            'label' => get_string(
                'commerce_offers_access_created',
                'local_subscriptions'
            ),
            'value' => userdate(
                (int)$grant->timecreated
            ),
        ],
        [
            'label' => get_string('modified'),
            'value' => userdate(
                (int)$grant->timemodified
            ),
        ],
    ]),
    'fa-history'
);

echo CommerceOffersAccessDetailRenderer::panel(
    get_string(
        'commerce_offers_access_campaign_communication_title',
        'local_subscriptions'
    ),
    CommerceOffersAccessDetailRenderer::rows([
        [
            'label' => get_string('email'),
            'value' => (string)$grant->beneficiaryemail,
        ],
        [
            'label' => get_string(
                'commerce_offers_access_grant_mail_journal',
                'local_subscriptions'
            ),
            'value' => html_writer::link(
                new moodle_url(
                    '/local/subscriptions/admin/commerce/mail/index.php',
                    ['q' => (string)$grant->beneficiaryemail]
                ),
                get_string(
                    'commerce_offers_access_campaign_open_mail_journal',
                    'local_subscriptions'
                ),
                ['class' => 'crm-offers-access-detail-link']
            ),
            'html' => true,
        ],
    ]),
    'fa-envelope-o'
);

echo html_writer::end_div();

$technical = CommerceOffersAccessDetailRenderer::rows([
    [
        'label' => get_string(
            'commerce_offers_access_grant_reference',
            'local_subscriptions'
        ),
        'value' => (string)$grant->grantreference,
    ],
    [
        'label' => get_string(
            'commerce_offers_access_grant_resource',
            'local_subscriptions'
        ),
        'value' => (string)$grant->resourcekey,
    ],
    [
        'label' => get_string(
            'commerce_offers_access_grant_idempotency',
            'local_subscriptions'
        ),
        'value' => (string)$grant->idempotencykey,
    ],
    [
        'label' => get_string(
            'commerce_offers_access_grant_item_reference',
            'local_subscriptions'
        ),
        'value' => (string)$grant->itemreference,
    ],
]);
$technical .= html_writer::tag(
    'h3',
    get_string(
        'commerce_offers_access_grant_configuration',
        'local_subscriptions'
    ),
    ['class' => 'h6 mt-3']
);
$technical .= html_writer::tag(
    'pre',
    s(json_encode(
        $configuration,
        JSON_PRETTY_PRINT
        | JSON_UNESCAPED_UNICODE
        | JSON_UNESCAPED_SLASHES
    )),
    ['class' => 'crm-offers-access-detail-metadata']
);
$technical .= html_writer::tag(
    'h3',
    get_string(
        'commerce_offers_access_grant_metadata',
        'local_subscriptions'
    ),
    ['class' => 'h6 mt-3']
);
$technical .= html_writer::tag(
    'pre',
    s(json_encode(
        $metadata,
        JSON_PRETTY_PRINT
        | JSON_UNESCAPED_UNICODE
        | JSON_UNESCAPED_SLASHES
    )),
    ['class' => 'crm-offers-access-detail-metadata']
);

echo CommerceOffersAccessDetailRenderer::technical(
    get_string(
        'commerce_personal_offer_technical_title',
        'local_subscriptions'
    ),
    $technical
);

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
