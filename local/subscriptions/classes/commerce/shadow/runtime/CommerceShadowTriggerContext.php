<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\shadow\runtime;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\shadow\CommerceShadowSource;

/** Canonical source and entry-point metadata for dual-write Shadow observations. */
final class CommerceShadowTriggerContext {
    public function __construct(
        private readonly string $source,
        private readonly string $entrypoint
    ) {
        if (!CommerceShadowSource::is_valid($this->source)) {
            throw new \coding_exception('Invalid Commerce Shadow trigger source.');
        }
        if (trim($this->entrypoint) === '') {
            throw new \coding_exception('A Commerce Shadow trigger requires an entry point.');
        }
    }

    public static function from_dualwrite(string $family, string $trigger): self {
        $family = strtolower(trim($family));
        $trigger = self::normalise($trigger);

        if ($family === 'digital') {
            return self::digital($trigger);
        }
        if ($family === 'subscription') {
            return self::subscription($trigger);
        }

        throw new \coding_exception('Unsupported Commerce dual-write family.');
    }

    public function get_source(): string {
        return $this->source;
    }

    public function get_entrypoint(): string {
        return $this->entrypoint;
    }

    private static function digital(string $trigger): self {
        if (str_contains($trigger, 'reconciliation')) {
            return new self(CommerceShadowSource::RECONCILIATION_JOB, 'cli.reconciliation.digital');
        }
        if (str_contains($trigger, 'repair')) {
            return new self(CommerceShadowSource::REPAIR_JOB, 'cli.repair.digital');
        }
        if (str_contains($trigger, 'email') || str_contains($trigger, 'token')) {
            return new self(CommerceShadowSource::CRM_MANUAL, 'admin.digital.' . $trigger);
        }

        return new self(CommerceShadowSource::CHECKOUT_DIGITAL, 'checkout.digital.' . $trigger);
    }

    private static function subscription(string $trigger): self {
        if (str_contains($trigger, 'repair')) {
            return new self(CommerceShadowSource::REPAIR_JOB, 'cli.repair.subscription');
        }
        if (str_contains($trigger, 'manual') || str_contains($trigger, 'admin')) {
            return new self(CommerceShadowSource::CRM_MANUAL, 'admin.subscription.' . $trigger);
        }

        return new self(CommerceShadowSource::CHECKOUT_SUBSCRIPTION, 'checkout.subscription.' . $trigger);
    }

    private static function normalise(string $trigger): string {
        $trigger = strtolower(trim($trigger));
        $trigger = preg_replace('/[^a-z0-9_.-]+/', '_', $trigger) ?? '';
        return $trigger !== '' ? $trigger : 'unspecified';
    }
}
