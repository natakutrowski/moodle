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
        $kpis = $this->build_kpis($criteria);

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
            $kpis,
            $canviewinbox
        );
    }


    private function build_kpis(UserExplorerCriteria $criteria): array {
        $filters = [
            '' => 'users',
            \local_subscriptions\crm\user\UserExplorerFilter::HOT_LEAD => 'hot_leads',
            \local_subscriptions\crm\user\UserExplorerFilter::AT_RISK => 'at_risk',
            \local_subscriptions\crm\user\UserExplorerFilter::VIP => 'vip',
        ];

        $counts = [];
        foreach ($filters as $filter => $key) {
            $scoped = $criteria->with_intelligence($filter);
            $counts[$key] = $this->repository->count($scoped)
                + $this->legacyguests->count($scoped);
        }

        $suspended = $criteria->with_account_status(
            UserExplorerCriteria::ACCOUNT_SUSPENDED
        );
        $counts['suspended'] = $this->repository->count($suspended);

        // The Legacy guest read model is the canonical identity source for
        // digital customers who do not yet have a Moodle account.
        $nomoodle = $criteria->with_account_status(
            UserExplorerCriteria::ACCOUNT_NO_MOODLE
        );
        $counts['no_moodle'] = $this->legacyguests->count($nomoodle);

        return $counts;
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
            UserExplorerSort::SCORE_ASC => ((int)($a->globalscore ?? 0)) <=> ((int)($b->globalscore ?? 0)),
            UserExplorerSort::SCORE_DESC => $descnum($a->globalscore ?? 0, $b->globalscore ?? 0),
            UserExplorerSort::RISK_ASC => ((int)($a->riskscore ?? 0)) <=> ((int)($b->riskscore ?? 0)),
            UserExplorerSort::RISK_DESC => $descnum($a->riskscore ?? 0, $b->riskscore ?? 0),
            UserExplorerSort::SUBSCRIPTIONS_ASC => ((int)($a->subscriptioncount ?? 0)) <=> ((int)($b->subscriptioncount ?? 0)),
            UserExplorerSort::SUBSCRIPTIONS_DESC => $descnum($a->subscriptioncount ?? 0, $b->subscriptioncount ?? 0),
            UserExplorerSort::PURCHASES_ASC => ((int)($a->purchasecount ?? 0)) <=> ((int)($b->purchasecount ?? 0)),
            UserExplorerSort::PURCHASES_DESC => $descnum($a->purchasecount ?? 0, $b->purchasecount ?? 0),
            UserExplorerSort::LAST_ACCESS_ASC => ((int)($a->lastaccess ?? 0)) <=> ((int)($b->lastaccess ?? 0)),
            UserExplorerSort::LAST_ACCESS_DESC => $descnum($a->lastaccess ?? 0, $b->lastaccess ?? 0),
            UserExplorerSort::CREATED_ASC => ((int)($a->timecreated ?? 0)) <=> ((int)($b->timecreated ?? 0)),
            UserExplorerSort::CREATED_DESC => $descnum($a->timecreated ?? 0, $b->timecreated ?? 0),
            default => strcmp($name($a), $name($b)),
        };

        return $cmp !== 0 ? $cmp : strcmp(
            (string)($a->email ?? ''),
            (string)($b->email ?? '')
        );
    }

}