<?php

namespace local_subscriptions\commandcenter;

defined('MOODLE_INTERNAL') || die();

interface CommandProviderInterface {

    /**
     * @return array<int, array{
     *     icon: string,
     *     type: string,
     *     title: string,
     *     subtitle: string,
     *     url: string,
     *     score: int
     * }>
     */
    public function search(CommandQuery $query, int $limit = 10): array;
}