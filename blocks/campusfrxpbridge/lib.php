<?php

defined('MOODLE_INTERNAL') || die();

/**
 * Level Up XP callback invoked after every positive personal XP increase.
 *
 * No block instance is required. Level Up XP discovers block callbacks by plugin.
 * Personal XP remains authoritative; failures here never roll it back.
 *
 * Level Up XP may use either a course context or a system context. The latter is
 * its native site-wide XP mode and deliberately carries no source course.
 *
 * @param \context $context Level Up XP world context.
 * @param int $id User id.
 * @param int $points Positive XP delta.
 * @return void
 */
function block_campusfrxpbridge_xp_points_increased(\context $context, $id, $points): void {
    $userid = (int)$id;
    $points = (int)$points;
    if ($userid <= 0 || $points <= 0) {
        return;
    }

    try {
        $timeearned = time();
        $gainid = implode(':', [
            'gain',
            (string)$context->contextlevel,
            (string)$context->instanceid,
            (string)$userid,
            (string)$timeearned,
            bin2hex(random_bytes(16)),
        ]);

        $service = \local_subscriptions\commerce\education\xp\CommercePedagogicalLevelupXpBridgeService::create();

        if ($context->contextlevel === CONTEXT_SYSTEM) {
            $service->record_global_gain(
                $userid,
                $points,
                $timeearned,
                $gainid
            );
            return;
        }

        $coursecontext = $context->contextlevel === CONTEXT_COURSE
            ? $context
            : $context->get_course_context(false);
        if (!$coursecontext) {
            return;
        }

        $service->record_gain(
            $userid,
            (int)$coursecontext->instanceid,
            $points,
            $timeearned,
            $gainid
        );
    } catch (\Throwable $e) {
        debugging('CampusFR Level Up XP promotion bridge failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
    }
}

/**
 * Level Up XP callback invoked when a matching positive reward is suppressed
 * because the rule/reason repeat limit has already been reached.
 *
 * This never changes personal XP. CampusFR may award a promotion-only replay
 * once when the matching personal reward predates the learner's current promo.
 *
 * @param \context $context Level Up world context.
 * @param int $id User id.
 * @param int $points Configured rule points that were not added personally.
 * @param int $ruleid Level Up rule id.
 * @param \block_xp\local\reason\reason $reason Matched reason.
 * @param \block_xp\local\ruletype\limit_spec $limit Reached repeat limit.
 * @return void
 */
function block_campusfrxpbridge_xp_points_suppressed_by_reason_limit(
    \context $context,
    $id,
    $points,
    $ruleid,
    \block_xp\local\reason\reason $reason,
    \block_xp\local\ruletype\limit_spec $limit
): void {
    $userid = (int)$id;
    $points = (int)$points;
    $ruleid = (int)$ruleid;
    if ($userid <= 0 || $points <= 0 || $ruleid <= 0) {
        return;
    }

    try {
        $reasonclass = get_class($reason);
        $reasonname = $reasonclass;
        $prefix = 'block_xp\\local\\reason\\';
        if (str_starts_with($reasonname, $prefix)) {
            $reasonname = substr($reasonname, strlen($prefix));
            if (str_ends_with($reasonname, '_reason')) {
                $reasonname = substr($reasonname, 0, -7);
            }
        }

        $subtype = $reason instanceof \block_xp\local\reason\reason_with_subtype
            ? $reason->get_subtype()
            : null;
        $envid = null;
        $parentid = null;
        $objectid = null;
        if ($reason instanceof \block_xp\local\reason\reason_with_tracking) {
            $envid = $reason->get_env_id();
            $parentid = $reason->get_parent_id();
            $objectid = $reason->get_object_id();
        }

        $service = \local_subscriptions\commerce\education\xp\CommercePedagogicalLevelupReplayBridgeService::create();
        $timeearned = time();
        $scope = $limit->get_scope();

        if ($context->contextlevel === CONTEXT_SYSTEM) {
            $service->record_global_replay(
                $userid,
                $points,
                $timeearned,
                (int)$context->id,
                $ruleid,
                $reasonname,
                $subtype,
                $envid,
                $parentid,
                $objectid,
                $scope
            );
            return;
        }

        $coursecontext = $context->contextlevel === CONTEXT_COURSE
            ? $context
            : $context->get_course_context(false);
        if (!$coursecontext) {
            return;
        }

        $service->record_course_replay(
            $userid,
            (int)$coursecontext->instanceid,
            $points,
            $timeearned,
            (int)$context->id,
            $ruleid,
            $reasonname,
            $subtype,
            $envid,
            $parentid,
            $objectid,
            $scope
        );
    } catch (\Throwable $e) {
        debugging('CampusFR Level Up XP team-only replay bridge failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
    }
}
