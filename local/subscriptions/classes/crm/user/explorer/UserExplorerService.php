<?php

namespace local_subscriptions\crm\user\explorer;

defined('MOODLE_INTERNAL') || die();

final class UserExplorerService {

    private UserExplorerRepository $repository;
    private UserExplorerLegacyGuestRepository $legacyguests;

    public function __construct(
        ?UserExplorerRepository $repository = null,
        ?UserExplorerLegacyGuestRepository $legacyguests = null
    ) {
        $this->repository = $repository ?? new UserExplorerRepository();
        $this->legacyguests = $legacyguests ?? new UserExplorerLegacyGuestRepository();
    }

    public function explore(
        UserExplorerCriteria $criteria,
        bool $canviewinbox = false
    ): UserExplorerResult {
        global $USER;

        if (!$canviewinbox) {
            $criteria =
                $criteria->without_inbox();
        }

        $moodletotal = $this->repository->count($criteria);
        $guesttotal = $this->legacyguests->count($criteria);
        $total = $moodletotal + $guesttotal;

        $lastpage = $total > 0
            ? (int)floor(
                ($total - 1) /
                $criteria->perpage
            )
            : 0;

        if ($criteria->page > $lastpage) {
            $criteria =
                $criteria->with_page(
                    $lastpage
                );
        }

        // Fetch enough rows from each identity source to build the requested
        // combined page correctly, then apply the Explorer sort once.
        $window = $criteria->offset() + $criteria->perpage;
        $moodlecriteria = $criteria->with_page(0);
        $records = $this->repository->get_records_for_export(
            $moodlecriteria,
            $window,
            $canviewinbox
        );
        $records = array_merge(
            $records,
            $this->legacyguests->get_records($criteria, $window)
        );
        usort($records, fn(\stdClass $a, \stdClass $b): int =>
            $this->compare_records($a, $b, $criteria->sort)
        );
        $records = array_slice(
            $records,
            $criteria->offset(),
            $criteria->perpage
        );

        $userids = array_map(
            static fn(
                \stdClass $record
            ): int => (int)$record->id,
            $records
        );

        $tagsbyuser = $this->repository
            ->get_tags_by_userids(
                $userids
            );

        $users = [];

        foreach ($records as $record) {
            $userid = (int)$record->id;

            $users[] =
                UserExplorerUserViewModel::
                    from_record(
                        $record,
                        $tagsbyuser[
                            $userid
                        ] ?? []
                    );
        }

        $preferencerepository =
            new UserExplorerPreferenceRepository();

        return new UserExplorerResult(
            $criteria,
            $users,
            $total,
            $this->repository
                ->get_available_countries(),
            $this->repository
                ->get_available_tags(),
            $preferencerepository
                ->get_columns(
                    (int)$USER->id,
                    $canviewinbox
                ),
            (
                new UserExplorerSavedViewService(
                    $preferencerepository
                )
            )->get_views(
                (int)$USER->id,
                $canviewinbox
            ),
            $canviewinbox
        );
    }

    private function compare_records(
        \stdClass $a,
        \stdClass $b,
        string $sort
    ): int {
        $name = static function (\stdClass $record): string {
            return \core_text::strtolower(trim(
                (string)($record->lastname ?? '') . ' ' .
                (string)($record->firstname ?? '') . ' ' .
                (string)($record->email ?? '')
            ));
        };

        $descnum = static function (mixed $left, mixed $right): int {
            return ((int)$right) <=> ((int)$left);
        };

        $cmp = match (UserExplorerSort::normalize($sort)) {
            UserExplorerSort::NAME_DESC => strcmp($name($b), $name($a)),
            UserExplorerSort::SCORE_DESC => $descnum($a->globalscore ?? 0, $b->globalscore ?? 0),
            UserExplorerSort::RISK_DESC => $descnum($a->riskscore ?? 0, $b->riskscore ?? 0),
            UserExplorerSort::LAST_ACCESS_DESC => $descnum($a->lastaccess ?? 0, $b->lastaccess ?? 0),
            UserExplorerSort::CREATED_DESC => $descnum($a->timecreated ?? 0, $b->timecreated ?? 0),
            default => strcmp($name($a), $name($b)),
        };

        return $cmp !== 0 ? $cmp : strcmp(
            (string)($a->email ?? ''),
            (string)($b->email ?? '')
        );
    }

}