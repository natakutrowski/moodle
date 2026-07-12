<?php

namespace local_subscriptions\crm\user\explorer;

defined('MOODLE_INTERNAL') || die();

final class UserExplorerColumnService {

    private UserExplorerPreferenceRepository $repository;

    public function __construct(
        ?UserExplorerPreferenceRepository $repository = null
    ) {
        $this->repository =
            $repository ?? new UserExplorerPreferenceRepository();
    }

    public function get_columns(int $userid): array {
        return $this->repository->get_columns($userid);
    }

    public function save_columns(
        int $userid,
        array $columns
    ): array {
        $columns = UserExplorerColumn::normalize($columns);

        $this->repository->save_columns(
            $userid,
            $columns
        );

        return $columns;
    }

    public function reset(int $userid): void {
        $this->repository->reset_columns($userid);
    }
}