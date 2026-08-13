<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail\context;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseDetails;
use moodle_database;

/**
 * Resolves the customer language used by transactional purchase mail.
 *
 * Priority is deliberately identity/source based rather than administrator-session based:
 * 1. current Moodle user preference;
 * 2. authoritative Legacy Digital buyer language for projected purchases;
 * 3. persisted Commerce purchase metadata;
 * 4. current Moodle language / FR fallback.
 */
final class CommercePurchaseMailLanguageResolver {
    public function __construct(private readonly moodle_database $database) {
    }

    public function resolve(?int $userid, CommercePurchaseDetails $details): string {
        if ($userid !== null && $userid > 0) {
            $language = $this->database->get_field(
                'user',
                'lang',
                ['id' => $userid, 'deleted' => 0],
                IGNORE_MISSING
            );
            $resolved = $this->normalise($language);
            if ($resolved !== null) {
                return $resolved;
            }
        }

        $legacy = $this->legacy_digital_language($details);
        if ($legacy !== null) {
            return $legacy;
        }

        foreach (['customerlang', 'buyer_language', 'buyer_lang', 'language', 'lang'] as $key) {
            $resolved = $this->normalise($details->metadata[$key] ?? null);
            if ($resolved !== null) {
                return $resolved;
            }
        }

        return $this->normalise(current_language()) ?? 'fr';
    }

    private function legacy_digital_language(CommercePurchaseDetails $details): ?string {
        if (
            strtolower(trim((string)($details->legacyfamily ?? ''))) !== 'digital'
            || empty($details->legacyid)
        ) {
            return null;
        }

        $language = $this->database->get_field(
            'subscription_digital_payment_request',
            'buyer_lang',
            ['id' => (int)$details->legacyid],
            IGNORE_MISSING
        );

        return $this->normalise($language);
    }

    private function normalise(mixed $language): ?string {
        if (!is_scalar($language)) {
            return null;
        }

        $language = strtolower(trim((string)$language));
        if ($language === '') {
            return null;
        }

        // Do not use PARAM_LANG here: it may resolve/fallback according to the
        // language packs installed in the current Moodle runtime. Transactional
        // Commerce owns FR/EN/RU templates independently and must preserve the
        // customer's persisted language even in PHPUnit/minimal installations.
        $language = str_replace('-', '_', $language);
        if (!preg_match('/^[a-z]{2,3}(?:_[a-z0-9]{2,8})*$/', $language)) {
            return null;
        }

        $base = explode('_', $language)[0];
        return in_array($base, ['fr', 'en', 'ru'], true) ? $base : null;
    }
}