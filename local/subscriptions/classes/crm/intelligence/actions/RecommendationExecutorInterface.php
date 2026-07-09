<?php

namespace local_subscriptions\crm\intelligence\actions;

defined('MOODLE_INTERNAL') || die();

interface RecommendationExecutorInterface {

    public function supports(string $action): bool;

    public function execute(int $userid, array $params = []): RecommendationExecutionResult;
}