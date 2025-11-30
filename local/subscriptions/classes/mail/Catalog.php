<?php
namespace local_subscriptions\mail;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\mailer as Mailer;

final class Catalog {

    /* ===== Abonnement : évènements canoniques ===== */
    public static function welcome(\stdClass $user, string $tmpPassword, \stdClass $plan, \stdClass $pr, \stdClass $sub): void {
        Mailer::send_welcome($user, $tmpPassword, $plan, $pr,  $sub);
    }
    public static function upgrade_confirmed(\stdClass $user, \stdClass $plan, \stdClass $pr, \stdClass $sub): void {
        Mailer::send_upgrade_confirmation($user, $plan, $pr, $sub);
    }
    public static function subscription_updated(\stdClass $user, \stdClass $plan, \stdClass $pr, \stdClass $sub): void {
        Mailer::send_subscription_update($user, $plan, $pr, $sub);
    }

    /* ===== Paiements ===== */
    public static function receipt(\stdClass $user, \stdClass $plan, \stdClass $pr, \stdClass $sub): void {
        Mailer::send_receipt($user, $plan, $pr, $sub);
    }
    public static function recurring_started(\stdClass $user, \stdClass $plan, \stdClass $pr): void {
        Mailer::send_recurring_started($user, $plan, $pr);
    }
    public static function payment_failed(\stdClass $pr): void {
        Mailer::send_failed($pr);
    }
    public static function payment_abandoned(\stdClass $pr): void {
        Mailer::send_abandoned($pr);
    }

    /* ===== Cycle d’abonnement ===== */
    public static function renewal_ok(\stdClass $user, \stdClass $plan, \stdClass $sub, ?float $amount, ?string $currency, ?string $invoiceid, ?int $oldend): void {
        Mailer::send_renewal_ok($user, $plan, $sub, $amount, $currency, $invoiceid, $oldend);
    }
    public static function failed_recurring(\stdClass $user, \stdClass $plan, array $opts = []): void {
        Mailer::send_failed_recurring($user, $plan, $opts);
    }
    public static function cancellation_info(\stdClass $user, \stdClass $plan, \stdClass $sub, ?int $atperiodend): void {
        Mailer::send_cancellation_info($user, $plan, $sub, $atperiodend);
    }
    public static function subscription_activated(\stdClass $user, \stdClass $plan, \stdClass $sub): void {
        Mailer::send_subscription_activated($user, $plan, $sub);
    }
    public static function subscription_expired(\stdClass $user, \stdClass $plan, \stdClass $sub): void {
        Mailer::send_subscription_expired($user, $plan, $sub);
    }
    public static function subscription_expiry_reminder(\stdClass $user, \stdClass $plan, \stdClass $sub, int $daysleft): void {
        Mailer::send_subscription_expiry_reminder($user, $plan, $sub, $daysleft);
    }

    /* ===== Rappels de paiement (PR) ===== */
    public static function send_reminder(\stdClass $pr): void {
        Mailer::send_reminder($pr);
    }
    public static function send_reminder_second(\stdClass $pr): void {
        Mailer::send_reminder_second($pr);
    }
    /* ===== Contact ===== */
    public static function contact_admin(string $toemail, string $fullname, string $fromemail, string $message, array $meta = []): void {
        Mailer::send_contact_admin($toemail, $fullname, $fromemail, $message, $meta);
    }
    public static function contact_ack(string $toemail, string $fullname, string $message, ?string $fromsupport = null): void {
        Mailer::send_contact_ack($toemail, $fullname, $message, $fromsupport);
    }

    /* ===== Essai ===== */
    public static function trial_started(array $p): void {
        Mailer::send_trial_started($p);
    }
    public static function trial_rem3(array $p): void {
        Mailer::send_trial_rem3($p);
    }
    public static function trial_pre_suspend(array $p): void {
        Mailer::send_trial_pre_suspend($p);
    }
    public static function trial_suspended(array $p): void {
        Mailer::send_trial_suspended($p);
    }
    public static function trial_expired(array $p): void {
        Mailer::send_trial_expired($p);
    }
}
