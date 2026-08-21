<?php

namespace local_subscriptions\crm\admin_tools\rendering;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\crm\admin_tools\AdminTool;
use local_subscriptions\crm\admin_tools\AdminToolKeys;
use local_subscriptions\crm\admin_tools\AdminToolRegistry;
use local_subscriptions\crm\admin_tools\repositories\AdminToolActorRepository;
use local_subscriptions\crm\admin_tools\repositories\AdminToolRunRepository;
use local_subscriptions\subscription_config;
use moodle_url;

/**
 * Renders CRM administrative tools and their history.
 */
final class AdminToolRenderer {

    /**
     * @param AdminTool[] $tools
     */
    public static function render_catalogue(
        array $tools,
        AdminToolRunRepository $repository
    ): string {
        if ($tools === []) {
            return html_writer::div(
                get_string(
                    'crm_admin_tools_empty',
                    'local_subscriptions'
                ),
                'alert alert-info'
            );
        }

        $groups = [
            'messaging' => [
                'title' =>
                    get_string(
                        'crm_admin_tools_group_messaging_n128a',
                        'local_subscriptions'
                    ),
                'description' =>
                    get_string(
                        'crm_admin_tools_group_messaging_help_n128a',
                        'local_subscriptions'
                    ),
                'keys' => [
                    AdminToolKeys::INBOX_SYNC,
                    AdminToolKeys::INBOX_DIAGNOSTICS,
                ],
            ],
            'automation' => [
                'title' =>
                    get_string(
                        'crm_admin_tools_group_automation_n128a',
                        'local_subscriptions'
                    ),
                'description' =>
                    get_string(
                        'crm_admin_tools_group_automation_help_n128a',
                        'local_subscriptions'
                    ),
                'keys' => [
                    AdminToolKeys::AUTOMATIONS,
                    AdminToolKeys::INTELLIGENCE_SNAPSHOT,
                    AdminToolKeys::RECOMMENDATIONS,
                ],
            ],
            'operations' => [
                'title' =>
                    get_string(
                        'crm_admin_tools_group_operations_n128a',
                        'local_subscriptions'
                    ),
                'description' =>
                    get_string(
                        'crm_admin_tools_group_operations_help_n128a',
                        'local_subscriptions'
                    ),
                'keys' => [
                    AdminToolKeys::DIGITAL_RECONCILIATION,
                    AdminToolKeys::HELP_VALIDATION,
                ],
            ],
        ];

        $bykey = [];

        foreach ($tools as $tool) {
            $bykey[$tool->key()] = $tool;
        }

        $sections = [];

        foreach ($groups as $key => $group) {
            $cards = [];

            foreach ($group['keys'] as $toolkey) {
                if (!isset($bykey[$toolkey])) {
                    continue;
                }

                $tool = $bykey[$toolkey];

                $cards[] =
                    self::render_tool_card(
                        $tool,
                        $repository->last_for_tool(
                            $tool->key()
                        )
                    );

                unset($bykey[$toolkey]);
            }

            if ($cards === []) {
                continue;
            }

            $sections[] = html_writer::tag(
                'section',
                html_writer::div(
                    html_writer::tag(
                        'h2',
                        s($group['title']),
                        [
                            'class' =>
                                'crm-admin-tools-group-title',
                        ]
                    )
                    . html_writer::div(
                        s($group['description']),
                        'crm-admin-tools-group-description'
                    ),
                    'crm-admin-tools-group-heading'
                )
                . html_writer::div(
                    implode('', $cards),
                    'crm-admin-tools-grid'
                ),
                [
                    'class' =>
                        'crm-admin-tools-group '
                        . 'crm-admin-tools-group-' . $key,
                ]
            );
        }

        if ($bykey !== []) {
            $cards = [];

            foreach ($bykey as $tool) {
                $cards[] = self::render_tool_card(
                    $tool,
                    $repository->last_for_tool(
                        $tool->key()
                    )
                );
            }

            $sections[] = html_writer::div(
                implode('', $cards),
                'crm-admin-tools-grid'
            );
        }

        return html_writer::div(
            implode('', $sections),
            'crm-admin-tools-catalogue'
        );
    }


    private static function render_tool_card(
        AdminTool $tool,
        ?\stdClass $last
    ): string {
        $actionurl = new moodle_url(
            subscription_config::
                admin_crm_tool_action_page(),
            [
                'tool' => $tool->key(),
            ]
        );

        $risk = get_string(
            'crm_admin_tool_risk_' .
                $tool->risk_level(),
            'local_subscriptions'
        );

        $lastcontent = get_string(
            'crm_admin_tool_never_run',
            'local_subscriptions'
        );

        if ($last) {
            $lastcontent = get_string(
                'crm_admin_tool_last_run',
                'local_subscriptions',
                (object)[
                    'date' => userdate(
                        (int)$last->timecreated
                    ),
                    'status' => get_string(
                        'crm_admin_tool_status_' .
                            $last->status,
                        'local_subscriptions'
                    ),
                ]
            );
        }

        $header =
            html_writer::span(
                s($tool->icon()),
                'crm-admin-tool-icon',
                [
                    'aria-hidden' => 'true',
                ]
            ) .
            html_writer::tag(
                'h3',
                s($tool->title()),
                [
                    'class' =>
                        'crm-admin-tool-title',
                ]
            );

        $body =
            html_writer::div(
                $header,
                'crm-admin-tool-header'
            );

        $body .= html_writer::tag(
            'p',
            s($tool->description()),
            [
                'class' =>
                    'crm-admin-tool-description',
            ]
        );

        $body .= html_writer::div(
            html_writer::span(
                s($risk),
                'crm-admin-tool-risk ' .
                'is-' .
                $tool->risk_level()
            ) .
            html_writer::span(
                s($lastcontent),
                'crm-admin-tool-last-run'
            ),
            'crm-admin-tool-meta'
        );

        $body .= html_writer::link(
            $actionurl,
            get_string(
                'crm_admin_tool_open',
                'local_subscriptions'
            ),
            [
                'class' =>
                    'btn btn-primary',
            ]
        );

        return html_writer::tag(
            'article',
            $body,
            [
                'class' => 'crm-admin-tool-card',
            ]
        );
    }

    public static function render_history(
        array $runs,
        AdminToolRegistry $registry,
        AdminToolActorRepository $actorrepository
    ): string {
        if ($runs === []) {
            return html_writer::div(
                get_string(
                    'crm_admin_tool_history_empty',
                    'local_subscriptions'
                ),
                'alert alert-info'
            );
        }

        $actorids = [];

        foreach ($runs as $run) {
            $actorid = (int)$run->actorid;

            if ($actorid > 0) {
                $actorids[$actorid] = $actorid;
            }
        }

        $actors =
            $actorrepository->find_by_ids(
                array_values($actorids)
            );

        $headings = [
            get_string(
                'crm_admin_tool_history_date',
                'local_subscriptions'
            ),
            get_string(
                'crm_admin_tool_history_tool',
                'local_subscriptions'
            ),
            get_string(
                'crm_admin_tool_history_actor',
                'local_subscriptions'
            ),
            get_string(
                'crm_admin_tool_history_status',
                'local_subscriptions'
            ),
            get_string(
                'crm_admin_tool_history_duration',
                'local_subscriptions'
            ),
        ];

        $head = '';

        foreach ($headings as $heading) {
            $head .= html_writer::tag(
                'th',
                s($heading),
                ['scope' => 'col']
            );
        }

        $rows = '';

        foreach ($runs as $run) {
            $actorid = (int)$run->actorid;

            $actor =
                $actors[$actorid] ??
                null;

            if (
                $actor &&
                empty($actor->deleted)
            ) {
                $actorname = fullname($actor);
            } else {
                $actorname = get_string(
                    'crm_admin_tool_unknown_actor',
                    'local_subscriptions',
                    $actorid
                );
            }

            $duration = $run->durationms !== null
                ? (int)$run->durationms . ' ms'
                : '—';

            $tool =
                $registry->find(
                    (string)$run->toolkey
                );

            $tooltitle = $tool
                ? $tool->title()
                : (string)$run->toolkey;

            $statuslabel = get_string(
                'crm_admin_tool_status_' .
                    $run->status,
                'local_subscriptions'
            );

            $row =
                html_writer::tag(
                    'td',
                    html_writer::span(
                        userdate(
                            (int)$run->timecreated,
                            get_string(
                                'strftimedatetimeshort',
                                'langconfig'
                            )
                        ),
                        'crm-admin-tool-history-date'
                    )
                )
                . html_writer::tag(
                    'td',
                    html_writer::div(
                        html_writer::span(
                            s($tool
                                ? $tool->icon()
                                : '•'),
                            'crm-admin-tool-history-icon',
                            ['aria-hidden' => 'true']
                        )
                        . html_writer::span(
                            s($tooltitle),
                            'crm-admin-tool-history-tool'
                        ),
                        'crm-admin-tool-history-tool-cell'
                    )
                )
                . html_writer::tag(
                    'td',
                    s($actorname)
                )
                . html_writer::tag(
                    'td',
                    html_writer::span(
                        s($statuslabel),
                        'crm-admin-tool-history-status '
                        . 'is-' . s((string)$run->status)
                    )
                )
                . html_writer::tag(
                    'td',
                    html_writer::span(
                        s($duration),
                        'crm-admin-tool-history-duration'
                    )
                );

            $rows .= html_writer::tag(
                'tr',
                $row
            );
        }

        return html_writer::tag(
            'div',
            html_writer::tag(
                'table',
                html_writer::tag(
                    'thead',
                    html_writer::tag(
                        'tr',
                        $head
                    )
                ) .
                html_writer::tag(
                    'tbody',
                    $rows
                ),
                [
                    'class' =>
                        'table ' .
                        'crm-admin-tool-history-table',
                ]
            ),
            [
                'class' =>
                    'table-responsive',
            ]
        );
    }
}