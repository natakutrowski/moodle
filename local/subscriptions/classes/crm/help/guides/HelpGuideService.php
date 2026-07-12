<?php

namespace local_subscriptions\crm\help\guides;

defined('MOODLE_INTERNAL') || die();

final class HelpGuideService {

    private HelpGuideRegistry $registry;
    private HelpGuideProgressRepository $repository;

    public function __construct(
        ?HelpGuideRegistry $registry = null,
        ?HelpGuideProgressRepository $repository = null
    ) {
        $this->registry =
            $registry ?? new HelpGuideRegistry();

        $this->repository =
            $repository ?? new HelpGuideProgressRepository();
    }

    public function get_state(
        int $userid,
        string $guideid
    ): \stdClass {
        $guide = $this->require_guide($guideid);

        $completedids = $this->repository
            ->get_completed_step_ids(
                $userid,
                $guideid
            );

        $completedlookup = array_fill_keys(
            $completedids,
            true
        );

        $items = [];

        foreach ($guide->steps as $step) {
            $items[] = (object)[
                'step' => $step,
                'completed' => isset(
                    $completedlookup[$step->id]
                ),
            ];
        }

        $total = count($items);

        $completed = count(array_filter(
            $items,
            static fn(\stdClass $item): bool =>
                $item->completed
        ));

        return (object)[
            'guide' => $guide,
            'items' => $items,
            'total' => $total,
            'completed' => $completed,
            'percentage' => $total > 0
                ? (int)round(
                    ($completed / $total) * 100
                )
                : 100,
            'finished' => $total === $completed,
        ];
    }

    public function toggle_step(
        int $userid,
        string $guideid,
        string $stepid
    ): bool {
        $guide = $this->require_guide($guideid);

        if ($guide->get_step($stepid) === null) {
            throw new \moodle_exception(
                'crm_help_guide_step_not_found',
                'local_subscriptions'
            );
        }

        $completedids = $this->repository
            ->get_completed_step_ids(
                $userid,
                $guideid
            );

        if (in_array($stepid, $completedids, true)) {
            $completedids = array_values(array_filter(
                $completedids,
                static fn(string $completedid): bool =>
                    $completedid !== $stepid
            ));

            $this->repository
                ->save_completed_step_ids(
                    $userid,
                    $guideid,
                    $completedids
                );

            return false;
        }

        $completedids[] = $stepid;

        $this->repository->save_completed_step_ids(
            $userid,
            $guideid,
            $completedids
        );

        return true;
    }

    public function reset(
        int $userid,
        string $guideid
    ): void {
        $this->require_guide($guideid);

        $this->repository->reset(
            $userid,
            $guideid
        );
    }

    private function require_guide(
        string $guideid
    ): HelpGuide {
        $guide = $this->registry->get_guide(
            $guideid
        );

        if ($guide === null) {
            throw new \moodle_exception(
                'crm_help_guide_not_found',
                'local_subscriptions'
            );
        }

        return $guide;
    }
}