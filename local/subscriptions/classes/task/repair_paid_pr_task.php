<?php
namespace local_subscriptions\task;

class repair_paid_pr_task extends \core\task\scheduled_task {
    public function get_name() {
        return get_string('task_repair_paid_pr', 'local_subscriptions');
    }
    public function execute() {
        global $DB;

        // PR payées, sans sub liée, depuis > 60s (pour laisser passer le webhook)
        $prs = $DB->get_records_select('subscription_payment_request',
            "status = 'paid' AND (subscriptionid IS NULL OR subscriptionid = 0) AND payment_date <= :t",
            ['t' => time() - 60], 'payment_date ASC',
            'id, planid, userid, email, firstname, lastname, currency, price, operation, reference_subscription_id, payment_provider');

        foreach ($prs as $pr) {
            try {
                // reconstruit l'utilisateur (par email si besoin)
                $user = null;
                if (!empty($pr->userid)) {
                    $user = $DB->get_record('user', ['id'=>$pr->userid, 'deleted'=>0], '*', IGNORE_MISSING);
                }
                if (!$user && !empty($pr->email)) {
                    $user = $DB->get_record('user', ['email'=>\core_text::strtolower($pr->email), 'deleted'=>0], '*', IGNORE_MISSING);
                }
                if (!$user) { continue; }

                $plan = $DB->get_record('subscription_plan', ['id'=>$pr->planid, 'is_active'=>1], '*', IGNORE_MISSING);
                if (!$plan) { continue; }

                // helper durée
                $add = function(int $ts, string $duration_key): int {
                    $dt = new \DateTime('@'.$ts);
                    $dt->setTimezone(new \DateTimeZone('UTC'));
                    switch ($duration_key) {
                        case '1month':  $dt->modify('+1 month'); break;
                        case '3months': $dt->modify('+3 months'); break;
                        case '6months': $dt->modify('+6 months'); break;
                        case '1year':   $dt->modify('+1 year'); break;
                        case '2years':  $dt->modify('+2 years'); break;
                        case '3years':  $dt->modify('+3 years'); break;
                        default:        $dt->modify('+1 year');
                    }
                    return $dt->getTimestamp();
                };

                // reconstruit selon operation
                $op   = $pr->operation ?? 'purchase_new';
                $ref  = (int)($pr->reference_subscription_id ?? 0);

                if ($op === 'queue_future') {
                    // ancre = max(end des active/queued)
                    $anchor = (int)$DB->get_field_sql(
                        "SELECT COALESCE(MAX(end_date), 0) FROM {user_subscription}
                          WHERE userid = :u AND planid = :p AND status IN ('active','queued')",
                        ['u'=>$user->id, 'p'=>$plan->id]
                    );
                    // si ref fournie → anchor = max(anchor, end(ref))
                    if ($ref) {
                        $refsub = $DB->get_record('user_subscription', ['id'=>$ref,'userid'=>$user->id], 'end_date,planid', IGNORE_MISSING);
                        if ($refsub && (int)$refsub->planid === (int)$plan->id) {
                            $anchor = max($anchor, (int)$refsub->end_date);
                        }
                    }
                    $start = max($anchor + 1, time());
                    $end   = $add($start, $plan->duration_key);

                    // idempotence
                    if ($DB->record_exists('user_subscription', [
                        'userid'=>$user->id,'planid'=>$plan->id,'start_date'=>$start,'status'=>'queued'
                    ])) {
                        continue;
                    }

                    // création
                    $sub = (object)[
                        'userid'=>$user->id, 'planid'=>$plan->id,
                        'payment_provider'=>$pr->payment_provider ?? 'stripe',
                        'start_date'=>$start, 'end_date'=>$end,
                        'status'=> ($start > time() ? 'queued':'active'),
                        'creation_date'=>time(), 'last_update'=>time(),
                        'pricepaid'=>(float)($pr->price ?? 0),
                        'currency'=>$pr->currency ?? '',
                        'transactionid'=>$pr->transactionid ?? null
                    ];
                    $subid = $DB->insert_record('user_subscription', $sub);
                    if (\local_subscriptions\domain\PaymentService::db_field_exists('subscription_payment_request','subscriptionid')) {
                        $pr->subscriptionid = $subid;
                        $DB->update_record('subscription_payment_request', $pr);
                    }
                } else {
                    // purchase_new
                    $start = time();
                    $end   = $add($start, $plan->duration_key);

                    if ($DB->record_exists('user_subscription', [
                        'userid'=>$user->id,'planid'=>$plan->id,'start_date'=>$start,'status'=>'active'
                    ])) {
                        continue;
                    }

                    $sub = (object)[
                        'userid'=>$user->id,'planid'=>$plan->id,
                        'payment_provider'=>$pr->payment_provider ?? 'stripe',
                        'start_date'=>$start,'end_date'=>$end,'status'=>'active',
                        'creation_date'=>time(),'last_update'=>time(),
                        'pricepaid'=>(float)($pr->price ?? 0),'currency'=>$pr->currency ?? '',
                        'transactionid'=>$pr->transactionid ?? null
                    ];
                    $subid = $DB->insert_record('user_subscription', $sub);
                    if (\local_subscriptions\domain\PaymentService::db_field_exists('subscription_payment_request','subscriptionid')) {
                        $pr->subscriptionid = $subid;
                        $DB->update_record('subscription_payment_request', $pr);
                    }
                }
            } catch (\Throwable $ex) {
                mtrace('[local_subscriptions][repair_paid_pr] PR#'.$pr->id.' : '.$ex->getMessage());
            }
        }
    }
}
