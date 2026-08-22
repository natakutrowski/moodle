<?php

declare(strict_types=1);

namespace local_subscriptions\crm\inbox\rendering;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use moodle_url;

/**
 * Human-readable CRM Inbox diagnostics dashboard.
 */
final class InboxDiagnosticsRenderer {

    public static function render(array $result, moodle_url $refreshurl, moodle_url $inboxurl): string {
        $checks = $result['checks'] ?? [];
        $errors = (int)($result['errors'] ?? 0);
        $success = !empty($result['success']);

        $output = html_writer::start_div('crm-inbox-diagnostics-dashboard');
        $output .= self::status_banner($success, $errors, $refreshurl, $inboxurl);
        $output .= self::overview($checks, $result['account'] ?? null);
        $output .= self::folder_sync($result['folderstatus'] ?? []);
        $output .= self::metrics($result['metrics'] ?? []);
        $output .= self::operational_health(
            $result['operational'] ?? []
        );
        $output .= self::technical_checks($checks, $errors);
        $output .= html_writer::end_div();

        return $output;
    }

    private static function status_banner(
        bool $success,
        int $errors,
        moodle_url $refreshurl,
        moodle_url $inboxurl
    ): string {
        $title = get_string(
            $success ? 'crm_inbox_diagnostics_operational' : 'crm_inbox_diagnostics_attention',
            'local_subscriptions'
        );
        $description = get_string(
            $success ? 'crm_inbox_diagnostics_operational_desc' : 'crm_inbox_diagnostics_attention_desc',
            'local_subscriptions',
            $errors
        );

        $actions = html_writer::link(
            $refreshurl,
            '↻ ' . get_string('crm_inbox_diagnostics_rerun', 'local_subscriptions'),
            ['class' => 'btn btn-primary']
        );
        $actions .= html_writer::link(
            $inboxurl,
            '← ' . get_string('crm_inbox_diagnostics_open_inbox', 'local_subscriptions'),
            ['class' => 'btn btn-outline-secondary']
        );

        return html_writer::div(
            html_writer::div(
                html_writer::tag('strong', ($success ? '✓ ' : '⚠ ') . $title)
                . html_writer::div($description, 'crm-inbox-diagnostics-status-description'),
                'crm-inbox-diagnostics-status-copy'
            )
            . html_writer::div($actions, 'crm-inbox-diagnostics-status-actions'),
            'crm-inbox-diagnostics-status ' . ($success ? 'is-success' : 'is-error')
        );
    }

    private static function overview(array $checks, ?object $account): string {
        $imap = self::check_by_key($checks, 'imap_connection');
        $smtp = self::check_by_key($checks, 'smtp_connection');
        $enabled = self::check_by_key($checks, 'account_enabled');
        $credentials = self::check_by_key($checks, 'credentials');
        $extension = self::check_by_key($checks, 'imap_extension');
        $folders = self::check_by_key($checks, 'imap_folders');
        $tablechecks = array_values(array_filter(
            $checks,
            static fn(array $check): bool => str_starts_with((string)($check['key'] ?? ''), 'table_')
        ));
        $tablesok = $tablechecks !== [] && count(array_filter(
            $tablechecks,
            static fn(array $check): bool => !empty($check['success'])
        )) === count($tablechecks);

        $cards = self::health_card('📥', get_string('crm_inbox_diagnostics_imap', 'local_subscriptions'), $imap);
        $cards .= self::health_card('📤', get_string('crm_inbox_diagnostics_smtp', 'local_subscriptions'), $smtp);
        $cards .= self::health_card('👤', get_string('crm_inbox_diagnostics_account', 'local_subscriptions'), $enabled);
        $cards .= self::health_card(
            '🗄',
            get_string('crm_inbox_diagnostics_database', 'local_subscriptions'),
            ['success' => $tablesok]
        );

        $accountemail = $account && !empty($account->email) ? s((string)$account->email) : '—';
        $accountrows = self::detail_row(
            get_string('crm_inbox_diagnostics_email_account', 'local_subscriptions'),
            $accountemail
        );
        $accountrows .= self::detail_row(
            get_string('crm_inbox_diagnostics_account_state', 'local_subscriptions'),
            self::state_badge($enabled)
        );
        $accountrows .= self::detail_row(
            get_string('crm_inbox_diagnostics_credentials', 'local_subscriptions'),
            self::state_badge($credentials)
        );

        $serverrows = self::detail_row('PHP IMAP', self::state_badge($extension));
        $serverrows .= self::detail_row('IMAP', self::state_badge($imap));
        $serverrows .= self::detail_row('SMTP', self::state_badge($smtp));

        if ($folders !== null) {
            $serverrows .= self::detail_row(
                get_string('crm_inbox_diagnostics_folders', 'local_subscriptions'),
                s((string)($folders['message'] ?? '—'))
            );
        }

        return html_writer::div($cards, 'crm-inbox-diagnostics-health-grid')
            . html_writer::div(
                self::detail_panel(
                    get_string('crm_inbox_diagnostics_account_panel', 'local_subscriptions'),
                    $accountrows
                )
                . self::detail_panel(
                    get_string('crm_inbox_diagnostics_server_panel', 'local_subscriptions'),
                    $serverrows
                ),
                'crm-inbox-diagnostics-detail-grid'
            );
    }

    private static function health_card(string $icon, string $label, ?array $check): string {
        $ok = $check !== null && !empty($check['success']);
        $state = get_string(
            $ok ? 'crm_inbox_diagnostics_ok' : 'crm_inbox_diagnostics_problem',
            'local_subscriptions'
        );

        return html_writer::div(
            html_writer::div($icon, 'crm-inbox-diagnostics-health-icon')
            . html_writer::div(
                html_writer::div(s($label), 'crm-inbox-diagnostics-health-label')
                . html_writer::tag('strong', $state, ['class' => 'crm-inbox-diagnostics-health-state']),
                'crm-inbox-diagnostics-health-copy'
            ),
            'crm-inbox-diagnostics-health-card ' . ($ok ? 'is-success' : 'is-error')
        );
    }

    private static function detail_panel(string $title, string $rows): string {
        return html_writer::tag(
            'section',
            html_writer::tag('h3', s($title), ['class' => 'crm-inbox-diagnostics-panel-title']) . $rows,
            ['class' => 'crm-inbox-diagnostics-panel']
        );
    }

    private static function detail_row(string $label, string $value): string {
        return html_writer::div(
            html_writer::span(s($label), 'crm-inbox-diagnostics-detail-label')
            . html_writer::span($value, 'crm-inbox-diagnostics-detail-value'),
            'crm-inbox-diagnostics-detail-row'
        );
    }

    private static function folder_sync(
        array $folders
    ): string {
        if ($folders === []) {
            return '';
        }

        $rows = '';

        foreach ($folders as $folder) {
            $enabled = !empty($folder['enabled']);
            $incrementalat = (int)($folder['incrementalat'] ?? 0);
            $reconciledat = (int)($folder['reconciledat'] ?? 0);

            $rows .= html_writer::div(
                html_writer::div(
                    html_writer::tag(
                        'strong',
                        s(
                            get_string(
                                'crm_inbox_o1_folder_' .
                                    (string)$folder['type'],
                                'local_subscriptions'
                            )
                        )
                    )
                    . html_writer::span(
                        s((string)$folder['folder']),
                        'crm-inbox-o1-folder-path'
                    ),
                    'crm-inbox-o1-folder-name'
                )
                . html_writer::span(
                    get_string(
                        $enabled
                            ? 'crm_inbox_o1_sync_enabled'
                            : 'crm_inbox_o1_sync_disabled',
                        'local_subscriptions'
                    ),
                    'crm-inbox-o1-folder-state ' .
                        ($enabled ? 'is-enabled' : 'is-disabled')
                )
                . html_writer::div(
                    self::sync_time(
                        get_string(
                            'crm_inbox_o1_incremental',
                            'local_subscriptions'
                        ),
                        $incrementalat
                    )
                    . self::sync_time(
                        get_string(
                            'crm_inbox_o1_reconciliation',
                            'local_subscriptions'
                        ),
                        $reconciledat
                    ),
                    'crm-inbox-o1-folder-times'
                )
                . html_writer::div(
                    get_string(
                        'crm_inbox_o1_reconciliation_counts',
                        'local_subscriptions',
                        (object)[
                            'checked' =>
                                (int)($folder['checked'] ?? 0),
                            'updated' =>
                                (int)($folder['updated'] ?? 0),
                            'moved' =>
                                (int)($folder['moved'] ?? 0),
                            'missing' =>
                                (int)($folder['missing'] ?? 0),
                        ]
                    ),
                    'crm-inbox-o1-folder-counts'
                ),
                'crm-inbox-o1-folder-row'
            );
        }

        return html_writer::tag(
            'section',
            html_writer::tag(
                'h2',
                get_string(
                    'crm_inbox_o1_mailbox_sync_title',
                    'local_subscriptions'
                ),
                [
                    'class' =>
                        'crm-inbox-diagnostics-section-title',
                ]
            )
            . html_writer::div(
                get_string(
                    'crm_inbox_o1_mailbox_sync_description',
                    'local_subscriptions'
                ),
                'crm-inbox-o1-sync-description'
            )
            . html_writer::div(
                $rows,
                'crm-inbox-o1-folder-grid'
            ),
            [
                'class' =>
                    'crm-inbox-o1-mailbox-sync',
            ]
        );
    }

    private static function sync_time(
        string $label,
        int $timestamp
    ): string {
        $value = $timestamp > 0
            ? userdate(
                $timestamp,
                get_string(
                    'strftimedatetimeshort',
                    'langconfig'
                )
            )
            : get_string(
                'crm_inbox_o1_never',
                'local_subscriptions'
            );

        return html_writer::span(
            html_writer::tag('strong', s($label))
            . ' '
            . s($value),
            'crm-inbox-o1-sync-time'
        );
    }

    private static function metrics(array $metrics): string {
        if ($metrics === []) {
            return '';
        }

        $definitions = [
            'threads' => ['💬', 'crm_inbox_diagnostics_metric_threads'],
            'messages' => ['✉', 'crm_inbox_diagnostics_metric_messages'],
            'contacts' => ['👥', 'crm_inbox_diagnostics_metric_contacts'],
            'unmatchedcontacts' => ['?', 'crm_inbox_diagnostics_metric_unmatched'],
            'ambiguouscontacts' => ['⚠', 'crm_inbox_diagnostics_metric_ambiguous'],
            'pendingattachments' => ['📎', 'crm_inbox_diagnostics_metric_pending_attachments'],
            'failedattachments' => ['!', 'crm_inbox_diagnostics_metric_failed_attachments'],
        ];

        $cards = '';
        foreach ($definitions as $key => [$icon, $stringkey]) {
            if (!array_key_exists($key, $metrics)) {
                continue;
            }
            $cards .= html_writer::div(
                html_writer::span($icon, 'crm-inbox-diagnostics-metric-icon')
                . html_writer::div(
                    html_writer::tag('strong', (string)(int)$metrics[$key])
                    . html_writer::span(get_string($stringkey, 'local_subscriptions')),
                    'crm-inbox-diagnostics-metric-copy'
                ),
                'crm-inbox-diagnostics-metric-card'
            );
        }

        return html_writer::tag(
            'section',
            html_writer::tag(
                'h2',
                get_string('crm_inbox_diagnostics_metrics', 'local_subscriptions'),
                ['class' => 'crm-inbox-diagnostics-section-title']
            ) . html_writer::div($cards, 'crm-inbox-diagnostics-metrics-grid'),
            ['class' => 'crm-inbox-diagnostics-metrics']
        );
    }

    private static function operational_health(
        array $operational
    ): string {
        if ($operational === []) {
            return '';
        }

        $lastsuccess = (int)(
            $operational['lastsuccessat']
            ?? 0
        );

        $cards = '';

        $definitions = [
            [
                'value' =>
                    (int)($operational['failed24h'] ?? 0),
                'label' =>
                    'crm_inbox_o14_failed_syncs',
                'problem' =>
                    (int)($operational['failed24h'] ?? 0) > 0,
            ],
            [
                'value' =>
                    (int)($operational['partial24h'] ?? 0),
                'label' =>
                    'crm_inbox_o14_partial_syncs',
                'problem' =>
                    (int)($operational['partial24h'] ?? 0) > 0,
            ],
            [
                'value' =>
                    (int)($operational['stalerunning'] ?? 0),
                'label' =>
                    'crm_inbox_o14_stale_runs',
                'problem' =>
                    (int)($operational['stalerunning'] ?? 0) > 0,
            ],
            [
                'value' =>
                    (int)($operational['duplicateidentities'] ?? 0),
                'label' =>
                    'crm_inbox_o14_duplicate_identities',
                'problem' =>
                    (int)($operational['duplicateidentities'] ?? 0) > 0,
            ],
            [
                'value' =>
                    (int)($operational['orphanremote'] ?? 0),
                'label' =>
                    'crm_inbox_o14_orphan_remote',
                'problem' =>
                    (int)($operational['orphanremote'] ?? 0) > 0,
            ],
            [
                'value' =>
                    (int)($operational['sentcopyfailures24h'] ?? 0),
                'label' =>
                    'crm_inbox_o14_sentcopy_failures',
                'problem' =>
                    (int)($operational['sentcopyfailures24h'] ?? 0) > 0,
            ],
        ];

        foreach ($definitions as $definition) {
            $cards .= html_writer::div(
                html_writer::tag(
                    'strong',
                    (string)$definition['value']
                )
                . html_writer::span(
                    get_string(
                        $definition['label'],
                        'local_subscriptions'
                    )
                ),
                'crm-inbox-o14-health-card '
                . (
                    $definition['problem']
                        ? 'is-problem'
                        : 'is-ok'
                )
            );
        }

        $freshness = $lastsuccess > 0
            ? userdate(
                $lastsuccess,
                get_string(
                    'strftimedatetimeshort',
                    'langconfig'
                )
            )
            : get_string(
                'crm_inbox_o1_never',
                'local_subscriptions'
            );

        $summary = html_writer::div(
            html_writer::span(
                get_string(
                    'crm_inbox_o14_last_success',
                    'local_subscriptions'
                ),
                'crm-inbox-o14-last-label'
            )
            . html_writer::tag(
                'strong',
                s($freshness)
            ),
            'crm-inbox-o14-last-success'
        );

        $logs = '';

        foreach (
            array_values(
                $operational['recentlogs']
                ?? []
            )
            as $log
        ) {
            $status = trim(
                (string)$log->status
            );

            $logs .= html_writer::div(
                html_writer::span(
                    s(
                        (string)(
                            $log->folder
                            ?: $log->synctype
                        )
                    ),
                    'crm-inbox-o14-log-folder'
                )
                . html_writer::span(
                    s($status),
                    'crm-inbox-o14-log-status '
                    . 'is-' . clean_param(
                        $status,
                        PARAM_ALPHANUMEXT
                    )
                )
                . html_writer::span(
                    userdate(
                        (int)$log->startedat,
                        get_string(
                            'strftimedatetimeshort',
                            'langconfig'
                        )
                    ),
                    'crm-inbox-o14-log-date'
                )
                . html_writer::span(
                    get_string(
                        'crm_inbox_o14_log_counts',
                        'local_subscriptions',
                        (object)[
                            'fetched' =>
                                (int)$log->fetchedcount,
                            'created' =>
                                (int)$log->createdcount,
                            'errors' =>
                                (int)$log->errorcount,
                        ]
                    ),
                    'crm-inbox-o14-log-counts'
                ),
                'crm-inbox-o14-log-row'
            );
        }

        return html_writer::tag(
            'section',
            html_writer::tag(
                'h2',
                get_string(
                    'crm_inbox_o14_operational_title',
                    'local_subscriptions'
                ),
                [
                    'class' =>
                        'crm-inbox-diagnostics-section-title',
                ]
            )
            . html_writer::div(
                get_string(
                    'crm_inbox_o14_operational_desc',
                    'local_subscriptions'
                ),
                'crm-inbox-o14-operational-desc'
            )
            . $summary
            . html_writer::div(
                $cards,
                'crm-inbox-o14-health-grid'
            )
            . (
                $logs !== ''
                    ? html_writer::tag(
                        'details',
                        html_writer::tag(
                            'summary',
                            get_string(
                                'crm_inbox_o14_recent_runs',
                                'local_subscriptions'
                            )
                        )
                        . html_writer::div(
                            $logs,
                            'crm-inbox-o14-log-list'
                        ),
                        [
                            'class' =>
                                'crm-inbox-o14-recent-runs',
                        ]
                    )
                    : ''
            ),
            [
                'class' =>
                    'crm-inbox-o14-operational',
            ]
        );
    }

    private static function technical_checks(array $checks, int $errors): string {
        $items = '';
        foreach ($checks as $check) {
            $ok = !empty($check['success']);
            $items .= html_writer::div(
                html_writer::span($ok ? '✓' : '✕', 'crm-inbox-diagnostics-check-icon')
                . html_writer::span(s((string)($check['message'] ?? '')), 'crm-inbox-diagnostics-check-message'),
                'crm-inbox-diagnostics-check ' . ($ok ? 'is-success' : 'is-error')
            );
        }

        $summary = get_string(
            $errors === 0 ? 'crm_inbox_diagnostics_checks_all_ok' : 'crm_inbox_diagnostics_checks_errors',
            'local_subscriptions',
            $errors === 0 ? count($checks) : $errors
        );

        return html_writer::tag(
            'details',
            html_writer::tag(
                'summary',
                html_writer::tag('strong', get_string('crm_inbox_diagnostics_technical', 'local_subscriptions'))
                . html_writer::span($summary, 'crm-inbox-diagnostics-check-summary')
            )
            . html_writer::div($items, 'crm-inbox-diagnostics-check-grid'),
            [
                'class' => 'crm-inbox-diagnostics-technical ' . ($errors > 0 ? 'has-errors' : ''),
                ...($errors > 0 ? ['open' => 'open'] : []),
            ]
        );
    }

    private static function state_badge(?array $check): string {
        $ok = $check !== null && !empty($check['success']);
        return html_writer::span(
            ($ok ? '✓ ' : '✕ ') . get_string(
                $ok ? 'crm_inbox_diagnostics_ok' : 'crm_inbox_diagnostics_problem',
                'local_subscriptions'
            ),
            'crm-inbox-diagnostics-state-badge ' . ($ok ? 'is-success' : 'is-error')
        );
    }

    private static function check_by_key(array $checks, string $key): ?array {
        foreach ($checks as $check) {
            if (($check['key'] ?? null) === $key) {
                return $check;
            }
        }
        return null;
    }
}
