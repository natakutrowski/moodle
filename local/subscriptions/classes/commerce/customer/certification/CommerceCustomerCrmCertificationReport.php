<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\customer\certification;

defined('MOODLE_INTERNAL') || die();

final class CommerceCustomerCrmCertificationReport {
    /** @param array<int,array{status:string,label:string,detail:string}> $findings */
    public function __construct(public readonly array $findings) {}

    public function error_count(): int {
        return count(array_filter($this->findings, static fn(array $finding): bool => $finding['status'] === 'ERROR'));
    }

    public function warning_count(): int {
        return count(array_filter($this->findings, static fn(array $finding): bool => $finding['status'] === 'WARN'));
    }

    public function is_certified(bool $strict = false): bool {
        return $this->error_count() === 0 && (!$strict || $this->warning_count() === 0);
    }
}
