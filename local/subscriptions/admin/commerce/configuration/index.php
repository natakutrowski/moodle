<?php

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\commerce\rendering\CommerceConfigurationNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

$context = AdminSecurity::require(Capabilities::MANAGE_CONFIGURATION);
$pageurl = new moodle_url('/local/subscriptions/admin/commerce/configuration/index.php');
$title = get_string('commerce_configuration_title', 'local_subscriptions');
CrmPageConfigurator::configure($PAGE, $context, $pageurl, $title, 'local-subscriptions-commerce-configuration-page');

$config = get_config('local_subscriptions');
$adminsettingsurl = new moodle_url('/admin/settings.php', ['section' => 'local_subscriptions_settings']);
$value = static fn(string $name, string $fallback = '—'): string => isset($config->{$name}) && $config->{$name} !== ''
    ? (string)$config->{$name} : $fallback;
$badge = static fn(string $label, string $class, string $icon = ''): array => [
    'html' => html_writer::span(
        ($icon !== '' ? html_writer::tag('i', '', ['class' => $icon, 'aria-hidden' => 'true']) : '') . s($label),
        'badge rounded-pill ' . $class . ' commerce-config-summary-badge'
    ),
];
$onoff = static fn(bool $enabled): array => $badge(
    get_string($enabled ? 'commerce_configuration_status_enabled' : 'commerce_configuration_status_disabled', 'local_subscriptions'),
    $enabled ? 'text-bg-success' : 'text-bg-secondary',
    $enabled ? 'fa-solid fa-check' : 'fa-solid fa-minus'
);
$environment = static fn(string $env): array => $badge(
    strtoupper($env),
    str_starts_with(strtolower($env), 'live') ? 'text-bg-success' : 'text-bg-warning'
);
$runtimebadge = static fn(string $mode): array => $badge(
    strtoupper($mode),
    strtolower($mode) === 'native' ? 'text-bg-success' : (strtolower($mode) === 'shadow' ? 'text-bg-warning' : 'text-bg-secondary')
);
$availabilitybadge = static fn(string $mode): array => $badge(
    get_string('availability_' . $mode, 'local_subscriptions'),
    $mode === 'enabled' ? 'text-bg-success' : ($mode === 'adminonly' ? 'text-bg-warning' : 'text-bg-danger')
);
$provider = static function(string $name): array {
    $normalized = strtolower($name) === 'alfa' ? 'alfa' : 'stripe';
    $label = $normalized === 'alfa' ? 'Alfa' : 'Stripe';
    $src = (new moodle_url('/local/subscriptions/pix/providers/' . $normalized . '.svg'))->out(false);
    return [
        'html' => html_writer::span(
            html_writer::empty_tag('img', [
                'src' => $src,
                'alt' => '',
                'class' => 'commerce-config-summary-provider-icon',
            ]) . s($label),
            'commerce-config-summary-provider'
        ),
    ];
};
$language = static function(string $code): array {
    $code = strtolower(trim($code));
    $flags = ['fr' => '🇫🇷', 'ru' => '🇷🇺', 'en' => '🇬🇧'];
    if ($code === '') {
        return ['html' => html_writer::span(get_string('commerce_configuration_site_default', 'local_subscriptions'), 'badge rounded-pill text-bg-light border')];
    }
    return ['html' => html_writer::span(($flags[$code] ?? '🌐') . ' ' . strtoupper($code), 'badge rounded-pill text-bg-light border commerce-config-summary-language')];
};
$currencyflags = static function(string $csv): array {
    $flags = ['EUR' => '🇪🇺', 'RUB' => '🇷🇺', 'USD' => '🇺🇸', 'GBP' => '🇬🇧', 'CHF' => '🇨🇭', 'CAD' => '🇨🇦', 'JPY' => '🇯🇵'];
    $items = [];
    foreach (array_filter(array_map('trim', explode(',', strtoupper($csv)))) as $currency) {
        $items[] = html_writer::span(($flags[$currency] ?? '💱') . ' ' . s($currency), 'badge rounded-pill text-bg-light border');
    }
    return ['html' => html_writer::span(implode(' ', $items), 'commerce-config-summary-inline-badges')];
};
$duration = static function(string $minutes): string {
    if (!is_numeric($minutes)) {
        return '—';
    }
    $minutes = max(0, (int)$minutes);
    if ($minutes >= 1440 && $minutes % 1440 === 0) {
        return intdiv($minutes, 1440) . ' j';
    }
    if ($minutes >= 60 && $minutes % 60 === 0) {
        return intdiv($minutes, 60) . ' h';
    }
    return $minutes . ' min';
};

$cards = [
    [
        'key' => 'payments',
        'icon' => '💳',
        'title' => get_string('commerce_configuration_payments_title', 'local_subscriptions'),
        'description' => get_string('commerce_configuration_payments_description', 'local_subscriptions'),
        'facts' => [
            get_string('commerce_configuration_fact_provider', 'local_subscriptions') => $provider($value('provider_default', 'stripe')),
            'Stripe' => $environment($value('stripe_env', 'test')),
            'Alfa' => $environment($value('alfa_env', 'test')),
        ],
    ],
    [
        'key' => 'localisation',
        'icon' => '🌍',
        'title' => get_string('commerce_configuration_localisation_title', 'local_subscriptions'),
        'description' => get_string('commerce_configuration_localisation_description', 'local_subscriptions'),
        'facts' => [
            get_string('commerce_configuration_fact_availability', 'local_subscriptions') => $availabilitybadge($value('availability_mode', 'enabled')),
            get_string('commerce_configuration_fact_user_language', 'local_subscriptions') => $language($value('defaultuserlang', '')),
            get_string('commerce_configuration_fact_currencies', 'local_subscriptions') => $currencyflags($value('commerce_enabled_currencies', 'EUR,RUB')),
        ],
    ],
    [
        'key' => 'checkout',
        'icon' => '🛒',
        'title' => get_string('commerce_configuration_checkout_title', 'local_subscriptions'),
        'description' => get_string('commerce_configuration_checkout_description', 'local_subscriptions'),
        'facts' => [
            get_string('commerce_configuration_fact_pending_expiry', 'local_subscriptions') => $duration($value('expire_pending_after_minutes', '60')),
            get_string('commerce_configuration_fact_reminder1', 'local_subscriptions') => $duration($value('reminder1_after_minutes', '1440')),
            get_string('commerce_configuration_fact_guest_cleanup', 'local_subscriptions') => $onoff(!empty($config->guest_checkout_cleanup_enabled)),
        ],
    ],
    [
        'key' => 'communications',
        'icon' => '✉️',
        'title' => get_string('commerce_configuration_communications_title', 'local_subscriptions'),
        'description' => get_string('commerce_configuration_communications_description', 'local_subscriptions'),
        'facts' => [
            get_string('commerce_configuration_fact_support_email', 'local_subscriptions') => $value('support_email'),
            get_string('commerce_configuration_fact_transactional_worker', 'local_subscriptions') => $onoff(!isset($config->commerce_mail_transactional_enabled) || !empty($config->commerce_mail_transactional_enabled)),
            get_string('commerce_configuration_fact_marketing_worker', 'local_subscriptions') => $onoff(!isset($config->commerce_mail_marketing_enabled) || !empty($config->commerce_mail_marketing_enabled)),
        ],
    ],
    [
        'key' => 'legal',
        'icon' => '🧾',
        'title' => get_string('commerce_configuration_legal_title', 'local_subscriptions'),
        'description' => get_string('commerce_configuration_legal_description', 'local_subscriptions'),
        'facts' => [
            get_string('commerce_configuration_fact_invoice_eur', 'local_subscriptions') => ['html' => html_writer::span('🇪🇺 ', 'me-1') . html_writer::span(s($value('invoice_eur_name')), 'fw-semibold text-break')],
            get_string('commerce_configuration_fact_invoice_rub', 'local_subscriptions') => ['html' => html_writer::span('🇷🇺 ', 'me-1') . html_writer::span(s($value('invoice_rub_name')), 'fw-semibold text-break')],
            get_string('commerce_configuration_fact_legal_regions', 'local_subscriptions') => ['html' => html_writer::span('🇷🇺 🇧🇾 + 🌍', 'fw-semibold')],
        ],
    ],
    [
        'key' => 'storefront',
        'icon' => '🏪',
        'title' => get_string('commerce_configuration_storefront_title', 'local_subscriptions'),
        'description' => get_string('commerce_configuration_storefront_description', 'local_subscriptions'),
        'facts' => [
            get_string('commerce_configuration_fact_ai_translation', 'local_subscriptions') => $onoff(!empty($config->storefront_ai_translation_enabled)),
            get_string('commerce_configuration_fact_legacy_featured_plan', 'local_subscriptions') => !empty($config->featured_planid)
                ? $badge('#' . (string)$config->featured_planid, 'text-bg-warning')
                : $badge(get_string('none'), 'text-bg-light border text-dark'),
        ],
    ],
    [
        'key' => 'engine',
        'icon' => '⚙️',
        'title' => get_string('commerce_configuration_engine_title', 'local_subscriptions'),
        'description' => get_string('commerce_configuration_engine_description', 'local_subscriptions'),
        'facts' => [
            get_string('commerce_configuration_fact_runtime', 'local_subscriptions') => $runtimebadge($value('commerce_runtime_mode', 'legacy')),
            get_string('commerce_configuration_fact_read_mode', 'local_subscriptions') => $runtimebadge($value('commerce_runtime_read_mode', 'legacy')),
            get_string('commerce_configuration_fact_engine_switches', 'local_subscriptions') => [
                'html' => html_writer::span(
                    $onoff(!isset($config->commerce_checkout_enabled) || !empty($config->commerce_checkout_enabled))['html'] .
                    $onoff(!isset($config->commerce_fulfillment_enabled) || !empty($config->commerce_fulfillment_enabled))['html'],
                    'commerce-config-summary-stack'
                ),
            ],
        ],
        'technical' => true,
    ],
];

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    ['label' => get_string('crm_commerce_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php')],
    ['label' => $title, 'url' => null],
]);
echo CrmPageHeader::render($title, get_string('commerce_configuration_description_n101', 'local_subscriptions'), HelpContext::COMMERCE);
echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::CONFIGURATION, $context);
echo CommerceConfigurationNavigationRenderer::render(CommerceConfigurationNavigationRenderer::OVERVIEW);

echo html_writer::start_div('d-flex align-items-end justify-content-between gap-3 mb-3');
echo html_writer::div(
    html_writer::tag('h2', get_string('commerce_configuration_system_title', 'local_subscriptions'), ['class' => 'h4 mb-1']) .
    html_writer::tag('p', get_string('commerce_configuration_system_description', 'local_subscriptions'), ['class' => 'text-muted mb-0'])
);
echo html_writer::link($adminsettingsurl, get_string('commerce_configuration_advanced_settings', 'local_subscriptions'), ['class' => 'btn btn-outline-secondary']);
echo html_writer::end_div();

echo html_writer::start_div('row g-3 commerce-configuration-hub');
foreach ($cards as $card) {
    $facts = '';
    foreach ($card['facts'] as $label => $fact) {
        $rendered = is_array($fact) && isset($fact['html'])
            ? $fact['html']
            : html_writer::span(s((string)$fact), 'fw-semibold text-break');
        $facts .= html_writer::div(html_writer::span(s($label), 'text-muted') . $rendered, 'commerce-config-fact');
    }
    $badge = !empty($card['technical'])
        ? html_writer::span(get_string('commerce_configuration_technical_badge', 'local_subscriptions'), 'badge bg-light text-dark border') : '';
    $body = html_writer::div(html_writer::span($card['icon'], 'commerce-configuration-card__icon', ['aria-hidden' => 'true']) . $badge, 'd-flex justify-content-between align-items-start') .
        html_writer::tag('h3', $card['title'], ['class' => 'h5 mt-2 mb-1']) .
        html_writer::tag('p', $card['description'], ['class' => 'text-muted mb-3']) .
        html_writer::div($facts, 'commerce-config-facts') .
        html_writer::link(new moodle_url('/local/subscriptions/admin/commerce/configuration/section.php', ['section' => $card['key']]), get_string('commerce_configuration_open_section', 'local_subscriptions') . ' →', ['class' => 'stretched-link commerce-config-card-link']);
    echo html_writer::div(html_writer::div($body, 'card card-body h-100 position-relative commerce-configuration-card'), 'col-md-6 col-xl-4');
}
echo html_writer::end_div();

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
