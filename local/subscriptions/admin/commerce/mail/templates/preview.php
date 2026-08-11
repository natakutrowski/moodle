<?php

require_once(__DIR__ . '/../../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\mail\admin\CommerceMailAdminPresentation;
use local_subscriptions\commerce\mail\admin\CommerceMailPreviewRenderer;
use local_subscriptions\commerce\mail\CommerceMailContext;
use local_subscriptions\commerce\mail\CommerceMailIdempotencyKey;
use local_subscriptions\commerce\mail\CommerceMailRecipient;
use local_subscriptions\commerce\mail\CommerceMailRequest;
use local_subscriptions\commerce\mail\CommerceMailRuntime;
use local_subscriptions\commerce\mail\CommerceMailType;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

$context = AdminSecurity::require(Capabilities::MANAGE_CONFIGURATION);
$mailtype = required_param('mailtype', PARAM_ALPHANUMEXT);
$language = required_param('language', PARAM_ALPHANUMEXT);
$previewview = CommerceMailPreviewRenderer::normalise_view(
    optional_param('view', CommerceMailPreviewRenderer::DESKTOP, PARAM_ALPHA)
);
$previewfont = CommerceMailPreviewRenderer::normalise_font(
    optional_param('font', CommerceMailPreviewRenderer::FONT_BRAND, PARAM_ALPHA)
);
if (!in_array($mailtype, CommerceMailType::all(), true) || !in_array($language, ['fr', 'en', 'ru'], true)) {
    throw new moodle_exception('invalidparameter');
}

$url = new moodle_url('/local/subscriptions/admin/commerce/mail/templates/preview.php', [
    'mailtype' => $mailtype,
    'language' => $language,
    'font' => $previewfont,
]);
$title = get_string('commerce_mail_template_preview_title', 'local_subscriptions', (object)[
    'type' => CommerceMailAdminPresentation::type_label($mailtype),
    'language' => CommerceMailAdminPresentation::language_label($language),
]);
CrmPageConfigurator::configure($PAGE, $context, $url, $title, 'local-subscriptions-commerce-mail-template-preview-page');
$PAGE->requires->css('/local/subscriptions/styles/commerce_mail_admin.css');

$request = new CommerceMailRequest(
    $mailtype,
    new CommerceMailRecipient('nata@example.test', 'Nata CampusFR'),
    new CommerceMailContext([
        'customer' => ['firstname' => 'Nata', 'fullname' => 'Nata CampusFR'],
        'purchase' => ['reference' => 'CFR-2026-000123', 'totalformatted' => '129,00 €'],
        'items' => [
            [
                'type' => 'course',
                'title' => 'Cours de français A1',
                'totalformatted' => '79,00 €',
                'producturl' => 'https://www.campusfr.fr/example/course',
                'accesses' => [[
                    'kind' => 'course',
                    'label' => 'Accéder au cours',
                    'url' => 'https://www.campusfr.fr/example/access/course',
                ]],
            ],
            [
                'type' => 'digital',
                'title' => 'Guide des verbes français',
                'totalformatted' => '50,00 €',
                'accesses' => [[
                    'kind' => 'download',
                    'label' => 'Version classique',
                    'url' => 'https://www.campusfr.fr/example/access/download',
                    'filename' => 'guide-verbes.pdf',
                    'filetype' => 'PDF',
                    'filesize' => '4,8 Mo',
                ]],
            ],
        ],
        'payment' => [
            'providerlabel' => 'Stripe',
            'transactionreference' => 'pi_example_123',
            'statuslabel' => 'Payé',
            'amountformatted' => '129,00 €',
        ],
        'links' => [
            'order' => 'https://www.campusfr.fr/example/order',
            'purchases' => 'https://www.campusfr.fr/example/purchases',
            'resources' => 'https://www.campusfr.fr/example/resources',
            'courses' => 'https://www.campusfr.fr/example/courses',
            'campus' => 'https://www.campusfr.fr/mon-campus',
        ],
        'activationurl' => 'https://www.campusfr.fr/local/subscriptions/guest_account_activate.php?example=1',
        'activationexpirestimestamp' => time() + DAYSECS,
        'accountemail' => 'nata@example.test',
        'trialurl' => 'https://www.campusfr.fr/mes-cours',
        'reseturl' => 'https://www.campusfr.fr/login/forgot_password.php',
        'personaloffer' => [
            'url' => 'https://www.campusfr.fr/local/subscriptions/offer.php?token=example',
            'productname' => 'Entraîneur des verbes du 3e groupe',
            'campaignname' => 'Acheteurs historiques des cartes',
            'priceformatted' => '30,00 EUR · 2 990,00 RUB',
            'expiresformatted' => '31 août 2026',
            'offerlabel' => get_string('commerce_mail_personal_offer_card_label', 'local_subscriptions'),
            'expirylabel' => get_string('commerce_mail_personal_offer_expiry_label', 'local_subscriptions'),
        ],
    ]),
    $language,
    CommerceMailIdempotencyKey::for_purchase(123, $mailtype),
    123
);
$message = CommerceMailRuntime::template_registry()->get($mailtype)->render($request);

$listurl = new moodle_url('/local/subscriptions/admin/commerce/mail/templates/index.php');
$editurl = new moodle_url('/local/subscriptions/admin/commerce/mail/templates/edit.php', [
    'mailtype' => $mailtype,
    'language' => $language,
]);

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    ['label' => get_string('crm_commerce_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php')],
    ['label' => get_string('commerce_mail_templates_title', 'local_subscriptions'), 'url' => $listurl],
    ['label' => $title, 'url' => null],
]);
echo CrmPageHeader::render(
    $title,
    get_string('commerce_mail_template_preview_description', 'local_subscriptions'),
    HelpContext::COMMERCE
);
echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::MAIL);

$pageactions = html_writer::link(
    $listurl,
    get_string('back'),
    ['class' => 'btn btn-outline-secondary']
);
$pageactions .= html_writer::link(
    $editurl,
    get_string('edit'),
    ['class' => 'btn btn-primary']
);
echo html_writer::div(
    $pageactions,
    'd-flex flex-wrap align-items-center gap-2 mb-3'
);

echo html_writer::tag('h3', s($message->get_subject()), ['class' => 'h5 mb-3']);

$previewnavigation = CommerceMailPreviewRenderer::render_navigation($url, $previewview);
$fontnavigation = in_array(
    $previewview,
    [CommerceMailPreviewRenderer::DESKTOP, CommerceMailPreviewRenderer::MOBILE],
    true
)
    ? CommerceMailPreviewRenderer::render_font_navigation($url, $previewfont)
    : '';

$languageoptions = [];
foreach (['fr', 'en', 'ru'] as $languagecode) {
    $languageoptions[$languagecode] = CommerceMailAdminPresentation::language_label($languagecode);
}

$languageform = html_writer::start_tag('form', [
    'method' => 'get',
    'action' => (new moodle_url('/local/subscriptions/admin/commerce/mail/templates/preview.php'))->out(false),
    'class' => 'commerce-mail-preview-language',
]);
$languageform .= html_writer::empty_tag('input', [
    'type' => 'hidden',
    'name' => 'mailtype',
    'value' => $mailtype,
]);
$languageform .= html_writer::empty_tag('input', [
    'type' => 'hidden',
    'name' => 'view',
    'value' => $previewview,
]);
$languageform .= html_writer::empty_tag('input', [
    'type' => 'hidden',
    'name' => 'font',
    'value' => $previewfont,
]);
$languageform .= html_writer::label(
    get_string('language'),
    'commerce-mail-preview-language-select',
    false,
    ['class' => 'commerce-mail-preview-language__label']
);
$languageform .= html_writer::select(
    $languageoptions,
    'language',
    $language,
    false,
    [
        'id' => 'commerce-mail-preview-language-select',
        'class' => 'form-select form-select-sm commerce-mail-preview-language__select',
        'onchange' => 'this.form.submit();',
    ]
);
$languageform .= html_writer::end_tag('form');

$toolbar = html_writer::div(
    $previewnavigation,
    'commerce-mail-preview-toolbar__navigation'
);
if ($fontnavigation !== '') {
    $toolbar .= html_writer::div(
        $fontnavigation,
        'commerce-mail-preview-toolbar__font'
    );
}
$toolbar .= html_writer::div(
    $languageform,
    'commerce-mail-preview-toolbar__language'
);

echo html_writer::div($toolbar, 'commerce-mail-preview-toolbar');

echo CommerceMailPreviewRenderer::render(
    $message->get_html(),
    $message->get_text(),
    $previewview,
    $previewfont
);

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
