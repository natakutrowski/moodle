<?php

namespace local_subscriptions\crm\success\collectors;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\success\collection\SuccessCollectionReport;
use local_subscriptions\crm\success\collection\SuccessMetric;
use local_subscriptions\crm\success\collection\SuccessMetricCollection;
use local_subscriptions\crm\success\contracts\SuccessCollectorInterface;
use local_subscriptions\crm\success\contracts\SuccessCollectorRegistryInterface;
use local_subscriptions\crm\intelligence\runtime\CrmComputationProfiler;

/**
 * Registry and execution coordinator for Customer Success collectors.
 */
final class SuccessCollectorRegistry implements
    SuccessCollectorRegistryInterface {

    /**
     * @var array<string,SuccessCollectorInterface>
     */
    private array $collectors = [];

    /**
     * @param SuccessCollectorInterface[] $collectors
     */
    public function __construct(
        array $collectors = []
    ) {
        foreach ($collectors as $collector) {
            $this->register($collector);
        }
    }

    public function register(
        SuccessCollectorInterface $collector
    ): void {
        $key = $collector->key();

        if (!$this->is_valid_key($key)) {
            throw new \InvalidArgumentException(
                'Invalid Customer Success collector key.'
            );
        }

        if (isset($this->collectors[$key])) {
            throw new \InvalidArgumentException(
                'Duplicate Customer Success collector key: ' . $key
            );
        }

        $this->collectors[$key] = $collector;
    }

    public function get(
        string $collectorkey
    ): ?SuccessCollectorInterface {
        return $this->collectors[$collectorkey] ?? null;
    }

    public function all(): array {
        return array_values($this->collectors);
    }

    public function available(): array {
        return array_values(array_filter(
            $this->collectors,
            static function(
                SuccessCollectorInterface $collector
            ): bool {
                try {
                    return $collector->is_available();
                } catch (\Throwable $exception) {
                    return false;
                }
            }
        ));
    }

    public function keys(): array {
        return array_keys($this->collectors);
    }

    /**
     * Executes all registered collectors for one user.
     */
    public function collect(
        int $userid,
        ?int $measuredat = null
    ): SuccessCollectionReport {
        if ($userid <= 0) {
            throw new \InvalidArgumentException(
                'Customer Success collection userid must be greater than zero.'
            );
        }

        $measuredat = $measuredat ?? time();

        if ($measuredat <= 0) {
            throw new \InvalidArgumentException(
                'Customer Success collection timestamp must be greater than zero.'
            );
        }

        $metrics = new SuccessMetricCollection();
        $executed = [];
        $unavailable = [];
        $errors = [];

        foreach ($this->collectors as $key => $collector) {
            try {
                $availabilityprofile =
                    CrmComputationProfiler::start();

                $available =
                    $collector->is_available();

                CrmComputationProfiler::finish(
                    runid: 'customer_success',
                    userid: $userid,
                    stage:
                        'collector_' .
                        $key .
                        '_availability',
                    start: $availabilityprofile
                );

                if (!$available) {
                    $unavailable[] = $key;
                    continue;
                }

                $collectionprofile =
                    CrmComputationProfiler::start();

                $collected = $collector->collect(
                    $userid,
                    $measuredat
                );

                CrmComputationProfiler::finish(
                    runid: 'customer_success',
                    userid: $userid,
                    stage:
                        'collector_' .
                        $key .
                        '_collect',
                    start: $collectionprofile
                );

                if (
                    $collected->userid() !== null &&
                    $collected->userid() !== $userid
                ) {
                    throw new \UnexpectedValueException(
                        'Collector returned metrics for another user.'
                    );
                }

                foreach ($collected as $metric) {
                    if (!$metric instanceof SuccessMetric) {
                        throw new \UnexpectedValueException(
                            'Collector returned an invalid metric.'
                        );
                    }

                    $metrics->add($metric);
                }

                $executed[] = $key;
            } catch (\Throwable $exception) {
                $errors[$key] = $this->sanitize_error($exception);
            }
        }

        return new SuccessCollectionReport(
            $userid,
            $metrics,
            array_values(array_unique($executed)),
            array_values(array_unique($unavailable)),
            $errors,
            $measuredat
        );
    }

    private function is_valid_key(
        string $key
    ): bool {
        return preg_match(
            '/^[a-z][a-z0-9_]{1,99}$/',
            $key
        ) === 1;
    }

    private function sanitize_error(
        \Throwable $exception
    ): string {
        $message = trim($exception->getMessage());

        if ($message === '') {
            return 'Collector execution failed.';
        }

        $message = preg_replace(
            '/[\\r\\n\\t]+/',
            ' ',
            $message
        );

        $message = preg_replace(
            '/\\s{2,}/',
            ' ',
            (string)$message
        );

        return \core_text::substr(
            (string)$message,
            0,
            500
        );
    }
}