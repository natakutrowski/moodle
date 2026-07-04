<?php

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\subscription_config;
use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\admin\AdminNavigation;

$context = AdminSecurity::require(Capabilities::MANAGE_DIGITAL);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url(subscription_config::digital_products_admin_page()));
$PAGE->set_title(get_string('digital_products_admin_title', 'local_subscriptions'));
$PAGE->set_heading(get_string('digital_products_admin_title', 'local_subscriptions'));

$sql = "
    SELECT
        p.id,
        p.slug,
        p.name,
        p.filename,
        p.mobile_filename,
        p.coverimage,
        p.price_eur,
        p.price_rub,
        p.enabled,
        p.sortorder,
        p.creation_date,
        p.last_update,

        tfr.title AS title_fr,
        ten.title AS title_en,
        tru.title AS title_ru,

        COUNT(pr.id) AS purchasescount
    FROM {subscription_digital_product} p

    LEFT JOIN {subscription_digital_product_lang} tfr
        ON tfr.productid = p.id AND tfr.lang = 'fr'

    LEFT JOIN {subscription_digital_product_lang} ten
        ON ten.productid = p.id AND ten.lang = 'en'

    LEFT JOIN {subscription_digital_product_lang} tru
        ON tru.productid = p.id AND tru.lang = 'ru'

    LEFT JOIN {subscription_digital_payment_request} pr
        ON pr.productid = p.id

    GROUP BY
        p.id,
        p.slug,
        p.name,
        p.filename,
        p.mobile_filename,
        p.coverimage,
        p.price_eur,
        p.price_rub,
        p.enabled,
        p.sortorder,
        p.creation_date,
        p.last_update,
        tfr.title,
        ten.title,
        tru.title

    ORDER BY p.sortorder ASC, p.id ASC
";

$products = $DB->get_records_sql($sql);

echo $OUTPUT->header();
echo AdminNavigation::back_button();

echo html_writer::start_div('mb-4 d-flex gap-2 flex-wrap');

echo html_writer::link(
    new moodle_url(subscription_config::digital_product_edit_admin_page()),
    get_string('digital_products_add', 'local_subscriptions'),
    ['class' => 'btn btn-primary']
);

echo html_writer::link(
    new moodle_url(subscription_config::digital_purchases_admin_page()),
    get_string('digital_products_view_purchases', 'local_subscriptions'),
    ['class' => 'btn btn-outline-secondary']
);

echo html_writer::link(
    new moodle_url(subscription_config::boutique_page()),
    get_string('digital_products_view_catalog', 'local_subscriptions'),
    ['class' => 'btn btn-outline-secondary', 'target' => '_blank']
);

echo html_writer::end_div();

echo html_writer::tag(
    'p',
    get_string('digital_products_count', 'local_subscriptions', count($products)),
    ['class' => 'text-muted']
);

$table = new html_table();

$table->head = [
    'ID',
    get_string('digital_products_cover', 'local_subscriptions'),
    get_string('digital_products_slug', 'local_subscriptions'),
    get_string('digital_products_titles', 'local_subscriptions'),
    get_string('digital_products_prices', 'local_subscriptions'),
    get_string('digital_products_files', 'local_subscriptions'),
    get_string('digital_products_status', 'local_subscriptions'),
    get_string('digital_products_purchases', 'local_subscriptions'),
    get_string('digital_products_sortorder', 'local_subscriptions'),
    get_string('digital_products_actions', 'local_subscriptions'),
];

$table->attributes['class'] = 'generaltable table table-striped';
$table->attributes['style'] = 'table-layout:fixed;width:100%;';

$table->colclasses = [
    'col-id',
    'col-cover',
    'col-slug',
    'col-titles',
    'col-prices',
    'col-files',
    'col-status',
    'col-purchases',
    'col-sortorder',
    'col-actions',
];

foreach ($products as $p) {
    $cover = '—';

    if (!empty($p->coverimage)) {
        $coverpath = $CFG->dirroot . '/local/subscriptions/pix/cover/' . $p->coverimage;

        if (file_exists($coverpath)) {
            $cover = html_writer::empty_tag('img', [
                'src' => $CFG->wwwroot . '/local/subscriptions/pix/cover/' . rawurlencode($p->coverimage),
                'alt' => '',
                'style' => 'width:70px;height:auto;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,0.12);',
            ]);
        } else {
            $cover = html_writer::tag('span', get_string('digital_products_cover_missing', 'local_subscriptions'), [
                'class' => 'badge bg-warning text-dark',
            ]);
        }
    }

    $titles = [];
    $titles[] = '🇫🇷 ' . format_text($p->title_fr ?? '—', FORMAT_HTML, [
        'trusted' => true,
        'noclean' => true,
        'para' => false,
    ]);

    $titles[] = '🇬🇧 ' . format_text($p->title_en ?? '—', FORMAT_HTML, [
        'trusted' => true,
        'noclean' => true,
        'para' => false,
    ]);

    $titles[] = '🇷🇺 ' . format_text($p->title_ru ?? '—', FORMAT_HTML, [
        'trusted' => true,
        'noclean' => true,
        'para' => false,
    ]);

    $prices = [];
    $prices[] = number_format((float)$p->price_eur, 2, ',', ' ') . ' €';
    $prices[] = number_format((float)$p->price_rub, 0, ',', ' ') . ' ₽';

    $files = [];

    $files[] = html_writer::tag('strong', get_string('digital_products_file_main', 'local_subscriptions') . ': ') .
        local_subscriptions_render_file_status($p->filename ?? '', 'pdf') .
        local_subscriptions_admin_file_preview_link(
            (int)$p->id,
            $p->filename ?? '',
            'main',
            get_string('preview')
        );

    $files[] = html_writer::tag('strong', get_string('digital_products_file_mobile', 'local_subscriptions') . ': ') .
        local_subscriptions_render_file_status($p->mobile_filename ?? '', 'pdf') .
        local_subscriptions_admin_file_preview_link(
            (int)$p->id,
            $p->mobile_filename ?? '',
            'mobile',
            get_string('preview')
        );

    $files[] = html_writer::tag('strong', get_string('digital_products_cover', 'local_subscriptions') . ': ') .
        local_subscriptions_render_file_status($p->coverimage ?? '', 'cover') .
        local_subscriptions_admin_file_preview_link(
            (int)$p->id,
            $p->coverimage ?? '',
            'cover',
            get_string('preview')
        );

    $status = !empty($p->enabled)
        ? html_writer::tag('span', get_string('digital_products_enabled', 'local_subscriptions'), ['class' => 'badge bg-success'])
        : html_writer::tag('span', get_string('digital_products_disabled', 'local_subscriptions'), ['class' => 'badge bg-secondary']);

    $actions = [];

    $actions[] = html_writer::link(
        new moodle_url(subscription_config::digital_product_edit_admin_page(), ['id' => $p->id]),
        get_string('edit'),
        ['class' => 'btn btn-sm btn-outline-primary']
    );

    $actions[] = html_writer::link(
        new moodle_url('/digital/' . $p->slug),
        get_string('digital_products_open_public', 'local_subscriptions'),
        ['class' => 'btn btn-sm btn-outline-secondary', 'target' => '_blank']
    );

    $actions[] = html_writer::link(
        new moodle_url(subscription_config::digital_product_toggle_admin_page(), [
            'id' => $p->id,
            'sesskey' => sesskey(),
        ]),
        !empty($p->enabled)
            ? get_string('digital_products_disable', 'local_subscriptions')
            : get_string('digital_products_enable', 'local_subscriptions'),
        ['class' => 'btn btn-sm btn-outline-warning']
    );    

    $actions[] = html_writer::link(
        new moodle_url(subscription_config::digital_product_duplicate_admin_page(), [
            'id' => $p->id,
            'sesskey' => sesskey(),
        ]),
        get_string('digital_products_duplicate', 'local_subscriptions'),
        ['class' => 'btn btn-sm btn-outline-info']
    );    

    if ((int)$p->purchasescount === 0) {
        $actions[] = html_writer::link(
            new moodle_url(subscription_config::digital_product_delete_admin_page(), [
                'id' => $p->id,
                'sesskey' => sesskey(),
            ]),
            get_string('delete'),
            [
                'class' => 'btn btn-sm btn-outline-danger',
                'onclick' => "return confirm('" . addslashes(get_string('digital_products_delete_confirm', 'local_subscriptions')) . "');",
            ]
        );
    }

    $table->data[] = [
        (int)$p->id,
        $cover,
        html_writer::tag('code', s($p->slug)),
        implode(html_writer::empty_tag('br'), $titles),
        implode(html_writer::empty_tag('br'), $prices),
        implode(html_writer::empty_tag('br'), $files),
        $status,
        (int)$p->purchasescount,
        (int)$p->sortorder,
        html_writer::div(implode(' ', $actions), 'd-flex gap-1 flex-wrap'),
    ];
}

echo html_writer::tag('style', '
    .col-id { width:55px; }
    .col-cover { width:95px; text-align:center; }
    .col-slug { width:170px; }
    .col-titles { width:260px; }
    .col-prices { width:100px; }
    .col-files { width:250px; word-break:break-word; }
    .col-status { width:90px; }
    .col-purchases { width:90px; text-align:center; }
    .col-sortorder { width:80px; text-align:center; }
    .col-actions { width:190px; }

    .generaltable td,
    .generaltable th {
        vertical-align: middle;
    }
');

echo html_writer::div(
    html_writer::table($table),
    'table-responsive'
);

echo $OUTPUT->footer();

function local_subscriptions_render_file_status(
    string $filename,
    string $type
): string {
    global $CFG;

    if ($filename === '') {
        return html_writer::tag('span', '—', ['class' => 'text-muted']);
    }

    if ($type === 'pdf') {
        $path = $CFG->dataroot . '/local_subscriptions/private_pdfs/' . $filename;
    } else {
        $path = $CFG->dirroot . '/local/subscriptions/pix/cover/' . $filename;
    }

    if (!file_exists($path)) {
        return html_writer::tag('span', '❌ ' . s($filename), [
            'class' => 'text-danger',
        ]);
    }

    $size = display_size(filesize($path));

    return html_writer::tag('span', '✅ ' . s($filename) . ' <span class="text-muted">(' . s($size) . ')</span>', [
        'class' => 'text-success',
    ]);
}

function local_subscriptions_admin_file_preview_link(
    int $productid,
    string $filename,
    string $type,
    string $label
): string {
    global $CFG;

    if ($filename === '') {
        return '';
    }

    if ($type === 'cover') {
        $path = $CFG->dirroot . '/local/subscriptions/pix/cover/' . $filename;
    } else {
        $path = $CFG->dataroot . '/local_subscriptions/private_pdfs/' . $filename;
    }

    if (!is_readable($path)) {
        return '';
    }

    return html_writer::link(
        new moodle_url(subscription_config::digital_product_file_preview_admin_page(), [
            'id' => $productid,
            'type' => $type,
        ]),
        $label,
        [
            'class' => 'btn btn-xs btn-outline-secondary ms-1',
            'target' => '_blank',
        ]
    );
}