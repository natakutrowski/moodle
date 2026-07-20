<?php

namespace local_subscriptions\payment;

defined('MOODLE_INTERNAL') || die();

/**
 * Centralise la journalisation et la présentation des erreurs de paiement.
 */
final class PaymentFailureReporter {

    /**
     * Taille maximale conservée dans les colonnes last_error.
     */
    private const MAX_STORED_MESSAGE_LENGTH = 8000;

    /**
     * Génère une référence courte pour un incident.
     *
     * @return string
     */
    public static function generate_reference(): string {
        return strtoupper(
            substr(
                bin2hex(random_bytes(8)),
                0,
                12
            )
        );
    }

    /**
     * Construit le détail technique à conserver en base.
     *
     * @param \Throwable $exception
     * @param string $reference
     * @param string $context
     * @return string
     */
    public static function technical_message(
        \Throwable $exception,
        string $reference,
        string $context
    ): string {
        $message = trim(
            (string)$exception->getMessage()
        );

        if ($message === '') {
            $message = get_class($exception);
        }

        $technical = sprintf(
            '[%s] [%s] %s: %s',
            $reference,
            $context,
            get_class($exception),
            $message
        );

        return self::truncate(
            $technical,
            self::MAX_STORED_MESSAGE_LENGTH
        );
    }

    /**
     * Écrit une erreur dans les logs Moodle.
     *
     * @param \Throwable $exception
     * @param string $reference
     * @param string $context
     * @param array<string,scalar|null> $metadata
     * @return void
     */
    public static function log(
        \Throwable $exception,
        string $reference,
        string $context,
        array $metadata = []
    ): void {
        $metadata['reference'] = $reference;
        $metadata['context'] = $context;
        $metadata['exception'] =
            get_class($exception);
        $metadata['message'] =
            self::truncate(
                trim((string)$exception->getMessage()),
                2000
            );

        debugging(
            '[local_subscriptions][payment] ' .
            json_encode(
                $metadata,
                JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES
            ),
            DEBUG_DEVELOPER
        );

        error_log(
            '[local_subscriptions][payment] ' .
            json_encode(
                $metadata,
                JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES
            )
        );
    }

    /**
     * Normalise un code public d’erreur.
     *
     * @param string $code
     * @return string
     */
    public static function normalize_public_code(
        string $code
    ): string {
        $code = clean_param(
            strtolower(trim($code)),
            PARAM_ALPHANUMEXT
        );

        $allowed = [
            'security',
            'link',
            'currency',
            'amount',
            'gateway',
            'canceled',
            'declined',
            'expired',
            'owner',
            'status',
            'invalidsesskey',
            'session_create',
            'payment_retry',
            'digital_session_create',
            'invalid_redirect',
            'provider_unavailable',
            'no_redirect',
        ];

        return in_array(
            $code,
            $allowed,
            true
        )
            ? $code
            : 'session_create';
    }

    /**
     * Limite proprement une chaîne Unicode.
     *
     * @param string $value
     * @param int $maxlength
     * @return string
     */
    private static function truncate(
        string $value,
        int $maxlength
    ): string {
        if (
            \core_text::strlen($value) <=
            $maxlength
        ) {
            return $value;
        }

        return \core_text::substr(
            $value,
            0,
            $maxlength - 1
        ) . '…';
    }
}