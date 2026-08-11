<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\presentation;

defined('MOODLE_INTERNAL') || die();

/**
 * Human-readable Commerce label with optional diagnostic metadata.
 */
final class CommercePresentationLabel {
    public const INTENT_SUCCESS = 'success';
    public const INTENT_INFO = 'info';
    public const INTENT_WARNING = 'warning';
    public const INTENT_DANGER = 'danger';
    public const INTENT_MUTED = 'muted';

    private const VALID_INTENTS = [
        self::INTENT_SUCCESS,
        self::INTENT_INFO,
        self::INTENT_WARNING,
        self::INTENT_DANGER,
        self::INTENT_MUTED,
    ];

    public function __construct(
        private readonly string $label,
        private readonly string $intent = self::INTENT_MUTED,
        private readonly ?string $technicalreference = null
    ) {
        if (trim($this->label) === '') {
            throw new \coding_exception('A Commerce presentation label cannot be empty.');
        }

        if (!in_array($this->intent, self::VALID_INTENTS, true)) {
            throw new \coding_exception('Unsupported Commerce presentation intent: ' . $this->intent);
        }
    }

    public function label(): string {
        return $this->label;
    }

    public function intent(): string {
        return $this->intent;
    }

    public function diagnostic_reference(string $context): ?string {
        if (!CommercePresentationContext::allows_technical_details($context)) {
            return null;
        }

        $reference = trim((string) $this->technicalreference);
        return $reference === '' ? null : $reference;
    }

    /**
     * @return array{label: string, intent: string, technicalreference: ?string}
     */
    public function export(string $context): array {
        return [
            'label' => $this->label(),
            'intent' => $this->intent(),
            'technicalreference' => $this->diagnostic_reference($context),
        ];
    }
}
