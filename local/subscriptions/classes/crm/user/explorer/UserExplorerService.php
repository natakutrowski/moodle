<?php

namespace local_subscriptions\crm\user\explorer;

defined('MOODLE_INTERNAL') || die();

final class UserExplorerService {

    private UserExplorerRepository $repository;

    public function __construct(
        ?UserExplorerRepository $repository = null
    ) {
        $this->repository =
            $repository ?? new UserExplorerRepository();
    }

    public function explore(
        UserExplorerCriteria $criteria
    ): UserExplorerResult {

        global $USER;

        $total = $this->repository->count($criteria);
        $lastpage = $total > 0
            ? (int)floor(($total - 1) / $criteria->perpage)
            : 0;

        if ($criteria->page > $lastpage) {
            $criteria = $criteria->with_page($lastpage);
        }

        $records = $this->repository
            ->get_records($criteria);

        $userids = array_map(
            static fn(\stdClass $record): int =>
                (int)$record->id,
            $records
        );

        $tagsbyuser = $this->repository
            ->get_tags_by_userids($userids);

        $users = [];

        foreach ($records as $record) {
            $userid = (int)$record->id;

            $users[] =
                UserExplorerUserViewModel::from_record(
                    $record,
                    $tagsbyuser[$userid] ?? []
                );
        }

        $preferencerepository =
            new UserExplorerPreferenceRepository();

        return new UserExplorerResult(
            $criteria,
            $users,
            $total,
            $this->repository->get_available_countries(),
            $this->repository->get_available_tags(),
            $preferencerepository->get_columns(
                (int)$USER->id
            ),
            (new UserExplorerSavedViewService(
                $preferencerepository
            ))->get_views(
                (int)$USER->id
            )
        );
    }
}