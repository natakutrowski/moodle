<?php

namespace local_subscriptions\crm\success\dto;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\success\collection\SuccessCollectionReport;
use local_subscriptions\crm\success\scoring\CustomerSuccessScore;
use local_subscriptions\crm\success\signals\SuccessSignalCollection;

/**
 * Complete output of the Customer Success runtime.
 */
final class CustomerSuccessResult {

    /**
     * @param int $userid Moodle user ID.
     * @param SuccessCollectionReport $collection Collection report.
     * @param SuccessSignalCollection $signals Generated signals.
     * @param CustomerSuccessScore $score Calculated score.
     * @param array<string,string> $signalerrors Sanitized signal rule errors.
     */
    public function __construct(
        public readonly int $userid,
        public readonly SuccessCollectionReport $collection,
        public readonly SuccessSignalCollection $signals,
        public readonly CustomerSuccessScore $score,
        public readonly array $signalerrors
    ) {
        if ($this->userid <= 0) {
            throw new \InvalidArgumentException(
                'Customer Success result userid must be greater than zero.'
            );
        }

        if (
            $this->collection->userid !== $this->userid ||
            $this->score->userid !== $this->userid
        ) {
            throw new \InvalidArgumentException(
                'Customer Success result components belong to another user.'
            );
        }

        if (
            $this->signals->userid() !== null &&
            $this->signals->userid() !== $this->userid
        ) {
            throw new \InvalidArgumentException(
                'Customer Success result signals belong to another user.'
            );
        }

        foreach ($this->signalerrors as $rulekey => $message) {
            if (
                !is_string($rulekey) ||
                $rulekey === '' ||
                !is_string($message)
            ) {
                throw new \InvalidArgumentException(
                    'Invalid Customer Success signal error.'
                );
            }
        }
    }

    public function is_successful(): bool {
        return
            $this->collection->errors === [] &&
            $this->signalerrors === [];
    }

    public function has_data(): bool {
        return
            $this->collection->metrics->count() > 0 ||
            $this->signals->count() > 0;
    }

    public function to_object(): \stdClass {
        return (object)[
            'userid' => $this->userid,
            'collection' => $this->collection->to_object(),
            'signals' => $this->signals->to_objects(),
            'score' => $this->score->to_object(),
            'signalerrors' => $this->signalerrors,
            'successful' => $this->is_successful(),
            'hasdata' => $this->has_data(),
        ];
    }
}