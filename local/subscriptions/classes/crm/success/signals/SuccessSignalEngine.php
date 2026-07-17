<?php

namespace local_subscriptions\crm\success\signals;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\success\collection\SuccessMetricCollection;

/**
 * Converts collected metrics into normalized explainable signals.
 */
final class SuccessSignalEngine {

    public function __construct(
        private readonly SuccessSignalRuleRegistry $registry
    ) {
    }

    /**
     * @param array<string,string>|null $errors Receives sanitized rule errors.
     */
    public function evaluate(
        SuccessMetricCollection $metrics,
        ?int $detectedat = null,
        ?array &$errors = null
    ): SuccessSignalCollection {
        $detectedat = $detectedat ?? time();

        if ($detectedat <= 0) {
            throw new \InvalidArgumentException(
                'Customer Success signal timestamp must be greater than zero.'
            );
        }

        $userid = $metrics->userid();
        $signals = new SuccessSignalCollection();
        $ruleerrors = [];

        if ($userid === null) {
            $errors = [];
            return $signals;
        }

        foreach ($this->registry->all() as $rule) {
            try {
                if (!$rule->supports($metrics)) {
                    continue;
                }

                $result = $rule->evaluate(
                    $metrics,
                    $detectedat
                );

                if (
                    $result->userid() !== null &&
                    $result->userid() !== $userid
                ) {
                    throw new \UnexpectedValueException(
                        'Signal rule returned signals for another user.'
                    );
                }

                foreach ($result as $signal) {
                    if (!$signal instanceof SuccessSignal) {
                        throw new \UnexpectedValueException(
                            'Signal rule returned an invalid signal.'
                        );
                    }

                    if ($signal->userid !== $userid) {
                        throw new \UnexpectedValueException(
                            'Signal rule returned a signal for another user.'
                        );
                    }

                    $signals->add($signal);
                }
            } catch (\Throwable $exception) {
                $ruleerrors[$rule->key()] =
                    $this->sanitize_error($exception);
            }
        }

        $errors = $ruleerrors;

        return $signals;
    }

    private function sanitize_error(
        \Throwable $exception
    ): string {
        $message = trim($exception->getMessage());

        if ($message === '') {
            return 'Signal rule execution failed.';
        }

        $message = preg_replace(
            '/[\\r\\n\\t]+/',
            ' ',
            $message
        );

        $message = preg_replace(
            '/\\s{2,}/',
            ' ',
            (string)$message
        );

        return \core_text::substr(
            (string)$message,
            0,
            500
        );
    }
}