<?php

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\user\UserProfileTimelineBuilder;
use local_subscriptions\output\UserProfileRenderer;

global $DB;

AdminSecurity::require(
    Capabilities::VIEW_USERS
);

require_sesskey();

$userid = required_param(
    'userid',
    PARAM_INT
);

$offset = optional_param(
    'offset',
    0,
    PARAM_INT
);

$limit = optional_param(
    'limit',
    20,
    PARAM_INT
);

$limit = max(
    1,
    min(50, $limit)
);

$offset = max(
    0,
    $offset
);

/*
 * Deleted Moodle users are intentionally accepted here so their
 * historical read-only Timeline can also use progressive loading.
 */
$user = $DB->get_record(
    'user',
    [
        'id' => $userid,
    ],
    'id,email,deleted,suspended'
);

if (!$user) {
    throw new moodle_exception(
        'crm_user_not_found',
        'local_subscriptions'
    );
}

$includeinbox =
    Capabilities::can_view_inbox();

$builder =
    new UserProfileTimelineBuilder();

$page =
    $builder->build_page_for_user(
        $user,
        $limit,
        $offset,
        $includeinbox
    );

$events =
    $builder->to_legacy_objects(
        $page->events
    );

$groups =
    UserProfileRenderer::
        render_timeline_ajax_groups(
            $events
        );

header(
    'Content-Type: application/json; charset=utf-8'
);

echo json_encode(
    [
        'success' => true,
        'groups' => $groups,
        'hasMore' => $page->hasmore,
        'nextOffset' => $page->next_offset(),
    ],
    JSON_THROW_ON_ERROR
);