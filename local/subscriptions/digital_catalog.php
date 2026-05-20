<?php
require_once(__DIR__ . '/../../config.php');

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\digital\product_manager;
use local_subscriptions\url\UrlFactory;

\local_subscriptions\subscription_config::guard_public_access();

$missingproduct = optional_param('missingproduct', 0, PARAM_BOOL);

$PAGE->set_context(context_system::instance());
$PAGE->set_url(UrlFactory::digital_catalog([
    'missingproduct' => $missingproduct,
]));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('digital_catalog_title', 'local_subscriptions'));
$PAGE->set_heading(get_string('digital_catalog_title', 'local_subscriptions'));

$products = product_manager::get_available_products(current_language());

echo $OUTPUT->header();

echo html_writer::start_div('container my-5');

if ($missingproduct) {
    echo $OUTPUT->notification(
        get_string('digital_product_not_found_catalog_notice', 'local_subscriptions'),
        'warning'
    );
}

echo html_writer::tag('h1', get_string('digital_catalog_title', 'local_subscriptions'), [
    'class' => 'mb-3',
]);

echo html_writer::tag('p', get_string('digital_catalog_intro', 'local_subscriptions'), [
    'class' => 'lead mb-5',
]);

if (empty($products)) {
    echo $OUTPUT->notification(
        get_string('digital_catalog_empty', 'local_subscriptions'),
        'info'
    );

    echo html_writer::end_div();
    echo $OUTPUT->footer();
    exit;
}

echo html_writer::start_div('row g-4');

foreach ($products as $product) {
    echo html_writer::start_div('col-md-6 col-lg-4');

    echo html_writer::start_div('card h-100 border-0 shadow-sm overflow-hidden');

    if (!empty($product->coverimage)) {
        $coverpath = $CFG->dirroot . '/local/subscriptions/pix/cover/' . $product->coverimage;

        if (file_exists($coverpath)) {
            echo html_writer::start_div('text-center bg-light p-4');

            echo html_writer::empty_tag('img', [
                'src' => $CFG->wwwroot . '/local/subscriptions/pix/cover/' . rawurlencode($product->coverimage),
                'alt' => '',
                'style' => 'width:100%;max-width:220px;border-radius:14px;box-shadow:0 10px 30px rgba(0,0,0,0.12);',
            ]);

            echo html_writer::end_div();
        }
    }

    echo html_writer::start_div('card-body p-4 d-flex flex-column');

    echo html_writer::tag('div', get_string('digital_pdf_badge', 'local_subscriptions'), [
        'class' => 'badge bg-primary align-self-start mb-3',
    ]);

    echo html_writer::tag('h2', format_string($product->localized_title ?? $product->name), [
        'class' => 'h4 mb-3',
    ]);

    if (!empty($product->sales_intro)) {
        echo html_writer::tag('p', format_text($product->sales_intro, FORMAT_PLAIN), [
            'class' => 'text-muted mb-4',
        ]);
    }

    echo html_writer::start_div('mt-auto');

    echo html_writer::start_div('d-flex gap-2 mb-3 flex-wrap');

    echo html_writer::tag('span', number_format((float)$product->price_eur, 2, ',', ' ') . ' €', [
        'class' => 'badge bg-light text-dark border fs-6',
    ]);

    echo html_writer::tag('span', number_format((float)$product->price_rub, 0, ',', ' ') . ' ₽', [
        'class' => 'badge bg-light text-dark border fs-6',
    ]);

    echo html_writer::end_div();

    echo html_writer::link(
        UrlFactory::digital_product($product->slug),
        get_string('digital_catalog_view_product', 'local_subscriptions'),
        ['class' => 'btn btn-primary w-100']
    );

    echo html_writer::end_div();

    echo html_writer::end_div();
    echo html_writer::end_div();

    echo html_writer::end_div();
}

echo html_writer::end_div();
echo html_writer::end_div();

echo $OUTPUT->footer();