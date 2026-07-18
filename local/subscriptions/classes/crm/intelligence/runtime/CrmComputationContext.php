<?php

namespace local_subscriptions\crm\intelligence\runtime;

defined('MOODLE_INTERNAL') || die();

/**
 * Immutable execution context shared by one CRM computation cycle.
 *
 * The context provides a stable run identifier and timestamp to all
 * services participating in the same logical computation.
 */
final class CrmComputationContext {

    /**
     * Constructor.
     *
     * @param string $runid Unique computation identifier.
     * @param int $startedat Unix timestamp.
     * @param string $engineversion Engine version identifier.
     * @param string $source Trigger source.
     */
    public function __construct(
        public readonly string $runid,
        public readonly int $startedat,
        public readonly string $engineversion,
        public readonly string $source
    ) {
        if (trim($this->runid) === '') {
            throw new \InvalidArgumentException(
                'CRM computation run id cannot be empty.'
            );
        }

        if ($this->startedat <= 0) {
            throw new \InvalidArgumentException(
                'CRM computation start timestamp must be greater than zero.'
            );
        }

        if (trim($this->engineversion) === '') {
            throw new \InvalidArgumentException(
                'CRM computation engine version cannot be empty.'
            );
        }

        if (trim($this->source) === '') {
            throw new \InvalidArgumentException(
                'CRM computation source cannot be empty.'
            );
        }
    }

    /**
     * Creates a context with a generated run identifier.
     *
     * Used by computation cycles that do not yet have a persistent
     * run record.
     *
     * @param string $source Trigger source.
     * @param int|null $startedat Optional stable start timestamp.
     * @return self
     */
    public static function create(
        string $source,
        ?int $startedat = null
    ): self {
        return new self(
            runid: bin2hex(random_bytes(16)),
            startedat: $startedat ?? time(),
            engineversion: '7.5',
            source: $source
        );
    }

    /**
     * Creates a context from an existing persistent run identifier.
     *
     * @param string $runid Existing run identifier.
     * @param string $source Trigger source.
     * @param int|null $startedat Optional stable start timestamp.
     * @return self
     */
    public static function from_run(
        string $runid,
        string $source,
        ?int $startedat = null
    ): self {
        return new self(
            runid: $runid,
            startedat: $startedat ?? time(),
            engineversion: '7.5',
            source: $source
        );
    }
}