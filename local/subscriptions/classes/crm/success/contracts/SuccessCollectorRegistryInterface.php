<?php

namespace local_subscriptions\crm\success\contracts;

defined('MOODLE_INTERNAL') || die();

/**
 * Registry contract for Customer Success metric collectors.
 */
interface SuccessCollectorRegistryInterface {

    public function register(
        SuccessCollectorInterface $collector
    ): void;

    public function get(
        string $collectorkey
    ): ?SuccessCollectorInterface;

    /**
     * @return SuccessCollectorInterface[]
     */
    public function all(): array;

    /**
     * @return SuccessCollectorInterface[]
     */
    public function available(): array;

    /**
     * @return string[]
     */
    public function keys(): array;
}