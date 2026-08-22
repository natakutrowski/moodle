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

    private const ALLOWED_DIRECTIONS = [
        '',
        'inbound',
        'outbound',
        'draft',
    ];

    private const ALLOWED_READ_STATES = [
        '',
        'unread',
        'read',
    ];

    private const ALLOWED_ATTACHMENT_STATES = [
        '',
        'with',
        'without',
    ];

    private const ALLOWED_PERIODS = [
        '',
        'today',
        '7days',
        '30days',
        '90days',
        'custom',
    ];

    private const ALLOWED_FOLDERS = [
        'inbox',
        'sent',
        'drafts',
        'archive',
        'trash',
        'all',
    ];

    public function __construct(
        public readonly string $query,
        public readonly string $status,
        public readonly string $priority,
        public readonly string $assignment,
        public readonly string $match,
        public readonly string $direction,
        public readonly string $readstate,
        public readonly string $attachmentstate,
        public readonly string $period,
        public readonly string $datefrom,
        public readonly string $dateto,
        public readonly int $accountid,
        public readonly string $folder,
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

        $direction = optional_param(
            'direction',
            '',
            PARAM_ALPHA
        );

        $readstate = optional_param(
            'readstate',
            '',
            PARAM_ALPHA
        );

        $attachmentstate = optional_param(
            'attachments',
            '',
            PARAM_ALPHA
        );

        $period = optional_param(
            'period',
            '',
            PARAM_ALPHANUMEXT
        );

        $datefrom = self::normalize_date(
            optional_param(
                'datefrom',
                '',
                PARAM_RAW_TRIMMED
            )
        );

        $dateto = self::normalize_date(
            optional_param(
                'dateto',
                '',
                PARAM_RAW_TRIMMED
            )
        );

        $folder = optional_param(
            'folder',
            'inbox',
            PARAM_ALPHA
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
            in_array(
                $direction,
                self::ALLOWED_DIRECTIONS,
                true
            ) ? $direction : '',
            in_array(
                $readstate,
                self::ALLOWED_READ_STATES,
                true
            ) ? $readstate : '',
            in_array(
                $attachmentstate,
                self::ALLOWED_ATTACHMENT_STATES,
                true
            ) ? $attachmentstate : '',
            in_array(
                $period,
                self::ALLOWED_PERIODS,
                true
            ) ? $period : '',
            $datefrom,
            $dateto,
            max(0, optional_param(
                'accountid',
                0,
                PARAM_INT
            )),
            in_array(
                $folder,
                self::ALLOWED_FOLDERS,
                true
            ) ? $folder : 'inbox',
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
            $this->direction,
            $this->readstate,
            $this->attachmentstate,
            $this->period,
            $this->datefrom,
            $this->dateto,
            $this->accountid,
            $this->folder,
            $this->unreadonly,
            $this->teamid,
            max(0, $page),
            $this->perpage
        );
    }

    public function with_folder(
        string $folder
    ): self {
        if (
            !in_array(
                $folder,
                self::ALLOWED_FOLDERS,
                true
            )
        ) {
            $folder = 'inbox';
        }

        return new self(
            $this->query,
            $this->status,
            $this->priority,
            $this->assignment,
            $this->match,
            $this->direction,
            $this->readstate,
            $this->attachmentstate,
            $this->period,
            $this->datefrom,
            $this->dateto,
            $this->accountid,
            $folder,
            $this->unreadonly,
            $this->teamid,
            0,
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

        if ($this->direction !== '') {
            $params['direction'] =
                $this->direction;
        }

        if ($this->readstate !== '') {
            $params['readstate'] =
                $this->readstate;
        }

        if ($this->attachmentstate !== '') {
            $params['attachments'] =
                $this->attachmentstate;
        }

        if ($this->period !== '') {
            $params['period'] =
                $this->period;
        }

        if (
            $this->period === 'custom'
            && $this->datefrom !== ''
        ) {
            $params['datefrom'] = $this->datefrom;
        }

        if (
            $this->period === 'custom'
            && $this->dateto !== ''
        ) {
            $params['dateto'] = $this->dateto;
        }

        if ($this->accountid > 0) {
            $params['accountid'] =
                $this->accountid;
        }

        if ($this->folder !== 'inbox') {
            $params['folder'] =
                $this->folder;
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
        $params = $this->url_params(false);

        unset(
            $params['datefrom'],
            $params['dateto']
        );

        return count($params);
    }

    private static function normalize_date(
        string $date
    ): string {
        $date = trim($date);

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return '';
        }

        [$year, $month, $day] = array_map(
            'intval',
            explode('-', $date)
        );

        return checkdate($month, $day, $year)
            ? $date
            : '';
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