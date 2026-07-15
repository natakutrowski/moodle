<?php

namespace local_subscriptions\crm\user\explorer;

defined('MOODLE_INTERNAL') || die();

final class UserExplorerColumnService {

    private UserExplorerPreferenceRepository $repository;

    public function __construct(
        ?UserExplorerPreferenceRepository $repository = null
    ) {
        $this->repository =
            $repository ??
            new UserExplorerPreferenceRepository();
    }

    public function get_columns(
        int $userid,
        bool $includeinbox = true
    ): array {
        return $this->repository->get_columns(
            $userid,
            $includeinbox
        );
    }

    public function save_columns(
        int $userid,
        array $columns,
        bool $includeinbox = true
    ): array {
        $columns = UserExplorerColumn::normalize(
            $columns,
            $includeinbox
        );

        $this->repository->save_columns(
            $userid,
            $columns,
            $includeinbox
        );

        return $columns;
    }

    public function reset(int $userid): void {
        $this->repository->reset_columns(
            $userid
        );
    }
}