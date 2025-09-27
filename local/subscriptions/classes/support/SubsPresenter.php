<?php
namespace local_subscriptions\support;
defined('MOODLE_INTERNAL') || die();

final class SubsPresenter {

    private static function format_end(?int $ts): string {
        if (empty($ts)) {
            return get_string('subfield_unlimited', 'local_subscriptions'); // 'Sans fin'
        }
        return userdate((int)$ts);
    }

    private static function yesno(bool $v): string {
        return $v ? get_string('yes') : get_string('no');
    }

    private static function label_status(\stdClass $sub): string {
        $status = (string)($sub->status ?? '');
        $key = 'substatus_'.$status;
        return get_string_manager()->string_exists($key, 'local_subscriptions')
            ? get_string($key, 'local_subscriptions')
            : s($status);
    }

    /** Construit les lignes (label, value) d'une souscription. */
    public static function rows(\stdClass $sub, \stdClass $plan, callable $fmtmoney, string $view='user'): array {
        $rows = [];
        $isadmin = ($view === 'admin');

        // USER VIEW (publique)
        if (!$isadmin) {
            // Plan
            $rows[] = [get_string('plan', 'local_subscriptions'), format_string($plan->name ?? '')];
            // Statut (traduit)
            $rows[] = [get_string('status', 'local_subscriptions'), self::label_status($sub)];
            // Période (Start → End)
            $period = userdate((int)$sub->start_date).' → '.self::format_end($sub->end_date ?? null);
            $rows[] = [get_string('period', 'local_subscriptions'), $period];
            // Montant payé (avec devise)
            $amount = (float)($sub->pricepaid ?? 0);
            $currency = strtoupper((string)($sub->currency ?? ''));
            $rows[] = [get_string('subfield_amount', 'local_subscriptions'), $fmtmoney($amount, $currency)];

            // Statut paiement (simple)
            if (!empty($sub->payment_failed)) {
                $rows[] = [
                    get_string('subfield_payment_status', 'local_subscriptions'),
                    get_string('subpayment_action', 'local_subscriptions') // "Action requise"
                ];
            } else {
                $rows[] = [
                    get_string('subfield_payment_status', 'local_subscriptions'),
                    get_string('subpayment_ok', 'local_subscriptions') // "À jour"
                ];
            }

            if (!empty($sub->payment_provider)) {
                $rows[] = [get_string('subfield_provider', 'local_subscriptions'), s($sub->payment_provider)];
            }

            // Dernière mise à jour (facultatif, utile UX)
            $rows[] = [get_string('subfield_updated', 'local_subscriptions'), userdate((int)$sub->last_update)];
            return $rows;
        }

        // ADMIN VIEW (technique)
        $rows[] = [get_string('subfield_id', 'local_subscriptions'), (string)$sub->id];
        $rows[] = [get_string('subfield_userid', 'local_subscriptions'), (string)$sub->userid];
        $rows[] = [get_string('subfield_planid', 'local_subscriptions'), (string)$sub->planid];

        $rows[] = [get_string('plan', 'local_subscriptions'), format_string($plan->name ?? '')];
        $rows[] = [get_string('status', 'local_subscriptions'), self::label_status($sub)];
        $rows[] = [get_string('subfield_start', 'local_subscriptions'), userdate((int)$sub->start_date)];
        $rows[] = [get_string('subfield_end', 'local_subscriptions'), self::format_end($sub->end_date ?? null)];

        $amount = (float)($sub->pricepaid ?? 0);
        $currency = strtoupper((string)($sub->currency ?? ''));
        $rows[] = [get_string('subfield_amount', 'local_subscriptions'), $fmtmoney($amount, $currency)];

        if (!empty($sub->transactionid)) {
            $rows[] = [get_string('subfield_txn', 'local_subscriptions'), s($sub->transactionid)];
        }
        if (!empty($sub->payment_provider)) {
            $rows[] = [get_string('subfield_provider', 'local_subscriptions'), s($sub->payment_provider)];
        }
        if (!empty($sub->provider_subscription_id)) {
            $rows[] = [get_string('subfield_provider_sub', 'local_subscriptions'), s($sub->provider_subscription_id)];
        }
        if (!empty($sub->provider_customer_id)) {
            $rows[] = [get_string('subfield_provider_customer', 'local_subscriptions'), s($sub->provider_customer_id)];
        }
        if (!empty($sub->last_invoice_id)) {
            $rows[] = [get_string('subfield_last_invoice', 'local_subscriptions'), s($sub->last_invoice_id)];
        }

        $rows[] = [get_string('payment_failed', 'local_subscriptions'), self::yesno(!empty($sub->payment_failed))];
        $rows[] = [get_string('subfield_last_failed_at', 'local_subscriptions'),
                   !empty($sub->last_payment_failed_at) ? userdate((int)$sub->last_payment_failed_at) : '-'];
        if (!empty($sub->last_payment_failed_reason)) {
            $rows[] = [get_string('subfield_fail_reason', 'local_subscriptions'), s($sub->last_payment_failed_reason)];
        }

        $rows[] = [get_string('subfield_created', 'local_subscriptions'), userdate((int)$sub->creation_date)];
        $rows[] = [get_string('subfield_updated', 'local_subscriptions'), userdate((int)$sub->last_update)];

        return $rows;
    }
}
