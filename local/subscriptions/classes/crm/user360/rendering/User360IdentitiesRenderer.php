<?php

declare(strict_types=1);

namespace local_subscriptions\crm\user360\rendering;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\crm\user360\guest\User360GuestCheckoutRecoveryRenderer;
use local_subscriptions\crm\user360\identity\User360IdentityGraphRenderer;
use local_subscriptions\crm\user360\merge\User360MergeHistoryRenderer;

/**
 * N11.3D consolidated identity surface for User360.
 */
final class User360IdentitiesRenderer {

    public static function render(\stdClass $profile): string {
        if (empty($profile->user)) {
            return '';
        }

        $main = self::identity_graph($profile);
        $sidebar = '';

        if (!empty($profile->iscommerceguest)) {
            $recovery = self::guest_recovery($profile);
            if ($recovery !== '') {
                $sidebar .= self::panel(
                    'fa fa-user-plus',
                    get_string(
                        'crm_user360_n113d_account_linking',
                        'local_subscriptions'
                    ),
                    get_string(
                        'crm_user360_n113d_account_linking_help',
                        'local_subscriptions'
                    ),
                    $recovery,
                    'linking'
                );
            }
        } else if (!empty($profile->user->id)) {
            $history = User360MergeHistoryRenderer::render(
                (int)$profile->user->id
            );

            if ($history !== '') {
                $sidebar .= self::panel(
                    'fa fa-random',
                    get_string(
                        'crm_user360_n113d_merge_history',
                        'local_subscriptions'
                    ),
                    get_string(
                        'crm_user360_n113d_merge_history_help',
                        'local_subscriptions'
                    ),
                    $history,
                    'merge-history'
                );
            }
        }

        $safety = self::safety_notice();

        $gridclass = 'crm-user360-n113d-identities-grid';
        if ($sidebar === '') {
            $gridclass .= ' is-single-column';
        }

        $grid = html_writer::div(
            html_writer::div(
                $main,
                'crm-user360-n113d-identities-main'
            )
            . ($sidebar !== ''
                ? html_writer::div(
                    $sidebar,
                    'crm-user360-n113d-identities-sidebar'
                )
                : ''),
            $gridclass
        );

        return html_writer::tag(
            'section',
            self::heading($profile)
            . $safety
            . $grid,
            [
                'id' => 'user360-identities',
                'class' => 'crm-user360-n113d-identities',
                'aria-labelledby' => 'user360-identities-title',
            ]
        );
    }

    private static function heading(\stdClass $profile): string {
        $email = trim((string)($profile->user->email ?? ''));

        $status = !empty($profile->iscommerceguest)
            ? get_string(
                'crm_user360_n113d_commerce_identity',
                'local_subscriptions'
            )
            : get_string(
                'crm_user360_n113d_moodle_identity',
                'local_subscriptions'
            );

        return html_writer::div(
            html_writer::div(
                html_writer::tag(
                    'h2',
                    get_string(
                        'crm_user360_n113d_identities_title',
                        'local_subscriptions'
                    ),
                    [
                        'id' => 'user360-identities-title',
                        'class' => 'crm-user360-n113d-title',
                    ]
                )
                . html_writer::div(
                    get_string(
                        'crm_user360_n113d_identities_intro',
                        'local_subscriptions'
                    ),
                    'crm-user360-n113d-intro'
                ),
                'crm-user360-n113d-heading-copy'
            )
            . html_writer::div(
                html_writer::span(
                    $status,
                    'crm-user360-n113d-identity-status'
                )
                . ($email !== ''
                    ? html_writer::span(
                        s($email),
                        'crm-user360-n113d-identity-email'
                    )
                    : ''),
                'crm-user360-n113d-heading-meta'
            ),
            'crm-user360-n113d-heading'
        );
    }

    private static function safety_notice(): string {
        return html_writer::div(
            html_writer::tag('i', '', [
                'class' => 'fa fa-shield',
                'aria-hidden' => 'true',
            ])
            . html_writer::span(
                get_string(
                    'crm_user360_n113d_identity_safety_help',
                    'local_subscriptions'
                )
            ),
            'crm-user360-n113e-safety-notice'
        );
    }

    private static function identity_graph(\stdClass $profile): string {
        if (!empty($profile->user->id)) {
            $content = User360IdentityGraphRenderer::render(
                (int)$profile->user->id
            );
        } else {
            $email = trim((string)($profile->user->email ?? ''));
            $content = $email !== ''
                ? User360IdentityGraphRenderer::render_email($email)
                : '';
        }

        if ($content === '') {
            $content = html_writer::div(
                get_string(
                    'crm_user360_n113d_no_identity_data',
                    'local_subscriptions'
                ),
                'text-muted'
            );
        }

        return self::panel(
            'fa fa-id-card-o',
            get_string(
                'crm_user360_n113d_known_identities',
                'local_subscriptions'
            ),
            get_string(
                'crm_user360_n113d_known_identities_help',
                'local_subscriptions'
            ),
            $content,
            'graph'
        );
    }

    private static function guest_recovery(\stdClass $profile): string {
        $email = trim((string)($profile->user->email ?? ''));
        if ($email === '') {
            return '';
        }

        return User360GuestCheckoutRecoveryRenderer::render(
            $email
        );
    }

    private static function panel(
        string $icon,
        string $title,
        string $help,
        string $content,
        string $key
    ): string {
        if (trim($content) === '') {
            return '';
        }

        $heading = html_writer::tag('i', '', [
            'class' => $icon,
            'aria-hidden' => 'true',
        ])
        . html_writer::div(
            html_writer::tag(
                'h3',
                s($title),
                ['class' => 'crm-user360-n113d-panel-title']
            )
            . ($help !== ''
                ? html_writer::div(
                    s($help),
                    'crm-user360-n113d-panel-help'
                )
                : ''),
            'crm-user360-n113d-panel-heading-copy'
        );

        return html_writer::tag(
            'section',
            html_writer::div(
                $heading,
                'crm-user360-n113d-panel-heading'
            )
            . html_writer::div(
                $content,
                'crm-user360-n113d-panel-body'
            ),
            [
                'class' =>
                    'crm-user360-n113d-panel crm-user360-n113d-' . $key,
            ]
        );
    }
}
