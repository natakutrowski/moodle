<?php

namespace local_subscriptions\crm\intelligence\actions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\admin\Capabilities;

final class RecommendationActionRegistry {

    /** @var RecommendationExecutorInterface[] */
    private array $executors;

    public function __construct(?array $executors = null) {
        $this->executors = $executors ?? [
            new OpenUserProfileExecutor(),
        ];
    }

    public function execute(string $action, int $userid, array $params = []): RecommendationExecutionResult {
        if (!Capabilities::can_manage_users()) {
            return new RecommendationExecutionResult(false, 'permission_denied');
        }

        foreach ($this->executors as $executor) {
            if ($executor->supports($action)) {
                return $executor->execute($userid, $params);
            }
        }

        return new RecommendationExecutionResult(false, 'unsupported_action');
    }
}