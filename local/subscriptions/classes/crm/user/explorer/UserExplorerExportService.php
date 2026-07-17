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
        int $limit = 5000,
        bool $includeinbox = false
    ): void {
        global $CFG;

        require_once(
            $CFG->libdir . '/csvlib.class.php'
        );

        if (!$includeinbox) {
            $criteria =
                $criteria->without_inbox();
        }

        $records = $this->repository
            ->get_records_for_export(
                $criteria,
                $limit,
                $includeinbox
            );

        $userids = array_map(
            static fn(\stdClass $record): int =>
                (int)$record->id,
            $records
        );

        $tagsbyuser = $this->repository
            ->get_tags_by_userids($userids);

        $columns = UserExplorerColumn::normalize(
            $columns,
            $includeinbox
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

            UserExplorerColumn::INBOX =>
                $this->inbox_export_value(
                    $viewmodel
                ),

            UserExplorerColumn::CUSTOMER_SUCCESS_PLANS =>
                $this->customer_success_export_value(
                    $viewmodel
                ),

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
    
    private function customer_success_export_value(
        UserExplorerUserViewModel $viewmodel
    ): string {
        $user = $viewmodel->user;

        $opencount = (int)(
            $user->customer_success_open_count
            ?? 0
        );

        if ($opencount <= 0) {
            return '';
        }

        $parts = [
            get_string(
                'crm_user_customer_success_open_count',
                'local_subscriptions',
                $opencount
            ),
        ];

        $blockedcount = (int)(
            $user->customer_success_blocked_count
            ?? 0
        );

        if ($blockedcount > 0) {
            $parts[] = get_string(
                'crm_user_customer_success_blocked_count',
                'local_subscriptions',
                $blockedcount
            );
        }

        $priority = trim(
            (string)(
                $user->customer_success_highest_priority
                ?? ''
            )
        );

        if ($priority !== '') {
            $parts[] = get_string(
                'csplanpriority_' . $priority,
                'local_subscriptions'
            );
        }

        return implode(' | ', $parts);
    }

    private function inbox_export_value(
        UserExplorerUserViewModel $viewmodel
    ): string {
        $user = $viewmodel->user;

        $conversationcount = (int)(
            $user->inboxconversationcount
            ?? 0
        );

        if ($conversationcount <= 0) {
            return '';
        }

        return implode(
            ' | ',
            [
                get_string(
                    'crm_user_inbox_conversation_count',
                    'local_subscriptions',
                    $conversationcount
                ),

                get_string(
                    'crm_user_inbox_open_count',
                    'local_subscriptions',
                    (int)(
                        $user->inboxopenconversationcount
                        ?? 0
                    )
                ),

                get_string(
                    'crm_user_inbox_unread_count',
                    'local_subscriptions',
                    (int)(
                        $user->inboxunreadcount
                        ?? 0
                    )
                ),

                get_string(
                    'crm_user_inbox_urgent_count',
                    'local_subscriptions',
                    (int)(
                        $user->inboxurgentcount
                        ?? 0
                    )
                ),
            ]
        );
    }

}