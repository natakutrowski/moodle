<?php

namespace local_subscriptions\crm\inbox\dto;

defined('MOODLE_INTERNAL') || die();

final class InboxThreadCriteria {

    private const ALLOWED_STATUSES = [
        '',
        'open',
        'pending',
        'resolved',
        'closed',
        'spam',
    ];

    private const ALLOWED_PRIORITIES = [
        '',
        'low',
        'normal',
        'high',
        'urgent',
    ];

    private const ALLOWED_ASSIGNMENTS = [
        '',
        'mine',
        'unassigned',
        'team',
    ];

    private const ALLOWED_MATCHES = [
        '',
        'matched',
        'unmatched',
        'ambiguous',
    ];

    public function __construct(
        public readonly string $query,
        public readonly string $status,
        public readonly string $priority,
        public readonly string $assignment,
        public readonly string $match,
        public readonly bool $unreadonly,
        public readonly int $teamid,
        public readonly int $page,
        public readonly int $perpage
    ) {
    }

    public static function from_request(): self {
        $status = optional_param(
            'status',
            '',
            PARAM_ALPHANUMEXT
        );

        $priority = optional_param(
            'priority',
            '',
            PARAM_ALPHANUMEXT
        );

        $assignment = optional_param(
            'assignment',
            '',
            PARAM_ALPHANUMEXT
        );

        $match = optional_param(
            'match',
            '',
            PARAM_ALPHANUMEXT
        );

        return new self(
            trim(optional_param(
                'q',
                '',
                PARAM_TEXT
            )),
            in_array(
                $status,
                self::ALLOWED_STATUSES,
                true
            ) ? $status : '',
            in_array(
                $priority,
                self::ALLOWED_PRIORITIES,
                true
            ) ? $priority : '',
            in_array(
                $assignment,
                self::ALLOWED_ASSIGNMENTS,
                true
            ) ? $assignment : '',
            in_array(
                $match,
                self::ALLOWED_MATCHES,
                true
            ) ? $match : '',
            optional_param(
                'unreadonly',
                0,
                PARAM_BOOL
            ) === 1,
            max(0, optional_param(
                'teamid',
                0,
                PARAM_INT
            )),
            max(0, optional_param(
                'page',
                0,
                PARAM_INT
            )),
            self::normalize_perpage(
                optional_param(
                    'perpage',
                    25,
                    PARAM_INT
                )
            )
        );
    }

    public function offset(): int {
        return $this->page * $this->perpage;
    }

    public function with_page(int $page): self {
        return new self(
            $this->query,
            $this->status,
            $this->priority,
            $this->assignment,
            $this->match,
            $this->unreadonly,
            $this->teamid,
            max(0, $page),
            $this->perpage
        );
    }

    public function url_params(
        bool $includepage = true
    ): array {
        $params = [];

        if ($this->query !== '') {
            $params['q'] = $this->query;
        }

        if ($this->status !== '') {
            $params['status'] = $this->status;
        }

        if ($this->priority !== '') {
            $params['priority'] = $this->priority;
        }

        if ($this->assignment !== '') {
            $params['assignment'] = $this->assignment;
        }

        if ($this->match !== '') {
            $params['match'] = $this->match;
        }

        if ($this->unreadonly) {
            $params['unreadonly'] = 1;
        }

        if ($this->teamid > 0) {
            $params['teamid'] = $this->teamid;
        }

        if ($this->perpage !== 25) {
            $params['perpage'] = $this->perpage;
        }

        if ($includepage && $this->page > 0) {
            $params['page'] = $this->page;
        }

        return $params;
    }

    public function active_filter_count(): int {
        return count(
            $this->url_params(false)
        );
    }

    private static function normalize_perpage(
        int $perpage
    ): int {
        return in_array(
            $perpage,
            [25, 50, 100],
            true
        ) ? $perpage : 25;
    }
}