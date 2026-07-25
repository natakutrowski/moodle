<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\student;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\persistence\sql\CommercePurchaseSqlRepository;
use local_subscriptions\commerce\runtime\read\CommerceRuntimeReadMode;

/** I9 runtime facade for student purchase screens. */
final class StudentCommercePurchaseService {
    public function __construct(
        private readonly CommercePurchaseSqlRepository $repository,
        private readonly NativeStudentCommercePurchaseMapper $mapper,
        private readonly ?string $modeoverride = null,
        private readonly ?bool $strictoverride = null
    ) {
    }

    public function get_for_customer(int $userid, string $email): StudentCommercePurchaseCollection {
        $mode = $this->modeoverride ?? (string)get_config('local_subscriptions', 'commerce_runtime_read_mode');
        $mode = CommerceRuntimeReadMode::normalise($mode === '' ? CommerceRuntimeReadMode::LEGACY : $mode);
        $strict = $this->strictoverride ?? !empty(get_config('local_subscriptions', 'commerce_runtime_read_strict'));

        $legacy = null;
        $native = null;
        if (in_array($mode, [CommerceRuntimeReadMode::LEGACY, CommerceRuntimeReadMode::SHADOW, CommerceRuntimeReadMode::AUTO], true)) {
            $legacy = $this->load_legacy($userid, $email);
        }
        if (in_array($mode, [CommerceRuntimeReadMode::NATIVE, CommerceRuntimeReadMode::SHADOW, CommerceRuntimeReadMode::AUTO], true)) {
            $native = $this->load_native($userid, $email);
        }

        if ($mode === CommerceRuntimeReadMode::LEGACY) { return $legacy; }
        if ($mode === CommerceRuntimeReadMode::NATIVE) { return $native; }

        if ($mode === CommerceRuntimeReadMode::SHADOW) {
            $differences = $this->compare($legacy, $native);
            if ($strict && $differences !== []) {
                throw new \RuntimeException('Student Commerce shadow read differs: ' . implode(', ', $differences));
            }
            return new StudentCommercePurchaseCollection(
                $legacy->get_subscriptions(),
                $legacy->get_digital_purchases(),
                'legacy',
                $differences
            );
        }

        // AUTO prefers native only when it is complete; otherwise it safely falls back.
        $differences = $this->compare($legacy, $native);
        if ($differences === []) { return $native; }
        if ($strict) {
            throw new \RuntimeException('Student Commerce auto fallback required: ' . implode(', ', $differences));
        }
        return new StudentCommercePurchaseCollection(
            $legacy->get_subscriptions(),
            $legacy->get_digital_purchases(),
            'legacy_fallback',
            $differences
        );
    }

    private function load_native(int $userid, string $email): StudentCommercePurchaseCollection {
        $subscriptions = [];
        $digital = [];
        foreach ($this->repository->find_by_customer($userid, $email) as $snapshot) {
            $type = $snapshot->get_purchase()->get_type();
            if ($type === 'subscription') {
                $record = $this->mapper->map_subscription($snapshot);
                $subscriptions[$record->id ?: $record->commerce_purchase_uuid] = $record;
            } else if ($type === 'digital') {
                $record = $this->mapper->map_digital($snapshot);
                if (in_array($record->status, ['paid', 'captured', 'fulfilled', 'completed'], true)) {
                    $digital[$record->id ?: $record->commerce_purchase_uuid] = $record;
                }
            }
        }
        uasort($subscriptions, static fn($a, $b) => ((int)$b->end_date <=> (int)$a->end_date));
        uasort($digital, static fn($a, $b) => ((int)($b->payment_date ?? $b->creation_date) <=> (int)($a->payment_date ?? $a->creation_date)));
        return new StudentCommercePurchaseCollection($subscriptions, $digital, 'native');
    }

    private function load_legacy(int $userid, string $email): StudentCommercePurchaseCollection {
        global $DB;
        $subscriptions = $DB->get_records('user_subscription', ['userid' => $userid], 'end_date DESC');
        $sql = "SELECT pr.* FROM {subscription_digital_payment_request} pr
                 WHERE pr.status IN ('paid', 'completed')
                   AND (pr.userid = :userid OR " . $DB->sql_compare_text('pr.email') . " = " . $DB->sql_compare_text(':email') . ")
              ORDER BY COALESCE(pr.payment_date, pr.creation_date) DESC, pr.id DESC";
        $digital = $DB->get_records_sql($sql, ['userid' => $userid, 'email' => $email]);
        return new StudentCommercePurchaseCollection($subscriptions, $digital, 'legacy');
    }

    private function compare(StudentCommercePurchaseCollection $legacy, StudentCommercePurchaseCollection $native): array {
        $differences = [];
        if (count($legacy->get_subscriptions()) !== count($native->get_subscriptions())) { $differences[] = 'subscription_count'; }
        if (count($legacy->get_digital_purchases()) !== count($native->get_digital_purchases())) { $differences[] = 'digital_count'; }
        $legacyids = array_map(static fn($r) => (int)$r->id, $legacy->get_subscriptions());
        $nativeids = array_map(static fn($r) => (int)$r->id, $native->get_subscriptions());
        sort($legacyids); sort($nativeids);
        if ($legacyids !== $nativeids) { $differences[] = 'subscription_ids'; }
        $legacyids = array_map(static fn($r) => (int)$r->id, $legacy->get_digital_purchases());
        $nativeids = array_map(static fn($r) => (int)$r->id, $native->get_digital_purchases());
        sort($legacyids); sort($nativeids);
        if ($legacyids !== $nativeids) { $differences[] = 'digital_ids'; }
        return array_values(array_unique($differences));
    }
}
