<?php

namespace local_subscriptions\crm\user\explorer;

defined('MOODLE_INTERNAL') || die();

final class UserExplorerExportService {

    private UserExplorerRepository $repository;

    public function __construct(
        ?UserExplorerRepository $repository = null
    ) {
        $this->repository =
            $repository ?? new UserExplorerRepository();
    }

    public function export(
        UserExplorerCriteria $criteria,
        array $columns,
        int $limit = 5000
    ): void {
        global $CFG;

        require_once(
            $CFG->libdir . '/csvlib.class.php'
        );

        $records = $this->repository
            ->get_records_for_export(
                $criteria,
                $limit
            );

        $userids = array_map(
            static fn(\stdClass $record): int =>
                (int)$record->id,
            $records
        );

        $tagsbyuser = $this->repository
            ->get_tags_by_userids($userids);

        $columns = UserExplorerColumn::normalize(
            $columns
        );

        $csv = new \csv_export_writer();

        $csv->set_filename(
            'crm-users-' . date('Y-m-d-His')
        );

        $csv->add_data(array_map(
            static fn(string $column): string =>
                UserExplorerColumn::label($column),
            $columns
        ));

        foreach ($records as $record) {
            $viewmodel =
                UserExplorerUserViewModel::from_record(
                    $record,
                    $tagsbyuser[(int)$record->id] ?? []
                );

            $row = [];

            foreach ($columns as $column) {
                $row[] = $this->column_value(
                    $viewmodel,
                    $column
                );
            }

            $csv->add_data($row);
        }

        $csv->download_file();
        exit;
    }

    private function column_value(
        UserExplorerUserViewModel $viewmodel,
        string $column
    ): string|int {
        $user = $viewmodel->user;

        return match ($column) {
            UserExplorerColumn::USER =>
                fullname($user) . ' <' . $user->email . '>',

            UserExplorerColumn::TAGS =>
                implode(', ', array_map(
                    static fn(\stdClass $tag): string =>
                        $tag->label,
                    $viewmodel->tags
                )),

            UserExplorerColumn::SCORE =>
                (int)$user->globalscore,

            UserExplorerColumn::RISK =>
                (int)$user->riskscore,

            UserExplorerColumn::INTELLIGENCE =>
                implode(', ', array_map(
                    static fn(\stdClass $item): string =>
                        $item->label,
                    array_merge(
                        $viewmodel->segments,
                        $viewmodel->opportunities
                    )
                )),

            UserExplorerColumn::SUBSCRIPTIONS =>
                (int)$user->subscriptioncount,

            UserExplorerColumn::PURCHASES =>
                (int)$user->purchasecount,

            UserExplorerColumn::COUNTRY =>
                (string)$user->country,

            UserExplorerColumn::REGISTERED =>
                !empty($user->timecreated)
                    ? userdate(
                        (int)$user->timecreated,
                        get_string('strftimedatetimeshort')
                    )
                    : '',

            UserExplorerColumn::LAST_ACCESS =>
                !empty($user->lastaccess)
                    ? userdate(
                        (int)$user->lastaccess,
                        get_string('strftimedatetimeshort')
                    )
                    : '',

            default => '',
        };
    }
}