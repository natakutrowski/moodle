<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\customer\merge;

defined('MOODLE_INTERNAL') || die();

/**
 * Virtual dry-run of a customer merge.
 *
 * This object never persists a temporary Moodle user.
 */
final class CommerceCustomerMergePlan {
    /**
     * @param CommerceCustomerMergeAccountProfile[] $profiles
     * @param array<int,array{type:string,userid:int,detail:string}> $warnings
     */
    public function __construct(
        public readonly array $profiles,
        public readonly int $recommendedtargetuserid,
        public readonly int $targetuserid,
        public readonly array $warnings,
        public readonly int $sharedcoursecount
    ) {
    }

    /** @return CommerceCustomerMergeAccountProfile[] */
    public function source_profiles(): array {
        return array_values(array_filter(
            $this->profiles,
            fn(CommerceCustomerMergeAccountProfile $profile): bool =>
                $profile->userid() !== $this->targetuserid
        ));
    }

    public function target_profile(): CommerceCustomerMergeAccountProfile {
        foreach ($this->profiles as $profile) {
            if ($profile->userid() === $this->targetuserid) {
                return $profile;
            }
        }

        throw new \coding_exception('Merge plan target profile is missing.');
    }

    /** @return array{purchases:int,grants:int,digitalaccesses:int,guestsessions:int} */
    public function commerce_transfer_totals(): array {
        $totals = [
            'purchases' => 0,
            'grants' => 0,
            'digitalaccesses' => 0,
            'guestsessions' => 0,
        ];

        foreach ($this->source_profiles() as $profile) {
            $totals['purchases'] += $profile->purchases;
            $totals['grants'] += $profile->grants;
            $totals['digitalaccesses'] += $profile->digitalaccesses;
            $totals['guestsessions'] += $profile->guestsessions;
        }

        return $totals;
    }
}
