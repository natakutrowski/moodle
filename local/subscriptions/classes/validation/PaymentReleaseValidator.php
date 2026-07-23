<?php

namespace local_subscriptions\validation;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\constants\Status;
use local_subscriptions\payment\PaymentFailureReporter;
use local_subscriptions\url\UrlFactory;

/**
 * Validateur de non-régression des paiements.
 *
 * Toutes les vérifications sont en lecture seule.
 */
final class PaymentReleaseValidator {

    /**
     * Lance la validation complète.
     *
     * @return ValidationResult
     */
    public function validate(): ValidationResult {
        $result = new ValidationResult();

        $this->validate_autoload($result);
        $this->validate_external_urls($result);
        $this->validate_failure_references($result);
        $this->validate_public_error_codes($result);
        $this->validate_status_constants($result);
        $this->validate_language_strings($result);
        $this->validate_database_schema($result);
        $this->validate_payment_files($result);
        (new CommerceReleaseValidator())->validate($result);

        return $result;
    }

    /**
     * Vérifie l’autoload des classes.
     *
     * @param ValidationResult $result
     * @return void
     */
    private function validate_autoload(
        ValidationResult $result
    ): void {
        $classes = [
            UrlFactory::class,
            PaymentFailureReporter::class,
            ValidationResult::class,
            self::class,
            \local_subscriptions\payment\audit\PaymentConsistencyReport::class,
            \local_subscriptions\payment\audit\PaymentConsistencyAuditor::class,
        ];

        foreach ($classes as $class) {
            if (class_exists($class)) {
                $result->success(
                    'autoload_class',
                    'Classe disponible : ' . $class,
                    [
                        'class' => $class,
                    ]
                );
            } else {
                $result->error(
                    'autoload_class_missing',
                    'Classe introuvable : ' . $class,
                    [
                        'class' => $class,
                    ]
                );
            }
        }
    }

    /**
     * Teste les URLs de paiement externes.
     *
     * @param ValidationResult $result
     * @return void
     */
    private function validate_external_urls(
        ValidationResult $result
    ): void {
        $validurls = [
            'https://checkout.stripe.com/c/pay/test',
            'https://payment.alfabank.ru/payment/merchants/test',
            'https://example.org/payment?id=123',
        ];

        foreach ($validurls as $url) {
            try {
                $validated =
                    UrlFactory::validate_external_payment_url(
                        $url
                    );

                if ($validated !== $url) {
                    $result->error(
                        'external_url_changed',
                        'L’URL validée a été modifiée.',
                        [
                            'input' => $url,
                            'output' => $validated,
                        ]
                    );

                    continue;
                }

                $result->success(
                    'external_url_valid',
                    'URL HTTPS acceptée.',
                    [
                        'url' => $url,
                    ]
                );
            } catch (\Throwable $e) {
                $result->error(
                    'external_url_rejected',
                    'Une URL HTTPS valide a été rejetée.',
                    [
                        'url' => $url,
                        'exception' =>
                            get_class($e),
                        'message' =>
                            $e->getMessage(),
                    ]
                );
            }
        }

        $invalidurls = [
            '',
            'http://checkout.stripe.com/test',
            'javascript:alert(1)',
            'data:text/html,test',
            '/local/subscriptions/payment/return.php',
            '//checkout.stripe.com/test',
            'checkout.stripe.com/test',
            'https:///missing-host',
        ];

        foreach ($invalidurls as $url) {
            try {
                UrlFactory::validate_external_payment_url(
                    $url
                );

                $result->error(
                    'external_url_should_fail',
                    'Une URL externe invalide a été acceptée.',
                    [
                        'url' => $url,
                    ]
                );
            } catch (\Throwable $e) {
                $result->success(
                    'external_url_rejected_as_expected',
                    'URL externe invalide correctement rejetée.',
                    [
                        'url' => $url,
                        'exception' =>
                            get_class($e),
                    ]
                );
            }
        }
    }

    /**
     * Vérifie les références d’incident.
     *
     * @param ValidationResult $result
     * @return void
     */
    private function validate_failure_references(
        ValidationResult $result
    ): void {
        $references = [];

        for ($i = 0; $i < 100; $i++) {
            $reference =
                PaymentFailureReporter::
                    generate_reference();

            if (
                !preg_match(
                    '/^[A-F0-9]{12}$/',
                    $reference
                )
            ) {
                $result->error(
                    'invalid_failure_reference',
                    'Format de référence d’incident invalide.',
                    [
                        'reference' => $reference,
                    ]
                );

                return;
            }

            $references[] = $reference;
        }

        if (
            count(array_unique($references)) !==
            count($references)
        ) {
            $result->error(
                'duplicate_failure_reference',
                'Une référence d’incident dupliquée a été générée.'
            );

            return;
        }

        $result->success(
            'failure_reference_generation',
            '100 références d’incident valides et uniques ont été générées.'
        );
    }

    /**
     * Vérifie les codes d’erreur publics.
     *
     * @param ValidationResult $result
     * @return void
     */
    private function validate_public_error_codes(
        ValidationResult $result
    ): void {
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

        foreach ($allowed as $code) {
            $normalized =
                PaymentFailureReporter::
                    normalize_public_code(
                        $code
                    );

            if ($normalized !== $code) {
                $result->error(
                    'public_error_code_changed',
                    'Un code public autorisé a été remplacé.',
                    [
                        'input' => $code,
                        'output' => $normalized,
                    ]
                );

                continue;
            }

            $result->success(
                'public_error_code_valid',
                'Code public autorisé : ' . $code,
                [
                    'code' => $code,
                ]
            );
        }

        $invalidcodes = [
            '',
            '../secret',
            'unknown-provider-error',
            '<script>',
            'curl_error_60',
        ];

        foreach ($invalidcodes as $code) {
            $normalized =
                PaymentFailureReporter::
                    normalize_public_code(
                        $code
                    );

            if ($normalized !== 'session_create') {
                $result->error(
                    'invalid_public_error_code',
                    'Un code public invalide n’a pas été normalisé.',
                    [
                        'input' => $code,
                        'output' => $normalized,
                    ]
                );

                continue;
            }

            $result->success(
                'public_error_code_normalized',
                'Code public invalide correctement normalisé.',
                [
                    'input' => $code,
                ]
            );
        }
    }

    /**
     * Vérifie les constantes de statut.
     *
     * @param ValidationResult $result
     * @return void
     */
    private function validate_status_constants(
        ValidationResult $result
    ): void {
        $required = [
            'PENDING',
            'FAILED',
        ];

        foreach ($required as $constant) {
            $fullname =
                Status::class . '::' . $constant;

            if (defined($fullname)) {
                $result->success(
                    'status_constant',
                    'Constante disponible : ' . $constant,
                    [
                        'constant' => $constant,
                        'value' => constant($fullname),
                    ]
                );
            } else {
                $result->error(
                    'status_constant_missing',
                    'Constante manquante : ' . $constant,
                    [
                        'constant' => $constant,
                    ]
                );
            }
        }

        $canceledvariants = [
            Status::class . '::CANCELED',
            Status::class . '::CANCELLED',
        ];

        $found = [];

        foreach ($canceledvariants as $constant) {
            if (defined($constant)) {
                $found[$constant] =
                    constant($constant);
            }
        }

        if (empty($found)) {
            $result->warning(
                'cancel_status_constant_missing',
                'Aucune constante CANCELED ou CANCELLED n’est définie.'
            );
        } else {
            $result->success(
                'cancel_status_constant',
                'Constante d’annulation disponible.',
                [
                    'constants' => $found,
                ]
            );
        }

        $errorconstant =
            Status::class . '::ERROR';

        if (defined($errorconstant)) {
            $result->warning(
                'legacy_error_status',
                'Status::ERROR existe encore. Vérifier son usage résiduel.',
                [
                    'value' =>
                        constant($errorconstant),
                ]
            );
        }
    }

    /**
     * Vérifie les chaînes FR / EN / RU.
     *
     * @param ValidationResult $result
     * @return void
     */
    private function validate_language_strings(
        ValidationResult $result
    ): void {
        global $CFG;

        $keys = [
            'err_invalid_redirect_url',
            'payment_error_session_create',
            'payment_error_digital_session_create',
            'payment_error_retry',
            'payment_error_invalid_redirect',
            'payment_error_provider_unavailable',
            'payment_error_reference',
        ];

        $languages = [
            'fr',
            'en',
            'ru',
        ];

        foreach ($languages as $language) {
            $file =
                $CFG->dirroot .
                '/local/subscriptions/lang/' .
                $language .
                '/local_subscriptions.php';

            if (!is_readable($file)) {
                $result->error(
                    'language_file_missing',
                    'Fichier de langue introuvable.',
                    [
                        'language' => $language,
                        'file' => $file,
                    ]
                );

                continue;
            }

            $string = [];
            require($file);

            foreach ($keys as $key) {
                if (
                    array_key_exists(
                        $key,
                        $string
                    )
                ) {
                    $result->success(
                        'language_string',
                        sprintf(
                            '%s : chaîne présente : %s',
                            $language,
                            $key
                        ),
                        [
                            'language' => $language,
                            'key' => $key,
                        ]
                    );
                } else {
                    $result->error(
                        'language_string_missing',
                        sprintf(
                            '%s : chaîne manquante : %s',
                            $language,
                            $key
                        ),
                        [
                            'language' => $language,
                            'key' => $key,
                        ]
                    );
                }
            }
        }
    }

    /**
     * Vérifie les tables et colonnes critiques.
     *
     * @param ValidationResult $result
     * @return void
     */
    private function validate_database_schema(
        ValidationResult $result
    ): void {
        global $DB;

        $tables = [
            'subscription_payment_request' => [
                'id',
                'status',
                'payment_provider',
                'currency',
                'last_error',
            ],
            'subscription_digital_payment_request' => [
                'id',
                'status',
                'payment_provider',
                'currency',
                'last_error',
                'last_update',
            ],
        ];

        $manager = $DB->get_manager();

        foreach ($tables as $table => $fields) {
            $xmldbtable =
                new \xmldb_table($table);

            if (!$manager->table_exists($xmldbtable)) {
                $result->error(
                    'database_table_missing',
                    'Table absente : ' . $table,
                    [
                        'table' => $table,
                    ]
                );

                continue;
            }

            $result->success(
                'database_table',
                'Table disponible : ' . $table,
                [
                    'table' => $table,
                ]
            );

            $columns = $DB->get_columns($table);

            foreach ($fields as $field) {
                if (isset($columns[$field])) {
                    $result->success(
                        'database_field',
                        sprintf(
                            'Champ disponible : %s.%s',
                            $table,
                            $field
                        ),
                        [
                            'table' => $table,
                            'field' => $field,
                        ]
                    );
                } else {
                    $result->error(
                        'database_field_missing',
                        sprintf(
                            'Champ absent : %s.%s',
                            $table,
                            $field
                        ),
                        [
                            'table' => $table,
                            'field' => $field,
                        ]
                    );
                }
            }
        }
    }

    /**
     * Vérifie les fichiers principaux.
     *
     * @param ValidationResult $result
     * @return void
     */
    private function validate_payment_files(
        ValidationResult $result
    ): void {
        global $CFG;

        $files = [
            'payment/create_session.php',
            'payment/digital_create_session.php',
            'payment/retry_payment.php',
            'payment_error.php',
            'classes/url/UrlFactory.php',
            'classes/payment/PaymentFailureReporter.php',
            'classes/payment/audit/PaymentConsistencyReport.php',
            'classes/payment/audit/PaymentConsistencyAuditor.php',
        ];

        foreach ($files as $relativepath) {
            $file =
                $CFG->dirroot .
                '/local/subscriptions/' .
                $relativepath;

            if (is_readable($file)) {
                $result->success(
                    'payment_file',
                    'Fichier disponible : ' . $relativepath,
                    [
                        'file' => $relativepath,
                    ]
                );
            } else {
                $result->error(
                    'payment_file_missing',
                    'Fichier absent : ' . $relativepath,
                    [
                        'file' => $relativepath,
                    ]
                );
            }
        }
    }
}