<?php

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\subscription_config;
use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBackLinkRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\admin\AdminFormatter;
use local_subscriptions\admin\AdminEntityLinks;
use local_subscriptions\support\DigitalPresenter;

$context = AdminSecurity::require(Capabilities::MANAGE_DIGITAL);

$id = optional_param('id', 0, PARAM_INT);

$isnew = $id === 0;

$product = null;
$translations = [];

if (!$isnew) {
    $product = $DB->get_record('subscription_digital_product', ['id' => $id], '*', MUST_EXIST);

    $records = $DB->get_records('subscription_digital_product_lang', [
        'productid' => $id,
    ]);

    foreach ($records as $record) {
        $translations[$record->lang] = $record;
    }
}

$stats = $DB->get_record_sql("
    SELECT
        COUNT(*) AS total_purchases,
        SUM(CASE WHEN status IN ('paid', 'completed', 'PAID', 'COMPLETED') THEN 1 ELSE 0 END) AS paid_purchases,
        SUM(CASE WHEN last_error IS NOT NULL AND last_error <> '' THEN 1 ELSE 0 END) AS error_count
      FROM {subscription_digital_payment_request}
     WHERE productid = :productid
", ['productid' => $id]);

$recentpurchases = $DB->get_records_sql("
    SELECT pr.*
      FROM {subscription_digital_payment_request} pr
     WHERE pr.productid = :productid
  ORDER BY pr.creation_date DESC, pr.id DESC
", ['productid' => $id], 0, 10);

$revenues = $DB->get_records_sql("
    SELECT
        currency,
        SUM(price) AS total
      FROM {subscription_digital_payment_request}
     WHERE productid = :productid
       AND status IN ('paid', 'completed', 'PAID', 'COMPLETED')
  GROUP BY currency
  ORDER BY currency ASC
", ['productid' => $id]);

$pageurl = new moodle_url(
    subscription_config::digital_product_edit_admin_page(),
    ['id' => $id]
);

$pagetitle = $isnew
    ? get_string('digital_product_edit_new_title', 'local_subscriptions')
    : get_string('digital_product_edit_edit_title', 'local_subscriptions');

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    $pagetitle,
    'local-subscriptions-commerce-digital-product-edit-page'
);

$langs = ['fr', 'en', 'ru'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();

    $now = time();

    $slug = required_param('slug', PARAM_ALPHANUMEXT);
    $name = required_param('name', PARAM_TEXT);
    $filename = $product->filename ?? '';
    $mobilefilename = $product->mobile_filename ?? '';
    $coverimage = $product->coverimage ?? '';
    $priceeur = required_param('price_eur', PARAM_FLOAT);
    $pricerub = required_param('price_rub', PARAM_FLOAT);
    $enabled = optional_param('enabled', 0, PARAM_BOOL);
    $sortorder = optional_param('sortorder', 0, PARAM_INT);

    $pdfdir = $CFG->dataroot . '/local_subscriptions/private_pdfs';
    $coverdir = $CFG->dirroot . '/local/subscriptions/pix/cover';

    // PDF classique.
    $uploaded = local_subscriptions_digital_move_uploaded_file(
        'pdf_file',
        $pdfdir,
        $slug . '_main',
        ['pdf']
    );

    if ($uploaded !== '') {
        $filename = $uploaded;
    }

    // PDF mobile.
    $uploaded = local_subscriptions_digital_move_uploaded_file(
        'mobile_pdf_file',
        $pdfdir,
        $slug . '_mobile',
        ['pdf']
    );

    if ($uploaded !== '') {
        $mobilefilename = $uploaded;
    }

    // Cover.
    $uploaded = local_subscriptions_digital_move_uploaded_file(
        'cover_file',
        $coverdir,
        $slug . '_cover',
        ['png', 'jpg', 'jpeg', 'webp']
    );

    if ($uploaded !== '') {
        $coverimage = $uploaded;
    }   

    $existing = $DB->get_record('subscription_digital_product', ['slug' => $slug], '*', IGNORE_MISSING);

    if ($existing && ($isnew || (int)$existing->id !== (int)$id)) {
        redirect(
            new moodle_url(subscription_config::digital_product_edit_admin_page(), ['id' => $id]),
            get_string('digital_product_edit_slug_exists', 'local_subscriptions'),
            5,
            \core\output\notification::NOTIFY_ERROR
        );
    }

    $record = (object)[
        'slug' => $slug,
        'name' => $name,
        'description' => '',
        'descriptionformat' => FORMAT_HTML,
        'filename' => $filename,
        'mobile_filename' => $mobilefilename ?: null,
        'coverimage' => $coverimage ?: null,
        'price_eur' => $priceeur,
        'price_rub' => $pricerub,
        'enabled' => $enabled ? 1 : 0,
        'sortorder' => $sortorder,
        'last_update' => $now,
    ];

    if ($isnew) {
        $record->creation_date = $now;
        $id = $DB->insert_record('subscription_digital_product', $record);
    } else {
        $record->id = $id;
        $DB->update_record('subscription_digital_product', $record);
    }

    foreach ($langs as $lang) {
        $title = optional_param("title_{$lang}", '', PARAM_RAW);
        $title = trim(html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $salesintro = optional_param("sales_intro_{$lang}", '', PARAM_RAW);
        $contentitems = optional_param("content_items_{$lang}", '', PARAM_RAW);
        $forwhoitems = optional_param("forwho_items_{$lang}", '', PARAM_RAW);

        $accessnote = optional_param("access_note_{$lang}", '', PARAM_RAW);
        $contenttitle = optional_param("content_title_{$lang}", '', PARAM_RAW);
        $forwhotitle = optional_param("forwho_title_{$lang}", '', PARAM_RAW);
        $buytitle = optional_param("buy_title_{$lang}", '', PARAM_RAW);


        $existingtranslation = $DB->get_record('subscription_digital_product_lang', [
            'productid' => $id,
            'lang' => $lang,
        ], '*', IGNORE_MISSING);

        // Si tout est vide, on ne crée pas de traduction.
        if (
            $title === ''
            && trim($salesintro) === ''
            && trim($contentitems) === ''
            && trim($forwhoitems) === ''
        ) {
            if ($existingtranslation) {
                $DB->delete_records('subscription_digital_product_lang', [
                    'id' => $existingtranslation->id,
                ]);
            }

            continue;
        }

        $translation = (object)[
            'productid' => $id,
            'lang' => $lang,
            'title' => $title !== '' ? $title : $name,
            'sales_intro' => $salesintro,
            'content_items' => $contentitems,
            'forwho_items' => $forwhoitems,
            'access_note' => $accessnote,
            'content_title' => $contenttitle,
            'forwho_title' => $forwhotitle,
            'buy_title' => $buytitle,
            'last_update' => $now,
        ];

        if ($existingtranslation) {
            $translation->id = $existingtranslation->id;
            $DB->update_record('subscription_digital_product_lang', $translation);
        } else {
            $translation->creation_date = $now;
            $DB->insert_record('subscription_digital_product_lang', $translation);
        }
    }

    redirect(
        new moodle_url(subscription_config::digital_products_admin_page()),
        get_string('digital_product_edit_saved', 'local_subscriptions'),
        2,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();

echo CrmWorkspaceRenderer::start(
    CrmNavigationKeys::COMMERCE,
    $context
);

echo CrmBreadcrumbRenderer::render([
    [
        'label' => get_string('crm_commerce_title', 'local_subscriptions'),
        'url' => new moodle_url(subscription_config::admin_commerce_page()),
    ],
    [
        'label' => get_string('digital_products_admin_title', 'local_subscriptions'),
        'url' => new moodle_url(subscription_config::digital_products_admin_page()),
    ],
    [
        'label' => $pagetitle,
        'url' => null,
    ],
]);

echo CrmBackLinkRenderer::render(
    new moodle_url(subscription_config::digital_products_admin_page()),
    get_string('digital_products_admin_title', 'local_subscriptions')
);

echo CrmPageHeader::render(
    $pagetitle,
    get_string(
        $isnew ? 'crm_digital_product_add_description' : 'crm_digital_product_edit_description',
        'local_subscriptions'
    ),
    HelpContext::DIGITAL_PURCHASES
);

echo CommerceSectionNavigationRenderer::render(
    CommerceSectionNavigationRenderer::DIGITAL_PRODUCTS
);

echo html_writer::start_div('row mb-4');

$revenuetext = '-';

if ($revenues) {
    $parts = [];

    foreach ($revenues as $revenue) {
        $parts[] = AdminFormatter::price($revenue->total ?? 0, $revenue->currency ?? '');
    }

    $revenuetext = implode('<br>', $parts);
}

$cards = [
    [
        get_string('digital_product_total_purchases', 'local_subscriptions'),
        (int)($stats->total_purchases ?? 0),
    ],
    [
        get_string('digital_product_paid_purchases', 'local_subscriptions'),
        (int)($stats->paid_purchases ?? 0),
    ],
    [
        get_string('digital_product_total_revenue', 'local_subscriptions'),
        $revenuetext,
    ],
    [
        get_string('digital_product_error_count', 'local_subscriptions'),
        (int)($stats->error_count ?? 0),
    ],
];

foreach ($cards as [$label, $value]) {
    echo html_writer::div(
        html_writer::div(
            html_writer::tag('div', $value, ['class' => 'crm-stat-number']) .
            html_writer::tag('div', $label, ['class' => 'text-muted']),
            'card card-body'
        ),
        'col-md-3 mb-3'
    );
}

echo html_writer::end_div();

echo html_writer::start_div('container-fluid my-4');

echo html_writer::start_tag('form', [
    'method' => 'post',
    'enctype' => 'multipart/form-data',
]);

echo html_writer::empty_tag('input', [
    'type' => 'hidden',
    'name' => 'sesskey',
    'value' => sesskey(),
]);

echo html_writer::start_div('row g-4');

echo html_writer::start_div('col-lg-4');

echo html_writer::start_div('card border-0 shadow-sm');
echo html_writer::start_div('card-body p-4');

echo html_writer::tag('h2', get_string('digital_product_edit_main_info', 'local_subscriptions'), [
    'class' => 'h4 mb-4',
]);

local_subscriptions_digital_text_input('slug', get_string('digital_products_slug', 'local_subscriptions'), $product->slug ?? '', true);
local_subscriptions_digital_text_input('name', get_string('digital_product_edit_internal_name', 'local_subscriptions'), $product->name ?? '', true);
local_subscriptions_digital_file_input(
    'pdf_file',
    get_string('digital_products_file_main', 'local_subscriptions'),
    $product->filename ?? '',
    '.pdf'
);

local_subscriptions_digital_file_input(
    'mobile_pdf_file',
    get_string('digital_products_file_mobile', 'local_subscriptions'),
    $product->mobile_filename ?? '',
    '.pdf'
);

local_subscriptions_digital_file_input(
    'cover_file',
    get_string('digital_products_cover', 'local_subscriptions'),
    $product->coverimage ?? '',
    '.png,.jpg,.jpeg,.webp'
);

local_subscriptions_digital_number_input('price_eur', get_string('digital_product_edit_price_eur', 'local_subscriptions'), $product->price_eur ?? '0.00', '0.01');
local_subscriptions_digital_number_input('price_rub', get_string('digital_product_edit_price_rub', 'local_subscriptions'), $product->price_rub ?? '0', '1');
local_subscriptions_digital_number_input('sortorder', get_string('digital_products_sortorder', 'local_subscriptions'), $product->sortorder ?? '0', '1');

echo html_writer::start_div('form-check mb-3');

echo html_writer::empty_tag('input', [
    'type' => 'checkbox',
    'name' => 'enabled',
    'id' => 'enabled',
    'value' => 1,
    'class' => 'form-check-input',
    !empty($product->enabled) || $isnew ? 'checked' : 'data-unchecked' => !empty($product->enabled) || $isnew ? 'checked' : '1',
]);

echo html_writer::tag('label', get_string('digital_products_enabled', 'local_subscriptions'), [
    'for' => 'enabled',
    'class' => 'form-check-label',
]);

echo html_writer::end_div();

echo html_writer::tag('p', get_string('digital_product_edit_files_hint', 'local_subscriptions'), [
    'class' => 'small text-muted',
]);

echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::end_div();

echo html_writer::start_div('col-lg-8');

echo html_writer::start_div('card border-0 shadow-sm');
echo html_writer::start_div('card-body p-4');

echo html_writer::tag('h2', get_string('digital_product_edit_translations', 'local_subscriptions'), [
    'class' => 'h4 mb-4',
]);

echo html_writer::start_div('accordion', ['id' => 'digitalProductTranslations']);

foreach ($langs as $index => $lang) {
    $tr = $translations[$lang] ?? null;

    $collapseid = 'translation-' . $lang;
    $headingid = 'heading-' . $lang;

    $flag = [
        'fr' => '🇫🇷',
        'en' => '🇬🇧',
        'ru' => '🇷🇺',
    ][$lang];

    echo html_writer::start_div('accordion-item');

    echo html_writer::tag('h2',
        html_writer::tag('button',
            $flag . ' ' . strtoupper($lang),
            [
                'class' => 'accordion-button' . ($index === 0 ? '' : ' collapsed'),
                'type' => 'button',
                'data-bs-toggle' => 'collapse',
                'data-bs-target' => '#' . $collapseid,
                'aria-expanded' => $index === 0 ? 'true' : 'false',
                'aria-controls' => $collapseid,
            ]
        ),
        [
            'class' => 'accordion-header',
            'id' => $headingid,
        ]
    );

    echo html_writer::start_div('accordion-collapse collapse' . ($index === 0 ? ' show' : ''), [
        'id' => $collapseid,
        'aria-labelledby' => $headingid,
        'data-bs-parent' => '#digitalProductTranslations',
    ]);

    echo html_writer::start_div('accordion-body');

    local_subscriptions_digital_text_input("title_{$lang}", get_string('digital_product_edit_title', 'local_subscriptions'), $tr->title ?? '', false);

    local_subscriptions_digital_textarea(
        "sales_intro_{$lang}",
        get_string('digital_sales_hero_intro', 'local_subscriptions'),
        $tr->sales_intro ?? '',
        3
    );

    local_subscriptions_digital_textarea(
        "access_note_{$lang}",
        get_string('digital_product_edit_access_note', 'local_subscriptions'),
        $tr->access_note ?? '',
        2
    );

    local_subscriptions_digital_textarea(
        "content_title_{$lang}",
        get_string('digital_product_edit_content_title', 'local_subscriptions'),
        $tr->content_title ?? '',
        2
    );    

    local_subscriptions_digital_textarea(
        "content_items_{$lang}",
        get_string('digital_sales_content_title', 'local_subscriptions'),
        $tr->content_items ?? '',
        8
    );

    local_subscriptions_digital_textarea(
        "forwho_title_{$lang}",
        get_string('digital_product_edit_forwho_title', 'local_subscriptions'),
        $tr->forwho_title ?? '',
        2
    );

    local_subscriptions_digital_textarea(
        "forwho_items_{$lang}",
        get_string('digital_sales_forwho_title', 'local_subscriptions'),
        $tr->forwho_items ?? '',
        6
    );

    local_subscriptions_digital_textarea(
        "buy_title_{$lang}",
        get_string('digital_product_edit_buy_title', 'local_subscriptions'),
        $tr->buy_title ?? '',
        2
    );

    echo html_writer::end_div();
    echo html_writer::end_div();

    echo html_writer::end_div();
}

echo html_writer::end_div();

echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::end_div();

echo html_writer::end_div();

echo html_writer::start_div('mt-4 d-flex gap-2');

echo html_writer::tag('button', get_string('savechanges'), [
    'type' => 'submit',
    'class' => 'btn btn-primary',
]);

echo html_writer::link(
    new moodle_url(subscription_config::digital_products_admin_page()),
    get_string('cancel'),
    ['class' => 'btn btn-outline-secondary']
);

echo html_writer::end_div();

echo html_writer::end_tag('form');

echo html_writer::end_div();

echo html_writer::tag('style', '
    .digital-file-dropzone:hover {
        border-color:#6f42c1 !important;
        background:#faf7ff !important;
        box-shadow:0 8px 24px rgba(111,66,193,0.08);
    }

    .digital-file-dropzone input[type="file"] {
        cursor:pointer;
    }
');

echo html_writer::tag('h4', get_string('digital_product_recent_purchases', 'local_subscriptions'), [
    'class' => 'mt-5 mb-3',
]);

if ($recentpurchases) {
    $table = new html_table();
    $table->head = [
        get_string('idnumber'),
        get_string('user'),
        get_string('email'),
        get_string('price', 'local_subscriptions'),
        get_string('status', 'local_subscriptions'),
        get_string('creation_date', 'local_subscriptions'),
    ];

    foreach ($recentpurchases as $purchase) {
        $buyername = trim(($purchase->firstname ?? '') . ' ' . ($purchase->lastname ?? ''));
        $buyerlabel = $buyername !== '' ? s($buyername) : s($purchase->email ?? '-');

        $crmuserid = !empty($purchase->userid) ? (int)$purchase->userid : 0;

        if ($crmuserid <= 0 && !empty($purchase->email)) {
            $crmuserid = (int)$DB->get_field_sql("
                SELECT id
                  FROM {user}
                 WHERE deleted = 0
                   AND " . $DB->sql_compare_text('email') . " = " . $DB->sql_compare_text(':email') . "
              ORDER BY id DESC
            ", ['email' => $purchase->email], IGNORE_MISSING);
        }

        $table->data[] = [
            html_writer::link(
                new moodle_url(subscription_config::digital_purchase_view_admin_page(), ['id' => $purchase->id]),
                '#' . $purchase->id,
                ['class' => 'crm-entity-link']
            ),
            $crmuserid > 0 ? AdminEntityLinks::user($crmuserid, $buyerlabel) : $buyerlabel,
            s($purchase->email ?? '-'),
            AdminFormatter::price($purchase->price ?? 0, $purchase->currency ?? ''),
            DigitalPresenter::render_status_badge($purchase->status ?? ''),
            AdminFormatter::datetime((int)($purchase->creation_date ?? 0)),
        ];
    }

    echo html_writer::table($table);
} else {
    echo $OUTPUT->notification(get_string('digital_product_no_recent_purchases', 'local_subscriptions'), 'info');
}

echo CrmWorkspaceRenderer::end();

echo $OUTPUT->footer();


function local_subscriptions_digital_text_input(
    string $name,
    string $label,
    string $value = '',
    bool $required = false
): void {
    echo html_writer::start_div('mb-3');

    echo html_writer::tag('label', $label, [
        'for' => $name,
        'class' => 'form-label fw-semibold',
    ]);

    echo html_writer::empty_tag('input', [
        'type' => 'text',
        'name' => $name,
        'id' => $name,
        'value' => s($value),
        'class' => 'form-control',
        $required ? 'required' : 'data-optional' => $required ? 'required' : '1',
    ]);

    echo html_writer::end_div();
}


function local_subscriptions_digital_number_input(
    string $name,
    string $label,
    $value = '',
    string $step = '1'
): void {
    echo html_writer::start_div('mb-3');

    echo html_writer::tag('label', $label, [
        'for' => $name,
        'class' => 'form-label fw-semibold',
    ]);

    echo html_writer::empty_tag('input', [
        'type' => 'number',
        'name' => $name,
        'id' => $name,
        'value' => s((string)$value),
        'step' => $step,
        'class' => 'form-control',
        'required' => 'required',
    ]);

    echo html_writer::end_div();
}


function local_subscriptions_digital_textarea(
    string $name,
    string $label,
    string $value = '',
    int $rows = 4
): void {
    echo html_writer::start_div('mb-3');

    echo html_writer::tag('label', $label, [
        'for' => $name,
        'class' => 'form-label fw-semibold',
    ]);

    echo html_writer::tag('textarea', s($value), [
        'name' => $name,
        'id' => $name,
        'class' => 'form-control',
        'rows' => $rows,
    ]);

    echo html_writer::end_div();
}

function local_subscriptions_digital_file_input(
    string $name,
    string $label,
    string $currentfile = '',
    string $accept = ''
): void {
    global $CFG;

    echo html_writer::start_div('mb-4');

    echo html_writer::tag('label', $label, [
        'for' => $name,
        'class' => 'form-label fw-semibold',
    ]);

    echo html_writer::start_tag('label', [
        'for' => $name,
        'class' => 'digital-file-dropzone',
        'style' => '
            display:block;
            cursor:pointer;
            border:2px dashed #d0d5dd;
            border-radius:16px;
            padding:18px;
            background:#fff;
            transition:all .15s ease;
        ',
    ]);

    if ($currentfile !== '') {
        echo html_writer::tag('div',
            get_string('digital_product_edit_current_file', 'local_subscriptions') .
            ': <code>' . s($currentfile) . '</code>',
            ['class' => 'small text-muted mb-2']
        );

        $isimage = preg_match('~\.(png|jpg|jpeg|webp)$~i', $currentfile);

        if ($isimage) {
            $path = $CFG->dirroot . '/local/subscriptions/pix/cover/' . $currentfile;

            if (file_exists($path)) {
                echo html_writer::empty_tag('img', [
                    'src' => $CFG->wwwroot . '/local/subscriptions/pix/cover/' . rawurlencode($currentfile),
                    'alt' => '',
                    'style' => '
                        max-width:180px;
                        margin:10px 0;
                        border-radius:12px;
                        box-shadow:0 6px 20px rgba(0,0,0,0.12);
                        display:block;
                    ',
                ]);
            }
        }
    } else {
        echo html_writer::tag('div',
            get_string('digital_product_edit_no_file', 'local_subscriptions'),
            ['class' => 'small text-muted mb-2']
        );
    }

    echo html_writer::tag('div',
        get_string('digital_product_edit_click_to_upload', 'local_subscriptions'),
        ['class' => 'fw-semibold']
    );

    echo html_writer::empty_tag('input', [
        'type' => 'file',
        'name' => $name,
        'id' => $name,
        'accept' => $accept,
        'class' => 'form-control mt-3',
        'style' => 'cursor:pointer;',
    ]);

    echo html_writer::end_tag('label');

    echo html_writer::end_div();
}

function local_subscriptions_digital_uploaded_extension(string $filename, array $allowed): string {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed, true)) {
        throw new moodle_exception('invalidfiletype', 'error');
    }

    return $ext;
}

function local_subscriptions_digital_move_uploaded_file(
    string $inputname,
    string $targetdir,
    string $targetbasename,
    array $allowedextensions
): string {
    if (empty($_FILES[$inputname]['name'])) {
        return '';
    }

    if (!empty($_FILES[$inputname]['error'])) {
        throw new moodle_exception('uploaderror');
    }

    $originalname = clean_param($_FILES[$inputname]['name'], PARAM_FILE);
    $extension = local_subscriptions_digital_uploaded_extension($originalname, $allowedextensions);

    if (!is_dir($targetdir)) {
        make_writable_directory($targetdir);
    }

    $filename = $targetbasename . '.' . $extension;
    $destination = rtrim($targetdir, '/') . '/' . $filename;

    if (!@move_uploaded_file($_FILES[$inputname]['tmp_name'], $destination)) {
        throw new moodle_exception('uploaderror');
    }

    return $filename;
}