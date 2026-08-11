<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail;

defined('MOODLE_INTERNAL') || die();

/**
 * Idempotent entry point for transactional mail intents.
 */
final class CommerceMailQueueService {

    public function __construct(
        private readonly CommerceMailQueueRepository $repository
    ) {
    }

    public function queue(CommerceMailRequest $request, int $maxattempts = 5): \stdClass {
        return $this->repository->enqueue($request, $maxattempts);
    }
}
