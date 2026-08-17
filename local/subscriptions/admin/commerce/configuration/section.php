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
use local_subscriptions\commerce\runtime\switching\CommerceRuntimeConfiguration;
use local_subscriptions\commerce\runtime\switching\CommerceRuntimeMode;

$context = AdminSecurity::require(Capabilities::MANAGE_CONFIGURATION);
$PAGE->set_context($context);
global $DB;
$section = required_param('section', PARAM_ALPHA);
$allowed = ['payments', 'localisation', 'checkout', 'communications', 'legal', 'storefront', 'engine'];
if (!in_array($section, $allowed, true)) {
    throw new moodle_exception('invalidparameter');
}

$langoptions = ['' => get_string('settings:sitedefault', 'local_subscriptions')] + get_string_manager()->get_list_of_translations();
$featuredplanoptions = [0 => get_string('commerce_configuration_no_featured_plan', 'local_subscriptions')];
foreach ($DB->get_records('subscription_plan', [], 'name ASC', 'id,name,is_active') as $plan) {
    $label = format_string((string)$plan->name);
    if (empty($plan->is_active)) {
        $label .= ' · ' . get_string('commerce_configuration_plan_inactive', 'local_subscriptions');
    }
    $featuredplanoptions[(int)$plan->id] = $label;
}
$yesno = [0 => get_string('no'), 1 => get_string('yes')];
$field = static function(string $key, string $label, string $description, string $type = 'text', $default = '', array $options = [], string $param = PARAM_RAW_TRIMMED): array {
    return compact('key', 'label', 'description', 'type', 'default', 'options', 'param');
};

$definitions = [
    'payments' => [
        'icon' => '💳', 'title' => 'commerce_configuration_payments_title', 'description' => 'commerce_configuration_payments_description',
        'groups' => [
            'commerce_configuration_group_payment_routing' => [
                $field('provider_default', 'provider_default', 'provider_default_desc', 'select', 'stripe', ['stripe' => get_string('provider_stripe', 'local_subscriptions'), 'alfa' => get_string('provider_alfa', 'local_subscriptions')]),
                $field('stripe_env', 'commerce_configuration_stripe_environment', 'env_mode_desc', 'select', 'test', ['test' => get_string('stripe_profile_test', 'local_subscriptions'), 'live_ei' => get_string('stripe_profile_live_ei', 'local_subscriptions'), 'live_sas' => get_string('stripe_profile_live_sas', 'local_subscriptions')]),
                $field('alfa_env', 'commerce_configuration_alfa_environment', 'env_mode_desc', 'select', 'test', ['test' => get_string('env_test', 'local_subscriptions'), 'live' => get_string('env_live', 'local_subscriptions')]),
            ],
            'commerce_configuration_group_reconciliation' => [
                $field('stripe_reconciliation_cron_enabled', 'stripe_reconciliation_cron_enabled', 'stripe_reconciliation_cron_enabled_desc', 'checkbox', 0),
                $field('stripe_reconciliation_batch_size', 'stripe_reconciliation_batch_size', 'commerce_configuration_stripe_reconciliation_batch_size_desc', 'number', 20, [], PARAM_INT),
                $field('stripe_reconciliation_min_age', 'stripe_reconciliation_min_age', 'commerce_configuration_stripe_reconciliation_min_age_desc', 'number', 300, [], PARAM_INT),
                $field('stripe_reconciliation_max_age', 'stripe_reconciliation_max_age', 'commerce_configuration_stripe_reconciliation_max_age_desc', 'number', 172800, [], PARAM_INT),
                $field('alfa_reconciliation_cron_enabled', 'settings:alfa_reconciliation_cron_enabled', 'settings:alfa_reconciliation_cron_enabled_desc', 'checkbox', 0),
                $field('alfa_reconciliation_batch_size', 'settings:alfa_reconciliation_batch_size', 'settings:alfa_reconciliation_batch_size_desc', 'number', 20, [], PARAM_INT),
                $field('alfa_reconciliation_min_age', 'settings:alfa_reconciliation_min_age', 'settings:alfa_reconciliation_min_age_desc', 'number', 300, [], PARAM_INT),
                $field('alfa_reconciliation_max_age', 'settings:alfa_reconciliation_max_age', 'settings:alfa_reconciliation_max_age_desc', 'number', 172800, [], PARAM_INT),
            ],
            'commerce_configuration_group_payment_integrity' => [
                $field('payments_lock_strict', 'settings_paylock_strict', 'settings_paylock_strict_desc', 'checkbox', 0),
                $field('payments_mismatch_tolerance_cents', 'settings_paylock_tolerance', 'settings_paylock_tolerance_desc', 'number', 2, [], PARAM_INT),
            ],
        ],
    ],
    'localisation' => [
        'icon' => '🌍', 'title' => 'commerce_configuration_localisation_title', 'description' => 'commerce_configuration_localisation_description',
        'groups' => [
            'commerce_configuration_group_commerce_availability' => [
                $field('availability_mode', 'commerce_configuration_availability_label', 'commerce_configuration_availability_desc', 'select', 'enabled', ['enabled' => get_string('availability_enabled', 'local_subscriptions'), 'adminonly' => get_string('availability_adminonly', 'local_subscriptions'), 'disabled' => get_string('availability_disabled', 'local_subscriptions')]),
            ],
            'commerce_configuration_group_languages' => [
                $field('defaultuserlang', 'commerce_configuration_default_user_language_label', 'commerce_configuration_default_user_language_desc', 'select', '', $langoptions),
                $field('defaultemaillang', 'commerce_configuration_default_email_language_label', 'commerce_configuration_default_email_language_desc', 'select', '', $langoptions),
            ],
            'commerce_configuration_group_currencies' => [
                $field('commerce_enabled_currencies', 'commerce_configuration_enabled_currencies_label', 'commerce_configuration_enabled_currencies_desc', 'multicheck_csv', 'EUR,RUB', [
                    'EUR' => '🇪🇺 EUR — ' . get_string('commerce_configuration_currency_eur', 'local_subscriptions'),
                    'RUB' => '🇷🇺 RUB — ' . get_string('commerce_configuration_currency_rub', 'local_subscriptions'),
                    'USD' => '🇺🇸 USD — ' . get_string('commerce_configuration_currency_usd', 'local_subscriptions'),
                    'GBP' => '🇬🇧 GBP — ' . get_string('commerce_configuration_currency_gbp', 'local_subscriptions'),
                    'CHF' => '🇨🇭 CHF — ' . get_string('commerce_configuration_currency_chf', 'local_subscriptions'),
                    'CAD' => '🇨🇦 CAD — ' . get_string('commerce_configuration_currency_cad', 'local_subscriptions'),
                    'JPY' => '🇯🇵 JPY — ' . get_string('commerce_configuration_currency_jpy', 'local_subscriptions'),
                ]),
                $field('display_currency_symbols', 'commerce_configuration_currency_symbols_label', 'commerce_configuration_currency_symbols_desc', 'checkbox', 1),
            ],
        ],
    ],
    'checkout' => [
        'icon' => '🛒', 'title' => 'commerce_configuration_checkout_title', 'description' => 'commerce_configuration_checkout_description',
        'groups' => [
            'commerce_configuration_group_payment_lifecycle' => [
                $field('expire_pending_after_minutes', 'expire_pending_after_minutes_label', 'commerce_configuration_expire_pending_after_minutes_desc', 'duration_minutes', 60, [], PARAM_INT),
            ],
            'commerce_configuration_group_payment_reminders' => [
                $field('reminder1_after_minutes', 'reminder1_after_minutes_label', 'commerce_configuration_reminder1_after_minutes_desc', 'duration_minutes', 1440, [], PARAM_INT),
                $field('reminder2_after_minutes', 'reminder2_after_minutes_label', 'commerce_configuration_reminder2_after_minutes_desc', 'duration_minutes', 4320, [], PARAM_INT),
            ],
            'commerce_configuration_group_guest_cleanup' => [
                $field('guest_checkout_cleanup_enabled', 'settings:guest_checkout_cleanup_enabled', 'settings:guest_checkout_cleanup_enabled_desc', 'checkbox', 0),
                $field('guest_checkout_cleanup_age_days', 'settings:guest_checkout_cleanup_age_days', 'settings:guest_checkout_cleanup_age_days_desc', 'number', 30, [], PARAM_INT),
                $field('guest_checkout_cleanup_batch_size', 'settings:guest_checkout_cleanup_batch_size', 'settings:guest_checkout_cleanup_batch_size_desc', 'number', 20, [], PARAM_INT),
            ],
        ],
    ],
    'communications' => [
        'icon' => '✉️', 'title' => 'commerce_configuration_communications_title', 'description' => 'commerce_configuration_communications_description',
        'groups' => [
            'commerce_configuration_group_mail_identity' => [
                $field('support_email', 'settings_support_email', 'commerce_configuration_support_email_desc', 'email', 'support@campusfr.fr', [], PARAM_EMAIL),
                $field('brand_logo_url', 'commerce_configuration_brand_logo_email_label', 'commerce_configuration_brand_logo_email_desc', 'url', '', [], PARAM_URL),
            ],
            'commerce_configuration_group_mail_global_throttle' => [
                $field('commerce_mail_global_hourly_limit', 'commerce_mail_configuration_global_hourly', 'commerce_mail_configuration_global_hourly_help', 'number', 0, [], PARAM_INT),
            ],
            'commerce_configuration_group_mail_workers' => [
                $field('commerce_mail_transactional_enabled', 'commerce_mail_configuration_transactional_title', 'commerce_configuration_transactional_enabled_desc', 'checkbox', 1),
                $field('commerce_mail_transactional_batch_size', 'commerce_configuration_transactional_batch_label', 'commerce_mail_configuration_transactional_batch_help', 'number', 50, [], PARAM_INT),
                $field('commerce_mail_transactional_hourly_limit', 'commerce_configuration_transactional_hourly_label', 'commerce_mail_configuration_hourly_zero_help', 'number', 0, [], PARAM_INT),
                $field('personal_offer_mail_enabled', 'commerce_mail_configuration_personal_title', 'commerce_configuration_personal_offer_enabled_desc', 'checkbox', 1),
                $field('personal_offer_mail_batch_size', 'commerce_configuration_personal_offer_batch_label', 'commerce_mail_configuration_personal_batch_help', 'number', 20, [], PARAM_INT),
                $field('personal_offer_mail_hourly_limit', 'commerce_configuration_personal_offer_hourly_label', 'commerce_mail_configuration_personal_hourly_help', 'number', 100, [], PARAM_INT),
                $field('commerce_mail_marketing_enabled', 'commerce_mail_configuration_marketing_title', 'commerce_configuration_marketing_enabled_desc', 'checkbox', 1),
                $field('commerce_mail_marketing_batch_size', 'commerce_configuration_marketing_batch_label', 'commerce_mail_configuration_marketing_batch_help', 'number', 50, [], PARAM_INT),
                $field('commerce_mail_marketing_hourly_limit', 'commerce_configuration_marketing_hourly_label', 'commerce_mail_configuration_marketing_hourly_help', 'number', 250, [], PARAM_INT),
            ],
            'commerce_configuration_group_mail_audit' => [
                $field('commerce_mail_audit_copy_enabled', 'commerce_configuration_audit_copy_generation_label', 'commerce_configuration_audit_copy_generation_desc', 'checkbox', 0),
                $field('commerce_mail_audit_copy_address', 'commerce_configuration_copy_destination_label', 'commerce_configuration_copy_destination_desc', 'email', 'log@campusfr.fr', [], PARAM_EMAIL),
                $field('commerce_mail_audit_enabled', 'commerce_configuration_audit_worker_label', 'commerce_configuration_audit_worker_desc', 'checkbox', 1),
                $field('commerce_mail_audit_batch_size', 'commerce_configuration_audit_batch_label', 'commerce_mail_configuration_audit_batch_help', 'number', 10, [], PARAM_INT),
                $field('commerce_mail_audit_hourly_limit', 'commerce_configuration_audit_hourly_label', 'commerce_mail_configuration_audit_hourly_help', 'number', 50, [], PARAM_INT),
            ],
            'commerce_configuration_group_legacy_mail' => [
                $field('legacy_auto_mail_enabled', 'commerce_mail_configuration_legacy_master', 'commerce_mail_configuration_legacy_master_help', 'checkbox', 0),
                $field('legacy_auto_payment_reminders_enabled', 'commerce_mail_configuration_legacy_payment_reminders', 'commerce_mail_configuration_legacy_payment_reminders_help', 'checkbox', 0),
                $field('legacy_auto_expiry_reminders_enabled', 'commerce_mail_configuration_legacy_expiry_reminders', 'commerce_mail_configuration_legacy_expiry_reminders_help', 'checkbox', 0),
                $field('legacy_auto_lifecycle_emails_enabled', 'commerce_mail_configuration_legacy_lifecycle', 'commerce_mail_configuration_legacy_lifecycle_help', 'checkbox', 0),
            ],
        ],
    ],
    'legal' => [
        'icon' => '🧾', 'title' => 'commerce_configuration_legal_title', 'description' => 'commerce_configuration_legal_description',
        'groups' => [
            'commerce_configuration_group_invoice_eur' => [
                $field('invoice_eur_name', 'commerce_i411_invoice_name', 'commerce_configuration_invoice_name_desc', 'text'),
                $field('invoice_eur_address', 'commerce_i411_invoice_address', 'commerce_configuration_invoice_address_desc', 'textarea'),
                $field('invoice_eur_legal', 'commerce_i411_invoice_legal', 'commerce_configuration_invoice_legal_desc', 'textarea'),
                $field('invoice_eur_email', 'commerce_i411_invoice_email', 'commerce_configuration_invoice_email_desc', 'email', '', [], PARAM_EMAIL),
                $field('invoice_eur_phone', 'commerce_i411_invoice_phone', 'commerce_configuration_invoice_phone_desc', 'text'),
                $field('invoice_eur_website', 'commerce_i411_invoice_website', 'commerce_configuration_invoice_website_desc', 'text'),
                $field('invoice_eur_tax_notice', 'commerce_i411_invoice_tax_notice', 'commerce_configuration_invoice_tax_notice_desc', 'textarea'),
                $field('invoice_eur_footer', 'commerce_i411_invoice_footer', 'commerce_configuration_invoice_footer_desc', 'textarea'),
            ],
            'commerce_configuration_group_invoice_rub' => [
                $field('invoice_rub_name', 'commerce_i411_invoice_name', 'commerce_configuration_invoice_name_desc', 'text'),
                $field('invoice_rub_address', 'commerce_i411_invoice_address', 'commerce_configuration_invoice_address_desc', 'textarea'),
                $field('invoice_rub_legal', 'commerce_i411_invoice_legal', 'commerce_configuration_invoice_legal_desc', 'textarea'),
                $field('invoice_rub_email', 'commerce_i411_invoice_email', 'commerce_configuration_invoice_email_desc', 'email', '', [], PARAM_EMAIL),
                $field('invoice_rub_phone', 'commerce_i411_invoice_phone', 'commerce_configuration_invoice_phone_desc', 'text'),
                $field('invoice_rub_website', 'commerce_i411_invoice_website', 'commerce_configuration_invoice_website_desc', 'text'),
                $field('invoice_rub_tax_notice', 'commerce_i411_invoice_tax_notice', 'commerce_configuration_invoice_tax_notice_desc', 'textarea'),
                $field('invoice_rub_footer', 'commerce_i411_invoice_footer', 'commerce_configuration_invoice_footer_desc', 'textarea'),
            ],
            'commerce_configuration_group_legal_ru_by' => [
                $field('policy_url_ru', 'commerce_configuration_policy_document_label', 'commerce_configuration_policy_url_ru_by_desc', 'text'),
                $field('terms_url_ru', 'commerce_configuration_terms_document_label', 'commerce_configuration_terms_url_ru_by_desc', 'text'),
                $field('offer_url_ru', 'commerce_configuration_offer_document_label', 'commerce_configuration_offer_url_ru_by_desc', 'text'),
            ],
            'commerce_configuration_group_legal_row' => [
                $field('policy_url_row', 'commerce_configuration_policy_document_label', 'commerce_configuration_policy_url_row_desc', 'text'),
                $field('terms_url_row', 'commerce_configuration_terms_document_label', 'commerce_configuration_terms_url_row_desc', 'text'),
                $field('offer_url_row', 'commerce_configuration_offer_document_label', 'commerce_configuration_offer_url_row_desc', 'text'),
            ],
        ],
    ],
    'storefront' => [
        'icon' => '🏪', 'title' => 'commerce_configuration_storefront_title', 'description' => 'commerce_configuration_storefront_description',
        'groups' => [
            'commerce_configuration_group_storefront_editorial' => [
                $field('storefront_ai_translation_enabled', 'settings:storefront_ai_translation_enabled', 'commerce_configuration_storefront_ai_translation_desc', 'checkbox', 0),
            ],
            'commerce_configuration_group_storefront_legacy' => [
                $field('featured_planid', 'commerce_configuration_featured_plan_legacy_label', 'commerce_configuration_featured_plan_legacy_desc', 'select', 0, $featuredplanoptions, PARAM_INT),
            ],
        ],
    ],
    'engine' => [
        'icon' => '⚙️', 'title' => 'commerce_configuration_engine_title', 'description' => 'commerce_configuration_engine_description',
        'groups' => [
            'commerce_configuration_group_engine_availability' => [
                $field('commerce_checkout_enabled', 'settings:commerce_checkout_enabled', 'commerce_configuration_checkout_engine_enabled_desc', 'checkbox', 1),
                $field('commerce_fulfillment_enabled', 'settings:commerce_fulfillment_enabled', 'commerce_configuration_fulfillment_engine_enabled_desc', 'checkbox', 1),
            ],
            'commerce_configuration_group_runtime' => [
                $field('commerce_runtime_mode', 'commerce_configuration_runtime_mode_label', 'commerce_configuration_runtime_mode_desc', 'select', 'legacy', [
                    CommerceRuntimeMode::LEGACY => get_string('commerce_runtime_mode_legacy', 'local_subscriptions'),
                    CommerceRuntimeMode::SHADOW => get_string('commerce_runtime_mode_shadow', 'local_subscriptions'),
                    CommerceRuntimeMode::NATIVE => get_string('commerce_runtime_mode_native', 'local_subscriptions'),
                ]),
                $field('commerce_runtime_native_fallback_enabled', 'commerce_runtime_native_fallback_enabled', 'commerce_configuration_runtime_fallback_desc', 'checkbox', 1),
            ],
            'commerce_configuration_group_runtime_reads' => [
                $field('commerce_runtime_read_mode', 'commerce_configuration_runtime_read_mode_label', 'commerce_configuration_runtime_read_mode_desc', 'select', 'legacy', [
                    'legacy' => get_string('settings:commerce_runtime_read_mode_legacy', 'local_subscriptions'),
                    'shadow' => get_string('settings:commerce_runtime_read_mode_shadow', 'local_subscriptions'),
                    'native' => get_string('settings:commerce_runtime_read_mode_native', 'local_subscriptions'),
                    'auto' => get_string('settings:commerce_runtime_read_mode_auto', 'local_subscriptions'),
                ]),
                $field('commerce_runtime_read_strict', 'commerce_configuration_runtime_read_strict_label', 'commerce_configuration_runtime_read_strict_desc', 'checkbox', 0),
            ],
            'commerce_configuration_group_native_tools' => [
                $field('commerce_native_reconciliation_enabled', 'commerce_configuration_native_reconciliation_label', 'commerce_configuration_native_reconciliation_desc', 'checkbox', 0),
                $field('commerce_native_repair_enabled', 'commerce_configuration_native_repair_label', 'commerce_configuration_native_repair_desc', 'checkbox', 0),
            ],
        ],
    ],
];

$definition = $definitions[$section];
$title = get_string($definition['title'], 'local_subscriptions');
$pageurl = new moodle_url('/local/subscriptions/admin/commerce/configuration/section.php', ['section' => $section]);
CrmPageConfigurator::configure($PAGE, $context, $pageurl, $title, 'local-subscriptions-commerce-configuration-section');

$allfields = [];
foreach ($definition['groups'] as $fields) {
    foreach ($fields as $definitionfield) {
        $allfields[$definitionfield['key']] = $definitionfield;
    }
}

if (data_submitted() && confirm_sesskey()) {
    if ($section === 'engine') {
        $submittedmode = optional_param('commerce_runtime_mode', CommerceRuntimeMode::LEGACY, PARAM_ALPHA);
        if (!in_array($submittedmode, CommerceRuntimeMode::all(), true)) {
            throw new moodle_exception('invalidparameter');
        }
        $submittedreconciliation = optional_param('commerce_native_reconciliation_enabled', 0, PARAM_BOOL) ? 1 : 0;
        $submittedrepair = optional_param('commerce_native_repair_enabled', 0, PARAM_BOOL) ? 1 : 0;
        if ($submittedrepair && !$submittedreconciliation) {
            throw new moodle_exception('commerce_configuration_repair_requires_reconciliation', 'local_subscriptions');
        }
    }

    foreach ($allfields as $key => $definitionfield) {
        if ($definitionfield['type'] === 'checkbox') {
            $clean = optional_param($key, 0, PARAM_BOOL) ? 1 : 0;
        } else if ($definitionfield['type'] === 'multicheck_csv') {
            $selected = optional_param_array($key, [], PARAM_ALPHANUMEXT);
            $selected = array_values(array_intersect(array_keys($definitionfield['options']), $selected));
            if ($selected === []) {
                throw new moodle_exception('commerce_configuration_currency_required', 'local_subscriptions');
            }
            $clean = implode(',', $selected);
        } else {
            $raw = optional_param($key, (string)$definitionfield['default'], PARAM_RAW);
            $clean = clean_param($raw, $definitionfield['param']);
            if ($definitionfield['type'] === 'select' && !array_key_exists((string)$clean, $definitionfield['options'])) {
                throw new moodle_exception('invalidparameter');
            }
        }
        if ($key === 'commerce_runtime_mode') {
            (new CommerceRuntimeConfiguration())->set_mode((string)$clean);
        } else {
            set_config($key, $clean, 'local_subscriptions');
        }
        if ($key === 'commerce_mail_audit_copy_address') {
            // One destination for Native audit copies and Legacy administrative copies.
            set_config('email_copy_to', $clean, 'local_subscriptions');
        }
    }
    redirect($pageurl, get_string('commerce_configuration_saved', 'local_subscriptions'), null, \core\output\notification::NOTIFY_SUCCESS);
}

$config = get_config('local_subscriptions');
$string = static function(string $key): string {
    return get_string_manager()->string_exists($key, 'local_subscriptions') ? get_string($key, 'local_subscriptions') : $key;
};

$renderfield = static function(array $fielddef) use ($config, $string): string {
    $key = $fielddef['key'];
    $current = property_exists($config, $key) ? (string)$config->{$key} : (string)$fielddef['default'];
    if ($key === 'commerce_mail_audit_copy_address' && trim($current) === '' && property_exists($config, 'email_copy_to')) {
        $current = (string)$config->email_copy_to;
    }
    $attrs = ['name' => $key, 'id' => 'id_' . $key, 'class' => 'form-control'];
    $interpreted = '';
    if ($fielddef['type'] === 'number' || $fielddef['type'] === 'duration_minutes') {
        $attrs['type'] = 'number'; $attrs['step'] = '1';
        $control = html_writer::empty_tag('input', $attrs + ['value' => $current]);
        if ($fielddef['type'] === 'duration_minutes') {
            $minutes = max(0, (int)$current);
            if ($minutes >= 1440 && $minutes % 1440 === 0) {
                $days = intdiv($minutes, 1440);
                $interpreted = get_string('commerce_configuration_duration_days', 'local_subscriptions', $days);
            } else if ($minutes >= 60 && $minutes % 60 === 0) {
                $hours = intdiv($minutes, 60);
                $interpreted = get_string('commerce_configuration_duration_hours', 'local_subscriptions', $hours);
            } else {
                $interpreted = get_string('commerce_configuration_duration_minutes', 'local_subscriptions', $minutes);
            }
        }
    } else if ($fielddef['type'] === 'checkbox') {
        $control = html_writer::checkbox($key, '1', !empty($current), '', ['id' => 'id_' . $key, 'class' => 'form-check-input']);
    } else if ($fielddef['type'] === 'select') {
        $control = html_writer::select($fielddef['options'], $key, $current, false, ['id' => 'id_' . $key, 'class' => 'form-select']);
    } else if ($fielddef['type'] === 'textarea') {
        $control = html_writer::tag('textarea', s($current), ['name' => $key, 'id' => 'id_' . $key, 'class' => 'form-control', 'rows' => 4]);
    } else if ($fielddef['type'] === 'multicheck_csv') {
        $selected = array_filter(array_map('trim', explode(',', $current)));
        $items = [];
        foreach ($fielddef['options'] as $optionvalue => $optionlabel) {
            $checkboxid = 'id_' . $key . '_' . strtolower($optionvalue);
            $items[] = html_writer::label(
                html_writer::checkbox($key . '[]', $optionvalue, in_array($optionvalue, $selected, true), '', ['id' => $checkboxid, 'class' => 'form-check-input'])
                    . html_writer::span(s($optionlabel), 'commerce-config-currency-label'),
                $checkboxid,
                false,
                ['class' => 'commerce-config-currency-option']
            );
        }
        $control = html_writer::div(implode('', $items), 'commerce-config-currency-grid');
    } else {
        $attrs['type'] = $fielddef['type'] === 'email' ? 'email' : ($fielddef['type'] === 'url' ? 'url' : 'text');
        $control = html_writer::empty_tag('input', $attrs + ['value' => $current]);
    }
    $technical = html_writer::tag('code', 'local_subscriptions | ' . s($key), ['class' => 'commerce-config-technical-name']);
    return html_writer::div(
        html_writer::tag('label', (($key === 'stripe_env' || str_starts_with($key, 'stripe_reconciliation_') || str_starts_with($key, 'invoice_eur_')) ? html_writer::empty_tag('img', ['src' => (new moodle_url('/local/subscriptions/pix/providers/stripe.svg'))->out(false), 'alt' => '', 'class' => 'commerce-config-provider-icon']) . ' ' : (($key === 'alfa_env' || str_starts_with($key, 'alfa_reconciliation_') || str_starts_with($key, 'invoice_rub_')) ? html_writer::empty_tag('img', ['src' => (new moodle_url('/local/subscriptions/pix/providers/alfa.svg'))->out(false), 'alt' => '', 'class' => 'commerce-config-provider-icon']) . ' ' : '')) . s($string($fielddef['label'])), ['for' => 'id_' . $key, 'class' => 'form-label fw-semibold mb-1']) .
        html_writer::div($technical, 'mb-2') . $control .
        ($interpreted !== '' ? html_writer::div(s($interpreted), 'commerce-config-interpreted-value mt-2') : '') .
        html_writer::tag('div', s($string($fielddef['description'])), ['class' => 'form-text mt-2']),
        'commerce-config-edit-field'
    );
};

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    ['label' => get_string('crm_commerce_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php')],
    ['label' => get_string('commerce_configuration_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/configuration/index.php')],
    ['label' => $title, 'url' => null],
]);
echo CrmPageHeader::render(
    $definition['icon'] . ' ' . $title,
    get_string($definition['description'], 'local_subscriptions'),
    HelpContext::COMMERCE
);
echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::CONFIGURATION, $context);
echo CommerceConfigurationNavigationRenderer::render($section);

echo html_writer::start_div('commerce-config-section');
// N10.2 used commerce_configuration_section_notice for the former read-only view.
echo html_writer::div(get_string('commerce_configuration_edit_notice', 'local_subscriptions'), 'alert alert-info mb-4');
if ($section === 'checkout') {
    echo html_writer::div(get_string('commerce_configuration_checkout_mail_policy_note', 'local_subscriptions'), 'commerce-config-subtle-note mb-4');
} else if ($section === 'legal') {
    echo html_writer::div(get_string('commerce_configuration_legal_resolution_note', 'local_subscriptions'), 'commerce-config-subtle-note mb-4');
}
if ($section === 'communications') {
    echo html_writer::div(
        get_string('commerce_configuration_communications_mail_engine_note', 'local_subscriptions')
            . ' ' . html_writer::link(
                new moodle_url('/local/subscriptions/admin/commerce/mail/configuration.php'),
                get_string('commerce_configuration_open_mail_engine', 'local_subscriptions')
            ),
        'commerce-config-subtle-note mb-4'
    );
}

if ($section === 'storefront') {
    $storefrontlinks = html_writer::link(
        new moodle_url('/local/subscriptions/admin/commerce/products/index.php'),
        get_string('commerce_configuration_storefront_open_products', 'local_subscriptions'),
        ['class' => 'btn btn-sm btn-outline-primary']
    ) . html_writer::link(
        new moodle_url('/local/subscriptions/admin/commerce/showrooms/index.php'),
        get_string('commerce_configuration_storefront_open_showrooms', 'local_subscriptions'),
        ['class' => 'btn btn-sm btn-outline-primary']
    );
    echo html_writer::div(
        html_writer::tag('strong', get_string('commerce_configuration_storefront_scope_title', 'local_subscriptions'))
            . html_writer::tag('p', get_string('commerce_configuration_storefront_scope_desc', 'local_subscriptions'), ['class' => 'mb-2'])
            . html_writer::div($storefrontlinks, 'd-flex gap-2 flex-wrap'),
        'commerce-config-subtle-note mb-4'
    );
}

if ($section === 'engine') {
    $runtimeconfig = new CommerceRuntimeConfiguration();
    $shadowactive = (bool)get_config('local_subscriptions', 'commerce_fulfillment_shadow_enabled');
    $expectedshadow = $runtimeconfig->get_mode() === CommerceRuntimeMode::SHADOW;
    $shadowstatus = $shadowactive
        ? get_string('commerce_configuration_status_enabled', 'local_subscriptions')
        : get_string('commerce_configuration_status_disabled', 'local_subscriptions');
    $shadowclass = $shadowactive === $expectedshadow ? 'text-bg-success' : 'text-bg-warning';
    echo html_writer::div(
        html_writer::tag('strong', get_string('commerce_configuration_engine_shadow_status_title', 'local_subscriptions'))
            . html_writer::span($shadowstatus, 'badge rounded-pill ' . $shadowclass . ' ms-2')
            . html_writer::tag('p', get_string('commerce_configuration_engine_shadow_status_desc', 'local_subscriptions'), ['class' => 'mb-1 mt-2'])
            . html_writer::tag('code', 'local_subscriptions | commerce_fulfillment_shadow_enabled', ['class' => 'commerce-config-technical-name']),
        'commerce-config-subtle-note mb-4'
    );
}

echo html_writer::start_tag('form', ['method' => 'post', 'action' => $pageurl, 'class' => 'commerce-config-edit-form']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
foreach ($definition['groups'] as $groupkey => $fields) {
    $groupicons = [
        'commerce_configuration_group_payment_routing' => '↗',
        'commerce_configuration_group_reconciliation' => '⟳',
        'commerce_configuration_group_payment_integrity' => '🛡',
        'commerce_configuration_group_general' => '⚙',
        'commerce_configuration_group_commerce_availability' => '🌐',
        'commerce_configuration_group_languages' => '文',
        'commerce_configuration_group_currencies' => '💱',
        'commerce_configuration_group_payment_lifecycle' => '⌛',
        'commerce_configuration_group_payment_reminders' => '🔔',
        'commerce_configuration_group_guest_cleanup' => '🧹',
        'commerce_configuration_group_mail_identity' => '✉',
        'commerce_configuration_group_mail_global_throttle' => '🚦',
        'commerce_configuration_group_mail_workers' => '⚙',
        'commerce_configuration_group_mail_audit' => '🛡',
        'commerce_configuration_group_legacy_mail' => '⚠',
        'commerce_configuration_group_legal_ru_by' => '🇷🇺 🇧🇾',
        'commerce_configuration_group_legal_row' => '🌍',
        'commerce_configuration_group_storefront_editorial' => '✨',
        'commerce_configuration_group_storefront_legacy' => '🕰',
        'commerce_configuration_group_engine_availability' => '🛡',
        'commerce_configuration_group_runtime' => '⚙',
        'commerce_configuration_group_runtime_reads' => '👁',
        'commerce_configuration_group_native_tools' => '🧰',
    ];
    $groupicon = $groupicons[$groupkey] ?? '⚙';
    $groupclasses = 'card mb-3 commerce-config-section-card';
    if (in_array($groupkey, [
        'commerce_configuration_group_legacy_mail',
        'commerce_configuration_group_storefront_legacy',
    ], true)) {
        $groupclasses .= ' commerce-config-section-card--legacy';
    }
    echo html_writer::start_div($groupclasses);
    echo html_writer::start_div('card-body');
    echo html_writer::tag('h2', html_writer::span($groupicon, 'commerce-config-group-icon') . ' ' . get_string($groupkey, 'local_subscriptions'), ['class' => 'h5 mb-3 commerce-config-group-title']);
    echo html_writer::start_div('row g-4');
    foreach ($fields as $fielddef) {
        echo html_writer::div($renderfield($fielddef), 'col-12 col-xl-6');
    }
    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_div();
}
echo html_writer::div(
    html_writer::empty_tag('input', ['type' => 'submit', 'class' => 'btn btn-primary', 'value' => get_string('savechanges')]) .
    html_writer::link(new moodle_url('/local/subscriptions/admin/commerce/configuration/index.php'), get_string('cancel'), ['class' => 'btn btn-outline-secondary']),
    'd-flex gap-2 mt-4 mb-4'
);
echo html_writer::end_tag('form');
echo html_writer::end_div();

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
