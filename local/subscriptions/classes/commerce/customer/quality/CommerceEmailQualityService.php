<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\customer\quality;

defined('MOODLE_INTERNAL') || die();

/**
 * Conservative typo detector for customer email addresses.
 *
 * This deliberately does not perform DNS/network checks. It only flags invalid syntax
 * and domains that are very close to a curated set of common providers.
 */
final class CommerceEmailQualityService {
    public const STATUS_OK = 'ok';
    public const STATUS_INVALID = 'invalid';
    public const STATUS_SUSPECT = 'suspect';

    /** @var string[] */
    private const COMMON_DOMAINS = [
        'gmail.com', 'googlemail.com',
        'outlook.com', 'hotmail.com', 'live.com', 'msn.com',
        'yahoo.com', 'yahoo.fr',
        'icloud.com', 'me.com',
        'orange.fr', 'free.fr', 'sfr.fr', 'laposte.net',
        'mail.ru', 'yandex.ru', 'ya.ru', 'rambler.ru',
        'bk.ru', 'inbox.ru', 'list.ru',
    ];

    /** @var array<string,string> */
    private const KNOWN_TYPOS = [
        'gmai.com' => 'gmail.com',
        'gmal.com' => 'gmail.com',
        'gmial.com' => 'gmail.com',
        'gamil.com' => 'gmail.com',
        'gmail.con' => 'gmail.com',
        'gmail.co' => 'gmail.com',
        'gmail.cm' => 'gmail.com',
        'gmail.om' => 'gmail.com',
        'hotmai.com' => 'hotmail.com',
        'hotmal.com' => 'hotmail.com',
        'hotmail.con' => 'hotmail.com',
        'outlok.com' => 'outlook.com',
        'outloo.com' => 'outlook.com',
        'outlook.con' => 'outlook.com',
        'yaho.com' => 'yahoo.com',
        'yahoo.con' => 'yahoo.com',
        'icloud.con' => 'icloud.com',
        'iclod.com' => 'icloud.com',
        'mail.ry' => 'mail.ru',
        'yandex.ry' => 'yandex.ru',
    ];

    public function diagnose(string $email): CommerceEmailQualityDiagnostic {
        $email = $this->normalise($email);
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new CommerceEmailQualityDiagnostic(
                $email,
                self::STATUS_INVALID,
                null,
                'syntax'
            );
        }

        [$local, $domain] = explode('@', $email, 2);
        $suggesteddomain = self::KNOWN_TYPOS[$domain] ?? $this->closest_common_domain($domain);
        if ($suggesteddomain !== null && $suggesteddomain !== $domain) {
            return new CommerceEmailQualityDiagnostic(
                $email,
                self::STATUS_SUSPECT,
                $local . '@' . $suggesteddomain,
                'domain_typo'
            );
        }

        return new CommerceEmailQualityDiagnostic($email, self::STATUS_OK);
    }

    private function normalise(string $email): string {
        return strtolower(trim($email));
    }

    private function closest_common_domain(string $domain): ?string {
        // Unknown/custom domains are valid and must not be flagged merely for being uncommon.
        // Only suggest a provider when the spelling is extremely close.
        $best = null;
        $bestdistance = PHP_INT_MAX;
        foreach (self::COMMON_DOMAINS as $candidate) {
            if ($domain === $candidate) {
                return null;
            }
            $distance = levenshtein($domain, $candidate);
            $maxlength = max(strlen($domain), strlen($candidate));
            $threshold = $maxlength <= 8 ? 1 : 2;
            if ($distance <= $threshold && $distance < $bestdistance) {
                $best = $candidate;
                $bestdistance = $distance;
            }
        }
        return $best;
    }
}
