<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\idempotency;

defined('MOODLE_INTERNAL') || die();

final class CommerceIdempotencyService {
    public function __construct(
        private readonly CommerceIdempotencyRepository $repository
    ) {
    }

    public function execute(string $scope, string $key, array $payload, callable $operation): array {
        $scope = trim($scope);
        $key = trim($key);
        if ($scope === '' || $key === '') {
            throw new \InvalidArgumentException('Idempotency scope and key are required.');
        }

        $payloadhash = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
        $record = $this->repository->reserve($scope, $key, $payloadhash);

        if ($record->is_completed()) {
            return [
                'replayed' => true,
                'result' => $record->get_result() ?? [],
            ];
        }

        if ($record->get_status() === 'processing' && $record->get_result() === null) {
            try {
                $result = $operation();
                if (!is_array($result)) {
                    throw new \RuntimeException('Idempotent Commerce operation must return an array.');
                }
                $this->repository->complete($record->get_id(), $result);
                return ['replayed' => false, 'result' => $result];
            } catch (\Throwable $exception) {
                $this->repository->fail($record->get_id(), $exception->getMessage());
                throw $exception;
            }
        }

        throw new \RuntimeException('Commerce idempotency key is not executable in its current state.');
    }
}
