<?php
declare(strict_types=1);

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\commerce\rendering\CommerceOffersAccessNavigationRenderer;
use local_subscriptions\crm\commerce\rendering\CommerceOffersAccessWorkflowRenderer;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\subscription_config;

$context = AdminSecurity::require(Capabilities::MANAGE_SUBSCRIPTIONS);
$kind = optional_param('kind', '', PARAM_ALPHA);
$audience = optional_param('audience', '', PARAM_ALPHA);
$kind = in_array($kind, ['offer', 'grant'], true) ? $kind : '';
$audience = in_array($audience, ['one', 'many'], true) ? $audience : '';

if ($kind !== '' && $audience !== '') {
    $destination = match ($kind . ':' . $audience) {
        'offer:one' => new moodle_url('/local/subscriptions/admin/commerce/personal-offers/create.php'),
        'offer:many' => new moodle_url('/local/subscriptions/admin/commerce/personal-offers/campaign_edit.php'),
        'grant:one' => new moodle_url(subscription_config::add_manual_subscription_page(), ['workspace' => 'grants']),
        'grant:many' => new moodle_url('/local/subscriptions/admin/commerce/grants/bulk.php'),
    };
    redirect($destination);
}

$url = new moodle_url(
    '/local/subscriptions/admin/commerce/offers-access/create.php',
    array_filter(['kind' => $kind, 'audience' => $audience])
);
$title = get_string(
    $kind === '' ? 'commerce_offers_access_create_title' : 'commerce_offers_access_create_audience_title',
    'local_subscriptions'
);

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $url,
    $title,
    'local-subscriptions-commerce-offers-access-create-page'
);

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    ['label' => get_string('crm_commerce_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php')],
    ['label' => get_string('commerce_offers_access_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/offers-access/index.php')],
    ['label' => $title, 'url' => null],
]);
echo CrmPageHeader::render(
    $title,
    get_string(
        $kind === '' ? 'commerce_offers_access_create_description' : 'commerce_offers_access_create_audience_description',
        'local_subscriptions'
    ),
    HelpContext::COMMERCE
);
echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::OFFERS_ACCESS, $context);
$localnav = $audience === 'many'
    ? CommerceOffersAccessNavigationRenderer::CAMPAIGNS
    : CommerceOffersAccessNavigationRenderer::OVERVIEW;
if ($kind === 'offer') {
    $localnav = $audience === 'many'
        ? CommerceOffersAccessNavigationRenderer::CAMPAIGNS
        : CommerceOffersAccessNavigationRenderer::OFFERS;
} else if ($kind === 'grant') {
    $localnav = $audience === 'many'
        ? CommerceOffersAccessNavigationRenderer::CAMPAIGNS
        : CommerceOffersAccessNavigationRenderer::GRANTS;
}
echo CommerceOffersAccessNavigationRenderer::render($localnav);

if ($kind === '') {
    echo html_writer::div(
        html_writer::div(get_string('commerce_offers_access_create_question', 'local_subscriptions'), 'crm-offers-access-create-eyebrow')
        . html_writer::div(
            html_writer::link(
                new moodle_url('/local/subscriptions/admin/commerce/offers-access/create.php', array_filter(['kind' => 'offer', 'audience' => $audience])),
                html_writer::span(html_writer::tag('i', '', ['class' => 'fa fa-tag', 'aria-hidden' => 'true']), 'crm-offers-access-create-card-icon')
                . html_writer::tag('h2', get_string('commerce_offers_access_create_offer', 'local_subscriptions'), ['class' => 'crm-offers-access-create-card-title'])
                . html_writer::tag('p', get_string('commerce_offers_access_create_offer_help', 'local_subscriptions'), ['class' => 'crm-offers-access-create-card-copy'])
                . html_writer::span(get_string('commerce_offers_access_create_continue', 'local_subscriptions') . ' →', 'crm-offers-access-create-card-action'),
                ['class' => 'crm-offers-access-create-card is-offer']
            )
            . html_writer::link(
                new moodle_url('/local/subscriptions/admin/commerce/offers-access/create.php', array_filter(['kind' => 'grant', 'audience' => $audience])),
                html_writer::span(html_writer::tag('i', '', ['class' => 'fa fa-key', 'aria-hidden' => 'true']), 'crm-offers-access-create-card-icon')
                . html_writer::tag('h2', get_string('commerce_offers_access_create_grant', 'local_subscriptions'), ['class' => 'crm-offers-access-create-card-title'])
                . html_writer::tag('p', get_string('commerce_offers_access_create_grant_help', 'local_subscriptions'), ['class' => 'crm-offers-access-create-card-copy'])
                . html_writer::span(get_string('commerce_offers_access_create_continue', 'local_subscriptions') . ' →', 'crm-offers-access-create-card-action'),
                ['class' => 'crm-offers-access-create-card is-grant']
            ),
            'crm-offers-access-create-grid'
        ),
        'crm-offers-access-create-shell'
    );
} else {
    echo CommerceOffersAccessWorkflowRenderer::render(
        CommerceOffersAccessWorkflowRenderer::BENEFICIARIES,
        $kind,
        $audience !== '' ? $audience : 'one'
    );

    $kindlabel = get_string(
        $kind === 'offer' ? 'commerce_offers_access_create_offer' : 'commerce_offers_access_create_grant',
        'local_subscriptions'
    );

    echo html_writer::div(
        html_writer::div(
            html_writer::span(
                html_writer::tag('i', '', ['class' => 'fa ' . ($kind === 'offer' ? 'fa-tag' : 'fa-key'), 'aria-hidden' => 'true']),
                'crm-offers-access-create-selection-icon is-' . $kind
            )
            . html_writer::div(
                html_writer::div(get_string('commerce_offers_access_create_selected_action', 'local_subscriptions'), 'crm-offers-access-create-selection-label')
                . html_writer::div(s($kindlabel), 'crm-offers-access-create-selection-value'),
                'crm-offers-access-create-selection-copy'
            )
            . html_writer::link(
                new moodle_url('/local/subscriptions/admin/commerce/offers-access/create.php'),
                get_string('commerce_offers_access_change', 'local_subscriptions'),
                ['class' => 'btn btn-sm btn-outline-secondary ms-auto']
            ),
            'crm-offers-access-create-selection'
        )
        . html_writer::tag('h2', get_string('commerce_offers_access_create_who', 'local_subscriptions'), ['class' => 'crm-offers-access-create-audience-title'])
        . html_writer::div(
            html_writer::link(
                new moodle_url(
                    '/local/subscriptions/admin/commerce/offers-access/create.php',
                    ['kind' => $kind, 'audience' => 'one']
                ),
                html_writer::span(html_writer::tag('i', '', ['class' => 'fa fa-user', 'aria-hidden' => 'true']), 'crm-offers-access-audience-icon')
                . html_writer::tag('h3', get_string('commerce_offers_access_create_one', 'local_subscriptions'), ['class' => 'crm-offers-access-audience-title'])
                . html_writer::tag('p', get_string('commerce_offers_access_create_one_help', 'local_subscriptions'), ['class' => 'crm-offers-access-audience-copy']),
                ['class' => 'crm-offers-access-audience-card']
            )
            . html_writer::link(
                new moodle_url(
                    '/local/subscriptions/admin/commerce/offers-access/create.php',
                    ['kind' => $kind, 'audience' => 'many']
                ),
                html_writer::span(html_writer::tag('i', '', ['class' => 'fa fa-users', 'aria-hidden' => 'true']), 'crm-offers-access-audience-icon')
                . html_writer::tag('h3', get_string('commerce_offers_access_create_many', 'local_subscriptions'), ['class' => 'crm-offers-access-audience-title'])
                . html_writer::tag('p', get_string('commerce_offers_access_create_many_help', 'local_subscriptions'), ['class' => 'crm-offers-access-audience-copy']),
                ['class' => 'crm-offers-access-audience-card']
            ),
            'crm-offers-access-audience-grid'
        ),
        'crm-offers-access-create-shell'
    );
}

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
