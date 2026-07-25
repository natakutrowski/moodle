<?php

declare(strict_types=1);
namespace local_subscriptions\commerce\rollout;
defined('MOODLE_INTERNAL') || die();
final class CommerceCertificationEvidence {
 public function __construct(private readonly string $key, private readonly bool $passed, private readonly string $evidence='') {}
 public function get_key(): string{return $this->key;} public function passed(): bool{return $this->passed;}
 public function get_evidence(): string{return $this->evidence;}
}
