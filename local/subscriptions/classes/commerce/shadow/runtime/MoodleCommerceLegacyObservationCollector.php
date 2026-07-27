<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\shadow\runtime;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\shadow\CommerceProjectedPurchaseShadowGrantSource;
use local_subscriptions\commerce\shadow\CommerceShadowGrantSource;
use local_subscriptions\commerce\shadow\CommerceLegacyFulfillmentObservation;
use local_subscriptions\commerce\shadow\CommerceShadowEffect;

/** Read-only Moodle observation of effects already applied by Legacy fulfillment. */
final class MoodleCommerceLegacyObservationCollector implements CommerceLegacyObservationCollector {
    public function __construct(
        private readonly CommerceShadowGrantSource $grants = new CommerceProjectedPurchaseShadowGrantSource()
    ) {
    }

    public function collect(string $purchasereference, string $source): CommerceLegacyFulfillmentObservation {
        $effects = [];
        $issues = [];
        foreach ($this->grants->find_for_purchase($purchasereference) as $grant) {
            $attributes = $this->observe($grant->get_type(), $grant->get_resource_key(), $grant->get_beneficiary_user_id());
            if ($attributes === null) {
                $issues[] = ['grantreference' => $grant->get_reference(), 'reason' => 'unsupported_legacy_observation'];
                continue;
            }
            $effects[] = new CommerceShadowEffect(
                $grant->get_reference(),
                $grant->get_type(),
                $grant->get_resource_key(),
                $grant->get_beneficiary_user_id(),
                $grant->get_beneficiary_email(),
                $attributes
            );
        }
        return new CommerceLegacyFulfillmentObservation(
            $purchasereference,
            $source,
            $effects,
            $issues === [],
            $issues
        );
    }

    private function observe(string $type, string $resourcekey, ?int $userid): ?array {
        global $DB;
        if ($type === 'course_access') {
            if ($userid === null || !preg_match('/^course:(\d+):([^:]+)$/', $resourcekey, $matches)) {
                return null;
            }
            $courseid = (int) $matches[1];
            $context = \context_course::instance($courseid, IGNORE_MISSING);
            if (!$context) {
                return ['courseid' => $courseid, 'active' => false];
            }
            return [
                'courseid' => $courseid,
                'userid' => $userid,
                'accesslevel' => $matches[2],
                'active' => is_enrolled($context, $userid, '', true),
            ];
        }
        if ($type === 'digital_download') {
            $record = $DB->get_record('subscription_digital_payment_request', [
                'email' => $this->email_for_user($userid),
                'status' => 'paid',
            ], 'id,download_token', IGNORE_MULTIPLE);
            return ['resourcekey' => $resourcekey, 'active' => $record !== false && !empty($record->download_token)];
        }
        return null;
    }

    private function email_for_user(?int $userid): string {
        global $DB;
        if ($userid === null) {
            return '';
        }
        return (string) $DB->get_field('user', 'email', ['id' => $userid], IGNORE_MISSING);
    }
}
