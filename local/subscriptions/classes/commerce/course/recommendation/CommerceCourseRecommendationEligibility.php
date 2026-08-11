<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\course\recommendation;

defined('MOODLE_INTERNAL') || die();

/** Decides whether a course product is useful for a learner. */
final class CommerceCourseRecommendationEligibility {
    /**
     * @param int[] $productcourseids
     * @param int[] $accessiblecourseids
     * @param int[] $trialcourseids
     * @return array{relevant:bool,upgrade:bool,newcourseids:int[]}
     */
    public function evaluate(
        bool $productowned,
        array $productcourseids,
        array $accessiblecourseids,
        array $trialcourseids,
        bool $explicitupgrade = false
    ): array {
        $productcourses = $this->normalise($productcourseids);
        $accessible = $this->normalise($accessiblecourseids);
        $trials = $this->normalise($trialcourseids);

        $newcourseids = array_values(array_diff(array_keys($productcourses), array_keys($accessible)));
        $upgrade = $explicitupgrade || array_intersect_key($productcourses, $trials) !== [];

        // A product already linked to a learner may still be the legitimate upgrade path.
        // Outside an explicit upgrade, recommending an already-owned product is misleading.
        $relevant = (!$productowned && $newcourseids !== []) || $upgrade;

        return [
            'relevant' => $relevant,
            'upgrade' => $upgrade,
            'newcourseids' => $newcourseids,
        ];
    }

    /** @return array<int, true> */
    private function normalise(array $courseids): array {
        $result = [];
        foreach ($courseids as $courseid) {
            $courseid = (int)$courseid;
            if ($courseid > 0) {
                $result[$courseid] = true;
            }
        }
        return $result;
    }
}
