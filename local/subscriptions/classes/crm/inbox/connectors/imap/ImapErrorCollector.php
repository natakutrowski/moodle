<?php

namespace local_subscriptions\crm\inbox\connectors\imap;

defined('MOODLE_INTERNAL') || die();

final class ImapErrorCollector {

    /**
     * Vide les erreurs et alertes laissées par une opération précédente.
     */
    public function clear(): void {
        imap_errors();
        imap_alerts();
    }

    /**
     * @return string[]
     */
    public function collect(): array {
        $messages = [];

        $lasterror = imap_last_error();

        if (
            $lasterror !== false &&
            trim($lasterror) !== ''
        ) {
            $messages[] = trim($lasterror);
        }

        $errors = imap_errors();

        if (is_array($errors)) {
            foreach ($errors as $error) {
                $error = trim((string)$error);

                if ($error !== '') {
                    $messages[] = $error;
                }
            }
        }

        $alerts = imap_alerts();

        if (is_array($alerts)) {
            foreach ($alerts as $alert) {
                $alert = trim((string)$alert);

                if ($alert !== '') {
                    $messages[] = $alert;
                }
            }
        }

        return array_values(
            array_unique($messages)
        );
    }

    public function message(
        string $operation,
        array $context = []
    ): string {
        $parts = [$operation];

        if ($context) {
            $contextparts = [];

            foreach ($context as $key => $value) {
                if (
                    $value === null ||
                    $value === ''
                ) {
                    continue;
                }

                $contextparts[] =
                    $key . '=' . (string)$value;
            }

            if ($contextparts) {
                $parts[] =
                    '[' . implode(', ', $contextparts) . ']';
            }
        }

        $errors = $this->collect();

        if ($errors) {
            $parts[] = implode(' | ', $errors);
        }

        return implode(' ', $parts);
    }
}