<?php
declare(strict_types=1);

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\catalog\currency\CommerceCurrencyRegistry;
use local_subscriptions\commerce\catalog\domain\CommerceProductPrice;
use local_subscriptions\commerce\catalog\presentation\CommerceCatalogProductNameResolver;
use local_subscriptions\commerce\catalog\service\CommerceCatalogFactory;
use local_subscriptions\commerce\currency\CommerceCatalogFxBulkSuggestionService;
use local_subscriptions\commerce\currency\CommerceCurrencyAmount;
use local_subscriptions\commerce\currency\CommerceFxPriceSuggestionService;
use local_subscriptions\commerce\currency\CommerceFxRateBook;
use local_subscriptions\commerce\domain\value\CommerceMoney;
use local_subscriptions\currency\CommerceCurrencyLabelFormatter;
use local_subscriptions\currency\Currency;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

$context = AdminSecurity::require(Capabilities::MANAGE_CONFIGURATION);

$pageurl = new moodle_url(
    '/local/subscriptions/admin/commerce/configuration/currency_prices.php'
);
$pagetitle = get_string('commerce_fx_bulk_title', 'local_subscriptions');

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    $pagetitle,
    'local-subscriptions-commerce-fx-bulk-page'
);

$factory = new CommerceCatalogFactory($DB);
$manager = $factory->product_manager();
$registry = new CommerceCurrencyRegistry();
$ratebook = new CommerceFxRateBook($registry);
$bulk = new CommerceCatalogFxBulkSuggestionService(
    $manager,
    $ratebook,
    new CommerceFxPriceSuggestionService($ratebook)
);

$previewkey = 'commerce_fx_bulk_price_preview';
$preview = $SESSION->{$previewkey} ?? null;
$action = optional_param('action', '', PARAM_ALPHANUMEXT);

if (data_submitted() && confirm_sesskey()) {
    if ($action === 'preview') {
        $targetcurrency = $registry->require_enabled(
            strtoupper(required_param('targetcurrency', PARAM_ALPHA))
        );
        $rounding = optional_param('rounding', 'none', PARAM_ALPHANUMEXT);
        if (!in_array($rounding, ['none', 'whole', 'ending90'], true)) {
            $rounding = 'none';
        }

        try {
            $rows = $bulk->preview($targetcurrency, $rounding);
            $SESSION->{$previewkey} = [
                'targetcurrency' => $targetcurrency,
                'rounding' => $rounding,
                'rows' => $rows,
                'createdat' => time(),
            ];
            redirect($pageurl);
        } catch (\Throwable $e) {
            unset($SESSION->{$previewkey});
            redirect(
                $pageurl,
                get_string(
                    'commerce_fx_bulk_preview_failed',
                    'local_subscriptions',
                    $e->getMessage()
                ),
                null,
                \core\output\notification::NOTIFY_ERROR
            );
        }
    }

    if ($action === 'discard') {
        unset($SESSION->{$previewkey});
        redirect($pageurl);
    }

    if ($action === 'apply') {
        $preview = $SESSION->{$previewkey} ?? null;
        if (!is_array($preview) || empty($preview['rows'])) {
            redirect(
                $pageurl,
                get_string(
                    'commerce_fx_bulk_preview_missing',
                    'local_subscriptions'
                ),
                null,
                \core\output\notification::NOTIFY_ERROR
            );
        }

        $selected = optional_param_array(
            'selected',
            [],
            PARAM_ALPHANUMEXT
        );
        $amounts = optional_param_array(
            'amounts',
            [],
            PARAM_RAW_TRIMMED
        );
        $activate = optional_param('activate', 0, PARAM_BOOL) === 1;
        $created = 0;
        $skipped = 0;

        foreach ((array)$preview['rows'] as $row) {
            $sku = (string)($row['sku'] ?? '');
            $currency = strtoupper(
                (string)($row['targetcurrency'] ?? '')
            );

            $rowkey = 'p' . sha1($sku);

            if ($sku === '' || !in_array($rowkey, $selected, true)) {
                continue;
            }

            // Stale preview protection: never replace a price created since
            // the preview was generated.
            if ($manager->price_currency_exists($sku, $currency)) {
                $skipped++;
                continue;
            }

            $raw = trim((string)($amounts[$rowkey] ?? ''));
            if ($raw === '') {
                $skipped++;
                continue;
            }

            try {
                $money = CommerceCurrencyAmount::from_major_input(
                    $raw,
                    $currency
                );
            } catch (\coding_exception) {
                throw new moodle_exception(
                    'commerce_fx_bulk_invalid_amount',
                    'local_subscriptions',
                    '',
                    $sku
                );
            }

            if ($money->get_amount_minor() <= 0) {
                throw new moodle_exception(
                    'commerce_fx_bulk_invalid_amount',
                    'local_subscriptions',
                    '',
                    $sku
                );
            }

            $manager->save_price(new CommerceProductPrice(
                $sku,
                CommerceMoney::from_minor(
                    $money->get_amount_minor(),
                    $currency
                ),
                $activate,
                null,
                null,
                [
                    'source' => 'fx_bulk_assistant',
                    'fxbase' => (string)($row['sourcecurrency'] ?? ''),
                    'fxrate' => (string)($row['rate'] ?? ''),
                ]
            ));
            $created++;
        }

        if ($created === 0) {
            redirect(
                $pageurl,
                get_string(
                    'commerce_fx_bulk_apply_none',
                    'local_subscriptions'
                ),
                null,
                \core\output\notification::NOTIFY_WARNING
            );
        }

        unset($SESSION->{$previewkey});
        redirect(
            $pageurl,
            get_string(
                'commerce_fx_bulk_apply_success',
                'local_subscriptions',
                (object)[
                    'created' => $created,
                    'skipped' => $skipped,
                ]
            ),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    }
}

$preview = $SESSION->{$previewkey} ?? null;
$basecurrency = $ratebook->base_currency();
$rates = $ratebook->rates();
$targetoptions = [];

foreach ($registry->enabled() as $currency) {
    if ($currency !== $basecurrency && isset($rates[$currency])) {
        $targetoptions[$currency] =
            CommerceCurrencyLabelFormatter::format($currency);
    }
}

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(
    CrmNavigationKeys::COMMERCE,
    $context
);
echo CommerceSectionNavigationRenderer::render(
    CommerceSectionNavigationRenderer::CONFIGURATION,
    $context
);

echo html_writer::div(
    html_writer::link(
        new moodle_url(
            '/local/subscriptions/admin/commerce/configuration/currencies.php'
        ),
        '← ' . get_string('commerce_fx_bulk_back', 'local_subscriptions'),
        ['class' => 'btn btn-outline-secondary btn-sm']
    ),
    'mb-3'
);

echo html_writer::tag('h1', $pagetitle, ['class' => 'h3']);
echo html_writer::tag(
    'p',
    get_string('commerce_fx_bulk_help', 'local_subscriptions'),
    ['class' => 'text-muted mb-4']
);

echo html_writer::start_div('card mb-4');
echo html_writer::start_div('card-body');

echo html_writer::div(
    get_string(
        'commerce_fx_bulk_base_summary',
        'local_subscriptions',
        CommerceCurrencyLabelFormatter::format($basecurrency)
    ),
    'alert alert-info py-2'
);

if ($targetoptions === []) {
    echo html_writer::div(
        get_string('commerce_fx_bulk_no_targets', 'local_subscriptions'),
        'alert alert-warning mb-0'
    );
} else {
    echo html_writer::start_tag('form', [
        'method' => 'post',
        'action' => $pageurl->out(false),
        'class' => 'row g-3 align-items-end',
    ]);
    echo html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'sesskey',
        'value' => sesskey(),
    ]);
    echo html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'action',
        'value' => 'preview',
    ]);

    $selectedtarget = is_array($preview)
        ? (string)($preview['targetcurrency'] ?? '')
        : '';

    echo html_writer::div(
        html_writer::tag(
            'label',
            get_string('commerce_fx_bulk_target', 'local_subscriptions'),
            ['for' => 'fx-bulk-target', 'class' => 'form-label']
        )
        . html_writer::select(
            $targetoptions,
            'targetcurrency',
            $selectedtarget,
            ['' => get_string('choose')],
            [
                'id' => 'fx-bulk-target',
                'class' => 'form-select',
            ]
        ),
        'col-md-5'
    );

    $roundingoptions = [
        'none' => get_string(
            'commerce_fx_rounding_none',
            'local_subscriptions'
        ),
        'whole' => get_string(
            'commerce_fx_rounding_whole',
            'local_subscriptions'
        ),
        'ending90' => get_string(
            'commerce_fx_rounding_ending90',
            'local_subscriptions'
        ),
    ];
    $rounding = is_array($preview)
        ? (string)($preview['rounding'] ?? 'none')
        : 'none';

    echo html_writer::div(
        html_writer::tag(
            'label',
            get_string(
                'commerce_product_fx_rounding',
                'local_subscriptions'
            ),
            ['for' => 'fx-bulk-rounding', 'class' => 'form-label']
        )
        . html_writer::select(
            $roundingoptions,
            'rounding',
            $rounding,
            false,
            [
                'id' => 'fx-bulk-rounding',
                'class' => 'form-select',
            ]
        ),
        'col-md-4'
    );

    echo html_writer::div(
        html_writer::tag(
            'button',
            get_string(
                'commerce_fx_bulk_generate',
                'local_subscriptions'
            ),
            ['type' => 'submit', 'class' => 'btn btn-primary']
        ),
        'col-md-auto'
    );

    echo html_writer::end_tag('form');
}

echo html_writer::end_div();
echo html_writer::end_div();

if (is_array($preview)) {
    $rows = (array)($preview['rows'] ?? []);
    $targetcurrency = (string)($preview['targetcurrency'] ?? '');

    echo html_writer::start_div('card mb-4 border-primary');
    echo html_writer::start_div('card-body');

    echo html_writer::start_div(
        'd-flex justify-content-between align-items-center mb-2'
    );
    echo html_writer::tag(
        'h2',
        get_string(
            'commerce_fx_bulk_preview_title',
            'local_subscriptions',
            CommerceCurrencyLabelFormatter::format($targetcurrency)
        ),
        ['class' => 'h5 mb-0']
    );
    echo html_writer::div(
        html_writer::tag(
            'button',
            get_string(
                'commerce_select_all',
                'local_subscriptions'
            ),
            [
                'type' => 'button',
                'class' => 'btn btn-link btn-sm p-0 me-3',
                'data-fx-bulk-select-all' => '1',
            ]
        )
        . html_writer::tag(
            'button',
            get_string(
                'commerce_deselect_all',
                'local_subscriptions'
            ),
            [
                'type' => 'button',
                'class' => 'btn btn-link btn-sm p-0',
                'data-fx-bulk-deselect-all' => '1',
            ]
        )
    );
    echo html_writer::end_div();

    if ($rows === []) {
        echo html_writer::div(
            get_string(
                'commerce_fx_bulk_empty',
                'local_subscriptions'
            ),
            'alert alert-info mb-0'
        );
    } else {
        echo html_writer::start_tag('form', [
            'method' => 'post',
            'action' => $pageurl->out(false),
        ]);
        echo html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'sesskey',
            'value' => sesskey(),
        ]);
        echo html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'action',
            'value' => 'apply',
        ]);

        $table = new html_table();
        $table->attributes['class'] = 'table table-sm align-middle';
        $table->head = [
            get_string(
                'commerce_fx_apply_column',
                'local_subscriptions'
            ),
            get_string(
                'commerce_product_name',
                'local_subscriptions'
            ),
            'SKU',
            get_string(
                'commerce_fx_bulk_reference_price',
                'local_subscriptions'
            ),
            get_string(
                'commerce_product_fx_raw_suggestion',
                'local_subscriptions'
            ),
            get_string(
                'commerce_product_fx_price_to_create',
                'local_subscriptions'
            ),
        ];

        foreach ($rows as $row) {
            $sku = (string)$row['sku'];
            $rowkey = 'p' . sha1($sku);
            $target = (string)$row['targetcurrency'];
            $sourcecurrency = (string)$row['sourcecurrency'];

            $productid = (int)($row['productid'] ?? 0);
            if ($productid <= 0) {
                try {
                    $productid = (int)$manager
                        ->get_editor_data($sku)
                        ->get_product()
                        ->get_id();
                } catch (\Throwable) {
                    $productid = 0;
                }
            }

            $displayname =
                CommerceCatalogProductNameResolver::resolve_native_id(
                    $DB,
                    $productid,
                    (string)$row['name']
                );

            $table->data[] = [
                html_writer::checkbox(
                    'selected[]',
                    $rowkey,
                    true,
                    '',
                    [
                        'class' => 'form-check-input',
                        'data-fx-bulk-row' => '1',
                    ]
                ),
                format_string($displayname),
                s($sku),
                s(
                    CommerceCurrencyAmount::major_input_from_minor(
                        (int)$row['sourceamountminor'],
                        $sourcecurrency
                    )
                    . ' '
                    . $sourcecurrency
                ),
                s(
                    number_format(
                        (float)$row['rawmajor'],
                        max(
                            2,
                            Currency::minor_unit_exponent($target)
                        ),
                        '.',
                        ''
                    )
                ),
                html_writer::empty_tag('input', [
                    'type' => 'text',
                    'name' => 'amounts[' . $rowkey . ']',
                    'value' =>
                        CommerceCurrencyAmount::major_input_from_minor(
                            (int)$row['suggestedminor'],
                            $target
                        ),
                    'class' => 'form-control form-control-sm',
                    'inputmode' => 'decimal',
                ]),
            ];
        }

        echo html_writer::table($table);

        $activateid = 'fx-bulk-activate';
        echo html_writer::div(
            html_writer::checkbox(
                'activate',
                '1',
                false,
                '',
                [
                    'id' => $activateid,
                    'class' => 'form-check-input',
                ]
            )
            . html_writer::tag(
                'label',
                get_string(
                    'commerce_fx_bulk_activate',
                    'local_subscriptions'
                ),
                [
                    'for' => $activateid,
                    'class' => 'form-check-label ms-2',
                ]
            ),
            'form-check mt-3 mb-3'
        );

        echo html_writer::start_div('d-flex gap-2');
        echo html_writer::tag(
            'button',
            get_string(
                'commerce_fx_bulk_create',
                'local_subscriptions'
            ),
            ['type' => 'submit', 'class' => 'btn btn-success']
        );
        echo html_writer::end_tag('form');

        echo html_writer::start_tag('form', [
            'method' => 'post',
            'action' => $pageurl->out(false),
            'class' => 'm-0',
        ]);
        echo html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'sesskey',
            'value' => sesskey(),
        ]);
        echo html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'action',
            'value' => 'discard',
        ]);
        echo html_writer::tag(
            'button',
            get_string(
                'commerce_fx_discard_refresh',
                'local_subscriptions'
            ),
            [
                'type' => 'submit',
                'class' => 'btn btn-outline-secondary',
            ]
        );
        echo html_writer::end_tag('form');
        echo html_writer::end_div();
    }

    echo html_writer::end_div();
    echo html_writer::end_div();
}

$PAGE->requires->js_init_code(<<<JS
(function() {
    var boxes = function() {
        return Array.prototype.slice.call(
            document.querySelectorAll('[data-fx-bulk-row="1"]')
        );
    };
    var all = document.querySelector('[data-fx-bulk-select-all="1"]');
    var none = document.querySelector('[data-fx-bulk-deselect-all="1"]');
    if (all) {
        all.addEventListener('click', function() {
            boxes().forEach(function(box) {
                box.checked = true;
            });
        });
    }
    if (none) {
        none.addEventListener('click', function() {
            boxes().forEach(function(box) {
                box.checked = false;
            });
        });
    }
})();
JS);

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
