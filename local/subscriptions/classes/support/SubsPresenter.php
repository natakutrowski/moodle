<?php
namespace local_subscriptions\support;
defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/../../lib/plans_lib.php');

use local_subscriptions\payment\Provider;
use local_subscriptions\constants\Status;

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
        global $DB;

        $rows = [];
        $isadmin = ($view === 'admin');

        $planname = local_subscriptions_plan_display_name($plan);

        // USER VIEW (publique)
        if (!$isadmin) {
            // Plan
            $rows[] = [get_string('plan', 'local_subscriptions'), format_string($planname)];
            // Statut (traduit)
            $rows[] = [get_string('status', 'local_subscriptions'), self::render_status_badge($sub->status)];
            // Période (Start → End)
            $period = userdate((int)$sub->start_date).' → '.self::format_end($sub->end_date ?? null);
            $rows[] = [get_string('period', 'local_subscriptions'), $period];
            // Montant payé (avec devise)
            $amount = (float)($sub->pricepaid ?? 0);
            $currency = strtoupper((string)($sub->currency ?? ''));
            $rows[] = [get_string('subfield_amount', 'local_subscriptions'), $fmtmoney($amount, $currency)];

            // Statut de paiement (badge)
            $rows[] = [
                get_string('subfield_payment_status', 'local_subscriptions'),
                self::render_payment_badge($sub)
            ];

            if (!empty($sub->payment_provider)) {
                $rows[] = [get_string('subfield_provider', 'local_subscriptions'), Provider::label_with_icon($sub->payment_provider)];
            }

            // Dernière mise à jour (facultatif, utile UX)
            $rows[] = [get_string('subfield_updated', 'local_subscriptions'), userdate((int)$sub->last_update)];
            return $rows;
        }

        // ADMIN VIEW (technique)
        $rows[] = [get_string('subfield_id', 'local_subscriptions'), (string)$sub->id];
        $rows[] = [get_string('subfield_userid', 'local_subscriptions'), (string)$sub->userid];
        $rows[] = [get_string('subfield_planid', 'local_subscriptions'), (string)$sub->planid];

        $rows[] = [get_string('plan', 'local_subscriptions'), format_string($planname)];
        $rows[] = [get_string('status', 'local_subscriptions'), self::render_status_badge($sub->status)];
        $rows[] = [get_string('subfield_start', 'local_subscriptions'), userdate((int)$sub->start_date)];
        $rows[] = [get_string('subfield_end', 'local_subscriptions'), self::format_end($sub->end_date ?? null)];

        $amount = (float)($sub->pricepaid ?? 0);
        $currency = strtoupper((string)($sub->currency ?? ''));
        $rows[] = [get_string('subfield_amount', 'local_subscriptions'), $fmtmoney($amount, $currency)];

        if (!empty($sub->transactionid)) {
            $rows[] = [get_string('subfield_txn', 'local_subscriptions'), s($sub->transactionid)];
        }
        if (!empty($sub->payment_provider)) {
            $rows[] = [get_string('subfield_provider', 'local_subscriptions'), Provider::label_with_icon_env($sub->payment_provider)];
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

        // --- Bloc Payment Request (PR) lié ------------------------------------
        // 1) PR liée directement à cette sub
        $pr = $DB->get_record('subscription_payment_request',
            ['subscriptionid' => $sub->id],
            '*',
            IGNORE_MISSING
        );

        // 2) Sinon, dernière PR "paid/completed" pour le même user/plan
        if (!$pr) {
            $sql = "SELECT pr.*
                    FROM {subscription_payment_request} pr
                    WHERE pr.userid = :uid
                    AND pr.planid = :planid
                    AND pr.status IN ('".Status::PAID."','".Status::COMPLETED."')
                ORDER BY pr.payment_date DESC, pr.id DESC";
            $pr = $DB->get_record_sql($sql, ['uid' => $sub->userid, 'planid' => $sub->planid]);
        }

        if ($pr) {

            // 1 ou 2 espaces insécables (U+00A0). Mets 1 ou 2 selon l’effet souhaité.
            $prindent = str_repeat("\u{00A0}", 3); // ou: $prindent = "\xC2\xA0\xC2\xA0";

            $rows[] = [get_string('subfield_pr_id', 'local_subscriptions'), (string)$pr->id];
            $rows[] = [$prindent . get_string('subfield_pr_status', 'local_subscriptions'), self::render_status_badge(s($pr->status))];
            if (!empty($pr->payment_provider)) {
                $rows[] = [$prindent . get_string('subfield_pr_provider', 'local_subscriptions'), Provider::label_with_icon_env($pr->payment_provider)];
            }
            // Montant PR
            $prccy = strtoupper((string)($pr->currency ?? ''));
            $pramt = $fmtmoney((float)$pr->price, $prccy);
            $rows[] = [$prindent . get_string('subfield_pr_amount', 'local_subscriptions'), $pramt];

            if (!empty($pr->sessionid)) {
                $rows[] = [$prindent . get_string('subfield_pr_orderid', 'local_subscriptions'), s($pr->sessionid)];
            }
            if (!empty($pr->transactionid)) {
                $rows[] = [$prindent . get_string('subfield_pr_txnid', 'local_subscriptions'), s($pr->transactionid)];
            }
            $rows[] = [
                $prindent . get_string('subfield_pr_paidat', 'local_subscriptions'),
                !empty($pr->payment_date) ? userdate((int)$pr->payment_date) : '-'
            ];
            if (!empty($pr->payment_link)) {
                $rows[] = [$prindent . get_string('subfield_pr_link', 'local_subscriptions'), s($pr->payment_link)];
            }
            if (!empty($pr->last_error)) {
                $rows[] = [$prindent . get_string('subfield_pr_lasterror', 'local_subscriptions'), s($pr->last_error)];
            }
        } else {
            // Pas de PR retrouvée (info utile en debug)
            $rows[] = [get_string('subfield_pr_id', 'local_subscriptions'), get_string('notavailable', 'local_subscriptions')];
        }

        return $rows;
    }

    /** Rend un badge Bootstrap pour un statut de souscription. */
    public static function render_status_badge(string $status): string {
        $s = \core_text::strtolower(trim($status));

        // mapping : statut → classes bootstrap et libellé i18n
        $map = [
            Status::ACTIVE   => ['cls' => 'crm-commerce-status crm-commerce-status--success badge bg-success',             'str' => 'status_active'],
            Status::QUEUED   => ['cls' => 'crm-commerce-status crm-commerce-status--neutral badge bg-secondary',           'str' => 'status_queued'],
            Status::EXPIRED  => ['cls' => 'crm-commerce-status crm-commerce-status--warning badge bg-warning text-dark',   'str' => 'status_expired'],
            Status::REPLACED => ['cls' => 'crm-commerce-status crm-commerce-status--warning badge bg-warning text-dark',   'str' => 'status_replaced'],
            // optionnels (au cas où tu les utilises déjà) :
            Status::PENDING  => ['cls' => 'crm-commerce-status crm-commerce-status--info badge bg-info',                'str' => 'status_pending'],
            Status::CANCELED => ['cls' => 'crm-commerce-status crm-commerce-status--neutral badge bg-dark',                'str' => 'status_canceled'],
            Status::ERROR    => ['cls' => 'crm-commerce-status crm-commerce-status--danger badge bg-danger',              'str' => 'status_error'],
            Status::SUSPENDED => ['cls' => 'crm-commerce-status crm-commerce-status--neutral badge bg-light text-dark',    'str' => 'status_suspended'],
            Status::PAID     => ['cls' => 'crm-commerce-status crm-commerce-status--success badge bg-success',             'str' => 'status_paid'],
            Status::COMPLETED => ['cls' => 'crm-commerce-status crm-commerce-status--success badge bg-success',            'str' => 'status_completed'],
            Status::FAILED    => ['cls' => 'crm-commerce-status crm-commerce-status--danger badge bg-danger',             'str' => 'status_failed'],
        ];

        $conf = $map[$s] ?? ['cls' => 'crm-commerce-status crm-commerce-status--neutral badge bg-light text-dark', 'str' => 'status_unknown'];
        $label = get_string('sub'.$conf['str'], 'local_subscriptions');
        return \html_writer::span($label, $conf['cls'].' ls-status-badge');
    }

    /** Rend le statut de paiement sous forme de badge (PAID/ERROR) + action si erreur. */
    private static function render_payment_badge(\stdClass $sub): string {
        $haserror = !empty($sub->payment_failed);

        // Libellés i18n si présents, sinon fallback "PAID/ERROR".
        $key = $haserror ? 'substatus_error' : 'substatus_paid';
        $label = get_string($key, 'local_subscriptions');

        $cls = $haserror ? 'badge bg-danger' : 'badge bg-success';
        $html = \html_writer::span($label, $cls.' ls-status-badge');

        // Cas erreur : ajouter la consigne d’action sous le badge.
        if ($haserror) {
            $action = get_string('subpayment_action', 'local_subscriptions');
            $html .= \html_writer::div($action, 'text-danger small mt-1');
        }

        return $html;
    }


}
