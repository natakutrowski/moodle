<?php

namespace local_subscriptions\crm\intelligence\core;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\user\UserProfileRepository;

final class CrmIntelligenceRepository {

    public function __construct(
        private readonly UserProfileRepository $userRepository = new UserProfileRepository()
    ) {
    }

    public function snapshot_for_user(\stdClass $user): CrmIntelligenceSnapshot {
        $userid = (int)$user->id;

        $subscriptions = $this->userRepository->get_subscriptions($userid);
        $digitalpayments = $this->userRepository->get_digital_payments($userid, (string)$user->email, 100);
        $tags = $this->userRepository->get_user_tags($userid, 100);
        $notes = $this->userRepository->get_user_notes($userid, 100);

        return new CrmIntelligenceSnapshot(
            $userid,
            !empty($user->suspended),
            (int)($user->timecreated ?? 0),
            (int)($user->lastaccess ?? 0),
            $this->userRepository->last_activity($userid),
            $this->count_subscription_status($subscriptions, 'active'),
            $this->count_subscription_status($subscriptions, 'trial'),
            $this->count_subscription_status($subscriptions, 'expired'),
            $this->count_paid_subscriptions($subscriptions),
            $this->count_paid_digital_purchases($digitalpayments),
            $this->userRepository->sum_spent_by_currency($userid, 'EUR'),
            $this->userRepository->sum_spent_by_currency($userid, 'RUB'),
            count($notes),
            count($tags),
            array_map(static fn($tag): string => (string)$tag->tag, $tags)
        );
    }

    private function count_subscription_status(array $subscriptions, string $status): int {
        return count(array_filter(
            $subscriptions,
            static fn($subscription): bool => strtolower((string)($subscription->status ?? '')) === $status
        ));
    }

    private function count_paid_subscriptions(array $subscriptions): int {
        return count(array_filter(
            $subscriptions,
            static fn($subscription): bool => in_array(
                strtolower((string)($subscription->status ?? '')),
                ['active', 'expired', 'cancelled', 'replaced'],
                true
            )
        ));
    }

    private function count_paid_digital_purchases(array $payments): int {
        return count(array_filter(
            $payments,
            static fn($payment): bool => in_array(
                strtoupper((string)($payment->status ?? '')),
                ['PAID', 'COMPLETED'],
                true
            )
        ));
    }
}