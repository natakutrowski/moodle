<?php

namespace local_subscriptions\crm\user\explorer;

defined('MOODLE_INTERNAL') || die();

final class UserExplorerSavedViewService {

    private const MAX_VIEWS = 20;

    private UserExplorerPreferenceRepository $repository;

    public function __construct(
        ?UserExplorerPreferenceRepository $repository = null
    ) {
        $this->repository =
            $repository ?? new UserExplorerPreferenceRepository();
    }

    public function get_views(int $userid): array {
        $views = [];

        foreach (
            $this->repository->get_saved_views($userid)
            as $rawview
        ) {
            if (!is_array($rawview)) {
                continue;
            }

            $view = UserExplorerSavedView::from_array(
                $rawview
            );

            if ($view !== null) {
                $views[] = $view;
            }
        }

        usort(
            $views,
            static fn(
                UserExplorerSavedView $a,
                UserExplorerSavedView $b
            ): int => $b->timecreated <=> $a->timecreated
        );

        return $views;
    }

    public function save(
        int $userid,
        string $name,
        UserExplorerCriteria $criteria
    ): UserExplorerSavedView {
        $name = trim(clean_param($name, PARAM_TEXT));

        if ($name === '') {
            throw new \moodle_exception(
                'crm_user_view_name_required',
                'local_subscriptions'
            );
        }

        $views = $this->get_views($userid);

        if (count($views) >= self::MAX_VIEWS) {
            throw new \moodle_exception(
                'crm_user_view_limit_reached',
                'local_subscriptions',
                '',
                self::MAX_VIEWS
            );
        }

        $view = new UserExplorerSavedView(
            'view_' . bin2hex(random_bytes(6)),
            $name,
            $criteria->saved_params(),
            time()
        );

        $views[] = $view;

        $this->repository->save_views(
            $userid,
            array_map(
                static fn(
                    UserExplorerSavedView $savedview
                ): array => $savedview->to_array(),
                $views
            )
        );

        return $view;
    }

    public function delete(
        int $userid,
        string $viewid
    ): void {
        $views = array_values(array_filter(
            $this->get_views($userid),
            static fn(
                UserExplorerSavedView $view
            ): bool => $view->id !== $viewid
        ));

        $this->repository->save_views(
            $userid,
            array_map(
                static fn(
                    UserExplorerSavedView $view
                ): array => $view->to_array(),
                $views
            )
        );
    }
}