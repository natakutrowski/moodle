<?php

namespace local_subscriptions\crm\admin_tools\repositories;

defined('MOODLE_INTERNAL') || die();

/**
 * Loads users associated with administrative tool executions.
 */
final class AdminToolActorRepository {

    /**
     * Loads several users in one database query.
     *
     * The returned array is indexed by user ID.
     *
     * @param int[] $actorids
     * @return \stdClass[]
     */
    public function find_by_ids(
        array $actorids
    ): array {
        global $DB;

        $actorids = array_values(
            array_unique(
                array_filter(
                    array_map(
                        'intval',
                        $actorids
                    ),
                    static fn(int $actorid): bool =>
                        $actorid > 0
                )
            )
        );

        if ($actorids === []) {
            return [];
        }

        return $DB->get_records_list(
            'user',
            'id',
            $actorids,
            '',
            implode(
                ',',
                [
                    'id',
                    'deleted',
                    'firstname',
                    'lastname',
                    'firstnamephonetic',
                    'lastnamephonetic',
                    'middlename',
                    'alternatename',
                ]
            )
        );
    }
}