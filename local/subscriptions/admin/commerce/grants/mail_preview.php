<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\mail\admin\CommerceMailPreviewRenderer;
use local_subscriptions\commerce\mail\CommerceMailContext;
use local_subscriptions\commerce\mail\CommerceMailIdempotencyKey;
use local_subscriptions\commerce\mail\CommerceMailRecipient;
use local_subscriptions\commerce\mail\CommerceMailRequest;
use local_subscriptions\commerce\mail\CommerceMailRuntime;
use local_subscriptions\commerce\mail\CommerceMailType;
use local_subscriptions\commerce\mail\service\CommerceGrantMailStudioSelection;
use local_subscriptions\commerce\personaloffer\admin\CommercePersonalOfferCrmPresentation;
use local_subscriptions\crm\commerce\rendering\CommerceOffersAccessNavigationRenderer;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

$context = AdminSecurity::require(Capabilities::MANAGE_SUBSCRIPTIONS);

$templateid = max(0, optional_param('templateid', 0, PARAM_INT));
$productid = max(0, optional_param('productid', 0, PARAM_INT));
$userid = max(0, optional_param('userid', 0, PARAM_INT));
$language = trim(optional_param('language', '', PARAM_LANG));
$view = CommerceMailPreviewRenderer::normalise_view(
    optional_param('view', CommerceMailPreviewRenderer::DESKTOP, PARAM_ALPHA)
);
$embed = optional_param('embed', 0, PARAM_BOOL) === 1;

$previewpageurl = new moodle_url(
    '/local/subscriptions/admin/commerce/grants/mail_preview.php'
);
$PAGE->set_context($context);
$PAGE->set_url($previewpageurl);

$user = null;
if ($userid > 0) {
    $user = $DB->get_record(
        'user',
        ['id' => $userid, 'deleted' => 0],
        'id,firstname,lastname,email,lang',
        IGNORE_MISSING
    );
}
if ($language === '' && $user) {
    $language = clean_param((string)$user->lang, PARAM_LANG);
}
if (!in_array($language, ['fr', 'en', 'ru'], true)) {
    $language = 'ru';
}

$fullname = $user ? fullname($user) : 'Nata CampusFR';
$firstname = $user ? (string)$user->firstname : 'Nata';
$email = $user ? (string)$user->email : 'nata@example.test';

$productname = get_string(
    'commerce_grant_preview_example_product',
    'local_subscriptions'
);
if ($productid > 0) {
    $productname = CommercePersonalOfferCrmPresentation::business_product_label(
        $DB,
        $productid
    );
}

$mailtemplatesnapshot = [];
if ($templateid > 0) {
    $mailtemplatesnapshot = CommerceGrantMailStudioSelection::create($DB)
        ->snapshot($templateid);
}

$request = new CommerceMailRequest(
    CommerceMailType::GRANT_ACCESS,
    new CommerceMailRecipient($email, $fullname),
    new CommerceMailContext([
        'customer' => [
            'firstname' => $firstname,
            'fullname' => $fullname,
        ],
        'items' => [[
            'type' => 'course',
            'title' => $productname,
            'accesses' => [[
                'kind' => 'course',
                'label' => get_string(
                    'commerce_mail_access_my_campus',
                    'local_subscriptions'
                ),
                'url' => (new moodle_url('/mon-campus'))->out(false),
            ]],
        ]],
        'links' => [
            'hascampus' => true,
            'campus' => (new moodle_url('/mon-campus'))->out(false),
            'courses' => (new moodle_url('/mon-campus'))->out(false),
            'resources' => (new moodle_url('/mon-campus'))->out(false),
        ],
        'grantaccess' => [
            'mailtemplatesnapshot' => $mailtemplatesnapshot,
            'rootproductid' => $productid,
        ],
    ]),
    $language,
    CommerceMailIdempotencyKey::normalise(
        'grant-preview:' . $templateid . ':' . $productid . ':' . $userid . ':' . $language
    )
);

$message = CommerceMailRuntime::template_registry()
    ->get(CommerceMailType::GRANT_ACCESS)
    ->render($request);

$url = new moodle_url(
    '/local/subscriptions/admin/commerce/grants/mail_preview.php',
    array_filter([
        'templateid' => $templateid,
        'productid' => $productid,
        'userid' => $userid,
        'language' => $language,
        'view' => $view,
    ], static fn(mixed $value): bool => $value !== '' && $value !== 0)
);

$title = get_string('commerce_grant_mail_preview_title', 'local_subscriptions');

if ($embed) {
    echo '<!doctype html><html><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<style>'
        . 'html,body{margin:0;padding:0;background:#f8fafc;}'
        . 'body{padding:14px;font-family:Arial,Helvetica,sans-serif;}'
        . '.grant-mail-embed{max-width:760px;margin:0 auto;background:#fff;'
        . 'border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;}'
        . '</style></head><body><div class="grant-mail-embed">'
        . $message->get_html()
        . '</div></body></html>';
    exit;
}

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $url,
    $title,
    'local-subscriptions-commerce-grant-mail-preview-page'
);
$PAGE->requires->css('/local/subscriptions/styles/commerce_mail_admin.css');

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    [
        'label' => get_string('crm_commerce_title', 'local_subscriptions'),
        'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php'),
    ],
    [
        'label' => get_string('commerce_offers_access_title', 'local_subscriptions'),
        'url' => new moodle_url('/local/subscriptions/admin/commerce/offers-access/index.php'),
    ],
    [
        'label' => get_string('commerce_grants_title', 'local_subscriptions'),
        'url' => new moodle_url('/local/subscriptions/admin/commerce/grants/index.php'),
    ],
    ['label' => $title, 'url' => null],
]);
echo CrmPageHeader::render(
    $title,
    get_string('commerce_grant_mail_preview_description', 'local_subscriptions'),
    HelpContext::COMMERCE
);
echo CommerceSectionNavigationRenderer::render(
    CommerceSectionNavigationRenderer::OFFERS_ACCESS,
    $context
);
echo CommerceOffersAccessNavigationRenderer::render(
    CommerceOffersAccessNavigationRenderer::GRANTS
);

echo html_writer::div(
    html_writer::tag(
        'strong',
        s($message->get_subject())
    ),
    'mb-3'
);

$navigation = CommerceMailPreviewRenderer::render_navigation($url, $view);

$languageoptions = [
    'ru' => get_string('commerce_language_ru', 'local_subscriptions'),
    'fr' => get_string('commerce_language_fr', 'local_subscriptions'),
    'en' => get_string('commerce_language_en', 'local_subscriptions'),
];
$languageform = html_writer::start_tag('form', [
    'method' => 'get',
    'action' => $url->out_omit_querystring(),
    'class' => 'commerce-mail-preview-language',
]);
foreach ([
    'templateid' => $templateid,
    'productid' => $productid,
    'userid' => $userid,
    'view' => $view,
] as $name => $value) {
    if ($value !== '' && $value !== 0) {
        $languageform .= html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => $name,
            'value' => $value,
        ]);
    }
}
$languageform .= html_writer::label(
    get_string('language'),
    'grant-mail-preview-language',
    false,
    ['class' => 'commerce-mail-preview-language__label']
);
$languageform .= html_writer::select(
    $languageoptions,
    'language',
    $language,
    false,
    [
        'id' => 'grant-mail-preview-language',
        'class' => 'form-select form-select-sm commerce-mail-preview-language__select',
        'onchange' => 'this.form.submit();',
    ]
);
$languageform .= html_writer::end_tag('form');

echo html_writer::div(
    html_writer::div(
        $navigation,
        'commerce-mail-preview-toolbar__navigation'
    )
    . html_writer::div(
        $languageform,
        'commerce-mail-preview-toolbar__language'
    ),
    'commerce-mail-preview-toolbar'
);

echo CommerceMailPreviewRenderer::render(
    $message->get_html(),
    $message->get_text(),
    $view
);

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
