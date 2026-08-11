<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\cover;

defined('MOODLE_INTERNAL') || die();

/** Resolved product artwork with transparent fallback information. */
final class CommerceProductCover {
    public function __construct(
        private readonly string $requestedcontext,
        private readonly ?string $url,
        private readonly ?string $resolvedcontext,
        private readonly bool $fallback
    ) {
    }

    public function get_requested_context(): string { return $this->requestedcontext; }
    public function get_url(): ?string { return $this->url; }
    public function get_resolved_context(): ?string { return $this->resolvedcontext; }
    public function is_fallback(): bool { return $this->fallback; }
    public function exists(): bool { return $this->url !== null; }

    /** @return array<string,mixed> */
    public function to_array(): array {
        return [
            'context' => $this->requestedcontext,
            'url' => $this->url,
            'resolvedcontext' => $this->resolvedcontext,
            'fallback' => $this->fallback,
            'exists' => $this->exists(),
        ];
    }
}
