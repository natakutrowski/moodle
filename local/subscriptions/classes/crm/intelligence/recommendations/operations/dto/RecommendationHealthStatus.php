<?php

namespace local_subscriptions\crm\intelligence\recommendations\operations\dto;

defined('MOODLE_INTERNAL') || die();

/**
 * Health status of the Recommendation Engine operational pipeline.
 */
final class RecommendationHealthStatus {

    public const HEALTHY = 'healthy';
    public const DEGRADED = 'degraded';
    public const UNHEALTHY = 'unhealthy';

    /**
     * @param string[] $warnings
     * @param string[] $errors
     */
    public function __construct(
        public readonly string $status,
        public readonly ?int $lastrunat,
        public readonly ?string $lastrunstatus,
        public readonly int $activecount,
        public readonly int $criticalcount,
        public readonly int $dueexpirationcount,
        public readonly int $failedruns24h,
        public readonly array $warnings = [],
        public readonly array $errors = []
    ) {
        if (
            !in_array(
                $this->status,
                [
                    self::HEALTHY,
                    self::DEGRADED,
                    self::UNHEALTHY,
                ],
                true
            )
        ) {
            throw new \InvalidArgumentException(
                'Invalid Recommendation Engine health status.'
            );
        }
    }

    public function to_object(): \stdClass {
        return (object)[
            'status' => $this->status,
            'lastrunat' => $this->lastrunat,
            'lastrunstatus' =>
                $this->lastrunstatus,
            'activecount' => $this->activecount,
            'criticalcount' =>
                $this->criticalcount,
            'dueexpirationcount' =>
                $this->dueexpirationcount,
            'failedruns24h' =>
                $this->failedruns24h,
            'warnings' => $this->warnings,
            'errors' => $this->errors,
        ];
    }
}