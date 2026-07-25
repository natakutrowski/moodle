<?php

declare(strict_types=1);
namespace local_subscriptions\commerce\rollout;
defined('MOODLE_INTERNAL') || die();
final class CommerceRuntimePath {
    public function __construct(private readonly string $key, private readonly string $label, private readonly string $family, private readonly string $risk, private readonly array $files) {}
    public function get_key(): string { return $this->key; }
    public function get_label(): string { return $this->label; }
    public function get_family(): string { return $this->family; }
    public function get_risk(): string { return $this->risk; }
    public function get_files(): array { return $this->files; }
}
