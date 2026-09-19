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
use local_subscriptions\commerce\catalog\currency\CommerceCurrencyRegistry;
use local_subscriptions\commerce\payment\method\CommercePaymentMethodCatalogue;
use local_subscriptions\crm\commerce\rendering\CommercePaymentProviderOperationalRenderer;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderOperationalStatusService;
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

$paymentprovideroptions = [
    'stripe' => get_string('provider_stripe', 'local_subscriptions'),
    'alfa' => get_string('provider_alfa', 'local_subscriptions'),
    'paypal' => get_string('provider_paypal', 'local_subscriptions'),
];

$paymentmethodoptions = [];
foreach (CommercePaymentMethodCatalogue::keys() as $paymentmethodkey) {
    $paymentmethodoptions[$paymentmethodkey] = get_string(
        'commerce_payment_method_' . $paymentmethodkey,
        'local_subscriptions'
    );
}

$field = static function(string $key, string $label, string $description, string $type = 'text', $default = '', array $options = [], string $param = PARAM_RAW_TRIMMED): array {
    return compact('key', 'label', 'description', 'type', 'default', 'options', 'param');
};

$definitions = [
    'payments' => [
        'icon' => '💳', 'title' => 'commerce_configuration_payments_title', 'description' => 'commerce_configuration_payments_description',
        'groups' => [
            'commerce_configuration_group_payment_routing' => [
                $field(
                    'provider_default',
                    'provider_default',
                    'provider_default_desc',
                    'select',
                    'stripe',
                    $paymentprovideroptions
                ),
                $field('stripe_env', 'commerce_configuration_stripe_environment', 'env_mode_desc', 'select', 'test', ['test' => get_string('stripe_profile_test', 'local_subscriptions'), 'live_ei' => get_string('stripe_profile_live_ei', 'local_subscriptions'), 'live_sas' => get_string('stripe_profile_live_sas', 'local_subscriptions')]),
                $field('alfa_env', 'commerce_configuration_alfa_environment', 'env_mode_desc', 'select', 'test', ['test' => get_string('env_test', 'local_subscriptions'), 'live' => get_string('env_live', 'local_subscriptions')]),
                $field('alfa_widget_enabled', 'alfa_widget_enabled', 'alfa_widget_enabled_desc', 'select', 0, $yesno),
                $field('alfa_iframe_enabled', 'alfa_iframe_enabled', 'alfa_iframe_enabled_desc', 'select', 0, $yesno),
                $field('paypal_env', 'commerce_configuration_paypal_environment', 'paypal_env_desc', 'select', 'sandbox', ['sandbox' => get_string('paypal_env_sandbox', 'local_subscriptions'), 'live' => get_string('paypal_env_live', 'local_subscriptions')]),
            ],
            'commerce_configuration_group_payment_credentials' => [
                $field('stripe_test_publishable', 'stripe_publishable_test', 'commerce_provider_ops_secret_hub_desc', 'text', ''),
                $field('stripe_test_secret', 'stripe_secret_test', 'commerce_provider_ops_secret_hub_desc', 'password_keep', ''),
                $field('stripe_test_webhook_secret', 'stripe_webhook_secret_test', 'commerce_provider_ops_secret_hub_desc', 'password_keep', ''),
                $field('stripe_live_publishable', 'stripe_publishable_live', 'commerce_provider_ops_secret_hub_desc', 'text', ''),
                $field('stripe_live_secret', 'stripe_secret_live', 'commerce_provider_ops_secret_hub_desc', 'password_keep', ''),
                $field('stripe_live_webhook_secret', 'stripe_webhook_secret_live', 'commerce_provider_ops_secret_hub_desc', 'password_keep', ''),
                $field('stripe_live_sas_publishable', 'stripe_publishable_live_sas', 'commerce_provider_ops_secret_hub_desc', 'text', ''),
                $field('stripe_live_sas_secret', 'stripe_secret_live_sas', 'commerce_provider_ops_secret_hub_desc', 'password_keep', ''),
                $field('stripe_live_sas_webhook_secret', 'stripe_webhook_secret_live_sas', 'commerce_provider_ops_secret_hub_desc', 'password_keep', ''),
                $field('alfa_test_api_base', 'alfa_api_base_test', 'commerce_provider_ops_secret_hub_desc', 'url', 'https://alfa.rbsuat.com', [], PARAM_URL),
                $field('alfa_test_username', 'alfa_username_test', 'commerce_provider_ops_secret_hub_desc', 'text', ''),
                $field('alfa_test_password', 'alfa_password_test', 'commerce_provider_ops_secret_hub_desc', 'password_keep', ''),
                $field('alfa_test_token', 'alfa_token_test', 'commerce_provider_ops_secret_hub_desc', 'password_keep', ''),
                $field('alfa_test_widget_token', 'alfa_widget_token_test', 'alfa_widget_token_desc', 'text', ''),
                $field('alfa_test_refund_username', 'alfa_refund_username_test', 'alfa_refund_credentials_desc', 'text', ''),
                $field('alfa_test_refund_password', 'alfa_refund_password_test', 'alfa_refund_credentials_desc', 'password_keep', ''),
                $field('alfa_live_api_base', 'alfa_api_base_live', 'commerce_provider_ops_secret_hub_desc', 'url', '', [], PARAM_URL),
                $field('alfa_live_username', 'alfa_username_live', 'commerce_provider_ops_secret_hub_desc', 'text', ''),
                $field('alfa_live_password', 'alfa_password_live', 'commerce_provider_ops_secret_hub_desc', 'password_keep', ''),
                $field('alfa_live_token', 'alfa_token_live', 'commerce_provider_ops_secret_hub_desc', 'password_keep', ''),
                $field('alfa_live_widget_token', 'alfa_widget_token_live', 'alfa_widget_token_desc', 'text', ''),
                $field('alfa_live_refund_username', 'alfa_refund_username_live', 'alfa_refund_credentials_desc', 'text', ''),
                $field('alfa_live_refund_password', 'alfa_refund_password_live', 'alfa_refund_credentials_desc', 'password_keep', ''),
                $field('paypal_sandbox_client_id', 'paypal_client_id_sandbox', 'commerce_provider_ops_secret_hub_desc', 'text', ''),
                $field('paypal_sandbox_client_secret', 'paypal_client_secret_sandbox', 'commerce_provider_ops_secret_hub_desc', 'password_keep', ''),
                $field('paypal_sandbox_webhook_id', 'paypal_webhook_id_sandbox', 'paypal_webhook_id_desc', 'text', ''),
                $field('paypal_live_client_id', 'paypal_client_id_live', 'commerce_provider_ops_secret_hub_desc', 'text', ''),
                $field('paypal_live_client_secret', 'paypal_client_secret_live', 'commerce_provider_ops_secret_hub_desc', 'password_keep', ''),
                $field('paypal_live_webhook_id', 'paypal_webhook_id_live', 'paypal_webhook_id_desc', 'text', ''),
            ],
            'commerce_configuration_group_payment_presentation' => [
                $field(
                    'commerce_presented_payment_providers',
                    'commerce_payment_presentation_providers',
                    'commerce_payment_presentation_providers_desc',
                    'multicheck_csv_allowempty',
                    implode(',', array_keys($paymentprovideroptions)),
                    $paymentprovideroptions
                ),
                $field(
                    'commerce_presented_payment_methods',
                    'commerce_payment_presentation_methods',
                    'commerce_payment_presentation_methods_desc',
                    'multicheck_csv_allowempty',
                    implode(',', CommercePaymentMethodCatalogue::keys()),
                    $paymentmethodoptions
                ),
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
                $field('paypal_reconciliation_cron_enabled', 'paypal_reconciliation_cron_enabled', 'paypal_reconciliation_cron_enabled_desc', 'checkbox', 0),
                $field('paypal_reconciliation_batch_size', 'paypal_reconciliation_batch_size', 'paypal_reconciliation_batch_size_desc', 'number', 20, [], PARAM_INT),
                $field('paypal_reconciliation_min_age', 'paypal_reconciliation_min_age', 'paypal_reconciliation_min_age_desc', 'number', 300, [], PARAM_INT),
                $field('paypal_reconciliation_max_age', 'paypal_reconciliation_max_age', 'paypal_reconciliation_max_age_desc', 'number', 172800, [], PARAM_INT),
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
                $field('commerce_enabled_currencies', 'commerce_configuration_enabled_currencies_label', 'commerce_configuration_enabled_currencies_desc', 'multicheck_csv', 'EUR,RUB', (new CommerceCurrencyRegistry())->known_options()),
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
            'commerce_configuration_group_legal_entity_fr' => [
                $field('legal_entity_fr_name', 'commerce_i411_invoice_name', 'commerce_configuration_invoice_name_desc', 'text'),
                $field('legal_entity_fr_address', 'commerce_i411_invoice_address', 'commerce_configuration_invoice_address_desc', 'textarea'),
                $field('legal_entity_fr_legal', 'commerce_i411_invoice_legal', 'commerce_configuration_invoice_legal_desc', 'textarea'),
                $field('legal_entity_fr_registration', 'commerce_legal_entity_registration', 'commerce_legal_entity_registration_desc', 'text'),
                $field('legal_entity_fr_tax_identifier', 'commerce_legal_entity_tax_identifier', 'commerce_legal_entity_tax_identifier_desc', 'text'),
                $field('legal_entity_fr_email', 'commerce_i411_invoice_email', 'commerce_configuration_invoice_email_desc', 'email', '', [], PARAM_EMAIL),
                $field('legal_entity_fr_phone', 'commerce_i411_invoice_phone', 'commerce_configuration_invoice_phone_desc', 'text'),
                $field('legal_entity_fr_website', 'commerce_i411_invoice_website', 'commerce_configuration_invoice_website_desc', 'text'),
                $field('legal_entity_fr_tax_notice', 'commerce_i411_invoice_tax_notice', 'commerce_configuration_invoice_tax_notice_desc', 'textarea'),
                $field('legal_entity_fr_footer', 'commerce_i411_invoice_footer', 'commerce_configuration_invoice_footer_desc', 'textarea'),
            ],
            'commerce_configuration_group_legal_entity_ru' => [
                $field('legal_entity_ru_name', 'commerce_i411_invoice_name', 'commerce_configuration_invoice_name_desc', 'text'),
                $field('legal_entity_ru_address', 'commerce_i411_invoice_address', 'commerce_configuration_invoice_address_desc', 'textarea'),
                $field('legal_entity_ru_legal', 'commerce_i411_invoice_legal', 'commerce_configuration_invoice_legal_desc', 'textarea'),
                $field('legal_entity_ru_registration', 'commerce_legal_entity_registration', 'commerce_legal_entity_registration_desc', 'text'),
                $field('legal_entity_ru_tax_identifier', 'commerce_legal_entity_tax_identifier', 'commerce_legal_entity_tax_identifier_desc', 'text'),
                $field('legal_entity_ru_email', 'commerce_i411_invoice_email', 'commerce_configuration_invoice_email_desc', 'email', '', [], PARAM_EMAIL),
                $field('legal_entity_ru_phone', 'commerce_i411_invoice_phone', 'commerce_configuration_invoice_phone_desc', 'text'),
                $field('legal_entity_ru_website', 'commerce_i411_invoice_website', 'commerce_configuration_invoice_website_desc', 'text'),
                $field('legal_entity_ru_tax_notice', 'commerce_i411_invoice_tax_notice', 'commerce_configuration_invoice_tax_notice_desc', 'textarea'),
                $field('legal_entity_ru_footer', 'commerce_i411_invoice_footer', 'commerce_configuration_invoice_footer_desc', 'textarea'),
            ],
            'commerce_configuration_group_legal_ru_by' => [
                $field('legal_documents_ru_version', 'commerce_legal_documents_version', 'commerce_legal_documents_version_desc', 'text', '2026-09-v1'),
                $field('policy_url_ru', 'commerce_configuration_policy_document_label', 'commerce_configuration_policy_url_ru_by_desc', 'text'),
                $field('terms_url_ru', 'commerce_configuration_terms_document_label', 'commerce_configuration_terms_url_ru_by_desc', 'text'),
                $field('offer_url_ru', 'commerce_configuration_offer_document_label', 'commerce_configuration_offer_url_ru_by_desc', 'text'),
            ],
            'commerce_configuration_group_legal_row' => [
                $field('legal_documents_row_version', 'commerce_legal_documents_version', 'commerce_legal_documents_version_desc', 'text', '2026-09-v1'),
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
        } else if (
            $definitionfield['type'] === 'multicheck_csv'
            || $definitionfield['type'] === 'multicheck_csv_allowempty'
        ) {
            $selected = optional_param_array(
                $key,
                [],
                PARAM_ALPHANUMEXT
            );
            $selected = array_values(
                array_intersect(
                    array_keys(
                        $definitionfield['options']
                    ),
                    $selected
                )
            );

            if (
                $definitionfield['type'] === 'multicheck_csv'
                && $selected === []
            ) {
                throw new moodle_exception(
                    'commerce_configuration_currency_required',
                    'local_subscriptions'
                );
            }

            $clean = implode(',', $selected);
        } else {
            $raw = optional_param($key, (string)$definitionfield['default'], PARAM_RAW);
            $clean = clean_param($raw, $definitionfield['param']);
            if ($definitionfield['type'] === 'select' && !array_key_exists((string)$clean, $definitionfield['options'])) {
                throw new moodle_exception('invalidparameter');
            }
        }
        if ($definitionfield['type'] === 'password_keep' && trim((string)$clean) === '') {
            continue;
        }
        if ($key === 'commerce_runtime_mode') {
            (new CommerceRuntimeConfiguration())->set_mode((string)$clean);
        } else {
            set_config($key, $clean, 'local_subscriptions');
        }
        // Legacy invoice_* profiles are intentionally read-only compatibility
        // fallbacks. Active legal-entity edits must not mutate them.
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
    if (!property_exists($config, $key) && preg_match('/^legal_entity_(fr|ru)_(.+)$/', $key, $matches)) {
        $legacyfield = $matches[2];
        if (in_array($legacyfield, ['name', 'address', 'legal', 'email', 'phone', 'website', 'tax_notice', 'footer'], true)) {
            $legacyprefix = $matches[1] === 'fr' ? 'invoice_eur_' : 'invoice_rub_';
            $legacykey = $legacyprefix . $legacyfield;
            if (property_exists($config, $legacykey)) {
                $current = (string)$config->{$legacykey};
            }
        }
    }
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
    } else if ($fielddef['type'] === 'password_keep') {
        $control = html_writer::empty_tag('input', $attrs + [
            'type' => 'password',
            'value' => '',
            'autocomplete' => 'new-password',
            'placeholder' => trim($current) !== '' ? '••••••••' : '',
        ]);
    } else if ($fielddef['type'] === 'checkbox') {
        $control = html_writer::checkbox($key, '1', !empty($current), '', ['id' => 'id_' . $key, 'class' => 'form-check-input']);
    } else if ($fielddef['type'] === 'select') {
        $control = html_writer::select($fielddef['options'], $key, $current, false, ['id' => 'id_' . $key, 'class' => 'form-select']);
    } else if ($fielddef['type'] === 'textarea') {
        $control = html_writer::tag('textarea', s($current), ['name' => $key, 'id' => 'id_' . $key, 'class' => 'form-control', 'rows' => 4]);
    } else if (
        $fielddef['type'] === 'multicheck_csv'
        || $fielddef['type'] === 'multicheck_csv_allowempty'
    ) {
        $selected = array_filter(
            array_map(
                'trim',
                explode(',', $current)
            )
        );
        $items = [];
        foreach ($fielddef['options'] as $optionvalue => $optionlabel) {
            $checkboxid = 'id_' . $key . '_' . strtolower($optionvalue);
            $items[] = html_writer::label(
                html_writer::checkbox($key . '[]', $optionvalue, in_array($optionvalue, $selected, true), '', ['id' => $checkboxid, 'class' => 'form-check-input', 'data-currency-option' => $key === 'commerce_enabled_currencies' ? '1' : null])
                    . html_writer::span(
                        s($optionlabel),
                        'commerce-config-currency-label'
                    ),
                $checkboxid,
                false,
                [
                    'class' =>
                        'commerce-config-currency-option',
                ]
            );
        }
        $bulkcontrols = '';
        if ($key === 'commerce_enabled_currencies') {
            $bulkcontrols = html_writer::div(
                html_writer::tag('button', get_string('commerce_select_all', 'local_subscriptions'), ['type'=>'button','class'=>'btn btn-link btn-sm p-0 me-3','data-currency-select-all'=>'1'])
                . html_writer::tag('button', get_string('commerce_deselect_all', 'local_subscriptions'), ['type'=>'button','class'=>'btn btn-link btn-sm p-0','data-currency-deselect-all'=>'1']),
                'mb-2'
            );
        }
        $control = $bulkcontrols . html_writer::div(implode('', $items), 'commerce-config-currency-grid');
    } else {
        $attrs['type'] = $fielddef['type'] === 'email' ? 'email' : ($fielddef['type'] === 'url' ? 'url' : 'text');
        $control = html_writer::empty_tag('input', $attrs + ['value' => $current]);
    }
    $technical = html_writer::tag('code', 'local_subscriptions | ' . s($key), ['class' => 'commerce-config-technical-name']);
    $providerprefix = '';
    if (empty($fielddef['hideprovidericon'])) {
        if (
            str_starts_with($key, 'stripe_')
        ) {
            $providerprefix = html_writer::empty_tag(
                'img',
                [
                    'src' => (
                        new moodle_url(
                            '/local/subscriptions/pix/providers/stripe.svg'
                        )
                    )->out(false),
                    'alt' => '',
                    'class' => 'commerce-config-provider-icon',
                ]
            ) . ' ';
        } else if (
            str_starts_with($key, 'alfa_')
        ) {
            $providerprefix = html_writer::empty_tag(
                'img',
                [
                    'src' => (
                        new moodle_url(
                            '/local/subscriptions/pix/providers/alfa.svg'
                        )
                    )->out(false),
                    'alt' => '',
                    'class' => 'commerce-config-provider-icon',
                ]
            ) . ' ';
        } else if (str_starts_with($key, 'paypal_')) {
            $providerprefix = html_writer::empty_tag(
                'img',
                [
                    'src' => (
                        new moodle_url(
                            '/local/subscriptions/pix/providers/paypal.svg'
                        )
                    )->out(false),
                    'alt' => '',
                    'class' => 'commerce-config-provider-icon',
                ]
            ) . ' ';
        }
    }

    return html_writer::div(
        html_writer::tag(
            'label',
            $providerprefix . s($string($fielddef['label'])),
            [
                'for' => 'id_' . $key,
                'class' => 'form-label fw-semibold mb-1',
            ]
        ) .
        html_writer::div($technical, 'mb-2') . $control .
        ($interpreted !== '' ? html_writer::div(s($interpreted), 'commerce-config-interpreted-value mt-2') : '') .
        html_writer::tag('div', s($string($fielddef['description'])), ['class' => 'form-text mt-2']),
        'commerce-config-edit-field'
    );
};

$credentialvalue = static function(
    object $config,
    string $key
): string {
    return property_exists($config, $key)
        ? trim((string)$config->{$key})
        : '';
};

$credentialenvironmentready = static function(
    string $provider,
    string $environment
) use ($config, $credentialvalue): bool {
    if ($provider === 'stripe') {
        $prefix = match ($environment) {
            'test' => 'stripe_test_',
            'live_ei' => 'stripe_live_',
            'live_sas' => 'stripe_live_sas_',
            default => '',
        };

        return $prefix !== ''
            && $credentialvalue(
                $config,
                $prefix . 'publishable'
            ) !== ''
            && $credentialvalue(
                $config,
                $prefix . 'secret'
            ) !== ''
            && $credentialvalue(
                $config,
                $prefix . 'webhook_secret'
            ) !== '';
    }

    if ($provider === 'alfa') {
        $prefix = $environment === 'live'
            ? 'alfa_live_'
            : 'alfa_test_';

        $haspaymentcredentials =
            $credentialvalue(
                $config,
                $prefix . 'token'
            ) !== ''
            || (
                $credentialvalue(
                    $config,
                    $prefix . 'username'
                ) !== ''
                && $credentialvalue(
                    $config,
                    $prefix . 'password'
                ) !== ''
            );

        return $credentialvalue(
            $config,
            $prefix . 'api_base'
        ) !== ''
            && $haspaymentcredentials;
    }

    if ($provider === 'paypal') {
        $prefix = $environment === 'live'
            ? 'paypal_live_'
            : 'paypal_sandbox_';

        return $credentialvalue(
            $config,
            $prefix . 'client_id'
        ) !== ''
            && $credentialvalue(
                $config,
                $prefix . 'client_secret'
            ) !== ''
            && $credentialvalue(
                $config,
                $prefix . 'webhook_id'
            ) !== '';
    }

    return false;
};

$rendercredentialsgroup = static function(
    array $fields
) use (
    $renderfield,
    $credentialenvironmentready
): string {
    $fieldmap = [];
    foreach ($fields as $fielddef) {
        $fieldmap[$fielddef['key']] = $fielddef;
    }

    $providers = [
        'stripe' => [
            'label' => get_string(
                'provider_stripe',
                'local_subscriptions'
            ),
            'icon' => 'stripe.svg',
            'environments' => [
                'test' => [
                    'label' => get_string(
                        'stripe_profile_test',
                        'local_subscriptions'
                    ),
                    'keys' => [
                        'stripe_test_publishable',
                        'stripe_test_secret',
                        'stripe_test_webhook_secret',
                    ],
                ],
                'live_ei' => [
                    'label' => get_string(
                        'stripe_profile_live_ei',
                        'local_subscriptions'
                    ),
                    'keys' => [
                        'stripe_live_publishable',
                        'stripe_live_secret',
                        'stripe_live_webhook_secret',
                    ],
                ],
                'live_sas' => [
                    'label' => get_string(
                        'stripe_profile_live_sas',
                        'local_subscriptions'
                    ),
                    'keys' => [
                        'stripe_live_sas_publishable',
                        'stripe_live_sas_secret',
                        'stripe_live_sas_webhook_secret',
                    ],
                ],
            ],
        ],
        'alfa' => [
            'label' => get_string(
                'provider_alfa',
                'local_subscriptions'
            ),
            'icon' => 'alfa.svg',
            'environments' => [
                'test' => [
                    'label' => get_string(
                        'env_test',
                        'local_subscriptions'
                    ),
                    'keys' => [
                        'alfa_test_api_base',
                        'alfa_test_username',
                        'alfa_test_password',
                        'alfa_test_token',
                        'alfa_test_widget_token',
                        'alfa_test_refund_username',
                        'alfa_test_refund_password',
                    ],
                ],
                'live' => [
                    'label' => get_string(
                        'env_live',
                        'local_subscriptions'
                    ),
                    'keys' => [
                        'alfa_live_api_base',
                        'alfa_live_username',
                        'alfa_live_password',
                        'alfa_live_token',
                        'alfa_live_widget_token',
                        'alfa_live_refund_username',
                        'alfa_live_refund_password',
                    ],
                ],
            ],
        ],
        'paypal' => [
            'label' => get_string(
                'provider_paypal',
                'local_subscriptions'
            ),
            'icon' => 'paypal.svg',
            'environments' => [
                'sandbox' => [
                    'label' => get_string(
                        'paypal_env_sandbox',
                        'local_subscriptions'
                    ),
                    'keys' => [
                        'paypal_sandbox_client_id',
                        'paypal_sandbox_client_secret',
                        'paypal_sandbox_webhook_id',
                    ],
                ],
                'live' => [
                    'label' => get_string(
                        'paypal_env_live',
                        'local_subscriptions'
                    ),
                    'keys' => [
                        'paypal_live_client_id',
                        'paypal_live_client_secret',
                        'paypal_live_webhook_id',
                    ],
                ],
            ],
        ],
    ];

    $html = html_writer::tag(
        'p',
        get_string(
            'commerce_payment_credentials_accordion_help',
            'local_subscriptions'
        ),
        ['class' => 'text-muted mb-3']
    );

    $html .= html_writer::start_div(
        'commerce-payment-credentials-providers'
    );

    foreach ($providers as $providerkey => $provider) {
        $readycount = 0;
        foreach (
            array_keys($provider['environments'])
            as $environmentkey
        ) {
            if (
                $credentialenvironmentready(
                    $providerkey,
                    $environmentkey
                )
            ) {
                $readycount++;
            }
        }

        $providerbadge = html_writer::span(
            get_string(
                'commerce_payment_credentials_ready_count',
                'local_subscriptions',
                (object)[
                    'ready' => $readycount,
                    'total' => count(
                        $provider['environments']
                    ),
                ]
            ),
            $readycount === count($provider['environments'])
                ? 'badge text-bg-success'
                : (
                    $readycount > 0
                        ? 'badge text-bg-warning'
                        : 'badge text-bg-secondary'
                )
        );

        $summary =
            html_writer::empty_tag(
                'img',
                [
                    'src' => (
                        new moodle_url(
                            '/local/subscriptions/pix/providers/'
                            . $provider['icon']
                        )
                    )->out(false),
                    'alt' => '',
                    'class' =>
                        'commerce-payment-credentials-provider-logo',
                ]
            )
            . html_writer::span(
                s($provider['label']),
                'commerce-payment-credentials-provider-name'
            )
            . $providerbadge;

        $html .= html_writer::start_tag(
            'details',
            [
                'class' =>
                    'commerce-payment-credentials-provider',
            ]
        );
        $html .= html_writer::tag(
            'summary',
            $summary,
            [
                'class' =>
                    'commerce-payment-credentials-provider-summary',
            ]
        );

        $html .= html_writer::start_div(
            'commerce-payment-credentials-environments'
        );

        foreach (
            $provider['environments']
            as $environmentkey => $environment
        ) {
            $ready =
                $credentialenvironmentready(
                    $providerkey,
                    $environmentkey
                );

            $envsummary =
                html_writer::span(
                    s($environment['label']),
                    'fw-semibold'
                )
                . html_writer::span(
                    get_string(
                        $ready
                            ? 'commerce_provider_ops_configured'
                            : 'commerce_provider_ops_missing',
                        'local_subscriptions'
                    ),
                    $ready
                        ? 'badge text-bg-success'
                        : 'badge text-bg-secondary'
                );

            $html .= html_writer::start_tag(
                'details',
                [
                    'class' =>
                        'commerce-payment-credentials-environment',
                ]
            );
            $html .= html_writer::tag(
                'summary',
                $envsummary,
                [
                    'class' =>
                        'commerce-payment-credentials-environment-summary',
                ]
            );

            $fieldhtml = '';
            foreach ($environment['keys'] as $key) {
                if (!isset($fieldmap[$key])) {
                    continue;
                }

                $fielddef = $fieldmap[$key];
                $fielddef['hideprovidericon'] = true;

                $fieldhtml .= html_writer::div(
                    $renderfield($fielddef),
                    'col-12 col-xl-6'
                );
            }

            $html .= html_writer::div(
                $fieldhtml,
                'row g-3 commerce-payment-credentials-fields'
            );
            $html .= html_writer::end_tag('details');
        }

        $html .= html_writer::end_div();
        $html .= html_writer::end_tag('details');
    }

    $html .= html_writer::end_div();

    return $html;
};


$renderreconciliationgroup = static function(
    array $fields
) use (
    $renderfield,
    $config
): string {
    $fieldmap = [];
    foreach ($fields as $fielddef) {
        $fieldmap[$fielddef['key']] = $fielddef;
    }

    $providers = [
        'stripe' => [
            'label' => get_string(
                'provider_stripe',
                'local_subscriptions'
            ),
            'icon' => 'stripe.svg',
            'enabledkey' =>
                'stripe_reconciliation_cron_enabled',
            'keys' => [
                'stripe_reconciliation_cron_enabled',
                'stripe_reconciliation_batch_size',
                'stripe_reconciliation_min_age',
                'stripe_reconciliation_max_age',
            ],
        ],
        'alfa' => [
            'label' => get_string(
                'provider_alfa',
                'local_subscriptions'
            ),
            'icon' => 'alfa.svg',
            'enabledkey' =>
                'alfa_reconciliation_cron_enabled',
            'keys' => [
                'alfa_reconciliation_cron_enabled',
                'alfa_reconciliation_batch_size',
                'alfa_reconciliation_min_age',
                'alfa_reconciliation_max_age',
            ],
        ],
        'paypal' => [
            'label' => get_string(
                'provider_paypal',
                'local_subscriptions'
            ),
            'icon' => 'paypal.svg',
            'enabledkey' =>
                'paypal_reconciliation_cron_enabled',
            'keys' => [
                'paypal_reconciliation_cron_enabled',
                'paypal_reconciliation_batch_size',
                'paypal_reconciliation_min_age',
                'paypal_reconciliation_max_age',
            ],
        ],
    ];

    $html = html_writer::tag(
        'p',
        get_string(
            'commerce_payment_reconciliation_accordion_help',
            'local_subscriptions'
        ),
        ['class' => 'text-muted mb-3']
    );

    $html .= html_writer::start_div(
        'commerce-payment-reconciliation-providers'
    );

    foreach ($providers as $provider) {
        $enabledkey = $provider['enabledkey'];
        $enabled = property_exists(
            $config,
            $enabledkey
        )
            ? !empty(
                $config->{$enabledkey}
            )
            : false;

        $summary =
            html_writer::empty_tag(
                'img',
                [
                    'src' => (
                        new moodle_url(
                            '/local/subscriptions/pix/providers/'
                            . $provider['icon']
                        )
                    )->out(false),
                    'alt' => '',
                    'class' =>
                        'commerce-payment-reconciliation-provider-logo',
                ]
            )
            . html_writer::span(
                s($provider['label']),
                'commerce-payment-reconciliation-provider-name'
            )
            . html_writer::span(
                get_string(
                    $enabled
                        ? 'commerce_payment_reconciliation_enabled'
                        : 'commerce_payment_reconciliation_disabled',
                    'local_subscriptions'
                ),
                $enabled
                    ? 'badge text-bg-success'
                    : 'badge text-bg-secondary'
            );

        $html .= html_writer::start_tag(
            'details',
            [
                'class' =>
                    'commerce-payment-reconciliation-provider',
            ]
        );
        $html .= html_writer::tag(
            'summary',
            $summary,
            [
                'class' =>
                    'commerce-payment-reconciliation-provider-summary',
            ]
        );

        $fieldhtml = '';
        foreach ($provider['keys'] as $key) {
            if (!isset($fieldmap[$key])) {
                continue;
            }

            $fielddef = $fieldmap[$key];
            $fielddef['hideprovidericon'] = true;

            $fieldhtml .= html_writer::div(
                $renderfield($fielddef),
                'col-12 col-xl-6'
            );
        }

        $html .= html_writer::div(
            $fieldhtml,
            'row g-3 commerce-payment-reconciliation-fields'
        );

        $html .= html_writer::end_tag(
            'details'
        );
    }

    $html .= html_writer::end_div();

    return $html;
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

if ($section === 'payments') {
    echo html_writer::start_div('card mb-4 border-primary');
    echo html_writer::start_div(
        'card-body d-flex flex-wrap justify-content-between align-items-center gap-3'
    );
    echo html_writer::div(
        html_writer::tag(
            'h2',
            '🧭 ' . get_string(
                'commerce_payment_architecture_title',
                'local_subscriptions'
            ),
            ['class' => 'h5 mb-1']
        )
        . html_writer::tag(
            'p',
            get_string(
                'commerce_payment_architecture_card_help',
                'local_subscriptions'
            ),
            ['class' => 'text-muted mb-0']
        )
    );
    echo html_writer::link(
        new moodle_url(
            '/local/subscriptions/admin/commerce/configuration/payment_architecture.php'
        ),
        get_string(
            'commerce_payment_architecture_open',
            'local_subscriptions'
        ),
        ['class' => 'btn btn-primary']
    );
    echo html_writer::end_div();
    echo html_writer::end_div();
}

if ($section === 'checkout') {
    echo html_writer::div(get_string('commerce_configuration_checkout_mail_policy_note', 'local_subscriptions'), 'commerce-config-subtle-note mb-4');
} else if ($section === 'legal') {
    echo html_writer::div(get_string('commerce_configuration_legal_resolution_note', 'local_subscriptions'), 'commerce-config-subtle-note mb-4');
}
if ($section === 'localisation') {
    $fxbook = new \local_subscriptions\commerce\currency\CommerceFxRateBook();
    $fxrates = $fxbook->rates();
    $latestfx = 0;
    foreach ($fxrates as $fxentry) {
        $latestfx = max($latestfx, (int)($fxentry['updatedat'] ?? 0));
    }
    $fxsummary = get_string('commerce_fx_configuration_card_summary', 'local_subscriptions', (object)[
        'base' => $fxbook->base_currency(),
        'count' => count($fxrates),
        'updated' => $latestfx > 0 ? userdate($latestfx) : get_string('commerce_fx_never_updated', 'local_subscriptions'),
    ]);
    echo html_writer::start_div('card mb-4 border-primary');
    echo html_writer::start_div('card-body d-flex flex-wrap justify-content-between align-items-center gap-3');
    echo html_writer::div(
        html_writer::tag('h2', '💱 ' . get_string('commerce_fx_title', 'local_subscriptions'), ['class' => 'h5 mb-1'])
        . html_writer::tag('p', get_string('commerce_fx_configuration_note', 'local_subscriptions'), ['class' => 'text-muted mb-1'])
        . html_writer::tag('div', $fxsummary, ['class' => 'small text-muted'])
    );
    echo html_writer::link(
        new moodle_url('/local/subscriptions/admin/commerce/configuration/currencies.php'),
        get_string('commerce_fx_open_tool', 'local_subscriptions'),
        ['class' => 'btn btn-primary']
    );
    echo html_writer::end_div();
    echo html_writer::end_div();
}

if ($section === 'payments') {
    $providerstatusservice =
        new CommercePaymentProviderOperationalStatusService();

    $providercards = '';
    foreach (['stripe', 'alfa', 'paypal'] as $providerkey) {
        $providercards .= html_writer::div(
            CommercePaymentProviderOperationalRenderer::overview_card(
                $providerstatusservice->get(
                    $providerkey
                ),
                new moodle_url(
                    '/local/subscriptions/admin/commerce/configuration/provider.php',
                    ['provider' => $providerkey]
                )
            ),
            'col-12 col-xl-4'
        );
    }

    echo html_writer::start_div(
        'card mb-4 commerce-config-provider-hub'
    );
    echo html_writer::start_div('card-body');
    echo html_writer::tag(
        'h2',
        get_string(
            'commerce_provider_ops_hub_title',
            'local_subscriptions'
        ),
        ['class' => 'h5 mb-2']
    );
    echo html_writer::tag(
        'p',
        get_string(
            'commerce_provider_ops_hub_desc',
            'local_subscriptions'
        ),
        ['class' => 'text-muted mb-3']
    );
    echo html_writer::div(
        $providercards,
        'row g-3'
    );
    echo html_writer::end_div();
    echo html_writer::end_div();
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

if ($section === 'legal') {
    echo html_writer::div(
        html_writer::tag(
            'strong',
            get_string('commerce_configuration_legal_v1_scope_title', 'local_subscriptions')
        )
        . html_writer::tag(
            'p',
            get_string('commerce_configuration_legal_v1_scope_desc', 'local_subscriptions'),
            ['class' => 'mb-2']
        )
        . html_writer::tag(
            'p',
            get_string('commerce_configuration_legal_v1_snapshot_desc', 'local_subscriptions'),
            ['class' => 'mb-3 text-muted']
        )
        . html_writer::tag(
            'strong',
            get_string('commerce_configuration_legal_legacy_title', 'local_subscriptions')
        )
        . html_writer::tag(
            'p',
            get_string('commerce_configuration_legal_legacy_desc', 'local_subscriptions'),
            ['class' => 'mb-0 text-muted']
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
        'commerce_configuration_group_payment_credentials' => '🔑',
        'commerce_configuration_group_payment_presentation' => '👁',
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
    echo html_writer::tag(
        'h2',
        html_writer::span(
            $groupicon,
            'commerce-config-group-icon'
        )
        . ' '
        . get_string(
            $groupkey,
            'local_subscriptions'
        ),
        [
            'class' =>
                'h5 mb-3 commerce-config-group-title',
        ]
    );

    if (
        $groupkey
        === 'commerce_configuration_group_payment_credentials'
    ) {
        echo $rendercredentialsgroup($fields);
    } else if (
        $groupkey
        === 'commerce_configuration_group_reconciliation'
    ) {
        echo $renderreconciliationgroup($fields);
    } else {
        echo html_writer::start_div('row g-4');
        foreach ($fields as $fielddef) {
            echo html_writer::div(
                $renderfield($fielddef),
                'col-12 col-xl-6'
            );
        }
        echo html_writer::end_div();
    }

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

$PAGE->requires->js_init_code(<<<JS
(function() {
    var currencyBoxes = Array.prototype.slice.call(document.querySelectorAll('[data-currency-option="1"]'));
    var selectAll = document.querySelector('[data-currency-select-all="1"]');
    var deselectAll = document.querySelector('[data-currency-deselect-all="1"]');
    if (selectAll) selectAll.addEventListener('click', function() { currencyBoxes.forEach(function(box) { box.checked = true; }); });
    if (deselectAll) deselectAll.addEventListener('click', function() { currencyBoxes.forEach(function(box) { box.checked = false; }); });
})();
JS);

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
