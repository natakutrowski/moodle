<?php
require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\personaloffer\admin\CommercePersonalOfferCrmInput;
use local_subscriptions\commerce\personaloffer\admin\CommercePersonalOfferCrmPresentation;
use local_subscriptions\commerce\order\reference\CommercePublicOrderReference;
use local_subscriptions\commerce\personaloffer\campaign\CommercePersonalOfferCampaignManager;
use local_subscriptions\commerce\personaloffer\domain\CommercePersonalOfferTerms;
use local_subscriptions\commerce\personaloffer\mail\CommercePersonalOfferMailImageService;
use local_subscriptions\commerce\personaloffer\mail\CommercePersonalOfferIndividualMailStudioBridge;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\commerce\rendering\CommerceOffersAccessNavigationRenderer;
use local_subscriptions\crm\commerce\rendering\CommerceOffersAccessWorkflowRenderer;
use local_subscriptions\crm\commerce\rendering\CommerceOffersAccessConfigurationRenderer;
use local_subscriptions\crm\commerce\rendering\CommercePersonalOfferConditionsRenderer;
use local_subscriptions\commerce\personaloffer\campaign\CommercePersonalOfferCampaignValidityService;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

$context = AdminSecurity::require(Capabilities::MANAGE_CRM_ADMIN_TOOLS);

$prefillemail = trim(optional_param('prefillemail', '', PARAM_EMAIL));
$prefillsourcemode = optional_param(
    'prefillsourcemode',
    'none',
    PARAM_ALPHANUMEXT
);
if (!in_array($prefillsourcemode, ['none', 'product', 'purchase'], true)) {
    $prefillsourcemode = 'none';
}
$prefillsourcepurchase = trim(optional_param(
    'prefillsourcepurchase',
    '',
    PARAM_RAW_TRIMMED
));

$url = new moodle_url('/local/subscriptions/admin/commerce/personal-offers/create.php');
$title = get_string('commerce_personal_offer_create_individual', 'local_subscriptions');
CrmPageConfigurator::configure($PAGE, $context, $url, $title, 'local-subscriptions-commerce-personal-offer-create-page');

$products = $DB->get_records('local_subs_commerce_product', [], 'name ASC', 'id,sku,name,status,type,metadatajson');
$currencies = array_values(array_unique(array_map(
    static fn($r): string => strtoupper((string)$r->currency),
    array_values($DB->get_records_sql("SELECT DISTINCT currency FROM {local_subs_commerce_prod_price} WHERE active = 1 ORDER BY currency"))
)));
if ($currencies === []) { $currencies = ['EUR', 'RUB']; }

$campaigns = $DB->get_records('local_subs_commerce_offer_campaign', [], 'name ASC', 'id,campaignkey,name');
$mailstudiobridge = CommercePersonalOfferIndividualMailStudioBridge::create($DB);
$mailtemplateoptions = $mailstudiobridge->template_options();

$productdisplaylabel = static function(\stdClass $product) use ($DB): string {
    return CommercePersonalOfferCrmPresentation::business_product_label(
        $DB,
        (int)$product->id
    );
};

$emailrecords = $DB->get_records_sql(
    "SELECT id, firstname, lastname, email
       FROM {user}
      WHERE deleted = 0 AND email <> ''
   ORDER BY lastname, firstname, email",
    [],
    0,
    1000
);
$purchases = $DB->get_records_sql(
    "SELECT id, reference, customeremail, timecreated
       FROM {local_subscriptions_commerce_purchase}
   ORDER BY timecreated DESC",
    [],
    0,
    300
);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()) {
    try {
        $amountvalues = [];
        foreach ($currencies as $currency) {
            $amountvalues[$currency] = optional_param('amount_' . strtolower($currency), '', PARAM_RAW_TRIMMED);
        }
        $terms = CommercePersonalOfferCrmInput::terms(
            required_param('strategy', PARAM_ALPHANUMEXT),
            CommercePersonalOfferCrmInput::amounts_from_major($amountvalues),
            optional_param('percent', 0, PARAM_INT)
        );
        $email = CommercePersonalOfferCrmInput::resolve_beneficiary_email(
            $DB,
            required_param('email', PARAM_RAW_TRIMMED)
        );
        $u = $DB->get_record('user', ['email' => $email, 'deleted' => 0], 'id,email', IGNORE_MULTIPLE);
        $sourcemode = optional_param('sourcemode', 'none', PARAM_ALPHANUMEXT);
        $sourcepurchaseid = null;
        if ($sourcemode === 'purchase') {
            $sourcepurchaseid = CommercePersonalOfferCrmInput::resolve_purchase_id(
                $DB,
                optional_param('sourcepurchase', '', PARAM_RAW_TRIMMED)
            );
            if ($sourcepurchaseid === null) { throw new coding_exception('Unable to resolve the selected source purchase.'); }
        } else if ($sourcemode === 'product') {
            $ownership = CommercePersonalOfferCrmInput::resolve_product_ownership(
                $DB, $email, $u ? (int)$u->id : null, required_param('sourceproductid', PARAM_INT)
            );
            if (!$ownership['owned']) {
                throw new coding_exception('The beneficiary does not own the selected source product.');
            }
            $sourcepurchaseid = $ownership['sourcepurchaseid'];
            $ownershipsource = $ownership['source'];
            $ownershipproductid = $ownership['productid'] ?? null;
            $ownershipproductsku = $ownership['productsku'] ?? null;
        }
        $campaignkey = optional_param('campaignkey', '', PARAM_TEXT);

        $validitymode = CommercePersonalOfferCampaignValidityService::normalise_mode(
            optional_param(
                'validitymode',
                CommercePersonalOfferCampaignValidityService::MODE_FIXED,
                PARAM_ALPHANUMEXT
            )
        );
        $validitytimezone = CommercePersonalOfferCampaignValidityService::normalise_timezone(
            optional_param(
                'validitytimezone',
                CommercePersonalOfferCampaignValidityService::DEFAULT_TIMEZONE,
                PARAM_RAW_TRIMMED
            )
        );
        $noexpiration = optional_param('noexpiration', 0, PARAM_BOOL) === 1;
        $validfrom = null;
        $expiresat = null;

        if ($validitymode === CommercePersonalOfferCampaignValidityService::MODE_FIXED) {
            $validfrom = CommercePersonalOfferCrmInput::datetime_local(
                optional_param('validfrom', '', PARAM_RAW_TRIMMED),
                $validitytimezone
            );
            if (!$noexpiration) {
                $expiresraw = trim(required_param('expiresat', PARAM_RAW_TRIMMED));
                if ($expiresraw === '') {
                    throw new coding_exception(
                        get_string(
                            'commerce_personal_offer_expiration_required',
                            'local_subscriptions'
                        )
                    );
                }
                $expiresat = CommercePersonalOfferCrmInput::datetime_local(
                    $expiresraw,
                    $validitytimezone
                );
                if ($expiresat === null) {
                    throw new coding_exception(
                        get_string(
                            'commerce_personal_offer_expiration_required',
                            'local_subscriptions'
                        )
                    );
                }
                if ($validfrom !== null && $expiresat <= $validfrom) {
                    throw new coding_exception(
                        get_string(
                            'commerce_personal_offer_expiration_after_start',
                            'local_subscriptions'
                        )
                    );
                }
            }
        } else {
            $durationseconds = CommercePersonalOfferCampaignValidityService::duration_seconds(
                required_param('validitydurationvalue', PARAM_INT),
                required_param('validitydurationunit', PARAM_ALPHA)
            );
            $validfrom = time();
            $expiresat = $validfrom + $durationseconds;
            $noexpiration = false;
        }

        $mailtemplateid = optional_param('mailtemplateid', 0, PARAM_INT);
        $mailtemplatesnapshot = $mailtemplateid > 0
            ? $mailstudiobridge->snapshot($mailtemplateid)
            : [];

        $res = CommercePersonalOfferCampaignManager::create($DB)->issue_individual([
            'email' => $email,
            'beneficiaryuserid' => $u ? (int)$u->id : null,
            'sourcepurchaseid' => $sourcepurchaseid,
            'targetproductid' => required_param('targetproductid', PARAM_INT),
            'eligibilitymode' => $sourcemode === 'purchase' ? 'source_purchase' : ($sourcemode === 'product' ? 'product_ownership' : 'standalone'),
            'ownershipsource' => $ownershipsource ?? null,
            'ownershipproductid' => $ownershipproductid ?? null,
            'ownershipproductsku' => $ownershipproductsku ?? null,
            'campaignkey' => $campaignkey !== '' ? $campaignkey : 'crm-individual',
            'terms' => $terms->get_data(),
            'validfrom' => $validfrom,
            'expiresat' => $expiresat,
            'validitymode' => $validitymode,
            'validitytimezone' => $validitytimezone,
            'noexpiration' => $noexpiration ? 1 : 0,
            'mailtemplateid' => $mailtemplateid > 0 ? $mailtemplateid : null,
            'mailtemplatename' => (string)($mailtemplatesnapshot['templatename'] ?? ''),
            'mailtemplatesnapshot' => $mailtemplatesnapshot,
        ], (int)$USER->id);

        $offerid = (int)$res['offer']->get_id();
        if (!empty($_FILES['mailimage']['tmp_name'])) {
            (new CommercePersonalOfferMailImageService())->save_uploaded_file(
                $offerid,
                (array)$_FILES['mailimage']
            );
        }

        redirect(new moodle_url('/local/subscriptions/admin/commerce/personal-offers/view.php', ['id' => $offerid]));
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    ['label' => get_string('crm_commerce_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php')],
    ['label' => get_string('commerce_offers_access_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/offers-access/index.php')],
    ['label' => get_string('commerce_personal_offers_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/personal-offers/index.php')],
    ['label' => $title, 'url' => null],
]);
echo CrmPageHeader::render($title, get_string('commerce_personal_offer_create_individual_help', 'local_subscriptions'), HelpContext::COMMERCE);
echo CommerceSectionNavigationRenderer::render(
    CommerceSectionNavigationRenderer::OFFERS_ACCESS,
    $context
);
echo CommerceOffersAccessNavigationRenderer::render(
    CommerceOffersAccessNavigationRenderer::OFFERS
);
echo CommerceOffersAccessWorkflowRenderer::render(
    CommerceOffersAccessWorkflowRenderer::CONFIGURATION,
    'offer',
    'one'
);
if ($error !== '') { echo html_writer::div(s($error), 'alert alert-danger'); }

echo html_writer::start_tag('form', [
    'method' => 'post',
    'enctype' => 'multipart/form-data',
    'class' => 'crm-offers-access-guided-form',
]);
echo html_writer::empty_tag('input', [
    'type' => 'hidden',
    'name' => 'sesskey',
    'value' => sesskey(),
]);
echo CommerceOffersAccessConfigurationRenderer::start_layout();
echo CommerceOffersAccessConfigurationRenderer::start_main();
echo CommerceOffersAccessConfigurationRenderer::start_section(
    get_string('commerce_offers_access_config_beneficiary_title', 'local_subscriptions'),
    get_string('commerce_offers_access_config_beneficiary_help', 'local_subscriptions'),
    'fa-user'
);

echo html_writer::start_div('mb-3');
echo html_writer::tag('label', get_string('commerce_personal_offer_email', 'local_subscriptions'), ['for' => 'offer-email', 'class' => 'form-label fw-semibold']);
echo html_writer::empty_tag('input', [
    'id' => 'offer-email',
    'name' => 'email',
    'type' => 'text',
    'list' => 'offer-email-list',
    'class' => 'form-control',
    'autocomplete' => 'off',
    'required' => 'required',
    'value' => $prefillemail,
]);
echo html_writer::start_tag('datalist', ['id' => 'offer-email-list']);
foreach ($emailrecords as $record) {
    $fullname = trim((string)$record->firstname . ' ' . (string)$record->lastname);
    $value = $fullname !== '' ? $fullname . ' <' . $record->email . '>' : $record->email;
    echo html_writer::tag('option', '', ['value' => $value]);
}
echo html_writer::end_tag('datalist');
echo html_writer::div(get_string('commerce_personal_offer_email_help', 'local_subscriptions'), 'form-text');
echo html_writer::end_div();

ob_start();
echo html_writer::start_div('mb-4');
echo html_writer::tag('label', get_string('commerce_personal_offer_campaign_optional', 'local_subscriptions'), ['for' => 'campaignkey', 'class' => 'form-label fw-semibold']);
$campaignopts = ['' => get_string('commerce_personal_offer_campaign_none', 'local_subscriptions')];
foreach ($campaigns as $campaign) { $campaignopts[$campaign->campaignkey] = $campaign->name . ' [' . $campaign->campaignkey . ']'; }
echo html_writer::select($campaignopts, 'campaignkey', '', false, ['id' => 'campaignkey', 'class' => 'form-select']);
echo html_writer::div(get_string('commerce_personal_offer_campaign_optional_help', 'local_subscriptions'), 'form-text');
echo html_writer::end_div();

echo html_writer::start_div('mb-4');
echo html_writer::tag('label', get_string('commerce_personal_offer_source_basis', 'local_subscriptions'), ['for' => 'sourcemode', 'class' => 'form-label fw-semibold']);
echo html_writer::select([
    'none' => get_string('commerce_personal_offer_source_none', 'local_subscriptions'),
    'product' => get_string('commerce_personal_offer_source_product', 'local_subscriptions'),
    'purchase' => get_string('commerce_personal_offer_source_purchase_mode', 'local_subscriptions'),
], 'sourcemode', $prefillsourcemode, false, [
    'id' => 'sourcemode',
    'class' => 'form-select mb-2',
]);
echo html_writer::div(get_string('commerce_personal_offer_source_basis_help', 'local_subscriptions'), 'form-text mb-3');

echo html_writer::tag('label', get_string('commerce_personal_offer_source_product', 'local_subscriptions'), ['for' => 'sourceproductid', 'class' => 'form-label']);
$sourceproductopts = ['' => get_string('choose')];
foreach ($products as $product) { $sourceproductopts[$product->id] = $productdisplaylabel($product); }
echo html_writer::select($sourceproductopts, 'sourceproductid', '', false, ['id' => 'sourceproductid', 'class' => 'form-select mb-3']);

echo html_writer::tag('label', get_string('commerce_personal_offer_source_purchase_optional', 'local_subscriptions'), ['for' => 'sourcepurchase', 'class' => 'form-label']);
echo html_writer::empty_tag('input', [
    'id' => 'sourcepurchase',
    'name' => 'sourcepurchase',
    'list' => 'sourcepurchase-list',
    'class' => 'form-control',
    'autocomplete' => 'off',
    'placeholder' => get_string(
        'commerce_personal_offer_source_purchase_placeholder',
        'local_subscriptions'
    ),
    'value' => $prefillsourcepurchase,
]);
echo html_writer::start_tag('datalist', ['id' => 'sourcepurchase-list']);
$publicreferences = new CommercePublicOrderReference();
foreach ($purchases as $purchase) {
    $public = $publicreferences->from_internal((string)$purchase->reference, (int)$purchase->timecreated);
    $label = $public . ($purchase->customeremail ? ' — ' . $purchase->customeremail : '') . ' [' . $purchase->reference . ']';
    echo html_writer::tag('option', $label, ['value' => $purchase->reference]);
}
echo html_writer::end_tag('datalist');
echo html_writer::div(get_string('commerce_personal_offer_source_purchase_help', 'local_subscriptions'), 'form-text');
echo html_writer::end_div();
$advancedsource = ob_get_clean();
echo CommerceOffersAccessConfigurationRenderer::advanced(
    get_string('commerce_offers_access_config_context_title', 'local_subscriptions'),
    $advancedsource,
    get_string('commerce_offers_access_config_context_hint', 'local_subscriptions')
);
echo CommerceOffersAccessConfigurationRenderer::end_section();

echo CommerceOffersAccessConfigurationRenderer::start_section(
    get_string('commerce_personal_offer_offer_title', 'local_subscriptions'),
    get_string('commerce_offers_access_config_offer_help', 'local_subscriptions'),
    'fa-tag'
);

echo html_writer::start_div('mb-4');
echo html_writer::tag('label', get_string('commerce_personal_offer_target', 'local_subscriptions'), ['for' => 'targetproductid', 'class' => 'form-label fw-semibold']);
$productopts = [];
foreach ($products as $product) { $productopts[$product->id] = $productdisplaylabel($product); }
echo html_writer::select($productopts, 'targetproductid', '', false, ['id' => 'targetproductid', 'class' => 'form-select', 'required' => 'required']);
echo html_writer::div(get_string('commerce_personal_offer_target_help', 'local_subscriptions'), 'form-text');
echo html_writer::end_div();

echo CommercePersonalOfferConditionsRenderer::pricing($currencies);

ob_start();
echo html_writer::start_div('mb-3');
echo html_writer::tag(
    'label',
    get_string('commerce_personal_offer_mail_template', 'local_subscriptions'),
    ['for' => 'mailtemplateid', 'class' => 'form-label fw-semibold']
);
echo html_writer::select(
    [0 => get_string('commerce_personal_offer_mail_template_default', 'local_subscriptions')]
        + $mailtemplateoptions,
    'mailtemplateid',
    0,
    false,
    [
        'id' => 'mailtemplateid',
        'class' => 'form-select',
    ]
);
echo html_writer::div(
    get_string('commerce_personal_offer_mail_template_help', 'local_subscriptions'),
    'form-text'
);
echo html_writer::end_div();

echo html_writer::start_div('mb-3');
echo html_writer::tag(
    'label',
    get_string('commerce_personal_offer_mail_image', 'local_subscriptions'),
    ['for' => 'mailimage', 'class' => 'form-label fw-semibold']
);
echo html_writer::empty_tag('input', [
    'id' => 'mailimage',
    'name' => 'mailimage',
    'type' => 'file',
    'accept' => 'image/jpeg,image/png,image/webp',
    'class' => 'form-control',
]);
echo html_writer::div(
    get_string('commerce_personal_offer_mail_image_help', 'local_subscriptions'),
    'form-text'
);
echo html_writer::end_div();
$communicationoptions = ob_get_clean();
echo CommerceOffersAccessConfigurationRenderer::advanced(
    get_string('commerce_offers_access_config_communication', 'local_subscriptions'),
    $communicationoptions
        . html_writer::div(
            html_writer::link(
                new moodle_url(
                    '/local/subscriptions/admin/commerce/mail/templates/index.php',
                    ['category' => 'personal_offer']
                ),
                get_string(
                    'commerce_offers_access_config_open_mailstudio',
                    'local_subscriptions'
                ) . ' →',
                [
                    'class' => 'btn btn-sm btn-outline-secondary mt-2',
                    'target' => '_blank',
                    'rel' => 'noopener',
                ]
            ),
            'mt-2'
        ),
    get_string('commerce_offers_access_config_communication_hint', 'local_subscriptions')
);

echo html_writer::div(
    CommercePersonalOfferConditionsRenderer::validity(true, true),
    'crm-personal-offer-conditions-after-communication'
);
echo CommerceOffersAccessConfigurationRenderer::end_section();
echo CommerceOffersAccessConfigurationRenderer::end_main();

echo CommerceOffersAccessConfigurationRenderer::summary(
    get_string('commerce_offers_access_config_summary_offer', 'local_subscriptions'),
    [
        [
            'label' => get_string('commerce_offers_access_config_beneficiary', 'local_subscriptions'),
            'value' => $prefillemail !== ''
                ? $prefillemail
                : get_string('commerce_offers_access_config_not_set', 'local_subscriptions'),
            'id' => 'n73-individual-summary-beneficiary',
        ],
        [
            'label' => get_string('commerce_offers_access_config_product', 'local_subscriptions'),
            'value' => get_string('commerce_offers_access_config_not_set', 'local_subscriptions'),
            'id' => 'n73-individual-summary-product',
        ],
        [
            'label' => get_string('commerce_offers_access_config_conditions', 'local_subscriptions'),
            'value' => get_string('commerce_personal_offer_strategy_fixed_price', 'local_subscriptions'),
            'id' => 'n73-individual-summary-pricing',
        ],
        [
            'label' => get_string('commerce_offers_access_config_validity', 'local_subscriptions'),
            'value' => get_string('commerce_personal_offer_validity_fixed', 'local_subscriptions'),
            'id' => 'n73-individual-summary-validity',
        ],
    ],
    'offer',
    new moodle_url(
        '/local/subscriptions/admin/commerce/mail/templates/index.php',
        ['category' => 'personal_offer']
    )
);
echo CommerceOffersAccessConfigurationRenderer::end_layout();

echo html_writer::div(
    html_writer::tag('button', get_string('commerce_personal_offer_create', 'local_subscriptions'), ['type' => 'submit', 'class' => 'btn btn-primary']) .
    html_writer::link(new moodle_url('/local/subscriptions/admin/commerce/personal-offers/index.php'), get_string('cancel'), ['class' => 'btn btn-outline-secondary ms-2']),
    'd-flex gap-2'
);
echo html_writer::end_tag('form');


$PAGE->requires->js_init_code(<<<JS
(function() {
    function optionText(select) {
        if (!select || select.selectedIndex < 0) return '';
        return select.options[select.selectedIndex].text || '';
    }
    function setSummary(id, value) {
        var node = document.getElementById(id);
        if (node) node.textContent = value || '—';
    }

    var email = document.getElementById('offer-email');
    var product = document.getElementById('targetproductid');
    var strategy = document.getElementById('strategy');
    var validFrom = document.getElementById('validfrom');
    var expiresAt = document.getElementById('expiresat');
    var noExpiration = document.getElementById('noexpiration');
    var validityMode = document.getElementById('validitymode');
    var validityFixed = document.getElementById('validity-fixed');
    var validityDuration = document.getElementById('validity-duration');
    var durationValue = document.getElementById('validitydurationvalue');
    var durationUnit = document.getElementById('validitydurationunit');
    var validityTimezone = document.getElementById('validitytimezone');

    function syncValidityMode() {
        var duration = validityMode && validityMode.value === 'duration';
        if (validityFixed) validityFixed.classList.toggle('d-none', duration);
        if (validityDuration) validityDuration.classList.toggle('d-none', !duration);
        if (noExpiration) {
            noExpiration.disabled = duration;
            if (duration) noExpiration.checked = false;
        }
        if (expiresAt) {
            expiresAt.disabled = duration || (noExpiration && noExpiration.checked);
            expiresAt.required = !duration && !(noExpiration && noExpiration.checked);
            if (noExpiration && noExpiration.checked) expiresAt.value = '';
        }
        if (durationValue) durationValue.required = duration;
    }

    function refresh() {
        setSummary(
            'n73-individual-summary-beneficiary',
            email ? email.value : ''
        );
        setSummary(
            'n73-individual-summary-product',
            optionText(product)
        );
        setSummary(
            'n73-individual-summary-pricing',
            optionText(strategy)
        );

        var validity = '';
        var duration = validityMode && validityMode.value === 'duration';
        if (duration) {
            validity = (durationValue ? durationValue.value : '')
                + ' '
                + (durationUnit ? optionText(durationUnit) : '');
        } else {
            if (validFrom && validFrom.value) validity += validFrom.value;
            if (noExpiration && noExpiration.checked) {
                validity += (validity ? ' → ' : '') + '∞';
            } else if (expiresAt && expiresAt.value) {
                validity += (validity ? ' → ' : '') + expiresAt.value;
            }
        }
        if (validityTimezone && validityTimezone.value) {
            validity += (validity ? ' · ' : '') + validityTimezone.value;
        }
        setSummary('n73-individual-summary-validity', validity);
    }

    [validityMode, noExpiration].forEach(function(field) {
        if (!field) return;
        field.addEventListener('change', function() {
            syncValidityMode();
            refresh();
        });
    });

    [
        email,
        product,
        strategy,
        validFrom,
        expiresAt,
        durationValue,
        durationUnit,
        validityTimezone
    ].forEach(function(field) {
        if (!field) return;
        field.addEventListener(
            field.tagName === 'SELECT' ? 'change' : 'input',
            refresh
        );
    });

    syncValidityMode();
    refresh();
})();
JS);

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
