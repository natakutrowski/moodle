<?php

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\catalog\assets\CommerceCatalogDigitalFileManager;
use local_subscriptions\commerce\catalog\assets\CommerceCatalogDigitalProductConfigurator;
use local_subscriptions\commerce\catalog\assets\CommerceCatalogMediaManager;
use local_subscriptions\commerce\catalog\cover\CommerceProductCoverService;
use local_subscriptions\commerce\catalog\presentation\CommerceCatalogProductNameResolver;
use local_subscriptions\commerce\catalog\rendering\CommerceProductEditorNavigationRenderer;
use local_subscriptions\commerce\catalog\service\CommerceCatalogFactory;
use local_subscriptions\commerce\catalog\visual\CommerceProductVisualAuditService;
use local_subscriptions\commerce\catalog\visual\CommerceProductVisualFormat;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

$context = AdminSecurity::require(Capabilities::MANAGE_CONFIGURATION);
$sku = required_param('sku', PARAM_RAW_TRIMMED);
$factory = new CommerceCatalogFactory($DB);
$manager = $factory->product_manager();
$editor = $manager->get_editor_data($sku);
$product = $editor->get_product();
$productid = (int)$product->get_id();
$displayname = CommerceCatalogProductNameResolver::resolve_native_id(
    $DB,
    $productid,
    $product->get_name()
);
$pageurl = new moodle_url('/local/subscriptions/admin/commerce/products/assets.php', ['sku' => $sku]);
CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    get_string('commerce_product_assets_title', 'local_subscriptions'),
    'local-subscriptions-commerce-product-assets-page'
);
$PAGE->requires->css(
    new moodle_url(
        '/local/subscriptions/styles/commerce_product_assets.css'
    )
);
$PAGE->requires->css(
    new moodle_url('/local/subscriptions/styles/storefront.css')
);
$PAGE->requires->css(
    new moodle_url(
        '/local/subscriptions/styles/my_digital_products.css'
    )
);

$media = new CommerceCatalogMediaManager($context);
$digitalfiles = new CommerceCatalogDigitalFileManager($context);
$coverservice = new CommerceProductCoverService($media);

$coverroles = [
    CommerceCatalogMediaManager::ROLE_CHECKOUT,
    CommerceCatalogMediaManager::ROLE_STOREFRONT,
    CommerceCatalogMediaManager::ROLE_PRODUCT,
    CommerceCatalogMediaManager::ROLE_RESOURCES,
    CommerceCatalogMediaManager::ROLE_SHOWROOM,
];
$visualformats = [
    CommerceCatalogMediaManager::ROLE_CHECKOUT => [
        'format' => 'square',
        'ratio' => '1:1',
        'recommended' => '1200 × 1200 px',
    ],
    CommerceCatalogMediaManager::ROLE_STOREFRONT => [
        'format' => 'landscape',
        'ratio' => '4:3',
        'recommended' => '1600 × 1200 px',
    ],
    CommerceCatalogMediaManager::ROLE_PRODUCT => [
        'format' => 'wide',
        'ratio' => '16:9',
        'recommended' => '1920 × 1080 px',
    ],
    CommerceCatalogMediaManager::ROLE_RESOURCES => [
        'format' => 'portrait',
        'ratio' => '4:5',
        'recommended' => '1200 × 1500 px',
    ],
    CommerceCatalogMediaManager::ROLE_SHOWROOM => [
        'format' => 'showroom',
        'ratio' => '16:9',
        'recommended' => '1920 × 1080 px',
    ],
];

$requestedaction = optional_param('action', '', PARAM_ALPHAEXT);
$hasaction = str_starts_with(
    $requestedaction,
    'save_cover_'
) || str_starts_with(
    $requestedaction,
    'delete_cover_'
) || in_array(
    $requestedaction,
    [
        'save_digital',
        'delete_desktop',
        'delete_mobile',
    ],
    true
);

if ($hasaction && confirm_sesskey()) {
    $action = $requestedaction;

    try {
        if (str_starts_with($action, 'save_cover_')) {
            $role = substr($action, strlen('save_cover_'));
            if (!in_array($role, $coverroles, true)) {
                throw new moodle_exception('invalidparameter');
            }

            $stored = $media->store_uploaded_file(
                $productid,
                $role,
                'cover_' . $role
            );
            if ($stored === null) {
                redirect(
                    $pageurl,
                    get_string(
                        'commerce_product_visual_no_file_selected',
                        'local_subscriptions'
                    ),
                    null,
                    \core\output\notification::NOTIFY_WARNING
                );
            }
        } else if (str_starts_with($action, 'delete_cover_')) {
            $role = substr($action, strlen('delete_cover_'));
            if (!in_array($role, $coverroles, true)) {
                throw new moodle_exception('invalidparameter');
            }
            $media->delete_file($productid, $role);
        } else if ($product->get_type() === 'digital_download' && $action === 'save_digital') {
            $desktop = $digitalfiles->store_uploaded_file(
                $productid,
                CommerceCatalogDigitalFileManager::ROLE_DESKTOP,
                'desktop_file'
            );
            $mobile = $digitalfiles->store_uploaded_file(
                $productid,
                CommerceCatalogDigitalFileManager::ROLE_MOBILE,
                'mobile_file'
            );

            if ($desktop !== null || $mobile !== null) {
                $definitions = (new CommerceCatalogDigitalProductConfigurator())->with_default_entitlement(
                    $product,
                    $editor->get_entitlements()
                );
                if (count($definitions) !== count($editor->get_entitlements())) {
                    $manager->save_entitlements($sku, $definitions);
                }
            }
        } else if ($product->get_type() === 'digital_download' && $action === 'delete_desktop') {
            $digitalfiles->delete_file($productid, CommerceCatalogDigitalFileManager::ROLE_DESKTOP);
        } else if ($product->get_type() === 'digital_download' && $action === 'delete_mobile') {
            $digitalfiles->delete_file($productid, CommerceCatalogDigitalFileManager::ROLE_MOBILE);
        } else {
            throw new moodle_exception('invalidparameter');
        }

        redirect($pageurl, get_string('changessaved'));
    } catch (moodle_exception $exception) {
        $expected = [
            'maxbytes',
            'error_uploading_file',
            'commerce_invalid_asset_type',
            'commerce_invalid_digital_file_type',
        ];
        if (!in_array($exception->errorcode, $expected, true)) {
            throw $exception;
        }

        $message = $exception->errorcode === 'maxbytes'
            ? get_string(
                'commerce_digital_file_error_maxbytes',
                'local_subscriptions',
                display_size(CommerceCatalogDigitalFileManager::MAX_BYTES)
            )
            : get_string('commerce_digital_file_error_upload', 'local_subscriptions');
        redirect($pageurl, $message, null, \core\output\notification::NOTIFY_ERROR);
    }
}

$digital = null;
if ($product->get_type() === 'digital_download') {
    $map = $DB->get_record('local_subs_commerce_prod_map', [
        'productid' => $productid,
        'legacytable' => 'subscription_digital_product',
    ]);
    $digital = $map ? $DB->get_record('subscription_digital_product', ['id' => $map->legacyid]) : null;
}

$resolvedcovers = $coverservice->resolve_all($productid);
$placeholdericon = CommerceProductVisualAuditService::placeholder_icon(
    $product->get_type()
);

$sampleprice = '99,00 EUR';
foreach ($editor->get_prices() as $candidateprice) {
    if (!$candidateprice->is_active()) {
        continue;
    }
    $sampleprice = format_float(
        $candidateprice->get_amount_minor() / 100,
        2
    ) . ' ' . $candidateprice->get_currency();
    break;
}

$previewvisual = static function (
    ?\local_subscriptions\commerce\catalog\cover\CommerceProductCover $cover
): array {
    return [
        'hascover' => $cover !== null && $cover->exists(),
        'coverurl' => $cover !== null
            ? (string)$cover->get_url()
            : '',
    ];
};

$typekey = 'commerce_product_type_' . $product->get_type();
$typelabel = get_string_manager()->string_exists(
    $typekey,
    'local_subscriptions'
) ? get_string($typekey, 'local_subscriptions') : $product->get_type();

$contextpreview = [
    'title' => get_string(
        'commerce_product_visual_context_preview_title',
        'local_subscriptions'
    ),
    'description' => get_string(
        'commerce_product_visual_context_preview_help',
        'local_subscriptions'
    ),
    'badge' => get_string(
        'commerce_product_visual_context_preview_badge',
        'local_subscriptions'
    ),
    'productname' => format_string($displayname),
    'producttype' => $product->get_type(),
    'placeholdericon' => $placeholdericon,
    'typelabel' => $typelabel,
    'sampledescription' => get_string(
        'commerce_product_visual_context_preview_description',
        'local_subscriptions'
    ),
    'sampleprice' => $sampleprice,
    'pricelabel' => get_string(
        'commerce_storefront_price_standard',
        'local_subscriptions'
    ),
    'addtocartlabel' => get_string(
        'commerce_cart_add',
        'local_subscriptions'
    ),
    'detailslabel' => get_string(
        'commerce_cart_view_product',
        'local_subscriptions'
    ),
    'boutiquelabel' => get_string(
        'commerce_product_visual_context_boutique',
        'local_subscriptions'
    ),
    'storefrontlabel' => get_string(
        'commerce_product_visual_context_storefront',
        'local_subscriptions'
    ),
    'checkoutlabel' => get_string(
        'commerce_product_visual_context_checkout',
        'local_subscriptions'
    ),
    'resourceslabel' => get_string(
        'commerce_product_visual_context_resources',
        'local_subscriptions'
    ),
    'ordersummarylabel' => get_string(
        'commerce_checkout_order_summary',
        'local_subscriptions'
    ),
    'totallabel' => get_string(
        'commerce_cart_total',
        'local_subscriptions'
    ),
    'classiclabel' => get_string(
        'digital_download_classic',
        'local_subscriptions'
    ),
    'availablelabel' => get_string(
        'commerce_product_visual_context_available',
        'local_subscriptions'
    ),
    'downloadlabel' => get_string(
        'digital_library_download',
        'local_subscriptions'
    ),
    'boutique' => $previewvisual(
        $resolvedcovers[
            \local_subscriptions\commerce\catalog\cover\CommerceProductCoverContext::STOREFRONT
        ] ?? null
    ),
    'storefront' => $previewvisual(
        $resolvedcovers[
            \local_subscriptions\commerce\catalog\cover\CommerceProductCoverContext::PRODUCT
        ] ?? null
    ),
    'checkout' => $previewvisual(
        $resolvedcovers[
            \local_subscriptions\commerce\catalog\cover\CommerceProductCoverContext::CHECKOUT
        ] ?? null
    ),
    'resources' => $previewvisual(
        $resolvedcovers[
            \local_subscriptions\commerce\catalog\cover\CommerceProductCoverContext::RESOURCES
        ] ?? null
    ),
];

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CommerceProductEditorNavigationRenderer::breadcrumb(
    $displayname,
    get_string('commerce_product_step_assets', 'local_subscriptions')
);
echo CommerceSectionNavigationRenderer::render(
    CommerceSectionNavigationRenderer::PRODUCTS
);
echo CommerceProductEditorNavigationRenderer::render(
    $product,
    CommerceProductEditorNavigationRenderer::ASSETS
);
echo $OUTPUT->heading(get_string('commerce_product_assets_title', 'local_subscriptions'));
echo html_writer::tag(
    'p',
    get_string('commerce_product_assets_help', 'local_subscriptions'),
    ['class' => 'text-muted']
);

echo html_writer::start_div('card card-body mb-4');
echo html_writer::tag(
    'h3',
    get_string('commerce_product_covers_title', 'local_subscriptions'),
    ['class' => 'h5']
);
echo html_writer::tag(
    'p',
    get_string('commerce_product_covers_help', 'local_subscriptions'),
    ['class' => 'text-muted']
);
echo html_writer::start_div('row g-3');

foreach ($coverroles as $role) {
    $masterfile = $media->get_file($productid, $role);
    $masterurl = $media->get_url($productid, $role);
    $resolved = $resolvedcovers[$role] ?? null;

    $isfallback = $masterfile === null
        && $resolved !== null
        && $resolved->exists();
    $resolvedrole = $isfallback
        ? (string)$resolved->get_resolved_context()
        : $role;
    $previewfile = $masterfile;
    if ($previewfile === null && $resolvedrole !== '') {
        $previewfile = $media->get_file($productid, $resolvedrole);
    }

    $previewurl = $masterurl !== null
        ? $masterurl->out(false)
        : ($resolved !== null ? $resolved->get_url() : null);

    $width = 0;
    $height = 0;
    $filesize = 0;
    $filename = '';
    if ($previewfile instanceof stored_file) {
        $dimensions = @getimagesizefromstring(
            $previewfile->get_content()
        );
        $width = is_array($dimensions)
            ? (int)($dimensions[0] ?? 0)
            : 0;
        $height = is_array($dimensions)
            ? (int)($dimensions[1] ?? 0)
            : 0;
        $filesize = $previewfile->get_filesize();
        $filename = $previewfile->get_filename();
    }

    $ratiook = $masterfile instanceof stored_file
        && CommerceProductVisualFormat::ratio_matches(
            $visualformats[$role]['format'],
            $width,
            $height
        );

    echo html_writer::start_div('col-12 col-xl-6');
    echo html_writer::start_div(
        'commerce-product-asset-card h-100'
    );

    echo html_writer::start_div(
        'commerce-product-asset-card__heading'
    );
    echo html_writer::start_div('');
    echo html_writer::tag(
        'h4',
        get_string(
            'commerce_product_visual_format_'
                . $visualformats[$role]['format'],
            'local_subscriptions'
        ),
        ['class' => 'h6 mb-1']
    );
    echo html_writer::div(
        $visualformats[$role]['ratio']
            . ' · '
            . $visualformats[$role]['recommended'],
        'commerce-product-asset-card__format'
    );
    echo html_writer::end_div();

    $statuskey = $masterfile instanceof stored_file
        ? 'commerce_product_visual_status_ok'
        : (
            $isfallback
                ? 'commerce_product_visual_status_fallback'
                : 'commerce_product_visual_status_missing'
        );
    $statusclass = $masterfile instanceof stored_file
        ? 'text-bg-success'
        : ($isfallback ? 'text-bg-warning' : 'text-bg-secondary');
    echo html_writer::span(
        get_string($statuskey, 'local_subscriptions'),
        'badge rounded-pill ' . $statusclass
    );
    echo html_writer::end_div();

    echo html_writer::tag(
        'p',
        get_string(
            'commerce_product_visual_format_'
                . $visualformats[$role]['format']
                . '_help',
            'local_subscriptions',
            $visualformats[$role]['recommended']
        ),
        ['class' => 'small text-muted mb-3']
    );

    $previewclasses = [
        'commerce-product-asset-preview',
        'commerce-product-asset-preview--'
            . $visualformats[$role]['format'],
    ];
    if ($isfallback) {
        $previewclasses[] =
            'commerce-product-asset-preview--fallback';
    }

    echo html_writer::start_div(implode(' ', $previewclasses));
    if ($previewurl !== null && trim($previewurl) !== '') {
        echo html_writer::empty_tag('img', [
            'src' => $previewurl,
            'alt' => get_string(
                'commerce_product_visual_preview_alt',
                'local_subscriptions',
                format_string($displayname)
            ),
        ]);
        if ($isfallback) {
            echo html_writer::div(
                get_string(
                    'commerce_product_visual_fallback_source',
                    'local_subscriptions',
                    $resolvedrole
                ),
                'commerce-product-asset-preview__fallback'
            );
        }
    } else {
        echo html_writer::div(
            html_writer::tag('i', '', [
                'class' => $placeholdericon,
                'aria-hidden' => 'true',
            ]),
            'commerce-product-asset-preview__placeholder',
            ['aria-hidden' => 'true']
        );
    }
    echo html_writer::end_div();

    echo html_writer::start_div(
        'commerce-product-asset-card__metadata'
    );
    foreach ([
        'commerce_product_visual_metadata_dimensions' =>
            ($width > 0 && $height > 0
                ? $width . ' × ' . $height . ' px'
                : '—'),
        'commerce_product_visual_metadata_ratio' =>
            ($width > 0 && $height > 0
                ? number_format($width / $height, 3, ',', '')
                    . ' (' . $visualformats[$role]['ratio'] . ')'
                : $visualformats[$role]['ratio']),
        'commerce_product_visual_metadata_weight' =>
            ($filesize > 0 ? display_size($filesize) : '—'),
    ] as $labelkey => $value) {
        echo html_writer::start_div(
            'commerce-product-asset-card__metadata-row'
        );
        echo html_writer::span(
            get_string($labelkey, 'local_subscriptions')
        );
        echo html_writer::tag('strong', $value);
        echo html_writer::end_div();
    }

    if ($filename !== '') {
        echo html_writer::start_div(
            'commerce-product-asset-card__metadata-row'
        );
        echo html_writer::span(
            get_string(
                'commerce_product_visual_metadata_file',
                'local_subscriptions'
            )
        );
        echo html_writer::tag(
            'strong',
            s($filename),
            ['class' => 'text-break']
        );
        echo html_writer::end_div();
    }
    echo html_writer::end_div();

    if ($masterfile instanceof stored_file && !$ratiook) {
        $message = get_string(
            'commerce_product_visual_ratio_warning',
            'local_subscriptions',
            $visualformats[$role]['ratio']
        );
        $messageclass = 'alert-warning';
    } else if ($masterfile instanceof stored_file) {
        $message = get_string(
            'commerce_product_visual_ratio_ok',
            'local_subscriptions',
            $visualformats[$role]['ratio']
        );
        $messageclass = 'alert-success';
    } else if ($isfallback) {
        $message = get_string(
            'commerce_product_visual_fallback_help',
            'local_subscriptions'
        );
        $messageclass = 'alert-warning';
    } else {
        $message = get_string(
            'commerce_product_visual_missing_help',
            'local_subscriptions'
        );
        $messageclass = 'alert-light';
    }
    echo html_writer::div(
        $message,
        'alert ' . $messageclass . ' py-2 small mt-3 mb-0'
    );

    echo html_writer::start_div(
        'commerce-product-asset-card__actions'
    );

    echo html_writer::start_tag('form', [
        'method' => 'post',
        'enctype' => 'multipart/form-data',
        'class' => 'commerce-product-asset-card__upload-form',
    ]);
    echo html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'sesskey',
        'value' => sesskey(),
    ]);
    echo html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'action',
        'value' => 'save_cover_' . $role,
    ]);
    echo html_writer::empty_tag('input', [
        'type' => 'file',
        'name' => 'cover_' . $role,
        'accept' => '.png,.jpg,.jpeg,.webp',
        'class' => 'form-control',
        'aria-label' => get_string(
            'commerce_product_visual_format_'
                . $visualformats[$role]['format'],
            'local_subscriptions'
        ),
    ]);
    echo html_writer::start_div('commerce-product-asset-card__button-row');
    echo html_writer::empty_tag('input', [
        'type' => 'submit',
        'class' => 'btn btn-primary',
        'value' => get_string(
            'commerce_product_visual_save_format',
            'local_subscriptions'
        ),
    ]);
    if ($masterfile instanceof stored_file) {
        echo html_writer::link(
            new moodle_url($pageurl, [
                'action' => 'delete_cover_' . $role,
                'sesskey' => sesskey(),
            ]),
            html_writer::tag('i', '', [
                'class' => 'fa fa-trash-o me-1',
                'aria-hidden' => 'true',
            ]) . get_string('delete'),
            ['class' => 'btn btn-outline-danger']
        );
    }
    echo html_writer::end_div();
    echo html_writer::end_tag('form');
    echo html_writer::end_div();

    echo html_writer::end_div();
    echo html_writer::end_div();
}

echo html_writer::end_div();
echo html_writer::end_div();

echo $OUTPUT->render_from_template(
    'local_subscriptions/admin/product_cover_context_previews',
    $contextpreview
);

if ($product->get_type() === 'digital_download') {
    $desktopfile = $digitalfiles->get_file(
        $productid,
        CommerceCatalogDigitalFileManager::ROLE_DESKTOP
    );
    $mobilefile = $digitalfiles->get_file(
        $productid,
        CommerceCatalogDigitalFileManager::ROLE_MOBILE
    );

    echo html_writer::start_div('card card-body crm-product-assets-digital-files');
    echo html_writer::div(
        html_writer::tag('i', '', [
            'class' => 'fa fa-file-pdf-o',
            'aria-hidden' => 'true',
        ])
        . html_writer::div(
            html_writer::tag(
                'h3',
                get_string('commerce_digital_files', 'local_subscriptions'),
                ['class' => 'h5 mb-1']
            )
            . html_writer::tag(
                'p',
                get_string('commerce_digital_files_native_help', 'local_subscriptions'),
                ['class' => 'text-muted mb-0']
            ),
            'crm-product-assets-digital-heading-copy'
        ),
        'crm-product-assets-digital-heading'
    );

    echo html_writer::start_tag('form', [
        'method' => 'post',
        'enctype' => 'multipart/form-data',
        'class' => 'crm-product-assets-digital-form',
    ]);
    echo html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'sesskey',
        'value' => sesskey(),
    ]);
    echo html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'action',
        'value' => 'save_digital',
    ]);

    echo html_writer::start_div('crm-product-assets-digital-grid');
    foreach ([
        [
            'file' => $desktopfile,
            'label' => 'commerce_desktop_file',
            'input' => 'desktop_file',
            'delete' => 'delete_desktop',
            'icon' => 'fa-desktop',
        ],
        [
            'file' => $mobilefile,
            'label' => 'commerce_mobile_file',
            'input' => 'mobile_file',
            'delete' => 'delete_mobile',
            'icon' => 'fa-mobile',
        ],
    ] as $item) {
        $file = $item['file'];
        $hasfile = $file instanceof stored_file;

        echo html_writer::start_div(
            'crm-product-assets-digital-card' . ($hasfile ? ' has-file' : ' is-empty')
        );
        echo html_writer::div(
            html_writer::div(
                html_writer::tag('i', '', [
                    'class' => 'fa ' . $item['icon'],
                    'aria-hidden' => 'true',
                ]),
                'crm-product-assets-digital-icon'
            )
            . html_writer::div(
                html_writer::tag(
                    'strong',
                    get_string($item['label'], 'local_subscriptions')
                )
                . html_writer::div(
                    get_string(
                        $hasfile
                            ? 'commerce_product_digital_file_ready'
                            : 'commerce_product_digital_file_missing',
                        'local_subscriptions'
                    ),
                    'crm-product-assets-digital-state'
                ),
                'crm-product-assets-digital-title'
            )
            . html_writer::span(
                get_string(
                    $hasfile
                        ? 'commerce_product_visual_status_ok'
                        : 'commerce_product_visual_status_missing',
                    'local_subscriptions'
                ),
                'badge rounded-pill ' . ($hasfile ? 'text-bg-success' : 'text-bg-secondary')
            ),
            'crm-product-assets-digital-card-header'
        );

        if ($hasfile) {
            echo html_writer::div(
                html_writer::tag('i', '', [
                    'class' => 'fa fa-file-pdf-o me-2',
                    'aria-hidden' => 'true',
                ])
                . html_writer::div(
                    html_writer::tag(
                        'strong',
                        s($file->get_filename()),
                        ['class' => 'text-break']
                    )
                    . html_writer::span(
                        display_size($file->get_filesize()),
                        'crm-product-assets-digital-filesize'
                    ),
                    'crm-product-assets-digital-current-copy'
                ),
                'crm-product-assets-digital-current'
            );
        } else {
            echo html_writer::div(
                html_writer::tag('i', '', [
                    'class' => 'fa fa-cloud-upload me-2',
                    'aria-hidden' => 'true',
                ])
                . get_string(
                    'commerce_product_digital_file_upload_prompt',
                    'local_subscriptions'
                ),
                'crm-product-assets-digital-empty'
            );
        }

        echo html_writer::tag(
            'label',
            get_string(
                'commerce_product_digital_file_replace_or_add',
                'local_subscriptions'
            ),
            [
                'for' => $item['input'],
                'class' => 'form-label',
            ]
        );
        echo html_writer::empty_tag('input', [
            'id' => $item['input'],
            'type' => 'file',
            'name' => $item['input'],
            'accept' => '.pdf,application/pdf',
            'class' => 'form-control',
        ]);

        if ($hasfile) {
            echo html_writer::div(
                html_writer::link(
                    new moodle_url($pageurl, [
                        'action' => $item['delete'],
                        'sesskey' => sesskey(),
                    ]),
                    html_writer::tag('i', '', [
                        'class' => 'fa fa-trash-o me-1',
                        'aria-hidden' => 'true',
                    ]) . get_string('delete'),
                    ['class' => 'btn btn-sm btn-outline-danger']
                ),
                'crm-product-assets-digital-delete'
            );
        }
        echo html_writer::end_div();
    }
    echo html_writer::end_div();

    echo html_writer::div(
        html_writer::tag(
            'button',
            html_writer::tag('i', '', [
                'class' => 'fa fa-save me-1',
                'aria-hidden' => 'true',
            ]) . get_string('savechanges'),
            [
                'type' => 'submit',
                'class' => 'btn btn-primary',
            ]
        ),
        'crm-product-assets-digital-actions'
    );
    echo html_writer::end_tag('form');

    if ($digital && !$desktopfile && !$mobilefile) {
        echo html_writer::div(
            html_writer::tag('i', '', [
                'class' => 'fa fa-info-circle me-2',
                'aria-hidden' => 'true',
            ])
            . get_string('commerce_digital_files_legacy_fallback', 'local_subscriptions'),
            'crm-product-assets-digital-legacy-note'
        );
    }
    echo html_writer::end_div();
}

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
