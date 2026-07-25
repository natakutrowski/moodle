<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\command\service;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\command\dto\CommerceCommandRequest;
use local_subscriptions\commerce\command\dto\CommerceCommandResult;
use local_subscriptions\commerce\dualwrite\CommerceDualWriteService;
use local_subscriptions\commerce\idempotency\CommerceIdempotencyService;

final class CommercePurchaseCommandService {
    public function __construct(
        private readonly CommerceDualWriteService $dualwrite,
        private readonly ?CommerceIdempotencyService $idempotency = null
    ) {
    }

    public function synchronise(CommerceCommandRequest $request): CommerceCommandResult {
        $key = $request->get_idempotency_key();
        if ($key === null || $key === '' || $this->idempotency === null) {
            return $this->run($request);
        }

        $execution = $this->idempotency->execute(
            'purchase_command:' . $request->get_consumer(),
            $key,
            [
                'family' => $request->get_family(),
                'legacyid' => $request->get_legacy_id(),
                'trigger' => $request->get_trigger(),
            ],
            fn(): array => $this->run($request)->to_array()
        );

        return CommerceCommandResult::from_array($execution['result'])
            ->with_replayed((bool)$execution['replayed']);
    }

    private function run(CommerceCommandRequest $request): CommerceCommandResult {
        $result = $this->dualwrite->synchronise(
            $request->get_family(),
            $request->get_legacy_id(),
            $request->get_trigger()
        );

        return new CommerceCommandResult(
            $result->get_family(),
            $result->get_legacy_id(),
            $result->get_status(),
            $result->get_purchase_uuid(),
            $result->get_differences(),
            $result->get_error_message()
        );
    }
}
