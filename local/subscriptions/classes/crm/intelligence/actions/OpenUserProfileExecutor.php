<?php

namespace local_subscriptions\crm\intelligence\actions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\subscription_config;
use moodle_url;

final class OpenUserProfileExecutor implements RecommendationExecutorInterface {

    public function supports(string $action): bool {
        return $action === 'open_user_profile';
    }

    public function execute(int $userid, array $params = []): RecommendationExecutionResult {
        return new RecommendationExecutionResult(true, 'open_user_profile', [
            'url' => (new moodle_url(subscription_config::admin_user_view_page(), ['id' => $userid]))->out(false),
        ]);
    }
}