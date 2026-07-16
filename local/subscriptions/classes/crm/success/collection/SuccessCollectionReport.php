<?php

namespace local_subscriptions\crm\success\collection;

defined('MOODLE_INTERNAL') || die();

/**
 * Result of collecting Customer Success metrics for one user.
 */
final class SuccessCollectionReport {

    /**
     * @param int $userid Moodle user ID.
     * @param SuccessMetricCollection $metrics Successfully collected metrics.
     * @param string[] $executedcollectors Collector keys that were executed.
     * @param string[] $unavailablecollectors Unavailable collector keys.
     * @param array<string,string> $errors Sanitized errors indexed by collector.
     * @param int $measuredat Shared collection timestamp.
     */
    public function __construct(
        public readonly int $userid,
        public readonly SuccessMetricCollection $metrics,
        public readonly array $executedcollectors,
        public readonly array $unavailablecollectors,
        public readonly array $errors,
        public readonly int $measuredat
    ) {
        if ($this->userid <= 0) {
            throw new \InvalidArgumentException(
                'Success collection report userid must be greater than zero.'
            );
        }

        if ($this->measuredat <= 0) {
            throw new \InvalidArgumentException(
                'Success collection report timestamp must be greater than zero.'
            );
        }

        if (
            $this->metrics->userid() !== null &&
            $this->metrics->userid() !== $this->userid
        ) {
            throw new \InvalidArgumentException(
                'Success collection report metrics belong to another user.'
            );
        }

        $this->validate_string_list(
            $this->executedcollectors,
            'executed collector'
        );

        $this->validate_string_list(
            $this->unavailablecollectors,
            'unavailable collector'
        );

        foreach ($this->errors as $collectorkey => $message) {
            if (
                !is_string($collectorkey) ||
                $collectorkey === '' ||
                !is_string($message)
            ) {
                throw new \InvalidArgumentException(
                    'Invalid Customer Success collection error.'
                );
            }
        }
    }

    public function is_successful(): bool {
        return $this->errors === [];
    }

    public function has_metrics(): bool {
        return $this->metrics->count() > 0;
    }

    public function to_object(): \stdClass {
        return (object)[
            'userid' => $this->userid,
            'metrics' => $this->metrics->to_objects(),
            'executedcollectors' => $this->executedcollectors,
            'unavailablecollectors' => $this->unavailablecollectors,
            'errors' => $this->errors,
            'measuredat' => $this->measuredat,
            'successful' => $this->is_successful(),
        ];
    }

    private function validate_string_list(
        array $values,
        string $label
    ): void {
        foreach ($values as $value) {
            if (!is_string($value) || $value === '') {
                throw new \InvalidArgumentException(
                    'Invalid Customer Success ' . $label . ' key.'
                );
            }
        }
    }
}