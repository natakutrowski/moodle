<?php

declare(strict_types=1);

namespace local_subscriptions\crm\user360\rendering;

defined('MOODLE_INTERNAL') || die();

use html_table;
use html_writer;
use local_subscriptions\admin\AdminFormatter;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\mail\sales\CommerceSalesFollowupService;
use local_subscriptions\commerce\purchase\action\CommercePurchaseActionPolicy;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseReadRepository;
use local_subscriptions\commerce\purchase\presentation\CommercePurchasePresentation;
use local_subscriptions\support\SubsPresenter;
use local_subscriptions\subscription_config;
use moodle_url;

/**
 * N11.6A — Advanced Commerce view for User360.
 *
 * The support-first screen already owns effective access and digital-library
 * presentation. This advanced screen focuses on Commerce history and Legacy
 * subscription operations.
 */
final class User360CommerceAccessRenderer {

    public static function render(\stdClass $profile): string {
        $orders = self::orders($profile->commercepurchases ?? []);
        $legacy = self::legacy_subscriptions($profile->subscriptions ?? []);

        return html_writer::tag(
            'section',
            self::section(
                'fa fa-shopping-bag',
                get_string(
                    'crm_user360_n116a_orders_title',
                    'local_subscriptions'
                ),
                get_string(
                    'crm_user360_n116a_orders_help',
                    'local_subscriptions'
                ),
                $orders,
                'orders'
            )
            . self::section(
                'fa fa-history',
                get_string(
                    'crm_user360_n116a_legacy_subscriptions_title',
                    'local_subscriptions'
                ),
                get_string(
                    'crm_user360_n116a_legacy_subscriptions_help',
                    'local_subscriptions'
                ),
                $legacy,
                'legacy-subscriptions'
            ),
            [
                'id' => 'user360-commerce',
                'class' => 'crm-user360-n116a-commerce',
                'aria-label' => get_string(
                    'crm_user360_n115a_commerce',
                    'local_subscriptions'
                ),
            ]
        );
    }

    private static function orders(array $purchases): string {
        if ($purchases === []) {
            return self::empty_state(
                get_string(
                    'crm_commerce_no_purchases',
                    'local_subscriptions'
                )
            );
        }

        global $DB;

        $repository = new CommercePurchaseReadRepository($DB);
        $policy = new CommercePurchaseActionPolicy();
        $followup = CommerceSalesFollowupService::create($DB);

        $table = new html_table();
        $table->attributes['class'] =
            'generaltable crm-user360-n116a-table crm-user360-n116a-orders-table';

        $table->head = [
            get_string('date'),
            get_string(
                'crm_commerce_reference',
                'local_subscriptions'
            ),
            get_string(
                'crm_commerce_purchase_type',
                'local_subscriptions'
            ),
            get_string(
                'crm_commerce_contents',
                'local_subscriptions'
            ),
            get_string(
                'crm_commerce_amount',
                'local_subscriptions'
            ),
            get_string(
                'status',
                'local_subscriptions'
            ),
            get_string(
                'actions',
                'local_subscriptions'
            ),
        ];

        foreach ($purchases as $purchase) {
            $readmodel = $repository->find_by_id(
                (int)($purchase->id ?? 0)
            );
            $summary = $readmodel?->summary;

            $type = $summary !== null
                ? $summary->type
                : (string)($purchase->type ?? 'unknown');

            $commercialstatus = $summary !== null
                ? $summary->commercialstatus
                : (string)($purchase->status ?? '');

            $reference = trim(
                (string)(
                    $summary?->publicreference
                    ?: ($purchase->publicreference
                        ?? $purchase->reference
                        ?? '-')
                )
            );

            $purchaseid = (int)($purchase->id ?? 0);
            $viewurl = new moodle_url(
                '/local/subscriptions/admin/commerce/purchases/view.php',
                ['id' => $purchaseid]
            );

            $referencehtml = $purchaseid > 0
                ? html_writer::link(
                    $viewurl,
                    s($reference),
                    [
                        'class' =>
                            'crm-user360-n116a-primary-link',
                    ]
                )
                : s($reference);

            $table->data[] = [
                !empty($purchase->timecreated)
                    ? AdminFormatter::datetime(
                        (int)$purchase->timecreated
                    )
                    : ($summary !== null
                        ? AdminFormatter::datetime(
                            (int)$summary->timecreated
                        )
                        : '—'),
                $referencehtml,
                CommercePurchasePresentation::type_badge(
                    $type
                ),
                self::order_contents($purchase),
                $summary !== null
                    ? CommercePurchasePresentation::money(
                        $summary->totalminor,
                        $summary->currency
                    )
                    : AdminFormatter::price(
                        (float)($purchase->total ?? 0),
                        (string)($purchase->currency ?? '')
                    ),
                CommercePurchasePresentation::
                    commercial_status_badge(
                        $summary !== null
                            ? $commercialstatus
                            : 'unknown'
                    ),
                self::order_actions(
                    $purchase,
                    $summary,
                    $policy,
                    $followup
                ),
            ];
        }

        return html_writer::table($table);
    }

    private static function order_contents(\stdClass $purchase): string {
        $items = is_array($purchase->items ?? null)
            ? $purchase->items
            : [];

        if ($items === []) {
            return s(
                (string)($purchase->label ?? '—')
            );
        }

        $content = '';

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $label = trim(
                (string)($item['label'] ?? '')
            );
            if ($label === '') {
                $label = trim(
                    (string)($item['reference'] ?? '')
                );
            }
            if ($label === '') {
                continue;
            }

            $url = self::item_url($item);

            $labelhtml = $url !== null
                ? html_writer::link(
                    $url,
                    s($label),
                    [
                        'class' =>
                            'crm-user360-n116a-content-link',
                    ]
                )
                : html_writer::span(
                    s($label),
                    'crm-user360-n116a-content-label'
                );

            $content .= html_writer::div(
                $labelhtml,
                'crm-user360-n116a-order-item'
            );
        }

        return $content !== ''
            ? $content
            : s(
                (string)($purchase->label ?? '—')
            );
    }

    private static function item_url(array $item): ?moodle_url {
        $metadata = is_array($item['metadata'] ?? null)
            ? $item['metadata']
            : [];

        $fulfillment = is_array($item['fulfillment'] ?? null)
            ? $item['fulfillment']
            : [];

        $courseid = (int)(
            $fulfillment['courseid']
            ?? $metadata['courseid']
            ?? 0
        );

        if ($courseid > 0) {
            return new moodle_url(
                '/course/view.php',
                ['id' => $courseid]
            );
        }

        $sku = trim(
            (string)(
                $item['reference']
                ?? $metadata['sku']
                ?? ''
            )
        );

        if ($sku !== '') {
            return new moodle_url(
                '/local/subscriptions/admin/commerce/products/view.php',
                ['sku' => $sku]
            );
        }

        return null;
    }

    private static function order_actions(
        \stdClass $purchase,
        ?\local_subscriptions\commerce\purchase\readmodel\CommercePurchaseSummary $summary,
        CommercePurchaseActionPolicy $policy,
        CommerceSalesFollowupService $followup
    ): string {
        $sections = [
            'order' => [],
            'communication' => [],
        ];

        $publicreference = trim(
            (string)(
                $purchase->publicreference
                ?? $purchase->reference
                ?? ''
            )
        );
        $internalreference = trim(
            (string)($purchase->reference ?? '')
        );

        // Open the sale directly in the CRM Sales detail page.
        $sections['order'][] = self::menu_link(
            new moodle_url(
                '/local/subscriptions/admin/commerce/purchases/view.php',
                ['id' => (int)$purchase->id]
            ),
            'fa fa-shopping-cart',
            get_string(
                'crm_user360_n116b_open_sales',
                'local_subscriptions'
            )
        );

        if ($internalreference !== '') {
            $sections['order'][] = self::menu_link(
                new moodle_url(
                    '/local/subscriptions/order_details.php',
                    [
                        'reference' => $internalreference,
                        'adminreturn' => 1,
                    ]
                ),
                'fa fa-external-link',
                get_string(
                    'commerce_sales_action_view_customer_order',
                    'local_subscriptions'
                )
            );

            $sections['order'][] = self::menu_link(
                new moodle_url(
                    '/local/subscriptions/order_invoice.php',
                    ['reference' => $internalreference]
                ),
                'fa fa-file-pdf-o',
                get_string(
                    'commerce_purchase_download_invoice',
                    'local_subscriptions'
                ),
                [
                    'target' => '_blank',
                    'rel' => 'noopener noreferrer',
                ]
            );
        }

        if ((int)($purchase->id ?? 0) > 0) {
            $sections['order'][] = self::menu_link(
                new moodle_url(
                    '/local/subscriptions/admin/commerce/mail/index.php',
                    ['purchaseid' => (int)$purchase->id]
                ),
                'fa fa-list-alt',
                get_string(
                    'commerce_purchase_open_mail_journal',
                    'local_subscriptions'
                )
            );
        }

        if (
            $summary !== null
            && has_capability(
                Capabilities::MANAGE_SUBSCRIPTIONS,
                \context_system::instance()
            )
        ) {
            try {
                if ($followup->is_summary_eligible($summary)) {
                    $sections['communication'][] = self::menu_link(
                        new moodle_url(
                            '/local/subscriptions/admin/commerce/purchases/followup_mail.php',
                            ['id' => (int)$purchase->id]
                        ),
                        'fa fa-paper-plane-o',
                        get_string(
                            'commerce_sales_followup_action',
                            'local_subscriptions'
                        )
                    );
                }
            } catch (\Throwable) {
                // Presentation enrichment only; never break User360 for it.
            }

            $returnurl = self::current_return_url();

            if ($policy->can_resend_receipt_summary($summary)) {
                $resendreceipturl = new moodle_url(
                    '/local/subscriptions/admin/commerce/purchases/resend_receipt.php',
                    [
                        'id' => (int)$purchase->id,
                        'confirm' => 1,
                        'sesskey' => sesskey(),
                        'returnurl' => $returnurl,
                    ]
                );

                $sections['communication'][] = self::menu_link(
                    $resendreceipturl,
                    'fa fa-envelope-o',
                    get_string(
                        'commerce_sales_action_resend_invoice',
                        'local_subscriptions'
                    ),
                    [
                        'data-confirmation' => 'modal',
                        'data-confirmation-title-str' => json_encode([
                            'commerce_sales_action_resend_invoice',
                            'local_subscriptions',
                        ]),
                        'data-confirmation-content-str' => json_encode([
                            'commerce_sales_action_resend_invoice_confirm',
                            'local_subscriptions',
                        ]),
                        'data-confirmation-yes-button-str' => json_encode([
                            'yes',
                        ]),
                        'data-confirmation-destination' =>
                            $resendreceipturl->out(false),
                    ]
                );
            }

            if ($policy->can_resend_access_summary($summary)) {
                $resendaccessurl = new moodle_url(
                    '/local/subscriptions/admin/commerce/purchases/resend_access.php',
                    [
                        'id' => (int)$purchase->id,
                        'confirm' => 1,
                        'sesskey' => sesskey(),
                        'returnurl' => $returnurl,
                    ]
                );

                $sections['communication'][] = self::menu_link(
                    $resendaccessurl,
                    'fa fa-key',
                    get_string(
                        'commerce_sales_action_resend_access',
                        'local_subscriptions'
                    ),
                    [
                        'data-confirmation' => 'modal',
                        'data-confirmation-title-str' => json_encode([
                            'commerce_sales_action_resend_access',
                            'local_subscriptions',
                        ]),
                        'data-confirmation-content-str' => json_encode([
                            'commerce_purchase_resend_access_confirm',
                            'local_subscriptions',
                        ]),
                        'data-confirmation-yes-button-str' => json_encode([
                            'yes',
                        ]),
                        'data-confirmation-destination' =>
                            $resendaccessurl->out(false),
                    ]
                );
            }
        }

        return self::context_menu($sections);
    }

    private static function legacy_subscriptions(
        array $subscriptions
    ): string {
        if ($subscriptions === []) {
            return self::empty_state(
                get_string(
                    'crm_no_subscriptions',
                    'local_subscriptions'
                )
            );
        }

        $table = new html_table();
        $table->attributes['class'] =
            'generaltable crm-user360-n116a-table crm-user360-n116a-legacy-table';

        $table->head = [
            get_string(
                'plan',
                'local_subscriptions'
            ),
            get_string(
                'subscription_period',
                'local_subscriptions'
            ),
            get_string(
                'price',
                'local_subscriptions'
            ),
            get_string(
                'status',
                'local_subscriptions'
            ),
            get_string(
                'actions',
                'local_subscriptions'
            ),
        ];

        foreach ($subscriptions as $sub) {
            $subscriptionid = (int)($sub->id ?? 0);
            $planname = trim(
                (string)($sub->planname ?? '')
            );
            if ($planname === '') {
                $planname = get_string(
                    'unknown_plan',
                    'local_subscriptions'
                );
            }

            $planhtml = $subscriptionid > 0
                ? html_writer::link(
                    new moodle_url(
                        subscription_config::
                            user_subscription_view_page(),
                        ['id' => $subscriptionid]
                    ),
                    format_string($planname),
                    [
                        'class' =>
                            'crm-user360-n116a-primary-link',
                    ]
                )
                : format_string($planname);

            $start = !empty($sub->start_date)
                ? AdminFormatter::date(
                    (int)$sub->start_date
                )
                : '—';

            if (
                empty($sub->end_date)
                || (int)$sub->end_date
                    > strtotime('2100-01-01')
            ) {
                $period = $start
                    . ' → '
                    . get_string(
                        'unlimited',
                        'local_subscriptions'
                    );
            } else {
                $period = $start
                    . ' → '
                    . AdminFormatter::date(
                        (int)$sub->end_date
                    );
            }

            $price =
                ((float)($sub->pricepaid ?? 0) > 0)
                    ? AdminFormatter::price(
                        $sub->pricepaid ?? 0,
                        $sub->currency ?? ''
                    )
                    : '—';

            $table->data[] = [
                $planhtml,
                html_writer::span(
                    s($period),
                    'crm-user360-n116a-period'
                ),
                $price,
                SubsPresenter::render_status_badge(
                    $sub->status
                ),
                self::legacy_subscription_actions($sub),
            ];
        }

        return html_writer::table($table);
    }

    private static function legacy_subscription_actions(
        \stdClass $sub
    ): string {
        $subscriptionid = (int)($sub->id ?? 0);
        $userid = (int)($sub->userid ?? 0);

        if ($subscriptionid <= 0) {
            return '—';
        }

        $sections = [
            'order' => [
                self::menu_link(
                    new moodle_url(
                        subscription_config::
                            user_subscription_view_page(),
                        ['id' => $subscriptionid]
                    ),
                    'fa fa-eye',
                    get_string(
                        'view_details',
                        'local_subscriptions'
                    )
                ),
                self::menu_link(
                    new moodle_url(
                        subscription_config::
                            user_subscription_edit_page(),
                        ['id' => $subscriptionid]
                    ),
                    'fa fa-pencil',
                    get_string('edit')
                ),
            ],
            'communication' => [],
        ];

        if (
            $userid > 0
            && has_capability(
                Capabilities::MANAGE_USERS,
                \context_system::instance()
            )
        ) {
            $baseparams = [
                'userid' => $userid,
                'subscriptionid' => $subscriptionid,
                'sesskey' => sesskey(),
            ];

            $sections['communication'][] = self::menu_link(
                new moodle_url(
                    subscription_config::
                        admin_user_subscription_quick_action_page(),
                    $baseparams + ['action' => 'welcome']
                ),
                'fa fa-hand-paper-o',
                get_string(
                    'crm_resend_welcome_email',
                    'local_subscriptions'
                )
            );

            $sections['communication'][] = self::menu_link(
                new moodle_url(
                    subscription_config::
                        admin_user_subscription_quick_action_page(),
                    $baseparams + ['action' => 'access']
                ),
                'fa fa-key',
                get_string(
                    'crm_resend_access_email',
                    'local_subscriptions'
                )
            );

            $sections['communication'][] = self::menu_link(
                new moodle_url(
                    subscription_config::
                        admin_user_subscription_quick_action_page(),
                    $baseparams + ['action' => 'receipt']
                ),
                'fa fa-file-text-o',
                get_string(
                    'crm_resend_receipt',
                    'local_subscriptions'
                )
            );

        }

        return self::context_menu($sections);
    }

    private static function context_menu(array $sections): string {
        $sectionlabels = [
            'order' => 'commerce_sales_actions_order',
            'communication' => 'commerce_sales_actions_communication',
        ];

        $menu = '';

        foreach ($sectionlabels as $section => $labelkey) {
            $items = $sections[$section] ?? [];
            if ($items === []) {
                continue;
            }

            $menu .= html_writer::div(
                get_string(
                    $labelkey,
                    'local_subscriptions'
                ),
                'crm-sales-row-menu-section'
            )
            . implode('', $items);
        }

        if ($menu === '') {
            return '—';
        }

        return html_writer::tag(
            'details',
            html_writer::tag(
                'summary',
                html_writer::tag('i', '', [
                    'class' => 'fa fa-ellipsis-h',
                    'aria-hidden' => 'true',
                ]),
                [
                    'class' =>
                        'btn btn-sm btn-outline-secondary '
                        . 'crm-sales-row-menu-toggle',
                    'aria-label' => get_string(
                        'actions',
                        'local_subscriptions'
                    ),
                ]
            )
            . html_writer::div(
                $menu,
                'crm-sales-row-menu'
            ),
            [
                'class' =>
                    'crm-sales-row-actions-menu '
                    . 'crm-user360-n116b-actions-menu',
            ]
        );
    }

    private static function menu_link(
        moodle_url $url,
        string $icon,
        string $label,
        array $attributes = []
    ): string {
        $attributes = array_merge(
            [
                'class' => 'crm-sales-row-menu-link',
            ],
            $attributes
        );

        return html_writer::link(
            $url,
            html_writer::tag('i', '', [
                'class' => $icon,
                'aria-hidden' => 'true',
            ])
            . html_writer::span(
                s($label)
            ),
            $attributes
        );
    }

    private static function current_return_url(): string {
        global $PAGE;

        if (
            isset($PAGE)
            && $PAGE->url instanceof moodle_url
        ) {
            return $PAGE->url->out_as_local_url(false);
        }

        return '/local/subscriptions/admin/users/index.php';
    }

    private static function section(
        string $icon,
        string $title,
        string $help,
        string $content,
        string $key
    ): string {
        return html_writer::tag(
            'section',
            html_writer::div(
                html_writer::div(
                    html_writer::tag('i', '', [
                        'class' => $icon,
                        'aria-hidden' => 'true',
                    ])
                    . html_writer::div(
                        html_writer::tag(
                            'h3',
                            s($title),
                            [
                                'class' =>
                                    'crm-user360-n116a-section-title',
                            ]
                        )
                        . html_writer::div(
                            s($help),
                            'crm-user360-n116a-section-help'
                        ),
                        'crm-user360-n116a-section-copy'
                    ),
                    'crm-user360-n116a-section-heading'
                ),
                'crm-user360-n116a-section-header'
            )
            . html_writer::div(
                $content,
                'crm-user360-n116a-section-body table-responsive'
            ),
            [
                'class' =>
                    'crm-user360-n116a-section '
                    . 'crm-user360-n116a-' . $key,
            ]
        );
    }

    private static function empty_state(
        string $text
    ): string {
        return html_writer::div(
            s($text),
            'crm-user360-n116a-empty'
        );
    }
}
