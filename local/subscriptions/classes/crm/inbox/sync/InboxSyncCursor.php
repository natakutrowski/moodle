<?php

namespace local_subscriptions\crm\inbox\sync;

defined('MOODLE_INTERNAL') || die();

final class InboxSyncCursor {

    public function __construct(
        public readonly ?string $uidvalidity,
        public readonly int $lastuid
    ) {
    }

    public static function decode(
        ?string $value
    ): self {
        if ($value === null || trim($value) === '') {
            return new self(null, 0);
        }

        $value = trim($value);

        // Compatibilité avec les anciens curseurs numériques.
        if (ctype_digit($value)) {
            return new self(null, (int)$value);
        }

        try {
            $decoded = json_decode(
                $value,
                true,
                512,
                JSON_THROW_ON_ERROR
            );

            return new self(
                isset($decoded['uidvalidity'])
                    ? (string)$decoded['uidvalidity']
                    : null,
                max(0, (int)($decoded['lastuid'] ?? 0))
            );
        } catch (\Throwable $exception) {
            return new self(null, 0);
        }
    }

    public function encode(): string {
        return json_encode(
            [
                'uidvalidity' => $this->uidvalidity,
                'lastuid' => $this->lastuid,
            ],
            JSON_THROW_ON_ERROR |
            JSON_UNESCAPED_SLASHES
        );
    }
}