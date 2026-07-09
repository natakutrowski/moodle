<?php

namespace local_subscriptions\crm\intelligence\segmentation;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\intelligence\scoring\LeadScore;
use local_subscriptions\crm\intelligence\core\CrmIntelligenceSnapshot;

final class SegmentEngine {

    public function detect(CrmIntelligenceSnapshot $snapshot, LeadScore $score): array {
        $segments = [];

        if ($snapshot->is_customer()) {
            $segments[] = new Segment('customer');
        }

        if ($snapshot->is_trial()) {
            $segments[] = new Segment('trial');
        }

        if ($score->commercial >= 60) {
            $segments[] = new Segment('hot_lead');
        }

        if ($score->risk >= 40) {
            $segments[] = new Segment('at_risk');
        }

        if ($snapshot->total_spent_normalized() >= 250) {
            $segments[] = new Segment('vip');
        }

        $days = $snapshot->days_since_last_activity();

        if ($days !== null && $days >= 30) {
            $segments[] = new Segment('cold_user');
        }

        return $segments;
    }
}