<?php
defined('MOODLE_INTERNAL') || die();

use local_subscriptions\admin\Capabilities;
use local_subscriptions\subscription_config;

if ($hassiteconfig || has_capability('local/subscriptions:view_dashboard', context_system::instance())) {
    $ADMIN->add('localplugins', new admin_externalpage(
        'local_subscriptions_backoffice',
        get_string('admin_dashboard', 'local_subscriptions'),
        new moodle_url(subscription_config::admin_dashboard_page()),
        'local/subscriptions:view_dashboard'
    ));
}

if (
    $hassiteconfig ||
    has_capability(
        Capabilities::VIEW_INBOX,
        context_system::instance()
    )
) {
    $ADMIN->add('localplugins', new admin_externalpage(
        'local_subscriptions_crm_inbox',
        get_string('crm_inbox_navigation', 'local_subscriptions'),
        new moodle_url(
            subscription_config::admin_inbox_page()
        ),
        Capabilities::VIEW_INBOX
    ));
}

if (
    $hassiteconfig ||
    has_capability(
        Capabilities::VIEW_USERS,
        context_system::instance()
    )
) {
    $ADMIN->add(
        'localplugins',
        new admin_externalpage(
            'local_subscriptions_crm_assistant',
            get_string(
                'crm_assistant_navigation',
                'local_subscriptions'
            ),
            new moodle_url(
                subscription_config::
                    admin_crm_assistant_page()
            ),
            Capabilities::VIEW_USERS
        )
    );
}

if ($hassiteconfig || has_capability('local/subscriptions:manage_showrooms', context_system::instance())) {
    $ADMIN->add('localplugins', new admin_externalpage(
        'local_subscriptions_showrooms',
        get_string('commerce_showroom_cms_title', 'local_subscriptions'),
        new moodle_url('/local/subscriptions/admin/commerce/showrooms/index.php'),
        'local/subscriptions:manage_showrooms'
    ));
}

if ($hassiteconfig) {

	// Crée une catégorie principale "Subscriptions"
	$ADMIN->add('localplugins', new admin_category(
		'subscriptions_category', 
		'🧾 ' . get_string('pluginname', 'local_subscriptions')));

	// Ajoute les pages dans la catégorie

    $ADMIN->add('subscriptions_category', new admin_externalpage(
        'local_subscriptions_manage_subscription',
        get_string('manage_subscriptions', 'local_subscriptions'),
        new moodle_url(subscription_config::manage_page()),
		'moodle/site:config'
    ));

    $ADMIN->add('subscriptions_category', new admin_externalpage(
        'local_subscriptions_plan_upgrades',
        get_string('planupgrades', 'local_subscriptions'),
        new moodle_url(subscription_config::plan_upgrades_page()),
        'moodle/site:config'
    )); 

	$ADMIN->add('subscriptions_category', new admin_externalpage(
		'local_subscriptions_add_subscription',
		get_string('add_subscription', 'local_subscriptions'),
		new moodle_url(subscription_config::add_manual_subscription_page()),
		'moodle/site:config'
	));
	
	$ADMIN->add('subscriptions_category', new admin_externalpage(
		'local_subscriptions_import_csv',
		get_string('import_subscriptions_csv', 'local_subscriptions'),
		new moodle_url(subscription_config::import_csv_page()),
		'moodle/site:config'
	));

    $ADMIN->add('localplugins', new admin_externalpage(
        'local_subscriptions_export',
        'Export des souscriptions',
        new moodle_url(subscription_config::subscriptions_export_page()),
        'moodle/site:config'
    ));

    $ADMIN->add('subscriptions_category', new admin_externalpage(
        'local_subscriptions_digital_purchases',
        get_string('digital_purchases_title', 'local_subscriptions'),
        new moodle_url('/local/subscriptions/admin/digital_purchases.php'),
        'moodle/site:config'
    ));
        
    // Le nœud "Local plugins" existe déjà. On ajoute une page réglages pour ton plugin.
    $settings = new admin_settingpage('local_subscriptions_settings', get_string('pluginname', 'local_subscriptions'));

    if ($ADMIN->fulltree) {

        $settings->add(
            new admin_setting_heading(
                'local_subscriptions_storefront_header',
                get_string('settings:storefront_header', 'local_subscriptions'),
                get_string('settings:storefront_header_desc', 'local_subscriptions')
            )
        );

        $settings->add(
            new admin_setting_configcheckbox(
                'local_subscriptions/storefront_enabled',
                get_string('settings:storefront_enabled', 'local_subscriptions'),
                get_string('settings:storefront_enabled_desc', 'local_subscriptions'),
                0
            )
        );


        $settings->add(
            new admin_setting_heading(
                'local_subscriptions_commerce_security_header',
                get_string('settings:commerce_security_header', 'local_subscriptions'),
                get_string('settings:commerce_security_header_desc', 'local_subscriptions')
            )
        );

        $settings->add(
            new admin_setting_configcheckbox(
                'local_subscriptions/commerce_allow_destructive_product_delete',
                get_string('settings:commerce_allow_destructive_product_delete', 'local_subscriptions'),
                get_string('settings:commerce_allow_destructive_product_delete_desc', 'local_subscriptions'),
                0
            )
        );

        $settings->add(
            new admin_setting_heading(
                'local_subscriptions_inbox_ai_header',
                get_string(
                    'settings:inbox_ai_header',
                    'local_subscriptions'
                ),
                get_string(
                    'settings:inbox_ai_header_desc',
                    'local_subscriptions'
                )
            )
        );

        $settings->add(
            new admin_setting_configcheckbox(
                'local_subscriptions/inbox_ai_openai_enabled',
                get_string(
                    'settings:inbox_ai_openai_enabled',
                    'local_subscriptions'
                ),
                get_string(
                    'settings:inbox_ai_openai_enabled_desc',
                    'local_subscriptions'
                ),
                0
            )
        );

        $settings->add(
            new admin_setting_configtext(
                'local_subscriptions/inbox_ai_openai_model',
                get_string(
                    'settings:inbox_ai_openai_model',
                    'local_subscriptions'
                ),
                get_string(
                    'settings:inbox_ai_openai_model_desc',
                    'local_subscriptions'
                ),
                'gpt-5.6-luna',
                PARAM_RAW_TRIMMED
            )
        );

        $settings->add(
            new admin_setting_configtext(
                'local_subscriptions/inbox_ai_openai_endpoint',
                get_string(
                    'settings:inbox_ai_openai_endpoint',
                    'local_subscriptions'
                ),
                get_string(
                    'settings:inbox_ai_openai_endpoint_desc',
                    'local_subscriptions'
                ),
                'https://api.openai.com/v1/responses',
                PARAM_URL
            )
        );

        $settings->add(
            new admin_setting_configtext(
                'local_subscriptions/inbox_ai_openai_timeout',
                get_string(
                    'settings:inbox_ai_openai_timeout',
                    'local_subscriptions'
                ),
                '',
                45,
                PARAM_INT
            )
        );

        $settings->add(
            new admin_setting_configtext(
                'local_subscriptions/inbox_ai_openai_max_output_tokens',
                get_string(
                    'settings:inbox_ai_openai_max_output_tokens',
                    'local_subscriptions'
                ),
                '',
                1500,
                PARAM_INT
            )
        );

        $settings->add(
            new admin_setting_configcheckbox(
                'local_subscriptions/inbox_ai_openai_store',
                get_string(
                    'settings:inbox_ai_openai_store',
                    'local_subscriptions'
                ),
                get_string(
                    'settings:inbox_ai_openai_store_desc',
                    'local_subscriptions'
                ),
                0
            )
        );


        $settings->add(
            new admin_setting_configcheckbox(
                'local_subscriptions/storefront_ai_translation_enabled',
                get_string(
                    'settings:storefront_ai_translation_enabled',
                    'local_subscriptions'
                ),
                get_string(
                    'settings:storefront_ai_translation_enabled_desc',
                    'local_subscriptions'
                ),
                0
            )
        );

        $settings->add(
            new admin_setting_configcheckbox(
                'local_subscriptions/inbox_ai_include_crm_context',
                get_string(
                    'settings:inbox_ai_include_crm_context',
                    'local_subscriptions'
                ),
                '',
                1
            )
        );

        $settings->add(
            new admin_setting_configcheckbox(
                'local_subscriptions/inbox_ai_include_contact_email',
                get_string(
                    'settings:inbox_ai_include_contact_email',
                    'local_subscriptions'
                ),
                get_string(
                    'settings:inbox_ai_include_contact_email_desc',
                    'local_subscriptions'
                ),
                0
            )
        );

        $settings->add(
            new admin_setting_configtext(
                'local_subscriptions/inbox_ai_global_daily_limit',
                get_string(
                    'settings:inbox_ai_global_daily_limit',
                    'local_subscriptions'
                ),
                '',
                500,
                PARAM_INT
            )
        );

        $settings->add(
            new admin_setting_configtext(
                'local_subscriptions/inbox_ai_user_daily_limit',
                get_string(
                    'settings:inbox_ai_user_daily_limit',
                    'local_subscriptions'
                ),
                '',
                100,
                PARAM_INT
            )
        );

        $settings->add(
            new admin_setting_configcheckbox(
                'local_subscriptions/inbox_ai_automatic_analysis',
                get_string(
                    'settings:inbox_ai_automatic_analysis',
                    'local_subscriptions'
                ),
                get_string(
                    'settings:inbox_ai_automatic_analysis_desc',
                    'local_subscriptions'
                ),
                0
            )
        );

        $settings->add(new admin_setting_configselect(
            'local_subscriptions/availability_mode',
            get_string('availability_mode', 'local_subscriptions'),
            get_string('availability_mode_desc', 'local_subscriptions'),
            'enabled',
            [
                'enabled'   => get_string('availability_enabled', 'local_subscriptions'),
                'adminonly' => get_string('availability_adminonly', 'local_subscriptions'),
                'disabled'  => get_string('availability_disabled', 'local_subscriptions'),
            ]
        ));    
        
        // Liste des langues installées.
        $langs = get_string_manager()->get_list_of_translations(); // ['en'=>'English (en)', 'fr'=>'Français (fr)', ...]
        $options = ['' => get_string('settings:sitedefault', 'local_subscriptions')] + $langs;

        // 1) Langue par défaut pour les nouveaux comptes.
        $settings->add(new admin_setting_configselect(
            'local_subscriptions/defaultuserlang',
            get_string('settings:defaultuserlang', 'local_subscriptions'),
            get_string('settings:defaultuserlang_desc', 'local_subscriptions'),
            '', // vide => hérite de la langue du site
            $options
        ));

        // 2) Langue utilisée pour les e-mails du plugin.
        $settings->add(new admin_setting_configselect(
            'local_subscriptions/defaultemaillang',
            get_string('settings:defaultemaillang', 'local_subscriptions'),
            get_string('settings:defaultemaillang_desc', 'local_subscriptions'),
            '', // vide => langue de l’utilisateur ou langue du site
            $options
        ));

        $settings->add(new admin_setting_configcheckbox(
            'local_subscriptions/email_copy_verbose',
            get_string('email_copy_verbose', 'local_subscriptions'),
            get_string('email_copy_verbose_desc', 'local_subscriptions'),
            1
        ));

        $settings->add(new admin_setting_configcheckbox(
            'local_subscriptions/display_currency_symbols',
            get_string('set_display_currency_symbols', 'local_subscriptions'),
            get_string('set_display_currency_symbols_desc', 'local_subscriptions'),
            1
        ));

        // === Providers (global) ====================================================
        $settings->add(new admin_setting_heading(
            'local_subscriptions_providers_hdr',
            get_string('providers_header', 'local_subscriptions'),
            ''
        ));

        // Provider par défaut (quand rien ne force Stripe/Alfa)
        $settings->add(new admin_setting_configselect(
            'local_subscriptions/provider_default',
            get_string('provider_default', 'local_subscriptions'),
            get_string('provider_default_desc', 'local_subscriptions'),
            'stripe',
            [
                'stripe' => get_string('provider_stripe', 'local_subscriptions'),
                'alfa'   => get_string('provider_alfa',   'local_subscriptions'),
            ]
        ));

        // === Stripe ================================================================
        $settings->add(new admin_setting_heading(
            'local_subscriptions_stripe_hdr',
            get_string('provider_stripe', 'local_subscriptions'),
            ''
        ));

        // Profil Stripe actif: Test / Live EI / Live SAS
        $settings->add(new admin_setting_configselect(
            'local_subscriptions/stripe_env',
            get_string('env_mode', 'local_subscriptions'),
            get_string('env_mode_desc', 'local_subscriptions'),
            'test',
            [
                'test' => get_string('stripe_profile_test', 'local_subscriptions'),
                'live_ei' => get_string('stripe_profile_live_ei', 'local_subscriptions'),
                'live_sas' => get_string('stripe_profile_live_sas', 'local_subscriptions'),
            ]
        ));

        // TEST keys
        $settings->add(new admin_setting_configpasswordunmask(
            'local_subscriptions/stripe_test_secret',
            get_string('stripe_secret_test', 'local_subscriptions'),
            '', ''
        ));
        $settings->add(new admin_setting_configtext(
            'local_subscriptions/stripe_test_publishable',
            get_string('stripe_publishable_test', 'local_subscriptions'),
            '', '', PARAM_RAW_TRIMMED
        ));
        $settings->add(new admin_setting_configpasswordunmask(
            'local_subscriptions/stripe_test_webhook_secret',
            get_string('stripe_webhook_secret_test', 'local_subscriptions'),
            '', ''
        ));

        $settings->add(new admin_setting_configtext(
            'local_subscriptions/stripe_test_portal_configuration_id',
            get_string('stripe_portal_configuration_id_test', 'local_subscriptions'),
            get_string('stripe_portal_configuration_id_desc', 'local_subscriptions'),
            '', PARAM_RAW_TRIMMED
        ));

        // LIVE EI keys (historical stripe_live_* settings, preserved for compatibility)
        $settings->add(new admin_setting_configpasswordunmask(
            'local_subscriptions/stripe_live_secret',
            get_string('stripe_secret_live', 'local_subscriptions'),
            '', ''
        ));
        $settings->add(new admin_setting_configtext(
            'local_subscriptions/stripe_live_publishable',
            get_string('stripe_publishable_live', 'local_subscriptions'),
            '', '', PARAM_RAW_TRIMMED
        ));
        $settings->add(new admin_setting_configpasswordunmask(
            'local_subscriptions/stripe_live_webhook_secret',
            get_string('stripe_webhook_secret_live', 'local_subscriptions'),
            '', ''
        ));

        $settings->add(new admin_setting_configtext(
            'local_subscriptions/stripe_live_portal_configuration_id',
            get_string('stripe_portal_configuration_id_live', 'local_subscriptions'),
            get_string('stripe_portal_configuration_id_desc', 'local_subscriptions'),
            '', PARAM_RAW_TRIMMED
        ));


        // LIVE SAS keys.
        $settings->add(new admin_setting_configpasswordunmask(
            'local_subscriptions/stripe_live_sas_secret',
            get_string('stripe_secret_live_sas', 'local_subscriptions'),
            '', ''
        ));
        $settings->add(new admin_setting_configtext(
            'local_subscriptions/stripe_live_sas_publishable',
            get_string('stripe_publishable_live_sas', 'local_subscriptions'),
            '', '', PARAM_RAW_TRIMMED
        ));
        $settings->add(new admin_setting_configpasswordunmask(
            'local_subscriptions/stripe_live_sas_webhook_secret',
            get_string('stripe_webhook_secret_live_sas', 'local_subscriptions'),
            '', ''
        ));
        $settings->add(new admin_setting_configtext(
            'local_subscriptions/stripe_live_sas_portal_configuration_id',
            get_string('stripe_portal_configuration_id_live_sas', 'local_subscriptions'),
            get_string('stripe_portal_configuration_id_desc', 'local_subscriptions'),
            '', PARAM_RAW_TRIMMED
        ));

        // === Alfa Bank =============================================================
        $settings->add(new admin_setting_heading(
            'local_subscriptions_alfa_hdr',
            get_string('alfa_settings_header', 'local_subscriptions'),
            ''
        ));

        // Environnement Alfa: test/live
        $settings->add(new admin_setting_configselect(
            'local_subscriptions/alfa_env',
            get_string('env_mode', 'local_subscriptions'),
            get_string('env_mode_desc', 'local_subscriptions'),
            'test',
            ['test' => get_string('env_test', 'local_subscriptions'),
            'live' => get_string('env_live', 'local_subscriptions')]
        ));

        // TEST creds
        $settings->add(new admin_setting_configtext(
            'local_subscriptions/alfa_test_api_base',
            get_string('alfa_api_base_test', 'local_subscriptions'),
            'https://alfa.rbsuat.com', 'https://alfa.rbsuat.com', PARAM_URL
        ));
        $settings->add(new admin_setting_configtext(
            'local_subscriptions/alfa_test_username',
            get_string('alfa_username_test', 'local_subscriptions'),
            '', '', PARAM_RAW_TRIMMED
        ));
        $settings->add(new admin_setting_configpasswordunmask(
            'local_subscriptions/alfa_test_password',
            get_string('alfa_password_test', 'local_subscriptions'),
            '', ''
        ));
        $settings->add(new admin_setting_configpasswordunmask(
            'local_subscriptions/alfa_test_token',
            get_string('alfa_token_test', 'local_subscriptions'),
            '', ''
        ));
        $settings->add(new admin_setting_configpasswordunmask(
            'local_subscriptions/alfa_test_webhook_secret',
            get_string('alfa_webhook_secret_test', 'local_subscriptions'),
            '', ''
        ));

        // LIVE creds
        $settings->add(new admin_setting_configtext(
            'local_subscriptions/alfa_live_api_base',
            get_string('alfa_api_base_live', 'local_subscriptions'),
            '', '', PARAM_URL
        ));
        $settings->add(new admin_setting_configtext(
            'local_subscriptions/alfa_live_username',
            get_string('alfa_username_live', 'local_subscriptions'),
            '', '', PARAM_RAW_TRIMMED
        ));
        $settings->add(new admin_setting_configpasswordunmask(
            'local_subscriptions/alfa_live_password',
            get_string('alfa_password_live', 'local_subscriptions'),
            '', ''
        ));
        $settings->add(new admin_setting_configpasswordunmask(
            'local_subscriptions/alfa_live_token',
            get_string('alfa_token_live', 'local_subscriptions'),
            '', ''
        ));
        $settings->add(new admin_setting_configpasswordunmask(
            'local_subscriptions/alfa_live_webhook_secret',
            get_string('alfa_webhook_secret_live', 'local_subscriptions'),
            '', ''
        ));

        // === Misc. =============================================================
        $settings->add(new admin_setting_heading(
            'local_subs_email_heading',
        get_string('emails_links_heading', 'local_subscriptions'),
        get_string('emails_links_heading_desc', 'local_subscriptions')
        ));

        $settings->add(new admin_setting_configtext(
            'local_subscriptions/email_link_secret',
        get_string('email_link_secret_label', 'local_subscriptions'),
        get_string('email_link_secret_desc', 'local_subscriptions'),
            '', PARAM_RAW_TRIMMED
        ));

        $settings->add(new admin_setting_configtext(
            'local_subscriptions/support_email',
            get_string('settings_support_email', 'local_subscriptions'),
            get_string('settings_support_email_desc', 'local_subscriptions'),
            'support@campusfr.fr',
            PARAM_EMAIL
        ));


        $settings->add(new admin_setting_heading(
            'local_subscriptions_personal_offer_mail_header',
            get_string('settings:personal_offer_mail_header', 'local_subscriptions'),
            get_string('settings:personal_offer_mail_header_desc', 'local_subscriptions')
        ));
        $settings->add(new admin_setting_configtext(
            'local_subscriptions/personal_offer_mail_batch_size',
            get_string('settings:personal_offer_mail_batch_size', 'local_subscriptions'),
            get_string('settings:personal_offer_mail_batch_size_desc', 'local_subscriptions'),
            20,
            PARAM_INT
        ));
        $settings->add(new admin_setting_configtext(
            'local_subscriptions/personal_offer_mail_hourly_limit',
            get_string('settings:personal_offer_mail_hourly_limit', 'local_subscriptions'),
            get_string('settings:personal_offer_mail_hourly_limit_desc', 'local_subscriptions'),
            100,
            PARAM_INT
        ));

        $settings->add(new admin_setting_configtext(
            'local_subscriptions/brand_logo_url',
        get_string('brand_logo_url_label', 'local_subscriptions'),
        get_string('brand_logo_url_desc', 'local_subscriptions'),
            '', PARAM_URL
        ));

        $settings->add(new admin_setting_heading(
            'local_subscriptions/followups_heading',
        get_string('followups_heading', 'local_subscriptions'),
        get_string('followups_heading_desc', 'local_subscriptions')
        ));

        $settings->add(new admin_setting_configtext(
            'local_subscriptions/expire_pending_after_minutes',
        get_string('expire_pending_after_minutes_label', 'local_subscriptions'),
        get_string('expire_pending_after_minutes_desc', 'local_subscriptions'),
            60, PARAM_INT
        ));

        $settings->add(new admin_setting_configtext(
            'local_subscriptions/reminder1_after_minutes',
        get_string('reminder1_after_minutes_label', 'local_subscriptions'),
        get_string('reminder1_after_minutes_desc', 'local_subscriptions'),
            1440, PARAM_INT // 24 h
        ));

        $settings->add(new admin_setting_configtext(
            'local_subscriptions/reminder2_after_minutes',
        get_string('reminder2_after_minutes_label', 'local_subscriptions'),
        get_string('reminder2_after_minutes_desc', 'local_subscriptions'),
            4320, PARAM_INT // 72 h
        ));

        $settings->add(new admin_setting_configtext(
            'local_subscriptions/featured_planid',
        get_string('featured_planid_label', 'local_subscriptions'),
        get_string('featured_planid_desc', 'local_subscriptions'),
            '', PARAM_INT
        ));

        $settings->add(new admin_setting_configcheckbox(
            'local_subscriptions/email_show_pr_ref',
            get_string('email_show_pr_ref', 'local_subscriptions'),
            get_string('email_show_pr_ref_desc', 'local_subscriptions'),
            0
        ));

        // Pages politiques/CGU/CGV (RU vs ROW)
        $settings->add(new admin_setting_configtext(
            'local_subscriptions/policy_url_ru',
            get_string('policy_url_ru', 'local_subscriptions'),
            '', '', PARAM_URL
        ));
        $settings->add(new admin_setting_configtext(
            'local_subscriptions/policy_url_row',
            get_string('policy_url_row', 'local_subscriptions'),
            '', '', PARAM_URL
        ));
        $settings->add(new admin_setting_configtext(
            'local_subscriptions/terms_url_ru',
            get_string('terms_url_ru', 'local_subscriptions'),
            '', '', PARAM_URL
        ));
        $settings->add(new admin_setting_configtext(
            'local_subscriptions/terms_url_row',
            get_string('terms_url_row', 'local_subscriptions'),
            '', '', PARAM_URL
        ));
        $settings->add(new admin_setting_configtext(
            'local_subscriptions/offer_url_ru',
            get_string('offer_url_ru', 'local_subscriptions'),
            '', '', PARAM_URL
        ));
        $settings->add(new admin_setting_configtext(
            'local_subscriptions/offer_url_row',
            get_string('offer_url_row', 'local_subscriptions'),
            '', '', PARAM_URL
        ));

        $settings->add(new admin_setting_configtext(
            'local_subscriptions/email_copy_to',
            get_string('email_copy_to', 'local_subscriptions'),
            get_string('email_copy_to_desc', 'local_subscriptions'),
            'admin@campusfr.fr', PARAM_RAW_TRIMMED
        ));

        $settings->add(new admin_setting_heading(
            'local_subscriptions_commerce_mail_audit_heading',
            get_string('settings:commerce_mail_audit_heading', 'local_subscriptions'),
            get_string('settings:commerce_mail_audit_heading_desc', 'local_subscriptions')
        ));

        $settings->add(new admin_setting_configcheckbox(
            'local_subscriptions/commerce_mail_audit_copy_enabled',
            get_string('settings:commerce_mail_audit_copy_enabled', 'local_subscriptions'),
            get_string('settings:commerce_mail_audit_copy_enabled_desc', 'local_subscriptions'),
            0
        ));

        $settings->add(new admin_setting_configtext(
            'local_subscriptions/commerce_mail_audit_copy_address',
            get_string('settings:commerce_mail_audit_copy_address', 'local_subscriptions'),
            get_string('settings:commerce_mail_audit_copy_address_desc', 'local_subscriptions'),
            'log@campusfr.fr',
            PARAM_EMAIL
        ));

        $settings->add(new admin_setting_configmulticheckbox(
            'local_subscriptions/commerce_mail_audit_copy_types',
            get_string('settings:commerce_mail_audit_copy_types', 'local_subscriptions'),
            get_string('settings:commerce_mail_audit_copy_types_desc', 'local_subscriptions'),
            [
                \local_subscriptions\commerce\mail\CommerceMailType::PURCHASE_RECEIPT => 1,
                \local_subscriptions\commerce\mail\CommerceMailType::PURCHASE_ACCESS => 1,
            ],
            [
                \local_subscriptions\commerce\mail\CommerceMailType::PURCHASE_RECEIPT =>
                    get_string('commerce_mail_type_purchase_receipt', 'local_subscriptions'),
                \local_subscriptions\commerce\mail\CommerceMailType::PURCHASE_ACCESS =>
                    get_string('commerce_mail_type_purchase_access', 'local_subscriptions'),
                \local_subscriptions\commerce\mail\CommerceMailType::PAYMENT_PENDING =>
                    get_string('commerce_mail_type_payment_pending', 'local_subscriptions'),
                \local_subscriptions\commerce\mail\CommerceMailType::PAYMENT_FAILED =>
                    get_string('commerce_mail_type_payment_failed', 'local_subscriptions'),
                \local_subscriptions\commerce\mail\CommerceMailType::PAYMENT_CANCELLED =>
                    get_string('commerce_mail_type_payment_cancelled', 'local_subscriptions'),
            ]
        ));

    $settings->add(
        new admin_setting_configtext(
            'local_subscriptions/commerce_mail_audit_batch_size',
            get_string('settings:commerce_mail_audit_batch_size', 'local_subscriptions'),
            get_string('settings:commerce_mail_audit_batch_size_desc', 'local_subscriptions'),
            10,
            PARAM_INT
        )
    );

    $settings->add(
        new admin_setting_configtext(
            'local_subscriptions/commerce_mail_audit_hourly_limit',
            get_string('settings:commerce_mail_audit_hourly_limit', 'local_subscriptions'),
            get_string('settings:commerce_mail_audit_hourly_limit_desc', 'local_subscriptions'),
            50,
            PARAM_INT
        )
    );

        $settings->add(new admin_setting_configcheckbox(
            'local_subscriptions/commerce_mail_audit_copy_include_attachment',
            get_string('settings:commerce_mail_audit_copy_include_attachment', 'local_subscriptions'),
            get_string('settings:commerce_mail_audit_copy_include_attachment_desc', 'local_subscriptions'),
            0
        ));

        // --- Section Essai 7 jours ---
        $settings->add(new admin_setting_heading('ls_trial_heading',
            get_string('settings_trial_section', 'local_subscriptions'), ''));

        $settings->add(new admin_setting_configtext(
            'local_subscriptions/trial_plan_id',
            get_string('settings_trial_planid', 'local_subscriptions'),
            get_string('settings_trial_planid_desc', 'local_subscriptions'),
            0, PARAM_INT));

        $settings->add(new admin_setting_configtext(
            'local_subscriptions/trial_duration_days',
            get_string('settings_trial_duration_days', 'local_subscriptions'),
            get_string('settings_trial_duration_days_desc', 'local_subscriptions'),
            7, PARAM_INT));

        $settings->add(new admin_setting_configtext(
            'local_subscriptions/trial_discount_percent',
            get_string('settings_trial_discount_percent', 'local_subscriptions'),
            get_string('settings_trial_discount_percent_desc', 'local_subscriptions'),
            15, PARAM_INT));

        $settings->add(new admin_setting_configtext(
            'local_subscriptions/trial_discount_hours',
            get_string('settings_trial_discount_hours', 'local_subscriptions'),
            get_string('settings_trial_discount_hours_desc', 'local_subscriptions'),
            72, PARAM_INT));

        $settings->add(new admin_setting_heading(
            'local_subscriptions_commerce_migration_heading',
            get_string('settings:commerce_migration_heading', 'local_subscriptions'),
            get_string('settings:commerce_migration_heading_desc', 'local_subscriptions')
        ));

        $commercedefaults = [
            'commerce_checkout_enabled' => 1,
            'commerce_fulfillment_enabled' => 1,
            'commerce_dual_write_enabled' => 0,
            'commerce_dual_write_strict' => 0,
            'commerce_native_read_shadow_enabled' => 0,
            'commerce_native_read_shadow_strict' => 0,
        ];

        foreach ($commercedefaults as $commerceflag => $defaultvalue) {
            $settings->add(new admin_setting_configcheckbox(
                'local_subscriptions/' . $commerceflag,
                get_string('settings:' . $commerceflag, 'local_subscriptions'),
                get_string('settings:' . $commerceflag . '_desc', 'local_subscriptions'),
                $defaultvalue
            ));
        }

        $settings->add(new admin_setting_configselect(
            'local_subscriptions/commerce_runtime_read_mode',
            get_string('settings:commerce_runtime_read_mode', 'local_subscriptions'),
            get_string('settings:commerce_runtime_read_mode_desc', 'local_subscriptions'),
            'legacy',
            [
                'legacy' => get_string('settings:commerce_runtime_read_mode_legacy', 'local_subscriptions'),
                'shadow' => get_string('settings:commerce_runtime_read_mode_shadow', 'local_subscriptions'),
                'native' => get_string('settings:commerce_runtime_read_mode_native', 'local_subscriptions'),
                'auto' => get_string('settings:commerce_runtime_read_mode_auto', 'local_subscriptions'),
            ]
        ));

        $settings->add(new admin_setting_configcheckbox(
            'local_subscriptions/commerce_runtime_read_strict',
            get_string('settings:commerce_runtime_read_strict', 'local_subscriptions'),
            get_string('settings:commerce_runtime_read_strict_desc', 'local_subscriptions'),
            0
        ));

        $i10creadflags = [
            'commerce_native_crm_reads_enabled' => 0,
            'commerce_native_admin_reads_enabled' => 0,
            'commerce_native_user_reads_enabled' => 0,
            'commerce_native_email_reads_enabled' => 0,
            'commerce_native_task_reads_enabled' => 0,
            'commerce_native_shadow_compare_enabled' => 0,
            'commerce_native_legacy_fallback_enabled' => 1,
        ];

        foreach ($i10creadflags as $flag => $defaultvalue) {
            $settings->add(new admin_setting_configcheckbox(
                'local_subscriptions/' . $flag,
                get_string('settings:' . $flag, 'local_subscriptions'),
                get_string('settings:' . $flag . '_desc', 'local_subscriptions'),
                $defaultvalue
            ));
        }

        $i10dwriteflags = [
            'commerce_native_dual_write_enabled' => 0,
            'commerce_native_task_dual_write_enabled' => 0,
            'commerce_native_shadow_write_compare_enabled' => 0,
        ];

        foreach ($i10dwriteflags as $flag => $defaultvalue) {
            $settings->add(new admin_setting_configcheckbox(
                'local_subscriptions/' . $flag,
                get_string('settings:' . $flag, 'local_subscriptions'),
                get_string('settings:' . $flag . '_desc', 'local_subscriptions'),
                $defaultvalue
            ));
        }

        $settings->add(new admin_setting_heading(
            'local_subscriptions_invoice_heading',
            get_string('commerce_i411_invoice_settings', 'local_subscriptions'),
            get_string('commerce_i411_invoice_settings_desc', 'local_subscriptions')
        ));
        foreach (['eur', 'rub'] as $invoicecurrency) {
            $settings->add(new admin_setting_heading(
                'local_subscriptions_invoice_' . $invoicecurrency . '_heading',
                get_string('commerce_i411_invoice_profile_' . $invoicecurrency, 'local_subscriptions'),
                ''
            ));
            foreach (['name', 'address', 'legal', 'email', 'phone', 'website', 'tax_notice', 'footer'] as $invoicefield) {
                $settingclass = in_array($invoicefield, ['address', 'legal', 'tax_notice', 'footer'], true)
                    ? admin_setting_configtextarea::class : admin_setting_configtext::class;
                $settings->add(new $settingclass(
                    'local_subscriptions/invoice_' . $invoicecurrency . '_' . $invoicefield,
                    get_string('commerce_i411_invoice_' . $invoicefield, 'local_subscriptions'),
                    '',
                    '',
                    PARAM_RAW_TRIMMED
                ));
            }
        }

        $settings->add(new admin_setting_heading(
            'local_subscriptions_commerce_catalog_heading',
            get_string('settings:commerce_catalog_heading', 'local_subscriptions'),
            get_string('settings:commerce_catalog_heading_desc', 'local_subscriptions')
        ));
        $settings->add(new admin_setting_configtext(
            'local_subscriptions/commerce_enabled_currencies',
            get_string('settings:commerce_enabled_currencies', 'local_subscriptions'),
            get_string('settings:commerce_enabled_currencies_desc', 'local_subscriptions'),
            'EUR,RUB',
            PARAM_RAW_TRIMMED
        ));

        $settings->add(new admin_setting_heading('ls_paylock_heading',
            get_string('settings_paylock_section', 'local_subscriptions'), ''));

        $settings->add(new admin_setting_configcheckbox(
            'local_subscriptions/payments_lock_strict',
            get_string('settings_paylock_strict', 'local_subscriptions'),
            get_string('settings_paylock_strict_desc', 'local_subscriptions'),
            0
        ));

        $settings->add(new admin_setting_configtext(
            'local_subscriptions/payments_mismatch_tolerance_cents',
            get_string('settings_paylock_tolerance', 'local_subscriptions'),
            get_string('settings_paylock_tolerance_desc', 'local_subscriptions'),
            2, PARAM_INT
        ));

    }

    $ADMIN->add('localplugins', $settings);



    $settings->add(new admin_setting_configselect(
        'local_subscriptions/commerce_runtime_mode',
        get_string('commerce_runtime_mode', 'local_subscriptions'),
        get_string('commerce_runtime_mode_desc', 'local_subscriptions'),
        'legacy',
        [
            'legacy' => get_string('commerce_runtime_mode_legacy', 'local_subscriptions'),
            'shadow' => get_string('commerce_runtime_mode_shadow', 'local_subscriptions'),
            'native' => get_string('commerce_runtime_mode_native', 'local_subscriptions'),
        ]
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_subscriptions/commerce_runtime_native_fallback_enabled',
        get_string('commerce_runtime_native_fallback_enabled', 'local_subscriptions'),
        get_string('commerce_runtime_native_fallback_enabled_desc', 'local_subscriptions'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_subscriptions/commerce_fulfillment_shadow_enabled',
        get_string('commerce_fulfillment_shadow_enabled', 'local_subscriptions'),
        get_string('commerce_fulfillment_shadow_enabled_desc', 'local_subscriptions'),
        0
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_subscriptions/commerce_native_reconciliation_enabled',
        get_string('commerce_native_reconciliation_enabled', 'local_subscriptions'),
        get_string('commerce_native_reconciliation_enabled_desc', 'local_subscriptions'),
        0
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_subscriptions/commerce_native_repair_enabled',
        get_string('commerce_native_repair_enabled', 'local_subscriptions'),
        get_string('commerce_native_repair_enabled_desc', 'local_subscriptions'),
        0
    ));
}
