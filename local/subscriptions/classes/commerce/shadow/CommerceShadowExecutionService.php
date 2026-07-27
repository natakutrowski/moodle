<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\shadow;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentContext;
use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentResult;

/** Executes Native fulfillment in a strictly non-persistent, dry-run Shadow path. */
final class CommerceShadowExecutionService {
    public function __construct(
        private readonly CommerceShadowGrantSource $grants,
        private readonly CommerceShadowNativeExecutor $executor
    ) {
    }

    public function execute(
        string $purchasereference,
        string $source,
        ?int $actoruserid = null,
        ?int $now = null
    ): CommerceShadowExecutionReport {
        $purchasereference = trim($purchasereference);
        if ($purchasereference === '') {
            throw new \coding_exception('A purchase reference is required for Commerce Shadow execution.');
        }
        if (!CommerceShadowSource::is_valid($source)) {
            throw new \coding_exception('Invalid Commerce Shadow execution source.');
        }

        $startedat = $now ?? time();
        $executionreference = 'shadow-' . sha1($purchasereference . '|' . $source . '|' . $startedat);
        $context = CommerceNativeFulfillmentContext::dry_run(
            $executionreference,
            $startedat,
            $actoruserid,
            'shadow.' . $source,
            ['shadow' => true, 'postactions' => false, 'persistence' => false]
        );

        $results = [];
        $effects = [];
        $errors = [];
        foreach ($this->grants->find_for_purchase($purchasereference) as $grant) {
            try {
                $result = $this->executor->execute($grant, $context);
                $results[] = $result;
                if (!$result->is_failed()) {
                    $effects[] = new CommerceShadowEffect(
                        $grant->get_reference(),
                        $grant->get_type(),
                        $grant->get_resource_key(),
                        $grant->get_beneficiary_user_id(),
                        $grant->get_beneficiary_email(),
                        $this->effect_attributes($result)
                    );
                }
            } catch (\Throwable $exception) {
                $errors[] = [
                    'grantreference' => $grant->get_reference(),
                    'errorclass' => get_class($exception),
                    'message' => $exception->getMessage(),
                ];
            }
        }

        return new CommerceShadowExecutionReport(
            $purchasereference,
            $source,
            $executionreference,
            $startedat,
            $now ?? time(),
            $results,
            $effects,
            $errors
        );
    }

    private function effect_attributes(CommerceNativeFulfillmentResult $result): array {
        $payload = $result->get_payload();
        unset($payload['dryrun'], $payload['idempotencykey'], $payload['action']);
        return $payload;
    }
}
