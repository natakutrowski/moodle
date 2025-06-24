<?php
defined('MOODLE_INTERNAL') || die();

use local_subscriptions\subscription_manager;
use local_subscriptions\subscription_config;

/**
 * Enrol a user manually into a plan with payment and duration handling.
 *
 * @param int $userid
 * @param int $planid
 * @param string $pricecurrency Format: "100|EUR"
 * @param string|null $startdate Optional YYYY-MM-DD
 * @return string 'created' | 'exists'
 * @throws moodle_exception on format error
 */
function local_subscriptions_enrol_user_manual(int $userid, int $planid, string $pricecurrency, ?string $startdate = null): string {
    global $DB;

    $plan = $DB->get_record('subscription_plan', ['id' => $planid], '*', MUST_EXIST);

    $start = $startdate ? strtotime($startdate) : time();
    $end = subscription_manager::get_end_date_from_duration_key($plan->duration_key, $start);

    if (!preg_match('/^([\d\.]+)\|([A-Z]{3})$/', $pricecurrency, $matches)) {
        throw new \moodle_exception('invalidpricecurrency', 'local_subscriptions', '', null, 'Malformed price|currency format.');
    }

    $pricepaid = (float) $matches[1];
    $currency = $matches[2];

    $status = subscription_manager::create_or_extend_subscription(
        $userid,
        $planid,
        subscription_config::PAYMENT_PROVIDER_MANUAL,
        uniqid('manual_'),
        $start,
        $end,
        $pricepaid,
        $currency,
        time()
    );

    if ($status === 'created') {
        subscription_manager::enrol_user_to_courses($userid, $planid, $start, $end);
    }

    return $status;
}

/**
 * Enrol a user into a test plan (named 'test'), used for validation or dev purposes.
 *
 * @param int $userid
 * @return string 'created' | 'exists'
 * @throws moodle_exception if the plan is not found
 */
function local_subscriptions_enrol_user_test(int $userid): string {
    global $DB;

    $planname = 'test';
    $planid = subscription_manager::get_plan_id_by_name($planname);

    if (!$planid) {
        throw new \moodle_exception('plan_not_found', 'local_subscriptions', '', $planname);
    }

    $plan = $DB->get_record('subscription_plan', ['id' => $planid], '*', MUST_EXIST);
    $start = time();
    $end = subscription_manager::get_end_date_from_duration_key($plan->duration_key, $start);

    $status = subscription_manager::create_or_extend_subscription(
        $userid,
        $planid,
        subscription_config::PAYMENT_PROVIDER_DEV,
        uniqid('manual_'),
        $start,
        $end,
        0.00,
        'EUR',
        time()
    );

    if ($status === 'created') {
        subscription_manager::enrol_user_to_courses($userid, $planid, $start, $end);
    }

    return $status;
}

function handle_post_actions(): array {
    global $DB;
    $updated = 0;
    $deleted = 0;

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['selected'])) {
        $selected = $_POST['selected'];

        if (isset($_POST['save'])) {
            foreach ($selected as $subid) {
                $plan = $_POST['plan'][$subid] ?? null;
                $start = $_POST['start'][$subid] ?? null;
                $end = $_POST['end'][$subid] ?? null;

                if ($plan && $start && $end) {
                    $starttimestamp = strtotime($start);
                    $endtimestamp = strtotime($end);

                    $subscription = $DB->get_record('user_subscription', ['id' => $subid], '*', IGNORE_MISSING);
                    if ($subscription) {
                        $subscription->planid = $plan;
                        $subscription->start_date = $starttimestamp;
                        $subscription->end_date = $endtimestamp;
                        $subscription->last_update = time();
                        $DB->update_record('user_subscription', $subscription);
                        $updated++;
                    }
                }
            }
            \core\notification::success(get_string('updated_subscriptions', 'local_subscriptions', $updated));
        }

        if (isset($_POST['delete'])) {
            foreach ($selected as $subid) {
                $subscription = $DB->get_record('user_subscription', ['id' => $subid], '*', IGNORE_MISSING);
                if (!$subscription) continue;

                subscription_manager::unenrol_user_from_plan($subscription->userid, $subscription->planid);
                $DB->delete_records('user_subscription', ['id' => $subid]);
                $deleted++;
            }
            \core\notification::success(get_string('delete_subscriptions', 'local_subscriptions', $deleted));
        }
    }

    return [$updated, $deleted];
}
