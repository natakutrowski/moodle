<?php

namespace local_subscriptions\crm\help\onboarding;

defined('MOODLE_INTERNAL') || die();

final class HelpOnboardingService {

    public function __construct(
        private readonly HelpOnboardingRegistry $registry =
            new HelpOnboardingRegistry(),
        private readonly HelpOnboardingRepository $repository =
            new HelpOnboardingRepository()
    ) {
    }

    public function get_state(int $userid): \stdClass {
        $steps = $this->registry->steps();

        $completedids = $this->repository
            ->get_completed_step_ids($userid);

        $completedlookup = array_fill_keys(
            $completedids,
            true
        );

        $items = [];

        foreach ($steps as $step) {
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

        $percentage = $total > 0
            ? (int)round(($completed / $total) * 100)
            : 100;

        return (object)[
            'items' => $items,
            'total' => $total,
            'completed' => $completed,
            'remaining' => max(0, $total - $completed),
            'percentage' => $percentage,
            'finished' => $total === $completed,
        ];
    }

    public function mark_completed(
        int $userid,
        string $stepid
    ): void {
        $this->require_step($stepid);

        $completedids = $this->repository
            ->get_completed_step_ids($userid);

        $completedids[] = $stepid;

        $this->repository->save_completed_step_ids(
            $userid,
            $completedids
        );
    }

    public function mark_incomplete(
        int $userid,
        string $stepid
    ): void {
        $this->require_step($stepid);

        $completedids = $this->repository
            ->get_completed_step_ids($userid);

        $completedids = array_values(array_filter(
            $completedids,
            static fn(string $completedid): bool =>
                $completedid !== $stepid
        ));

        $this->repository->save_completed_step_ids(
            $userid,
            $completedids
        );
    }

    public function toggle(
        int $userid,
        string $stepid
    ): bool {
        $this->require_step($stepid);

        $completedids = $this->repository
            ->get_completed_step_ids($userid);

        if (in_array($stepid, $completedids, true)) {
            $this->mark_incomplete(
                $userid,
                $stepid
            );

            return false;
        }

        $this->mark_completed(
            $userid,
            $stepid
        );

        return true;
    }

    public function reset(int $userid): void {
        $this->repository->reset($userid);
    }

    private function require_step(string $stepid): void {
        if (!$this->registry->exists($stepid)) {
            throw new \moodle_exception(
                'crm_onboarding_invalid_step',
                'local_subscriptions'
            );
        }
    }
}