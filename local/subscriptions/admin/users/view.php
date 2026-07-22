<?php

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\crm\navigation\CrmBackLinkRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\user\HistoricalUserProfileRenderer;
use local_subscriptions\crm\user\UserProfileNotFoundException;
use local_subscriptions\crm\user360\workspace\User360WorkspaceRenderer;
use local_subscriptions\service\HistoricalUserProfileService;
use local_subscriptions\service\UserProfileService;
use local_subscriptions\subscription_config;

global $PAGE, $OUTPUT;

$context = AdminSecurity::require(
    Capabilities::VIEW_USERS
);

$id = required_param(
    'id',
    PARAM_INT
);

$url = new moodle_url(
    subscription_config::
        admin_user_view_page(),
    [
        'id' => $id,
    ]
);

$profile = null;
$historicalprofile = null;

try {
    $profile =
        UserProfileService::load(
            $id
        );
} catch (
    UserProfileNotFoundException $exception
) {
    if (!$exception->is_deleted()) {
        $pagetitle = get_string(
            'crm_user_not_found',
            'local_subscriptions'
        );

        CrmPageConfigurator::configure(
            $PAGE,
            $context,
            $url,
            $pagetitle,
            [
                'local-subscriptions-user-profile-page',
                'local-subscriptions-user-not-found-page',
            ]
        );

        echo $OUTPUT->header();

        echo CrmWorkspaceRenderer::start(
            CrmNavigationKeys::USERS,
            $context
        );

        echo CrmBreadcrumbRenderer::render(
            [
                [
                    'label' =>
                        get_string(
                            'crm_users',
                            'local_subscriptions'
                        ),

                    'url' =>
                        new moodle_url(
                            subscription_config::
                                admin_users_page()
                        ),
                ],
                [
                    'label' =>
                        $pagetitle,

                    'url' =>
                        null,
                ],
            ]
        );

        echo CrmPageHeader::render(
            $pagetitle,
            get_string(
                'crm_user_not_found_description',
                'local_subscriptions'
            ),
            HelpContext::USER_PROFILE
        );

        echo html_writer::start_div(
            'alert alert-warning',
            [
                'role' => 'alert',
            ]
        );

        echo html_writer::tag(
            'p',
            get_string(
                'crm_user_not_found_message',
                'local_subscriptions',
                $exception->get_userid()
            ),
            [
                'class' => 'mb-3',
            ]
        );

        echo html_writer::link(
            new moodle_url(
                subscription_config::
                    admin_users_page()
            ),
            get_string(
                'crm_user_not_found_back',
                'local_subscriptions'
            ),
            [
                'class' =>
                    'btn btn-primary',
            ]
        );

        echo html_writer::end_div();

        echo CrmWorkspaceRenderer::end();

        echo $OUTPUT->footer();

        exit;
    }

    $historicalprofile =
        HistoricalUserProfileService::create()
            ->load($id);
}

if ($historicalprofile !== null) {
    $pagetitle = get_string(
        'crm_user_history_title',
        'local_subscriptions',
        $historicalprofile->userid
    );
} else {
    $pagetitle =
        get_string(
            'crm_user_profile',
            'local_subscriptions'
        ) .
        ' - ' .
        fullname($profile->user);
}

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $url,
    $pagetitle,
    [
        'local-subscriptions-user-profile-page',
        'local-subscriptions-user-360-page',
    ]
);

/*
 * The editable workspace is only available for active users.
 */
if ($historicalprofile === null) {
    $PAGE->requires->js_call_amd(
        'local_subscriptions/workspace_edit_mode',
        'init'
    );

    $PAGE->requires->js_call_amd(
        'local_subscriptions/workspace_drag_drop',
        'init'
    );

    $PAGE->requires->js_call_amd(
        'local_subscriptions/workspace_item_menu',
        'init'
    );

    $PAGE->requires->js_call_amd(
        'local_subscriptions/workspace_personalization',
        'init'
    );
}

/*
 * The Timeline is available for active and historical profiles.
 */
$PAGE->requires->js_call_amd(
    'local_subscriptions/user_timeline',
    'init'
);

echo $OUTPUT->header();

echo CrmWorkspaceRenderer::start(
    CrmNavigationKeys::USERS,
    $context
);

echo CrmBreadcrumbRenderer::render(
    [
        [
            'label' =>
                get_string(
                    'crm_users',
                    'local_subscriptions'
                ),

            'url' =>
                new moodle_url(
                    subscription_config::
                        admin_users_page()
                ),
        ],
        [
            'label' =>
                $pagetitle,

            'url' =>
                null,
        ],
    ]
);

$backurl = new moodle_url(
    subscription_config::
        admin_users_page()
);

echo CrmBackLinkRenderer::render(
    $backurl,
    get_string(
        'back',
        'core'
    )
);

if ($historicalprofile !== null) {
    echo CrmPageHeader::render(
        get_string(
            'crm_user_history_title',
            'local_subscriptions',
            $historicalprofile->userid
        ),
        get_string(
            'crm_user_history_description',
            'local_subscriptions'
        ),
        HelpContext::USER_PROFILE
    );

    echo HistoricalUserProfileRenderer::render(
        $historicalprofile
    );
} else {
    echo CrmPageHeader::render(
        get_string(
            'crm_user_profile',
            'local_subscriptions'
        ),
        get_string(
            'crm_user_profile_help_description',
            'local_subscriptions'
        ),
        HelpContext::USER_PROFILE
    );

    echo User360WorkspaceRenderer::render(
        $profile
    );
}

echo CrmWorkspaceRenderer::end();

echo $OUTPUT->footer();