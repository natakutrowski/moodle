<?php

namespace local_subscriptions\dashboard\services;

defined('MOODLE_INTERNAL') || die();

use moodle_url;
use local_subscriptions\subscription_config;
use local_subscriptions\dashboard\issues\DashboardIssue;
use local_subscriptions\dashboard\repositories\DashboardIssueRepository;

final class DashboardIssueService {

    public function __construct(
        private readonly DashboardIssueRepository $repository = new DashboardIssueRepository()
    ) {
    }

    public function load(): array {
        return [
            $this->pending_digital_payments(),
            $this->failed_digital_payments(),
            $this->email_errors(),
            $this->expired_download_tokens(),
        ];
    }

    private function pending_digital_payments(): DashboardIssue {
        return new DashboardIssue(
            'pending_digital_payment',
            get_string(
                'dashboard_issue_pending_digital_title',
                'local_subscriptions'
            ),
            get_string(
                'dashboard_issue_pending_digital_desc',
                'local_subscriptions'
            ),
            $this->repository->count_pending_digital_payments(),
            new moodle_url(
                subscription_config::digital_purchases_admin_page(),
                [
                    'status' => 'pending',
                ]
            ),
            new moodle_url(
                subscription_config::digital_purchases_admin_page(),
                [
                    'status' => 'pending',
                ]
            ),
            get_string(
                'dashboard_issue_open_queue',
                'local_subscriptions'
            ),
            'info'
        );
    }

    private function failed_digital_payments(): DashboardIssue {
        return new DashboardIssue(
            'failed_digital_payment',
            get_string(
                'dashboard_issue_failed_digital_title',
                'local_subscriptions'
            ),
            get_string(
                'dashboard_issue_failed_digital_desc',
                'local_subscriptions'
            ),
            $this->repository->count_failed_digital_payments(),
            new moodle_url(
                subscription_config::digital_purchases_admin_page(),
                [
                    'status' => 'failed',
                ]
            ),
            new moodle_url(
                subscription_config::digital_purchases_admin_page(),
                [
                    'status' => 'failed',
                ]
            ),
            get_string(
                'dashboard_issue_review_queue',
                'local_subscriptions'
            ),
            'danger'
        );
    }

    private function email_errors(): DashboardIssue {
        return new DashboardIssue(
            'paid_email_error',
            get_string(
                'dashboard_issue_email_error_title',
                'local_subscriptions'
            ),
            get_string(
                'dashboard_issue_email_error_desc',
                'local_subscriptions'
            ),
            $this->repository->count_paid_email_errors(),
            new moodle_url(
                subscription_config::digital_purchases_admin_page(),
                [
                    'issue' => 'email_error',
                ]
            ),
            new moodle_url(
                subscription_config::digital_purchases_admin_page(),
                [
                    'issue' => 'email_error',
                ]
            ),
            get_string(
                'dashboard_issue_open_purchases',
                'local_subscriptions'
            ),
            'warning'
        );
    }

    private function expired_download_tokens(): DashboardIssue {
        return new DashboardIssue(
            'expired_paid_download_token',
            get_string(
                'dashboard_issue_expired_token_title',
                'local_subscriptions'
            ),
            get_string(
                'dashboard_issue_expired_token_desc',
                'local_subscriptions'
            ),
            $this->repository->count_expired_paid_download_tokens(),
            new moodle_url(
                subscription_config::digital_purchases_admin_page(),
                [
                    'issue' => 'expired_token',
                ]
            ),
            new moodle_url(
                subscription_config::digital_purchases_admin_page(),
                [
                    'issue' => 'expired_token',
                ]
            ),
            get_string(
                'dashboard_issue_open_purchases',
                'local_subscriptions'
            ),
            'warning'
        );
    }
}