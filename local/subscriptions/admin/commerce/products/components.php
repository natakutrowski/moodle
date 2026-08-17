<?php

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\bundle\admin\CommerceBundleComponentInput;
use local_subscriptions\commerce\catalog\service\CommerceCatalogFactory;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\crm\commerce\presentation\CommerceDesignSystemRenderer;
use local_subscriptions\crm\commerce\presentation\CommerceProductPageHeaderRenderer;
use local_subscriptions\commerce\catalog\rendering\CommerceProductEditorNavigationRenderer;
use local_subscriptions\commerce\catalog\presentation\CommerceProductPresentation;
use local_subscriptions\commerce\catalog\presentation\CommerceCatalogProductNameResolver;

$context = AdminSecurity::require(Capabilities::MANAGE_CONFIGURATION);
$sku = required_param('sku', PARAM_RAW_TRIMMED);
$rowcount = max(2, min(50, optional_param('rows', 2, PARAM_INT)));
$factory = new CommerceCatalogFactory($DB);
$manager = $factory->product_manager();
$editor = $manager->get_editor_data($sku);
$product = $editor->get_product();
$displayname = CommerceCatalogProductNameResolver::resolve_native_id(
    $DB,
    (int)$product->get_id(),
    $product->get_name()
);

if (!$product->is_bundle()) {
    throw new coding_exception('Only Bundle products have editable components.');
}

$pageurl = new moodle_url('/local/subscriptions/admin/commerce/products/components.php', ['sku' => $sku]);
$pagetitle = get_string('commerce_bundle_components_title', 'local_subscriptions', $displayname);
CrmPageConfigurator::configure($PAGE, $context, $pageurl, $pagetitle, 'local-subscriptions-commerce-bundle-components-page');

if (data_submitted() && confirm_sesskey()) {
    $skus = optional_param_array('childsku', [], PARAM_RAW_TRIMMED);
    $quantities = optional_param_array('quantity', [], PARAM_INT);
    $sortorders = optional_param_array('sortorder', [], PARAM_INT);
    $rows = [];

    foreach ($skus as $index => $childsku) {
        $rows[] = [
            'sku' => $childsku,
            'quantity' => $quantities[$index] ?? 1,
            'sortorder' => $sortorders[$index] ?? $index,
        ];
    }

    $components = (new CommerceBundleComponentInput())->build($sku, $rows);
    $manager->save_bundle($product, $components);

    redirect($pageurl, get_string('changessaved'));
}

$available = [];
foreach ($manager->list_products(null, 'active') as $summary) {
    $candidate = $summary->get_product();
    if ($candidate->get_sku() !== $sku) {
        $candidatename = CommerceCatalogProductNameResolver::resolve_native_id(
            $DB,
            (int)$candidate->get_id(),
            $candidate->get_name()
        );
        $available[$candidate->get_sku()] =
            $candidatename
            . ' — '
            . CommerceProductPresentation::type_label(
                $candidate->get_type()
            );
    }
}

$current = $editor->get_components();
$rowcount = max($rowcount, count($current), 2);


$componentlabeltemplate = get_string(
    'commerce_bundle_component_number',
    'local_subscriptions',
    '__NUMBER__'
);

$renderrow = static function(
    int $index,
    $component,
    array $available
): string {
    $selected = $component?->get_child_product_sku() ?? '';
    $quantity = $component?->get_quantity() ?? 1;
    $rowkey = 'bundle-component-' . $index;

    $productfield = html_writer::div(
        html_writer::tag(
            'label',
            get_string(
                'commerce_bundle_component_product',
                'local_subscriptions'
            ),
            [
                'for' => $rowkey . '-product',
                'class' => 'form-label',
            ]
        )
        . html_writer::select(
            $available,
            'childsku[]',
            $selected,
            ['' => get_string('choosedots')],
            [
                'id' => $rowkey . '-product',
                'class' => 'form-select',
            ]
        ),
        'crm-bundle-composition-product'
    );

    $quantityfield = html_writer::div(
        html_writer::tag(
            'label',
            get_string(
                'commerce_bundle_component_quantity',
                'local_subscriptions'
            ),
            [
                'for' => $rowkey . '-quantity',
                'class' => 'form-label',
            ]
        )
        . html_writer::empty_tag('input', [
            'type' => 'number',
            'name' => 'quantity[]',
            'id' => $rowkey . '-quantity',
            'min' => 1,
            'value' => $quantity,
            'class' => 'form-control',
        ]),
        'crm-bundle-composition-quantity'
    );

    $controls = html_writer::div(
        html_writer::tag(
            'button',
            html_writer::tag('i', '', [
                'class' => 'fa fa-chevron-up',
                'aria-hidden' => 'true',
            ]),
            [
                'type' => 'button',
                'class' => 'btn btn-sm btn-outline-secondary crm-bundle-composition-move',
                'data-move' => 'up',
                'aria-label' => get_string(
                    'commerce_bundle_move_up',
                    'local_subscriptions'
                ),
                'title' => get_string(
                    'commerce_bundle_move_up',
                    'local_subscriptions'
                ),
            ]
        )
        . html_writer::tag(
            'button',
            html_writer::tag('i', '', [
                'class' => 'fa fa-chevron-down',
                'aria-hidden' => 'true',
            ]),
            [
                'type' => 'button',
                'class' => 'btn btn-sm btn-outline-secondary crm-bundle-composition-move',
                'data-move' => 'down',
                'aria-label' => get_string(
                    'commerce_bundle_move_down',
                    'local_subscriptions'
                ),
                'title' => get_string(
                    'commerce_bundle_move_down',
                    'local_subscriptions'
                ),
            ]
        )
        . html_writer::tag(
            'button',
            html_writer::tag('i', '', [
                'class' => 'fa fa-trash-o',
                'aria-hidden' => 'true',
            ]),
            [
                'type' => 'button',
                'class' => 'btn btn-sm btn-outline-danger crm-bundle-composition-remove',
                'aria-label' => get_string('delete'),
                'title' => get_string('delete'),
            ]
        ),
        'crm-bundle-composition-row-actions'
    );

    return html_writer::div(
        html_writer::div(
            html_writer::span(
                html_writer::tag('i', '', [
                    'class' => 'fa fa-bars',
                    'aria-hidden' => 'true',
                ]),
                'crm-bundle-composition-drag-handle',
                [
                    'title' => get_string(
                        'commerce_bundle_drag_handle',
                        'local_subscriptions'
                    ),
                ]
            )
            . html_writer::div(
                html_writer::span(
                    get_string(
                        'commerce_bundle_component_number',
                        'local_subscriptions',
                        $index + 1
                    ),
                    'crm-bundle-composition-row-number'
                )
                . html_writer::span(
                    get_string(
                        'commerce_bundle_component_row_help',
                        'local_subscriptions'
                    ),
                    'crm-bundle-composition-row-help'
                ),
                'crm-bundle-composition-row-title'
            ),
            'crm-bundle-composition-row-header'
        )
        . html_writer::div(
            $productfield
            . $quantityfield
            . $controls
            . html_writer::empty_tag('input', [
                'type' => 'hidden',
                'name' => 'sortorder[]',
                'value' => $index,
                'data-sortorder' => '1',
            ]),
            'crm-bundle-composition-row-grid'
        ),
        'crm-bundle-composition-row',
        [
            'draggable' => 'true',
            'data-bundle-component-row' => '1',
        ]
    );
};

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(
    CrmNavigationKeys::COMMERCE,
    $context
);
echo CommerceProductEditorNavigationRenderer::breadcrumb(
    $displayname,
    get_string(
        'commerce_product_step_components',
        'local_subscriptions'
    )
);
echo CommerceSectionNavigationRenderer::render(
    CommerceSectionNavigationRenderer::PRODUCTS
);
echo CommerceProductEditorNavigationRenderer::render(
    $product,
    CommerceProductEditorNavigationRenderer::COMPONENTS
);
echo CommerceProductPageHeaderRenderer::render(
    $pagetitle,
    CommerceDesignSystemRenderer::page_intro(
        get_string(
            'commerce_bundle_components_help_n89',
            'local_subscriptions'
        )
    ),
    '',
    get_string(
        'commerce_product_step_components',
        'local_subscriptions'
    )
);

echo html_writer::start_tag('form', [
    'method' => 'post',
    'class' => 'crm-bundle-composition-form',
]);
echo html_writer::empty_tag('input', [
    'type' => 'hidden',
    'name' => 'sesskey',
    'value' => sesskey(),
]);

echo html_writer::div(
    html_writer::tag('i', '', [
        'class' => 'fa fa-arrows me-2',
        'aria-hidden' => 'true',
    ])
    . get_string(
        'commerce_bundle_reorder_help',
        'local_subscriptions'
    ),
    'crm-bundle-composition-reorder-help'
);

echo html_writer::start_div(
    'crm-bundle-composition-list',
    ['data-bundle-component-list' => '1']
);

for ($index = 0; $index < $rowcount; $index++) {
    echo $renderrow(
        $index,
        $current[$index] ?? null,
        $available
    );
}

echo html_writer::end_div();

echo html_writer::div(
    html_writer::tag(
        'button',
        html_writer::tag('i', '', [
            'class' => 'fa fa-plus me-1',
            'aria-hidden' => 'true',
        ])
        . get_string(
            'commerce_bundle_add_component',
            'local_subscriptions'
        ),
        [
            'type' => 'button',
            'class' => 'btn btn-outline-secondary',
            'data-add-component' => '1',
        ]
    ),
    'crm-bundle-composition-add'
);

echo CommerceDesignSystemRenderer::form_actions(
    html_writer::tag(
        'button',
        html_writer::tag('i', '', [
            'class' => 'fa fa-save me-1',
            'aria-hidden' => 'true',
        ])
        . get_string('savechanges'),
        [
            'type' => 'submit',
            'class' => 'btn btn-primary',
        ]
    )
);
echo html_writer::end_tag('form');

echo html_writer::tag(
    'template',
    $renderrow(999, null, $available),
    ['id' => 'bundle-component-template']
);

if ($editor->get_expansion() !== null) {
    echo html_writer::start_div(
        'card card-body crm-bundle-expanded-preview'
    );
    echo html_writer::div(
        html_writer::tag(
            'h2',
            html_writer::tag('i', '', [
                'class' => 'fa fa-sitemap me-2',
                'aria-hidden' => 'true',
            ])
            . get_string(
                'commerce_bundle_preview_title',
                'local_subscriptions'
            ),
            ['class' => 'h5 mb-1']
        )
        . html_writer::tag(
            'p',
            get_string(
                'commerce_bundle_preview_help_n89',
                'local_subscriptions'
            ),
            ['class' => 'text-muted mb-0']
        ),
        'crm-bundle-expanded-preview-header'
    );
    echo html_writer::start_div(
        'crm-bundle-expanded-preview-list'
    );

    foreach ($editor->get_expansion()->get_items() as $item) {
        $expandedproduct = $item->get_product();
        $expandedname =
            CommerceCatalogProductNameResolver::resolve_native_id(
                $DB,
                (int)$expandedproduct->get_id(),
                $expandedproduct->get_name()
            );

        echo html_writer::div(
            html_writer::div(
                CommerceProductPresentation::type_label(
                    $expandedproduct->get_type()
                ),
                'crm-bundle-expanded-preview-type'
            )
            . html_writer::div(
                html_writer::link(
                    new moodle_url(
                        '/local/subscriptions/admin/commerce/products/view.php',
                        [
                            'id' => (int)$expandedproduct->get_id(),
                            'origin' => 'native',
                        ]
                    ),
                    html_writer::tag(
                        'strong',
                        format_string($expandedname)
                    )
                ),
                'crm-bundle-expanded-preview-name'
            )
            . html_writer::span(
                '× ' . $item->get_quantity(),
                'crm-bundle-expanded-preview-quantity'
            ),
            'crm-bundle-expanded-preview-row'
        );
    }

    echo html_writer::end_div();
    echo html_writer::end_div();
}

$componentlabeljs = json_encode($componentlabeltemplate);

$PAGE->requires->js_init_code(<<<JS
(function() {
    var list = document.querySelector('[data-bundle-component-list]');
    var template = document.getElementById('bundle-component-template');
    var addButton = document.querySelector('[data-add-component]');
    var labelTemplate = {$componentlabeljs};
    var dragged = null;

    if (!list || !template) {
        return;
    }

    function rows() {
        return Array.prototype.slice.call(
            list.querySelectorAll('[data-bundle-component-row]')
        );
    }

    function renumber() {
        var currentRows = rows();
        currentRows.forEach(function(row, index) {
            var number = row.querySelector(
                '.crm-bundle-composition-row-number'
            );
            var sort = row.querySelector('[data-sortorder]');
            var up = row.querySelector('[data-move="up"]');
            var down = row.querySelector('[data-move="down"]');

            if (number) {
                number.textContent = labelTemplate.replace(
                    '__NUMBER__',
                    String(index + 1)
                );
            }
            if (sort) {
                sort.value = index;
            }
            if (up) {
                up.disabled = index === 0;
            }
            if (down) {
                down.disabled = index === currentRows.length - 1;
            }
        });
    }

    function bindRow(row) {
        row.addEventListener('dragstart', function(event) {
            dragged = row;
            row.classList.add('is-dragging');
            event.dataTransfer.effectAllowed = 'move';
        });

        row.addEventListener('dragend', function() {
            row.classList.remove('is-dragging');
            rows().forEach(function(item) {
                item.classList.remove('is-drag-over');
            });
            dragged = null;
            renumber();
        });

        row.addEventListener('dragover', function(event) {
            event.preventDefault();
            if (dragged && dragged !== row) {
                row.classList.add('is-drag-over');
            }
        });

        row.addEventListener('dragleave', function() {
            row.classList.remove('is-drag-over');
        });

        row.addEventListener('drop', function(event) {
            event.preventDefault();
            row.classList.remove('is-drag-over');
            if (!dragged || dragged === row) {
                return;
            }

            var box = row.getBoundingClientRect();
            var before = event.clientY < box.top + box.height / 2;
            list.insertBefore(
                dragged,
                before ? row : row.nextSibling
            );
            renumber();
        });

        row.querySelectorAll('[data-move]').forEach(function(button) {
            button.addEventListener('click', function() {
                var direction = button.getAttribute('data-move');
                if (direction === 'up') {
                    var previous = row.previousElementSibling;
                    if (previous) {
                        list.insertBefore(row, previous);
                    }
                } else {
                    var next = row.nextElementSibling;
                    if (next) {
                        list.insertBefore(next, row);
                    }
                }
                renumber();
            });
        });

        var remove = row.querySelector(
            '.crm-bundle-composition-remove'
        );
        if (remove) {
            remove.addEventListener('click', function() {
                var currentRows = rows();
                if (currentRows.length <= 2) {
                    var select = row.querySelector('select');
                    var quantity = row.querySelector(
                        'input[name="quantity[]"]'
                    );
                    if (select) {
                        select.value = '';
                    }
                    if (quantity) {
                        quantity.value = 1;
                    }
                    return;
                }

                row.remove();
                renumber();
            });
        }
    }

    rows().forEach(bindRow);

    if (addButton) {
        addButton.addEventListener('click', function() {
            if (rows().length >= 50) {
                return;
            }

            var fragment = template.content.cloneNode(true);
            var row = fragment.querySelector(
                '[data-bundle-component-row]'
            );
            if (!row) {
                return;
            }

            list.appendChild(row);
            bindRow(row);
            renumber();

            var select = row.querySelector('select');
            if (select) {
                select.focus();
            }
        });
    }

    renumber();
})();
JS);

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
