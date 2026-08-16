<?php

declare(strict_types=1);

namespace local_subscriptions\crm\commerce\rendering;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use moodle_url;

/**
 * Unified campaign monitoring UI for Offers & access.
 */
final class CommerceOffersAccessCampaignRenderer {
    /**
     * @param array<int,array{key:string,label:string,detail:string,state:string}> $steps
     */
    public static function workflow(array $steps, string $kind): string {
        $items = [];

        foreach ($steps as $step) {
            $state = in_array(
                $step['state'],
                ['complete', 'active', 'pending', 'error'],
                true
            ) ? $step['state'] : 'pending';

            $icon = match ($state) {
                'complete' => 'fa-check',
                'active' => 'fa-circle-o-notch fa-spin',
                'error' => 'fa-exclamation-triangle',
                default => 'fa-circle-o',
            };

            $items[] = html_writer::div(
                html_writer::span(
                    html_writer::tag('i', '', [
                        'class' => 'fa ' . $icon,
                        'aria-hidden' => 'true',
                    ]),
                    'crm-offers-access-campaign-step-marker is-' . $state
                )
                . html_writer::div(
                    html_writer::div(
                        s($step['label']),
                        'crm-offers-access-campaign-step-label'
                    )
                    . html_writer::div(
                        s($step['detail']),
                        'crm-offers-access-campaign-step-detail'
                    ),
                    'crm-offers-access-campaign-step-copy'
                ),
                'crm-offers-access-campaign-step is-' . $state
            );
        }

        return html_writer::div(
            implode('', $items),
            'crm-offers-access-campaign-workflow is-' . $kind
        );
    }

    /**
     * @param array<int,array{label:string,value:string|int,class?:string}> $metrics
     */
    public static function metrics(array $metrics): string {
        $items = [];
        foreach ($metrics as $metric) {
            $class = 'crm-offers-access-campaign-metric';
            if (!empty($metric['class'])) {
                $class .= ' ' . $metric['class'];
            }
            $items[] = html_writer::div(
                html_writer::div(
                    s((string)$metric['value']),
                    'crm-offers-access-campaign-metric-value'
                )
                . html_writer::div(
                    s($metric['label']),
                    'crm-offers-access-campaign-metric-label'
                ),
                $class
            );
        }

        return html_writer::div(
            implode('', $items),
            'crm-offers-access-campaign-metrics'
        );
    }

    public static function communication(
        int $queued,
        int $sent,
        int $failed,
        moodle_url $journalurl,
        ?moodle_url $studiourl = null,
        ?moodle_url $previewurl = null
    ): string {
        $content = self::metrics([
            [
                'label' => get_string(
                    'commerce_offers_access_campaign_mail_queued',
                    'local_subscriptions'
                ),
                'value' => $queued,
            ],
            [
                'label' => get_string(
                    'commerce_offers_access_campaign_mail_sent',
                    'local_subscriptions'
                ),
                'value' => $sent,
                'class' => 'is-success',
            ],
            [
                'label' => get_string(
                    'commerce_offers_access_campaign_mail_failed',
                    'local_subscriptions'
                ),
                'value' => $failed,
                'class' => $failed > 0 ? 'is-error' : '',
            ],
        ]);

        $actions = html_writer::link(
            $journalurl,
            get_string(
                'commerce_offers_access_campaign_open_mail_journal',
                'local_subscriptions'
            ),
            ['class' => 'btn btn-sm btn-outline-primary']
        );
        if ($studiourl !== null) {
            $actions .= html_writer::link(
                $studiourl,
                get_string(
                    'commerce_offers_access_config_open_mailstudio',
                    'local_subscriptions'
                ),
                [
                    'class' => 'btn btn-sm btn-outline-secondary ms-2',
                    'target' => '_blank',
                    'rel' => 'noopener',
                ]
            );
        }

        $preview = '';
        if ($previewurl !== null) {
            $preview = html_writer::tag(
                'details',
                html_writer::tag(
                    'summary',
                    html_writer::tag('i', '', [
                        'class' => 'fa fa-eye',
                        'aria-hidden' => 'true',
                    ])
                    . html_writer::span(
                        get_string(
                            'commerce_grant_campaign_email_preview',
                            'local_subscriptions'
                        ),
                        'crm-offers-access-campaign-preview-title'
                    ),
                    ['class' => 'crm-offers-access-campaign-preview-summary']
                )
                . html_writer::div(
                    html_writer::tag('iframe', '', [
                        'src' => $previewurl->out(false),
                        'title' => get_string(
                            'commerce_grant_campaign_email_preview',
                            'local_subscriptions'
                        ),
                        'class' => 'crm-offers-access-campaign-preview-frame',
                        'loading' => 'lazy',
                    ]),
                    'crm-offers-access-campaign-preview-body'
                ),
                ['class' => 'crm-offers-access-campaign-preview']
            );
        }

        return html_writer::div(
            html_writer::div(
                html_writer::tag('i', '', [
                    'class' => 'fa fa-envelope-o',
                    'aria-hidden' => 'true',
                ])
                . html_writer::tag(
                    'strong',
                    get_string(
                        'commerce_offers_access_campaign_communication_title',
                        'local_subscriptions'
                    )
                ),
                'crm-offers-access-campaign-panel-title'
            )
            . $content
            . html_writer::div(
                $actions,
                'crm-offers-access-campaign-panel-actions'
            )
            . $preview,
            'crm-offers-access-campaign-panel'
        );
    }

    public static function technical(string $title, string $content): string {
        return html_writer::tag(
            'details',
            html_writer::tag(
                'summary',
                html_writer::tag('i', '', [
                    'class' => 'fa fa-code',
                    'aria-hidden' => 'true',
                ])
                . html_writer::span(
                    s($title),
                    'crm-offers-access-campaign-technical-title'
                ),
                ['class' => 'crm-offers-access-campaign-technical-summary']
            )
            . html_writer::div(
                $content,
                'crm-offers-access-campaign-technical-body'
            ),
            ['class' => 'crm-offers-access-campaign-technical']
        );
    }

    private function __construct() {}
}
