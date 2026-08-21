<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\customer\identity;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\commerce\customer\reconciliation\CommerceCustomerIdentityReconciliationPreview;

/**
 * Human-readable preview of what an identity reconciliation will update.
 *
 * This renderer deliberately explains business effects rather than exposing
 * technical counter names such as grantsupdated / legacyrecordsupdated.
 */
final class CommerceCustomerIdentityImpactRenderer {

    public static function render(
        CommerceCustomerIdentityReconciliationPreview $preview,
        \stdClass $purchase,
        ?int $userid
    ): string {
        if ($userid === null) {
            return '—';
        }

        $items = [];

        if ($preview->purchaseupdates > 0) {
            $items[] = self::item(
                'fa fa-shopping-cart',
                get_string(
                    'crm_identity_reconciliation_effect_purchase_link',
                    'local_subscriptions',
                    (object)[
                        'count' => $preview->purchaseupdates,
                        'userid' => $userid,
                    ]
                )
            );
        }

        if ($preview->grantsupdated > 0) {
            $items[] = self::item(
                'fa fa-graduation-cap',
                get_string(
                    'crm_identity_reconciliation_effect_course_access_detail',
                    'local_subscriptions',
                    (object)[
                        'count' => $preview->grantsupdated,
                        'userid' => $userid,
                    ]
                )
            );
        }

        if ($preview->digitalaccessupdated > 0) {
            $items[] = self::item(
                'fa fa-cube',
                get_string(
                    'crm_identity_reconciliation_effect_digital_detail',
                    'local_subscriptions',
                    (object)[
                        'count' => $preview->digitalaccessupdated,
                        'userid' => $userid,
                    ]
                )
            );
        }

        if ($preview->guestsessionsupdated > 0) {
            $items[] = self::item(
                'fa fa-user-o',
                get_string(
                    'crm_identity_reconciliation_effect_guest_detail',
                    'local_subscriptions',
                    (object)[
                        'count' => $preview->guestsessionsupdated,
                        'userid' => $userid,
                    ]
                )
            );
        }

        if ($preview->legacyrecordsupdated > 0) {
            $legacyid = (int)($purchase->legacyid ?? 0);
            $items[] = self::item(
                'fa fa-history',
                get_string(
                    'crm_identity_reconciliation_effect_legacy_detail',
                    'local_subscriptions',
                    (object)[
                        'count' => $preview->legacyrecordsupdated,
                        'legacyid' => $legacyid,
                        'userid' => $userid,
                    ]
                )
            );
        }

        if ($items === []) {
            return html_writer::span(
                get_string(
                    'crm_identity_reconciliation_effect_none',
                    'local_subscriptions'
                ),
                'crm-identity-reconciliation-impact-empty'
            );
        }

        return html_writer::div(
            html_writer::div(
                get_string(
                    'crm_identity_reconciliation_effect_heading',
                    'local_subscriptions'
                ),
                'crm-identity-reconciliation-impact-heading'
            )
            . html_writer::div(
                implode('', $items),
                'crm-identity-reconciliation-impact-list'
            ),
            'crm-identity-reconciliation-impact'
        );
    }

    private static function item(
        string $icon,
        string $label
    ): string {
        return html_writer::div(
            html_writer::tag(
                'i',
                '',
                [
                    'class' => $icon,
                    'aria-hidden' => 'true',
                ]
            )
            . html_writer::span(
                s($label)
            ),
            'crm-identity-reconciliation-impact-item'
        );
    }
}
