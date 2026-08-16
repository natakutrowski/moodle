<?php
declare(strict_types=1);

namespace local_subscriptions\crm\commerce\rendering;

defined('MOODLE_INTERNAL') || die();

use html_writer;

/** Visual workflow for Offers & access operations. */
final class CommerceOffersAccessWorkflowRenderer {
    public const BENEFICIARIES = 'beneficiaries';
    public const CONFIGURATION = 'configuration';
    public const VERIFICATION = 'verification';
    public const EXECUTION = 'execution';

    public static function render(string $active, string $kind, string $audience): string {
        $steps = [
            self::BENEFICIARIES => ['1', 'commerce_offers_access_workflow_beneficiaries', 'fa-users'],
            self::CONFIGURATION => ['2',
                $kind === 'grant' ? 'commerce_offers_access_workflow_access' : 'commerce_offers_access_workflow_offer',
                $kind === 'grant' ? 'fa-key' : 'fa-tag'],
            self::VERIFICATION => ['3', 'commerce_offers_access_workflow_verification', 'fa-check-square-o'],
            self::EXECUTION => ['4', 'commerce_offers_access_workflow_execution', 'fa-play-circle-o'],
        ];

        $order = array_keys($steps);
        $activeindex = array_search($active, $order, true);
        $activeindex = $activeindex === false ? 0 : $activeindex;
        $items = [];

        foreach ($steps as $key => [$number, $labelkey, $icon]) {
            $index = array_search($key, $order, true);
            $state = $index < $activeindex ? ' is-complete' : ($index === $activeindex ? ' is-active' : '');
            $numberhtml = $index < $activeindex
                ? html_writer::tag('i', '', ['class' => 'fa fa-check', 'aria-hidden' => 'true'])
                : s($number);
            $meta = $key === self::BENEFICIARIES
                ? html_writer::div(
                    get_string(
                        $audience === 'many'
                            ? 'commerce_offers_access_workflow_many'
                            : 'commerce_offers_access_workflow_one',
                        'local_subscriptions'
                    ),
                    'crm-offers-access-workflow-meta'
                )
                : '';

            $items[] = html_writer::div(
                html_writer::span($numberhtml, 'crm-offers-access-workflow-number')
                . html_writer::div(
                    html_writer::div(
                        html_writer::tag('i', '', ['class' => 'fa ' . $icon, 'aria-hidden' => 'true'])
                        . get_string($labelkey, 'local_subscriptions'),
                        'crm-offers-access-workflow-label'
                    ) . $meta,
                    'crm-offers-access-workflow-copy'
                ),
                'crm-offers-access-workflow-step' . $state
            );
        }

        return html_writer::div(
            implode('', $items),
            'crm-offers-access-workflow is-' . ($kind === 'grant' ? 'grant' : 'offer')
        );
    }

    private function __construct() {}
}
