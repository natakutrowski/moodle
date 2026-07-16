<?php

namespace local_subscriptions\crm\work\dto;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\work\domain\WorkItemPriority;
use local_subscriptions\crm\work\domain\WorkItemStatus;
use local_subscriptions\crm\work\domain\WorkItemType;

final class WorkItemCriteria {

    public function __construct(
        public readonly string $query = '',
        public readonly string $status = '',
        public readonly string $priority = '',
        public readonly string $type = '',
        public readonly int $assigneduserid = 0,
        public readonly int $assignedteamid = 0,
        public readonly int $targetuserid = 0,
        public readonly bool $unassignedonly = false,
        public readonly bool $overdueonly = false,
        public readonly bool $mineonly = false,
        public readonly int $page = 0,
        public readonly int $perpage = 25
    ) {
    }

    public static function from_request(): self {
        $status = optional_param('status', '', PARAM_ALPHANUMEXT);
        $priority = optional_param('priority', '', PARAM_ALPHANUMEXT);
        $type = optional_param('type', '', PARAM_ALPHANUMEXT);

        if ($status !== '' && !WorkItemStatus::is_valid($status)) {
            $status = '';
        }

        if ($priority !== '' && !WorkItemPriority::is_valid($priority)) {
            $priority = '';
        }

        if ($type !== '' && !WorkItemType::is_valid($type)) {
            $type = '';
        }

        return new self(
            trim(optional_param('q', '', PARAM_TEXT)),
            $status,
            $priority,
            $type,
            max(0, optional_param('assigneduserid', 0, PARAM_INT)),
            max(0, optional_param('assignedteamid', 0, PARAM_INT)),
            max(0, optional_param('targetuserid', 0, PARAM_INT)),
            optional_param('unassignedonly', 0, PARAM_BOOL) === 1,
            optional_param('overdueonly', 0, PARAM_BOOL) === 1,
            optional_param('mineonly', 0, PARAM_BOOL) === 1,
            max(0, optional_param('page', 0, PARAM_INT)),
            min(100, max(10, optional_param('perpage', 25, PARAM_INT)))
        );
    }

    public function url_params(bool $withpage = true): array {
        $params = [];

        foreach ([
            'q' => $this->query,
            'status' => $this->status,
            'priority' => $this->priority,
            'type' => $this->type,
            'assigneduserid' => $this->assigneduserid,
            'assignedteamid' => $this->assignedteamid,
            'targetuserid' => $this->targetuserid,
            'unassignedonly' => $this->unassignedonly ? 1 : 0,
            'overdueonly' => $this->overdueonly ? 1 : 0,
            'mineonly' => $this->mineonly ? 1 : 0,
            'perpage' => $this->perpage,
        ] as $key => $value) {
            if ($value !== '' && $value !== 0) {
                $params[$key] = $value;
            }
        }

        if ($withpage && $this->page > 0) {
            $params['page'] = $this->page;
        }

        return $params;
    }
}