<?php

namespace local_subscriptions\commandcenter;

defined('MOODLE_INTERNAL') || die();

final class CommandCollection implements \JsonSerializable {

    private string $query;
    private array $results;
    private int $durationms;

    public function __construct(string $query, array $results, int $durationms = 0) {
        $this->query = $query;
        $this->results = $results;
        $this->durationms = $durationms;
    }

    public function results(): array {
        return $this->results;
    }

    public function count(): int {
        return count($this->results);
    }

    public function with_duration(int $durationms): self {
        return new self($this->query, $this->results, $durationms);
    }

    public function jsonSerialize(): array {
        return [
            'success' => true,
            'results' => $this->results,
            'meta' => [
                'query' => $this->query,
                'count' => $this->count(),
                'duration_ms' => $this->durationms,
            ],
        ];
    }
}