<?php

namespace local_subscriptions\service;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\mailer;
use local_subscriptions\admin\AdminLog;
use local_subscriptions\constants\Status;
use local_subscriptions\commerce\read\email\CommerceEmailReadGateway;

final class UserSubscriptionEmailService {

    public static function resend_welcome_email(int $userid, int $subscriptionid): void {
        [$user, $sub, $plan, $pr] = self::load_context($userid, $subscriptionid, false);

        mailer::dispatch(mailer::T_WELCOME, [
            'user' => $user,
            'plan' => $plan,
            'pr' => $pr,
            'sub' => $sub,
            'tmpPassword' => '',
        ]);

        AdminLog::emailWelcomeSent($userid, $subscriptionid, [
            'plan' => $plan->name ?? '',
            'provider' => $pr->payment_provider ?? '',
            'transactionid' => $pr->transactionid ?? '',
        ]);
    }

    public static function resend_access_email(int $userid, int $subscriptionid): void {
        [$user, $sub, $plan, $pr] = self::load_context($userid, $subscriptionid, false);

        mailer::dispatch(mailer::T_SUBSCRIPTION_UPDATED, [
            'user' => $user,
            'plan' => $plan,
            'pr' => $pr,
            'sub' => $sub,
        ]);

        AdminLog::emailSubscriptionAccessSent($userid, $subscriptionid, [
            'emailtype' => 'subscription_access',
            'plan' => $plan->name ?? '',
            'provider' => $pr->payment_provider ?? '',
            'transactionid' => $pr->transactionid ?? '',
        ]);
    }

    public static function resend_receipt(int $userid, int $subscriptionid): void {
        [$user, $sub, $plan, $pr] = self::load_context($userid, $subscriptionid, true);

        mailer::dispatch(mailer::T_RECEIPT, [
            'user' => $user,
            'plan' => $plan,
            'pr' => $pr,
            'sub' => $sub,
        ]);

        AdminLog::emailReceiptSent($userid, $subscriptionid, (int)$pr->id, [
            'plan' => $plan->name ?? '',
            'amount' => $pr->price ?? ($pr->locked_final_price ?? ''),
            'currency' => $pr->currency ?? '',
            'provider' => $pr->payment_provider ?? '',
            'transactionid' => $pr->transactionid ?? '',
        ]);
    }

    public static function extend_subscription(int $userid, int $subscriptionid, int $days): void {
        global $DB;

        $sub = $DB->get_record('user_subscription', [
            'id' => $subscriptionid,
            'userid' => $userid,
        ], '*', MUST_EXIST);

        $plan = $DB->get_record('subscription_plan', ['id' => $sub->planid], '*', IGNORE_MISSING);

        $oldend = (int)$sub->end_date;
        $base = $oldend > time() ? $oldend : time();

        $sub->end_date = $base + max(1, $days) * DAYSECS;
        $sub->status = Status::ACTIVE;
        $sub->last_update = time();

        $DB->update_record('user_subscription', $sub);

        \local_subscriptions\subscription_manager::enrol_user_to_courses(
            (int)$sub->userid,
            (int)$sub->planid,
            (int)$sub->start_date,
            (int)$sub->end_date
        );

        AdminLog::subscriptionExtended($sub, $plan ?: null, [
            'end_date' => [
                'from' => \local_subscriptions\admin\AdminFormatter::date($oldend),
                'to' => \local_subscriptions\admin\AdminFormatter::date((int)$sub->end_date),
            ],
        ]);
    }

    private static function load_context(int $userid, int $subscriptionid, bool $receiptrequired): array {
        global $DB;

        (new CommerceEmailReadGateway())->inspect_subscription($subscriptionid);

        $user = \core_user::get_user($userid, '*', MUST_EXIST);
        $user->mailformat = 1;

        $sub = $DB->get_record('user_subscription', [
            'id' => $subscriptionid,
            'userid' => $userid,
        ], '*', MUST_EXIST);

        $plan = $DB->get_record('subscription_plan', ['id' => $sub->planid], '*', MUST_EXIST);

        $pr = self::find_payment_request($sub);

        if ($pr === null && $receiptrequired) {
            throw new \moodle_exception('crm_receipt_not_available', 'local_subscriptions');
        }

        if ($pr === null) {
            $pr = (object)[
                'id' => 0,
                'userid' => $userid,
                'planid' => $sub->planid,
                'email' => $user->email,
                'price' => $sub->pricepaid ?? 0,
                'currency' => $sub->currency ?? '',
                'payment_provider' => $sub->payment_provider ?? '',
                'transactionid' => $sub->transactionid ?? '',
            ];
        }

        return [$user, $sub, $plan, $pr];
    }

    private static function find_payment_request(\stdClass $sub): ?\stdClass {
        global $DB;

        /*
        * A manually created subscription legitimately has no payment request.
        *
        * Only use explicit links that identify the payment request belonging
        * to this exact subscription. Never fall back to userid + planid,
        * because that could associate an older unrelated payment request.
        */
        if (
            $DB->get_manager()->field_exists(
                'subscription_payment_request',
                'subscriptionid'
            )
        ) {
            $records = $DB->get_records(
                'subscription_payment_request',
                [
                    'subscriptionid' => (int)$sub->id,
                ],
                'id DESC',
                '*',
                0,
                1
            );

            if ($records) {
                $pr = reset($records);

                return $pr instanceof \stdClass
                    ? $pr
                    : null;
            }
        }

        if (!empty($sub->transactionid)) {
            $pr = $DB->get_record(
                'subscription_payment_request',
                [
                    'transactionid' =>
                        (string)$sub->transactionid,
                ],
                '*',
                IGNORE_MISSING
            );

            return $pr ?: null;
        }

        return null;
    }
}