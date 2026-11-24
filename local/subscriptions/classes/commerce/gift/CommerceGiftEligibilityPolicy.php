<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\gift;

defined('MOODLE_INTERNAL') || die();

/**
 * Conservative initial eligibility assessment, no catalogue writes.
 * Result is provisional: current ownership, availability and group capacity
 * must be checked against authoritative services at future checkout time.
 */
final class CommerceGiftEligibilityPolicy {
    private const SAFE_GRANTS = ['reader_access', 'course_access', 'digital_download'];
    private const UNSUPPORTED_OPERATIONS = [
        'upgrade', 'trialconversion', 'promotion_join', 'trial', 'renewal',
    ];

    /** @param string[] $granttypes @param array<string,mixed> $metadata @return string[] blockers */
    public static function blockers(
        string $producttype,
        array $granttypes,
        array $metadata = [],
        bool $haslimitedseats = false
    ): array {
        $producttype = strtolower(trim($producttype));
        $operation = strtolower(trim((string)($metadata['operation'] ?? '')));
        $reasons = [];
        if (!in_array($producttype, ['course_access', 'digital_download', 'service'], true)) {
            $reasons[] = 'unsupported_product_type'; // Bundles require separate unit snapshots.
        }
        if ($operation !== '' && in_array($operation, self::UNSUPPORTED_OPERATIONS, true)) {
            $reasons[] = 'unsupported_operation';
        }
        if (!empty($metadata['is_recurring']) || !empty($metadata['is_trial'])) {
            $reasons[] = 'recurring_or_trial';
        }
        if ($haslimitedseats) {
            $reasons[] = 'limited_capacity_requires_reservation_policy';
        }
        if ($granttypes === []) {
            $reasons[] = 'no_effective_grants';
        }
        foreach ($granttypes as $type) {
            if (!is_string($type) || !in_array(strtolower(trim($type)), self::SAFE_GRANTS, true)) {
                $reasons[] = 'unsupported_grant_type';
                break;
            }
        }
        return array_values(array_unique($reasons));
    }
}
