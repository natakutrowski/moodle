<?php

namespace local_subscriptions\crm\intelligence\alerts;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\intelligence\core\UserIntelligence;

final class CrmAlertEngine {

    public function detect(\stdClass $user, UserIntelligence $intelligence): array {
        $snapshot = $intelligence->snapshot;
        $score = $intelligence->leadScore;
        $alerts = [];

        if ($score->risk >= 60) {
            $alerts[] = new CrmAlert('high_risk_user', 'danger', 95, (int)$user->id);
        }

        if ($snapshot->trialSubscriptions > 0 && $snapshot->paidSubscriptions === 0) {
            $alerts[] = new CrmAlert('trial_without_purchase', 'warning', 85, (int)$user->id);
        }

        if ($snapshot->expiredSubscriptions > 0 && $snapshot->activeSubscriptions === 0) {
            $alerts[] = new CrmAlert('expired_without_reactivation', 'warning', 80, (int)$user->id);
        }

        if ($snapshot->days_since_last_activity() !== null && $snapshot->days_since_last_activity() >= 30) {
            $alerts[] = new CrmAlert('inactive_user', 'warning', 70, (int)$user->id);
        }

        if ($score->commercial >= 60 && $score->risk <= 20) {
            $alerts[] = new CrmAlert('hot_opportunity', 'success', 75, (int)$user->id);
        }

        usort($alerts, static fn($a, $b): int => $b->priority <=> $a->priority);

        return $alerts;
    }
}